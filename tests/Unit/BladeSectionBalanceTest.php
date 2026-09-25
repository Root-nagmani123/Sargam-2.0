<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Every Blade directive that opens an output buffer must be closed.
 *
 * @section, @push, @prepend and @slot (each with one argument), @component,
 * @pushOnce, @prependOnce, @pushIf and @fragment all call ob_start(). If the
 * matching close is missing, the buffer is never cleaned and the consequences
 * are not local to that view:
 *
 *   - every buffer level above the leak shifts by one, so the OUTERMOST buffer -
 *     the one holding anything echoed before the view started - is the one that
 *     never gets cleaned, and its bytes are flushed at shutdown instead. That is
 *     where the stray whitespace before <!DOCTYPE html> on every admin page came
 *     from, and why CompressResponse has to absorb it before gzipping (leading
 *     bytes in front of a gzip stream render a blank page - the ERR_CONTENT_
 *     DECODING_FAILED incident).
 *   - the content of the unclosed section never reaches its @yield. It lands in
 *     the leaked buffer instead and is flushed OUTSIDE the response body, ahead of
 *     <!DOCTYPE html>. On master/country/create an @ensection typo put the whole
 *     addCountryField() script (and the literal text "@ensection") there, not in
 *     the page's scripts section.
 *   - any test that renders such a page is reported RISKY by PHPUnit rather than
 *     passing, because the test did not close its own output buffers.
 *
 * Four files were unbalanced when this test was written: both master layouts
 * (@section('css') with no close, affecting every admin and faculty page), the
 * @ensection typo above, and a view that opened two sections and closed one.
 */
class BladeSectionBalanceTest extends TestCase
{
    /**
     * Directives that call ob_start(). @section, @push, @prepend and @slot given a
     * second argument take it as the content and open no buffer. The leading
     * (?<![\w@]) mirrors Blade's own \B@: "@@push(" is an escape and "x@show" is
     * text, neither is a directive. Directive names are case-insensitive, as the
     * compiler dispatches them to PHP methods.
     */
    private const OPENERS = '/(?<![\w@])@(section|push|prepend|slot|component|pushOnce|prependOnce|pushIf|fragment)\s*\(/i';

    private const CLOSERS = '/(?<![\w@])@(endsection|stop|show|overwrite|append|endpush|endprepend|endslot|endcomponent|endPushOnce|endPrependOnce|endPushIf|endfragment)\b/i';

    /** Openers whose second argument is content rather than part of the call. */
    private const CONTENT_ARGUMENT = ['section', 'push', 'prepend', 'slot'];

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
        $this->assertSame(
            0,
            $this->imbalance("@push('scripts', '<script></script>')\r\n"),
            'a two-argument @push does not call ob_start() and must not be counted'
        );
        $this->assertNull(
            $this->imbalance("@endsection\r\n@section('css')\r\n"),
            'a closer before its opener balances on paper but must be reported'
        );
        $this->assertSame(
            0,
            $this->imbalance("@@section('css') is an escape, and so is x@show.y\r\n"),
            'escaped and word-embedded directives are text, not buffer operations'
        );
        foreach (['pushOnce' => 'endPushOnce', 'prependOnce' => 'endPrependOnce', 'pushIf' => 'endPushIf', 'fragment' => 'endfragment'] as $open => $close) {
            $this->assertSame(
                1,
                $this->imbalance("@{$open}('a', 'b')\r\n<i></i>\r\n"),
                "the detector no longer recognises an unclosed @{$open}"
            );
            $this->assertSame(
                0,
                $this->imbalance("@{$open}('a', 'b')\r\n<i></i>\r\n@{$close}\r\n"),
                "the detector does not pair @{$open} with @{$close}"
            );
        }

        $unbalanced = [];

        foreach ($this->bladeFiles() as $path) {
            $delta = $this->imbalance(file_get_contents($path));

            if ($delta !== 0) {
                $unbalanced[] = sprintf(
                    '%s  %s',
                    $delta === null ? 'closes before opening' : sprintf('%+d', $delta),
                    str_replace(base_path().DIRECTORY_SEPARATOR, '', $path)
                );
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

    /**
     * Openers minus closers, ignoring Blade comments, @php and @verbatim blocks.
     * Null when a closer comes before any opener it could close: Blade throws on
     * that at runtime, or it closes a buffer the file did not open.
     */
    private function imbalance(string $source): ?int
    {
        // Prose inside {{-- --}} and PHP inside @php ... @endphp mentions these
        // directive names without invoking them - the admin master layout has a
        // long comment about @section('content') that is not a directive.
        $source = preg_replace('/\{\{--.*?--\}\}/s', '', $source) ?? $source;
        $source = preg_replace('/@php\b.*?@endphp/s', '', $source) ?? $source;
        $source = preg_replace('/@verbatim\b.*?@endverbatim/s', '', $source) ?? $source;

        $events = [];
        preg_match_all(self::OPENERS, $source, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $match) {
            $name = strtolower($match[1][0]);

            if (in_array($name, self::CONTENT_ARGUMENT, true)
                && $this->hasSecondArgument($source, $match[0][1] + strlen($match[0][0]))) {
                continue;
            }

            $events[$match[0][1]] = 1;
        }

        preg_match_all(self::CLOSERS, $source, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $match) {
            $events[$match[0][1]] = -1;
        }

        ksort($events);
        $depth = 0;

        foreach ($events as $step) {
            $depth += $step;

            if ($depth < 0) {
                return null;
            }
        }

        return $depth;
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
