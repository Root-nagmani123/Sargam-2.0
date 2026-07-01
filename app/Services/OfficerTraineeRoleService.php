<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class OfficerTraineeRoleService
{
    public const ROLE_NAME = 'Officer Trainee';

    /**
     * Ensure a single student user has the Officer Trainee Spatie role.
     */
    public function ensureForUser(User $user): bool
    {
        if ($user->user_category !== 'S') {
            return false;
        }

        if ($user->hasRole(self::ROLE_NAME)) {
            return false;
        }

        $user->assignRole(self::ROLE_NAME);
        $this->forgetPermissionCache();

        return true;
    }

    /**
     * Assign Officer Trainee role to the given user_credentials PKs (category S only, missing role only).
     */
    public function assignToUserPks(array $userCredentialPks): int
    {
        if ($userCredentialPks === []) {
            return 0;
        }

        return $this->insertMissingRoleAssignments(function ($query) use ($userCredentialPks) {
            $query->whereIn('uc.pk', $userCredentialPks);
        });
    }

    /**
     * Assign Officer Trainee role to all category-S users that do not already have it.
     */
    public function assignToAllMissingStudents(): int
    {
        return $this->insertMissingRoleAssignments(function ($query) {
            $query->where('uc.user_category', 'S');
        });
    }

    private function insertMissingRoleAssignments(callable $scopeQuery): int
    {
        if (! Schema::hasTable('model_has_roles') || ! Schema::hasTable('roles')) {
            return 0;
        }

        $roleId = $this->resolveRoleId();
        if (! $roleId) {
            return 0;
        }

        $modelType = User::class;
        $assigned = 0;

        $baseQuery = DB::table('user_credentials as uc')
            ->where('uc.user_category', 'S')
            ->whereNotExists(function ($q) use ($roleId, $modelType) {
                $q->select(DB::raw(1))
                    ->from('model_has_roles as mhr')
                    ->whereColumn('mhr.model_id', 'uc.pk')
                    ->where('mhr.role_id', $roleId)
                    ->where('mhr.model_type', $modelType);
            });

        $scopeQuery($baseQuery);

        // chunkById (not chunk): offset-based chunk skips rows once inserts shrink the result set.
        $baseQuery
            ->select('uc.pk')
            ->chunkById(500, function ($rows) use ($roleId, $modelType, &$assigned) {
                $insert = [];
                foreach ($rows as $row) {
                    $insert[] = [
                        'role_id'    => $roleId,
                        'model_type' => $modelType,
                        'model_id'   => $row->pk,
                    ];
                }

                if ($insert !== []) {
                    DB::table('model_has_roles')->insertOrIgnore($insert);
                    $assigned += count($insert);
                }
            }, 'pk', 'uc');

        if ($assigned > 0) {
            $this->forgetPermissionCache();
        }

        return $assigned;
    }

    private function resolveRoleId(): ?int
    {
        $id = DB::table('roles')->where('name', self::ROLE_NAME)->value('id');

        return $id !== null ? (int) $id : null;
    }

    private function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
