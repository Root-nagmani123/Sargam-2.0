<?php

use App\Models\UserRoleMaster;
use App\Support\RoleNames;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Gives every role on Role & Permission › Roles a Role Assignment option.
 *
 * Since 658b3776e, RoleController::store() creates the option when a role is added. A
 * role added on a server before that build reached it has no option, so the Roles page
 * and the Role Assignment step show different counts (seen on preprod: "Dk Test Role",
 * created 16 Oct 2026). The 2026_09_10 sync migration does not run again where it has
 * already run, so this migration repeats that job once more.
 *
 * Insert-only. A role that already has an option row of any status (active or inactive)
 * is skipped, so no existing row is changed and a deliberately deactivated option is not
 * brought back. Safe to run on any server: where every role has an option, it does nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('user_role_master')) {
            return;
        }

        $existing = DB::table('user_role_master')
            ->pluck('user_role_display_name')
            ->map(fn ($name) => RoleNames::normalize((string) $name))
            ->flip();

        foreach (DB::table('roles')->orderBy('id')->pluck('name') as $roleName) {
            $key = RoleNames::normalize((string) $roleName);

            if (isset($existing[$key])) {
                continue;
            }

            // user_role_master name columns are varchar(100); a longer role name (allowed
            // before RoleController's max:100) cannot be stored, so report it and move on
            // rather than fail the deployment.
            if (mb_strlen((string) $roleName) > 100) {
                Log::warning('Role Assignment option not created: role name longer than 100 characters', ['role' => $roleName]);
                continue;
            }

            UserRoleMaster::ensureOptionForRole((string) $roleName);
            $existing[$key] = true;
        }
    }

    /**
     * Nothing is undone. The options added here belong to roles that exist, and the
     * Role Assignment step needs them; once employees are mapped to them, removing them
     * would lose data. Remove one by hand only if its role is also removed.
     */
    public function down(): void
    {
    }
};
