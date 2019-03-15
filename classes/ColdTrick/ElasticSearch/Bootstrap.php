<?php

namespace ColdTrick\ElasticSearch;

use Elgg\DefaultPluginBootstrap;

class Bootstrap extends DefaultPluginBootstrap {
	
	/**
	 * {@inheritDoc}
	 */
	public function init() {
		
		// ajax views
		elgg_register_ajax_view('elasticsearch/logging/view');
		
		// view extends
		elgg_extend_view('forms/search_advanced/search', 'elasticsearch/search/suggest', 800);
		elgg_extend_view('admin.css', 'elasticsearch/admin.css');
		
		// plugin hooks
		$this->registerPluginHooks();
		
		// events
		$this->registerEvents();
	}
	
	/**
	 * Register plugin hook handlers
	 *
	 * @return void
	 */
	protected function registerPluginHooks() {
		
		// other hooks
		$hooks = $this->elgg()->hooks;
		
		// plugin hooks
		$hooks->registerHandler('register', 'menu:page', __NAMESPACE__ . '\Admin::pageMenu');
		$hooks->registerHandler('cron', 'minute', __NAMESPACE__ . '\Cron::minuteSync');
		$hooks->registerHandler('cron', 'daily', __NAMESPACE__ . '\Cron::dailyCleanup');
		$hooks->registerHandler('view_vars', 'object/elements/imprint/contents', __NAMESPACE__ . '\Views::displaySearchScoreInImprint', 999);
		
		// search hooks
		$hooks->registerHandler('search:params', 'all', __NAMESPACE__ . '\SearchHooks::initSearchHooks');
		
		// menu hooks
		$hooks->registerHandler('register', 'menu:search_list', __NAMESPACE__ . '\SearchHooks::registerSortMenu');
		
		// extend exportable values
		$hooks->registerHandler('to:object', 'entity', __NAMESPACE__ . '\Export::entityToObject');
		$hooks->registerHandler('to:object', 'entity', __NAMESPACE__ . '\Export::entityRelationshipsToObject');
		$hooks->registerHandler('to:object', 'entity', __NAMESPACE__ . '\Export::entityMetadataToObject');
		$hooks->registerHandler('to:object', 'entity', __NAMESPACE__ . '\Export::entityCountersToObject');
		$hooks->registerHandler('to:object', 'entity', __NAMESPACE__ . '\Export::profileTagFieldsToTags');
		$hooks->registerHandler('to:object', 'entity', __NAMESPACE__ . '\Export::stripTags', 9999);
		$hooks->registerHandler('export:metadata_names', 'elasticsearch', __NAMESPACE__ . '\Export::exportProfileMetadata');
		$hooks->registerHandler('export:counters', 'elasticsearch', __NAMESPACE__ . '\Export::exportGroupMemberCount');
		$hooks->registerHandler('export:counters', 'elasticsearch', __NAMESPACE__ . '\Export::exportLikesCount');
		$hooks->registerHandler('export:counters', 'elasticsearch', __NAMESPACE__ . '\Export::exportCommentsCount');
		$hooks->registerHandler('to:entity', 'elasticsearch', __NAMESPACE__ . '\SearchHooks::sourceToEntity');
	}
	
	/**
	 * Register event handlers
	 *
	 * @return void
	 */
	protected function registerEvents() {
		$events = $this->elgg()->events;
		
		$events->registerHandler('create', 'all', __NAMESPACE__ . '\EventDispatcher::create');
		$events->registerHandler('update', 'all', __NAMESPACE__ . '\EventDispatcher::update');
		$events->registerHandler('delete', 'all', __NAMESPACE__ . '\EventDispatcher::delete');
		$events->registerHandler('disable', 'all', __NAMESPACE__ . '\EventDispatcher::disable');
		$events->registerHandler('ban', 'user', __NAMESPACE__ . '\EventDispatcher::banUser');
	}
}
