<?php

if (!defined('OPENSEARCH_INDEXED_NAME')) {
	define('OPENSEARCH_INDEXED_NAME', 'opensearch_last_indexed');
}

require_once(__DIR__ . '/lib/functions.php');

return [
	'plugin' => [
		'version' => '8.0',
		'dependencies' => [
			'search' => [
				'position' => 'after',
			],
		],
	],
	'settings' => [
		'sync' => 'no',
		'search' => 'no',
		'search_score' => 'no',
		'cron_validate' => 'no',
		'ignore_ssl' => 0,
		'decay_time_field' => 'time_created',
	],
	'actions' => [
		'opensearch/admin_search' => [
			'access' => 'admin',
		],
		'opensearch/admin/index_management' => [
			'access' => 'admin',
		],
		'opensearch/admin/reindex' => [
			'access' => 'admin',
		],
		'opensearch/admin/reindex_entity' => [
			'access' => 'admin',
		],
		'opensearch/admin/delete_entity' => [
			'access' => 'admin',
		],
	],
	'cli_commands' => [
		\ColdTrick\OpenSearch\Cli\Rebuild::class,
		\ColdTrick\OpenSearch\Cli\Sync::class,
	],
	'events' => [
		'ban' => [
			'user' => [
				'\ColdTrick\OpenSearch\EventDispatcher::banUser' => [],
			],
		],
		'create' => [
			'all' => [
				'\ColdTrick\OpenSearch\EventDispatcher::create' => [],
			],
		],
		'cron' => [
			'daily' => [
				'\ColdTrick\OpenSearch\Cron::dailyCleanup' => [],
			],
			'minute' => [
				'\ColdTrick\OpenSearch\Cron::minuteSync' => [],
			],
		],
		'delete' => [
			'all' => [
				'\ColdTrick\OpenSearch\EventDispatcher::delete' => [],
			],
		],
		'disable' => [
			'all' => [
				'\ColdTrick\OpenSearch\EventDispatcher::disable' => [],
			],
		],
		'export:counters' => [
			'opensearch' => [
				'\ColdTrick\OpenSearch\Export::exportGroupMemberCount' => [],
				'\ColdTrick\OpenSearch\Export::exportLikesCount' => [],
				'\ColdTrick\OpenSearch\Export::exportCommentsCount' => [],
			],
		],
		'export:metadata_names' => [
			'opensearch' => [
				'\ColdTrick\OpenSearch\Export::exportProfileMetadata' => [],
			],
		],
		'register' => [
			'menu:admin_header' => [
				'\ColdTrick\OpenSearch\Menus\AdminHeader::register' => [],
			],
			'menu:entity' => [
				'\ColdTrick\OpenSearch\Menus\Entity::inspect' => [],
			],
			'menu:entity_explorer' => [
				'\ColdTrick\OpenSearch\Menus\EntityExplorer::inspect' => [],
			],
		],
		'search:params' => [
			'all' => [
				'\ColdTrick\OpenSearch\SearchEvents::searchParams' => [],
			],
		],
		'search:fields' => [
			'all' => [
				'\ColdTrick\OpenSearch\SearchEvents::searchFields' => [
					'priority' => 999,
				],
				'\ColdTrick\OpenSearch\SearchEvents::searchFieldsNameToTitle' => [
					'priority' => 999,
				],
			],
			'group' => [
				'\ColdTrick\OpenSearch\SearchEvents::groupSearchFields' => [],
			],
			'object' => [
				'\ColdTrick\OpenSearch\SearchEvents::objectSearchFields' => [],
			],
			'user' => [
				'\ColdTrick\OpenSearch\SearchEvents::userSearchFields' => [],
			],
		],
		'search:results' => [
			'combined:all' => [
				'\ColdTrick\OpenSearch\SearchEvents::searchEntities' => [],
			],
			'combined:objects' => [
				'\ColdTrick\OpenSearch\SearchEvents::searchEntities' => [],
			],
			'entities' => [
				'\ColdTrick\OpenSearch\SearchEvents::searchEntities' => [],
			],
		],
		'search_params' => [
			'opensearch' => [
				'\ColdTrick\OpenSearch\SearchEvents::filterProfileFields' => [],
			],
		],
		'to:entity' => [
			'opensearch' => [
				'\ColdTrick\OpenSearch\SearchEvents::sourceToEntity' => [],
			],
		],
		'to:object' => [
			'entity' => [
				'\ColdTrick\OpenSearch\Export::entityToObject' => [],
				'\ColdTrick\OpenSearch\Export::entityRelationshipsToObject' => [],
				'\ColdTrick\OpenSearch\Export::entityMetadataToObject' => [],
				'\ColdTrick\OpenSearch\Export::entityCountersToObject' => [],
				'\ColdTrick\OpenSearch\Export::profileTagFieldsToTags' => [],
				'\ColdTrick\OpenSearch\Export::stripTags' => [
					'priority' => 9999,
				],
			],
		],
		'trash:after' => [
			'all' => [
				'\ColdTrick\OpenSearch\EventDispatcher::delete' => [],
			],
		],
		'update' => [
			'all' => [
				'\ColdTrick\OpenSearch\EventDispatcher::update' => [],
			],
		],
		'view_vars' => [
			'object/elements/imprint/contents' => [
				'\ColdTrick\OpenSearch\Views::displaySearchScoreInImprint' => [],
			],
			'resources/livesearch/users' => [
				'\ColdTrick\OpenSearch\Views::allowBannedUsers' => [
					'priority' => 600,
				],
			],
			'resources/search/index' => [
				'\ColdTrick\OpenSearch\Views::setDefaultSearchSorting' => [],
			],
			'search/entity' => [
				'\ColdTrick\OpenSearch\Views::preventSearchFieldChanges' => [],
				'\ColdTrick\OpenSearch\Views::enableSearchScorePresentation' => [],
			],
			'user/elements/imprint/contents' => [
				'\ColdTrick\OpenSearch\Views::displaySearchScoreInImprint' => [],
			],
		],
	],
	'view_extensions' => [
		'admin.css' => [
			'opensearch/admin.css' => [],
		],
		'forms/search' => [
			'opensearch/search/suggest' => [],
		],
	],
];
