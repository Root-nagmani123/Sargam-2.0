php artisan serve <?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\SidebarMenu\Menu;
use App\Services\SidebarMenu\SidebarNavResolver;

/**
 * Adds the "Doc Verification" sidebar link under Fc-reg Admin (parent menu 227),
 * alongside "Descriptive Report" (237) and "Fc Health Report" (238), plus its
 * Spatie permission so it can be granted to non-admin roles. Idempotent.
 */
return new class extends Migration
{
    private string $route          = 'admin/reports/document-verification';
    private string $permissionName = 'document_verification_report';
    private string $menuName        = 'Doc Verification';

    public function up(): void
    {
        // Anchor to the same parent/category/group as the sibling FC report menus.
        $parent = Menu::where('name', 'Fc-reg Admin')->first() ?? Menu::find(227);
        if (! $parent) {
            // No FC-reg Admin parent on this environment — nothing to attach to.
            return;
        }

        $maxOrder = (int) Menu::where('parent_id', $parent->id)->max('order');

        Menu::firstOrCreate(
            ['route' => $this->route],
            [
                'category_id'     => $parent->category_id,
                'group_id'        => $parent->group_id,
                'parent_id'       => $parent->id,
                'name'            => $this->menuName,
                'icon'            => null,
                'permission_name' => $this->permissionName,
                'order'           => $maxOrder + 1,
                'is_active'       => 1,
                'target'          => '0',
            ]
        );

        // Create the permission (guard web) so it can be assigned to roles.
        if (DB::getSchemaBuilder()->hasTable('permissions')) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $this->permissionName, 'guard_name' => 'web'],
                ['updated_at' => now(), 'created_at' => now()]
            );
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
