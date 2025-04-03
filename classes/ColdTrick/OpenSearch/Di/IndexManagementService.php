<?php

namespace ColdTrick\OpenSearch\Di;

use Elgg\Exceptions\UnexpectedValueException;
use OpenSearch\Exception\OpenSearchExceptionInterface;

/**
 * Manage the OpenSearch Indexes
 */
class IndexManagementService extends BaseClientService {

	/**
	 * {@inheritdoc}
	 */
	public static function name(): string {
		return 'opensearch.indexmanagementservice';
	}
	
	/**
	 * Get information about the index status
	 *
	 * @return null|array
	 */
	public function getIndexStatus(): ?array {
		if (!$this->isClientReady()) {
			return null;
		}
		
		try {
			$status = $this->getClient()->indices()->stats();
			
			return elgg_extract('indices', $status, false);
		} catch (OpenSearchExceptionInterface $e) {
			$this->logger->error($e);
		}
		
		return null;
	}
	
	/**
	 * Get information about the cluster the client is connected to
	 *
	 * @return null|array
	 */
	public function getClusterInformation(): ?array {
		if (!$this->isClientReady()) {
			return null;
		}
		
		try {
			return $this->getClient()->info();
		} catch (OpenSearchExceptionInterface $e) {
			$this->logger->error($e);
		}
		
		return null;
	}
	
	/**
	 * Get the index name used for the Elgg search index
	 *
	 * @param string $index_prefix the index prefix
	 *
	 * @return string|null
	 */
	public function getElggIndex(string $index_prefix): ?string {
		$indices = $this->getIndexStatus();
		
		foreach ($indices as $name => $status) {
			if (!preg_match("/^{$index_prefix}(_[0-9]+)?$/", $name)) {
				continue;
			}
			
			$aliases = $this->getAliases($name);
			if (in_array("{$index_prefix}_read", $aliases) && in_array("{$index_prefix}_write", $aliases)) {
				return $name;
			}
		}
		
		return null;
	}
	
	/**
	 * Check if an index exists with the given name
	 *
	 * @param string $index index name to check
	 *
	 * @return bool
	 */
	public function indexExists(string $index): bool {
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			return $this->getClient()->indices()->exists([
				'index' => $index,
			]);
		} catch (OpenSearchExceptionInterface $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Check if an index has the given alias
	 *
	 * @param string $index the index to check
	 * @param string $alias the alias
	 *
	 * @return bool
	 */
	public function indexHasAlias(string $index, string $alias): bool {
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			return $this->getClient()->indices()->existsAlias([
				'index' => $index,
				'name' => $alias,
			]);
		} catch (OpenSearchExceptionInterface $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Add an alias to an index
	 *
	 * @param string $index the index to add to
	 * @param string $alias the alias
	 *
	 * @return bool
	 */
	public function addAlias(string $index, string $alias): bool {
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			$response = $this->getClient()->indices()->putAlias([
				'index' => $index,
				'name' => $alias,
			]);
			return (bool) elgg_extract('acknowledged', $response, false);
		} catch (OpenSearchExceptionInterface $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Get all the aliases of an index
	 *
	 * @param string $index the index to check
	 *
	 * @return null|string[]
	 */
	public function getAliases(string $index): ?array {
		if (!$this->isClientReady()) {
			return null;
		}
		
		try {
			$response = $this->getClient()->indices()->getAlias([
				'index' => $index,
			]);
			
			$aliases = elgg_extract('aliases', elgg_extract($index, $response, []), []);
			return array_keys($aliases);
		} catch (OpenSearchExceptionInterface $e) {
			$this->logger->error($e);
		}
		
		return null;
	}
	
	/**
	 * Delete an alias from an index
	 *
	 * @param string $index the index to delete from
	 * @param string $alias the alias
	 *
	 * @return bool
	 */
	public function deleteAlias(string $index, string $alias): bool {
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			$response = $this->getClient()->indices()->deleteAlias([
				'index' => $index,
				'name' => $alias,
			]);
			return (bool) elgg_extract('acknowledged', $response, false);
		} catch (OpenSearchExceptionInterface $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Flush an index
	 *
	 * @param string $index index name to flush
	 *
	 * @return bool
	 */
	public function flush(string $index): bool {
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			$response = $this->getClient()->indices()->flush([
				'index' => $index,
			]);
			
			$failed_shards = elgg_extract('failed', elgg_extract('_shards', $response, []), 0);
			return empty($failed_shards);
		} catch (OpenSearchExceptionInterface $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Delete an index
	 *
	 * @param string $index index name to delete
	 *
	 * @return bool
	 */
	public function delete(string $index): bool {
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			$response = $this->getClient()->indices()->delete([
				'index' => $index,
			]);
			return (bool) elgg_extract('acknowledged', $response, false);
		} catch (OpenSearchExceptionInterface $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Create a new index
	 *
	 * @param string $index index name
	 *
	 * @return bool
	 */
	public function create(string $index): bool {
		if (!$this->isClientReady()) {
			return false;
		}
		
		$config = $this->getIndexConfiguration($index);
		
		try {
			$response = $this->getClient()->indices()->create($config);
			return (bool) elgg_extract('acknowledged', $response, false);
		} catch (OpenSearchExceptionInterface $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Add mapping to an index
	 *
	 * @param string $index index name
	 *
	 * @return bool
	 */
	public function addMapping(string $index): bool {
		if (!$this->isClientReady()) {
			return false;
		}
		
		$config = $this->getMappingConfiguration($index);
		
		try {
			$response = $this->getClient()->indices()->putMapping($config);
			return (bool) elgg_extract('acknowledged', $response, false);
		} catch (OpenSearchExceptionInterface $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Reindex data from one index into another index
	 *
	 * @param string $from_index source index
	 * @param string $to_index   destination index
	 *
	 * @return null|array
	 */
	public function reindex(string $from_index, string $to_index): ?array {
		if (!$this->isClientReady()) {
			return null;
		}
		
		try {
			return $this->getClient()->reindex([
				'body' => [
					'source' => [
						'index' => $from_index,
					],
					'dest' => [
						'index' => $to_index,
					],
				],
				'wait_for_completion' => true,
			]);
		} catch (OpenSearchExceptionInterface $e) {
			$this->logger->error($e);
		}
		
		return null;
	}
	
	/**
	 * Get the index configuration
	 *
	 * @param string $index index name
	 *
	 * @return array
	 * @throws UnexpectedValueException
	 */
	protected function getIndexConfiguration(string $index): array {
		
		$params = [
			'index' => $index,
		];
		
		$return = [
			'index' => $index,
			'body' => [
				'settings' => [
					'analysis' => [
						'analyzer' => [
							'default' => [
								'tokenizer' => 'standard',
								'filter' => [
									'lowercase',
									'asciifolding',
								],
							],
							'case_insensitive_sort' => [
								'tokenizer' => 'keyword',
								'filter' => [
									'lowercase',
								],
							],
						],
						'normalizer' => [
							'case_insensitive' => [
								'type' => 'custom',
								'filter' => [
									'lowercase',
									'asciifolding',
								],
							],
						],
					],
				],
			],
		];
		
		$return = $this->events->triggerResults('config:index', 'opensearch', $params, $return);
		if (!is_array($return)) {
			throw new UnexpectedValueException(elgg_echo('opensearch:index_management:exception:config:index'));
		}
		
		return $return;
	}
	
	/**
	 * Get the mapping configuration
	 *
	 * @param string $index index name
	 *
	 * @return array
	 * @throws UnexpectedValueException
	 */
	protected function getMappingConfiguration(string $index): array {
		
		$params = [
			'index' => $index,
		];
		
		$return = [
			'index' => $index,
			'body' => [
				'dynamic_templates' => [
					[
						'strings' => [
							'match_mapping_type' => 'string',
							'mapping' => [
								'type' => 'text',
								'fields' => [
									'raw' => [
										'type' => 'keyword',
										'normalizer' => 'case_insensitive',
										'ignore_above' => 8191,
									]
								]
							]
						],
					],
					[
						'metadata_strings' => [
							'path_match' => 'metadata.*',
							'mapping' => [
								'type' => 'text',
							],
						],
					],
				],
				'properties' => [
					'description' => [
						'type' => 'text',
					],
					'indexed_type' => [
						'type' => 'keyword',
					],
					'metadata' => [
						'type' => 'nested',
						'include_in_parent' => true,
					],
					'name' => [
						'type' => 'text',
						'copy_to' => 'title',
					],
					'relationships' => [
						'type' => 'nested',
					],
					'tags' => [
						'type' => 'text',
						'analyzer' => 'case_insensitive_sort',
					],
					'title' => [
						'type' => 'text',
						'analyzer' => 'default',
						'fields' => [
							'raw' => [
								'type' => 'keyword',
								'normalizer' => 'case_insensitive',
							],
							// this field is used for suggestions, make sure it's present in custom mappings
							// you can change the analyzer to what you want
							'suggestion' => [
								'type' => 'text',
								'analyzer' => 'default',
							],
						],
					],
				],
			],
		];
		
		$return = $this->events->triggerResults('config:mapping', 'opensearch', $params, $return);
		if (!is_array($return)) {
			throw new UnexpectedValueException(elgg_echo('opensearch:index_management:exception:config:mapping'));
		}
		
		return $return;
	}
}
