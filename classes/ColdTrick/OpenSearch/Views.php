<?php

namespace ColdTrick\OpenSearch;

class Views {
	
	/**
	 * Display the search score in the search results
	 *
	 * @param \Elgg\Hook $hook 'view_vars', 'object/elements/imprint/contents'
	 *
	 * @return void|array
	 */
	public static function displaySearchScoreInImprint(\Elgg\Hook $hook) {
		
		if (!elgg_is_admin_logged_in() || elgg_get_plugin_setting('search_score', 'opensearch') !== 'yes') {
			return;
		}
		
		$result = $hook->getValue();
		
		$entity = elgg_extract('entity', $result);
		if (!$entity instanceof \ElggEntity || !$entity->getVolatileData('search_score')) {
			return;
		}
		
		$imprint = elgg_extract('imprint', $result, []);
		$imprint['opensearch_score'] = [
			'icon_name' => 'search',
			'content' => elgg_echo('opensearch:search_score', [$entity->getVolatileData('search_score')]),
		];
		
		$result['imprint'] = $imprint;
		
		return $result;
	}
	
	/**
	 * Allow search for banned users in livesearch as no banned users are indexed in opensearch
	 * and this prevents the addition of unsupported params which would prevent opensearch from
	 * providing the search results
	 *
	 * NOTE: opensearch doesn't support searching for banned users as they aren't indexed
	 *
	 * @param \Elgg\Hook $hook 'view_vars', 'resources/livesearch/users'
	 *
	 * @return void|array
	 */
	public static function allowBannedUsers(\Elgg\Hook $hook) {
		
		if (elgg_get_plugin_setting('search', 'opensearch') !== 'yes') {
			return;
		}
		
		$vars = $hook->getValue();
		
		$vars['include_banned'] = true;
		
		return $vars;
	}
	
	/**
	 * Prevent search param manipulation during presentation, to prevent unwanted
	 * 'search_matched_extra' VolatileData
	 *
	 * @param \Elgg\Hook $hook 'view_vars', 'search/entity'
	 *
	 * @return void|array
	 */
	public static function preventSearchFieldChanges(\Elgg\Hook $hook) {
		
		if (elgg_get_plugin_setting('search', 'opensearch') !== 'yes') {
			return;
		}
		
		$vars = $hook->getValue();
		$search_params = elgg_extract('params', $vars, []);
		
		$search_params['_opensearch_no_transform_fields'] = true;
		unset($search_params['fields']['attributes']);
		
		$vars['params'] = $search_params;
		
		return $vars;
	}
}
