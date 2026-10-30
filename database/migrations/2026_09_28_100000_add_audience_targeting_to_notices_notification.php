<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hierarchical target-audience selection for notices.
 *
 * Until now a notice carried only `target_audience` (Office trainee / Staff/Faculty /
 * All) plus an optional `course_master_pk`. The enhancement lets an author narrow
 * that further:
 *
 *   Office trainee  -> course (or all courses) -> group type (or all / individual OTs)
 *   Staff/Faculty   -> department (or all)     -> all / individual employees
 *
 * The "which course / which department / which group" part is a single value per
 * notice, so it lives in columns on the notice row. The "individual" part is a
 * many-per-notice list, so it gets its own map table — a JSON column cannot be
 * joined or indexed for the feed query in helpers.php, and every other list-of-
 * references in this schema is modelled as a *_map table.
 *
 * `notices_notification` has no create migration in the repo (the live schema is
 * the source of truth), so every step here is guarded and re-runnable.
 */
return new class extends Migration
{
    private const TABLE = 'notices_notification';
    private const MAP_TABLE = 'notice_audience_map';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                // NULL department = "Select All" departments (Staff/Faculty notices).
                if (! Schema::hasColumn(self::TABLE, 'department_master_pk')) {
                    $table->unsignedBigInteger('department_master_pk')->nullable()->after('course_master_pk');
                }

                // group_type_master_course_master_map.pk — the course+group row,
                // not course_group_type_master.pk, because a group type only
                // resolves to students through that mapping.
                if (! Schema::hasColumn(self::TABLE, 'group_type_map_pk')) {
                    $table->unsignedBigInteger('group_type_map_pk')->nullable()->after('department_master_pk');
                }

                // all | group | individual. NULL on legacy rows, read as "all".
                if (! Schema::hasColumn(self::TABLE, 'audience_mode')) {
                    $table->string('audience_mode', 20)->nullable()->after('group_type_map_pk');
                }
            });

            if (! $this->indexExists(self::TABLE, 'idx_nn_department')) {
                DB::statement('ALTER TABLE ' . self::TABLE . ' ADD INDEX idx_nn_department (department_master_pk)');
            }
        }

        if (! Schema::hasTable(self::MAP_TABLE)) {
            Schema::create(self::MAP_TABLE, function (Blueprint $table) {
                $table->bigIncrements('pk');
                $table->unsignedBigInteger('notices_notification_pk');

                // S = student_master.pk (Officer Trainee), E = employee_master.pk
                $table->char('audience_type', 1);
                $table->unsignedBigInteger('reference_pk');
                $table->integer('active_inactive')->default(1);
                $table->timestamp('created_date')->useCurrent();

                // The feed asks "is this user in this notice's list?" — that is the
                // lookup this index serves.
                $table->index(['notices_notification_pk', 'audience_type'], 'idx_nam_notice');
                $table->index(['audience_type', 'reference_pk'], 'idx_nam_reference');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(self::MAP_TABLE);

        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        if ($this->indexExists(self::TABLE, 'idx_nn_department')) {
            DB::statement('ALTER TABLE ' . self::TABLE . ' DROP INDEX idx_nn_department');
        }

        Schema::table(self::TABLE, function (Blueprint $table) {
            foreach (['audience_mode', 'group_type_map_pk', 'department_master_pk'] as $column) {
                if (Schema::hasColumn(self::TABLE, $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function indexExists(string $table, string $name): bool
    {
        return ! empty(DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$name]));
    }
};
