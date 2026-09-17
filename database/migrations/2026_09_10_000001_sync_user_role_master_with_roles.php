<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ensures every role in `roles` (Spatie — managed via Role & Permission > Roles)
 * has at least one matching ACTIVE row in `user_role_master` (the Member
 * wizard's "Role Options" source, see docs/member-wizard-fixes-sept-2026.md
 * §6), so a role created on any environment automatically becomes selectable
 * there too — without requiring the manual data fix to be re-run by hand on
 * every environment.
 *
 * Deliberately insert-only: it never renames or deletes an existing
 * user_role_master row, even one that looks like a stale near-match (e.g.
 * "Mess-Admin" vs "Mess Admin"). Renaming requires knowing which existing row
 * was "meant" to be that role, which isn't safe to guess unsupervised on an
 * environment this migration hasn't been eyeballed against — worst case here
 * is a harmless duplicate-looking option, not a corrupted/mismatched one.
 */
return new class extends Migration
{
    public function up(): void
    {
        $roleNames = DB::table('roles')->pluck('name');

        $existingActiveNames = DB::table('user_role_master')
            ->where(function ($query) {
                $query->where('active_inactive', 1)
                    ->orWhereNull('active_inactive');
            })
            ->pluck('user_role_display_name')
            ->map(fn ($name) => mb_strtolower(trim($name)))
            ->flip();

        $now = now();
        $rowsToInsert = [];

        foreach ($roleNames as $roleName) {
            $key = mb_strtolower(trim($roleName));

            if (isset($existingActiveNames[$key])) {
                continue;
            }

            $rowsToInsert[] = [
                'user_role_name' => $roleName,
                'user_role_display_name' => $roleName,
                'active_inactive' => 1,
                'created_date' => $now,
                'updated_date' => $now,
            ];

            // Guard against the same role appearing twice in `roles` itself.
            $existingActiveNames[$key] = true;
        }

        if (!empty($rowsToInsert)) {
            DB::table('user_role_master')->insert($rowsToInsert);
        }
    }

    public function down(): void
    {
        // Intentionally a no-op: this migration only ever inserts rows for
        // roles that were missing, and doesn't track which specific rows it
        // added versus already existed, so there's nothing safe to reverse.
    }
};
