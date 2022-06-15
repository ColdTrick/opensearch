<?php

namespace ColdTrick\OpenSearch\Upgrades;

use Elgg\Upgrade\SystemUpgrade;
use Elgg\Upgrade\Result;
use ColdTrick\OpenSearch\Di\IndexManagementService;

class AddIndexAliases implements SystemUpgrade {
	
	/**
	 * {@inheritDoc}
	 */
	public function getVersion(): int {
		return 2022061500;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function needsIncrementOffset(): bool {
		return false;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function shouldBeSkipped(): bool {
		$service = IndexManagementService::instance();
		if (!$service->isClientReady()) {
			return true;
		}
		
		$plugin = elgg_get_plugin_from_id('opensearch');
		$index = $plugin->getSetting('index');
		
		if (!$service->indexExists($index)) {
			return true;
		}
		
		$has_read_alias = $service->indexHasAlias($index, "{$index}_read");
		$has_write_alias = $service->indexHasAlias($index, "{$index}_write");
		
		return $has_read_alias && $has_write_alias;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function countItems(): int {
		$service = IndexManagementService::instance();
		if (!$service->isClientReady()) {
			return 0;
		}
		
		$plugin = elgg_get_plugin_from_id('opensearch');
		$index = $plugin->getSetting('index');
		if (!$service->indexExists($index)) {
			return 0;
		}
		
		$count = 0;
		if (!$service->indexHasAlias($index, "{$index}_read")) {
			$count++;
		}
		
		if (!$service->indexHasAlias($index, "{$index}_write")) {
			$count++;
		}
		
		return $count;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function run(Result $result, $offset): Result {
		$service = IndexManagementService::instance();
		if (!$service->isClientReady()) {
			$result->addFailures();
			return $result;
		}
		
		$plugin = elgg_get_plugin_from_id('opensearch');
		$index = $plugin->getSetting('index');
		if (!$service->indexExists($index)) {
			$result->markComplete();
			return $result;
		}
		
		if (!$service->indexHasAlias($index, "{$index}_read")) {
			if ($service->addAlias($index, "{$index}_read")) {
				$result->addSuccesses();
			} else {
				$result->addFailures();
			}
		}
		
		if (!$service->indexHasAlias($index, "{$index}_write")) {
			if ($service->addAlias($index, "{$index}_write")) {
				$result->addSuccesses();
			} else {
				$result->addFailures();
			}
		}
		
		return $result;
	}
}
