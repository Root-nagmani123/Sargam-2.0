<?php

namespace Tests\Unit;

use App\Services\LeaveApplicationService;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Covers the same-day apply freeze window on LeaveApplicationService.
 *
 * Database-free: these four methods are pure date arithmetic over their arguments, so the
 * boundary behaviour that matters — the minute the window closes, what freeze_before_minutes
 * shifts, and which start date is offered once it has closed — is verifiable without one.
 * getPtMonthlyUsage() is not covered here: it is a query, and the application's leave tables
 * are legacy with no factories, so a test for it could not be made to run in this environment.
 *
 * Run with:  php vendor/bin/phpunit --filter=LeaveApplyFreezeWindow
 */
class LeaveApplyFreezeWindowTest extends TestCase
{
    private LeaveApplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LeaveApplicationService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * No cutoff configured means no freeze — the screen must stay usable.
     */
    public function test_a_blank_cutoff_allows_any_start_date(): void
    {
        $now = Carbon::parse('2026-09-16 23:59:00');

        $this->assertTrue($this->service->isLeaveStartDateAllowedForApply(null, '2026-09-16', $now));
        $this->assertTrue($this->service->isLeaveStartDateAllowedForApply('', '2026-09-16', $now));
    }

    /**
     * The window applies to today only. A future start date is never frozen.
     */
    public function test_a_future_start_date_is_never_frozen(): void
    {
        $now = Carbon::parse('2026-09-16 18:00:00');

        $this->assertTrue(
            $this->service->isLeaveStartDateAllowedForApply('06:00', '2026-09-17', $now),
            'A start date after today must remain applicable however late it is.'
        );
    }

    /**
     * The boundary itself: allowed strictly before the cutoff, refused from the cutoff minute on.
     */
    public function test_the_cutoff_minute_is_the_boundary(): void
    {
        $cutoff = '06:00';

        $this->assertTrue(
            $this->service->isLeaveStartDateAllowedForApply($cutoff, '2026-09-16', Carbon::parse('2026-09-16 05:59:59')),
            'One second before the cutoff must still be allowed.'
        );
        $this->assertFalse(
            $this->service->isLeaveStartDateAllowedForApply($cutoff, '2026-09-16', Carbon::parse('2026-09-16 06:00:00')),
            'The cutoff minute itself must be refused.'
        );
        $this->assertFalse(
            $this->service->isLeaveStartDateAllowedForApply($cutoff, '2026-09-16', Carbon::parse('2026-09-16 06:00:01')),
            'After the cutoff must be refused.'
        );
    }

    /**
     * freeze_before_minutes moves the boundary earlier by exactly that many minutes.
     */
    public function test_freeze_before_minutes_moves_the_boundary_earlier(): void
    {
        $cutoff = '06:00';

        // 30 minutes of freeze closes the window at 05:30.
        $this->assertTrue(
            $this->service->isLeaveStartDateAllowedForApply($cutoff, '2026-09-16', Carbon::parse('2026-09-16 05:29:59'), 30),
            'Before the shifted boundary must still be allowed.'
        );
        $this->assertFalse(
            $this->service->isLeaveStartDateAllowedForApply($cutoff, '2026-09-16', Carbon::parse('2026-09-16 05:30:00'), 30),
            'The shifted boundary must be refused.'
        );

        // Without the freeze the same instant is fine — the shift is what refused it.
        $this->assertTrue(
            $this->service->isLeaveStartDateAllowedForApply($cutoff, '2026-09-16', Carbon::parse('2026-09-16 05:30:00'), 0)
        );
    }

    /**
     * Once today's window has closed the earliest selectable start date moves to tomorrow.
     */
    public function test_the_earliest_start_date_moves_to_tomorrow_once_the_window_closes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-16 07:00:00'));

        $this->assertSame(
            '2026-09-17',
            $this->service->resolveEarliestFromDate(null, '06:00'),
            'After the cutoff, today must no longer be offered as a start date.'
        );

        Carbon::setTestNow(Carbon::parse('2026-09-16 05:00:00'));

        $this->assertSame(
            '2026-09-16',
            $this->service->resolveEarliestFromDate(null, '06:00'),
            'Before the cutoff, today is still selectable.'
        );
    }

    /**
     * A config effective-from later than tomorrow wins over the cutoff shift.
     */
    public function test_a_later_config_minimum_wins_over_the_cutoff_shift(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-16 07:00:00'));

        $this->assertSame(
            '2026-10-01',
            $this->service->resolveEarliestFromDate('2026-10-01', '06:00'),
            'The configured effective-from date must not be pulled earlier by the cutoff rule.'
        );
    }

    /**
     * The message the user sees names the shifted time, not the raw cutoff.
     */
    public function test_the_displayed_cutoff_time_includes_the_freeze(): void
    {
        $this->assertSame('06:00 AM', $this->service->formatCutoffTimeDisplay('06:00'));
        $this->assertSame('05:30 AM', $this->service->formatCutoffTimeDisplay('06:00', 30));
        $this->assertNull($this->service->formatCutoffTimeDisplay(null));

        $this->assertStringContainsString(
            '05:30 AM',
            $this->service->applyCutoffErrorMessage('PT Exemption', '06:00', 30),
            'The refusal message must quote the time the user is actually held to.'
        );
    }
}
