<?php
/**
 * Schedule an entity to be removed from the index
 */

use ColdTrick\OpenSearch\Di\SearchService;

$guid = (int) get_input('guid');
if ($guid < 1) {
	return elgg_error_response(elgg_echo('error:missing_data'));
}

$service = SearchService::instance();
if (!$service->isClientReady()) {
	return elgg_error_response(elgg_echo('opensearch:error:no_client'));
}

$es_data = $service->inspect($guid, true);
if (empty($es_data)) {
	return elgg_error_response(elgg_echo('opensearch:error:search'));
}

opensearch_add_document_for_deletion($guid, [
	'_index' => elgg_extract('_index', $es_data),
	'_id' => $guid,
]);

return elgg_ok_response('', elgg_echo('opensearch:action:admin:delete_entity:success'));
