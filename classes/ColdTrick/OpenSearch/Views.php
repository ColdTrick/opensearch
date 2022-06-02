<?php

namespace ColdTrick\OpenSearch;

class Views {
	
	/**
	 * Display the search score in the search results
	 *
	 * @param \Elgg\Hook $hook 'view_vars', 'object|user/elements/imprint/contents'
	 *
	 * @return void|array
	 */
	public static function displaySearchScoreInImprint(\Elgg\Hook $hook) {
		
		$vars = $hook->getValue();
		
		if (!(bool) elgg_extract('show_search_score', $vars, false)) {
			return;
		}
		
		$entity = elgg_extract('entity', $vars);
		if (!$entity instanceof \ElggEntity || !$entity->getVolatileData('search_score')) {
			return;
		}
		
		$imprint = elgg_extract('imprint', $vars, []);
		$imprint['opensearch_score'] = [
			'icon_name' => 'search',
			'content' => elgg_echo('opensearch:search_score', [$entity->getVolatileData('search_score')]),
		];
		
		$vars['imprint'] = $imprint;
		
		return $vars;
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
	
	/**
	 * Enable search score presentation (for admins when enabled)
	 *
	 * @param \Elgg\Hook $hook 'view_vars', 'search/entity'
	 *
	 * @return void|array
	 */
	public static function enableSearchScorePresentation(\Elgg\Hook $hook) {
		
		if (!elgg_is_admin_logged_in() || elgg_get_plugin_setting('search_score', 'opensearch') !== 'yes') {
			return;
		}
		
		$vars = $hook->getValue();
		$vars['show_search_score'] = true;
		
		return $vars;
	}
	
	/**
	 * Set the default sorting on the search result page to 'relevance'
	 *
	 * @param \Elgg\Hook $hook 'view_vars', 'resources/search/index'
	 *
	 * @return void
	 */
	public static function setDefaultSearchSorting(\Elgg\Hook $hook): void {
		$sort = get_input('sort');
		$sort_by = get_input('sort_by');
		
		if (!empty($sort) || !empty($sort_by)) {
			return;
		}
		
		set_input('sort', 'relevance');
	}
}
