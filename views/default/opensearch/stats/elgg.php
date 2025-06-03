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

$count = elgg_count_entities($options);
$content .= elgg_format_element('td', [], number_format($count, 0, elgg_echo('number_counter:decimal_separator'), elgg_echo('number_counter:thousands_separator')));
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

echo elgg_view_module('info', elgg_echo('opensearch:stats:elgg'), $content);
