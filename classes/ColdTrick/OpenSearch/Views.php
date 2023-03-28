<?php

namespace ColdTrick\OpenSearch;

/**
 * View events listener
 */
class Views {
	
	/**
	 * Display the search score in the search results
	 *
	 * @param \Elgg\Event $event 'view_vars', 'object|user/elements/imprint/contents'
	 *
	 * @return null|array
	 */
	public static function displaySearchScoreInImprint(\Elgg\Event $event): ?array {
		$vars = $event->getValue();
		
		if (!(bool) elgg_extract('show_search_score', $vars, false)) {
			return null;
		}
		
		$entity = elgg_extract('entity', $vars);
		if (!$entity instanceof \ElggEntity || !$entity->getVolatileData('search_score')) {
			return null;
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
	 * Allow search for banned users in livesearch as no banned users are indexed in OpenSearch
	 * and this prevents the addition of unsupported params which would prevent OpenSearch from
	 * providing the search results
	 *
	 * NOTE: OpenSearch doesn't support searching for banned users as they aren't indexed
	 *
	 * @param \Elgg\Event $event 'view_vars', 'resources/livesearch/users'
	 *
	 * @return null|array
	 */
	public static function allowBannedUsers(\Elgg\Event $event): ?array {
		if (elgg_get_plugin_setting('search', 'opensearch') !== 'yes') {
			return null;
		}
		
		$vars = $event->getValue();
		
		$vars['include_banned'] = true;
		
		return $vars;
	}
	
	/**
	 * Prevent search param manipulation during presentation, to prevent unwanted
	 * 'search_matched_extra' VolatileData
	 *
	 * @param \Elgg\Event $event 'view_vars', 'search/entity'
	 *
	 * @return null|array
	 */
	public static function preventSearchFieldChanges(\Elgg\Event $event): ?array {
		if (elgg_get_plugin_setting('search', 'opensearch') !== 'yes') {
			return null;
		}
		
		$vars = $event->getValue();
		$search_params = elgg_extract('params', $vars, []);
		
		$search_params['_opensearch_no_transform_fields'] = true;
		unset($search_params['fields']['attributes']);
		
		$vars['params'] = $search_params;
		
		return $vars;
	}
	
	/**
	 * Enable search score presentation (for admins when enabled)
	 *
	 * @param \Elgg\Event $event 'view_vars', 'search/entity'
	 *
	 * @return null|array
	 */
	public static function enableSearchScorePresentation(\Elgg\Event $event): ?array {
		if (!elgg_is_admin_logged_in() || elgg_get_plugin_setting('search_score', 'opensearch') !== 'yes') {
			return null;
		}
		
		$vars = $event->getValue();
		$vars['show_search_score'] = true;
		
		return $vars;
	}
	
	/**
	 * Set the default sorting on the search result page to 'relevance'
	 *
	 * @param \Elgg\Event $event 'view_vars', 'resources/search/index'
	 *
	 * @return void
	 */
	public static function setDefaultSearchSorting(\Elgg\Event $event): void {
		if (elgg_get_plugin_setting('search', 'opensearch') !== 'yes') {
			return;
		}
		
		$sort = get_input('sort');
		$sort_by = get_input('sort_by');
		
		if (!empty($sort) || !empty($sort_by)) {
			return;
		}
		
		set_input('sort', 'relevance');
	}
}
