<?php

/**
 * Notification Type to Route Mapping Configuration
 * 
 * This configuration maps notification types and module names to their respective routes.
 * When a notification is clicked, the system uses this mapping to redirect users to the
 * appropriate module view.
 * 
 * Structure:
 * - 'type' => The notification type (e.g., 'course', 'notice', 'memo', 'student')
 * - 'module_name' => The module name (e.g., 'Course', 'Notice', 'Memo', 'Student')
 * - 'route' => The route name or pattern
 * - 'params' => Array of parameter names to extract from notification (e.g., ['id' => 'reference_pk'])
 */

return [
    // Course/Programme Notifications
    'course' => [
        'Course' => [
            'route' => 'programme.show',
            'params' => ['id' => 'reference_pk'],
        ],
        'Programme' => [
            'route' => 'programme.show',
            'params' => ['id' => 'reference_pk'],
        ],
    ],

    // Notice Notifications
    'notice' => [
        'Notice' => [
            'route' => 'notice.index',
            'params' => [],
        ],
        'NoticeNotification' => [
            'route' => 'notice.index',
            'params' => [],
        ],
    ],

    // Memo Notifications
    'memo' => [
        'Memo' => [
            'route' => 'memo.notice.management.conversation_student',
            'params' => ['id' => 'reference_pk', 'type' => 'memo'],
        ],
        'MemoNotice' => [
            'route' => 'memo.notice.management.conversation_student',
            'params' => ['id' => 'reference_pk', 'type' => 'memo'],
        ],
    ],

    // Student Notifications
    'student' => [
        'Student' => [
            'route' => 'member.show',
            'params' => ['id' => 'reference_pk'],
        ],
        'Member' => [
            'route' => 'member.show',
            'params' => ['id' => 'reference_pk'],
        ],
    ],

    // Attendance Notifications
    'attendance' => [
        'Attendance' => [
            'route' => 'attendance.index',
            'params' => [],
        ],
        'CourseAttendance' => [
            'route' => 'attendance.index',
            'params' => [],
        ],
    ],

    // Group Mapping Notifications
    'group' => [
        'GroupMapping' => [
            'route' => 'group.mapping.index',
            'params' => ['id' => 'reference_pk'],
        ],
        'Group' => [
            'route' => 'group.mapping.index',
            'params' => ['id' => 'reference_pk'],
        ],
    ],

    // Faculty Notifications
    'faculty' => [
        'Faculty' => [
            'route' => 'faculty.show',
            'params' => ['id' => 'reference_pk'],
        ],
    ],

    // MDO (Medical Duty Officer) Notifications
    'mdo' => [
        'MDO' => [
            'route' => 'admin.mdo.index',
            'params' => ['id' => 'reference_pk'],
        ],
        'Duty' => [
            'route' => 'admin.mdo.index',
            'params' => ['id' => 'reference_pk'],
        ],
    ],

    // Calendar/Event Notifications
    'calendar' => [
        'Calendar' => [
            'route' => 'calendar.index',
            'params' => [],
        ],
        'Event' => [
            'route' => 'calendar.index',
            'params' => ['event_id' => 'reference_pk'],
        ],
    ],

    // Default fallback route
    'default' => [
        'route' => 'admin.dashboard',
        'params' => [],
    ],
];

