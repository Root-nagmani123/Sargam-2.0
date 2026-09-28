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
     * Drops only the rows this migration is responsible for. S and E rows are
     * the user's own picks and were never derived from a column, so they stay.
     */
    public function down(): void
    {
        if (! Schema::hasTable(self::MAP_TABLE)) {
            return;
        }

        DB::table(self::MAP_TABLE)
            ->whereIn('audience_type', array_values($this->columns))
            ->delete();
    }
};
