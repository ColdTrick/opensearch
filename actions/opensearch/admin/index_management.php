<?php

use ColdTrick\OpenSearch\Di\IndexManagementService;

$index = get_input('index');
$task = get_input('task');

if (empty($index)) {
	return elgg_error_response(elgg_echo('opensearch:error:no_index'));
}

$service = IndexManagementService::instance();
if (!$service->isClientReady()) {
	return elgg_error_response(elgg_echo('opensearch:error:no_client'));
}

$exists = $service->indexExists($index);
if (!$exists && $task !== 'create') {
	return elgg_error_response(elgg_echo('opensearch:error:index_not_exists', [$index]));
}

switch ($task) {
	case 'delete':
		if (!$service->delete($index)) {
			return elgg_error_response(elgg_echo('opensearch:action:admin:index_management:error:delete', [$index]));
		}
		return elgg_ok_response('', elgg_echo('opensearch:action:admin:index_management:delete', [$index]));
	case 'create':
		$index_prefix = elgg_get_plugin_setting('index', 'opensearch');
		
		if ($exists) {
			return elgg_error_response(elgg_echo('opensearch:action:admin:index_management:error:create:exists', [$index]));
		}
		
		// only allow the creation of the Elgg index
		if ($index !== $index_prefix) {
			return elgg_error_response(elgg_echo('opensearch:action:admin:index_management:error:task', [$task]));
		}
		
		$index = $index . '_' . time();
		if (!$service->create($index)) {
			return elgg_error_response(elgg_echo('opensearch:action:admin:index_management:error:create', [$index]));
		}
		
		// add read/write aliases
		if (!$service->addAlias($index, "{$index_prefix}_read")) {
			return elgg_error_response(elgg_echo('opensearch:action:admin:index_management:error:create', [$index]));
		}
		
		if (!$service->addAlias($index, "{$index_prefix}_write")) {
			return elgg_error_response(elgg_echo('opensearch:action:admin:index_management:error:create', [$index]));
		}
		return elgg_ok_response('', elgg_echo('opensearch:action:admin:index_management:create', [$index]));
	case 'add_mappings':
		if (!$service->addMapping($index)) {
			return elgg_error_response(elgg_echo('opensearch:action:admin:index_management:error:add_mappings', [$index]));
		}
		return elgg_ok_response('', elgg_echo('opensearch:action:admin:index_management:add_mappings', [$index]));
	case 'add_alias':
		$alias = elgg_get_plugin_setting('search_alias', 'opensearch');
		if (empty($alias)) {
			return elgg_error_response(elgg_echo('opensearch:error:alias_not_configured'));
		}
		
		if ($service->indexHasAlias($index, $alias)) {
			return elgg_error_response(elgg_echo('opensearch:action:admin:index_management:error:add_alias:exists', [$alias, $index]));
		}
		
		if (!$service->addAlias($index, $alias)) {
			return elgg_error_response(elgg_echo('opensearch:action:admin:index_management:error:add_alias', [$alias, $index]));
		}
		return elgg_ok_response('', elgg_echo('opensearch:action:admin:index_management:add_alias', [$alias, $index]));
	case 'delete_alias':
		$alias = elgg_get_plugin_setting('search_alias', 'opensearch');
		if (empty($alias)) {
			return elgg_error_response(elgg_echo('opensearch:error:alias_not_configured'));
		}
		
		if (!$service->indexHasAlias($index, $alias)) {
			return elgg_error_response(elgg_echo('opensearch:action:admin:index_management:error:delete_alias:exists', [$alias, $index]));
		}
		
		if (!$service->deleteAlias($index, $alias)) {
			return elgg_error_response(elgg_echo('opensearch:action:admin:index_management:error:delete_alias', [$alias, $index]));
		}
		return elgg_ok_response('', elgg_echo('opensearch:action:admin:index_management:delete_alias', [$alias, $index]));
}

return elgg_error_response(elgg_echo('opensearch:action:admin:index_management:error:task', [$task]));
