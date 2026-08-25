<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Manage Events (Peer Evaluation) needs three fields the table never had:
 * a schedule (start/end date) and a free-text description.
 *
 * It also fixes the uniqueness rule. `peer_events_event_name_unique` is a
 * GLOBAL unique on event_name, but events hang off a course — so once one
 * course owns "Event 1", no other course can ever have an event by that name.
 * The live data already shows people working around it ("Event1" on course 15
 * vs "Event 1" on course 19). Replaced with a composite unique on
 * (course_id, event_name): unique within a course, free across courses.
 *
 * peer_events has no create migration, so every step is guarded against the
 * live schema rather than against what the migration history claims.
 */
return new class extends Migration
{
    private const TABLE = 'peer_events';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            if (! Schema::hasColumn(self::TABLE, 'start_date')) {
                $table->date('start_date')->nullable()->after('course_id');
            }
            if (! Schema::hasColumn(self::TABLE, 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
            if (! Schema::hasColumn(self::TABLE, 'description')) {
                $table->text('description')->nullable()->after('end_date');
            }
        });

        // Drop the global unique before adding the composite one, or MySQL keeps
        // enforcing the old rule and the new index buys nothing.
        if ($this->hasIndex('peer_events_event_name_unique')) {
            DB::statement('ALTER TABLE `' . self::TABLE . '` DROP INDEX `peer_events_event_name_unique`');
        }

        if (! $this->hasIndex('peer_events_course_id_event_name_unique')
            && Schema::hasColumn(self::TABLE, 'course_id')
            && ! $this->hasDuplicateCourseEventNames()) {
            DB::statement(
                'ALTER TABLE `' . self::TABLE . '` '
                . 'ADD UNIQUE `peer_events_course_id_event_name_unique` (`course_id`, `event_name`)'
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        if ($this->hasIndex('peer_events_course_id_event_name_unique')) {
            DB::statement('ALTER TABLE `' . self::TABLE . '` DROP INDEX `peer_events_course_id_event_name_unique`');
        }

        // Only restore the global unique when the data still allows it — by then
        // two courses may legitimately share an event name.
        if (! $this->hasIndex('peer_events_event_name_unique') && ! $this->hasDuplicateEventNames()) {
            DB::statement('ALTER TABLE `' . self::TABLE . '` ADD UNIQUE `peer_events_event_name_unique` (`event_name`)');
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            foreach (['description', 'end_date', 'start_date'] as $column) {
                if (Schema::hasColumn(self::TABLE, $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function hasIndex(string $name): bool
    {
        return DB::select(
            'SHOW INDEX FROM `' . self::TABLE . '` WHERE Key_name = ?',
            [$name]
        ) !== [];
    }

    private function hasDuplicateCourseEventNames(): bool
    {
        return DB::table(self::TABLE)
            ->select('course_id', 'event_name')
            ->groupBy('course_id', 'event_name')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }

    private function hasDuplicateEventNames(): bool
    {
        return DB::table(self::TABLE)
            ->select('event_name')
            ->groupBy('event_name')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }
};
