<?php

use App\Models\SidebarMenu\Menu;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the "Manage Evaluation Columns" sidebar link under Peer Evaluation
 * (Setup > FC Forms), beside "Peer Evaluation Admin" and "Manage Events", plus
 * its Spatie permission so it can be granted to non-admin roles.
 *
 * The breadcrumb on that page is derived from this row (SidebarNavResolver walks
 * the category -> group -> parent chain), so without it the page falls back to
 * the legacy section-based trail and reads "Home / Academic / Manage Reflection
 * Fields" instead of "Home / Setup / FC Forms / Peer Evaluation / Manage
 * Reflection Fields".
 *
 * Idempotent.
 */
return new class extends Migration
{
    private string $route = 'admin/peer/columns';
    private string $permissionName = 'peer_evaluation_columns';
    private string $menuName = 'Manage Evaluation Columns';

    public function up(): void
    {
        // Anchor to the Peer Evaluation menu that owns the admin panel, so this
        // link inherits its category (Setup) and group (FC Forms).
        $parent = Menu::where('name', 'Peer Evaluation')
            ->where('route', 'peer_evaluation')
            ->first();

        if (! $parent) {
            // No Peer Evaluation menu on this environment - nothing to attach to.
            return;
        }

        $maxOrder = (int) Menu::where('parent_id', $parent->id)->max('order');

        Menu::firstOrCreate(
            ['route' => $this->route],
            [
                'category_id' => $parent->category_id,
                'group_id' => $parent->group_id,
                'parent_id' => $parent->id,
                'name' => $this->menuName,
                'icon' => 'view_column',
                'permission_name' => $this->permissionName,
                'order' => $maxOrder + 1,
                'is_active' => 1,
                'target' => '0',
            ]
        );

        // Create the permission (guard web) so it can be assigned to roles.
        // Written as exists-check + insert rather than updateOrInsert: one shared
        // payload would reset created_at on a pre-existing permission row.
        if (DB::getSchemaBuilder()->hasTable('permissions')) {
            $exists = DB::table('permissions')
                ->where('name', $this->permissionName)
                ->where('guard_name', 'web')
                ->exists();

            if ($exists) {
                DB::table('permissions')
                    ->where('name', $this->permissionName)
                    ->where('guard_name', 'web')
                    ->update(['updated_at' => now()]);
            } else {
                DB::table('permissions')->insert([
                    'name' => $this->permissionName,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        SidebarNavResolver::clearCache();
    }

    public function down(): void
    {
        Menu::where('route', $this->route)->delete();

        if (DB::getSchemaBuilder()->hasTable('permissions')) {
            DB::table('permissions')
                ->where('name', $this->permissionName)
                ->where('guard_name', 'web')
                ->delete();
        }

        SidebarNavResolver::clearCache();
    }
};
