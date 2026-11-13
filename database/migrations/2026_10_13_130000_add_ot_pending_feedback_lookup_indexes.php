<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEXES = [
        'course_group_timetable_mapping' => [
            'idx_cgtm_timetable_group' => ['timetable_pk', 'group_pk'],
        ],
        'student_course_group_map' => [
            'idx_scgm_student_group' => ['student_master_pk', 'group_type_master_course_master_map_pk'],
        ],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                if (! $this->indexExists($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
                }
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                if ($this->indexExists($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
                }
            }
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        return DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$name]) !== [];
    }
};
