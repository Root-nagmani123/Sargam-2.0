<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * examination_drive_component_maps gained faculty/exam-date/exam-type/sub-faculty
 * so the "Map Faculty to Examination Drive" and "Faculty Coordinates & Components"
 * screens can persist real data instead of UI-only placeholders.
 *
 * sub_faculty_names: simple JSON list of faculty_master.full_name values for the
 * "Sub-Coordinates" column — there is no dedicated per-component multi-faculty
 * junction table yet, and this keeps the Assign Faculty modal's existing
 * multi-select UI working without a schema redesign.
 */
return new class extends Migration
{
    private const TABLE = 'examination_drive_component_maps';

    private const FK = 'examination_drive_component_maps_faculty_master_pk_foreign';

    public function up(): void
    {
        // Each column is guarded so the migration is safe on a shared/developer
        // database that already has some of them (partial run, manual change).
        $has = fn (string $column) => Schema::hasColumn(self::TABLE, $column);

        Schema::table(self::TABLE, function (Blueprint $table) use ($has) {
            if (! $has('faculty_master_pk')) {
                $table->unsignedBigInteger('faculty_master_pk')->nullable()->after('subject_master_pk');
            }
            if (! $has('exam_date')) {
                $table->date('exam_date')->nullable()->after('weightage');
            }
            if (! $has('exam_type')) {
                $table->string('exam_type', 20)->nullable()->after('exam_date');
            }
            if (! $has('sub_faculty_names')) {
                $table->json('sub_faculty_names')->nullable()->after('exam_type');
            }
        });

        if (! $this->foreignKeyExists()) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->foreign('faculty_master_pk')->references('pk')->on('faculty_master')->nullOnDelete();
            });
        }
    }

    private function foreignKeyExists(): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', self::TABLE)
            ->where('CONSTRAINT_NAME', self::FK)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    public function down(): void
    {
        if ($this->foreignKeyExists()) {
            Schema::table(self::TABLE, fn (Blueprint $table) => $table->dropForeign(self::FK));
        }

        $existing = array_values(array_filter(
            ['faculty_master_pk', 'exam_date', 'exam_type', 'sub_faculty_names'],
            fn ($column) => Schema::hasColumn(self::TABLE, $column)
        ));
        if ($existing) {
            Schema::table(self::TABLE, fn (Blueprint $table) => $table->dropColumn($existing));
        }
    }
};
