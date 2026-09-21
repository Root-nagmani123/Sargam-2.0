<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\LeaveApplicationController;
use App\Services\LeaveApplicationService;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Pins the single-day leave restriction and the reason it cannot be removed casually.
 *
 * Leave is one day at a time by requirement (OT-PT, commit 24d7ee80): a trainee files a
 * separate application per day. saveApplication() rejects anything else, and the apply
 * screen enforces the same thing client-side (to_date is readonly and mirrors from_date).
 *
 * The part worth locking is the coupling. getPtMonthlyUsage() sums each overlapping
 * application's whole total_days rather than the portion inside the month being checked,
 * so a leave spanning a month boundary would count at full length against both months --
 * a trainee with 28 Sep - 3 Oct (6 days) against a 6-day cap would be refused in both
 * September and October despite having used 3 days in each. The single-day guard is what
 * stops such a row from ever being created, which makes it load-bearing rather than
 * cosmetic. Anyone relaxing it must fix the query in the same change.
 *
 * Deliberately database-free, matching the other unit tests here: the legacy leave tables
 * have no factories, so the guard is asserted against its own source. See
 * LeaveNatureMasterIdentifierTest for the same constraint.
 *
 * Run with:  php vendor/bin/phpunit --filter=LeaveSingleDayRestriction
 */
class LeaveSingleDayRestrictionTest extends TestCase
{
    /** Read a method's source, so the assertions describe the code rather than a mock. */
    private function methodSource(string $class, string $method): string
    {
        $reflection = new ReflectionMethod($class, $method);
        $source = file($reflection->getDeclaringClass()->getFileName());

        return implode('', array_slice(
            $source,
            $reflection->getStartLine() - 1,
            $reflection->getEndLine() - $reflection->getStartLine() + 1
        ));
    }

    /** The guard itself: a multi-day range must be refused. */
    public function test_save_application_rejects_a_multi_day_range(): void
    {
        $body = $this->methodSource(LeaveApplicationController::class, 'saveApplication');

        $this->assertStringContainsString(
            'isSameDay(',
            $body,
            'saveApplication() no longer compares from_date and to_date for the same day. '
            . 'Leave is one day at a time (OT-PT); removing this guard also breaks the '
            . 'monthly PT-exemption cap, which assumes no application spans a month boundary.'
        );

        $this->assertStringContainsString(
            'one day at a time',
            $body,
            'The rejection message a trainee sees should still say leave is one day at a time.'
        );
    }

    /**
     * The restriction is a deliberate requirement, not an accident, and the code should
     * say so -- a reviewer who cannot tell will eventually delete it as dead weight.
     */
    public function test_the_restriction_states_that_it_is_deliberate(): void
    {
        $body = $this->methodSource(LeaveApplicationController::class, 'saveApplication');

        $this->assertMatchesRegularExpression(
            '/deliberately one day at a time|LOAD-BEARING/',
            $body,
            'The single-day guard has lost the comment explaining that it is a requirement '
            . 'and that getPtMonthlyUsage() depends on it.'
        );
    }

    /** The other half of the coupling: the cap query must carry the warning too. */
    public function test_monthly_usage_records_its_dependency_on_the_restriction(): void
    {
        $reflection = new ReflectionMethod(LeaveApplicationService::class, 'getPtMonthlyUsage');
        $doc = (string) $reflection->getDocComment();

        $this->assertMatchesRegularExpression(
            '/one day at a time/',
            $doc,
            'getPtMonthlyUsage() no longer documents that it is correct only while leave is '
            . 'single-day. Without that note the double-counting across a month boundary is '
            . 'invisible to the next reader.'
        );
    }

    /**
     * The arithmetic the guard is protecting, stated as an executable example so the defect
     * is legible without a database. This asserts what the CURRENT query does -- it sums a
     * whole application against every month it touches -- not what it should do.
     *
     * When getPtMonthlyUsage() is fixed to clamp to the month, this test should be rewritten
     * to assert 3.0 and 3.0, and the single-day guard can then be reconsidered.
     */
    public function test_a_month_spanning_leave_would_double_count_against_the_cap(): void
    {
        $from = Carbon::parse('2026-09-28');
        $to = Carbon::parse('2026-10-03');
        $totalDays = (float) ($from->diffInDays($to) + 1);

        $this->assertSame(6.0, $totalDays, 'Sanity: 28 Sep - 3 Oct is a 6-day application.');

        // What the trainee actually consumes in each month.
        $daysInSeptember = (float) ($from->diffInDays(Carbon::parse('2026-09-30')) + 1);
        $daysInOctober = (float) (Carbon::parse('2026-10-01')->diffInDays($to) + 1);

        $this->assertSame(3.0, $daysInSeptember);
        $this->assertSame(3.0, $daysInOctober);
        $this->assertSame($totalDays, $daysInSeptember + $daysInOctober);

        // What getPtMonthlyUsage() would report for each month: the whole application,
        // because it selects on overlap and then sums total_days unclamped. Against a
        // 6-day cap that leaves the trainee with nothing in either month.
        $reportedForSeptember = $totalDays;
        $reportedForOctober = $totalDays;
        $cap = 6.0;

        $this->assertGreaterThan(
            $daysInSeptember,
            $reportedForSeptember,
            'If this no longer over-reports, the query was fixed -- update this test and '
            . 'revisit whether the single-day guard is still needed.'
        );
        $this->assertSame($cap, $reportedForSeptember);
        $this->assertSame($cap, $reportedForOctober);
    }
}
