<?php

namespace ColdTrick\OpenSearch\Cli;

use ColdTrick\OpenSearch\Di\IndexManagementService;
use Elgg\Cli\Command;

/**
 * CLI command to rebuild the search index
 */
class Rebuild extends Command {
	
	protected IndexManagementService $service;
	
	protected \ElggPlugin $plugin;
	
	/**
	 * {@inheritdoc}
	 */
	protected function configure() {
		$this->setName('opensearch:rebuild')
			->setDescription(elgg_echo('opensearch:cli:rebuild:description'));
	}
	
	/**
	 * {@inheritdoc}
	 */
	protected function command() {
		$quiet = $this->option('quiet');
		$service = $this->getService();
		$plugin = $this->getPlugin();
		
		if (!$service->isClientReady()) {
			$this->write(elgg_echo('opensearch:cli:error:client'), 'error');
			return self::FAILURE;
		}
		
		// this could take a while
		set_time_limit(0);
		
		// log helper
		$write = function(string $action, bool $result = true) use ($quiet) {
			if ($quiet) {
				return;
			}
			
			if (!$result) {
				$this->write(elgg_echo("opensearch:cli:rebuild:{$action}:error"), 'error');
				return;
			}
			
			$this->write(elgg_echo("opensearch:cli:rebuild:{$action}"));
		};
		
		// further preparation
		$index_prefix = $plugin->getSetting('index');
		$current_index = $service->getElggIndex($index_prefix);
		if (empty($current_index)) {
			$write('current_index', false);
			return self::FAILURE;
		}
		
		// stop indexing (plugin settings)
		$write('disable_indexing');
		$current_sync = $plugin->getSetting('sync');
		$plugin->setSetting('sync', 'no');
		
		// create new index
		$new_index_name = $index_prefix . '_' . time();
		
		// add index configuration
		$result = $service->create($new_index_name);
		$write('create', $result);
		if (!$result) {
			return self::FAILURE;
		}
		
		// add mappings
		$result = $service->addMapping($new_index_name);
		$write('mapping', $result);
		if (!$result) {
			return self::FAILURE;
		}
		
		// copy existing data to new index (API reindex)
		$write('reindex_start');
		$result = $service->reindex($current_index, $new_index_name);
		$write('reindex', $result !== false);
		if ($result === false) {
			return self::FAILURE;
		}
		
		// add read/write alias to new index
		$result = $service->addAlias($new_index_name, "{$index_prefix}_read");
		$write('add_alias:read', $result);
		if (!$result) {
			return self::FAILURE;
		}
		
		$result = $service->addAlias($new_index_name, "{$index_prefix}_write");
		$write('add_alias:write', $result);
		if (!$result) {
			return self::FAILURE;
		}
		
		// remove read/write alias from old index
		$result = $service->deleteAlias($current_index, "{$index_prefix}_read");
		$write('remove_alias:read', $result);
		if (!$result) {
			return self::FAILURE;
		}
		
		$result = $service->deleteAlias($current_index, "{$index_prefix}_write");
		$write('remove_alias:write', $result);
		if (!$result) {
			return self::FAILURE;
		}
		
		// enable indexing (plugin settings)
		$write('enable_indexing');
		$plugin->setSetting('sync', $current_sync);
		
		// remove old index
		$result = $service->delete($current_index);
		$write('delete', $result);
		if (!$result) {
			return self::FAILURE;
		}
		
		return self::SUCCESS;
	}
	
	/**
	 * Get the indexing service
	 *
	 * @return IndexManagementService
	 */
	protected function getService(): IndexManagementService {
		if (!isset($this->service)) {
			$this->service = IndexManagementService::instance();
		}
		
		return $this->service;
	}
	
	/**
	 * Get the OpenSearch Elgg plugin
	 *
	 * @return \ElggPlugin
	 */
	protected function getPlugin(): \ElggPlugin {
		if (!isset($this->plugin)) {
			$this->plugin = elgg_get_plugin_from_id('opensearch');
		}
		
		return $this->plugin;
	}
}
