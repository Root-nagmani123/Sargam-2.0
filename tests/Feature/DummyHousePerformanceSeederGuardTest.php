<?php

namespace Tests\Feature;

use Database\Seeders\DummyHousePerformanceSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * DummyHousePerformanceSeeder books closed discipline memos with marks against
 * real officer trainees. It now writes only when APP_ENV is local or testing — an
 * allow-list (PR #334 F-021): the earlier production-only deny-list let
 * APP_ENV=staging write fabricated memos.
 *
 * The seeder skips work it has already done, and its marker rows already exist on
 * the development copy, so "nothing changed" alone proves nothing. Each test first
 * removes the seeder's own marker memos inside the rolled-back transaction, so the
 * seeder would book them again if allowed to run — the local-environment control
 * shows that it does.
 */
class DummyHousePerformanceSeederGuardTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const MARKER = 'Dummy data - House wise Performance demo';

    private function markerMemos(): int
    {
        return DB::table('discipline_memo_status')->where('remarks', self::MARKER)->count();
    }

    private function counts(): array
    {
        return [
            DB::table('discipline_memo_status')->count(),
            DB::table('student_course_group_map')->count(),
            DB::table('group_type_master_course_master_map')->count(),
        ];
    }

    private function runAs(string $env): void
    {
        $original = $this->app['env'];
        $this->app['env'] = $env;

        try {
            (new DummyHousePerformanceSeeder)->run();
        } finally {
            $this->app['env'] = $original;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('discipline_memo_status')->where('remarks', self::MARKER)->delete();
    }

    /** Without this, the refusals below could pass on a database where the seeder has nothing to do. */
    public function test_control_on_local_the_seeder_does_write(): void
    {
        $this->runAs('local');

        if ($this->markerMemos() === 0) {
            $this->markTestSkipped('no running course / house type / students here, so the seeder has nothing to write');
        }

        $this->assertGreaterThan(0, $this->markerMemos());
    }

    /** @dataProvider refusedEnvironments */
    public function test_the_seeder_writes_nothing_outside_local_and_testing(string $env): void
    {
        $before = $this->counts();

        $this->runAs($env);

        $this->assertSame(0, $this->markerMemos(), "no fabricated memo on {$env}");
        $this->assertSame($before, $this->counts(), "no row of any kind on {$env}");
    }

    public static function refusedEnvironments(): array
    {
        return [['production'], ['staging'], ['uat'], ['development']];
    }
}
