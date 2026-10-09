<?php

/**
 * ज्ञानकोश (ai.lbsnaa.gov.in/knowledge) ingest feed.
 *
 * Notices published on Sargam are pushed to the Academy knowledge base so staff
 * can search them and get them quoted with a citation. Contract:
 * api-sargam-ingest.md from the AI Platform team (POST /ingest, /ingest/withdraw,
 * /ingest/reconcile).
 *
 * Drivers:
 * - log  → nothing leaves this server; every call is written to the log and
 *          answered as a success. For local testing — the real endpoint is only
 *          reachable from the Academy LAN.
 * - http → the real API. Needs KNOWLEDGE_INGEST_URL and KNOWLEDGE_INGEST_KEY.
 */
return [

    // Master switch. Off → no job is dispatched and the command does nothing.
    'enabled' => env('KNOWLEDGE_INGEST_ENABLED', false),

    'driver' => env('KNOWLEDGE_INGEST_DRIVER', 'log'),

    'base_url' => rtrim((string) env('KNOWLEDGE_INGEST_URL', 'https://ai.lbsnaa.gov.in/knowledge/api'), '/'),

    // Bearer key issued to "sargam". Server-side only; never log it.
    'key' => env('KNOWLEDGE_INGEST_KEY'),

    // Must be exactly "sargam" — the API rejects anything else with 400.
    'external_source' => 'sargam',

    // external_id = prefix + notices_notification.pk. Never change this once live:
    // a new id makes ज्ञानकोश hold the same notice twice and quote the stale one.
    'notice_id_prefix' => env('KNOWLEDGE_NOTICE_ID_PREFIX', 'SARGAM-NOTICE-'),

    'timeout' => (int) env('KNOWLEDGE_INGEST_TIMEOUT', 60),
    'connect_timeout' => (int) env('KNOWLEDGE_INGEST_CONNECT_TIMEOUT', 10),

    // true, false, or a path to the Academy CA bundle if the LAN cert is internal.
    'verify_tls' => env('KNOWLEDGE_INGEST_CA_BUNDLE', env('KNOWLEDGE_INGEST_VERIFY_TLS', true)),

    /*
     * What may leave Sargam. Everything pushed becomes visible Academy-wide in
     * ज्ञानकोश (their §5) — so anything addressed to a person or a single course
     * is held back by default. Widen these only after the AI Platform team has
     * been told (their §7 question 1).
     */
    'notices' => [
        // notice_type values never sent.
        'exclude_types' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('KNOWLEDGE_NOTICE_EXCLUDE_TYPES', 'Personal'))
        ))),

        // Notices tied to one course (course_master_pk set).
        'include_course_notices' => (bool) env('KNOWLEDGE_NOTICE_INCLUDE_COURSE', false),

        // notice_type → ज्ञानकोश kind (circular, guideline, act, training, reckoner, other).
        'kind_map' => [
            'Office order'    => 'circular',
            'Office notice'   => 'circular',
            'Service related' => 'circular',
            'Course notice'   => 'training',
        ],
        'default_kind' => 'other',
    ],
];
