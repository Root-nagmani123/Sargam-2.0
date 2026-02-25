<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;
use App\Models\UserRoleMaster;
use App\Models\User;

class MessPermission extends Model
{
    protected $table = 'mess_permissions';

    protected $fillable = [
        'role_id',
        'action_name',
        'display_name',
        'module',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Get the role that owns the permission
     */
    public function role()
    {
        return $this->belongsTo(UserRoleMaster::class, 'role_id', 'pk');
    }

    /**
     * Get the users assigned to this permission
     */
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'mess_permission_users',
            'mess_permission_id',
            'user_id',
            'id',
            'pk'
        )->withTimestamps();
    }

    /**
     * Get permission assignments
     */
    public function permissionUsers()
    {
        return $this->hasMany(MessPermissionUser::class, 'mess_permission_id');
    }

    /**
     * Check if a user has this permission
     */
    public static function userHasPermission($userId, $actionName)
    {
        return self::where('action_name', $actionName)
            ->where('is_active', true)
            ->whereHas('users', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->exists();
    }

    /**
     * Get all available permission actions
     */
    public static function getAvailableActions()
    {
        return [
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
            'reports.view' => 'View Reports',
            'reports.export' => 'Export Reports',
        ];
    }
}
