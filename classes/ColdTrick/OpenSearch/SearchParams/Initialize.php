<?php

namespace ColdTrick\OpenSearch\SearchParams;

use Elgg\Exceptions\DataFormatException;
use Elgg\Traits\Database\LegacyQueryOptionsAdapter;
use Elgg\Values;

/**
 * Search param helper
 */
trait Initialize {
	
	use LegacyQueryOptionsAdapter;
	
	/**
	 * Convert search params from elgg_search to internal workings of OpenSearch
	 *
	 * @param array $search_params search params as used by elgg_search()
	 *
	 * @return void
	 */
	public function initializeSearchParams(array $search_params = []): void {
		// normalize everything
		$search_params = $this->normalizeOptions($search_params);
		
		// apply limit and offset
		$this->setFrom(elgg_extract('offset', $search_params, 0));
		$this->setLimit(elgg_extract('limit', $search_params, elgg_get_config('default_limit')));
		
		$this->initializeQuery($search_params);
		$this->initializeGUID($search_params);
		$this->initializeContainerGUID($search_params);
		$this->initializeOwnerGUID($search_params);
		$this->initializeSorting($search_params);
		$this->initializeTypeSubtypePairs($search_params);
		$this->initializeTimeConstraints($search_params);
		$this->initializeAccessConstraints($search_params);
	}
	
	/**
	 * Set the search query
	 *
	 * @param array $search_params search params as used by elgg_search()
	 *
	 * @return void
	 */
	protected function initializeQuery(array $search_params = []): void {
		$query = elgg_extract('query', $search_params);
		if (empty($query )) {
			return;
		}

		if (elgg_extract('tokenize', $search_params) === false && stristr($query, ' ')) {
			$query = '"' . $query . '"';
		} elseif (stristr($query, ' ')) {
			$query = $query . ' || "' . $query . '"';
		}
		
		$query_fields = $this->getQueryFields($search_params);
		
		$opensearch_query = [];
		
		$opensearch_query['bool']['must'][]['simple_query_string'] = [
			'fields' => $query_fields,
			'query' => $query,
			'default_operator' => 'AND',
		];
											
		if (!elgg_extract('count', $search_params, false)) {
			$original_query = elgg_extract('query', $search_params);
			
			$this->setSuggestion($original_query);
			$this->setHighlight($this->getDefaultHighlightParams($original_query));
		}
		
		$this->setQuery($opensearch_query);
	}
	
	/**
	 * Get the query fields from the params and apply field boosting
	 *
	 * @param array $search_params search params as used by elgg_search()
	 *
	 * @return string[]
	 */
	protected function getQueryFields(array $search_params = []): array {
		$result = [];
		
		$search_fields = elgg_extract('fields', $search_params, []);
		foreach ($search_fields as $type => $names) {
			if (empty($names)) {
				continue;
			}
			
			$names = array_unique($names);
			
			foreach ($names as $name) {
				switch ($type) {
					case 'attributes':
						$result[] = $name;
						break;
					case 'metadata':
						$result[] = "{$type}.{$name}";
						break;
					case 'annotations':
						// support user profile fields
						if (str_starts_with($name, 'profile:')) {
							$name = substr($name, strlen('profile:'));
							$result[] = "metadata.{$name}";
							break;
						}
						break;
				}
			}
		}
		
		// apply field boosting
		$field_boosting = (array) elgg_extract('field_boosting', $search_params, []);
		
		foreach ($result as $index => $fieldname) {
			$boost = elgg_extract($fieldname, $field_boosting);
			if (elgg_is_empty($boost)) {
				continue;
			}
			
			$result[$index] = "{$fieldname}^{$boost}";
		}
		
		return $result;
	}
	
	/**
	 * Get the highlighting query
	 *
	 * @param string $query the search query
	 *
	 * @return array
	 */
	protected function getDefaultHighlightParams(string $query): array {
		$result = [];
		
		// global settings
		$result['encoder'] = 'html';
		$result['pre_tags'] = ['<strong class="search-highlight search-highlight-color1">'];
		$result['post_tags'] = ['</strong>'];
		$result['number_of_fragments'] = 3;
		$result['fragment_size'] = 100;
		$result['type'] = 'plain';
		
		// title
		$title_query = [];
		$title_query['bool']['must']['match']['title']['query'] = $query;
		$result['fields']['title'] = [
			'number_of_fragments' => 0,
			'highlight_query' => $title_query,
		];
		
		// description
		$description_query = [];
		$description_query['bool']['must']['match']['description']['query'] = $query;
		$result['fields']['description'] = [
			'highlight_query' => $description_query,
		];
		
		// tags
		$tags_query = [];
		$tags_query['bool']['must']['match']['tags']['query'] = $query;
		$result['fields']['tags'] = [
			'number_of_fragments' => 0,
			'highlight_query' => $tags_query,
		];
		
		return $result;
	}
	
	/**
	 * Apply guid filter
	 *
	 * @param array $search_params search params as used by elgg_search()
	 *
	 * @return void
	 */
	protected function initializeGUID(array $search_params = []): void {
		$guid = (array) elgg_extract('guids', $search_params, []);
		$guid = array_filter(array_map(function ($v) {
			return (int) $v;
		}, $guid));
		
		if (empty($guid)) {
			return;
		}
		
		$guid_filter = [];
		$guid_filter['bool']['must'][]['terms']['guid'] = $guid;
		$this->addFilter($guid_filter);
	}
	
	/**
	 * Apply container_guid filter
	 *
	 * @param array $search_params search params as used by elgg_search()
	 *
	 * @return void
	 */
	protected function initializeContainerGUID(array $search_params = []): void {
		$container_guid = (array) elgg_extract('container_guids', $search_params, []);
		$container_guid = array_filter(array_map(function ($v) {
			return (int) $v;
		}, $container_guid));
		
		if (empty($container_guid)) {
			return;
		}
		
		$container_filter = [];
		$container_filter['bool']['must'][]['terms']['container_guid'] = $container_guid;
		$this->addFilter($container_filter);
	}
	
	/**
	 * Apply owner_guid filter
	 *
	 * @param array $search_params search params as used by elgg_search()
	 *
	 * @return void
	 */
	protected function initializeOwnerGUID(array $search_params = []): void {
		$owner_guid = (array) elgg_extract('owner_guids', $search_params);
		$owner_guid = array_filter(array_map(function ($v) {
			return (int) $v;
		}, $owner_guid));
		
		if (empty($owner_guid)) {
			return;
		}
		
		$owner_filter = [];
		$owner_filter['bool']['must'][]['terms']['owner_guid'] = $owner_guid;
		$this->addFilter($owner_filter);
	}
	
	/**
	 * Apply sorting
	 *
	 * @param array $search_params search params as used by elgg_search()
	 *
	 * @return void
	 */
	protected function initializeSorting(array $search_params = []): void {
		$sort_by = elgg_extract('sort_by', $search_params, []);
		if (isset($sort_by['property'])) {
			$sort_by = [$sort_by];
		}
		
		foreach ($sort_by as $clause) {
			$property_type = elgg_extract('property_type', $clause);
			$property = elgg_extract('property', $clause);
			$direction = elgg_extract('direction', $clause, 'desc');
			
			if (!isset($property_type)) {
				$property_type = in_array($property, \ElggEntity::PRIMARY_ATTR_NAMES) ? 'attribute' : 'metadata';
			}
			
			switch ($property_type) {
				case 'attribute':
					$this->addSort($property, [
						'order' => $direction,
						'unmapped_type' => elgg_extract('unmapped_type', $clause, 'long'),
						'missing' => '_last',
					]);
					break;
				
				case 'metadata':
					if (in_array($property, ['name', 'title'])) {
						$this->addSort('title.raw', [
							'order' => $direction,
							'unmapped_type' => 'keyword',
							'missing' => '_last',
						]);
					} elseif (in_array($property, ['description', 'tags'])) {
						$this->addSort($property, [
							'order' => $direction,
							'unmapped_type' => $property === 'description' ? 'string' : 'keyword',
							'missing' => '_last',
						]);
					} else {
						$this->addSort("metadata.{$property}", [
							'order' => $direction,
							'unmapped_type' => elgg_extract('unmapped_type', $clause, 'long'),
							'missing' => '_last',
						]);
					}
					break;
				
				case 'annotation':
				case 'relationship':
					// not supported yet
					break;
				
				case 'score':
					$this->addSort($property, [
						'order' => $direction,
					]);
					break;
				
				case 'counter':
				case 'counters':
					$this->addSort("counters.{$property}", [
						'order' => $direction,
						'unmapped_type' => 'long',
						'missing' => '_last',
					]);
					break;
			}
		}
	}
	
	/**
	 * Add type/subtype limitations
	 *
	 * @param array $search_params search params as used by elgg_search()
	 *
	 * @return void
	 */
	protected function initializeTypeSubtypePairs(array $search_params = []): void {
		$type_subtype_pairs = elgg_extract('type_subtype_pairs', $search_params);
		if (empty($type_subtype_pairs)) {
			return;
		}
		
		$types = [];
		foreach ($type_subtype_pairs as $type => $subtypes) {
			if (empty($subtypes)) {
				$types[] = "{$type}.{$type}";
				continue;
			}
			
			foreach ($subtypes as $subtype) {
				$types[] = "{$type}.{$subtype}";
			}
		}
		
		$type_filter = [];
		$type_filter['terms']['indexed_type'] = $types;
		
		$filter = [];
		$filter['bool']['must'][] = $type_filter;
		
		$this->addFilter($filter);
	}
	
	/**
	 * Add time constraints
	 *
	 * @param array $search_params search params as used by elgg_search()
	 *
	 * @return void
	 */
	protected function initializeTimeConstraints(array $search_params = []): void {
		$make_filter = function($time, $time_field, $direction) {
			try {
				$date = Values::normalizeTime($time);
			} catch (DataFormatException $e) {
				return false;
			}
			
			$range = [];
			$range['range'][$time_field][$direction] = $date->format('c');
			
			$range_filter = [];
			$range_filter['bool']['must'][] = $range;
			
			return $range_filter;
		};
		
		// created_before
		$created_before = elgg_extract('created_before', $search_params);
		if (!empty($created_before)) {
			$filter = $make_filter($created_before, 'time_created', 'lte');
			if (!empty($filter)) {
				$this->addFilter($filter);
			}
		}
		
		// created_after
		$created_after = elgg_extract('created_after', $search_params);
		if (!empty($created_after)) {
			$filter = $make_filter($created_after, 'time_created', 'gte');
			if (!empty($filter)) {
				$this->addFilter($filter);
			}
		}
		
		// updated_before
		$updated_before = elgg_extract('updated_before', $search_params);
		if (!empty($updated_before)) {
			$filter = $make_filter($updated_before, 'time_updated', 'lte');
			if (!empty($filter)) {
				$this->addFilter($filter);
			}
		}
		
		// updated_after
		$updated_after = elgg_extract('updated_after', $search_params);
		if (!empty($updated_after)) {
			$filter = $make_filter($updated_after, 'time_updated', 'gte');
			if (!empty($filter)) {
				$this->addFilter($filter);
			}
		}
	
		// last_action_before
		$last_action_before = elgg_extract('last_action_before', $search_params);
		if (!empty($last_action_before)) {
			$filter = $make_filter($last_action_before, 'last_action', 'lte');
			if (!empty($filter)) {
				$this->addFilter($filter);
			}
		}
		
		// last_action_after
		$last_action_after = elgg_extract('last_action_after', $search_params);
		if (!empty($last_action_after)) {
			$filter = $make_filter($last_action_after, 'last_action', 'gte');
			if (!empty($filter)) {
				$this->addFilter($filter);
			}
		}
	}
	
	/**
	 * Add access_ids constraints
	 *
	 * @param array $search_params search params as used by elgg_search()
	 *
	 * @return void
	 */
	protected function initializeAccessConstraints(array $search_params = []): void {
		$access_ids = (array) elgg_extract('access_ids', $search_params);
		if (empty($access_ids)) {
			return;
		}
		
		$access_filter = [];
		$access_filter['terms']['access_id'] = $access_ids;
		
		$filter = [];
		$filter['bool']['must'][] = $access_filter;
		
		$this->addFilter($filter);
	}
}
