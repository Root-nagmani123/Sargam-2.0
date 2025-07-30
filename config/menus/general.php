<?php

return [
    [
        'title' => 'General',
        // 'permission' => 'HR.INDEX',
        'id' => 'generalCollapse',
        'icon' => 'bi-chevron-down',
        'items' => [
            [
                'title' => 'Notifications',
                'route' => 'admin.dashboard',
                'icon' => 'solar:notification-unread-bold-duotone',
                'visible' => true,
                // 'permission' => 'HR.INDEX',
            ],
        ],
    ],
    [
        'title' => 'Course',
        'id' => 'courseCollapse',
        'icon' => 'bi-chevron-down',
        'allowedPermissions' => [
            'programme.index',
            'master.course.group.type.index',
            'group.mapping.index',
            'subject-module.index',
            'subject.index'
        ],
        'items' => [
            [
                'title' => 'Course Master',
                'route' => 'programme.index',
                'icon' => 'solar:mask-happly-line-duotone',
                'permission' => 'programme.index',
            ],
            [
                'title' => 'Course Group Type',
                'route' => 'master.course.group.type.index',
                'icon' => 'solar:face-scan-square-broken',
                'permission' => 'master.course.group.type.index',
            ],
            [
                'title' => 'Group Name Mapping',
                'route' => 'group.mapping.index',
                'icon' => 'solar:calendar-mark-line-duotone',
                'permission' => 'group.mapping.index',
            ],
            [
                'title' => 'Subject Module',
                'route' => 'subject-module.index',
                'icon' => 'solar:widget-4-line-duotone',
                'permission' => 'subject-module.index',
            ],
            [
                'title' => 'Subject',
                'route' => 'subject.index',
                'icon' => 'solar:speaker-minimalistic-line-duotone',
                'permission' => 'subject.index',
            ],
        ],
    ],
    [
        'title' => 'Exemption',
        'id' => 'exemptionCollapse',
        'icon' => 'bi-chevron-down',
        'allowedPermissions' => [
            'master.exemption.category.master.index',
            'master.exemption.medical.speciality.index'
        ],
        'items' => [
            [
                'title' => 'Exemption Category',
                'route' => 'master.exemption.category.master.index',
                'icon' => 'solar:face-scan-square-broken',
                'permission' => 'master.exemption.category.master.index',
            ],
            [
                'title' => 'Exemption Medical Speciality',
                'route' => 'master.exemption.medical.speciality.index',
                'icon' => 'solar:face-scan-square-broken',
                'permission' => 'master.exemption.medical.speciality.index',
            ],
        ],
    ],
    [
        'title' => 'Exemption Duty',
        'id' => 'exemptionDutyCollapse',
        'icon' => 'bi-chevron-down',
        'allowedPermissions' => [
            'student-medical-exemption.index',
            'mdo-escrot-exemption.index',
            'master.mdo_duty_type.index',
        ],
        'items' => [
            [
                'title' => 'Student Medical Exemption',
                'route' => 'student.medical.exemption.index',
                'icon' => 'solar:feed-bold-duotone',
                'permission' => 'student-medical-exemption.index',
            ],
            [
                'title' => 'MDO Escrot Exemption',
                'route' => 'mdo-escrot-exemption.index',
                'icon' => 'solar:calendar-mark-line-duotone',
                'permission' => 'mdo-escrot-exemption.index',
            ],
            [
                'title' => 'MDO Duty Type',
                'route' => 'master.mdo_duty_type.index',
                'icon' => 'solar:face-scan-square-broken',
                'permission' => 'master.mdo_duty_type.index',
            ],
        ],
    ],
    [
        'title' => 'Memo',
        'id' => 'memoCollapse',
        'icon' => 'bi-chevron-down',
        'allowedPermissions' => [
            'master.memo.type.master.index',
            'master.memo.conclusion.master.index',
            'memo.notice.management.index',
            // 'memo.notice.management.user',
            'course.memo.decision.index',
            // 'admin.courseAttendanceNoticeMap.memo_notice'
        ],
        'items' => [
            [
                'title' => 'Memo Type Master',
                'route' => 'master.memo.type.master.index',
                'icon' => 'solar:airbuds-case-line-duotone',
                'permission' => 'master.memo.type.master.index',
            ],
            [
                'title' => 'Memo Conclusion Master',
                'route' => 'master.memo.conclusion.master.index',
                'icon' => 'solar:airbuds-case-line-duotone',
                'permission' => 'master.memo.conclusion.master.index'
            ],
            [
                'title' => 'Memo Notice Management',
                'route' => 'memo.notice.management.index',
                'icon' => 'solar:feed-bold-duotone',
                'permission' => 'memo.notice.management.index'
            ],
            [
                'title' => 'Memo Notice Chat (User)',
                'route' => 'memo.notice.management.user',
                'icon' => 'solar:feed-bold-duotone',
            ],
            [
                'title' => 'Memo Course Mapping',
                'route' => 'course.memo.decision.index',
                'icon' => 'solar:airbuds-case-line-duotone',
                'permission' => 'course.memo.decision.index',
            ],
            [
                'title' => 'Memo / Notice Creation (Admin)',
                'route' => 'admin.courseAttendanceNoticeMap.memo_notice',
                'icon' => 'solar:airbuds-case-line-duotone',
            ],
        ],
    ],
    [
        'title' => 'Employee',
        'id' => 'employeeCollapse',
        'icon' => 'bi-chevron-down',
        'items' => [
            [
                'title' => 'Employee Type',
                'route' => 'master.employee.type.index',
                'icon' => 'solar:airbuds-case-line-duotone',
            ],
            [
                'title' => 'Employee Group',
                'route' => 'master.employee.group.index',
                'icon' => 'solar:airbuds-case-line-duotone',
            ],
            [
                'title' => 'Department Master',
                'route' => 'master.department.master.index',
                'icon' => 'solar:airbuds-case-line-duotone',
            ],
            [
                'title' => 'Designation Master',
                'route' => 'master.designation.index',
                'icon' => 'solar:airbuds-case-line-duotone',
            ],
            [
                'title' => 'Caste Category',
                'route' => 'master.caste.category.index',
                'icon' => 'solar:airbuds-case-line-duotone',
            ],
        ],
    ],
    [
        'title' => 'Faculty',
        'id' => 'facultyCollapse',
        'icon' => 'bi-chevron-down',
        'allowedPermissions' => [
            'master.faculty.expertise.index',
            'master.faculty-type-master.index',
            'faculty.index',
        ],
        'items' => [
            [
                'title' => 'Faculty Expertise',
                'route' => 'master.faculty.expertise.index',
                'icon' => 'solar:face-scan-square-broken',
                'permission' => 'master.faculty.expertise.index',
            ],
            [
                'title' => 'Faculty Type',
                'route' => 'master.faculty.type.master.index',
                'icon' => 'solar:face-scan-square-broken',
                'permission' => 'master.faculty-type-master.index',
            ],
            [
                'title' => 'Faculty',
                'route' => 'faculty.index',
                'icon' => 'solar:document-text-line-duotone',
                'permission' => 'faculty.index',
            ],
            // [
            //     'title' => 'Faculty Topic Mapping',
            //     'route' => 'mapping.index',
            //     'icon' => 'solar:map-arrow-up-bold-duotone',
            // ],
        ],
    ],
    [
        'title' => 'User Management',
        'id' => 'userManagementCollapse',
        'icon' => 'bi-chevron-down',
        'items' => [
            [
                'title' => 'Users',
                'route' => 'admin.users.index',
                'icon' => 'solar:atom-line-duotone',
            ],
            [
                'title' => 'Roles',
                'route' => 'admin.roles.index',
                'icon' => 'solar:atom-line-duotone',
            ],
            [
                'title' => 'Permissions',
                'route' => 'admin.permissions.index',
                'icon' => 'solar:atom-line-duotone',
            ],
        ],
    ],
];
