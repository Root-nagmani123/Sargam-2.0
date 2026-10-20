<?php

namespace Tests\Unit;

use Composer\Semver\Semver;
use PHPUnit\Framework\TestCase;

/**
 * The PHP version the app claims to support must be high enough for the
 * packages its lock actually contains.
 *
 * It was not. `composer.json` declared `"php": "^8.0"` while `config.platform.php`
 * pinned resolution to `8.2.12`, so the resolver was free to select packages that
 * cannot run on the range the manifest advertises - and it did: symfony/string,
 * symfony/css-selector, symfony/event-dispatcher and nette/utils all require
 * PHP >= 8.2. On a PHP 8.0 or 8.1 host, `composer install` would have accepted the
 * declared constraint and then the application would have failed at runtime.
 *
 * That gap is why "which PHP does production run?" was an open question nobody
 * could answer from inside the repository. It is now answerable without asking
 * the deploy host anything:
 *
 *   - the manifest declares ^8.2, so `composer install` REFUSES a host below 8.2
 *     rather than installing something that cannot run there;
 *   - this test asserts the declared floor is high enough for every locked
 *     package, so the gap cannot reopen;
 *   - this test asserts the RUNNING PHP satisfies the declaration, so any machine
 *     that runs the suite - CI, a developer box, a deploy smoke run - proves its
 *     own compatibility instead of being assumed compatible.
 *
 * The direction matters, and an earlier version of this test got it backwards.
 * Asserting "the pin satisfies the declaration" is vacuous: a wider declaration
 * admits the pin by definition, so `8.2.12` satisfies `^8.0` and the check passes
 * on exactly the broken configuration it was meant to catch. The meaningful
 * assertion is about the FLOOR the declaration admits.
 *
 * A pin older than the running PHP is fine and expected: resolving as if 8.2.12
 * yields packages that also run on 8.3+. The dangerous direction is a host below
 * the floor, which composer now rejects outright.
 *
 * No database, no application boot.
 */
class PhpPlatformConstraintTest extends TestCase
{
    /** PHP releases to probe when locating the floor a constraint admits. */
    private const CANDIDATES = ['8.0.0', '8.1.0', '8.2.0', '8.3.0', '8.4.0', '8.5.0'];

    private function repoRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    /** @return array{require: string, platform: string|null} */
    private function constraints(): array
    {
        $json = json_decode((string) file_get_contents($this->repoRoot() . '/composer.json'), true);

        $this->assertIsArray($json, 'composer.json did not parse.');

        return [
            'require' => $json['require']['php'] ?? '',
            'platform' => $json['config']['platform']['php'] ?? null,
        ];
    }

    /** Every locked package that constrains PHP, as name => constraint. */
    private function lockedPhpRequirements(): array
    {
        $lock = json_decode((string) file_get_contents($this->repoRoot() . '/composer.lock'), true);

        $this->assertIsArray($lock, 'composer.lock did not parse.');

        $out = [];
        foreach (['packages', 'packages-dev'] as $section) {
            foreach ($lock[$section] ?? [] as $package) {
                $php = $package['require']['php'] ?? null;
                if (is_string($php) && $php !== '') {
                    $out[$package['name']] = $php;
                }
            }
        }

        return $out;
    }

    /** The lowest probed release the constraint admits. */
    private function floorOf(string $constraint): ?string
    {
        foreach (self::CANDIDATES as $candidate) {
            if (Semver::satisfies($candidate, $constraint)) {
                return $candidate;
            }
        }

        return null;
    }

    public function test_the_declared_php_constraint_is_present(): void
    {
        $this->assertNotSame('', $this->constraints()['require'], 'composer.json must declare a php constraint.');
    }

    /**
     * The check that catches the original defect: a manifest promising support
     * for a PHP older than its own dependencies can run on.
     */
    public function test_the_declared_floor_is_high_enough_for_every_locked_package(): void
    {
        $declared = $this->constraints()['require'];
        $floor = $this->floorOf($declared);

        $this->assertNotNull($floor, sprintf('Could not locate the floor admitted by "%s".', $declared));

        $unsupported = [];
        foreach ($this->lockedPhpRequirements() as $name => $requirement) {
            if (! Semver::satisfies($floor, $requirement)) {
                $unsupported[] = sprintf('%s requires php %s', $name, $requirement);
            }
        }

        sort($unsupported);

        $this->assertSame(
            [],
            $unsupported,
            sprintf(
                "composer.json declares \"php\": \"%s\", whose lowest supported release is %s, "
                . "but these locked packages cannot run on it:\n  %s\n"
                . "Raise the declared constraint to match what the lock actually needs.",
                $declared,
                $floor,
                implode("\n  ", $unsupported)
            )
        );
    }

    /**
     * Makes every machine that runs the suite verify itself, which is the only
     * way this repository can say anything true about a deploy host.
     */
    public function test_the_running_php_satisfies_the_declared_constraint(): void
    {
        $declared = $this->constraints()['require'];

        // PHP_VERSION can carry a build suffix (e.g. "8.2.12-1~deb"); Semver
        // wants the numeric core.
        $running = implode('.', [PHP_MAJOR_VERSION, PHP_MINOR_VERSION, PHP_RELEASE_VERSION]);

        $this->assertTrue(
            Semver::satisfies($running, $declared),
            sprintf(
                'This host runs PHP %s, which does not satisfy composer.json\'s "%s". '
                . 'If this is a deploy target, its installed dependencies are not supported here.',
                $running,
                $declared
            )
        );
    }
}
