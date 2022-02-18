<?php

use ColdTrick\OpenSearch\Di\IndexManagementService;
use ColdTrick\OpenSearch\Di\IndexingService;
use ColdTrick\OpenSearch\Di\SearchService;
use ColdTrick\OpenSearch\Di\DeleteQueue;

return [
	DeleteQueue::name() => DI\create(DeleteQueue::class),
	IndexManagementService::name() => DI\create(IndexManagementService::class)
		->constructor(DI\get('logger'), DI\get('hooks')),
	IndexingService::name() => DI\create(IndexingService::class)
		->constructor(DI\get('logger'), DI\get('hooks')),
	SearchService::name() => DI\create(SearchService::class)
		->constructor(DI\get('logger'), DI\get('hooks')),
];
