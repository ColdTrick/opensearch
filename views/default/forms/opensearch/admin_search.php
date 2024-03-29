<?php

use ColdTrick\OpenSearch\Di\IndexManagementService;
use Elgg\Exceptions\ExceptionInterface;

$index_client = IndexManagementService::instance();
if (!$index_client->isClientReady()) {
	echo elgg_echo('opensearch:error:no_client');
	return;
}

elgg_require_js('forms/opensearch/admin_search');

echo elgg_view_field([
	'#type' => 'plaintext',
	'name' => 'q',
	'placeholder' => elgg_echo('opensearch:forms:admin_search:query:placeholder'),
]);

try {
	$status = $index_client->getIndexStatus();
} catch (ExceptionInterface $e) {
	elgg_log($e, 'ERROR');
	
	echo elgg_echo('opensearch:error:no_index');
	return;
}

$indices = array_keys($status);

echo elgg_view_field([
	'#type' => 'select',
	'name' => 'index',
	'options' => $indices,
	'value' => $index_client->getReadAlias(),
]);

$footer = elgg_view_field([
	'#type' => 'submit',
	'text' => elgg_echo('search'),
]);
elgg_set_form_footer($footer);
