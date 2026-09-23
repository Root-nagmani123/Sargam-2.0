<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Every Blade directive that opens an output buffer must be closed.
 *
 * @section (one argument), @push, @prepend, @component and @slot all call
 * ob_start(). If the matching close is missing, the buffer is never cleaned and
 * the consequences are not local to that view:
 *
 *   - every buffer level above the leak shifts by one, so the OUTERMOST buffer -
 *     the one holding anything echoed before the view started - is the one that
 *     never gets cleaned, and its bytes are flushed at shutdown instead. That is
 *     where the stray whitespace before <!DOCTYPE html> on every admin page came
 *     from, and why CompressResponse has to absorb it before gzipping (leading
 *     bytes in front of a gzip stream render a blank page - the ERR_CONTENT_
 *     DECODING_FAILED incident).
 *   - the content of the unclosed section is captured rather than emitted, so
 *     whatever follows the opener silently disappears. On master/country/create
 *     an @ensection typo meant addCountryField() was never rendered and the Add
 *     Country button did nothing.
 *   - any test that renders such a page is reported RISKY by PHPUnit rather than
 *     passing, because the test did not close its own output buffers.
 *
 * Four files were unbalanced when this test was written: both master layouts
 * (@section('css') with no close, affecting every admin and faculty page), the
 * @ensection typo above, and a view that opened two sections and closed one.
 */
class BladeSectionBalanceTest extends TestCase
{
    /** Directives that call ob_start(). @section with a second argument does not. */
    private const OPENERS = '@(section|push|prepend|slot|component)\s*\(';

    private const CLOSERS = '@(endsection|stop|show|overwrite|append|endpush|endprepend|endslot|endcomponent)\b';

    public function test_every_blade_closes_the_buffers_it_opens(): void
    {
        // Prove the detector fires before trusting it to find nothing: a view that
        // opens a section and never closes it must be reported as unbalanced.
        $this->assertSame(
            1,
            $this->imbalance("@extends('x')\r\n@section('css')\r\n<style>a{}</style>\r\n"),
            'the detector no longer recognises an unclosed @section'
        );
        $this->assertSame(
            0,
            $this->imbalance("@section('css')\r\n<style>a{}</style>\r\n@endsection\r\n"),
            'the detector reports a balanced view as unbalanced'
        );
        $this->assertSame(
            0,
            $this->imbalance("@section('title', 'Two args opens no buffer')\r\n"),
            'a two-argument @section does not call ob_start() and must not be counted'
        );

        $unbalanced = [];

        foreach ($this->bladeFiles() as $path) {
            $delta = $this->imbalance(file_get_contents($path));

            if ($delta !== 0) {
                $unbalanced[] = sprintf('%+d  %s', $delta, str_replace(base_path().DIRECTORY_SEPARATOR, '', $path));
            }
        }

        $this->assertSame(
            [],
            $unbalanced,
            "These Blade files open more output buffers than they close (or the reverse).\n"
            . "An unclosed @section/@push/@component swallows everything after it and leaks a\n"
            . "buffer onto every page that renders the file:\n  " . implode("\n  ", $unbalanced)
        );
    }

    /** Openers minus closers, ignoring Blade comments and @php blocks. */
    private function imbalance(string $source): int
    {
        // Prose inside {{-- --}} and PHP inside @php ... @endphp mentions these
        // directive names without invoking them - the admin master layout has a
        // long comment about @section('content') that is not a directive.
        $source = preg_replace('/\{\{--.*?--\}\}/s', '', $source) ?? $source;
        $source = preg_replace('/@php\b.*?@endphp/s', '', $source) ?? $source;

        $opens = 0;
        preg_match_all('/' . self::OPENERS . '/', $source, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $match) {
            if ($match[1][0] === 'section' && $this->hasSecondArgument($source, $match[0][1] + strlen($match[0][0]))) {
                continue;
            }

            $opens++;
        }

        return $opens - preg_match_all('/' . self::CLOSERS . '/', $source);
    }

    /** True when the argument list starting at $offset carries a top-level comma. */
    private function hasSecondArgument(string $source, int $offset): bool
    {
        $depth = 1;
        $length = strlen($source);

        for ($i = $offset; $i < $length && $depth > 0; $i++) {
            $char = $source[$i];

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            } elseif ($char === ',' && $depth === 1) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function bladeFiles(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        $this->assertNotEmpty($files, 'no Blade files were found, so this test would pass vacuously');

        return $files;
    }
}
