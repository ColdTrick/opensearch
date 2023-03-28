<?php

use ColdTrick\OpenSearch\Di\IndexManagementService;
use ColdTrick\OpenSearch\Di\IndexingService;
use ColdTrick\OpenSearch\Di\SearchService;
use ColdTrick\OpenSearch\Di\DeleteQueue;

return [
	DeleteQueue::name() => DI\autowire(DeleteQueue::class),
	IndexManagementService::name() => DI\autowire(IndexManagementService::class),
	IndexingService::name() => DI\autowire(IndexingService::class),
	SearchService::name() => DI\autowire(SearchService::class),
	
	// map classes to alias to allow autowiring
	DeleteQueue::class => DI\get(DeleteQueue::name()),
	IndexManagementService::class => DI\get(IndexManagementService::name()),
	IndexingService::class => DI\get(IndexingService::name()),
	SearchService::class => DI\get(SearchService::name()),
];
