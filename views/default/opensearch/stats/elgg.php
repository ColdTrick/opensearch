<?php

use ColdTrick\OpenSearch\Di\DeleteQueue;
use Elgg\Exceptions\ExceptionInterface;

$content = '<table class="elgg-table">';

// sync enabled
$sync_enabled = elgg_get_plugin_setting('sync', 'opensearch');
$content .= '<tr>';
$content .= elgg_format_element('td', [], elgg_echo('opensearch:settings:sync'));
$content .= elgg_format_element('td', [], elgg_echo("option:{$sync_enabled}"));
$content .= '</tr>';

// total content to index
$options = opensearch_get_bulk_options('count');

$content .= '<tr>';
$content .= '<td>' . elgg_echo('opensearch:stats:elgg:total');
$content .= elgg_view('output/longtext', [
	'value' => elgg_echo('opensearch:stats:elgg:total:help'),
	'class' => 'elgg-subtext',
]) . '</td>';
$content .= elgg_format_element('td', [], elgg_count_entities($options));
$content .= '</tr>';

// new content to index
$options = opensearch_get_bulk_options();

$content .= '<tr>';
$content .= elgg_format_element('td', [], elgg_echo('opensearch:stats:elgg:no_index_ts'));
$content .= elgg_format_element('td', [], elgg_count_entities($options));
$content .= '</tr>';

// content to update
$options = opensearch_get_bulk_options('update');

$content .= '<tr>';
$content .= elgg_format_element('td', [], elgg_echo('opensearch:stats:elgg:update'));
$content .= elgg_format_element('td', [], elgg_count_entities($options));
$content .= '</tr>';

// content to reindex
$options = opensearch_get_bulk_options('reindex');
$count = 0;
if (!empty($options)) {
	$count = elgg_count_entities($options);
}

$content .= '<tr>';
$content .= elgg_format_element('td', [], elgg_echo('opensearch:stats:elgg:reindex'));
$content .= elgg_format_element('td', [], $count);
$content .= '</tr>';

$count = 0;
try {
	$count = DeleteQueue::instance()->size();
} catch (ExceptionInterface $e) {
	// something went wrong
	$count = elgg_echo('unknown');
}

$content .= '<tr>';
$content .= elgg_format_element('td', [], elgg_echo('opensearch:stats:elgg:delete'));
$content .= elgg_format_element('td', [], $count);
$content .= '</tr>';

$content .= '</table>';

// reindex option
$reindex_title = elgg_echo('opensearch:stats:elgg:reindex:action:title');
$last_ts = (int) elgg_get_plugin_setting('reindex_ts', 'opensearch');
if (!empty($last_ts)) {
	$reindex_title .= '&#10;&#10;' . elgg_echo('opensearch:stats:elgg:reindex:last_ts', [date('c', $last_ts)]);
}

$menu = elgg_view('output/url', [
	'confirm' => true,
	'icon' => 'refresh',
	'text' => elgg_echo('opensearch:stats:elgg:reindex:action:text'),
	'title' => $reindex_title,
	'href' => elgg_generate_action_url('opensearch/admin/reindex'),
	'class' => ['elgg-button', 'elgg-button-action']
]);

echo elgg_view_module('info', elgg_echo('opensearch:stats:elgg'), $content, ['menu' => $menu]);
