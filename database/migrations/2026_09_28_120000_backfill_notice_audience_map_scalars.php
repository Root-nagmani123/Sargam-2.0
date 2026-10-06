<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Make notice_audience_map the single source of truth for a notice's audience.
 *
 * The first cut of this feature allowed one course, one group and one department
 * per notice, held in scalar columns. Authors need to address several at once, so
 * those three selections move into the map table that already held the
 * individually-picked recipients:
 *
 *   C = course_master.pk
 *   G = group_type_master_course_master_map.pk
 *   D = department_master.pk
 *   S = student_master.pk      (unchanged)
 *   E = employee_master.pk     (unchanged)
 *
 * This backfills every existing notice so reads never need a "map row, else fall
 * back to the column" branch. The scalar columns stay on the table and are still
 * written when exactly one value is selected — they are a denormalised
 * convenience for anything outside this module that still reads them, never the
 * value this module reads back.
 *
 * Re-runnable: each insert skips notices that already have a row of that type.
 */
return new class extends Migration
{
    private const TABLE = 'notices_notification';
    private const MAP_TABLE = 'notice_audience_map';

    /** scalar column => audience_type */
    private array $columns = [
        'course_master_pk'     => 'C',
        'group_type_map_pk'    => 'G',
        'department_master_pk' => 'D',
    ];

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! Schema::hasTable(self::MAP_TABLE)) {
            return;
        }

        foreach ($this->columns as $column => $type) {
            if (! Schema::hasColumn(self::TABLE, $column)) {
                continue;
            }

            DB::table(self::TABLE)
                ->whereNotNull($column)
                ->where($column, '>', 0)
                ->orderBy('pk')
                ->select('pk', $column)
                ->chunk(500, function ($notices) use ($column, $type) {
                    $rows = [];

                    foreach ($notices as $notice) {
                        $exists = DB::table(self::MAP_TABLE)
                            ->where('notices_notification_pk', $notice->pk)
                            ->where('audience_type', $type)
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        $rows[] = [
                            'notices_notification_pk' => $notice->pk,
                            'audience_type'           => $type,
                            'reference_pk'            => $notice->{$column},
                            'active_inactive'         => 1,
                        ];
                    }

                    if ($rows) {
                        DB::table(self::MAP_TABLE)->insert($rows);
                    }
                });
        }
    }

    /**
     * Deliberately a no-op (PR #334 F-014).
     *
     * The previous body deleted EVERY C/G/D row. After deploy those rows are also
     * written by the notice form (multi-course, group and department picks), and
     * a backfilled row is indistinguishable from a form-written one — the form
     * keeps the scalar column in step for a single selection. Deleting them while
     * this module's code is live turns every targeted notice into "all courses /
     * all departments" (or, in group mode, into nobody), and the scalar columns
     * cannot rebuild a multi-value audience.
     *
     * Nothing needs undoing for a schema rollback: the backfill only added rows,
     * and 2026_09_28_100000's down() drops notice_audience_map altogether. If the
     * backfilled rows must go without that, remove them by hand under DBA
     * supervision after exporting the table.
     */
    public function down(): void
    {
        //
    }
};
