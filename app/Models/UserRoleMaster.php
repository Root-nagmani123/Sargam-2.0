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
            ->orderBy('pk')
            ->select('pk', 'user_role_display_name')
            ->get()
            ->filter(fn ($row) => isset($roleKeys[RoleNames::normalize((string) $row->user_role_display_name)]))
            // One option per role: if several rows match the same role (e.g. "Super Admin"
            // and "Super-Admin"), keep the oldest one. No row is changed or deleted.
            ->unique(fn ($row) => RoleNames::normalize((string) $row->user_role_display_name))
            ->pluck('user_role_display_name', 'pk');
    }

    /**
     * Options of ANY status whose display name matches this role name, oldest first.
     * Status is ignored on purpose: a deactivated option still belongs to its role, and
     * treating it as missing created an active duplicate that put the role back in the
     * wizard (PR #343 review, F-004). Same rule as the 2026_10_21 backfill's pre-check.
     */
    private static function optionsNamed(string $roleName)
    {
        $key = RoleNames::normalize($roleName);

        return self::orderBy('pk')
            ->get()
            ->filter(fn ($row) => RoleNames::normalize((string) $row->user_role_display_name) === $key)
            ->values();
    }

    /**
     * Refusal message when a role name would take over an option employees already hold,
     * or null when the name is safe.
     *
     * A new role adopts the existing option of the same name, and the next wizard save
     * converts every mapping on that option into a real Spatie grant (PR #343 review,
     * F-001: creating "Admin" granted it to the members tagged "Admin"). Pass the role's
     * current name on a rename so keeping its own option (e.g. a case-only change) is allowed.
     */
    public static function mappedOptionConflict(string $roleName, ?string $currentName = null): ?string
    {
        if ($currentName !== null && RoleNames::normalize($currentName) === RoleNames::normalize($roleName)) {
            return null;
        }

        $pks = self::optionsNamed($roleName)->pluck('pk')->all();

        if ($pks === []) {
            return null;
        }

        $held = EmployeeRoleMapping::whereIn('user_role_master_pk', $pks)
            ->distinct()
            ->count('user_credentials_pk');

        if ($held === 0) {
            return null;
        }

        return '"' . $roleName . '" is already a Role Assignment option held by ' . $held
            . ($held === 1 ? ' employee' : ' employees')
            . '. A role with this name would be granted to them on their next save. Choose a different name.';
    }

    /**
     * Make sure the Role Assignment step has an option for this role, using the same
     * name rule as getUserRoleList() and the sync migration. Returns the existing
     * option of any status when one already matches, so it never creates a duplicate
     * and never reactivates a deactivated option.
     */
    public static function ensureOptionForRole(string $roleName): self
    {
        $existing = self::optionsNamed($roleName)->first();

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
     * already mapped to it keep it under the new name. The option is renamed in place
     * and keeps its status, so a deactivated option stays deactivated (F-004).
     */
    public static function renameOptionForRole(string $oldName, string $newName): void
    {
        $oldKey = RoleNames::normalize($oldName);

        $old = self::optionsNamed($oldName)->first();
        $newExists = $oldKey !== RoleNames::normalize($newName)
            && self::optionsNamed($newName)->isNotEmpty();

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
