<?php

use ColdTrick\OpenSearch\Di\IndexManagementService;
use Elgg\Exceptions\ExceptionInterface;
use Psr\Log\LogLevel;

$index_client = IndexManagementService::instance();
if (!$index_client->isClientReady()) {
	echo elgg_echo('opensearch:error:no_client');
	return;
}

elgg_import_esm('forms/opensearch/admin_search');

echo elgg_view_field([
	'#type' => 'plaintext',
	'name' => 'q',
	'placeholder' => elgg_echo('opensearch:forms:admin_search:query:placeholder'),
]);

try {
	$status = $index_client->getIndexStatus();
} catch (ExceptionInterface $e) {
	elgg_log($e, LogLevel::ERROR);
	
	echo elgg_echo('opensearch:error:no_index');
	return;
}

$elgg_index = elgg_get_plugin_setting('index', 'opensearch');
$indices = array_keys($status);
natcasesort($indices);

echo elgg_view_field([
	'#type' => 'fieldset',
	'align' => 'horizontal',
	'class' => 'elgg-level',
	'fields' => [
		[
			'#type' => 'submit',
			'icon' => 'search',
			'text' => elgg_echo('search'),
		],
		[
			'#type' => 'select',
			'name' => 'index',
			'options' => $indices,
			'value' => $index_client->getElggIndex($elgg_index),
		],
	],
]);
