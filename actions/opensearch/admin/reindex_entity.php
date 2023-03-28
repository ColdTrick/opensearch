<?php

$guid = (int) get_input('guid');
if (empty($guid)) {
	return elgg_error_response(elgg_echo('error:missing_data'));
}

$entity = get_entity($guid);
if (empty($entity)) {
	return elgg_error_response(elgg_echo('error:missing_data'));
}

if (!$entity->{OPENSEARCH_INDEXED_NAME}) {
	// can't be re-indexed as it hasn't been indexed yet (or shouldn't)
	return elgg_error_response(elgg_echo('save:fail'));
}

$entity->{OPENSEARCH_INDEXED_NAME} = 0;

return elgg_ok_response('', elgg_echo('opensearch:action:admin:reindex_entity:success'));
