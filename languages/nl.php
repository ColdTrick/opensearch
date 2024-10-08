<?php
/**
 * This file was created by Translation Editor v14.0
 * On 2024-08-13 12:04
 */

return array (
  'opensearch:inspect:result:not_found:elgg' => 'De entiteit kon niet worden gevonden in Elgg en de OpenSearch index',
  'opensearch:inspect:result:not_found:index' => 'De entiteit kon niet worden gevonden in Elgg, maar bestaat nog wel in de OpenSearch index',
  'opensearch:upgrade:2022061500:title' => 'Voeg aliassen toe aan de OpenSearch index',
  'opensearch:upgrade:2022061500:description' => 'Wegens een aanpassing in de werking van de plugin moet de OpenSearch index een aantal extra aliassen krijgen. Deze upgrade zal deze toevoegen aan de index.',
  'opensearch:cli:rebuild:description' => 'Herbouw de index met een nieuwe configuratie en/of mappings',
  'opensearch:cli:rebuild:current_index:error' => 'Er kon geen huidige index worden gevonden',
  'opensearch:cli:rebuild:disable_indexing' => 'Indexeren uitgeschakeld tijdens het herbouwen van de index',
  'opensearch:cli:rebuild:create:error' => 'Er is een fout opgetreden tijdens het aanmaken van de nieuwe index',
  'opensearch:cli:rebuild:create' => 'Nieuwe index is aangemaakt',
  'opensearch:cli:rebuild:mapping:error' => 'Er is een fout opgetreden tijdens het toepassen van de mappings op de index',
  'opensearch:cli:rebuild:mapping' => 'Mappings zijn toegepast op de index',
  'opensearch:cli:rebuild:reindex_start' => 'Het herindexeren van de index is gestart',
  'opensearch:cli:rebuild:reindex:error' => 'Er is een fout opgetreden tijdens het herindexeren',
  'opensearch:cli:rebuild:reindex' => 'Herindexatie afgerond',
  'opensearch:cli:rebuild:add_alias:read:error' => 'Er is een fout opgetreden tijdens het toevoegen van de \'read\' alias op de nieuwe index',
  'opensearch:cli:rebuild:add_alias:read' => 'De \'read\' alias is succesvol toegevoegd aan de nieuwe index',
  'opensearch:cli:rebuild:add_alias:write:error' => 'Er is een fout opgetreden tijdens het toevoegen van de \'write\' alias op de nieuwe index',
  'opensearch:cli:rebuild:add_alias:write' => 'De \'write\' alias is succesvol toegevoegd aan de nieuwe index',
  'opensearch:cli:rebuild:remove_alias:read:error' => 'Er is een fout opgetreden tijdens het verwijderen van de \'read\' alias van de oude index',
  'opensearch:cli:rebuild:remove_alias:read' => 'De \'read\' alias is succesvol verwijderd van de oude index',
  'opensearch:cli:rebuild:remove_alias:write:error' => 'Er is een fout opgetreden tijdens het verwijderen van de \'write\' alias van de oude index',
  'opensearch:cli:rebuild:remove_alias:write' => 'De \'write\' alias is succesvol verwijderd van de oude index',
  'opensearch:cli:rebuild:enable_indexing' => 'Indexatie is ingeschakeld',
  'opensearch:cli:rebuild:delete:error' => 'Er is een fout opgetreden tijdens het verwijderen van de oude index',
  'opensearch:cli:rebuild:delete' => 'De oude index is succesvol verwijderd',
  'opensearch:settings:username' => 'Gebruikersnaam',
  'opensearch:settings:username:help' => 'Indien je OpenSearch cluster is beveiligd met een gebruikersnaam/wachtwoord, geef dan hier de gebruikersnaam op',
  'opensearch:settings:password' => 'Wachtwoord',
  'opensearch:settings:password:help' => 'Indien je OpenSearch cluster is beveiligd met een gebruikersnaam/wachtwoord, geef dan hier de wachtwoord op',
  'admin:opensearch' => 'OpenSearch',
  'opensearch:menu:entity:inspect' => 'Inspecteer in OpenSearch',
  'opensearch:error:no_client' => 'Het is niet gelukt om een OpenSearch client te maken',
  'opensearch:error:host_unavailable' => 'OpenSearch API host niet beschikbaar',
  'opensearch:settings:host:header' => 'OpenSearch host instellingen',
  'opensearch:settings:sync' => 'Synchroniseer Elgg data naar OpenSearch',
  'opensearch:settings:search' => 'Gebruik OpenSearch als de search engine',
  'opensearch:settings:search:help' => 'Zodra OpenSearch goed is ingericht en gevuld is met data, kun je hiermee bepalen of de zoekresultaten uit OpenSearch komen.',
  'opensearch:settings:type_boosting:info' => 'Indien je de score van een content type wilt beïnvloeden gedurende de zoekopdracht kun je hieronder een vermenigvuldigingsfactor opgeven.
Als je soortgelijke zoekresultaten wilt sorteren op basis van hun content type moet je een kleine vermenigvuldigingsfactor gebruiken, bijvoorbeeld 1.01.
Als je gebruikers altijd bovenaan de resultaten wilt hebben, ongeacht de kwaliteit van het resultaat, kun je een grote vermenigvuldigingsfactor gebruiken.

Meer informatie over content type boosting kun je kijken op de OpenSearch documentatie website.',
  'opensearch:stats:es_version' => 'OpenSearch versie',
  'opensearch:stats:elgg:total:help' => 'Dit kan content bevatten (zoals geblokkeerde gebruikers) welke niet zal worden gesynchroniseerd naar OpenSearch.',
  'opensearch:inspect:result:opensearch' => 'OpenSearch',
  'opensearch:inspect:result:error:type_subtype' => 'Het type/subtype van deze entity wordt niet ondersteund om te worden geïndexeerd in OpenSearch',
  'opensearch:cli:error:client' => 'De OpenSearch client is nog niet klaar, controleer de plugin instellingen',
  'opensearch:cli:sync:description' => 'Synchroniseer de Elgg database naar de OpenSearch index',
  'opensearch:index_management:exception:config:index' => 'Het resultaat van het \'config:index\', \'opensearch\' event moet een array zijn voor de index configuratie',
  'opensearch:index_management:exception:config:mapping' => 'Het resultaat van het \'config:mapping\', \'opensearch\' event moet een array zijn voor de mapping configuratie',
  'opensearch:indices:aliases' => 'aliassen',
  'opensearch:indices:mappings' => 'Mappings',
  'opensearch:indices:mappings:add' => 'Toevoegen / bijwerken',
  'opensearch:progress:start:no_index_ts' => 'Nieuwe documenten toevoegen aan de index',
  'opensearch:progress:start:update' => 'Documenten in de index bijwerken',
  'opensearch:progress:start:reindex' => 'Herindexeren van documenten in de index',
  'opensearch:cli:sync:delete' => 'Oude documenten zijn verwijderd uit de index',
  'opensearch:cli:sync:delete:error' => 'Er is een fout opgetreden tijdens het verwijderen van oude documenten uit de index',
  'opensearch:cli:sync:no_index_ts' => 'Nieuwe documenten zijn toegevoegd aan de index',
  'opensearch:cli:sync:no_index_ts:error' => 'Er is een fout opgetreden tijdens het toevoegen van nieuwe documenten aan de index',
  'opensearch:cli:sync:update' => 'Documenten in de index zijn bijgewerkt',
  'opensearch:cli:sync:update:error' => 'Er is een fout opgetreden tijdens het bijwerken van documenten in de index',
  'opensearch:cli:sync:reindex' => 'Herindexeren van document in de index is afgerond',
  'opensearch:cli:sync:reindex:error' => 'Er is een fout opgetreden tijdens het herindexeren van documenten in de index',
  'opensearch:settings:pattern:float' => 'Alleen getallen (0-9) en een punt (.) zijn toegestaan',
  'opensearch:settings:ignore_ssl' => 'Schakel SSL verificatie uit',
  'opensearch:settings:ignore_ssl:help' => 'Indien de host HTTPS gebruikt, maar dit gebeurt middels een self-signed certificaat kun je SSL verificatie uitschakelen middels deze instelling.',
  'opensearch:settings:search_score' => 'Toon zoekscore in resultaten',
  'opensearch:settings:search_score:help' => 'Toon de zoekresultaat score aan de beheerders in de zoekresultaten. Dit kan helpen bij het verklaren van de resultaat volgorde.',
  'opensearch:settings:type_boosting:title' => 'Content Type Boosting',
  'opensearch:settings:type_boosting:type' => 'Content type',
  'opensearch:settings:type_boosting:multiplier' => 'Vermenigvuldigingsfactor',
  'opensearch:settings:decay:title' => 'Content verval',
  'opensearch:settings:decay:info' => 'Indien geconfigureerd zal de verval factor worden toegepast op alle zoekresultaten',
  'opensearch:settings:decay_offset' => 'Begin',
  'opensearch:settings:decay_offset:help' => 'Geef het aantal dagen op voordat (min) de verval factor zal worden toegepast.',
  'opensearch:settings:decay_scale' => 'Schaal',
  'opensearch:settings:decay_scale:help' => 'Geef het aantal dagen op waarna (max) de laagste verval factor zal worden toegepast.',
  'opensearch:settings:decay_decay' => 'Verval',
  'opensearch:settings:decay_decay:help' => 'Geef de verval factor op welke zal worden toegepast indien de schaal is bereikt. Geef een nummer tussen 1 en 0 op. Hoe lager het nummer, hoe lager de zoekscore.',
  'opensearch:settings:decay_time_field' => 'Tijdveld',
  'opensearch:settings:decay_time_field:help' => 'Selecteer het tijdveld waarop het verval moet worden toegepast',
  'opensearch:settings:decay_time_field:time_created' => 'Creatiedatum',
  'opensearch:settings:decay_time_field:time_updated' => 'Laatste update',
  'opensearch:settings:decay_time_field:last_action' => 'Laatste actie',
  'opensearch:search_score' => 'Zoekscore: %s',
  'opensearch:inspect:result:delete' => 'Verwijder de entiteit uit de index',
  'opensearch:action:admin:delete_entity:success' => 'De entiteit is ingepland om te worden verwijderd uit de index',
  'opensearch:settings:cron_validate' => 'Valideer de zoekindex dagelijks',
  'opensearch:settings:cron_validate:help' => 'Valideer de index om er zeker van te zijn dat er geen content is zit welke er niet meer in hoort en om er zeker van te zijn dat alle content welke er wel in hoort er ook is.',
  'admin:opensearch:inspect' => 'Inspecteren',
  'opensearch:inspect:guid' => 'Geef de GUID op van de entity die je wilt inspecteren',
  'opensearch:inspect:guid:help' => 'Alle entiteiten in Elgg hebben een GUID, meestal kun je deze vinden middels de URL van de entity (bijv. blog/view/1234)',
  'opensearch:inspect:submit' => 'Inspecteer',
  'opensearch:inspect:result:title' => 'Inspectie resultaten',
  'opensearch:inspect:result:elgg' => 'Elgg',
  'opensearch:inspect:result:error:not_indexed' => 'De entity is nog niet geïndexeerd',
  'opensearch:inspect:result:last_indexed:none' => 'Deze entity is nog niet geïndexeerd',
  'opensearch:inspect:result:last_indexed:scheduled' => 'Deze entity is ingepland om te worden geïndexeerd',
  'opensearch:inspect:result:last_indexed:time' => 'Deze entity is voor het laatst geïndexeerd: %s',
  'opensearch:inspect:result:reindex' => 'Inplannen voor herindexatie',
  'opensearch:action:admin:reindex_entity:success' => 'De entity is ingepland om te worden geherindexeerd',
  'admin:opensearch:statistics' => 'Statistieken',
  'admin:opensearch:search' => 'Zoeken',
  'admin:opensearch:indices' => 'Indices',
  'opensearch:admin_search:results' => 'Zoek Resultaten',
  'opensearch:admin_search:results:info' => 'Resultaten worden hier getoond',
  'opensearch:error:no_index' => 'Geen index opgegeven voor de actie',
  'opensearch:error:index_not_exists' => 'De opgegeven index bestaat niet: %s',
  'opensearch:error:alias_not_configured' => 'Er is geen alias geconfigureerd in de plugin instellingen',
  'opensearch:error:search' => 'Er geen iets mis bij het uitvoeren van de zoekopdracht. Neem contact op met de beheerder van de site indien het probleem blijvend is.',
  'opensearch:settings:host' => 'API host',
  'opensearch:settings:host:help' => 'Je kunt meerdere hosts opgeven door ze te scheiden met een comma (,).',
  'opensearch:settings:index' => 'Index voor de Elgg data',
  'opensearch:settings:search_alias' => 'Zoek index alias (optioneel)',
  'opensearch:settings:index:help' => 'Je moet een index configureren om alle Elgg data in op te slaan. Indien je niet weet welke index je wilt gebruiken, misschien is \'%s\' een optie?',
  'opensearch:settings:features:header' => 'Instellingen',
  'opensearch:settings:search_alias:help' => 'Indien je in meer dan 1 index wilt zoeken, dan kun je een alias configureren waarin gezocht wordt. De alias zal dan moeten worden toegevoegd aan de Elgg index.',
  'opensearch:settings:sync:help' => 'Synchronisatie moet worden aangezet. Als je nog niet klaar bent om data te laten indexeren kun je dat hiermee bepalen.',
  'opensearch:stats:cluster' => 'Cluster informatie',
  'opensearch:stats:cluster_name' => 'Cluster naam',
  'opensearch:stats:lucene_version' => 'Lucene versie',
  'opensearch:stats:index:index' => 'Index: %s',
  'opensearch:stats:index:stat' => 'Statistiek',
  'opensearch:stats:index:value' => 'Waarde',
  'opensearch:stats:elgg' => 'Elgg informatie',
  'opensearch:stats:elgg:total' => 'Content die geïndexeerd moet worden',
  'opensearch:stats:elgg:no_index_ts' => 'Nieuwe content die geïndexeerd moet worden',
  'opensearch:stats:elgg:update' => 'Bijgewerkte content die geïndexeerd moet worden',
  'opensearch:stats:elgg:reindex' => 'Content die geherindexeerd moet worden',
  'opensearch:stats:elgg:reindex:action' => 'Je kunt een herindexatie forceren van alle content door hier te klikken.',
  'opensearch:stats:elgg:reindex:last_ts' => 'Huidige tijd die gebruikt wordt om te bepalen of er geherindexeerd moet worden: %s',
  'opensearch:stats:elgg:delete' => 'Content die nog verwijderd moet worden',
  'opensearch:indices:index' => 'Index',
  'opensearch:indices:alias' => 'Alias',
  'opensearch:menu:search_list:sort:title' => 'Wijzig de volgorde van de zoekresultaten',
  'opensearch:menu:search_list:sort:relevance' => 'Relevantie',
  'opensearch:menu:search_list:sort:alpha_az' => 'Alfabetisch (A-Z)',
  'opensearch:menu:search_list:sort:alpha_za' => 'Alfabetisch (Z-A)',
  'opensearch:menu:search_list:sort:newest' => 'Nieuwste eerst',
  'opensearch:menu:search_list:sort:oldest' => 'Oudste eerst',
  'opensearch:menu:search_list:sort:member_count' => 'Aantal leden',
  'opensearch:forms:admin_search:query:placeholder' => 'Voer hier je zoekopdracht in',
  'opensearch:action:admin:index_management:error:delete' => 'Er is een fout opgetreden tijdens het deleten van de index: %s',
  'opensearch:action:admin:index_management:error:create:exists' => 'Je kunt index \'%s\' niet aanmaken, want hij bestaat al.',
  'opensearch:action:admin:index_management:error:create' => 'Er is een fout opgetreden tijdens het aanmaken van de index: %s',
  'opensearch:action:admin:index_management:error:add_mappings' => 'Er is een fout opgetreden tijdens het aanmaken van de mappings voor de index: %s',
  'opensearch:action:admin:index_management:error:task' => 'De taak \'%s\' wordt niet ondersteund',
  'opensearch:action:admin:index_management:error:add_alias:exists' => 'De alias \'%s\' bestaat al voor de index \'%s\'',
  'opensearch:action:admin:index_management:error:delete_alias:exists' => 'De alias \'%s\' bestaat niet voor de index \'%s\'',
  'opensearch:action:admin:index_management:error:add_alias' => 'Er is een fout opgetreden tijdens het aanmaken van de alias \'%s\' voor de index: %s',
  'opensearch:action:admin:index_management:error:delete_alias' => 'Er is een fout opgetreden tijdens het verwijderen van de alias \'%s\' voor de index: %s',
  'opensearch:action:admin:index_management:delete' => 'De index \'%s\' is verwijderd',
  'opensearch:action:admin:index_management:create' => 'De index \'%s\' is aangemaakt',
  'opensearch:action:admin:index_management:add_mappings' => 'Mappings voor de index \'%s\' zijn aangemaakt',
  'opensearch:action:admin:index_management:add_alias' => 'De alias \'%s\' is toegevoegd aan de index \'%s\'',
  'opensearch:action:admin:index_management:delete_alias' => 'De alias \'%s\' is verwijderd van de index \'%s\'',
  'opensearch:suggest' => 'Bedoelde je %s in plaats van %s?',
);
