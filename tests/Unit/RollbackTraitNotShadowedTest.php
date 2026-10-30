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
            if ($file->getExtension() !== 'php' || $file->getPathname() === __FILE__) {
                continue;
            }
            foreach (self::offendersIn(file_get_contents($file->getPathname())) as $method) {
                $offenders[] = basename($file->getPathname()).'::'.$method;
            }
        }

        $this->assertSame([], $offenders, 'these shadow the trait and commit their fixtures');
    }

    /**
     * The guard must see every way of pulling the trait in, and an alias that is
     * never called is as bad as none (PR #334 F-046: a grouped or fully-qualified
     * import was skipped, and any "setUp as x" counted as correct).
     */
    public function test_the_scanner_flags_every_import_shape_and_an_uncalled_alias(): void
    {
        $body = "    protected function setUp(): void\n    {\n        parent::setUp();\n    }\n";

        $this->assertSame(['setUp'], self::offendersIn("class A {\n    use WithFaker, RollsBackAgainstAppDatabase;\n{$body}}"));
        $this->assertSame(['setUp'], self::offendersIn("class A {\n    use \\Tests\\Feature\\Concerns\\RollsBackAgainstAppDatabase;\n{$body}}"));
        $this->assertSame(['setUp'], self::offendersIn("class A {\n    use RollsBackAgainstAppDatabase;\n{$body}}"));
        $this->assertSame(['setUp'], self::offendersIn(
            "class A {\n    use RollsBackAgainstAppDatabase {\n        setUp as openRollbackTransaction;\n    }\n{$body}}"
        ), 'aliased but never called');

        // PHP method names are case-insensitive, and a call inside a comment is not a call (PR #334 F-064).
        $this->assertSame(['setUp'], self::offendersIn(
            "class A {\n    use RollsBackAgainstAppDatabase;\n    protected function setup(): void\n    {\n        parent::setUp();\n    }\n}"
        ), 'lowercase setup()');
        $this->assertSame(['setUp'], self::offendersIn(
            "class A {\n    use RollsBackAgainstAppDatabase {\n        setUp as openRollbackTransaction;\n    }\n"
            ."    protected function setUp(): void\n    {\n        // \$this->openRollbackTransaction();\n        parent::setUp();\n    }\n}"
        ), 'alias called only in a comment');

        $this->assertSame([], self::offendersIn(
            "class A {\n    use WithFaker, RollsBackAgainstAppDatabase {\n        setUp as openRollbackTransaction;\n    }\n"
            ."    protected function setUp(): void\n    {\n        \$this->openRollbackTransaction();\n    }\n}"
        ));
        $this->assertSame([], self::offendersIn("class A {\n    use RollsBackAgainstAppDatabase;\n}"), 'no override, no problem');
        $this->assertSame([], self::offendersIn("class A {\n    use WithFaker;\n{$body}}"), 'trait not used');
    }

    /** @return list<string> the trait methods this source overrides without calling the trait's own */
    private static function offendersIn(string $src): array
    {
        // Any `use` list naming the trait: alone, grouped, or fully qualified.
        if (! preg_match('/^\s*use\s+[^;{]*\bRollsBackAgainstAppDatabase\b/m', $src)) {
            return [];
        }

        // Comments out, so a commented-out call does not count as one.
        $code = '';
        foreach (token_get_all(str_starts_with(ltrim($src), '<?php') ? $src : "<?php\n".$src) as $token) {
            $code .= is_array($token) ? (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true) ? ' ' : $token[1]) : $token;
        }

        $offenders = [];
        foreach (['setUp', 'tearDown'] as $method) {
            // `i`: PHP method names are case-insensitive, so setup() overrides setUp().
            if (! preg_match('/function\s+'.$method.'\s*\(/i', $code)) {
                continue;
            }

            $called = preg_match('/\b'.$method.'\s+as\s+(\w+)/i', $code, $alias)
                && preg_match('/\$this->'.preg_quote($alias[1], '/').'\s*\(/i', $code);

            if (! $called) {
                $offenders[] = $method;
            }
        }

        return $offenders;
    }
}
