<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Role-wise dashboard alignment (Sargam-Role-Dashboard-Alignment, 19-11-2026):
 * which existing card each role sees on which header tab.
 *
 * Only cards that already have a $cardDefinitions entry. Left out on purpose:
 *  - the six security cards for Security Approver II / III, and requested_family_id /
 *    requested_vehicle_pass for Super Admin: their counts only recognise roles named
 *    "Security Card" / "Admin Security", which no role holds, so they would always read 0.
 *    Since fixed; 2026_11_23_000000_assign_security_approver_dashboard_cards assigns them.
 *  - cards that need new count code ([NEW] in the document) and the protocol cards
 *    (their definitions are on main, not this branch).
 *
 * A role, tab or card missing from an environment is skipped, not created.
 *
 * Recoverable (PR #356 F-003): every row up() actually inserts or deletes is first written to
 * role_dashboard_card_snapshots (migration, action, role_id, dashboard_card_id,
 * sidebar_category_id), in the same transaction, and down() replays it in reverse: deleted rows
 * come back, rows up() added go. Rows that were already there are left alone either way.
 */
return new class extends Migration
{
    private const SNAPSHOTS = 'role_dashboard_card_snapshots';

    private const WIDGETS = ['widget_notices', 'widget_admin_summary', 'widget_todays_birthdays', 'widget_calendar', 'widget_campus_tweets'];

    private const ACADEMY_STATS = ['total_active_courses', 'upcoming_courses', 'upcoming_events', 'total_inhouse_faculty', 'total_guest_faculty', 'widget_admin_summary'];

    /** role => tab slug => card keys */
    private function additions(): array
    {
        return [
            'Officer Trainee' => ['home' => ['medical_exception', 'ot_mdo_escort']],
            'Employee' => ['home' => ['widget_admin_summary', 'widget_campus_tweets']],
            'Faculty' => ['home' => ['widget_admin_summary']],
            'Director' => ['home' => ['pending_id_approval1', 'pending_dup_id_approval1', 'widget_campus_tweets']],
            'Training-Induction' => ['home' => ['upcoming_courses', 'upcoming_events', 'total_guest_faculty', 'total_students', 'widget_admin_summary']],
            'Training MCTP Admin' => ['home' => self::ACADEMY_STATS],
            'Training IST' => ['home' => self::ACADEMY_STATS],
            'PA' => ['home' => ['total_inhouse_faculty', 'total_guest_faculty', 'widget_admin_summary']],
            'TA and RF' => ['home' => ['total_inhouse_faculty', 'total_guest_faculty', 'widget_campus_tweets']],
            'Mess Admin' => ['home' => ['widget_admin_summary']],
            'Mess Billing' => ['home' => ['widget_campus_tweets']],
            'Estate Admin' => ['home' => ['widget_notices']],
            'FC-Sec-Audit' => ['home' => self::WIDGETS],
            'FC Reports Viewer' => ['home' => self::WIDGETS],
            'Super Admin' => ['home' => array_merge(self::WIDGETS, [
                'total_active_courses', 'upcoming_courses', 'total_students',
                'total_inhouse_faculty', 'total_guest_faculty', 'pending_id_approval1',
            ])],
        ];
    }

    /** role => tab slug => card keys */
    private function removals(): array
    {
        return [
            // Gated off for trainees (the academy scope refuses them).
            'Officer Trainee' => ['home' => ['academic_timetable']],
            // Academy-admin numbers; Employee menus are self-service.
            'Employee' => ['home' => ['total_active_courses', 'upcoming_courses', 'total_inhouse_faculty']],
            'Faculty' => ['academics' => array_merge(
                // Security counts
                ['pending_permanent_id', 'pending_contractual_id', 'duplicate_permanent_id', 'duplicate_contractual_id',
                    'requested_family_id', 'requested_vehicle_pass', 'pending_id_approval1', 'pending_dup_id_approval1'],
                // OT cards
                ['my_groups', 'discipline_marks_deducted', 'pending_feedback', 'medical_exception', 'ot_mdo_escort'],
                // Already on Faculty's Home
                ['total_sessions', 'total_feedback', 'academic_timetable', 'my_counsellees', 'house_wise_details',
                    'whos_who', 'widget_house_performance'],
                self::WIDGETS,
            )],
            // Faculty / OT cards: hidden from a Super Admin login by the gate anyway.
            'Super Admin' => [
                'setup' => ['total_feedback', 'my_timetable', 'my_counsellees'],
                'communications' => ['total_sessions'],
                'academics' => ['academic_timetable', 'house_wise_details', 'whos_who', 'pending_feedback', 'my_groups'],
            ],
        ];
    }

    public function up(): void
    {
        // Cards are placed per tab: needs the column 2026_11_13_000000_add_module_to_role_dashboard_cards adds.
        if (! Schema::hasColumn('role_dashboard_cards', 'sidebar_category_id')) {
            throw new RuntimeException('Run 2026_11_13_000000_add_module_to_role_dashboard_cards first: role_dashboard_cards has no sidebar_category_id column yet.');
        }

        $roles = DB::table('roles')->pluck('id', 'name');
        $tabs = DB::table('sidebar_categories')->when(Schema::hasColumn('sidebar_categories', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))->pluck('id', 'slug');
        $cards = DB::table('dashboard_cards')->pluck('id', 'key');

        $rows = function (array $plan) use ($roles, $tabs, $cards): array {
            $out = [];
            foreach ($plan as $role => $byTab) {
                foreach ($byTab as $tab => $keys) {
                    foreach ($keys as $key) {
                        if (isset($roles[$role], $tabs[$tab], $cards[$key])) {
                            $out[] = ['role_id' => $roles[$role], 'dashboard_card_id' => $cards[$key], 'sidebar_category_id' => $tabs[$tab]];
                        }
                    }
                }
            }

            return $out;
        };

        // DDL commits implicitly in MySQL, so the snapshot table is made before the transaction.
        $this->ensureSnapshotTable();
        $migration = $this->name();

        DB::transaction(function () use ($rows, $migration) {
            $exists = fn (array $row) => DB::table('role_dashboard_cards')->where($row)->lockForUpdate()->exists();
            $snapshot = fn (array $row, string $action) => DB::table(self::SNAPSHOTS)->insert($row + [
                'migration' => $migration, 'action' => $action, 'created_at' => now(),
            ]);

            // Only rows not already there are added (an admin's own row stays as it is), and
            // each is recorded so down() removes exactly what up() added.
            foreach ($rows($this->additions()) as $row) {
                if (! $exists($row)) {
                    $snapshot($row, 'inserted');
                    DB::table('role_dashboard_cards')->insert($row);
                }
            }

            // Each row is recorded before it is deleted, so an assignment an admin made is
            // never lost: down() puts it back.
            foreach ($rows($this->removals()) as $row) {
                if ($exists($row)) {
                    $snapshot($row, 'deleted');
                    DB::table('role_dashboard_cards')->where($row)->delete();
                }
            }
        });
    }

    /**
     * Undo exactly what up() recorded: re-insert the rows it deleted, remove the rows it added.
     * Without a snapshot (up() ran before PR #356 F-003 made it record one, as on dev) there is
     * nothing to replay and nothing is changed: restore from the role_dashboard_cards export.
     */
    public function down(): void
    {
        if (! Schema::hasTable(self::SNAPSHOTS)) {
            return;
        }
        $migration = $this->name();

        DB::transaction(function () use ($migration) {
            $recorded = DB::table(self::SNAPSHOTS)->where('migration', $migration)->orderByDesc('id')->get();
            foreach ($recorded as $snap) {
                $row = ['role_id' => $snap->role_id, 'dashboard_card_id' => $snap->dashboard_card_id, 'sidebar_category_id' => $snap->sidebar_category_id];
                $snap->action === 'deleted'
                    ? DB::table('role_dashboard_cards')->insertOrIgnore($row)
                    : DB::table('role_dashboard_cards')->where($row)->delete();
            }
            DB::table(self::SNAPSHOTS)->where('migration', $migration)->delete();
        });
    }

    /** Migration name as the migrations table records it. */
    private function name(): string
    {
        return '2026_11_19_000000_align_role_dashboard_cards';
    }

    /** The audit table up() records into; kept by down() so other migrations can share it. */
    public function ensureSnapshotTable(): void
    {
        if (Schema::hasTable(self::SNAPSHOTS)) {
            return;
        }
        Schema::create(self::SNAPSHOTS, function (Blueprint $table) {
            $table->id();
            $table->string('migration');
            $table->string('action', 16); // inserted | deleted
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('dashboard_card_id');
            $table->unsignedBigInteger('sidebar_category_id');
            $table->timestamp('created_at')->nullable();
            $table->index(['migration', 'action']);
        });
    }
};
