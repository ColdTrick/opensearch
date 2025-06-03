<?php

use Elgg\Database\EntityTable;
use Elgg\Database\MetadataTable;
use Elgg\Database\Update;

$entity_type = get_input('entity_type');
$entity_subtype = get_input('entity_subtype');

if (empty($entity_type) || empty($entity_subtype)) {
	$plugin = elgg_get_plugin_from_id('opensearch');
	$result = $plugin->setSetting('reindex_ts', time());
} else {
	$update = Update::table(MetadataTable::TABLE_NAME);
	$update->set('value', $update->param(0, ELGG_VALUE_INTEGER))
		->where($update->compare('name', '=', OPENSEARCH_INDEXED_NAME, ELGG_VALUE_STRING));
	
	$sub = $update->subquery(EntityTable::TABLE_NAME);
	$sub->select('guid')
		->where($update->compare('type', '=', $entity_type, ELGG_VALUE_STRING))
		->andWhere($update->compare('subtype', '=', $entity_subtype, ELGG_VALUE_STRING));
	
	$update->andWhere($update->compare('entity_guid', 'in', $sub->getSQL()));
	
	$result = elgg()->db->updateData($update);
}

if ($result) {
	return elgg_ok_response('', elgg_echo('save:success'));
}

return elgg_error_response(elgg_echo('save:fail'));
