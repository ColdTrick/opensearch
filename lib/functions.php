<?php
/**
 * All helper functions are bundled here
 */

use ColdTrick\OpenSearch\Di\DeleteQueue;
use Elgg\Database\MetadataTable;
use Elgg\Database\QueryBuilder;
use Elgg\Database\Select;
use Elgg\Exceptions\ExceptionInterface;
use Elgg\Values;
use Psr\Log\LogLevel;

/**
 * Get the type/subtypes to index in OpenSearch
 *
 * @return array
 */
function opensearch_get_registered_entity_types(): array {
	$type_subtypes = elgg_entity_types_with_capability('searchable');
	foreach ($type_subtypes as $type => $subtypes) {
		if (empty($subtypes)) {
			// repair so it can be used in elgg_get_entities*
			$type_subtypes[$type] = ELGG_ENTITIES_ANY_VALUE;
		}
	}
	
	return elgg_trigger_event_results('index_entity_type_subtypes', 'opensearch', $type_subtypes, $type_subtypes);
}

/**
 * Get the type/subtypes for search in OpenSearch
 *
 * @return array
 */
function opensearch_get_registered_entity_types_for_search(): array {
	$type_subtypes = elgg_entity_types_with_capability('searchable');
	foreach ($type_subtypes as $type => $subtypes) {
		if (empty($subtypes)) {
			// repair so it can be used in elgg_get_entities*
			$type_subtypes[$type] = ELGG_ENTITIES_ANY_VALUE;
		}
	}

	return elgg_trigger_event_results('search', 'type_subtype_pairs', $type_subtypes, $type_subtypes);
}

/**
 * Returns the boostable types for OpenSearch
 *
 * @return array
 */
function opensearch_get_types_for_boosting(): array {
	$type_subtypes = opensearch_get_registered_entity_types_for_search();
	
	$result = [];
	foreach ($type_subtypes as $type => $subtypes) {
		if (empty($subtypes)) {
			$result[] = $type;
			continue;
		}
		
		foreach ($subtypes as $subtype) {
			$result[] = "{$type}.{$subtype}";
		}
	}
	
	return elgg_trigger_event_results('boostable_types', 'opensearch', $result, $result);
}

/**
 * Get the $options for elgg_get_entities in order to update the OpenSearch index
 *
 * @param string $type which options to get
 *
 * @return null|array
 * @interal
 */
function opensearch_get_bulk_options(string $type = 'no_index_ts'): ?array {
	$type_subtypes = opensearch_get_registered_entity_types();
	if (empty($type_subtypes)) {
		return null;
	}
	
	$defaults = [
		'type_subtype_pairs' => $type_subtypes,
		'limit' => false,
		'batch' => true,
		'batch_size' => 100,
		'batch_inc_offset' => false,
	];
	
	switch ($type) {
		case 'no_index_ts':
			// new or updated entities
			return array_merge($defaults, [
				'wheres' => [
					function (QueryBuilder $qb, $main_alias) {
						$select = $qb->subquery(MetadataTable::TABLE_NAME, 'mdi');
						$select->select("{$select->getTableAlias()}.entity_guid")
							->where($qb->compare("{$select->getTableAlias()}.name", '=', OPENSEARCH_INDEXED_NAME, ELGG_VALUE_STRING));
						
						return $qb->compare("{$main_alias}.guid", 'NOT IN', $select->getSQL());
					},
					function (QueryBuilder $qb, $main_alias) {
						$select = $qb->subquery(MetadataTable::TABLE_NAME, 'b');
						$select->select("{$select->getTableAlias()}.entity_guid")
							->joinEntitiesTable($select->getTableAlias(), 'entity_guid', 'inner', 'be');
						$select->where($qb->compare('be.type', '=', 'user', ELGG_VALUE_STRING))
							->andWhere($qb->compare("{$select->getTableAlias()}.name", '=', 'banned', ELGG_VALUE_STRING))
							->andWhere($qb->compare("{$select->getTableAlias()}.value", '=', 'yes', ELGG_VALUE_STRING));
						
						return $qb->compare("{$main_alias}.guid", 'NOT IN', $select->getSQL());
					},
				],
			]);
			
		case 'reindex':
			// a reindex has been initiated, so update all out of date entities
			$setting = (int) elgg_get_plugin_setting('reindex_ts', 'opensearch');
			if ($setting < 1) {
				return null;
			}
			return array_merge($defaults, [
				'metadata_name_value_pairs' => [
					[
						'name' => OPENSEARCH_INDEXED_NAME,
						'value' => $setting,
						'operand' => '<',
						'type' => ELGG_VALUE_INTEGER,
					],
					[
						'name' => OPENSEARCH_INDEXED_NAME,
						'value' => 0,
						'operand' => '>',
						'type' => ELGG_VALUE_INTEGER,
					],
				],
			]);
			
		case 'update':
			// content that was updated in Elgg and needs to be reindexed
			return array_merge($defaults, [
				'metadata_name_value_pairs' => [
					[
						'name' => OPENSEARCH_INDEXED_NAME,
						'value' => 0,
					],
				],
			]);
			
		case 'count':
			// content that needs to be indexed
			return [
				'type_subtype_pairs' => $type_subtypes,
				'count' => true,
			];
	}
	
	return null;
}

/**
 * Saves an array of documents to be deleted from the OpenSearch index
 *
 * @param int   $guid        GUID of the document to be deleted
 * @param array $info        an array of information needed to be saved to be able to delete it from the index
 * @param mixed $time_offset time offset to set the document in the delete queue (default: null)
 *
 * @return void
 */
function opensearch_add_document_for_deletion(int $guid, array $info, mixed $time_offset = null): void {
	try {
		$queue = DeleteQueue::instance();
	} catch (\Exception $e) {
		elgg_log($e, LogLevel::WARNING);
		return;
	}
	
	if (isset($time_offset)) {
		$current_time = $queue->getCurrentTime();
		$date = Values::normalizeTime($time_offset);
		$queue->setCurrentTime($date);
	}
	
	try {
		$queue->enqueue([
			'guid' => $guid,
			'info' => $info,
		]);
	} catch (ExceptionInterface $e) {
		// just to make sure we can reset the time
	}
	
	if (isset($time_offset)) {
		// reset to previous time
		$queue->setCurrentTime($current_time);
	}
}

/**
 * Check if a deleted GUID from the OpenSearch index exists in Elgg and reset the indexing flag
 *
 * @param int $guid GUID of the entity which was removed
 *
 * @return void
 */
function opensearch_remove_document_for_deletion(int $guid): void {
	// check if the entity still exists in Elgg (could be unregistered as searchable)
	// and remove indexing timestamp, so it can be reindexed when needed
	elgg_call(ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES | ELGG_DISABLE_SYSTEM_LOG | ELGG_SHOW_DELETED_ENTITIES, function() use ($guid) {
		$entity = get_entity($guid);
		if ($entity instanceof \ElggEntity) {
			unset($entity->{OPENSEARCH_INDEXED_NAME});
		}
	});
}

/**
 * Returns an array of documents to be deleted from the OpenSearch index
 *
 * @return array
 */
function opensearch_get_documents_for_deletion(): array {
	try {
		$queue = DeleteQueue::instance();
	} catch (ExceptionInterface $e) {
		elgg_log($e, LogLevel::WARNING);
		return [];
	}
	
	$documents = $queue->dequeue();
	if (empty($documents)) {
		return [];
	}
	
	$result = [];
	foreach ($documents as $document) {
		$guid = elgg_extract('guid', $document);
		$info = elgg_extract('info', $document);
		if (empty($guid) || empty($info)) {
			continue;
		}
		
		$result[$guid] = $info;
	}
	
	return $result;
}

/**
 * Make inspection values into a table structure
 *
 * @param mixed $key               the key to present
 * @param array $merged_values     the base array to show from
 * @param array $elgg_values       the Elgg values
 * @param array $opensearch_values the OpenSearch values
 * @param int   $depth             internal usage only
 *
 * @return null|array
 */
function opensearch_inspect_show_values(mixed $key, array $merged_values, array $elgg_values, array $opensearch_values, int $depth = 0): ?array {
	$rows = [];
	if (empty($depth)) {
		$rows[] = elgg_format_element('tr', [], elgg_format_element('th', ['colspan' => 3], $key));
	} else {
		$rows[] = elgg_format_element('tr', [], elgg_format_element('td', ['colspan' => 3], elgg_format_element('b', [], $key)));
	}
	
	foreach ($merged_values as $key => $values) {
		if (is_array($values)) {
			$subvalues = opensearch_inspect_show_values($key, $values, (array) elgg_extract($key, $elgg_values), (array) elgg_extract($key, $opensearch_values), $depth + 1);
			if (empty($subvalues)) {
				continue;
			}
			
			$rows = array_merge($rows, $subvalues);
			continue;
		}
		
		$elgg_value = elgg_extract($key, $elgg_values, '');
		if (is_array($elgg_value)) {
			$elgg_value = implode(', ', $elgg_value);
		}
		
		$opensearch_value = elgg_extract($key, $opensearch_values, '');
		$class = [];
		if ($elgg_value !== $opensearch_value) {
			$class[] = 'elgg-state';
			$class[] = 'elgg-state-error';
		}
		
		$rows[] = elgg_format_element('tr', ['class' => $class], implode(PHP_EOL, [
			elgg_format_element('td', [], $key),
			elgg_format_element('td', [], $elgg_value),
			elgg_format_element('td', [], $opensearch_value),
		]));
	}
	
	return $rows;
}
