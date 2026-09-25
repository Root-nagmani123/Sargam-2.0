<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures every role in `roles` (Spatie — managed via Role & Permission >
 * Roles) has at least one matching ACTIVE row in `user_role_master` (the
 * Member wizard's "Role Options" checkbox source), so a role created on any
 * environment automatically becomes selectable there too, instead of relying
 * on a manual data fix being re-run by hand on every environment.
 *
 * IMPORTANT — this only mirrors *names* between the two tables; it does not
 * make Member wizard role selection grant Spatie RBAC by itself (see
 * MemberController::syncSpatieRolesFromWizardSelection() — PR #319 review
 * F-007/F-018/F-019, which gate and scope that separately).
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
 *
 * PR #319 review round 2 (F-006 follow-up): the review flagged "Student-OT"
 * beside "Officer Trainee", and "Internal Faculty"/"Guest Faculty" beside
 * "Faculty", as looking like semantic near-duplicates this migration should
 * collapse. Checked against how those names are actually used elsewhere in
 * this codebase before concluding otherwise:
 *   - "Student-OT" is never assigned as a Spatie role anywhere (grepped) — it
 *     is injected into the session only, at login (LoginController,
 *     Authenticate middleware), per the hasRole() helper's own docblock. It
 *     is a session-only pseudo-role, unrelated in kind to the real Spatie
 *     "Officer Trainee" role it superficially resembles.
 *   - "Internal Faculty" and "Guest Faculty" are each checked via hasRole()
 *     well over a dozen times across AttendanceController/CalendarController/
 *     etc., including places that branch on them *separately* from a plain
 *     hasRole('Faculty') check in the same method (e.g.
 *     CalendarController.php around lines 2490, 2536 and 2565) — they are
 *     relied on as distinct categories, not aliases of "Faculty".
 * Merging any of these would silently change existing authorization behavior
 * elsewhere in the app, which this migration must not do. Left as separate
 * rows deliberately, not as an unresolved gap.
 *
 * PR #319 review round 2 (F-020): down() used to identify "rows this
 * migration could have added" by re-deriving the same test up() uses to
 * decide whether to insert (exact name match + not currently in use) —
 * which isn't the same question. A row that already had that exact name
 * *before* up() ever ran (and so was skipped, not inserted) passes that same
 * test and got deleted anyway; an executed migrate/migrate:rollback cycle
 * showed exactly this happening to a pre-existing row ("HAC Person"). up()
 * now records the exact pk of every row it inserts in a small tracking
 * table, and down() deletes only those recorded pks — never a row it can't
 * prove it created itself, no matter how closely that row's name matches.
 */
return new class extends Migration
{
    private const LOG_TABLE = 'pr319_role_sync_log';

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

        // PR #319 review round 2 (F-007). The tracking table used to be created, and the
        // pks written into it, only AFTER the whole loop had finished. Every
        // insertGetId() below therefore committed a user_role_master row while the record
        // of it existed nowhere but in a PHP array. If the migration failed partway — a
        // duplicate-key error, a lock timeout, a killed connection — those rows survived
        // and $insertedPks did not, and down() (which returns early when the log table is
        // absent, by design) could never reverse them. An operator running
        // migrate:rollback would get a success message and no change.
        //
        // Creating the table FIRST and recording each pk in the same step as its insert
        // closes that window: whatever subset of the loop completed, the log describes
        // exactly that subset. Note this cannot be solved by wrapping the loop in a
        // transaction instead — Schema::create() causes an implicit commit in MySQL, so
        // the DDL could not participate in it. The ordering is the fix.
        if (!Schema::hasTable(self::LOG_TABLE)) {
            Schema::create(self::LOG_TABLE, function (Blueprint $table) {
                $table->unsignedBigInteger('user_role_master_pk')->primary();
            });
        }

        foreach ($roleNames as $roleName) {
            $key = $this->normalize($roleName);

            if (isset($existingActiveNames[$key])) {
                continue;
            }

            // insertGetId (rather than a single bulk insert()) so the exact pk
            // just-inserted can be recorded — the only reliable way to tell
            // this row apart later from a pre-existing row with the same name.
            $insertedPk = DB::table('user_role_master')->insertGetId([
                'user_role_name' => $roleName,
                'user_role_display_name' => $roleName,
                'active_inactive' => 1,
                'created_date' => $now,
                'updated_date' => $now,
            ]);

            DB::table(self::LOG_TABLE)->insert(['user_role_master_pk' => $insertedPk]);

            // Guard against the same role appearing twice in `roles` itself.
            $existingActiveNames[$key] = true;
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::LOG_TABLE)) {
            // No record of what (if anything) a prior up() inserted — nothing can be
            // safely reversed, so do nothing rather than guess by name.
            return;
        }

        $trackedPks = DB::table(self::LOG_TABLE)->pluck('user_role_master_pk');

        foreach ($trackedPks as $pk) {
            $inUse = DB::table('employee_role_mapping')
                ->where('user_role_master_pk', $pk)
                ->exists();

            if (!$inUse) {
                DB::table('user_role_master')->where('pk', $pk)->delete();
            }
        }

        Schema::dropIfExists(self::LOG_TABLE);
    }
};
