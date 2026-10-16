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

    /**
     * Make sure the Role Assignment step has an option for this role, using the same
     * name rule as getUserRoleList() and the sync migration. Returns the existing
     * option when one already matches, so it never creates a duplicate.
     */
    public static function ensureOptionForRole(string $roleName): self
    {
        $key = RoleNames::normalize($roleName);

        $existing = self::where(function ($query) {
                $query->where('active_inactive', 1)
                    ->orWhereNull('active_inactive');
            })
            ->get()
            ->first(fn ($row) => RoleNames::normalize((string) $row->user_role_display_name) === $key);

        if ($existing) {
            return $existing;
        }

        $option = new self();
        $option->forceFill([
            'user_role_name' => $roleName,
            'user_role_display_name' => $roleName,
            'active_inactive' => 1,
            'created_date' => now(),
            'updated_date' => now(),
        ])->save();

        return $option;
    }

    /**
     * Keep the Role Assignment option in step when a role is renamed, so employees
     * already mapped to it keep it under the new name.
     */
    public static function renameOptionForRole(string $oldName, string $newName): void
    {
        $oldKey = RoleNames::normalize($oldName);
        $newKey = RoleNames::normalize($newName);

        $rows = self::where(function ($query) {
                $query->where('active_inactive', 1)
                    ->orWhereNull('active_inactive');
            })
            ->get();

        $old = $rows->first(fn ($row) => RoleNames::normalize((string) $row->user_role_display_name) === $oldKey);
        $newExists = $oldKey !== $newKey
            && $rows->contains(fn ($row) => RoleNames::normalize((string) $row->user_role_display_name) === $newKey);

        if ($old && ! $newExists) {
            $old->forceFill([
                'user_role_display_name' => $newName,
                'user_role_name' => RoleNames::normalize((string) $old->user_role_name) === $oldKey
                    ? $newName
                    : $old->user_role_name,
                'updated_date' => now(),
            ])->save();

            return;
        }

        self::ensureOptionForRole($newName);
    }
}
