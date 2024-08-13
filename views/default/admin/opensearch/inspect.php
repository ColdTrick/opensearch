<?php

use ColdTrick\OpenSearch\Di\SearchService;

// form for inspect
$form_vars = [
	'method' => 'GET',
	'action' => 'admin/opensearch/inspect',
	'disable_security' => true,
	'class' => 'mbl',
];
echo elgg_view_form('opensearch/inspect', $form_vars);

// result listing
$result = '';

$guid = (int) get_input('guid');
if (empty($guid)) {
	return;
}

$service = SearchService::instance();
$registered_types = opensearch_get_registered_entity_types();

$entity = elgg_call(ELGG_SHOW_DELETED_ENTITIES, function() use ($guid) {
	return get_entity($guid);
});

$opensearch_content = $service->inspect($guid);

if (!$entity instanceof \ElggEntity) {
	// Entity doesn't exist in Elgg or was trashed
	if (!empty($opensearch_content)) {
		// entity still exists in OpenSearch index add button to delete entity from index
		$result = elgg_view('output/longtext', [
			'value' => elgg_echo('opensearch:inspect:result:not_found:index'),
		]);
		
		$result .= elgg_view('output/url', [
			'icon' => 'delete',
			'text' => elgg_echo('opensearch:inspect:result:delete'),
			'href' => elgg_generate_action_url('opensearch/admin/delete_entity', [
				'guid' => $entity->guid,
			]),
			'class' => 'elgg-button elgg-button-action',
		]);
	} else {
		$result = elgg_view('output/longtext', [
			'value' => elgg_echo('opensearch:inspect:result:not_found:elgg'),
		]);
	}
} elseif (!array_key_exists($entity->getType(), $registered_types) || (array_key_exists($entity->getType(), $registered_types) && !empty($entity->getSubtype()) && !in_array($entity->getSubtype(), $registered_types[$entity->getType()]))) {
	// entity won't be exported to ES
	$result = elgg_view('output/longtext', [
		'value' => elgg_echo('opensearch:inspect:result:error:type_subtype'),
	]);
} else {
	// show inspect result
	elgg_push_context('search:index');
	$current_content = (array) $entity->toObject();
	elgg_pop_context();
	
	$last_indexed = $entity->{OPENSEARCH_INDEXED_NAME};
	if (is_null($last_indexed)) {
		$result = elgg_view('output/longtext', [
			'value' => elgg_echo('opensearch:inspect:result:last_indexed:none'),
		]);
	} elseif (empty($last_indexed)) {
		$result = elgg_view('output/longtext', [
			'value' => elgg_echo('opensearch:inspect:result:last_indexed:scheduled'),
		]);
	} else {
		$result = elgg_view('output/longtext', [
			'value' => elgg_echo('opensearch:inspect:result:last_indexed:time', [date('c', $last_indexed)]),
		]);
		$result .= elgg_view('output/url', [
			'icon' => 'refresh',
			'text' => elgg_echo('opensearch:inspect:result:reindex'),
			'href' => elgg_generate_action_url('opensearch/admin/reindex_entity', [
				'guid' => $entity->guid,
			]),
			'class' => 'elgg-button elgg-button-action',
		]);
	}
	
	if (empty($opensearch_content)) {
		$result = elgg_view('output/longtext', [
			'value' => elgg_echo('opensearch:inspect:result:error:not_indexed'),
		]);
	} else {
		// add button to delete entity from index
		$result .= elgg_view('output/url', [
			'icon' => 'delete',
			'text' => elgg_echo('opensearch:inspect:result:delete'),
			'href' => elgg_generate_action_url('opensearch/admin/delete_entity', [
				'guid' => $entity->guid,
			]),
			'class' => 'elgg-button elgg-button-action',
		]);
		
		// needed for listing all possible values
		$merged = array_replace_recursive($current_content, $opensearch_content);
		
		$header = elgg_format_element('tr', [], implode(PHP_EOL, [
			elgg_format_element('th', [], '&nbsp'),
			elgg_format_element('th', [], elgg_echo('opensearch:inspect:result:elgg')),
			elgg_format_element('th', [], elgg_echo('opensearch:inspect:result:opensearch')),
		]));
		$header = elgg_format_element('thead', [], $header);
		
		$rows = [];
		$extras = [];
		foreach ($merged as $key => $values) {
			if (!is_array($values)) {
				// main content
				$elgg_value = elgg_extract($key, $current_content);
				if (is_array($elgg_value)) {
					$elgg_value = implode(', ', $elgg_value);
				}
				
				$es_value = elgg_extract($key, $opensearch_content);
				$class = [];
				if ($elgg_value != $es_value) {
					$class[] = 'elgg-state';
					$class[] = 'elgg-state-error';
				}
				
				$rows[] = elgg_format_element('tr', ['class' => $class], implode(PHP_EOL, [
					elgg_format_element('td', [], $key),
					elgg_format_element('td', [], $elgg_value),
					elgg_format_element('td', [], $es_value),
				]));
			} else {
				// has subvalues
				$subvalues = opensearch_inspect_show_values($key, $values, (array) elgg_extract($key, $current_content), (array) elgg_extract($key, $opensearch_content));
				if (!empty($subvalues)) {
					$extras = array_merge($extras, $subvalues);
				}
			}
		}
		
		$rows = array_merge($rows, $extras);
		
		$table_content = $header;
		$table_content .= elgg_format_element('tbody', [], implode(PHP_EOL, $rows));
		
		$result .= elgg_format_element('table', ['class' => ['elgg-table', 'opensearch-inspect-table']], $table_content);
	}
}

if (!empty($result)) {
	echo elgg_view_module('info', elgg_echo('opensearch:inspect:result:title'), $result);
}
