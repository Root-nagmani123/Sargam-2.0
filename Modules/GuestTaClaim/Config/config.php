<?php

return [
    'name' => 'GuestTaClaim',

    /*
    | Layout the module pages extend. The default ships a Sargam-style shell
    | (Government of India strip, header, icon rail). To sit inside the
    | existing Sargam layout, set GTC_LAYOUT to that layout's view name; it
    | must have @yield('content') plus @stack('styles') and @stack('scripts').
    */
    'layout' => env('GTC_LAYOUT', 'guesttaclaim::layouts.app'),

    'route_prefix' => env('GTC_ROUTE_PREFIX', 'guest-ta-claim'),
    'middleware' => ['web', 'auth'],

    // Host user model and the column used as the display name.
    'user_model' => env('GTC_USER_MODEL', 'App\\Models\\User'),
    'user_name_column' => 'first_name',
    'user_designation_column' => env('GTC_USER_DESIGNATION_COLUMN'), // nullable

    // Users who always have every permission (e.g. the Sargam super admin).
    'super_admin_ids' => array_filter(array_map('intval', explode(',', env('GTC_SUPER_ADMIN_IDS', '1')))),

    // When true, only the coordinator of the claim's course can approve it.
    'restrict_approval_to_course_coordinator' => env('GTC_RESTRICT_TO_CC', true),

    /*
    | Timetable source.
    |  - sargam:   the real Sargam tables (course_master, faculty_master, timetable)
    |  - demo:     generated sample timetable (for trying the module)
    |  - database: generic table/column mapping below
    */
    'timetable_provider' => env('GTC_TIMETABLE_PROVIDER', 'sargam'),

    'sargam' => [
        // faculty_master.faculty_type values that mean guest faculty. CHECK with:
        //   SELECT faculty_type, COUNT(*) FROM faculty_master GROUP BY faculty_type;
        'guest_faculty_types' => array_filter(explode(',', env('GTC_GUEST_FACULTY_TYPES', '2'))),

        // faculty_details roles that can be claimed for.
        'claimable_roles' => ['Teaching'],

        // Column on course_master holding the coordinator's users.id, if there is one.
        // Leave null and any user with "Approve or return claims" can approve.
        'course_coordinator_column' => env('GTC_COURSE_COORDINATOR_COLUMN'),

        // Optional name lookups. If the table or columns differ, change them here;
        // if they are wrong the screen shows "Venue 17" / an empty city instead of failing.
        'venue' => ['table' => 'venue_master', 'id' => 'venue_id', 'name' => 'venue_name'],
        'city' => ['table' => 'city_master', 'id' => 'pk', 'name' => 'city_name'],
    ],

    'timetable' => [
        'courses' => [
            'table' => 'courses',
            'id' => 'id',
            'name' => 'name',
            'coordinator_user_id' => 'coordinator_user_id',
            'active_scope' => null, // e.g. ['status' => 'active']
        ],
        'sessions' => [
            'table' => 'timetable_sessions',
            'id' => 'id',
            'course_id' => 'course_id',
            'faculty_id' => 'faculty_id',
            'date' => 'session_date',
            'start' => 'start_time',
            'end' => 'end_time',
            'topic' => 'topic',
            'venue' => 'venue',
        ],
        'faculty' => [
            'table' => 'faculties',
            'id' => 'id',
            'name' => 'name',
            'designation' => 'designation',
            'organisation' => 'organisation',
            'category' => 'category',
            'city' => 'city',
            'type_column' => 'faculty_type',  // column that marks guest faculty
            'guest_value' => 'guest',
        ],
    ],

    // PLACEHOLDER rates: replace with the Academy's notified rates.
    'honorarium_rates' => [
        'Academic' => 3000,
        'Retired officer' => 3000,
        'Non-official expert' => 2500,
        'Serving officer' => 2000,
    ],
    'da_rate' => 1500,
    'max_da_days' => 30,
    'travel_modes' => ['Air', 'Rail', 'Road', 'Own vehicle', 'Not applicable'],

    'claim_number_prefix' => 'GTA',

    'attachments' => [
        'disk' => env('GTC_DISK', 'local'),
        'directory' => 'guest-ta-claim',
        'max_kb' => 5120,
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png'],
    ],
];