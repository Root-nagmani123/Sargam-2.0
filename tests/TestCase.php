<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /** Checked once per process, not once per test. */
    private static bool $targetDatabaseChecked = false;

    /**
     * Refuse to run against a database that looks like a shared environment.
     *
     * This suite deliberately runs against whatever `.env` points at, because
     * the tests read real reference rows and would not survive an empty schema
     * (see phpunit.xml). The price is that the only thing standing between a
     * mutating feature test and a shared database is a transaction — and a
     * connection drop mid-test defeats that.
     *
     * A name check is not isolation, but it turns the most likely accident — a
     * developer whose .env still points at staging — from silent data damage
     * into a refusal on the first test. Set SARGAM_ALLOW_TESTS_ON to the exact
     * database name to override deliberately.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (self::$targetDatabaseChecked) {
            return;
        }

        self::$targetDatabaseChecked = true;

        $database = (string) config('database.connections.'.config('database.default').'.database');

        if ($database === '' || $database === ':memory:') {
            return;
        }

        if (preg_match('/(prod|production|live|staging|uat)/i', $database)
            && $database !== env('SARGAM_ALLOW_TESTS_ON')) {
            $this->fail(
                "Refusing to run the test suite against '{$database}': the name looks like a shared "
                .'environment and these tests write rows. Point DB_DATABASE at a development copy, '
                ."or set SARGAM_ALLOW_TESTS_ON={$database} if that really is what you want."
            );
        }
    }
}
