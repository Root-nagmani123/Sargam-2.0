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
    | Form-builder non-delete actions (one flag per action, all default OFF)
    |--------------------------------------------------------------------------
    |
    | Deleting is not the only way to break a live intake from
    | fc-reg/admin/forms/{form}/edit. Re-pointing a step at a different target
    | table orphans every answer already collected; changing a tracker column
    | makes every trainee read as incomplete; reordering steps changes the
    | sequence AND the "earlier steps must be done first" gating; switching the
    | form inactive removes it from every trainee at once. None of those prompt
    | for confirmation and none of them are reversible by undo.
    |
    | Each action therefore gets its OWN flag, so an admin can be handed exactly
    | the one change an intake needs without opening the rest. Same enforcement
    | as the delete flag: the control is hidden/disabled in the UI AND the value
    | is refused server-side, so a stale tab or a hand-crafted POST cannot get
    | through either.
    |
    | Locked FIELDS are not rejected with a validation error — the value already
    | in the database is substituted before validation, so the save succeeds and
    | the locked field simply does not move. This keeps the always-safe fields on
    | the same form (form name, description, icon, step name) editable during a
    | live intake.
    |
    | ALL DEFAULT FALSE. Turn on only what is needed, e.g.
    |
    |     FC_FORM_STEP_ADD_ENABLED=true
    |
    | Re-run `php artisan config:cache` if config is cached on the server.
    |
    */

    // ── Form Settings card ────────────────────────────────────────────────
    // is_active — turning this off hides the whole form from every trainee.
    'form_activate_enabled'         => (bool) env('FC_FORM_ACTIVATE_ENABLED', false),
    // consolidation_table — where step-completion flags are read from / written to.
    'form_tracking_table_enabled'   => (bool) env('FC_FORM_TRACKING_TABLE_ENABLED', false),
    // registration_requires_all_steps — changes who counts as registered, and so
    // who appears in Migrate Students.
    'form_completion_rule_enabled'  => (bool) env('FC_FORM_COMPLETION_RULE_ENABLED', false),

    // ── Steps ─────────────────────────────────────────────────────────────
    // Add Step. Also performs live DDL when a tracker column is named — see below.
    'form_step_add_enabled'         => (bool) env('FC_FORM_STEP_ADD_ENABLED', false),
    // Up / Down arrows. Fires immediately with no confirm dialog.
    'form_step_reorder_enabled'     => (bool) env('FC_FORM_STEP_REORDER_ENABLED', false),
    // step_slug (bookmarked URLs) and target_table (where answers are stored).
    'form_step_structure_enabled'   => (bool) env('FC_FORM_STEP_STRUCTURE_ENABLED', false),
    // completion_column / tracker_column. Naming a column that does not exist runs
    // ALTER TABLE ... ADD COLUMN against the live consolidation table
    // (FormManagementController::ensureTrackerColumn), so a typo is permanent.
    'form_step_tracker_enabled'     => (bool) env('FC_FORM_STEP_TRACKER_ENABLED', false),
    // applicability_rule — silently adds/removes trainees from a step.
    'form_step_applicability_enabled' => (bool) env('FC_FORM_STEP_APPLICABILITY_ENABLED', false),
    // per-step is_active — hides one step from every trainee mid-intake.
    'form_step_activate_enabled'    => (bool) env('FC_FORM_STEP_ACTIVATE_ENABLED', false),
    // The whole Actions column on the STEPS table — the "Step" (edit step settings) and
    // "Fields" (open the field editor) buttons. The individual field locks above already stop
    // a locked VALUE from moving, but the two screens behind these buttons are still where a
    // live intake gets restructured: the field editor can add, rename, retype and reorder the
    // questions trainees are answering right now, and none of that is covered by a per-field
    // lock on the step row. This flag removes the entry points entirely.
    //
    // The FIELD EDITOR is enforced on the endpoint as well as the button, so a bookmarked
    // /form-builder/steps/{id} URL is refused too — hiding a link is not access control.
    //
    // The "Step" button is hidden but its endpoint stays open on purpose: every field in that
    // modal already has its own per-field lock above, and refusing the endpoint outright would
    // take the always-safe step NAME down with it.
    //
    // Reordering, deleting and every per-field lock keep their own flags; this one is only
    // about reaching the two editors.
    'form_step_actions_enabled'     => (bool) env('FC_FORM_STEP_ACTIONS_ENABLED', false),

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

    /*
    |--------------------------------------------------------------------------
    | Registration-PDF rendering engine
    |--------------------------------------------------------------------------
    |
    | Which engine renders the Descriptive Roll / Descriptive Data PDFs:
    |
    |   mpdf   (default) ships with the application and is pinned by composer.lock, so a
    |          server with no headless Chrome produces the SAME document as a developer's
    |          laptop. It also shapes Devanagari correctly.
    |   chrome headless Chrome when a binary is found, falling back to Dompdf when not.
    |   dompdf forces the Dompdf path.
    |
    | This lives in config/ rather than being read straight from env() in the controller
    | BECAUSE of `php artisan config:cache`: env() outside a config file returns its default
    | once the config is cached, so an FC_REGISTRATION_PDF_ENGINE override in .env was
    | silently ignored on a config-cached deployment — the operator believed they had rolled
    | back to Chrome and had not. Reading it through config() makes the override work in both
    | states, and the env() call here is evaluated at config-build time where it is valid.
    |
    */
    'pdf_engine' => env('FC_REGISTRATION_PDF_ENGINE', 'mpdf'),

];
