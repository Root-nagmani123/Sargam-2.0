<?php

namespace Tests\Unit;

use App\Support\LogSafe;
use PHPUnit\Framework\TestCase;

/**
 * Audit lines must not be forgeable by the person being audited.
 *
 * The export audit record and the toggle-status refusal record both copy
 * request text into their context. Laravel's default LineFormatter writes one
 * record per line but keeps INLINE line breaks, so a search term containing a
 * real LF ends the genuine record and starts a second one that is
 * indistinguishable from a record the application wrote — naming any actor,
 * format and row count the caller chose.
 *
 * A forgeable audit line is worse than no audit line, because it gets trusted.
 */
class LogSafeTest extends TestCase
{
    /** @return array<string, array{0: string}> */
    public static function controlCharacterProvider(): array
    {
        return [
            'line feed'        => ["\n"],
            'carriage return'  => ["\r"],
            'CRLF'             => ["\r\n"],
            'tab'              => ["\t"],
            'null byte'        => ["\0"],
            'vertical tab'     => ["\v"],
            'form feed'        => ["\f"],
            'escape'           => ["\x1B"],
            'delete'           => ["\x7F"],
        ];
    }

    /**
     * @dataProvider controlCharacterProvider
     */
    public function test_it_removes_every_control_character(string $control): void
    {
        $forged = 'searched' . $control . '[2026-09-15 10:00:00] production.INFO: Master grid export';

        $clean = LogSafe::text($forged);

        $this->assertStringNotContainsString($control, $clean);
        $this->assertSame(
            1,
            preg_match_all('/\R/', $clean) + 1,
            'A sanitised value must not be able to span more than one log line.'
        );
    }

    public function test_the_forged_second_record_cannot_start_a_new_line(): void
    {
        // The exact payload from the review: ?q=x%0A<a plausible record>
        $payload = "x\n[2026-09-15 10:00:00] production.INFO: Master grid export "
            . '{"actor":42,"slug":"Faculty","format":"excel","rows":668}';

        $clean = LogSafe::text($payload);

        $this->assertStringNotContainsString("\n", $clean);
        // The text survives — this is a neutraliser, not a censor. Only its
        // ability to break out of the record is removed.
        $this->assertStringContainsString('production.INFO', $clean);
    }

    public function test_it_leaves_an_ordinary_search_term_untouched(): void
    {
        $this->assertSame('Sharma, R. K.', LogSafe::text('Sharma, R. K.'));
        $this->assertSame('faculty@example.gov.in', LogSafe::text('faculty@example.gov.in'));
    }

    public function test_it_passes_non_strings_through_unchanged(): void
    {
        $this->assertNull(LogSafe::text(null));
        $this->assertSame(42, LogSafe::text(42));
        $this->assertSame(1.5, LogSafe::text(1.5));
        $this->assertTrue(LogSafe::text(true));
    }

    public function test_it_bounds_the_length_so_one_value_cannot_flood_the_file(): void
    {
        $clean = LogSafe::text(str_repeat('a', LogSafe::MAX_LENGTH * 3));

        $this->assertLessThanOrEqual(LogSafe::MAX_LENGTH + 1, mb_strlen($clean));
    }

    public function test_context_sanitises_every_value_including_nested_arrays(): void
    {
        $context = LogSafe::context([
            'actor'  => 7,
            'filter' => "Name: a\nforged",
            'nested' => ['table' => "venue\r\nmaster"],
        ]);

        $this->assertSame(7, $context['actor']);
        $this->assertStringNotContainsString("\n", $context['filter']);
        $this->assertStringNotContainsString("\r", $context['nested']['table']);
    }
}
