<?php

return [
    'name' => 'Protocol',

    /*
    |--------------------------------------------------------------------------
    | Request Types
    |--------------------------------------------------------------------------
    | Maps the request_type value stored on protocol_requests to a human
    | label and the Eloquent model that holds the type-specific fields.
    */
    'request_types' => [
        'guesthouse' => [
            'label' => 'Guest House',
            'model' => \Modules\Protocol\Entities\GuestHouseRequest::class,
            'route_segment' => 'guest-house',
        ],
        'vehicle' => [
            'label' => 'Vehicle Pass',
            'model' => \Modules\Protocol\Entities\VehiclePassRequest::class,
            'route_segment' => 'vehicle-pass',
        ],
        'ticket' => [
            'label' => 'Ticket',
            'model' => \Modules\Protocol\Entities\TicketRequest::class,
            'route_segment' => 'ticket',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Lifecycle
    |--------------------------------------------------------------------------
    | pending      -> raised by employee, awaiting Protocol Staff action
    | recommended  -> Protocol Staff forwarded to a manager/staff for final call
    | approved     -> final approval given (either directly by Protocol Staff
    |                 or by the recommended manager)
    | rejected     -> final rejection (only possible at the manager stage)
    */
    'statuses' => [
        'pending' => 'Pending',
        'recommended' => 'Recommended',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles (Spatie permission role names)
    |--------------------------------------------------------------------------
    | Adjust these to match the role names already used in the host
    | application. The module only checks role names — it does not create
    | them; run the seeder or create them via your existing role management.
    */
    'roles' => [
        'employee' => 'employee',
        'protocol_staff' => 'protocol-staff',
        'manager' => 'protocol-manager',
        'admin' => 'protocol-admin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */
    'per_page' => 15,
];
