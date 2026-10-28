<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Map Faculty to Examination Drive (faculty Complete Setup) redesign:
 *   - "Exam Date" became "Exam Range": exam_date is the start, exam_end_date the end.
 *   - The old Online/Offline column became "Exam Mode" (exam_mode), and a new
 *     "Exam Type" (Offline Objective, ...) now uses exam_type.
 *
 * Existing exam_type values are all Online/Offline, i.e. modes, so they move
 * to exam_mode and exam_type is cleared for those rows.
 */
return new class extends Migration
{
    private const TABLE = 'examination_drive_component_maps';

    private const OLD_MODES = ['Online', 'Offline'];

    public function up(): void
    {
        // Each column is guarded so the migration is safe on a shared/developer
        // database that already has some of them (partial run, manual change).
        $has = fn (string $column) => Schema::hasColumn(self::TABLE, $column);

        Schema::table(self::TABLE, function (Blueprint $table) use ($has) {
            if (! $has('exam_end_date')) {
                $table->date('exam_end_date')->nullable()->after('exam_date');
            }
            if (! $has('exam_mode')) {
                $table->string('exam_mode', 30)->nullable()->after('exam_end_date');
            }
        });

        // Only rows not migrated yet (exam_mode still empty).
        DB::table(self::TABLE)
            ->whereNull('exam_mode')
            ->whereIn('exam_type', self::OLD_MODES)
            ->update(['exam_mode' => DB::raw('exam_type'), 'exam_type' => null]);
    }

    public function down(): void
    {
        if (Schema::hasColumn(self::TABLE, 'exam_mode')) {
            // Put Online/Offline back where the old screens read it. A new-style
            // exam_type on such a row is lost; "Offline & Online" has no old equivalent.
            DB::table(self::TABLE)
                ->whereIn('exam_mode', self::OLD_MODES)
                ->update(['exam_type' => DB::raw('exam_mode')]);
        }

        $existing = array_values(array_filter(
            ['exam_end_date', 'exam_mode'],
            fn ($column) => Schema::hasColumn(self::TABLE, $column)
        ));
        if ($existing) {
            Schema::table(self::TABLE, fn (Blueprint $table) => $table->dropColumn($existing));
        }
    }
};
