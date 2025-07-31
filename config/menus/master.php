<?php

return [

    [
        'title' => 'General Master',
        'id' => 'generalMasterMenu',
        'icon' => 'bi bi-chevron-down',
        'allowedPermissions' => [
            'venue-master.index',
            // 'master.class.session.index',
            // 'stream.index',
            // 'section.index',
        ],
        'items' => [
            [
                'title' => 'Venue Master',
                'route' => 'Venue-Master.index',
                'icon' => 'solar:face-scan-square-broken',
                'permission' => 'venue-master.index',
            ],
            [
                'title' => 'Class Session',
                'route' => 'master.class.session.index',
                'icon' => 'solar:face-scan-square-broken',
            ],
            [
                'title' => 'Stream',
                'route' => 'stream.index',
                'icon' => 'solar:widget-4-line-duotone',
            ],
            [
                'title' => 'Section',
                'route' => 'section.index',
                'icon' => 'solar:calendar-mark-line-duotone',
            ],
        ],
    ],

    [
        'title' => 'Hostel',
        'id' => 'hostelMenu',
        'icon' => 'bi bi-chevron-down',
        'items' => [
            [
                'title' => 'Hostel Building',
                'route' => 'master.hostel.building.index',
                'icon' => 'solar:airbuds-case-line-duotone',
            ],
            [
                'title' => 'Hostel Room',
                'route' => 'master.hostel.room.index',
                'icon' => 'solar:airbuds-case-line-duotone',
            ],
            [
                'title' => 'Hostel Floor',
                'route' => 'master.hostel.floor.index',
                'icon' => 'solar:airbuds-case-line-duotone',
            ],
            [
                'title' => 'Hostel Floor Mapping',
                'route' => 'hostel.building.map.index',
                'icon' => 'solar:airbuds-case-line-duotone',
            ],
            [
                'title' => 'Building Floor Room Mapping',
                'route' => 'hostel.building.floor.room.map.index',
                'icon' => 'solar:airbuds-case-line-duotone',
            ],
            [
                'title' => 'Assign Hostel',
                'route' => 'hostel.building.map.assign.student',
                'icon' => 'solar:airbuds-case-line-duotone',
            ],
        ],
    ],

    [
        'title' => 'Address',
        'id' => 'addressMenu',
        'icon' => 'bi bi-chevron-down',
        'items' => [
            [
                'title' => 'Country',
                'route' => 'master.country.index',
                'icon' => 'solar:airbuds-case-line-duotone',
            ],
            [
                'title' => 'State',
                'route' => 'master.state.index',
                'icon' => 'solar:airbuds-case-line-duotone',
            ],
            [
                'title' => 'District',
                'route' => 'master.district.index',
                'icon' => 'solar:airbuds-case-line-duotone',
            ],
            [
                'title' => 'City',
                'route' => 'master.city.index',
                'icon' => 'solar:airbuds-case-line-duotone',
            ],
        ],
    ],

    [
        'title' => 'Time Table',
        'id' => 'timeTableMenu',
        'icon' => 'bi bi-chevron-down',
        'items' => [
            [
                'title' => 'Calendar',
                'route' => 'calendar.index',
                'icon' => 'solar:calendar-mark-line-duotone',
            ],
            [
                'title' => 'Attendance',
                'route' => 'attendance.index',
                'icon' => 'solar:calendar-mark-line-duotone',
            ],
        ],
    ],

];
