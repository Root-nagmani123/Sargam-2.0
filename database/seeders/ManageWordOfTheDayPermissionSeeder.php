<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ManageWordOfTheDayPermissionSeeder extends Seeder
{
    /**
     * Optional permission for Word of the Day admin (in addition to Admin / Super Admin roles).
     */
    public function run(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $guard = config('auth.defaults.guard', 'web');

        $permission = Permission::query()->firstOrCreate(
            ['name' => 'manage word of the day', 'guard_name' => $guard]
        );

        foreach (['Admin', 'Super Admin'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', $guard)->first();
            if (! $role) {
                continue;
            }
            try {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            } catch (\Throwable $e) {
                // Ignore duplicate / cache issues during seed
            }
        }
    }
}
