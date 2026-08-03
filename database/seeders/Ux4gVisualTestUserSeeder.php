<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

/**
 * Ux4gVisualTestUserSeeder — Stage 0 / S-4 fixture.
 *
 * Creates ONE dedicated, broadly-permissioned test account so the visual
 * regression baseline (tests/e2e/visual/baseline.spec.js) can log in and
 * capture the 494 admin pages repeatably, in any environment, without a
 * human-held production password.
 *
 * This replaces the obsolete UserSeeder.php, which targeted an `email` column
 * that does not exist on the user_credentials table and would throw if run.
 *
 * WHY THIS IS SAFE
 * ─────────────────
 *  • Idempotent: updateOrCreate keyed on user_name — re-running only refreshes
 *    the one fixture row. It never reads, edits, or deletes any real user.
 *  • Additive: inserts exactly one row into user_credentials and one role
 *    mapping. No schema change, no business data, no existing record touched.
 *  • Local-only by intent: on localhost/127.0.0.1 the login flow authenticates
 *    a user by username WITHOUT a password check (see LoginController@authenticate,
 *    the LOCAL branch), so no password/hash is stored or needed here. In
 *    production the same username would fall through to LDAP and fail closed.
 *
 * USAGE
 *   php artisan db:seed --class=Ux4gVisualTestUserSeeder
 *   E2E_USERNAME=ux4g_visual_test E2E_PASSWORD=not-checked-on-localhost \
 *     npx playwright test tests/e2e/visual/baseline.spec.js --project=chrome
 *
 * REMOVE WHEN DONE (optional)
 *   php artisan tinker --execute="\App\Models\User::where('user_name','ux4g_visual_test')->delete();"
 */
class Ux4gVisualTestUserSeeder extends Seeder
{
    private const USERNAME  = 'ux4g_visual_test';
    private const ROLE_NAME = 'Super Admin';   // id 1, 170 permissions → broadest sidebar

    public function run(): void
    {
        // Guard against ever running outside local/testing. This fixture only makes
        // sense where the login flow skips password verification; anywhere else it
        // would seed a dead account (LDAP would reject it) and pollute prod data.
        if (! app()->environment(['local', 'testing'])) {
            $this->command->warn("Refusing to seed the visual-test user in '" . app()->environment() . "' environment.");
            return;
        }

        // The User model's $fillable is stale (name/email/password are not real
        // columns), so forceFill with the ACTUAL user_credentials columns.
        $user = User::where('user_name', self::USERNAME)->first() ?? new User();
        // This table has no created_at/updated_at — it uses reg_date/updated_date,
        // both DEFAULT CURRENT_TIMESTAMP. Disable Eloquent's timestamp writes so it
        // doesn't try to insert non-existent columns.
        $user->timestamps = false;
        $user->forceFill([
            'user_name'       => self::USERNAME,
            'first_name'      => 'UX4G',
            'last_name'       => 'Visual Test',
            'user_category'   => null,   // most common category; avoids the 'E'/'S' branches
            'Active_inactive' => 1,
            'jbp_enabled'     => 1,
            'login_status'    => 1,
        ])->save();

        // Ensure the Super Admin role exists before assigning (it does in this DB,
        // but keep the seeder self-contained and non-fatal if run elsewhere).
        if (! DB::table('roles')->where('name', self::ROLE_NAME)->where('guard_name', 'web')->exists()) {
            $this->command->warn("Role '" . self::ROLE_NAME . "' not found — user created without a role.");
        } else {
            $user->syncRoles([self::ROLE_NAME]);
        }

        $this->command->info(
            "Visual-test user ready: user_name=" . self::USERNAME .
            " pk={$user->pk} role=" . self::ROLE_NAME
        );
    }
}
