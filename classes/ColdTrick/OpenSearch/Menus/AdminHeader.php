<?php

namespace ColdTrick\OpenSearch\Menus;

use Elgg\Menu\MenuItems;

/**
 * Add menu items to the admin_header menu
 */
class AdminHeader {

	/**
	 * Add menu items to the admin page menu
	 *
	 * @param \Elgg\Event $event 'register', 'menu:admin_header'
	 *
	 * @return null|MenuItems
	 */
	public static function register(\Elgg\Event $event): ?MenuItems {
		if (!elgg_in_context('admin') || !elgg_is_admin_logged_in()) {
			return null;
		}
		
		/* @var $returnvalue MenuItems */
		$returnvalue = $event->getValue();
		
		// parent
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'opensearch',
			'text' => elgg_echo('admin:opensearch'),
			'href' => false,
			'parent_name' => 'administer',
		]);
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'opensearch:stats',
			'text' => elgg_echo('admin:opensearch:statistics'),
			'href' => elgg_generate_url('admin', ['segments' => 'opensearch/statistics']),
			'parent_name' => 'opensearch',
		]);
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'opensearch:indices',
			'text' => elgg_echo('admin:opensearch:indices'),
			'href' => elgg_generate_url('admin', ['segments' => 'opensearch/indices']),
			'parent_name' => 'opensearch',
		]);
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'opensearch:search',
			'text' => elgg_echo('admin:opensearch:search'),
			'href' => elgg_generate_url('admin', ['segments' => 'opensearch/search']),
			'parent_name' => 'opensearch',
		]);
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'opensearch:settings',
			'text' => elgg_echo('settings'),
			'href' => elgg_generate_url('admin:plugin_settings', [
				'plugin_id' => 'opensearch',
			]),
			'parent_name' => 'opensearch',
		]);
		
		$returnvalue[] = \ElggMenuItem::factory([
			'name' => 'opensearch:inspect',
			'text' => elgg_echo('admin:opensearch:inspect'),
			'href' => elgg_generate_url('admin', ['segments' => 'opensearch/inspect']),
			'selected' => elgg_http_url_is_identical(
				elgg_get_current_url(),
				elgg_generate_url('admin', ['segments' => 'opensearch/inspect']),
				['guid']
			),
			'parent_name' => 'opensearch',
		]);
		
		return $returnvalue;
	}
}
