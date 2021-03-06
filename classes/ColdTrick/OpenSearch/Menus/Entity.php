<?php

namespace ColdTrick\OpenSearch\Menus;

use Elgg\Menu\MenuItems;

/**
 * Add items to the 'entity' menu
 */
class Entity {
	
	/**
	 * Add an inspect menu item
	 *
	 * @param \Elgg\Hook $hook 'register', 'menu:entity'
	 *
	 * @return void|MenuItems
	 */
	public static function inspect(\Elgg\Hook $hook) {
		
		if (!elgg_is_admin_logged_in()) {
			return;
		}
		
		$entity = $hook->getEntityParam();
		if (!$entity instanceof \ElggEntity) {
			return;
		}
		
		/* @var $result MenuItems */
		$result = $hook->getValue();
		
		$result[] = \ElggMenuItem::factory([
			'name' => 'opensearch_inspect',
			'icon' => 'search',
			'text' => elgg_echo('opensearch:menu:entity:inspect'),
			'href' => elgg_http_add_url_query_elements('admin/opensearch/inspect', [
				'guid' => $entity->guid,
			]),
		]);
		
		return $result;
	}
}
