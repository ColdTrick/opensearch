<?php
/**
 * Show a suggestion for better search results
 */

use ColdTrick\OpenSearch\Di\SearchService;

if (!elgg_extract('inline_form', $vars)) {
	// this is a var supported by the Search Advanced plugin
	// otherwise it would also do this in the topbar search
	return;
}

$query = get_input('q');
if (empty($query)) {
	return;
}

$client = SearchService::instance();

$suggestions = $client->getSuggestions();
if (empty($suggestions)) {
	// maby no search query excuted where we can extract suggestions from
	// so do a suggest query ourselfs
	$client->suggest($query);
	
	$suggestions = $client->getSuggestions();
	if (empty($suggestions)) {
		// still nothing
		return;
	}
}

$suggestions = $suggestions['suggestions'][0]['options'];
if (empty($suggestions)) {
	return;
}

$suggestion = $suggestions[0]['text'];

$url = elgg_view('output/url', [
	'text' => elgg_format_element('strong', [], $suggestion),
	'href' => elgg_http_add_url_query_elements(current_page_url(), ['q' => $suggestion]),
]);

$query = elgg_format_element('i', [], $query);

echo elgg_format_element('div', ['class' => ['pbm', 'opensearch-suggestion']], elgg_echo('opensearch:suggest', [$url, $query]));
