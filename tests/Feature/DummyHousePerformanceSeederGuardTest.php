<?php

namespace Tests\Feature;

use Database\Seeders\DummyHousePerformanceSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * DummyHousePerformanceSeeder books closed discipline memos with marks against
 * real officer trainees. It had no environment guard (PR #334 F-015); it now
 * writes nothing when the application environment is production.
 */
class DummyHousePerformanceSeederGuardTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function counts(): array
    {
        return [
            DB::table('discipline_memo_status')->count(),
            DB::table('student_course_group_map')->count(),
            DB::table('group_type_master_course_master_map')->count(),
        ];
    }

    public function test_the_seeder_writes_nothing_on_production(): void
    {
        $before = $this->counts();
        $env = $this->app['env'];

        $this->app['env'] = 'production';
        try {
            (new DummyHousePerformanceSeeder())->run();
        } finally {
            $this->app['env'] = $env;
        }

        $this->assertSame($before, $this->counts());
    }
}
