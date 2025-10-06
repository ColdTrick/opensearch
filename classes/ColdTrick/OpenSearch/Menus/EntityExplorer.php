<?php

namespace ColdTrick\OpenSearch\Menus;

use Elgg\Menu\MenuItems;

/**
 * Add items to the 'entity_explorer' menu
 */
class EntityExplorer {
	
	/**
	 * Add an inspect menu item
	 *
	 * @param \Elgg\Event $event 'register', 'menu:entity_explorer'
	 *
	 * @return null|MenuItems
	 */
	public static function inspect(\Elgg\Event $event): ?MenuItems {
		$entity = $event->getEntityParam();
		if (!$entity instanceof \ElggEntity) {
			return null;
		}
		
		/* @var $result MenuItems */
		$result = $event->getValue();
		
		$result[] = \ElggMenuItem::factory([
			'name' => 'opensearch_inspect',
			'icon' => 'search',
			'text' => elgg_echo('opensearch:menu:entity:inspect'),
			'href' => elgg_generate_url('admin', [
				'segments' => 'opensearch/inspect',
				'guid' => $entity->guid,
			]),
			'link_class' => ['elgg-button', 'elgg-button-action'],
		]);
		
		return $result;
	}
}
