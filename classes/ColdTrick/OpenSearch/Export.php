<?php

namespace ColdTrick\OpenSearch;

use Elgg\Database\QueryBuilder;
use Elgg\Export\Data;

/**
 * Entity export handler
 */
class Export {
	
	/**
	 * Event to adjust exportable values of basic entities for search
	 *
	 * @param \Elgg\Event $event 'to:object', 'entity'
	 *
	 * @return null|\Elgg\Export\Data
	 */
	public static function entityToObject(\Elgg\Event $event): ?Data {
		if (!elgg_in_context('search:index')) {
			return null;
		}
	
		$entity = $event->getEntityParam();
		if (!$entity instanceof \ElggEntity) {
			return null;
		}
	
		$return = $event->getValue();
		
		// add some extra values to be submitted to the search index
		$return->last_action = date('c', $entity->last_action);
		$return->access_id = $entity->access_id;
		$return->indexed_type = self::getEntityIndexType($entity);
		
		return $return;
	}
	
	/**
	 * Get the type under which the entity will be indexed
	 *
	 * Defaults to 'type.subtype'
	 *
	 * @param \ElggEntity $entity the entity to index
	 *
	 * @return string
	 */
	protected static function getEntityIndexType(\ElggEntity $entity): string {
		$parts = [
			$entity->getType(),
			$entity->getSubtype(),
		];
		
		$parts = array_filter($parts);
		
		$index_type = implode('.', $parts);
		
		$params = [
			'entity' => $entity,
			'default' => $index_type,
		];
		
		return elgg_trigger_event_results('index:entity:type', 'opensearch', $params, $index_type);
	}

	/**
	 * Event to export entity metadata for search
	 *
	 * @param \Elgg\Event $event 'to:object', 'entity'
	 *
	 * @return null|\Elgg\Export\Data
	 */
	public static function entityMetadataToObject(\Elgg\Event $event): ?Data {
		if (!elgg_in_context('search:index')) {
			return null;
		}
	
		$entity = $event->getEntityParam();
		if (!$entity instanceof \ElggEntity) {
			return null;
		}
		
		$defaults = [];
		switch ($entity->getType()) {
			case 'user':
				$defaults[] = 'name';
				$defaults[] = 'username';
				$defaults[] = 'language';
				break;
			
			case 'object':
				$defaults[] = 'title';
				$defaults[] = 'description';
				break;
			
			case 'group':
			case 'site':
				$defaults[] = 'name';
				$defaults[] = 'description';
				break;
		}
		
		$metadata_names = elgg_trigger_event_results('export:metadata_names', 'opensearch', $event->getParams(), $defaults);
		if (empty($metadata_names)) {
			return null;
		}
		
		$result = [];
		foreach ($metadata_names as $name) {
			$data = $entity->getMetadata($name);
			if (elgg_is_empty($data)) {
				continue;
			}
			
			$result[$name] = is_array($data) ? $data : [$data];
		}
		
		if (empty($result)) {
			return null;
		}
		
		$return = $event->getValue();
		$return->metadata = $result;
		
		return $return;
	}

	/**
	 * Event to join user/group profile tag fields with tags
	 *
	 * @param \Elgg\Event $event 'to:object', 'entity'
	 *
	 * @return null|\Elgg\Export\Data
	 */
	public static function profileTagFieldsToTags(\Elgg\Event $event): ?Data {
		if (!elgg_in_context('search:index')) {
			return null;
		}
	
		$entity = $event->getEntityParam();
		if (!$entity instanceof \ElggEntity) {
			return null;
		}
		
		if (!in_array($entity->getType(), ['user', 'group'])) {
			return null;
		}
		
		$profile_fields = false;
		if ($entity instanceof \ElggUser || $entity instanceof \ElggGroup) {
			$profile_fields = elgg()->fields->get($entity->getType(), $entity->getSubtype());
		}
		
		if (empty($profile_fields)) {
			return null;
		}
		
		$tags = [];
		foreach ($profile_fields as $field) {
			$type = elgg_extract('#type', $field);
			if ($type !== 'tags') {
				continue;
			}

			$field_name = elgg_extract('name', $field);
			$field_tags = (array) $entity->$field_name;
			if (!empty($field_tags)) {
				$tags = array_merge($tags, $field_tags);
			}
		}
		
		if (empty($tags)) {
			return null;
		}
		
		$return = $event->getValue();
		
		if (isset($return->tags)) {
			$current_tags = (array) $return->tags;
			$tags = array_merge($current_tags, $tags);
		}
		
		// make all lowercase (for better uniqueness)
		$tags = array_map('strtolower', $tags);
		// make unique
		$tags = array_unique($tags);
		// reset array indexes
		$tags = array_values($tags);
		
		// make them unique
		$return->tags = $tags;
		
		return $return;
	}

	/**
	 * Event to export entity counters for search
	 *
	 * @param \Elgg\Event $event 'to:object', 'entity'
	 *
	 * @return null|\Elgg\Export\Data
	 */
	public static function entityCountersToObject(\Elgg\Event $event): ?Data {
		if (!elgg_in_context('search:index')) {
			return null;
		}
	
		$entity = $event->getEntityParam();
		if (!$entity instanceof \ElggEntity) {
			return null;
		}
		
		$counters = elgg_trigger_event_results('export:counters', 'opensearch', $event->getParams(), []);
		if (empty($counters) || !is_array($counters)) {
			return null;
		}
		
		$return = $event->getValue();
		
		$return->counters = $counters;
		
		return $return;
	}
	
	/**
	 * Event to export relationship entities for search
	 *
	 * @param \Elgg\Event $event 'to:object', 'entity'
	 *
	 * @return null|\Elgg\Export\Data
	 */
	public static function entityRelationshipsToObject(\Elgg\Event $event): ?Data {
		if (!elgg_in_context('search:index')) {
			return null;
		}
	
		$entity = $event->getEntityParam();
		if (!$entity instanceof \ElggEntity) {
			return null;
		}
		
		$relationship_names = elgg_trigger_event_results('export:relationship_names', 'opensearch', $event->getParams(), []);
		if (empty($relationship_names) || !is_array($relationship_names)) {
			return null;
		}
	
		$relationships = elgg_get_relationships([
			'batch' => true,
			'limit' => false,
			'wheres' => [
				function(QueryBuilder $qb, $main_alias) use ($entity) {
					return $qb->compare("{$main_alias}.guid_one", '=', $entity->guid, ELGG_VALUE_GUID);
				},
				function(QueryBuilder $qb, $main_alias) use ($relationship_names) {
					return $qb->compare("{$main_alias}.relationship", 'in', $relationship_names, ELGG_VALUE_STRING);
				},
			],
		]);
		
		$result = [];
		/* @var $relationship \ElggRelationship */
		foreach ($relationships as $relationship) {
			$result[] = [
				'id' => $relationship->id,
				'time_created' => date('c', $relationship->time_created),
				'guid_one' => $relationship->guid_one,
				'guid_two' => $relationship->guid_two,
				'relationship' => $relationship->relationship,
			];
		}
		
		if (empty($result)) {
			return null;
		}
		
		$return = $event->getValue();
		
		if (!isset($return->relationships)) {
			$return->relationships = $result;
		} elseif (is_array($return->relationships)) {
			$return->relationships = array_merge($return->relationships, $result);
		}
		
		return $return;
	}
	
	/**
	 * Event to strip tags from selected entity fields
	 *
	 * @param \Elgg\Event $event 'to:object', 'entity'
	 *
	 * @return null|\Elgg\Export\Data
	 */
	public static function stripTags(\Elgg\Event $event): ?Data {
		if (!elgg_in_context('search:index')) {
			return null;
		}
		
		$return = $event->getValue();
		
		$fields = ['title', 'name', 'description'];
		foreach ($fields as $field) {
			if (!isset($return->$field)) {
				continue;
			}
			
			$curval = $return->$field;
			if (empty($curval)) {
				continue;
			}
			
			if (is_array($curval)) {
				// should not happen
				$curval = array_map(function($value) {
					$value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
					return elgg_strip_tags($value);
				}, $curval);
			} else {
				$curval = html_entity_decode($curval, ENT_QUOTES, 'UTF-8');
				$curval = elgg_strip_tags($curval);
			}
			
			$return->$field = $curval;
		}
		
		return $return;
	}
	
	/**
	 * Event to extend the exportable metadata names
	 *
	 * @param \Elgg\Event $event 'export:metadata_names', 'opensearch'
	 *
	 * @return null|array
	 */
	public static function exportProfileMetadata(\Elgg\Event $event): ?array {
		$entity = $event->getEntityParam();
		if (!$entity instanceof \ElggEntity) {
			return null;
		}
		
		$fields = false;
		if ($entity instanceof \ElggUser || $entity instanceof \ElggGroup) {
			$fields = elgg()->fields->get($entity->getType(), $entity->getSubtype());
		}
		
		if (empty($fields)) {
			return null;
		}
		
		$field_names = [];
		foreach ($fields as $field) {
			$field_names[] = elgg_extract('name', $field);
		}
		
		$field_names = array_filter($field_names);
		
		return array_merge($event->getValue(), $field_names);
	}
	
	/**
	 * Event to export group members count
	 *
	 * @param \Elgg\Event $event 'export:counters', 'opensearch'
	 *
	 * @return null|array
	 */
	public static function exportGroupMemberCount(\Elgg\Event $event): ?array {
		$entity = $event->getEntityParam();
		if (!$entity instanceof \ElggGroup) {
			return null;
		}
		
		$return = $event->getValue();
		
		$return['member_count'] = elgg_call(ELGG_IGNORE_ACCESS, function() use ($entity) {
			return $entity->getMembers(['count' => true]);
		});
		
		return $return;
	}
	
	/**
	 * Event to export likes count
	 *
	 * @param \Elgg\Event $event 'export:counters', 'opensearch'
	 *
	 * @return null|array
	 */
	public static function exportLikesCount(\Elgg\Event $event): ?array {
		if (!elgg_is_active_plugin('likes')) {
			return null;
		}
		
		$entity = $event->getEntityParam();
		if (!$entity instanceof \ElggEntity || !$entity->hasCapability('likable')) {
			return null;
		}
		
		$count = elgg_call(ELGG_IGNORE_ACCESS, function () use ($entity) {
			return likes_count($entity);
		});
		
		$return = $event->getValue();
		
		$return['likes'] = $count;
		
		return $return;
	}
	
	/**
	 * Event to export comments count
	 *
	 * @param \Elgg\Event $event 'export:counters', 'opensearch'
	 *
	 * @return null|array
	 */
	public static function exportCommentsCount(\Elgg\Event $event): ?array {
		$entity = $event->getEntityParam();
		if (!$entity instanceof \ElggEntity) {
			return null;
		}
		
		$return = $event->getValue();
		
		$return['comments'] = elgg_call(ELGG_IGNORE_ACCESS, function() use ($entity) {
			return $entity->countComments();
		});
		
		return $return;
	}
}
