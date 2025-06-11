<?php
/**
 * Show indexing stats about Elgg content
 */

$searchable = opensearch_get_registered_entity_types();
if (empty($searchable)) {
	return;
}

$format_number = function (int $number) {
	return number_format($number, 0, elgg_echo('number_counter:decimal_separator'), elgg_echo('number_counter:thousands_separator'));
};

$header = [
	elgg_format_element('th', [], elgg_echo('admin:statistics:numentities:type')),
	elgg_format_element('th', ['class' => 'center'], elgg_echo('total')),
	elgg_format_element('th', ['class' => 'center'], elgg_echo('opensearch:stats:elgg_content:no_index_ts')),
	elgg_format_element('th', ['class' => 'center'], elgg_echo('opensearch:stats:elgg_content:update')),
	elgg_format_element('th', ['class' => 'center'], elgg_echo('opensearch:stats:elgg_content:reindex')),
	elgg_format_element('th', [], elgg_echo('opensearch:stats:elgg_content:reindex:action')),
];

$header = elgg_format_element('tr', [], implode(PHP_EOL, $header));
$header = elgg_format_element('thead', [], $header);

$totals = [
	'content' => 0,
	'new' => 0,
	'update' => 0,
	'reindex' => 0,
];
$rows = [];

foreach ($searchable as $type => $subtypes) {
	foreach ($subtypes as $subtype) {
		$row = [];
		
		$type_subtype_options = [
			'type' => $type,
			'subtype' => $subtype,
		];
		
		$label = $subtype;
		if (elgg_language_key_exists("collection:{$type}:{$subtype}")) {
			$label = elgg_echo("collection:{$type}:{$subtype}");
		} elseif (elgg_language_key_exists("item:{$type}:{$subtype}")) {
			$label = elgg_echo("item:{$type}:{$subtype}");
		}
		
		$row[] = elgg_format_element('td', [], $label);
		
		// total
		$count = elgg_count_entities($type_subtype_options);
		$totals['content'] += $count;
		$row[] = elgg_format_element('td', ['class' => 'center'], $format_number($count));
		
		// new to index
		$options = opensearch_get_bulk_options();
		unset($options['type_subtype_pairs']);
		$options = array_merge($options, $type_subtype_options);
		$count = elgg_count_entities($options);
		$totals['new'] += $count;
		$row[] = elgg_format_element('td', ['class' => 'center'], $format_number($count));
		
		// update
		$options = opensearch_get_bulk_options('update');
		unset($options['type_subtype_pairs']);
		$options = array_merge($options, $type_subtype_options);
		$count = elgg_count_entities($options);
		$totals['update'] += $count;
		$row[] = elgg_format_element('td', ['class' => 'center'], $format_number($count));
		
		// reindex
		$options = opensearch_get_bulk_options('reindex');
		if (!empty($options)) {
			unset($options['type_subtype_pairs']);
			$options = array_merge($options, $type_subtype_options);
			$count = elgg_count_entities($options);
		} else {
			$count = 0;
		}
		
		$totals['reindex'] += $count;
		$row[] = elgg_format_element('td', ['class' => 'center'], $format_number($count));
		
		// reindex action
		$link = elgg_view('output/url', [
			'icon' => 'refresh',
			'text' => elgg_echo('opensearch:stats:elgg_content:reindex:action'),
			'href' => elgg_generate_action_url('opensearch/admin/reindex', [
				'entity_type' => $type,
				'entity_subtype' => $subtype,
			]),
			'confirm' => true,
			'class' => ['elgg-button', 'elgg-button-action', 'elgg-size-small'],
		]);
		
		$row[] = elgg_format_element('td', [], $link);
		
		$rows["{$type}_{$subtype}"] = elgg_format_element('tr', [], implode(PHP_EOL, $row));
	}
}

uksort($rows, function ($a, $b) {
	return strnatcasecmp($a, $b);
});

$body = elgg_format_element('tbody', [], implode(PHP_EOL, $rows));

$footer = [
	elgg_format_element('th', [], elgg_echo('total')),
	elgg_format_element('th', ['class' => 'center'], $format_number($totals['content'])),
	elgg_format_element('th', ['class' => 'center'], $format_number($totals['new'])),
	elgg_format_element('th', ['class' => 'center'], $format_number($totals['update'])),
	elgg_format_element('th', ['class' => 'center'], $format_number($totals['reindex'])),
	elgg_format_element('th', [], '&nbsp;'),
];
$footer = elgg_format_element('tr', [], implode(PHP_EOL, $footer));
$footer = elgg_format_element('tfoot', [], $footer);

$table = elgg_format_element('table', ['class' => 'elgg-table'], $header . $body . $footer);

// reindex option
$reindex_title = elgg_echo('opensearch:stats:elgg_content:reindex:action:title');
$last_ts = (int) elgg_get_plugin_setting('reindex_ts', 'opensearch');
if (!empty($last_ts)) {
	$reindex_title .= '&#10;&#10;' . elgg_echo('opensearch:stats:elgg_content:reindex:last_ts', [date('c', $last_ts)]);
}

$menu = elgg_view('output/url', [
	'icon' => 'refresh',
	'text' => elgg_echo('opensearch:stats:elgg_content:reindex:action:text'),
	'title' => $reindex_title,
	'href' => elgg_generate_action_url('opensearch/admin/reindex'),
	'confirm' => true,
	'class' => ['elgg-button', 'elgg-button-action']
]);

echo elgg_view_module('info', elgg_echo('opensearch:stats:elgg_content'), $table, ['menu' => $menu]);
