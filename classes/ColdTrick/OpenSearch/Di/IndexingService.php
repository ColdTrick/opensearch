<?php

namespace ColdTrick\OpenSearch\Di;

use Elgg\Database\QueryBuilder;
use Elgg\Cli\Progress;
use OpenSearch\Common\Exceptions\OpenSearchException;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Indexing service
 */
class IndexingService extends BaseClientService {

	/**
	 * @var string[] Supported indexing type
	 */
	const INDEXING_TYPES = [
		'no_index_ts',
		'update',
		'reindex',
	];
	
	protected ?ProgressBar $progress_bar = null;
	
	/**
	 * @var int[] GUIDs to skip during indexing
	 */
	protected array $skip_guids;
	
	/**
	 * {@inheritdoc}
	 */
	public static function name(): string {
		return 'opensearch.indexingservice';
	}
	
	/**
	 * Add or update entities in the OpenSearch index
	 *
	 * @param array $entities an array of \ElggEntity or of guids
	 *
	 * @return null|array
	 */
	public function addEntitiesToIndex(array $entities = []): ?array {
		if (empty($entities) || !$this->isClientReady()) {
			return null;
		}
		
		$params = [
			'body' => [],
		];
		foreach ($entities as $entity) {
			if (is_numeric($entity)) {
				// also able to provide guids
				$entity = get_entity($entity);
			}
			
			if (!$entity instanceof \ElggEntity) {
				continue;
			}
			
			// Set basic entity information for indexing
			$params['body'][] = [
				'index' => [
					'_index' => $this->getWriteAlias(),
					'_id' => $entity->guid,
				],
			];
			
			// get full entity information to put into index
			$params['body'][] = $this->getBodyFromEntity($entity);
		}
		
		if (empty($params)) {
			return null;
		}
		
		try {
			return $this->getClient()->bulk($params);
		} catch (OpenSearchException $e) {
			$this->logger->error($e);
		}
		
		return null;
	}
	
	/**
	 * Deletes documents in bulk from index
	 *
	 * @return bool
	 */
	public function bulkDeleteDocuments(): bool {
		if (!$this->isClientReady()) {
			return false;
		}
		
		$documents = opensearch_get_documents_for_deletion();
		if (empty($documents)) {
			// nothing to delete
			return true;
		}
		
		while (!empty($documents)) {
			$params = [
				'body' => [],
			];
			foreach ($documents as $document) {
				$params['body'][] = ['delete' => $document];
			}
			
			try {
				$result = $this->getClient()->bulk($params);
				if (empty($result)) {
					return false;
				}
				
				$items = elgg_extract('items', $result);
				foreach ($items as $action) {
					$status = elgg_extract('status', $action['delete']);
					$guid = (int) elgg_extract('_id', $action['delete']);
					
					if ($status === 200 || $status === 404) {
						// document was removed
						opensearch_remove_document_for_deletion($guid);
					} else {
						// some error occurred, reschedule delete
						opensearch_add_document_for_deletion($guid, $documents[$guid], '+1 hour');
					}
				}
			} catch (OpenSearchException $e) {
				$this->logger->error($e);
				return false;
			}
			
			// get next batch
			$documents = opensearch_get_documents_for_deletion();
		}
		
		return true;
	}
	
	/**
	 * Bulk index documents based on a given type
	 *
	 * @param array $params indexing parameters
	 *                      - (string) type: the type of documents to index
	 *                      - (int) max_run_time: the maximum number of seconds to spend on the indexing action (default: 30)
	 *                      - (\Elgg\Cli\Progress) progress: a provided cli progress for nice output (default: false)
	 *
	 * @return bool
	 */
	public function bulkIndexDocuments(array $params): bool {
		$defaults = [
			'max_run_time' => 30,
			'progress' => false,
		];
		$params = array_merge($defaults, $params);
		
		$type = elgg_extract('type', $params);
		if (!in_array($type, self::INDEXING_TYPES)) {
			return false;
		}
		
		return elgg_call(ELGG_IGNORE_ACCESS, function() use ($params) {
			// how long can this task run
			$max_run_time = (int) elgg_extract('max_run_time', $params);
			if (!empty($max_run_time)) {
				set_time_limit($max_run_time + 10);
			} else {
				set_time_limit(0);
			}
			
			// get indexing options
			$type = elgg_extract('type', $params);
			$options = opensearch_get_bulk_options($type);
			if (empty($options)) {
				return false;
			}
			
			// track progress
			$starttime = time();
			$progress = elgg_extract('progress', $params);
			if ($progress instanceof Progress) {
				$count = elgg_count_entities($options);
				
				$this->progress_bar = $progress->start(elgg_echo("opensearch:progress:start:{$type}"), $count);
			}
			
			$this->skip_guids = [];
			$options['wheres'][] = function(QueryBuilder $qb, $main_alias) {
				if (empty($this->skip_guids)) {
					return;
				}
				
				return $qb->compare("{$main_alias}.guid", 'NOT IN', $this->skip_guids);
			};
			
			$index_entities = [];
			$batch_size = (int) elgg_extract('batch_size', $options, 25);
			
			/* @var $entities \ElggBatch */
			$entities = elgg_get_entities($options);
			
			/* @var $entity \ElggEntity */
			foreach ($entities as $index => $entity) {
				// is this entity prevented from being indexed
				$event_params = [
					'entity' => $entity,
				];
				
				if ((bool) elgg_trigger_event_results('index:entity:prevent', 'opensearch', $event_params, false)) {
					$this->markEntityDone($entity);
				} else {
					// not prevented so add to the next batch
					$index_entities[] = $entity;
				}
				
				if (count($index_entities) > 0 && (($index + 1) % $batch_size) === 0) {
					// process a batch of allowed entities
					$this->processBulkIndexEntities($index_entities);
					// reset
					$index_entities = [];
					$this->clearCaches();
				}
				
				if (!empty($max_run_time) && (time() - $starttime) >= $max_run_time) {
					break;
				}
			}
			
			if (!empty($index_entities)) {
				$this->processBulkIndexEntities($index_entities);
			}
			
			// stop progress bar
			if ($progress instanceof Progress) {
				$progress->finish($this->progress_bar);
			}
			
			return true;
		});
	}
	
	/**
	 * Get body (data) for indexing of an entity
	 *
	 * @param \ElggEntity $entity entity
	 *
	 * @return array
	 */
	protected function getBodyFromEntity(\ElggEntity $entity): array {
		elgg_push_context('search:index');
		
		$result = (array) $entity->toObject();
		
		elgg_pop_context();
		
		return $result;
	}
	
	/**
	 * Mark an entity as indexed
	 *
	 * @param \ElggEntity $entity the entity to mark
	 *
	 * @return void
	 */
	protected function markEntityDone(\ElggEntity $entity): void {
		elgg_call(ELGG_DISABLE_SYSTEM_LOG, function() use ($entity) {
			$entity->{OPENSEARCH_INDEXED_NAME} = time();
			$entity->invalidateCache();
		});
		
		// advance progress bar
		if ($this->progress_bar instanceof ProgressBar) {
			$this->progress_bar->advance();
		}
	}
	
	/**
	 * Process a batch of entities for indexing
	 *
	 * @param \ElggEntity[] $entities entities to index
	 *
	 * @return void
	 */
	protected function processBulkIndexEntities(array $entities): void {
		$result = $this->addEntitiesToIndex($entities);
		if (empty($result)) {
			return;
		}
		
		$items = elgg_extract('items', $result);
		foreach ($items as $item) {
			$guid = (int) elgg_extract('_id', elgg_extract('index', $item));
			$status = elgg_extract('status', elgg_extract('index', $item));
			
			$success = ($status >= 200) && ($status < 300);
			if (!$success) {
				$this->skip_guids[] = $guid;
				
				$error = elgg_extract('error', elgg_extract('index', $item));
				elgg_log("OpenSearch failed to index {$guid} with error [{$status}][{$error['type']}]: {$error['reason']}", LogLevel::WARNING);
				continue;
			}
			
			$entity = get_entity($guid);
			if (!$entity instanceof \ElggEntity) {
				$this->skip_guids[] = $guid;
				continue;
			}
			
			$this->markEntityDone($entity);
		}
	}
	
	/**
	 * Clear caches to save memory
	 *
	 * @return void
	 */
	protected function clearCaches(): void {
		_elgg_services()->accessCache->clear();
		_elgg_services()->entityCache->clear();
		_elgg_services()->metadataCache->clear();
		_elgg_services()->queryCache->clear();
	}
}
