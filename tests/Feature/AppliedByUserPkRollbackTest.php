<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Rolling back 2026_09_17_000001 dropped applied_by_user_pk, the only record that
 * the training section entered a leave on an officer trainee's behalf. Every such
 * APPROVED stationed leave would then read as self-applied. down() now refuses,
 * as 2026_09_17_000003 does (PR #334 F-061).
 *
 * The Schema and DB facades are mocked, so no DDL reaches the database.
 */
class AppliedByUserPkRollbackTest extends TestCase
{
    public function test_rolling_back_does_not_drop_applied_by_user_pk(): void
    {
        $migration = require database_path('migrations/2026_09_17_000001_add_applied_by_user_pk_to_leave_application.php');

        Schema::shouldReceive('hasColumn')->andReturn(true);
        Schema::shouldReceive('table')->never();
        Schema::shouldReceive('dropColumns')->never();
        Schema::shouldReceive('dropColumn')->never();
        DB::shouldReceive('statement')->never();

        $migration->down();

        $this->addToAssertionCount(1);
    }
}
