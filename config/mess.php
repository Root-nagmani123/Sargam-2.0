<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mess Management Configuration
    |--------------------------------------------------------------------------
    */

    /**
     * Enable/Disable RBAC for Mess Management
     * Set to true to enable permission checks
     */
    'rbac_enabled' => env('MESS_RBAC_ENABLED', true),

    /**
     * Available Permissions
     * These are the actions that can be assigned to users
     */
    'permissions' => [
        'purchase_order.create' => 'Create Purchase Order',
        'purchase_order.approve' => 'Approve Purchase Order',
        'purchase_order.reject' => 'Reject Purchase Order',
        'material_request.create' => 'Create Material Request',
        'material_request.approve' => 'Approve Material Request',
        'store_issue.create' => 'Create Store Issue',
        'store_issue.approve' => 'Approve Store Issue',
        'finance_booking.create' => 'Create Finance Booking',
        'finance_booking.approve' => 'Approve Finance Booking',
        'finance_booking.reject' => 'Reject Finance Booking',
        'invoice.create' => 'Create Invoice',
        'invoice.approve' => 'Approve Invoice',
        'vendor.manage' => 'Manage Vendors',
        'inventory.manage' => 'Manage Inventory',
        'store.manage' => 'Manage Stores',
        'sale_counter.manage' => 'Manage Sale Counters',
        'credit_limit.manage' => 'Manage Credit Limits',
        'menu_rate.manage' => 'Manage Menu Rates',
        'meal_rate.manage' => 'Manage Meal Rates',
        'reports.view' => 'View Reports',
        'reports.export' => 'Export Reports',
    ],

    /**
     * Default settings
     */
    'defaults' => [
        'pagination' => 15,
        'module_name' => 'mess',
    ],
];
