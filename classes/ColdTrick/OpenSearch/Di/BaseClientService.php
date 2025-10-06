<?php

namespace ColdTrick\OpenSearch\Di;

use Elgg\EventsService;
use Elgg\Logger;
use Elgg\Traits\Di\ServiceFacade;
use OpenSearch\Client;
use OpenSearch\ClientBuilder;
use OpenSearch\Exception\OpenSearchExceptionInterface;
use OpenSearch\Exception\RuntimeException;

/**
 * Base client for OpenSearch
 */
abstract class BaseClientService {

	use ServiceFacade;
	
	/**
	 * @var Client|false
	 */
	private $client;
	
	private string $index_prefix;
	
	private string $search_alias;
	
	/**
	 * Create the service
	 *
	 * @param Logger        $logger Logger service
	 * @param EventsService $events Events service
	 */
	public function __construct(protected Logger $logger, protected EventsService $events) {
	}
	
	/**
	 * Is the client ready for use
	 *
	 * @return bool
	 */
	public function isClientReady(): bool {
		return !empty($this->getClient());
	}
	
	/**
	 * Are the OpenSearch servers reachable
	 *
	 * @return bool
	 */
	public function ping(): bool {
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			return $this->getClient()->ping();
		} catch (OpenSearchExceptionInterface $e) {
			// no need to log
			$this->logger->notice($e);
		}
		
		return false;
	}
	
	/**
	 * Get the OpenSearch client
	 *
	 * @return null|\OpenSearch\Client
	 */
	protected function getClient(): ?Client {
		if (!isset($this->client)) {
			$this->client = false;
			
			$config = $this->getClientConfig();
			if (empty($config)) {
				return null;
			}
			
			try {
				$this->client = ClientBuilder::fromConfig($config);
			} catch (RuntimeException $e) {
				$this->logger->error($e);
			}
		}
		
		return $this->client ?: null;
	}
	
	/**
	 * Get client configuration
	 *
	 * @return null|array
	 */
	protected function getClientConfig(): ?array {
		$hosts = elgg_get_plugin_setting('host', 'opensearch');
		if (empty($hosts)) {
			return null;
		}
		
		$config = [];
		
		// Hostnames
		$hosts = explode(',', $hosts);
		array_walk($hosts, function(&$value) {
			$value = trim($value);
		});
		array_walk($hosts, function(&$value) {
			$value = rtrim($value, '/');
		});
		
		$config['Hosts'] = $hosts;
		
		// SSL verification
		$config['SSLVerification'] = !(bool) elgg_get_plugin_setting('ignore_ssl', 'opensearch');
		
		// basic authentication
		$username = elgg_get_plugin_setting('username', 'opensearch');
		$password = elgg_get_plugin_setting('password', 'opensearch');
		if (!empty($username) && !empty($password)) {
			$config['BasicAuthentication'] = [$username, $password];
		}
		
		// Logger
		$config['Logger'] = $this->logger;
		
		return $config;
	}
	
	/**
	 * Get the name of the index that holds all information
	 *
	 * @return string
	 */
	protected function getIndexPrefix(): string {
		if (!isset($this->index_prefix)) {
			$this->index_prefix = '';
			
			$index = elgg_get_plugin_setting('index', 'opensearch');
			if (!empty($index)) {
				$this->index_prefix = $index;
			}
		}
		
		return $this->index_prefix;
	}
	
	/**
	 * Get the read alias to use
	 *
	 * @return string
	 */
	public function getReadAlias(): string {
		$prefix = $this->getIndexPrefix();
		if (empty($prefix)) {
			return '';
		}
		
		return "{$prefix}_read";
	}
	
	/**
	 * Get the write alias to use
	 *
	 * @return string
	 */
	public function getWriteAlias(): string {
		$prefix = $this->getIndexPrefix();
		if (empty($prefix)) {
			return '';
		}
		
		return "{$prefix}_write";
	}
	
	/**
	 * Get the index (or alias) to perform search operations in
	 *
	 * @return string
	 */
	public function getSearchIndex(): string {
		if (!isset($this->search_alias)) {
			$this->search_alias = $this->getReadAlias();
			
			$setting = elgg_get_plugin_setting('search_alias', 'opensearch');
			if (!empty($setting)) {
				$this->search_alias = $setting;
			}
		}
		
		return $this->search_alias;
	}
}
