<?php

namespace ColdTrick\OpenSearch\Cli;

use ColdTrick\OpenSearch\Di\IndexingService;
use Elgg\Cli\Command;
use Elgg\Cli\Progress;

/**
 * CLI command to sync the Elgg data to OpenSearch
 */
class Sync extends Command {
	
	/**
	 * {@inheritdoc}
	 */
	protected function configure() {
		$this->setName('opensearch:sync')
			->setDescription(elgg_echo('opensearch:cli:sync:description'));
	}
	
	/**
	 * {@inheritdoc}
	 */
	protected function command() {
		$quite = $this->option('quiet');
		$service = IndexingService::instance();
		
		if (!$service->isClientReady()) {
			$this->error(elgg_echo('opensearch:cli:error:client'));
			return self::FAILURE;
		}
		
		// log helper
		$write = function($action, $result) use ($quite) {
			if ($quite) {
				return;
			}
			
			if (!$result) {
				$this->error(elgg_echo("opensearch:cli:sync:{$action}:error"));
				return;
			}
			
			$this->write(elgg_echo("opensearch:cli:sync:{$action}"));
		};
		
		// bulk delete
		$result = $service->bulkDeleteDocuments();
		$write('delete', $result);
		
		// indexing actions
		foreach (IndexingService::INDEXING_TYPES as $action) {
			$progress = false;
			if (!$quite) {
				$progress = new Progress($this->output);
			}
			
			$result = $service->bulkIndexDocuments([
				'type' => $action,
				'max_run_time' => 0,
				'progress' => $progress,
			]);
			$write($action, $result);
		}
		
		return self::SUCCESS;
	}
}
