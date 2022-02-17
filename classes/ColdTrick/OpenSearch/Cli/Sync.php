<?php

namespace ColdTrick\OpenSearch\Cli;

use ColdTrick\OpenSearch\Di\IndexingService;
use Elgg\Cli\Command;
use Elgg\Cli\Progress;

class Sync extends Command {
	
	/**
	 * {@inheritDoc}
	 */
	protected function configure() {
		$this->setName('opensearch:sync')
			->setDescription(elgg_echo('opensearch:cli:sync:description'));
	}
	
	/**
	 * {@inheritDoc}
	 */
	protected function command() {
		$quite = $this->option('quiet');
		$service = IndexingService::instance();
		
		if (!$service->isClientReady()) {
			$this->error(elgg_echo('opensearch:cli:error:client'));
			return 1;
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
		
		return 0;
	}
}
