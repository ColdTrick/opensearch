<?php

namespace ColdTrick\OpenSearch\Menus;

use Elgg\Menu\MenuItems;

class Page {

	/**
	 * Add menu items to the admin page menu
	 *
	 * @param \Elgg\Hook $hook 'register', 'menu:page'
	 *
	 * @return void|MenuItems
	 */
	public static function admin(\Elgg\Hook $hook) {
		
		if (!elgg_in_context('admin') || !elgg_is_admin_logged_in()) {
			return;
		}
		
		$current_path = parse_url(elgg_get_current_url(), PHP_URL_PATH);
		$site_path = parse_url(elgg_get_site_url(), PHP_URL_PATH);
		$parsed_path = substr($current_path, strlen($site_path));
		
		/* @var $returnvalue MenuItems */
		$returnvalue = $hook->getValue();
		
		// parent
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'opensearch',
			'href' => false,
			'text' => elgg_echo('admin:opensearch'),
			'section' => 'administer',
		]);
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'opensearch:stats',
			'text' => elgg_echo('admin:opensearch:statistics'),
			'href' => 'admin/opensearch/statistics',
			'selected' => stristr($parsed_path, 'admin/opensearch/statistics') !== false,
			'parent_name' => 'opensearch',
			'section' => 'administer',
		]);
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'opensearch:indices',
			'text' => elgg_echo('admin:opensearch:indices'),
			'href' => 'admin/opensearch/indices',
			'selected' => stristr($parsed_path, 'admin/opensearch/indices') !== false,
			'parent_name' => 'opensearch',
			'section' => 'administer',
		]);
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'opensearch:search',
			'text' => elgg_echo('admin:opensearch:search'),
			'href' => 'admin/opensearch/search',
			'selected' => stristr($parsed_path, 'admin/opensearch/search') !== false,
			'parent_name' => 'opensearch',
			'section' => 'administer',
		]);
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'opensearch:settings',
			'text' => elgg_echo('settings'),
			'href' => 'admin/plugin_settings/opensearch',
			'selected' => stristr($parsed_path, 'admin/plugin_settings/opensearch') !== false,
			'parent_name' => 'opensearch',
			'section' => 'administer',
		]);
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'opensearch:inspect',
			'text' => elgg_echo('admin:opensearch:inspect'),
			'href' => 'admin/opensearch/inspect',
			'selected' => stristr($parsed_path, 'admin/opensearch/inspect') !== false,
			'parent_name' => 'opensearch',
			'section' => 'administer',
		]);
		
		return $returnvalue;
	}
}
