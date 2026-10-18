<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Rolling back 2026_09_17_000003 dropped time_from / time_to, the only copy of
 * every stationed-leave departure and return time written after deploy
 * (from_date / to_date hold days only). down() now refuses, as
 * 2026_09_03_000000 does (PR #334 F-057).
 *
 * The Schema and DB facades are mocked, so no DDL reaches the database.
 */
class LeaveTimeColumnsRollbackTest extends TestCase
{
    public function test_rolling_back_does_not_drop_the_time_columns(): void
    {
        $migration = require database_path('migrations/2026_09_17_000003_add_time_from_time_to_to_leave_application.php');

        Schema::shouldReceive('hasColumn')->andReturn(true);
        Schema::shouldReceive('table')->never();
        Schema::shouldReceive('dropColumns')->never();
        Schema::shouldReceive('dropColumn')->never();
        DB::shouldReceive('statement')->never();

        $migration->down();

        $this->addToAssertionCount(1);
    }
}
