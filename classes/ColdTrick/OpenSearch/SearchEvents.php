<?php

namespace ColdTrick\OpenSearch;

use ColdTrick\OpenSearch\Di\SearchService;
use Elgg\Exceptions\ExceptionInterface;
use Elgg\Exceptions\UnexpectedValueException;
use Psr\Log\LogLevel;

/**
 * Listen to different search events
 */
class SearchEvents {
	
	/**
	 * Check search params for unsupported options
	 *
	 * @param \Elgg\Event $event 'search:params', 'all'
	 *
	 * @return null|array
	 */
	public static function searchParams(\Elgg\Event $event): ?array {
		$search_params = $event->getValue();
		if (isset($search_params['_opensearch_supported'])) {
			return null;
		}
		
		if (!self::handleSearch() || self::isInAdminContext() || self::detectUnsupportedSearchParams($search_params)) {
			$search_params['_opensearch_supported'] = false;
			
			return $search_params;
		}
		
		$search_params['_opensearch_supported'] = true;
		
		self::transformSearchParamFields($search_params);
		self::transformSearchParamQueryInLivesearch($search_params);
		self::transformSearchParamSorting($search_params);
		
		return $search_params;
	}
	
	/**
	 * Transform provided search fields to the correct OpenSearch fields
	 *
	 * @param array $search_params the search params
	 *
	 * @return void
	 */
	protected static function transformSearchParamFields(array &$search_params): void {
		if (!isset($search_params['fields']) || !empty($search_params['_opensearch_no_transform_fields'])) {
			return;
		}
		
		$metadata_should_be_attribute = [
			'description',
			'name',
			'tags',
			'title',
			'username',
		];
		
		foreach ($search_params['fields'] as $type => $fields) {
			if ($type !== 'metadata') {
				continue;
			}
			
			if (!isset($search_params['fields']['attributes'])) {
				$search_params['fields']['attributes'] = [];
			}
			
			foreach ($fields as $index => $field) {
				if (!in_array($field, $metadata_should_be_attribute)) {
					continue;
				}
				
				$search_params['fields']['attributes'][] = $field;
				unset($search_params['fields']['metadata'][$index]);
				
				// add title alias for name
				// @see self::searchFieldsNameToTitle()
				if ($field === 'name') {
					$search_params['fields']['attributes'][] = 'title';
				}
			}
		}
	}
	
	/**
	 * Add wildcard to livesearch queries to find more content
	 *
	 * @param array $search_params the search params
	 *
	 * @return void
	 */
	protected static function transformSearchParamQueryInLivesearch(array &$search_params): void {
		if (!elgg_in_context('livesearch') || !isset($search_params['query'])) {
			return;
		}
		
		$query = elgg_extract('query', $search_params);
		
		// this is the replacement for the deprecated PHP 8.1 filter_var($query, FILTER_SANITIZE_STRING);
		$query = elgg_strip_tags($query);
		$query = filter_var($query, FILTER_SANITIZE_SPECIAL_CHARS);
		$query = str_replace('&#38;', '&', $query);
		
		$query = trim($query);
		$query = rtrim($query, '*');
		
		$search_params['query'] = "{$query}|{$query}*";
	}
	
	/**
	 * Transform sort options to the correct sort_by options
	 *
	 * @param array $search_params the search params
	 *
	 * @return void
	 */
	protected static function transformSearchParamSorting(array &$search_params): void {
		$sort = elgg_extract('sort', $search_params);
		$sort_by = elgg_extract('sort_by', $search_params, []);
		if (isset($sort_by['property'])) {
			$sort_by = [$sort_by];
		}
		
		if (empty($sort) && empty($sort_by)) {
			// default to relevance
			$sort = 'relevance';
		}
		
		switch ($sort) {
			case 'relevance':
				$sort_by = []; // ignore previously set sorts
				$sort_by[] = [
					'property_type' => 'score',
					'property' => '_score',
					'direction' => elgg_extract('order', $search_params, 'desc'),
				];
				$sort_by[] = [
					'property_type' => 'attribute',
					'property' => 'time_created',
					'direction' => 'desc',
				];
				$sort = null;
				break;
			
			case 'member_count':
				$sort_by = []; // ignore previously set sorts
				$sort_by[] = [
					'property_type' => 'counter',
					'property' => $sort,
					'direction' => elgg_extract('order', $search_params, 'desc'),
				];
				$sort_by[] = [
					'property_type' => 'score',
					'property' => '_score',
					'direction' => 'desc',
				];
				$sort = null;
				break;
		}
		
		$search_params['sort'] = $sort;
		$search_params['sort_by'] = $sort_by;
	}
	
	/**
	 * Change object search fields
	 *
	 * @param \Elgg\Event $event 'search:fields', 'object'
	 *
	 * @return null|array
	 */
	public static function objectSearchFields(\Elgg\Event $event): ?array {
		if (!self::handleSearch()) {
			return null;
		}
		
		$value = (array) $event->getValue();
		
		$defaults = [
			'metadata' => [],
		];
		
		$ignored_metadata_names = [
			'tags',
		];
		
		$value = array_merge($defaults, $value);
		if (empty($value['metadata'])) {
			return null;
		}
		
		// remove user profile tag fields
		$user_tags = self::getUserProfileTagsFields();
		if (!empty($user_tags)) {
			foreach ($value['metadata'] as $index => $metadata_name) {
				if (in_array($metadata_name, $ignored_metadata_names)) {
					continue;
				}
				
				if (!in_array($metadata_name, $user_tags)) {
					continue;
				}
				
				unset($value['metadata'][$index]);
			}
		}
		
		// remove group profile tag fields
		$group_tags = self::getGroupProfileTagsFields();
		if (!empty($group_tags)) {
			foreach ($value['metadata'] as $index => $metadata_name) {
				if (in_array($metadata_name, $ignored_metadata_names)) {
					continue;
				}
				
				if (!in_array($metadata_name, $group_tags)) {
					continue;
				}
				
				unset($value['metadata'][$index]);
			}
		}
		
		return $value;
	}
	
	/**
	 * Change group search fields
	 *
	 * @param \Elgg\Event $event 'search:fields', 'group'
	 *
	 * @return null|array
	 */
	public static function groupSearchFields(\Elgg\Event $event): ?array {
		if (!self::handleSearch()) {
			return null;
		}
		
		// remove user profile tag fields (not present in group profile fields)
		$value = (array) $event->getValue();
		
		$defaults = [
			'metadata' => [],
		];
		
		$value = array_merge($defaults, $value);
		if (empty($value['metadata'])) {
			return null;
		}
		
		$user_tags = self::getUserProfileTagsFields();
		if (empty($user_tags)) {
			return null;
		}
		
		$group_fields = elgg()->fields->get('group', 'group');
		$group_names = [];
		foreach ($group_fields as $field) {
			$group_names[] = elgg_extract('name', $field);
		}
		
		foreach ($value['metadata'] as $index => $metadata_name) {
			if (in_array($metadata_name, $group_names) || !in_array($metadata_name, $user_tags)) {
				continue;
			}
			
			unset($value['metadata'][$index]);
		}
		
		return $value;
	}
	
	/**
	 * Change user search fields
	 *
	 * @param \Elgg\Event $event 'search:fields', 'user'
	 *
	 * @return null|array
	 */
	public static function userSearchFields(\Elgg\Event $event): ?array {
		if (!self::handleSearch()) {
			return null;
		}
		
		// remove profile tag fields from metadata
		$value = (array) $event->getValue();
		
		$defaults = [
			'metadata' => [],
		];
		
		$value = array_merge($defaults, $value);
		if (empty($value['metadata'])) {
			return null;
		}
		
		$group_tags = self::getGroupProfileTagsFields();
		$user_tags = self::getUserProfileTagsFields();
		foreach ($value['metadata'] as $index => $metadata_name) {
			$unset = false;
			if (in_array($metadata_name, $user_tags)) {
				$unset = true;
			} elseif (in_array($metadata_name, $group_tags)) {
				$unset = true;
			}
			
			if (!$unset) {
				continue;
			}
			
			unset($value['metadata'][$index]);
		}
		
		return $value;
	}
	
	/**
	 * Move some search fields around
	 *
	 * @param \Elgg\Event $event 'search:fields', 'all'
	 *
	 * @return null|array
	 */
	public static function searchFields(\Elgg\Event $event): ?array {
		if (!self::handleSearch()) {
			return null;
		}
		
		if ($event->getParam('_opensearch_supported') === false) {
			return null;
		}
		
		$value = (array) $event->getValue();
		
		$defaults = [
			'attributes' => [],
			'metadata' => [],
		];
		
		$metadata_should_be_attribute = [
			'description',
			'name',
			'tags',
			'title',
			'username',
		];
		
		$value = array_merge($defaults, $value);
		
		foreach ($value['metadata'] as $index => $name) {
			if (!in_array($name, $metadata_should_be_attribute)) {
				continue;
			}
			
			$value['attributes'][] = $name;
			unset($value['metadata'][$index]);
		}
		
		$value['attributes'] = array_values(array_unique($value['attributes']));
		
		return $value;
	}
	
	/**
	 * When searching in the attribute name, move it to title according to the mapping
	 *
	 * @param \Elgg\Event $event 'search:fields', 'all'
	 *
	 * @return null|array
	 */
	public static function searchFieldsNameToTitle(\Elgg\Event $event): ?array {
		if (!self::handleSearch()) {
			return null;
		}
		
		$value = (array) $event->getValue();
		if (!isset($value['attributes']) || !in_array('name', $value['attributes'])) {
			return null;
		}
		
		foreach ($value['attributes'] as $index => $name) {
			if ($name !== 'name') {
				continue;
			}
			
			$value['attributes'][] = 'title';
			unset($value['attributes'][$index]);
			
			break;
		}
		
		$value['attributes'] = array_values(array_unique($value['attributes']));
		
		return $value;
	}
	
	/**
	 * Get all configured tag fields for users
	 *
	 * @return string[]
	 */
	protected static function getUserProfileTagsFields(): array {
		$fields = elgg()->fields->get('user', 'user');
		if (empty($fields)) {
			return [];
		}
		
		$result = [];
		foreach ($fields as $field) {
			$type = elgg_extract('#type', $field);
			if (!in_array($type, ['tags', 'tag', 'location'])) {
				continue;
			}
			
			$result[] = elgg_extract('name', $field);
		}
		
		return $result;
	}
	
	/**
	 * Get all configured tag fields for groups
	 *
	 * @return string[]
	 */
	protected static function getGroupProfileTagsFields(): array {
		$fields = elgg()->fields->get('group', 'group');
		if (empty($fields)) {
			return [];
		}
		
		$result = [];
		foreach ($fields as $field) {
			if (elgg_extract('#type', $field) !== 'tags') {
				continue;
			}
			
			$result[] = elgg_extract('name', $field);
		}
		
		return $result;
	}
	
	/**
	 * Event to return search results for entity searches
	 *
	 * @param \Elgg\Event $event 'search:result', 'entities'
	 *
	 * @return void|\ElggEntity[]|int
	 * @throws UnexpectedValueException
	 */
	public static function searchEntities(\Elgg\Event $event) {
		$value = $event->getValue();
		if (isset($value)) {
			return null;
		}
		
		$params = $event->getParams();
		
		$service = self::getServiceForEvents($params);
		if (!$service) {
			return;
		}
		
		$service = elgg_trigger_event_results('search_params', 'opensearch', ['search_params' => $params], $service);
		if (!$service instanceof SearchService) {
			throw new UnexpectedValueException("The return value of the 'search_params' 'opensearch' event should return an instanceof \ColdTrick\OpenSearch\Di\SearchService");
		}
		
		if (elgg_extract('count', $params) === true) {
			$result = $service->count();
		} else {
			$result = $service->search();
		}
		
		return self::transformSearchResults($result, $params);
	}

	/**
	 * Is this plugin doing the actual search
	 *
	 * @return bool
	 */
	protected static function handleSearch(): bool {
		return elgg_get_plugin_setting('search', 'opensearch') === 'yes';
	}
	
	/**
	 * Are we using the search in an admin context
	 *
	 * @return bool
	 */
	protected static function isInAdminContext(): bool {
		if (elgg_in_context('admin')) {
			return true;
		}
		
		if (elgg_is_xhr()) {
			$referer = (string) _elgg_services()->request->headers->get('referer');
			$admin = rtrim(elgg_generate_url('admin'), '/');
			
			return str_starts_with($referer, $admin);
		}
		
		return false;
	}
	
	/**
	 * Get the search service
	 *
	 * @param array $params search params
	 *
	 * @return null|\ColdTrick\OpenSearch\Di\SearchService
	 */
	protected static function getServiceForEvents($params): ?SearchService {
		if (!self::handleSearch()) {
			return null;
		}
		
		if (self::isInAdminContext()) {
			return null;
		}
		
		if (self::detectUnsupportedSearchParams($params)) {
			return null;
		}
		
		$service = SearchService::instance();
		if (!$service) {
			return null;
		}
		
		$service->initializeSearchParams($params);
		
		return $service;
	}
	
	/**
	 * Check the search params for unsupported params
	 *
	 * @param array $params search params
	 *
	 * @return bool
	 */
	protected static function detectUnsupportedSearchParams(array $params): bool {
		$keys = [
			'metadata_name_value_pair',
			'metadata_name_value_pairs',
			'relationship',
			'relationship_guid',
		];
		
		foreach ($keys as $key) {
			if (!empty($params[$key])) {
				return true;
			}
		}
		
		if (!elgg_in_context('search')) {
			// make sure all supplied type_subtype_pairs are supported for search
			// if a non-supported type/subtype is found don't handle the search through OpenSearch
			// if not in the search context
			$temp = new SearchParams();
			$normalized_params = $temp->normalizeOptions($params);
			
			$type_subtypes = elgg_extract('type_subtype_pairs', $normalized_params, []);
			$supported_type_subtypes = elgg_entity_types_with_capability('searchable');
			
			foreach ($type_subtypes as $type => $subtypes) {
				if (!isset($supported_type_subtypes[$type])) {
					return true;
				}
				
				if (!is_array($subtypes)) {
					$subtypes = [$type];
				}
				
				$diff = array_diff($subtypes, $supported_type_subtypes[$type]);
				if (!empty($diff)) {
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Transforms search result into events result array
	 *
	 * @param SearchResult $result       the OpenSearch results
	 * @param array        $event_params the search event params
	 *
	 * @return array|int
	 */
	protected static function transformSearchResults(SearchResult $result, array $event_params) {
		if (elgg_extract('count', $event_params) === true) {
			return $result->getCount();
		}
		
		return $result->toEntities();
	}
	
	/**
	 * Event to add profile field filters to search
	 *
	 * @param \Elgg\Event $event 'search_params', 'opensearch'
	 *
	 * @return null|SearchService
	 */
	public static function filterProfileFields(\Elgg\Event $event): ?SearchService {
		$search_params = $event->getParam('search_params');
		if (empty($search_params) || !is_array($search_params)) {
			return null;
		}
		
		$type = elgg_extract('type', $search_params);
		if ($type !== 'user') {
			return null;
		}
		
		$filter = elgg_extract('search_filter', $search_params, []);
		$profile_field_filter = elgg_extract('profile_fields', $filter, []);
		if (empty($profile_field_filter)) {
			return null;
		}
		
		$queries = [];
		foreach ($profile_field_filter as $field_name => $value) {
			$value = strtolower($value);
			$value = str_replace('&amp;', '&', $value);
			$value = str_replace('\\', ' ', $value);
			$value = str_replace('/', ' ', $value);
			$value = trim($value);
			if (elgg_is_empty($value)) {
				continue;
			}
			
			$string_value = $value;
			
			$value = explode(' ', $value);
			$value = array_filter($value);
			$value = implode('* *', $value);
			
			$sub_query = [];
			$sub_query['nested']['path'] = 'metadata';
			
			$shoulds = [];
			$shoulds['bool']['should'][] = [
				'query_string' => [
					'default_field' => "metadata.{$field_name}",
					'query' => "*{$value}*",
					'default_operator' => 'AND',
				],
			];
			$shoulds['bool']['should'][] = [
				'query_string' => [
					'default_field' => "metadata.{$field_name}",
					'query' => "'{$string_value}'",
					'default_operator' => 'AND',
				],
			];
			
			$sub_query['nested']['query']['bool']['must'][] = $shoulds;
			
			$queries['bool']['must'][] = $sub_query;
		}
		
		if (empty($queries)) {
			return null;
		}
		
		/* @var $service SearchService */
		$service = $event->getValue();
		
		$service->getSearchParams()->addQuery($queries);
		
		return $service;
	}
	
	/**
	 * Event to transform a search result to an ElggEntity
	 *
	 * @param \Elgg\Event $event 'to:entity', 'opensearch'
	 *
	 * @return null|\ElggEntity
	 */
	public static function sourceToEntity(\Elgg\Event $event): ?\ElggEntity {
		$hit = $event->getParam('hit');
		$index = elgg_extract('_index', $hit);
		
		$index_prefix = elgg_get_plugin_setting('index', 'opensearch');
		if (!preg_match("/^{$index_prefix}(_[0-9]+)?$/", $index)) {
			return null;
		}
		
		$source = elgg_extract('_source', $hit);
		
		$row = new \stdClass();
		foreach ($source as $key => $value) {
			switch ($key) {
				case 'last_action':
				case 'time_created':
				case 'time_updated':
					// convert the timestamps to unix timestamps
					$value = strtotime($value);
					// now set it
				default:
					$row->$key = $value;
					break;
			}
		}
		
		try {
			return _elgg_services()->entityTable->rowToElggStar($row);
		} catch (ExceptionInterface $e) {
			elgg_log($e->getMessage(), LogLevel::NOTICE);
		}
		
		return null;
	}
}
