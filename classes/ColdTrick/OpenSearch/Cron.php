<?php

namespace ColdTrick\OpenSearch;

use ColdTrick\OpenSearch\Di\IndexingService;
use ColdTrick\OpenSearch\Di\SearchService;
use Elgg\Database\QueryBuilder;
use OpenSearch\Common\Exceptions\OpenSearchException;

/**
 * Cron handler
 */
class Cron {
	
	/**
	 * Listen to the minute cron in order to sync data to OpenSearch
	 *
	 * @param \Elgg\Event $event 'cron', 'minute'
	 *
	 * @return void
	 */
	public static function minuteSync(\Elgg\Event $event): void {
		if (elgg_get_plugin_setting('sync', 'opensearch') !== 'yes') {
			// sync not enabled
			return;
		}
		
		$service = IndexingService::instance();
		if (!$service->isClientReady()) {
			return;
		}
		
		$max_run_time = 30;
		
		// delete first
		echo 'Starting OpenSearch indexing: delete' . PHP_EOL;
		elgg_log('Starting OpenSearch indexing: delete', 'NOTICE');
		
		$service->bulkDeleteDocuments();
		
		// indexing actions
		foreach (IndexingService::INDEXING_TYPES as $action) {
			$batch_starttime = time();
			
			echo "Starting OpenSearch indexing: {$action}" . PHP_EOL;
			elgg_log("Starting OpenSearch indexing: {$action}", 'NOTICE');
			
			$service->bulkIndexDocuments([
				'type' => $action,
				'max_run_time' => $max_run_time,
			]);
			
			$max_run_time = $max_run_time - (time() - $batch_starttime);
			if ($max_run_time < 1) {
				break;
			}
		}
		
		echo 'Done with OpenSearch indexing' . PHP_EOL;
		elgg_log('Done with OpenSearch indexing', 'NOTICE');
	}
	
	/**
	 * Listen to the daily cron the do some cleanup jobs
	 *
	 * @param \Elgg\Event $event 'cron', 'daily'
	 *
	 * @return void
	 */
	public static function dailyCleanup(\Elgg\Event $event): void {
		if (elgg_get_plugin_setting('sync', 'opensearch') !== 'yes') {
			// sync isn't enabled, so don't validate
			return;
		}
		
		if (elgg_get_plugin_setting('cron_validate', 'opensearch') !== 'yes') {
			// validate isn't enabled
			return;
		}
		
		echo 'Starting OpenSearch cleanup: ES' . PHP_EOL;
		elgg_log('Starting OpenSearch cleanup: ES', 'NOTICE');
		
		// find documents in ES which don't exist in Elgg anymore
		self::cleanupOpenSearch();
		
		echo 'Starting OpenSearch cleanup: Elgg' . PHP_EOL;
		elgg_log('Starting OpenSearch cleanup: Elgg', 'NOTICE');
		
		// find entities in Elgg which should be in ES but aren't
		self::checkElggIndex();
		
		echo 'Done with OpenSearch cleanup' . PHP_EOL;
		elgg_log('Done with OpenSearch cleanup', 'NOTICE');
	}
	
	/**
	 * Find documents in OpenSearch which don't exist in Elgg anymore
	 *
	 * @return void
	 */
	protected static function cleanupOpenSearch(): void {
		$service = SearchService::instance();
		if (!$service->isClientReady()) {
			return;
		}
		
		// this could take a while
		set_time_limit(0);
		
		// prepare a search for all documents
		$search_params = [
			'index' => $service->getWriteAlias(),
			'scroll' => '2m',
			'body' => [
				'query' => [
					'match_all' => (object) [],
				],
			],
		];
		
		try {
			$scroll_setup = $service->rawSearch($search_params);
		} catch (OpenSearchException $e) {
			return;
		}
		
		// now scroll through all results
		$scroll_params = [
			'scroll' => '2m',
			'body' => [
				'scroll_id' => elgg_extract('_scroll_id', $scroll_setup),
			],
		];
		
		try {
			// ignore Elgg access
			elgg_call(ELGG_IGNORE_ACCESS, function() use ($service, &$scroll_params, $search_params) {
				$searchable_types = opensearch_get_registered_entity_types();
				
				while ($result = $service->scroll($scroll_params)) {
					// update scroll_id
					$new_scroll_id = elgg_extract('_scroll_id', $result);
					if (!empty($new_scroll_id)) {
						$scroll_params['body']['scroll_id'] = $new_scroll_id;
					}
					
					// process results
					$search_result = new SearchResult($result, $search_params);
					
					$opensearch_guids = $search_result->toGuids();
					if (empty($opensearch_guids)) {
						break;
					}
					
					// only validate searchable types, so unregistered types get removed from the index
					$elgg_guids = elgg_get_entities([
						'type_subtype_pairs' => $searchable_types ?: null,
						'guids' => $opensearch_guids,
						'limit' => false,
						'callback' => function ($row) {
							return (int) $row->guid;
						},
						'wheres' => [
							function (QueryBuilder $qb, $main_alias) {
								// banned users should not be indexed
								$md = $qb->joinMetadataTable($main_alias, 'guid', 'banned', 'left');
								
								return $qb->merge([
									$qb->compare("{$main_alias}.type", '!=', 'user', ELGG_VALUE_STRING),
									$qb->compare("{$md}.value", '=', 'no', ELGG_VALUE_STRING),
								], 'OR');
							},
						],
					]);
					
					$guids_not_in_elgg = array_diff($opensearch_guids, $elgg_guids);
					if (empty($guids_not_in_elgg)) {
						continue;
					}
					
					// remove all left-over documents
					foreach ($guids_not_in_elgg as $guid) {
						// need to get the hist from OpenSearch to get the type, since it's not in Elgg anymore
						$hit = $search_result->getHit($guid);
						
						opensearch_add_document_for_deletion($guid, [
							'_index' => $service->getWriteAlias(),
							'_type' => elgg_extract('_type', $hit),
							'_id' => $guid,
						]);
					}
				}
			});
		} catch (OpenSearchException $e) {
			// probably reached the end of the scroll
			// elgg_log('OpenSearch cleanup: ' . $e->getMessage(), 'ERROR');
		}
		
		// clear scroll
		try {
			$service->clearScroll($scroll_params);
		} catch (OpenSearchException $e) {
			// unable to clean, could be because we came to the end of the scroll
		}
	}
	
	/**
	 * Find entities in Elgg which aren't in OpenSearch but should be
	 *
	 * @return void
	 */
	protected static function checkElggIndex(): void {
		// this could take a while
		set_time_limit(0);
		
		// ignore access
		elgg_call(ELGG_IGNORE_ACCESS, function() {
			// find unindexed GUIDs
			$guids = [];
			$unindexed = [];
			
			$batch = elgg_get_entities([
				'type_subtype_pairs' => opensearch_get_registered_entity_types_for_search(),
				'limit' => false,
				'batch' => true,
				'metadata_name_value_pairs' => [
					'name' => OPENSEARCH_INDEXED_NAME,
					'value' => 0,
					'operand' => '>',
				],
				'callback' => function ($row) {
					return (int) $row->guid;
				},
			]);
			
			foreach ($batch as $guid) {
				$guids[] = $guid;
				
				if (count($guids) < 250) {
					continue;
				}
				
				$unindexed = array_merge($unindexed, self::findUnindexedGUIDs($guids));
				$guids = [];
			}
			
			if (!empty($guids)) {
				$unindexed = array_merge($unindexed, self::findUnindexedGUIDs($guids));
			}
			
			if (empty($unindexed)) {
				return;
			}
			
			// reindex entities
			// do this in chunks to prevent SQL-query limit hits
			$chunks = array_chunk($unindexed, 250);
			foreach ($chunks as $chunk) {
				$reindex = elgg_get_entities([
					'guids' => $chunk,
					'limit' => false,
					'batch' => true,
				]);
				/* @var $entity \ElggEntity */
				foreach ($reindex as $entity) {
					// mark for reindex
					elgg_call(ELGG_DISABLE_SYSTEM_LOG, function() use ($entity) {
						$entity->{OPENSEARCH_INDEXED_NAME} = 0;
					});
				}
			}
		});
	}
	
	/**
	 * Find Elgg GUIDs not present in OpenSearch
	 *
	 * @param int[] $guids Elgg GUIDs
	 *
	 * @return int[]
	 */
	protected static function findUnindexedGUIDs(array $guids = []): array {
		if (empty($guids)) {
			return [];
		}
		
		$service = SearchService::instance();
		if (!$service->isClientReady()) {
			return [];
		}
		
		$search_params = [
			'index' => $service->getWriteAlias(),
			'size' => count($guids),
			'body' => [
				'query' => [
					'bool' => [
						'filter' => [
							'terms' => [
								'guid' => $guids,
							],
						],
					],
				],
			],
		];
		
		try {
			$es_result = $service->rawSearch($search_params);
			
			// process results
			$search_result = new SearchResult($es_result, $search_params);
			
			$opensearch_guids = $search_result->toGuids();
			
			return array_diff($guids, $opensearch_guids);
		} catch (OpenSearchException $e) {
			// some error occurred
		}
		
		return [];
	}
}
