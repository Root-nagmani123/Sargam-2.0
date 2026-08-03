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

    /*
    |--------------------------------------------------------------------------
    | FC form structure cache (feature-flagged, default OFF)
    |--------------------------------------------------------------------------
    |
    | When enabled, the SHARED form structure (steps / fields / groups) rendered by
    | GenericFormController is cached in Redis with a per-form epoch, following the
    | Programme (Course) module pattern. Only structure that is identical for every
    | trainee is cached — per-user data (a trainee's own answers) is NEVER cached here.
    | Invalidated instantly on an admin form edit (epoch bump) and by the TTL backstop.
    |
    | Leave OFF until validated on staging. Enable with FC_FORM_STRUCTURE_CACHE_ENABLED=true
    | (and re-run `php artisan config:cache` if you cache config on prod).
    |
    */
    'form_structure_cache_enabled' => (bool) env('FC_FORM_STRUCTURE_CACHE_ENABLED', false),
    'form_structure_cache_ttl' => (int) env('FC_FORM_STRUCTURE_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Joining-document ceremony date & place (batch-frozen — single source)
    |--------------------------------------------------------------------------
    |
    | The FC joining declarations (oath, surety bonds, property & dowry
    | declarations, assumption of charge, …) print ONE ceremony date and place
    | for the whole intake. These are frozen here so the on-screen default and
    | the generated PDF stay in lock-step, and the per-batch change is a single
    | edit instead of ~22 view files.
    |
    | document_declaration_date is stored ISO (Y-m-d) for <input type="date">;
    | use fc_document_date('display') for the d-m-Y form the PDFs print.
    | This does NOT change the fixed LBSNAA academy address ("… Mussoorie"),
    | which is institutional text, not the batch-variable ceremony place.
    |
    */
    'document_declaration_date' => env('FC_DOCUMENT_DECLARATION_DATE', '2026-08-24'),
    'document_place'            => env('FC_DOCUMENT_PLACE', 'Mussoorie'),
    'document_place_hi'         => env('FC_DOCUMENT_PLACE_HI', 'मसूरी'),

    /*
    |--------------------------------------------------------------------------
    | Form-builder delete actions (default OFF while the intake is live)
    |--------------------------------------------------------------------------
    |
    | Deleting a form / step / field / group / document master from the admin
    | form builder is destructive and cascades to trainee data, and this intake is
    | already live while the rest of the project is still in development. The delete
    | buttons are therefore hidden everywhere in the form builder and the delete
    | endpoints refuse the request, so a stale tab or a hand-crafted POST cannot
    | delete either. Nothing else changes — the delete code itself is untouched.
    |
    | Re-enable with FC_FORM_BUILDER_DELETE_ENABLED=true (re-run
    | `php artisan config:cache` if config is cached).
    |
    */
    'form_builder_delete_enabled' => (bool) env('FC_FORM_BUILDER_DELETE_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Joining letter shown on the trainee's form dashboard (per intake)
    |--------------------------------------------------------------------------
    |
    | joining_letter — the default letter, shown on EVERY dynamic form's dashboard. This is
    | deliberate: form slugs differ between environments (the 101st Foundation Course is
    | 'fc-101' on dev but 'fc-102' on production), so keying the letter on the slug meant the
    | card silently vanished on prod. One default that always applies is the predictable
    | behaviour. Set it to null/'' to hide the card everywhere.
    |
    | joining_letters — optional per-form OVERRIDES, keyed by fc_forms.form_slug. Use these
    | when an intake needs its own letter, or '' to suppress the card for one form (e.g. the
    | template). An entry whose slug matches nothing is simply ignored.
    |
    | Adding an intake: drop the PDF in public/fc-documents/ and either replace the default
    | or add a per-slug override below.
    |
    */
    'joining_letter' => env('FC_JOINING_LETTER', 'fc-documents/1st-communication-letter-to-ots.pdf'),

    'joining_letters' => [
        // 'fc_template' => '',   // example: no letter on the reusable template
    ],

];
