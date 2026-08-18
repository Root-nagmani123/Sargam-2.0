<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * TEMPORARY regression smoke test — GET-only, read-only, run against the live dev database
 * to confirm the PR-256 review fixes did not break any page they touch. Delete after use.
 */
class TmpAffectedRoutesSmokeTest extends TestCase
{
    private function go(string $uri, ?int $actAs): array
    {
        $this->refreshApplication();

        $res = $actAs
            ? $this->actingAs(User::findOrFail($actAs))->get($uri)
            : $this->get($uri);

        $status = $res->getStatusCode();
        $body = '';

        if ($status >= 500) {
            // Surface the real exception rather than the rendered error page.
            $ex = $res->baseResponse->exception ?? null;
            $body = $ex
                ? get_class($ex).': '.$ex->getMessage().' @ '.basename($ex->getFile()).':'.$ex->getLine()
                : trim(substr(strip_tags($res->getContent()), 0, 200));
        } elseif ($status === 302) {
            $body = '-> '.$res->headers->get('Location');
        }

        return [$status, strlen((string) $res->getContent()), $body];
    }

    public function test_affected_routes_do_not_error(): void
    {
        $admin = 1;

        $cases = [
            // label                              uri                                        actAs
            ['form-overview 21',                  '/admin/reports/form/21',                   $admin],
            ['form-overview 17',                  '/admin/reports/form/17',                   $admin],
            ['form-overview COMPLETE',            '/admin/reports/form/21?status=COMPLETE',   $admin],
            ['form-overview INCOMPLETE',          '/admin/reports/form/21?status=INCOMPLETE', $admin],
            ['form-overview search',              '/admin/reports/form/21?q=a',               $admin],
            ['form-overview CSV export',          '/admin/reports/form/21/export',            $admin],
            ['reports landing',                   '/admin/reports',                           $admin],
            ['studentDetail 2332',                '/admin/reports/student/2332',              $admin],
            ['studentDetail 4186',                '/admin/reports/student/4186',              $admin],
            ['studentDetail PDF 2332',            '/admin/reports/student/2332/pdf',          $admin],
            ['fc/status default',                 '/fc/status',                               null],
            ['fc/status not-responded',           '/fc/status?tab=not-responded',             null],
            ['fc/status registered',              '/fc/status?tab=registered',                null],
            ['fc/status exemption',               '/fc/status?tab=exemption',                 null],
            ['fc/status incomplete',              '/fc/status?tab=incomplete',                null],
            ['fc/status service',                 '/fc/status?tab=service',                   null],
            ['fc/status fragment',                '/fc/status/data?tab=incomplete',           null],
            ['form settings edit',                '/fc-reg/admin/forms/21/edit',              $admin],
            ['trainee dashboard 2332',            '/fc-reg/forms/21',                         2332],
            ['trainee dashboard 4186',            '/fc-reg/forms/21',                         4186],
            ['descriptive-roll PDF 2332',         '/fc-reg/forms/21/descriptive-roll/pdf',    2332],
        ];

        $failures = [];

        foreach ($cases as [$label, $uri, $actAs]) {
            [$status, $len, $note] = $this->go($uri, $actAs);
            fwrite(STDERR, sprintf("  %-28s %-3d %9s  %s\n", $label, $status, number_format($len), $note));

            if ($status >= 500) {
                $failures[] = "$label => $status $note";
            }
        }

        $this->assertSame([], $failures, "Routes returned 5xx:\n".implode("\n", $failures));
    }
}
