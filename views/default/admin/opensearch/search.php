<?php

echo elgg_view_form('opensearch/admin_search', [
	'prevent_double_submit' => false,
	'class' => 'mbl',
]);

echo elgg_view_module('info', elgg_echo('opensearch:admin_search:results'), elgg_echo('opensearch:admin_search:results:info'), [
	'id' => 'opensearch-admin-search-results',
]);
