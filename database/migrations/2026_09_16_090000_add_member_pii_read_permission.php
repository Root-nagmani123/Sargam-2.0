<?php

use App\Http\Middleware\EnsureMemberPiiAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Make the member personal-data capability grantable.
 *
 * EnsureMemberPiiAccess removes the member exports, the full-details dump, the
 * row profile and the row print sheet from every non-Super-Admin account, and
 * justifies that narrowing by naming a remedy: grant the permission and the role
 * has them back, with no code change. A remedy nobody can perform is not a
 * remedy, and until this migration there was no way to perform it — the
 * permissions CRUD route is commented out, and the role-assignment screen offers
 * only names that exist as menus.permission_name.
 *
 * So this ships both halves of the grant path:
 *
 *   1. the Spatie permissions row, so the name exists and a grant is auditable;
 *   2. a menus row carrying that permission_name, so the role-assignment screen
 *      renders a toggle for it.
 *
 * The menus row is a CAPABILITY, not a screen. It sits under the existing
 * Employee parent so it reads in context, and carries exclude_from_admin = 1 so
 * no administrator's sidebar changes. A non-admin who is granted it sees one
 * extra child pointing at the member listing they can already open.
 *
 * Guarded on both sides and idempotent, because this repository's schema has
 * drifted from its migrations before: every step checks the table and the row
 * before touching anything, and the parent menu is looked up by its permission
 * name rather than by a hard-coded id, which differs between environments.
 *
 * AND IT FLUSHES SPATIE'S PERMISSION CACHE, in both directions. That is not
 * housekeeping - without it this migration BREAKS the very grant it exists to
 * enable. Spatie caches the permission collection in the application cache (on
 * this deployment the file driver, 24-hour TTL) and invalidates it only when a
 * permission is saved through its own model. Writing the row with the query
 * builder - which the guard above requires - leaves the cache serving a
 * collection that does not contain the new name. RoleController::assignPermission()
 * then calls firstOrCreate(), which FINDS the row and so creates nothing and
 * flushes nothing, and the hasPermissionTo() on the next line raises
 * PermissionDoesNotExist. The toggle 500s for as long as the cache entry lives.
 * Reviewed as PR #309 F-025, where the counterfactual is the sharp part: with
 * this migration NOT run, the same grant succeeds.
 */
return new class extends Migration
{
    /** The Employee screen, under which the capability is listed. */
    private const PARENT_PERMISSION = 'member_index';

    private const MENU_NAME = 'Employee Downloads';

    public function up(): void
    {
        $permission = EnsureMemberPiiAccess::PII_PERMISSION;

        if (Schema::hasTable('permissions')
            && ! DB::table('permissions')->where('name', $permission)->where('guard_name', 'web')->exists()) {
            DB::table('permissions')->insert([
                'name' => $permission,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Before any early return below: the permission row is the half the
        // grant path resolves through the cache, so it must be visible now.
        $this->flushPermissionCache();

        if (! Schema::hasTable('menus')) {
            return;
        }

        if (DB::table('menus')->where('permission_name', $permission)->exists()) {
            return;
        }

        // By permission name, never by id: menu ids are environment data.
        $anchor = DB::table('menus')->where('permission_name', self::PARENT_PERMISSION)->first();

        if (! $anchor) {
            // No Employee screen on this database: the permission row above is
            // still useful, and a menus row with no parent would float loose in
            // the matrix. Stop rather than invent a home for it.
            return;
        }

        $row = [
            'name' => self::MENU_NAME,
            'permission_name' => $permission,
            // Points at the listing the capability belongs to. A granted
            // non-admin already reaches this page; the row exists to carry the
            // permission, not to expose anything new.
            'route' => $anchor->route,
            'parent_id' => $anchor->parent_id ?: $anchor->id,
            'category_id' => $anchor->category_id,
            'group_id' => $anchor->group_id,
            'icon' => 'download',
            'is_active' => 1,
            // Never in an administrator's sidebar: admins pass the gate on their
            // role and do not need a toggle for it.
            'exclude_from_admin' => 1,
            'target' => $anchor->target,
            'order' => (int) DB::table('menus')
                ->where('parent_id', $anchor->parent_id ?: $anchor->id)
                ->max('order') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('menus', 'is_container')) {
            $row['is_container'] = 0;
        }

        DB::table('menus')->insert($row);
    }

    public function down(): void
    {
        $permission = EnsureMemberPiiAccess::PII_PERMISSION;

        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('permission_name', $permission)->delete();
        }

        if (! Schema::hasTable('permissions')) {
            return;
        }

        $row = DB::table('permissions')->where('name', $permission)->where('guard_name', 'web')->first();

        if (! $row) {
            return;
        }

        // Revoke before deleting, or the pivot rows outlive the permission and
        // Spatie's cache serves a name that no longer resolves.
        foreach (['role_has_permissions', 'model_has_permissions'] as $pivot) {
            if (Schema::hasTable($pivot)) {
                DB::table($pivot)->where('permission_id', $row->id)->delete();
            }
        }

        DB::table('permissions')->where('id', $row->id)->delete();

        // Same reason as up(): the pivots and the permission were removed with
        // the query builder, so nothing has told Spatie. A rollback that leaves
        // a deleted permission in the cache is the same failure in reverse.
        $this->flushPermissionCache();
    }

    /**
     * Invalidate Spatie's cached permission collection.
     *
     * Deliberately tolerant. The database work is already committed by the time
     * this runs, and a cache backend that is unreachable at deploy time must not
     * fail the migration - the deploy notes carry
     * `php artisan permission:cache-reset` as the manual equivalent, and it is
     * listed as a numbered release step for exactly this case.
     */
    private function flushPermissionCache(): void
    {
        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (Throwable $e) {
            // No cache to reach, or the package is not booted: nothing to do.
        }
    }
};
