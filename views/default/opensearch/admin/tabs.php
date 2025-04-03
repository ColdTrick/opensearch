<?php
/**
 * Add tabs above certain admin pages
 */

$tabs = [
	[
		'name' => 'opensearch:stats',
		'text' => elgg_echo('admin:opensearch:statistics'),
		'href' => elgg_generate_url('admin', [
			'segments' => 'opensearch/statistics',
		]),
	],
	[
		'name' => 'opensearch:indices',
		'text' => elgg_echo('admin:opensearch:indices'),
		'href' => elgg_generate_url('admin', [
			'segments' => 'opensearch/indices',
		]),
	],
	[
		'name' => 'opensearch:search',
		'text' => elgg_echo('admin:opensearch:search'),
		'href' => elgg_generate_url('admin', [
			'segments' => 'opensearch/search',
		]),
	],
	[
		'name' => 'opensearch:settings',
		'text' => elgg_echo('settings'),
		'href' => elgg_generate_url('admin:plugin_settings', [
			'plugin_id' => 'opensearch',
		]),
	],
	[
		'name' => 'opensearch:inspect',
		'text' => elgg_echo('admin:opensearch:inspect'),
		'href' => elgg_generate_url('admin', [
			'segments' => 'opensearch/inspect',
		]),
		'selected' => elgg_http_url_is_identical(elgg_get_current_url(), elgg_generate_url('admin', [
			'segments' => 'opensearch/inspect',
		]), ['guid']),
	],
];

echo elgg_view('navigation/tabs', [
	'tabs' => $tabs,
]);
