<?php

namespace ColdTrick\OpenSearch\Di;

use ColdTrick\OpenSearch\SearchParams;
use ColdTrick\OpenSearch\SearchResult;
use OpenSearch\Common\Exceptions\OpenSearchException;

class SearchService extends BaseClientService {

	/**
	 * Search aggregations
	 *
	 * @var array
	 */
	private $aggregations;
	
	/**
	 * @var SearchParams
	 */
	private $search_params;
	
	/**
	 * @var array
	 */
	private $suggestions;
	
	/**
	 * {@inheritDoc}
	 */
	public static function name() {
		return 'opensearch.searchservice';
	}
	
	/**
	 * Inspect a GUID in opensearch
	 *
	 * @param int  $guid       the GUID to inspect
	 * @param bool $return_raw return full return or only _source (default: false)
	 *
	 * @return false|array
	 */
	public function inspect(int $guid, bool $return_raw = false) {
		
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			$result = $this->getClient()->get([
				'id' => $guid,
				'index' => $this->getReadAlias(),
			]);
			
			if ($return_raw) {
				return $result;
			}
			
			return elgg_extract('_source', $result);
		} catch (OpenSearchException $e) {
			$this->logger->error($e);
		}
		
		return false;
	}
	
	/**
	 * Perform a search on the opensearch client
	 *
	 * @param array $params search params
	 *
	 * @return false|array
	 */
	public function rawSearch(array $params = []) {
		
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			return $this->getClient()->search($params);
		} catch (OpenSearchException $e) {
			$this->logger->error($e);
		}
		
		return [];
	}
	
	/**
	 * Provide the Elgg search parameters before executing a search operation
	 *
	 * @param array $search_params the Elgg search parameters
	 *
	 * @return void
	 */
	public function initializeSearchParams(array $search_params = []) {
		$this->getSearchParams()->initializeSearchParams($search_params);
		$this->getSearchParams()->addEntityAccessFilter();
	}
	
	/**
	 * Execute a search query
	 *
	 * @param array $body optional search body
	 *
	 * @return false|\ColdTrick\OpenSearch\SearchResult
	 */
	public function search(array $body = []) {
		
		if (!$this->isClientReady()) {
			return false;
		}
		
		if (empty($body)) {
			$body = $this->getSearchParams()->getBody();
		}
		
		if (!isset($body['index'])) {
			$body['index'] = $this->getSearchIndex();
		}
		
		$this->requestToScreen($body, 'SEARCH');
		
		$result = [];
		try {
			$result = $this->getClient()->search($body);
		} catch (OpenSearchException $e) {
			// exception already logged by opensearch
		}
		
		$result = new SearchResult($result, $this->getSearchParams()->getParams());
		
		$aggregations = $result->getAggregations();
		if (!empty($aggregations)) {
			$this->setAggregations(elgg_extract('wrapper', $aggregations));
		}
		
		$suggest = $result->getSuggestions();
		if (!empty($suggest)) {
			$this->setSuggestions($suggest);
		}
		
		// reset search params after each search
		$this->getSearchParams()->resetParams();
		
		return $result;
	}
	
	/**
	 * Execute a suggest only search
	 *
	 * @param string $query the original search query which was executed
	 *
	 * @return false|\ColdTrick\OpenSearch\SearchResult
	 */
	public function suggest(string $query) {
		
		if (!$this->isClientReady()) {
			return false;
		}
		
		$this->getSearchParams()->setSuggestion($query);
		
		$body = $this->getSearchParams()->getBody();
		if (!isset($body['index'])) {
			$body['index'] = $this->getSearchIndex();
		}
		
		// no need to do an actual search
		unset($body['body']['query']);
		
		$this->requestToScreen($body, 'SUGGEST');
		
		$result = [];
		try {
			$result = $this->getClient()->search($body);
		} catch (OpenSearchException $e) {
			// exception already logged by opensearch
		}
		
		$result = new SearchResult($result, $this->getSearchParams()->getParams());
		
		$suggest = $result->getSuggestions();
		if (!empty($suggest)) {
			$this->setSuggestions($suggest);
		}
		
		// reset search params after each search
		$this->getSearchParams()->resetParams();
		
		return $result;
	}
	
	/**
	 * Execute a count query
	 *
	 * @param array $body optional search body
	 *
	 * @return false|\ColdTrick\OpenSearch\SearchResult
	 */
	public function count(array $body = []) {
		
		if (!$this->isClientReady()) {
			return false;
		}
		
		if (empty($body)) {
			$body = $this->getSearchParams()->getBody(true);
		}
		
		if (!isset($body['index'])) {
			$body['index'] = $this->getSearchIndex();
		}
		
		$this->requestToScreen($body, 'COUNT');
		
		$result = [];
		try {
			$result = $this->getClient()->count($body);
		} catch (OpenSearchException $e) {
			// exception already logged by opensearch
		}
		
		// reset search params after each search
		$this->getSearchParams()->resetParams();
		
		return new SearchResult($result, $this->getSearchParams()->getParams());
	}
	
	/**
	 * Scroll through a search setup
	 *
	 * @param array $params search params
	 *
	 * @return false|array
	 */
	public function scroll(array $params) {
		if (!$this->isClientReady()) {
			return false;
		}
		
		return $this->getClient()->scroll($params);
	}
	
	/**
	 * Clear a search scroll
	 *
	 * @param array $params search params
	 *
	 * @return false|array
	 */
	public function clearScroll(array $params) {
		if (!$this->isClientReady()) {
			return false;
		}
		
		return $this->getClient()->clearScroll($params);
	}
	
	/**
	 * Set aggregations from search  result
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function setAggregations(array $data) {
		$this->aggregations = $data;
	}
	
	/**
	 * Get aggregations from search result
	 *
	 * @return array
	 */
	public function getAggregations() {
		return $this->aggregations;
	}
	
	/**
	 * Get the search params helper class
	 *
	 * @return \ColdTrick\OpenSearch\SearchParams
	 */
	public function getSearchParams() {
		if (!isset($this->search_params)) {
			$this->search_params = new SearchParams([
				'service' => $this,
			]);
		}
		
		return $this->search_params;
	}
	
	/**
	 * Set suggestions from search result
	 *
	 * @param array $data suggestions
	 *
	 * @return void
	 */
	public function setSuggestions(array $data): void {
		$this->suggestions = $data;
	}
	
	/**
	 * Get suggestions from search
	 *
	 * @return array|null
	 */
	public function getSuggestions(): ?array {
		return $this->suggestions;
	}
	
	/**
	 * Log the current request to developers log
	 *
	 * @param array $params  search params
	 * @param string $action action name (search, count, etc)
	 *
	 * @return void
	 */
	protected function requestToScreen($params, $action = '') {
		
		$cache = elgg_get_config('log_cache');
		if (empty($cache)) {
			// developer tools log to screen is disabled
			return;
		}
		
		$msg = @json_encode($params, JSON_PRETTY_PRINT);
		
		if ($action) {
			$msg = "{$action}:\n $msg";
		}
		
		$this->logger->notice($msg);
	}
}
