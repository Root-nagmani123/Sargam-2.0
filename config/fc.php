<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Schema introspection cache (seconds)
    |--------------------------------------------------------------------------
    |
    | Schema::hasTable() / hasColumn() read information_schema, which is much
    | slower than a normal indexed query and contends heavily under concurrency.
    | fc_schema_columns() caches the column listing per table instead.
    |
    | The cache is invalidated automatically when migrations run, so this TTL is
    | only a backstop — a long value is safe. Set to 0 to disable caching.
    |
    */
    'schema_cache_ttl' => (int) env('FC_SCHEMA_CACHE_TTL', 86400),

    /*
    |--------------------------------------------------------------------------
    | Reference / master data lookup cache (seconds)
    |--------------------------------------------------------------------------
    |
    | Dropdown masters (states, countries, languages, districts, cities,
    | qualifications, …) are effectively static but were re-queried once per form
    | field on every page render.
    |
    | This TTL bounds how long an edit in the master-data screens takes to appear
    | in form dropdowns. Call fc_flush_lookup_cache() to publish changes at once.
    | Set to 0 to disable cross-request caching (per-request de-duplication still
    | applies).
    |
    */
    'lookup_cache_ttl' => (int) env('FC_LOOKUP_CACHE_TTL', 600),

    /*
    |--------------------------------------------------------------------------
    | Sidebar structure cache (seconds)
    |--------------------------------------------------------------------------
    |
    | The sidebar categories → groups → menus → children tree is identical for
    | every user, but cost 5 queries on every page in the application (login
    | included). Only the STRUCTURE is cached — per-user permission filtering
    | still runs live on each request, so revoking a permission takes effect
    | immediately.
    |
    | Menu create/update/delete/status flush this automatically. Editing menu
    | groups or categories directly takes up to this TTL to appear; call
    | MenuService::clearStructureCache() to publish at once. Set 0 to disable.
    |
    */
    'menu_cache_ttl' => (int) env('FC_MENU_CACHE_TTL', 600),

    /*
    |--------------------------------------------------------------------------
    | Migrate-students roster match cache (seconds)
    |--------------------------------------------------------------------------
    |
    | fc_registration_master (latin1) and user_credentials (utf8mb4) collate
    | differently, so the "already migrated?" test cannot use an index and cost
    | ~690ms per request. The matched pk set is computed once and cached instead.
    | Flushed automatically after a migration; set 0 to disable.
    |
    */
    'migrate_match_cache_ttl' => (int) env('FC_MIGRATE_MATCH_CACHE_TTL', 300),

];
