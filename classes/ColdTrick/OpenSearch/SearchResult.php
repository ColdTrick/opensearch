<?php

namespace ColdTrick\OpenSearch;

/**
 * Search result after a query to OpenSearch
 */
class SearchResult {
	
	/**
	 * Create a new SearchResults helper
	 *
	 * @param array $result        results from OpenSearch
	 * @param array $search_params original search params
	 */
	public function __construct(protected array $result, protected array $search_params) {
	}
	
	/**
	 * Get search results
	 *
	 * @return array
	 */
	public function getResult(): array {
		return $this->result;
	}
	
	/**
	 * Get search result count
	 *
	 * @return int
	 */
	public function getCount(): int {
		$hits = elgg_extract('hits', $this->result);
		if ($hits !== null) {
			return elgg_extract('total', $hits, 0);
		}
		
		return elgg_extract('count', $this->result, 0);
	}
	
	/**
	 * Get search hits
	 *
	 * @return array
	 */
	public function getHits(): array {
		$hits = elgg_extract('hits', $this->result, []);
		
		return elgg_extract('hits', $hits, []);
	}
	
	/**
	 * Get a single hit from the results
	 *
	 * @param int $id the id in OpenSearch (usually an Elgg GUID)
	 *
	 * @return null|array
	 */
	public function getHit(int $id): ?array {
		$hits = $this->getHits();
		if (empty($hits)) {
			return null;
		}
		
		foreach ($hits as $hit) {
			$_id = (int) elgg_extract('_id', $hit);
			if ($id === $_id) {
				return $hit;
			}
		}
		
		return null;
	}
	
	/**
	 * Get aggregations
	 *
	 * @return array
	 */
	public function getAggregations(): array {
		return elgg_extract('aggregations', $this->result, []);
	}
	
	/**
	 * Get search suggestions
	 *
	 * @return array
	 */
	public function getSuggestions(): array {
		return elgg_extract('suggest', $this->result, []);
	}
	
	/**
	 * Convert search results to entities
	 *
	 * @return \ElggEntity[]
	 */
	public function toEntities(): array {
		$hits = $this->getHits();
		if (empty($hits)) {
			return [];
		}
		
		$entities = [];
		
		foreach ($hits as $hit) {
			$params = [
				'hit' => $hit,
				'search_params' => $this->search_params,
			];
			
			$hit = elgg_trigger_event_results('to:entity:before', 'opensearch', $params, $hit);
			$params['hit'] = $hit;
			
			$entity = elgg_trigger_event_results('to:entity', 'opensearch', $params, null);
			if (!$entity instanceof \ElggEntity) {
				continue;
			}
			
			$source = elgg_extract('_source', $hit);
			
			// set correct search highlighting
			$highlight = (array) elgg_extract('highlight', $hit, []);
			
			// title
			$highlight_title = '';
			$title = elgg_extract('title', $highlight);
			if (!empty($title)) {
				if (is_array($title)) {
					$title = implode('', $title);
				}
				
				$highlight_title = $title;
			}
						
			// no title found
			if (empty($highlight_title)) {
				$highlight_title = elgg_extract('title', $source);
			}
			
			$entity->setVolatileData('search_matched_title', $highlight_title);
			
			// description
			$desc = elgg_extract('description', $highlight);
			if (empty($desc)) {
				$desc = elgg_get_excerpt((string) elgg_extract('description', $source));
			}
			
			if (is_array($desc)) {
				$desc = implode('...', $desc);
			}
			
			$entity->setVolatileData('search_matched_description', $desc);
			
			// tags
			$tags = elgg_extract('tags', $highlight);
			if (!empty($tags)) {
				if (is_array($tags)) {
					$tags = implode(', ', $tags);
				}
				
				$label = elgg_format_element('strong', [
					'class' => 'search-match-extra-label',
				], elgg_echo('tags'));
				
				// format output
				$tags = elgg_format_element('p', [
					'class' => 'elgg-output search-match-extra',
				], $label . ': ' . $tags);
				
				$entity->setVolatileData('search_matched_extra', $tags);
			}
			
			// score
			$score = elgg_extract('_score', $hit);
			if ($score) {
				$entity->setVolatileData('search_score', $score);
			}
			
			$entities[] = $entity;
		}
		
		return $entities;
	}
	
	/**
	 * Get the GUIDs of all results
	 *
	 * @return int[]
	 */
	public function toGuids(): array {
		$hits = $this->getHits();
		if (empty($hits)) {
			return [];
		}
		
		$guids = [];
		
		foreach ($hits as $hit) {
			$source = elgg_extract('_source', $hit);
			if (empty($source)) {
				continue;
			}
			
			$guid = (int) elgg_extract('guid', $source);
			if (empty($guid)) {
				continue;
			}
			
			$guids[] = $guid;
		}
		
		return $guids;
	}
}
