<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Everything Manage Evaluation Columns needs that peer_columns couldn't express.
 *
 * peer_columns was only (column_name, is_visible, course_id, event_id). The screen
 * scopes a column to a GROUP, gives it its own max marks, records whether the
 * evaluator must leave a remark, and splits columns into two rating types.
 *
 * evaluation_type is a plain string, not an enum: adding a third type later is a
 * data change instead of an ALTER, and the app validates the value anyway.
 *
 * buffer_marks goes on peer_groups, not peer_columns - it is the single pool of
 * marks an OT distributes across that group, which is why the design shows the
 * same number against every column of the group.
 *
 * peer_columns has no create migration, so every step is guarded against the live
 * schema rather than against what the migration history claims.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('peer_columns')) {
            Schema::table('peer_columns', function (Blueprint $table) {
                if (! Schema::hasColumn('peer_columns', 'group_id')) {
                    // Nullable: a column with no group is a course/event-wide one,
                    // which is how every existing row is scoped.
                    $table->unsignedBigInteger('group_id')->nullable()->after('event_id');
                    $table->index('group_id');
                }
                if (! Schema::hasColumn('peer_columns', 'max_marks')) {
                    $table->decimal('max_marks', 6, 2)->default(10)->after('column_name');
                }
                if (! Schema::hasColumn('peer_columns', 'has_remarks')) {
                    $table->boolean('has_remarks')->default(false)->after('max_marks');
                }
                if (! Schema::hasColumn('peer_columns', 'evaluation_type')) {
                    $table->string('evaluation_type', 32)->default('rate_peers')->after('has_remarks');
                    $table->index('evaluation_type');
                }
            });

            // Existing rows predate max_marks; give them the group default rather
            // than leaving a column the form would cap at 0.
            DB::table('peer_columns')->whereNull('max_marks')->update(['max_marks' => 10]);
        }

        if (Schema::hasTable('peer_groups') && ! Schema::hasColumn('peer_groups', 'buffer_marks')) {
            Schema::table('peer_groups', function (Blueprint $table) {
                // The total an OT gets to hand out under "Distribute Marks".
                $table->decimal('buffer_marks', 8, 2)->default(100)->after('max_marks');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('peer_columns')) {
            Schema::table('peer_columns', function (Blueprint $table) {
                foreach (['evaluation_type', 'has_remarks', 'max_marks', 'group_id'] as $column) {
                    if (Schema::hasColumn('peer_columns', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('peer_groups') && Schema::hasColumn('peer_groups', 'buffer_marks')) {
            Schema::table('peer_groups', function (Blueprint $table) {
                $table->dropColumn('buffer_marks');
            });
        }
    }
};
