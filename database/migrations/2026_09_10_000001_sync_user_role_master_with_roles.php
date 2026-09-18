<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ensures every role in `roles` (Spatie — managed via Role & Permission >
 * Roles) has at least one matching ACTIVE row in `user_role_master` (the
 * Member wizard's "Role Options" checkbox source), so a role created on any
 * environment automatically becomes selectable there too, instead of relying
 * on a manual data fix being re-run by hand on every environment.
 *
 * IMPORTANT — this only mirrors *names* between the two tables; it does not
 * make Member wizard role selection grant Spatie RBAC (they remain two
 * separate systems — see PR #319 review F-007, still an open product
 * decision as of this migration).
 *
 * Names are compared after lowercasing, trimming, and collapsing runs of
 * space/hyphen/underscore to a single space, so e.g. "Super Admin" and
 * "Super-Admin" are treated as the same role — this migration does not
 * itself create near-duplicate options that differ only by separator (PR
 * #319 review, F-006).
 *
 * Still insert-only for existing rows: it never renames or deletes a
 * user_role_master row just because it normalizes to the same key as
 * something already present, since which of possibly several existing rows
 * was "meant" to be that role isn't safe to guess unsupervised on an
 * environment this migration hasn't been eyeballed against.
 */
return new class extends Migration
{
    private function normalize(string $name): string
    {
        $name = mb_strtolower(trim($name));

        return preg_replace('/[\s_-]+/', ' ', $name);
    }

    public function up(): void
    {
        $roleNames = DB::table('roles')->pluck('name');

        $existingActiveNames = DB::table('user_role_master')
            ->where(function ($query) {
                $query->where('active_inactive', 1)
                    ->orWhereNull('active_inactive');
            })
            ->pluck('user_role_display_name')
            ->map(fn ($name) => $this->normalize($name))
            ->flip();

        $now = now();
        $rowsToInsert = [];

        foreach ($roleNames as $roleName) {
            $key = $this->normalize($roleName);

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
        // Best-effort reversal: remove only rows this migration could have
        // added — an exact display-name match against a role that still
        // exists in `roles` (this migration always inserts the exact
        // `roles.name` value, never a normalized form, so an exact match is
        // the correct test here even though up() uses normalized matching
        // to decide *whether* to insert) — and only if the row isn't
        // currently assigned to any member. A row that's in use, or that no
        // longer matches an existing role, is left in place rather than
        // guessed at.
        $roleNames = DB::table('roles')->pluck('name');

        if ($roleNames->isEmpty()) {
            return;
        }

        $candidates = DB::table('user_role_master')
            ->whereIn('user_role_display_name', $roleNames)
            ->get(['pk']);

        foreach ($candidates as $row) {
            $inUse = DB::table('employee_role_mapping')
                ->where('user_role_master_pk', $row->pk)
                ->exists();

            if (! $inUse) {
                DB::table('user_role_master')->where('pk', $row->pk)->delete();
            }
        }
    }
};
