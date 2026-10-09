<?php

/**
 * ज्ञानकोश (ai.lbsnaa.gov.in/knowledge) notice feed.
 *
 * ज्ञानकोश polls Sargam for published notices and stores them on its side:
 *
 *   GET /api/knowledge/notices             everything published right now
 *   GET /api/knowledge/notices/{id}/file   the document of one of them
 *
 * See App\Http\Controllers\Api\KnowledgeFeedController.
 */
return [

    'external_source' => 'sargam',

    // external_id = prefix + notices_notification.pk. Never change this once live:
    // a new id makes ज्ञानकोश hold the same notice twice and quote the stale one.
    'notice_id_prefix' => env('KNOWLEDGE_NOTICE_ID_PREFIX', 'SARGAM-NOTICE-'),

    /*
     * Auth is a Bearer key we issue to the AI Platform team. Only its SHA-256 is
     * kept here — generate one with `php artisan knowledge:feed-key`.
     */
    'feed' => [
        'enabled' => env('KNOWLEDGE_FEED_ENABLED', false),

        'key_sha256' => env('KNOWLEDGE_FEED_KEY_SHA256'),

        // Optional comma-separated client IPs. Empty = any IP holding the key.
        'allowed_ips' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('KNOWLEDGE_FEED_ALLOWED_IPS', ''))
        ))),
    ],

    /*
     * What may leave Sargam. Everything in the feed becomes visible Academy-wide
     * in ज्ञानकोश — so anything addressed to a person or a single course is held
     * back by default. Widen these only after the AI Platform team has been told.
     */
    'notices' => [
        // notice_type values never served.
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
