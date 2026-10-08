<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * A test class that uses RollsBackAgainstAppDatabase and defines its own setUp()
 * or tearDown() replaces the trait's methods (a class method wins over a trait
 * method), so no transaction opens and every fixture COMMITS to the application
 * database. Three tests did this and left courses, students, timetables and two
 * renamed faculty in the local database. The trait's methods must be aliased
 * (`setUp as openRollbackTransaction`) and called.
 */
class RollbackTraitNotShadowedTest extends TestCase
{
    public function test_no_test_shadows_the_rollback_trait(): void
    {
        $offenders = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__)));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $src = file_get_contents($file->getPathname());
            if (! preg_match('/^\s*use\s+RollsBackAgainstAppDatabase\b/m', $src)) {
                continue;
            }
            foreach (['setUp', 'tearDown'] as $method) {
                $defines = preg_match('/function\s+' . $method . '\s*\(/', $src);
                $aliased = preg_match('/\b' . $method . '\s+as\s+\w+/', $src);
                if ($defines && ! $aliased) {
                    $offenders[] = basename($file->getPathname()) . '::' . $method;
                }
            }
        }

        $this->assertSame([], $offenders, 'these shadow the trait and commit their fixtures');
    }
}
