<?php

namespace Tests\Unit;

use PHPUnit\Framework\AssertionFailedError;
use Tests\TestCase;

/**
 * The shared-database guard in Tests\TestCase only failed the first test of a
 * process: it set its "checked" flag before failing, so every later test ran
 * against the refused database. The verdict is now kept and re-applied per test.
 */
class TestDatabaseGuardTest extends TestCase
{
    private function refusalFor(string $database): string
    {
        $connection = config('database.default');
        $saved = config("database.connections.{$connection}.database");
        config(["database.connections.{$connection}.database" => $database]);

        try {
            $m = new \ReflectionMethod(TestCase::class, 'targetDatabaseRefusal');
            $m->setAccessible(true);

            return $m->invoke(null);
        } finally {
            config(["database.connections.{$connection}.database" => $saved]);
        }
    }

    public function test_shared_looking_names_are_refused_and_others_allowed(): void
    {
        $this->assertStringContainsString("Refusing to run the test suite against 'sargam_staging_copy'", $this->refusalFor('sargam_staging_copy'));
        $this->assertStringContainsString('Refusing', $this->refusalFor('app_PROD'));
        $this->assertSame('', $this->refusalFor('sargam_dev'));
        $this->assertSame('', $this->refusalFor(':memory:'));
    }

    public function test_a_refusal_fails_every_later_test_not_only_the_first(): void
    {
        $verdict = new \ReflectionProperty(TestCase::class, 'targetDatabaseRefusal');
        $verdict->setAccessible(true);
        $saved = $verdict->getValue();
        $verdict->setValue(null, 'refused for this test');

        $later = new class('later') extends TestCase {
            public function later(): void {}

            public function runSetUp(): void
            {
                $this->setUp();
            }

            public function runTearDown(): void
            {
                $this->tearDown();
            }
        };

        try {
            $later->runSetUp();
            $this->fail('setUp() should have refused');
        } catch (AssertionFailedError $e) {
            $this->assertSame('refused for this test', $e->getMessage());
        } finally {
            $verdict->setValue(null, $saved);
            $later->runTearDown();
        }
    }
}
