<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Points Peer Evaluation's groups at the real Course Group Mapping ones.
 *
 * Groups now come from `group_type_master_course_master_map` (212 active rows),
 * the same list Course Group Mapping manages. peer_groups is NOT replaced: it
 * still owns the settings that mapping table has nowhere to put - max_marks,
 * buffer_marks, is_form_active - and now carries a link back to the mapping row
 * it represents.
 *
 * That keeps every existing `group_id` reference working (peer_columns,
 * peer_reflection_fields, peer_scores, peer_group_members, peer_evaluation_remarks)
 * while making the pickers list real groups.
 *
 * ⚠️ Column names in group_type_master_course_master_map LIE: `type_name` holds a
 * `course_group_type_master.pk` and `course_name` holds a `course_master.pk`,
 * both as varchar. `group_name` is the only one that is actually a name.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('peer_groups') || Schema::hasColumn('peer_groups', 'group_map_pk')) {
            return;
        }

        Schema::table('peer_groups', function (Blueprint $table) {
            // Nullable: groups created before this (and any created by the legacy
            // panel's own box) have no mapping row behind them.
            $table->unsignedBigInteger('group_map_pk')->nullable()->after('id');
            $table->index('group_map_pk');

            // One peer_groups row per (event, mapping group) - picking the same
            // group for the same event twice must reuse the row, not add another.
            $table->unique(['event_id', 'group_map_pk'], 'peer_groups_event_group_map_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('peer_groups') || ! Schema::hasColumn('peer_groups', 'group_map_pk')) {
            return;
        }

        Schema::table('peer_groups', function (Blueprint $table) {
            $table->dropUnique('peer_groups_event_group_map_unique');
            $table->dropIndex(['group_map_pk']);
            $table->dropColumn('group_map_pk');
        });
    }
};
