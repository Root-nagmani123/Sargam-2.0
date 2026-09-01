<?php

use App\Models\SidebarMenu\Menu;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Database\Migrations\Migration;

/**
 * Renames the "Peer Evaluation Admin" sidebar link to "Manage Groups".
 *
 * That page (admin/peer-evaluation) used to be a catch-all admin panel carrying
 * four editors: courses, groups, evaluation columns and reflection fields. Three
 * of those now have dedicated screens of their own - Manage Events, Manage
 * Evaluation Columns, Manage Reflection Fields - so the duplicate column and
 * reflection editors were removed from it and groups are all that is left. The
 * label follows the page.
 *
 * ONLY `name` changes. `route` stays admin/peer-evaluation (bookmarks and the
 * RBAC url-access rows key off it) and `permission_name` stays
 * peer_evaluation_admin, because renaming the permission would silently drop it
 * from every role it is already assigned to.
 *
 * Matched on `route` rather than on the current name, so it is idempotent and
 * still finds the row if someone has already retitled it by hand.
 */
return new class extends Migration
{
    private string $route = 'admin/peer-evaluation';
    private string $oldName = 'Peer Evaluation Admin';
    private string $newName = 'Manage Groups';

    public function up(): void
    {
        $this->rename($this->newName);
    }

    public function down(): void
    {
        $this->rename($this->oldName);
    }

    private function rename(string $to): void
    {
        $menu = Menu::where('route', $this->route)->first();

        if (! $menu || $menu->name === $to) {
            return;
        }

        $menu->name = $to;
        $menu->save();

        SidebarNavResolver::clearCache();
    }
};
