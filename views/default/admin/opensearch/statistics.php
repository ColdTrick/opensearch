<?php

use ColdTrick\OpenSearch\Di\IndexManagementService;

echo elgg_view('opensearch/admin/tabs');

// Elgg configuration
echo elgg_view('opensearch/stats/elgg');

// OpenSearch stats require configured client
$service = IndexManagementService::instance();
if (!$service->isClientReady()) {
	echo elgg_echo('opensearch:error:no_client');
	return;
}

// check if server is up
if (!$service->ping()) {
	echo elgg_echo('opensearch:error:host_unavailable');
	return;
}

// cluster info
echo elgg_view('opensearch/stats/cluster', ['service' => $service]);

// index info
echo elgg_view('opensearch/stats/indices', ['service' => $service]);
