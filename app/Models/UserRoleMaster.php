<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRoleMaster extends Model
{
    protected $table = 'user_role_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'user_role_name',
        'user_role_display_name',
        'active_inactive',
    ];

    /**
     * Roles for the Member "Role Assignment" checkbox list.
     * Excludes only roles explicitly toggled inactive (active_inactive = 0) via
     * Role & Permission > Roles; a legacy row with no status set (NULL) is kept
     * rather than silently hidden.
     */
    public static function getUserRoleList()
    {
        return self::where(function ($query) {
                $query->where('active_inactive', 1)
                    ->orWhereNull('active_inactive');
            })
            ->select('pk', 'user_role_display_name')
            ->get()
            ->pluck('user_role_display_name', 'pk');
    }
}
