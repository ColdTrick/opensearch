<?php

namespace ColdTrick\OpenSearch\Di;

use Elgg\Logger;
use Elgg\PluginHooksService;
use Elgg\Traits\Di\ServiceFacade;
use OpenSearch\Client;
use OpenSearch\ClientBuilder;
use OpenSearch\Common\Exceptions\OpenSearchException;
use OpenSearch\Common\Exceptions\RuntimeException;

abstract class BaseClientService {

	use ServiceFacade;
	
	/**
	 * @var Client
	 */
	private $client;
	
	/**
	 * @var false|string
	 */
	private $index;
	
	/**
	 * @var false|string
	 */
	private $search_alias;
	
	/**
	 * @var Logger
	 */
	protected $logger;
	
	/**
	 * @var PluginHooksService
	 */
	protected $hooks;
	
	public function __construct(Logger $logger, PluginHooksService $hooks) {
		$this->logger = $logger;
		$this->hooks = $hooks;
	}
	
	/**
	 * Is the client ready for use
	 *
	 * @return bool
	 */
	public function isClientReady() {
		return !empty($this->getClient());
	}
	
	/**
	 * Are the opensearch servers reachable
	 *
	 * @return bool
	 */
	public function ping() {
		if (!$this->isClientReady()) {
			return false;
		}
		
		try {
			return $this->getClient()->ping();
		} catch (OpenSearchException $e) {
			// no need to log
			$this->logger->notice($e);
		}
		
		return false;
	}
	
	/**
	 * Get the opensearch client
	 *
	 * @return false|\opensearch\Client
	 */
	protected function getClient() {
		if (isset($this->client)) {
			return $this->client;
		}
		
		$this->client = false;
		
		$config = $this->getClientConfig();
		if (empty($config)) {
			return false;
		}
		
		try {
			$this->client = ClientBuilder::fromConfig($config);
		} catch (RuntimeException $e) {
			$this->logger->error($e);
		}
		
		return $this->client;
	}
	
	/**
	 * Get client configuration
	 *
	 * @return false|array
	 */
	protected function getClientConfig() {
		
		$hosts = elgg_get_plugin_setting('host', 'opensearch');
		if (empty($hosts)) {
			return false;
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
	 * @return false|string
	 */
	public function getIndex() {
		if (isset($this->index)) {
			return $this->index;
		}
		
		$this->index = false;
		
		$index = elgg_get_plugin_setting('index', 'opensearch');
		if (!empty($index)) {
			$this->index = $index;
		}
		
		return $this->index;
	}
	
	/**
	 * Get the index (or alias) to perform search operations in
	 *
	 * @return false|string
	 */
	public function getSearchIndex() {
		if (!isset($this->search_alias)) {
			$this->search_alias = false;
			
			$setting = elgg_get_plugin_setting('search_alias', 'opensearch');
			if (!empty($setting)) {
				$this->search_alias = $setting;
			}
		}
		
		if (!empty($this->search_alias)) {
			return $this->search_alias;
		}
		
		return $this->getIndex();
	}
}