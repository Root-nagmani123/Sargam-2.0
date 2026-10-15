<?php

namespace App\Models;

use App\Support\RoleNames;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

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
     * Excludes roles explicitly toggled inactive (active_inactive = 0); a legacy
     * row with no status set (NULL) is kept rather than silently hidden.
     * Only options that match a role on Role & Permission > Roles (the `roles`
     * table) are offered, so HR-only options such as "Staff" are not listed.
     */
    public static function getUserRoleList()
    {
        $roleKeys = Role::pluck('name')
            ->map(fn ($name) => RoleNames::normalize($name))
            ->flip();

        return self::where(function ($query) {
                $query->where('active_inactive', 1)
                    ->orWhereNull('active_inactive');
            })
            ->select('pk', 'user_role_display_name')
            ->get()
            ->filter(fn ($row) => isset($roleKeys[RoleNames::normalize((string) $row->user_role_display_name)]))
            ->pluck('user_role_display_name', 'pk');
    }
}
