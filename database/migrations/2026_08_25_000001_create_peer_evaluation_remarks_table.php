<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-evaluator remarks about an evaluated OT.
 *
 * The Evaluation Report's detail view shows one free-text remark per evaluator
 * beside the scores they gave, but nothing stored that. peer_scores is keyed per
 * CRITERION, so a remark can't live there; reflection_responses is keyed
 * (evaluator, field, group) - the evaluator's own reflection about the exercise,
 * not about a particular peer - so reusing it would repeat the same text against
 * every OT that evaluator scored.
 *
 * Hence one row per (group, member, evaluator), matching the granularity of the
 * "Remarks" toggle on the evaluation form.
 *
 * Also clears the four orphaned reflection_responses rows that
 * 2026_08_24_000002_point_peer_evaluation_at_course_master missed: they point at
 * group/field ids that migration deleted, so nothing can read them. Backed up
 * first, like that one.
 */
return new class extends Migration
{
    private const TABLE = 'peer_evaluation_remarks';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            Schema::create(self::TABLE, function (Blueprint $table) {
                $table->id();
                // No FK to peer_group_members: the module has exactly one FK today
                // (peer_group_members.group_id) and adding more here would make the
                // next data purge need constraint surgery. Indexed instead.
                $table->unsignedBigInteger('group_id');
                $table->unsignedBigInteger('member_id');   // peer_group_members.id
                $table->unsignedBigInteger('evaluator_id'); // user_credentials.pk
                $table->text('remarks')->nullable();
                $table->timestamps();

                // One remark per evaluator per evaluated OT, so the form's
                // updateOrInsert has a key to match on.
                $table->unique(['group_id', 'member_id', 'evaluator_id'], 'peer_eval_remarks_unique');
                $table->index('member_id');
            });
        }

        $this->purgeOrphanedReflectionResponses();
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
        // The purged rows are only in the backup file - down() cannot restore them.
    }

    private function purgeOrphanedReflectionResponses(): void
    {
        if (! Schema::hasTable('reflection_responses')) {
            return;
        }

        // Orphan = its group or its field no longer exists. Written as NOT EXISTS
        // rather than a blanket delete so a live response is never touched.
        $orphans = DB::table('reflection_responses')
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('peer_groups')
                ->whereColumn('peer_groups.id', 'reflection_responses.group_id'))
            ->orWhereNotExists(fn ($q) => $q->selectRaw('1')->from('peer_reflection_fields')
                ->whereColumn('peer_reflection_fields.id', 'reflection_responses.field_id'));

        $rows = $orphans->get();

        if ($rows->isEmpty()) {
            return;
        }

        $dir = storage_path('app/backups');
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new \RuntimeException("Cannot create {$dir} - refusing to purge without a backup.");
        }

        $path = $dir . DIRECTORY_SEPARATOR . 'reflection_responses_orphans_' . now()->format('Ymd_His') . '.json';
        $json = json_encode([
            'purged_at' => now()->toIso8601String(),
            'reason' => 'orphaned by 2026_08_24_000002_point_peer_evaluation_at_course_master',
            'rows' => $rows,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false || file_put_contents($path, $json) === false) {
            throw new \RuntimeException("Cannot write {$path} - refusing to purge without a backup.");
        }

        DB::table('reflection_responses')->whereIn('id', $rows->pluck('id'))->delete();
    }
};
