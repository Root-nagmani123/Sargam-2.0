<?php

namespace Modules\GuestTaClaim\Services;

use Illuminate\Support\Facades\DB;
use Modules\GuestTaClaim\Enums\Permission;
use Modules\GuestTaClaim\Models\UserPermission;

/**
 * Module-level permission mapping (Setup › Guest TA Claim › Assign permission).
 * Bound as a scoped singleton, so lookups are cached per request.
 */
class PermissionService
{
    /** @var array<int, array<int, string>> */
    protected array $cache = [];

    public function isSuperAdmin($user): bool
    {
        return $user && in_array((int) $user->getKey(), config('guest-ta-claim.super_admin_ids', []), true);
    }

    /** @return array<int, string> */
    public function permissionsOf(int $userId): array
    {
        return $this->cache[$userId] ??= UserPermission::where('user_id', $userId)->pluck('permission')->all();
    }

    public function has($user, Permission|string $permission): bool
    {
        if (! $user) {
            return false;
        }
        if ($this->isSuperAdmin($user)) {
            return true;
        }
        $value = $permission instanceof Permission ? $permission->value : $permission;

        return in_array($value, $this->permissionsOf((int) $user->getKey()), true);
    }

    /** @param array<int, Permission|string> $permissions */
    public function hasAny($user, array $permissions): bool
    {
        foreach ($permissions as $p) {
            if ($this->has($user, $p)) {
                return true;
            }
        }

        return false;
    }

    /** Replace a user's permissions with the given set. */
    public function sync(int $userId, array $permissions, int $grantedBy): void
    {
        $permissions = array_values(array_intersect(array_unique($permissions), Permission::values()));

        DB::transaction(function () use ($userId, $permissions, $grantedBy) {
            UserPermission::where('user_id', $userId)->whereNotIn('permission', $permissions)->delete();
            $existing = UserPermission::where('user_id', $userId)->pluck('permission')->all();
            foreach (array_diff($permissions, $existing) as $p) {
                UserPermission::create(['user_id' => $userId, 'permission' => $p, 'granted_by' => $grantedBy]);
            }
        });

        unset($this->cache[$userId]);
    }

    /** User ids holding a permission (super admins excluded). */
    public function userIdsWith(Permission $permission): array
    {
        return UserPermission::where('permission', $permission->value)->pluck('user_id')->all();
    }
}
