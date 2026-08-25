<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves Peer Evaluation off its own bespoke course list and onto the real
 * course_master.
 *
 * Every peer_* table stored `course_id` as a `peer_courses.id`. peer_courses was
 * a hand-typed list of 9 throwaway rows ("Test 123", "new course", "live2001")
 * with a single name in common with the 145 real courses, so the module could
 * never talk about the same course as the rest of the app. After this migration
 * `course_id` on peer_events / peer_groups / peer_columns /
 * peer_reflection_fields is a `course_master.pk`.
 *
 * ⚠️ DESTRUCTIVE, and deliberately so: the existing peer rows are test data
 * keyed to ids that mean nothing in course_master, so they are purged rather
 * than remapped onto arbitrary real courses. Everything deleted is written to
 * storage/app/backups/peer_evaluation_purge_<timestamp>.json first — down()
 * restores the table structure, NOT the rows, so that file is the only way back.
 *
 * No FK constraints had to be dropped: nothing referenced peer_courses (the only
 * FK in the module is peer_group_members.group_id -> peer_groups.id), and
 * course_id is already `bigint unsigned`, matching course_master.pk.
 */
return new class extends Migration
{
    /** Child-first, so peer_group_members never outlives peer_groups. */
    private const PURGE_ORDER = [
        'peer_scores',
        'peer_group_members',
        'peer_groups',
        'peer_columns',
        'peer_reflection_fields',
        'peer_events',
    ];

    public function up(): void
    {
        $this->backup();

        foreach (self::PURGE_ORDER as $table) {
            if (Schema::hasTable($table)) {
                // delete() not truncate(): truncate is DDL, so it would both
                // ignore the FK on peer_group_members and implicitly commit.
                DB::table($table)->delete();
            }
        }

        // peer_courses is retired - the module reads course_master now.
        if (Schema::hasTable('peer_courses')) {
            Schema::drop('peer_courses');
        }
    }

    public function down(): void
    {
        // Structure only. The purged rows live in the backup JSON written by up().
        if (! Schema::hasTable('peer_courses')) {
            Schema::create('peer_courses', function (Blueprint $table) {
                $table->id();
                $table->string('course_name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Dump every row this migration is about to destroy.
     *
     * Failure to write the backup aborts the migration - purging without a copy
     * is not an acceptable outcome just because the disk was unwritable.
     */
    private function backup(): void
    {
        $payload = [
            'purged_at' => now()->toIso8601String(),
            'reason' => 'peer_evaluation repointed from peer_courses to course_master',
            'tables' => [],
        ];

        foreach (array_merge(self::PURGE_ORDER, ['peer_courses']) as $table) {
            if (Schema::hasTable($table)) {
                $payload['tables'][$table] = DB::table($table)->get()->toArray();
            }
        }

        $dir = storage_path('app/backups');
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new \RuntimeException("Cannot create {$dir} - refusing to purge peer data without a backup.");
        }

        $path = $dir . DIRECTORY_SEPARATOR . 'peer_evaluation_purge_' . now()->format('Ymd_His') . '.json';
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false || file_put_contents($path, $json) === false) {
            throw new \RuntimeException("Cannot write {$path} - refusing to purge peer data without a backup.");
        }
    }
};
