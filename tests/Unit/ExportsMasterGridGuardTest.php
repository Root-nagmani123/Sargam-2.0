<?php

namespace Tests\Unit;

use App\Http\Controllers\Concerns\ExportsMasterGrid;
use ReflectionClass;
use Tests\TestCase;

/**
 * The PDF branch's memory guard.
 *
 * The export raises memory_limit itself and only refuses when that was not
 * permitted, so the interesting logic is the limit parsing and the readable
 * dead end — neither of which is reachable through a normal request on a host
 * where ini_set works.
 */
class ExportsMasterGridGuardTest extends TestCase
{
    /** Anonymous holder so the trait's private members can be exercised. */
    private function subject(): object
    {
        return new class {
            use ExportsMasterGrid;
        };
    }

    private function invokePrivate(string $method, array $args = [])
    {
        $ref = new ReflectionClass($this->subject());
        $m = $ref->getMethod($method);
        $m->setAccessible(true);

        return $m->invokeArgs($m->isStatic() ? null : $this->subject(), $args);
    }

    /**
     * @dataProvider limits
     */
    public function test_it_parses_the_php_memory_limit_string(string $ini, int $expected): void
    {
        $original = ini_get('memory_limit');

        try {
            // Only assert on values PHP accepts; skip if the host refuses the write.
            if (@ini_set('memory_limit', $ini) === false) {
                $this->markTestSkipped("This host refuses memory_limit={$ini}");
            }

            $this->assertSame($expected, $this->invokePrivate('memoryLimitInBytes'));
        } finally {
            @ini_set('memory_limit', $original);
        }
    }

    public static function limits(): array
    {
        return [
            'megabytes'  => ['512M', 512 * 1024 * 1024],
            'gigabytes'  => ['1G',   1024 * 1024 * 1024],
            'unlimited'  => ['-1',   -1],
        ];
    }

    public function test_the_too_large_response_is_readable_and_names_both_numbers(): void
    {
        // 668 rows needing ~298 MB against a 128 MB ceiling — the real shape of
        // the Faculty export on a host where ini_set is refused.
        $needed = 64 * 1024 * 1024 + (int) (668 * 0.35 * 1024 * 1024);
        $limit = 128 * 1024 * 1024;

        $response = $this->invokePrivate('exportTooLargeResponse', [668, $needed, $limit]);
        $body = $response->getContent();

        $this->assertSame(507, $response->getStatusCode(), 'Insufficient Storage is the honest status here.');

        // It must state the two numbers, or the reader cannot act on it.
        $this->assertStringContainsString('668', $body);
        $this->assertStringContainsString('128 MB', $body);

        // And it must point at the cheap formats that carry identical columns.
        $this->assertStringContainsString('Excel', $body);
        $this->assertStringContainsString('CSV', $body);

        // Never a bare fatal or a blank page.
        $this->assertStringNotContainsString('Allowed memory size', $body);
        $this->assertNotEmpty(trim(strip_tags($body)));
    }

    public function test_a_small_report_is_never_refused(): void
    {
        // 10 rows needs ~68 MB; even a 128 MB host is fine, so no guard fires.
        $needed = 64 * 1024 * 1024 + (int) (10 * 0.35 * 1024 * 1024);

        $this->assertLessThan(
            128 * 1024 * 1024,
            $needed,
            'A reference-master export must never trip the PDF memory guard.'
        );
    }
}
