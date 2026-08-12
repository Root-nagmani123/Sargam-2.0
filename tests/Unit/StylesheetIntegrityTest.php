<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Guards the failure mode that blocked PR #298.
 *
 * A merge resolution kept both sides of three selector lists in
 * public/css/custom.css, leaving two `{` sharing one `}`. CSS has no
 * syntax-error reporting: the browser silently discards everything after the
 * stray brace. Chromium parsed 32 of 389 rules — ~92% of a stylesheet that
 * every admin page loads — and nothing in the build, the tests or the diff
 * review caught it.
 *
 * A brace count is a crude parser, but it is exactly the property that broke,
 * it costs microseconds, and it turns "invisible until QA opens a page" into a
 * failing test. Braces inside strings/comments would confuse it in principle;
 * in practice these files contain none, and the test asserts that assumption
 * by checking the whole directory rather than one file.
 */
class StylesheetIntegrityTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function stylesheetProvider(): array
    {
        $dir = dirname(__DIR__, 2) . '/public/css';
        $cases = [];

        foreach (glob($dir . '/*.css') ?: [] as $path) {
            // .min.css files are generated; braces there are not hand-edited.
            if (str_ends_with($path, '.min.css')) {
                continue;
            }
            $cases[basename($path)] = [$path];
        }

        return $cases;
    }

    /**
     * @dataProvider stylesheetProvider
     */
    public function test_stylesheet_braces_are_balanced(string $path): void
    {
        $css = file_get_contents($path);

        $open = substr_count($css, '{');
        $close = substr_count($css, '}');

        $this->assertSame(
            $open,
            $close,
            sprintf(
                '%s has %d "{" against %d "}". An unbalanced brace makes the browser discard every '
                . 'rule after it — see PR #298, where three stray braces cost 92%% of custom.css.',
                basename($path),
                $open,
                $close
            )
        );
    }

    /**
     * The specific shape the bad merge produced: a selector list terminated by
     * `{` and immediately followed by another selector line rather than a
     * declaration. Brace counting alone would miss this if a later rule
     * happened to be short one closer.
     *
     * @dataProvider stylesheetProvider
     */
    public function test_no_selector_line_directly_follows_an_opening_brace(string $path): void
    {
        $lines = preg_split('/\R/', (string) file_get_contents($path));
        $offenders = [];

        foreach ($lines as $i => $line) {
            if (rtrim($line) === '' || ! str_ends_with(rtrim($line), '{')) {
                continue;
            }

            // At-rules (@media, @supports, @keyframes, @layer…) open a nested
            // block whose first child IS legitimately a selector — that is the
            // one case where this shape is correct.
            if (str_starts_with(ltrim($line), '@')) {
                continue;
            }

            $next = trim($lines[$i + 1] ?? '');
            if ($next === '' || str_starts_with($next, '/*') || str_starts_with($next, '*')) {
                continue;
            }

            // A declaration reads `prop: value;`. A selector line ends in a
            // comma or an opening brace and carries no colon-value pair.
            $looksLikeSelector = (str_ends_with($next, ',') || str_ends_with($next, '{'))
                && ! str_contains($next, ': ');

            if ($looksLikeSelector) {
                $offenders[] = sprintf('line %d: "%s" is followed by selector "%s"', $i + 1, trim($line), $next);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            basename($path) . ' has a selector list immediately after an opening brace — the '
            . "signature of a merge that kept both sides of a rule:\n  " . implode("\n  ", $offenders)
        );
    }
}
