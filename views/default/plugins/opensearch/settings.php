<?php

use Elgg\Database\DbConfig;

/* @var $plugin \ElggPlugin */
$plugin = elgg_extract('entity', $vars);

// host configuration
$host = elgg_view_field([
	'#type' => 'text',
	'#label' => elgg_echo('opensearch:settings:host'),
	'#help' => elgg_echo('opensearch:settings:host:help'),
	'name' => 'params[host]',
	'value' => $plugin->host,
]);

$host .= elgg_view_field([
	'#type' => 'checkbox',
	'#label' => elgg_echo('opensearch:settings:ignore_ssl'),
	'#help' => elgg_echo('opensearch:settings:ignore_ssl:help'),
	'name' => 'params[ignore_ssl]',
	'value' => 1,
	'checked' => !empty($plugin->ignore_ssl),
	'switch' => true,
]);

$host .= elgg_view_field([
	'#type' => 'text',
	'#label' => elgg_echo('opensearch:settings:username'),
	'#help' => elgg_echo('opensearch:settings:username:help'),
	'name' => 'params[username]',
	'value' => $plugin->username,
]);

$host .= elgg_view_field([
	'#type' => 'password',
	'#label' => elgg_echo('opensearch:settings:password'),
	'#help' => elgg_echo('opensearch:settings:password:help'),
	'name' => 'params[password]',
	'value' => $plugin->password,
	'always_empty' => false,
]);

$db_config = _elgg_services()->dbConfig->getConnectionConfig();
$host .= elgg_view_field([
	'#type' => 'text',
	'#label' => elgg_echo('opensearch:settings:index'),
	'#help' => elgg_echo('opensearch:settings:index:help', [elgg_extract('database', $db_config)]),
	'name' => 'params[index]',
	'value' => $plugin->index,
]);

$host .= elgg_view_field([
	'#type' => 'text',
	'#label' => elgg_echo('opensearch:settings:search_alias'),
	'#help' => elgg_echo('opensearch:settings:search_alias:help'),
	'name' => 'params[search_alias]',
	'value' => $plugin->search_alias,
]);

echo elgg_view_module('info', elgg_echo('opensearch:settings:host:header'), $host);

// features
$features = elgg_view_field([
	'#type' => 'checkbox',
	'#label' => elgg_echo('opensearch:settings:sync'),
	'#help' => elgg_echo('opensearch:settings:sync:help'),
	'name' => 'params[sync]',
	'default' => 'no',
	'value' => 'yes',
	'checked' => $plugin->sync === 'yes',
	'switch' => true,
]);

$features .= elgg_view_field([
	'#type' => 'checkbox',
	'#label' => elgg_echo('opensearch:settings:search'),
	'#help' => elgg_echo('opensearch:settings:search:help'),
	'name' => 'params[search]',
	'default' => 'no',
	'value' => 'yes',
	'checked' => $plugin->search === 'yes',
	'switch' => true,
]);

$features .= elgg_view_field([
	'#type' => 'checkbox',
	'#label' => elgg_echo('opensearch:settings:search_score'),
	'#help' => elgg_echo('opensearch:settings:search_score:help'),
	'name' => 'params[search_score]',
	'default' => 'no',
	'value' => 'yes',
	'checked' => $plugin->search_score === 'yes',
	'switch' => true,
]);

$features .= elgg_view_field([
	'#type' => 'checkbox',
	'#label' => elgg_echo('opensearch:settings:cron_validate'),
	'#help' => elgg_echo('opensearch:settings:cron_validate:help'),
	'name' => 'params[cron_validate]',
	'default' => 'no',
	'value' => 'yes',
	'checked' => $plugin->cron_validate === 'yes',
	'switch' => true,
]);

echo elgg_view_module('info', elgg_echo('opensearch:settings:features:header'), $features);

// boosting of types
$types = opensearch_get_types_for_boosting();
if (!empty($types)) {
	
	$boosting = elgg_view('output/longtext', [
		'value' => elgg_echo('opensearch:settings:type_boosting:info'),
	]);
	
	// header row
	$row = [
		elgg_format_element('th', [], elgg_echo('opensearch:settings:type_boosting:type')),
		elgg_format_element('th', [], elgg_echo('opensearch:settings:type_boosting:multiplier')),
	];
	$header = elgg_format_element('thead', [], elgg_format_element('tr', [], implode(PHP_EOL, $row)));
	
	// content rows
	$rows = [];
	foreach ($types as $type) {
		$row = [];
		$setting_name = "type_boosting_{$type}";
		
		$label = $type;
		list($entity_type, $entity_subtype) = explode('.', $type);
		$key = implode(':', ['item', $entity_type, $entity_subtype]);
		if (elgg_language_key_exists($key)) {
			$label = elgg_echo($key);
			$label .= elgg_format_element('span', ['class' => 'elgg-subtext'], " ({$type})");
		}
		
		$row[] = elgg_format_element('td', [], $label);
		$row[] = elgg_format_element('td', [], elgg_view_field([
			'#type' => 'text',
			'#class' => 'man',
			'name' => "params[{$setting_name}]",
			'value' => $plugin->$setting_name,
			'pattern' => '[0-9.]+',
			'title' => elgg_echo('opensearch:settings:pattern:float'),
		]));
		
		$rows[] = elgg_format_element('tr', [], implode(PHP_EOL, $row));
			
	}
	$boosting .= elgg_format_element('table', ['class' => 'elgg-table'], $header . elgg_format_element('tbody', [], implode(PHP_EOL, $rows)));

	echo elgg_view_module('info', elgg_echo('opensearch:settings:type_boosting:title'), $boosting);
}

// decay score manipulation
$decay = elgg_view('output/longtext', ['value' => elgg_echo('opensearch:settings:decay:info')]);

$decay .= elgg_view_field([
	'#type' => 'number',
	'#label' => elgg_echo('opensearch:settings:decay_offset'),
	'#help' => elgg_echo('opensearch:settings:decay_offset:help'),
	'name' => 'params[decay_offset]',
	'value' => $plugin->decay_offset,
	'min' => 0,
]);

$decay .= elgg_view_field([
	'#type' => 'number',
	'#label' => elgg_echo('opensearch:settings:decay_scale'),
	'#help' => elgg_echo('opensearch:settings:decay_scale:help'),
	'name' => 'params[decay_scale]',
	'value' => $plugin->decay_scale,
	'min' => 0,
]);

$decay .= elgg_view_field([
	'#type' => 'text',
	'#label' => elgg_echo('opensearch:settings:decay_decay'),
	'#help' => elgg_echo('opensearch:settings:decay_decay:help'),
	'name' => 'params[decay_decay]',
	'value' => $plugin->decay_decay,
	'pattern' => '[0-9.]+',
	'title' => elgg_echo('opensearch:settings:pattern:float'),
]);

$decay .= elgg_view_field([
	'#type' => 'select',
	'#label' => elgg_echo('opensearch:settings:decay_time_field'),
	'#help' => elgg_echo('opensearch:settings:decay_time_field:help'),
	'name' => 'params[decay_time_field]',
	'value' => $plugin->decay_time_field,
	'options_values' => [
		'time_created' => elgg_echo('opensearch:settings:decay_time_field:time_created'),
		'time_updated' => elgg_echo('opensearch:settings:decay_time_field:time_updated'),
		'last_action' => elgg_echo('opensearch:settings:decay_time_field:last_action'),
	],
]);

echo elgg_view_module('info', elgg_echo('opensearch:settings:decay:title'), $decay);
