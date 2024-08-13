<?php

return array(
	// admin sections
	'admin:opensearch' => "OpenSearch",
	'admin:opensearch:statistics' => "Statistics",
	'admin:opensearch:search' => "Search",
	'admin:opensearch:indices' => "Indices",
	'admin:opensearch:inspect' => "Inspect",
	
	// upgrades
	'opensearch:upgrade:2022061500:title' => "Add OpenSearch index aliases",
	'opensearch:upgrade:2022061500:description' => "Due to some changes in the working of the plugin the OpenSearch index needs some additional aliases. This upgrade will add those to the index.",
	
	// menus
	'opensearch:menu:entity:inspect' => "Inspect in OpenSearch",
	
	'opensearch:menu:search_list:sort:title' => "Change the sort order of the results",
	'opensearch:menu:search_list:sort:relevance' => "Relevance",
	'opensearch:menu:search_list:sort:alpha_az' => "Alphabetical (A-Z)",
	'opensearch:menu:search_list:sort:alpha_za' => "Alphabetical (Z-A)",
	'opensearch:menu:search_list:sort:newest' => "Newest first",
	'opensearch:menu:search_list:sort:oldest' => "Oldest first",
	'opensearch:menu:search_list:sort:member_count' => "Member count",
	
	// generic
	'opensearch:index_management:exception:config:index' => "The event 'config:index', 'opensearch' should return an array for the index configuration",
	'opensearch:index_management:exception:config:mapping' => "The event 'config:mapping', 'opensearch' should return an array for the mapping configuration",
	
	'opensearch:admin_search:results' => "Search Results",
	'opensearch:admin_search:results:info' => "Results will be shown here",
	
	'opensearch:search_score' => "Score: %s",
	'opensearch:suggest' => "Did you mean %s instead of %s?",
	
	// errors
	'opensearch:error:no_client' => "Unable to create an OpenSearch client",
	'opensearch:error:host_unavailable' => "OpenSearch API host unavailable",
	'opensearch:error:no_index' => "No index provided for the given action",
	'opensearch:error:index_not_exists' => "The given index doesn't exist: %s",
	'opensearch:error:alias_not_configured' => "No alias is configured in the plugin settings",
	'opensearch:error:search' => "An error occurred during your search operation. Please contact the site administrator if this problem persists.",
	
	// settings
	'opensearch:settings:pattern:float' => "Only numbers (0-9) and period (.) are allowed",
	
	'opensearch:settings:host:header' => "OpenSearch host settings",
	'opensearch:settings:host' => "API host",
	'opensearch:settings:host:help' => "You can configure multiple hosts by separating them with a comma (,).",
	'opensearch:settings:ignore_ssl' => "Disable SSL verification",
	'opensearch:settings:ignore_ssl:help' => "If your hosts use HTTPS, but you use self-signed certificates you can disable SSL verification with this setting.",
	'opensearch:settings:username' => "Username",
	'opensearch:settings:username:help' => "If your OpenSearch cluster is protected by a username/password enter the username here",
	'opensearch:settings:password' => "Password",
	'opensearch:settings:password:help' => "If your OpenSearch cluster is protected by a username/password enter the password here",
	'opensearch:settings:index' => "Index to use for Elgg data",
	'opensearch:settings:index:help' => "You need to configure an index to store all the Elgg data in. If you don't know which index to use, maybe '%s' is a suggestion?",
	'opensearch:settings:search_alias' => "Search index alias (optional)",
	'opensearch:settings:search_alias:help' => "If you wish to search in more then one index, you can configure an alias to search in. This alias will also be applied to the Elgg index.",
	
	'opensearch:settings:features:header' => "Behaviour settings",
	'opensearch:settings:sync' => "Synchronize Elgg data to OpenSearch",
	'opensearch:settings:sync:help' => "You need to enable synchronization to OpenSearch, this will prevent inserting data on your OpenSearch server if you're not ready yet.",
	'opensearch:settings:search' => "Use OpenSearch as the search engine",
	'opensearch:settings:search:help' => "Once you've set up OpenSearch correctly and it's populated with data, you can switch to use it as the search engine.",
	'opensearch:settings:search_score' => "Show search score in results",
	'opensearch:settings:search_score:help' => "Display the search result score to administrators in the search results. This can help explain why results are ordered as they are.",
	'opensearch:settings:cron_validate' => "Validate the search index daily",
	'opensearch:settings:cron_validate:help' => "Validate the index to make sure no content is left in the index that shouldn't be there and all content that should be there is present.",
	
	'opensearch:settings:type_boosting:title' => "Content Type Boosting",
	'opensearch:settings:type_boosting:info' => "If you want the score of a content type to be boosted during query time you can configure multipliers here.
If you want similar search results to be ordered based on the type you should use small multipliers like 1.01.
If you always want users to be on top of a combined query, regardless of the quality of the hit, you can use big multipliers.

More information on query time boosting can be found in the OpenSearch documentation website.",
	'opensearch:settings:type_boosting:type' => "Content Type",
	'opensearch:settings:type_boosting:multiplier' => "Multiplier",
	
	'opensearch:settings:decay:title' => "Content Decay",
	'opensearch:settings:decay:info' => "If configured the decay score multiplier will be applied to the content results.",
	'opensearch:settings:decay_offset' => "Offset",
	'opensearch:settings:decay_offset:help' => "Enter the number of days before (min) the decay multiplier will be applied.",
	'opensearch:settings:decay_scale' => "Scale",
	'opensearch:settings:decay_scale:help' => "Enter the number of days until (max) the lowest decay multiplier will be applied.",
	'opensearch:settings:decay_decay' => "Decay",
	'opensearch:settings:decay_decay:help' => "Enter the decay multiplier that will be applied when scale is reached. Enter a number between 1 and 0. The lower the number, the lower the score.",
	'opensearch:settings:decay_time_field' => "Time field",
	'opensearch:settings:decay_time_field:help' => "Select the time field to apply the decay on",
	'opensearch:settings:decay_time_field:time_created' => "Creation date",
	'opensearch:settings:decay_time_field:time_updated' => "Last update",
	'opensearch:settings:decay_time_field:last_action' => "Last action",
	
	// statistics
	'opensearch:stats:cluster' => "Cluster information",
	'opensearch:stats:cluster_name' => "Cluster name",
	'opensearch:stats:es_version' => "OpenSearch version",
	'opensearch:stats:lucene_version' => "Lucene version",
	
	'opensearch:stats:index:index' => "Index: %s",
	'opensearch:stats:index:stat' => "Statistic",
	'opensearch:stats:index:value' => "Value",
	
	'opensearch:stats:elgg' => "Elgg information",
	'opensearch:stats:elgg:total' => "Content that should have been indexed",
	'opensearch:stats:elgg:total:help' => "This could include content (like banned users) which isn't actually indexed by OpenSearch.",
	'opensearch:stats:elgg:no_index_ts' => "New content to be indexed",
	'opensearch:stats:elgg:update' => "Updated content to be reindexed",
	'opensearch:stats:elgg:reindex' => "Content to be reindexed",
	'opensearch:stats:elgg:reindex:action' => "You can force a refresh of all already indexed entities by clicking on this action.",
	'opensearch:stats:elgg:reindex:last_ts' => "Current time to be used to compare if reindex is needed: %s",
	'opensearch:stats:elgg:delete' => "Content waiting to be deleted",
	
	// index management
	'opensearch:indices:index' => "Index",
	'opensearch:indices:alias' => "Alias",
	'opensearch:indices:aliases' => "aliases",
	'opensearch:indices:mappings' => "Mappings",
	'opensearch:indices:mappings:add' => "Add / update",
	
	// inspect
	'opensearch:inspect:guid' => "Please enter the GUID of the entity you wish to inspect",
	'opensearch:inspect:guid:help' => "All entities in Elgg have a GUID, mostly you can find this in the URL to the entity (eg blog/view/1234)",
	'opensearch:inspect:submit' => "Inspect",
	
	'opensearch:inspect:result:title' => "Inspection results",
	'opensearch:inspect:result:elgg' => "Elgg",
	'opensearch:inspect:result:opensearch' => "OpenSearch",
	'opensearch:inspect:result:error:type_subtype' => "The type/subtype of this entity isn't supported for indexing in OpenSearch.",
	'opensearch:inspect:result:error:not_indexed' => "The entity is not yet indexed",
	'opensearch:inspect:result:last_indexed:none' => "This entity has not yet been indexed",
	'opensearch:inspect:result:last_indexed:scheduled' => "This entity is scheduled to be (re)indexed",
	'opensearch:inspect:result:last_indexed:time' => "This entity was last indexed: %s",
	'opensearch:inspect:result:reindex' => "Schedule for reindexing",
	'opensearch:inspect:result:delete' => "Remove entity from index",
	
	// forms
	'opensearch:forms:admin_search:query:placeholder' => "Enter your search query here",
	
	// CLI
	'opensearch:cli:error:client' => "The OpenSearch client isn't ready yet, please check the plugin settings",
	
	'opensearch:progress:start:no_index_ts' => "Adding new documents to index",
	'opensearch:progress:start:update' => "Updating documents in index",
	'opensearch:progress:start:reindex' => "Reindexing documents in index",
	
	// Sync
	'opensearch:cli:sync:description' => "Synchronize the Elgg database to the OpenSearch index",
	'opensearch:cli:sync:delete' => "Old documents have been removed from the index",
	'opensearch:cli:sync:delete:error' => "An error occurred while removing old documents from the index",
	'opensearch:cli:sync:no_index_ts' => "Added new documents to the index",
	'opensearch:cli:sync:no_index_ts:error' => "An error occurred while adding new documents to the index",
	'opensearch:cli:sync:update' => "Updated documents in the index",
	'opensearch:cli:sync:update:error' => "An error occurred while updating documents in the index",
	'opensearch:cli:sync:reindex' => "Reindexed documents in the index",
	'opensearch:cli:sync:reindex:error' => "An error occurred while reindexing documents in the index",
	
	// rebuild
	'opensearch:cli:rebuild:description' => "Rebuild the index with new index configuration and/or mappings",
	'opensearch:cli:rebuild:current_index:error' => "No current index could be found",
	'opensearch:cli:rebuild:disable_indexing' => "Disabling indexing during the index rebuild",
	'opensearch:cli:rebuild:create:error' => "An error occurred while creating the new index",
	'opensearch:cli:rebuild:create' => "New index created",
	'opensearch:cli:rebuild:mapping:error' => "An error occurred while adding the mappings to the index",
	'opensearch:cli:rebuild:mapping' => "Mappings added to the index",
	'opensearch:cli:rebuild:reindex_start' => "Starting the reindex process",
	'opensearch:cli:rebuild:reindex:error' => "An error occurred while reindexing",
	'opensearch:cli:rebuild:reindex' => "Reindexing complete",
	'opensearch:cli:rebuild:add_alias:read:error' => "An error occurred while adding the 'read' alias to the new search index",
	'opensearch:cli:rebuild:add_alias:read' => "The 'read' alias was successfully added to the new search index",
	'opensearch:cli:rebuild:add_alias:write:error' => "An error occurred while adding the 'write' alias to the new search index",
	'opensearch:cli:rebuild:add_alias:write' => "The 'write' alias was successfully added to the new search index",
	'opensearch:cli:rebuild:remove_alias:read:error' => "An error occurred while removing the 'read' alias from the old search index",
	'opensearch:cli:rebuild:remove_alias:read' => "The 'read' alias was successfully removed from the old search index",
	'opensearch:cli:rebuild:remove_alias:write:error' => "An error occurred while removing the 'write' alias from the old search index",
	'opensearch:cli:rebuild:remove_alias:write' => "The 'write' alias was successfully removed from the old search index",
	'opensearch:cli:rebuild:enable_indexing' => "Indexing has been enabled",
	'opensearch:cli:rebuild:delete:error' => "An error occurred while removing the old search index",
	'opensearch:cli:rebuild:delete' => "The old search index was removed",
	
	// actions
	'opensearch:action:admin:index_management:error:delete' => "An error occurred during the deletion of the index: %s",
	'opensearch:action:admin:index_management:error:create:exists' => "You can't create the index '%s' it already exists",
	'opensearch:action:admin:index_management:error:create' => "An error occurred during the creation of the index: %s",
	'opensearch:action:admin:index_management:error:add_mappings' => "An error occurred during the creation of the mappings for the index: %s",
	'opensearch:action:admin:index_management:error:task' => "The task '%s' is not supported",
	'opensearch:action:admin:index_management:error:add_alias:exists' => "The alias '%s' already exists on the index '%s'",
	'opensearch:action:admin:index_management:error:add_alias' => "An error occurred while adding the alias '%s' to the index '%s'",
	'opensearch:action:admin:index_management:error:delete_alias:exists' => "The alias '%s' doesn't exists on the index '%s'",
	'opensearch:action:admin:index_management:error:delete_alias' => "An error occurred while deleting the alias '%s' from the index '%s'",
	
	'opensearch:action:admin:index_management:delete' => "The index '%s' was deleted",
	'opensearch:action:admin:index_management:create' => "The index '%s' was created",
	'opensearch:action:admin:index_management:add_mappings' => "Mappings for the index '%s' are created",
	'opensearch:action:admin:index_management:add_alias' => "The alias '%s' was added to the index '%s'",
	'opensearch:action:admin:index_management:delete_alias' => "The alias '%s' was deleted from the index '%s'",
	
	'opensearch:action:admin:reindex_entity:success' => "The entity is scheduled for reindexing",
	'opensearch:action:admin:delete_entity:success' => "The entity is scheduled for deletion from the index",
);
