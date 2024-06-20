<?php

namespace ColdTrick\OpenSearch;

use ColdTrick\OpenSearch\Di\IndexingService;

/**
 * Generic event listener to update OpenSearch as needed
 */
class EventDispatcher {
	
	/**
	 * Listen to all create events and update OpenSearch as needed
	 *
	 * @param \Elgg\Event $event 'create', 'all'
	 *
	 * @return void
	 */
	public static function create(\Elgg\Event $event): void {
		$object = $event->getObject();
		if ($object instanceof \ElggRelationship) {
			self::updateRelationship($object);
		}
		
		self::checkComments($object);
		self::updateEntityForAnnotation($object);
	}
	
	/**
	 * Listen to all update events and update OpenSearch as needed
	 *
	 * @param \Elgg\Event $event 'update', 'all'
	 *
	 * @return void
	 */
	public static function update(\Elgg\Event $event): void {
		$object = $event->getObject();
		if ($object instanceof \ElggEntity) {
			self::updateEntity($object);
		}
		
		self::checkComments($object);
		self::updateEntityForAnnotation($object);
	}
	
	/**
	 * Listen to all delete events and update OpenSearch as needed
	 *
	 * @param \Elgg\Event $event 'delete|trash:after', 'all'
	 *
	 * @return void
	 */
	public static function delete(\Elgg\Event $event): void {
		$object = $event->getObject();
		
		// ignore access during cleanup
		elgg_call(ELGG_IGNORE_ACCESS, function() use ($object) {
			if ($object instanceof \ElggEntity) {
				self::deleteEntity($object);
			} elseif ($object instanceof \ElggRelationship) {
				self::updateRelationship($object);
			}
			
			self::checkComments($object);
			self::updateEntityForAnnotation($object);
		});
	}
	
	/**
	 * Listen to all disable events and update OpenSearch as needed
	 *
	 * @param \Elgg\Event $event 'disable', 'all'
	 *
	 * @return void
	 */
	public static function disable(\Elgg\Event $event): void {
		$object = $event->getObject();
		if ($object instanceof \ElggEntity) {
			self::disableEntity($object);
		}
		
		self::checkComments($object);
		self::updateEntityForAnnotation($object);
	}
	
	/**
	 * Listen to ban user events and update OpenSearch as needed
	 *
	 * @param \Elgg\Event $event 'ban', 'user'
	 *
	 * @return void
	 */
	public static function banUser(\Elgg\Event $event): void {
		$user = $event->getObject();
		if (!$user instanceof \ElggUser) {
			return;
		}
		
		// remove user from index
		self::deleteEntity($user);
		
		// remove indexed ts, so when unbanned it will get indexed automatically
		unset($user->{OPENSEARCH_INDEXED_NAME});
	}
	
	/**
	 * Updates the entity the annotation is related to
	 *
	 * @param mixed $annotation the annotation
	 *
	 * @return void
	 */
	protected static function updateEntityForAnnotation($annotation): void {
		if (!$annotation instanceof \ElggAnnotation) {
			return;
		}
		
		$entity = $annotation->getEntity();
		if (!$entity instanceof \ElggEntity) {
			return;
		}
		
		self::updateEntity($entity);
	}
	
	/**
	 * Updates parent entities for content that is commented on
	 *
	 * @param mixed $entity the entity
	 *
	 * @return void
	 */
	protected static function checkComments($entity): void {
		if (!$entity instanceof \ElggComment) {
			return;
		}
		
		$container_entity = $entity->getContainerEntity();
		if (!$container_entity instanceof \ElggEntity) {
			return;
		}
		
		self::updateEntity($container_entity);
	}
	
	/**
	 * Handle the update of an ElggEntity
	 *
	 * @param \ElggEntity $entity the entity
	 *
	 * @return void
	 */
	protected static function updateEntity(\ElggEntity $entity): void {
		if (!$entity->{OPENSEARCH_INDEXED_NAME}) {
			return;
		}
		
		elgg_call(ELGG_DISABLE_SYSTEM_LOG, function() use ($entity) {
			$entity->{OPENSEARCH_INDEXED_NAME} = 0;
		});
	}
	
	/**
	 * Handle the deletion of an ElggEntity
	 *
	 * @param \ElggEntity $entity the entity
	 *
	 * @return void
	 */
	protected static function deleteEntity(\ElggEntity $entity): void {
		$last_indexed = $entity->{OPENSEARCH_INDEXED_NAME};
		if (elgg_is_empty($last_indexed)) {
			return;
		}
		
		$service = IndexingService::instance();
		if (!$service->isClientReady()) {
			return;
		}
		
		opensearch_add_document_for_deletion($entity->guid, [
			'_index' => $service->getWriteAlias(),
			'_id' => $entity->guid,
		]);
	}
	
	/**
	 * Handle the disabling of an ElggEntity
	 *
	 * @param \ElggEntity $entity the entity
	 *
	 * @return void
	 */
	protected static function disableEntity(\ElggEntity $entity): void {
		// remove from index
		self::deleteEntity($entity);

		// remove indexed ts, so when re-enabled it will get indexed automatically
		$entity->{OPENSEARCH_INDEXED_NAME};
	}

	/**
	 * Handle a change of ElggRelationship
	 *
	 * @param \ElggRelationship $relationship the entity
	 *
	 * @return void
	 */
	protected static function updateRelationship(\ElggRelationship $relationship): void {
		// update entity one
		$entity = get_entity($relationship->guid_one);
		if ($entity instanceof \ElggEntity) {
			self::updateEntity($entity);
		}
		
		// update entity two
		$entity = get_entity($relationship->guid_two);
		if ($entity instanceof \ElggEntity) {
			self::updateEntity($entity);
		}
	}
}
