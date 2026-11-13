<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\LeaveApplicationController;
use App\Http\Controllers\Admin\LeaveOnBehalfController;
use App\Services\LeaveApplicationService;
use ReflectionMethod;
use Tests\TestCase;

class LeaveTimeCanonicalizationTest extends TestCase
{
    public function test_optional_seconds_are_normalized_before_comparison(): void
    {
        $service = new LeaveApplicationService;

        $this->assertSame('09:00:00', $service->normalizeTime('09:00'));
        $this->assertSame('09:00:00', $service->normalizeTime('09:00:00'));
        $this->assertLessThanOrEqual(
            $service->normalizeTime('09:00'),
            $service->normalizeTime('09:00:00')
        );
        $this->assertGreaterThan(
            $service->normalizeTime('09:00'),
            $service->normalizeTime('09:00:01')
        );
    }

    public function test_both_leave_submission_paths_use_canonical_time_comparison(): void
    {
        foreach ([
            [LeaveApplicationController::class, 'saveApplication'],
            [LeaveOnBehalfController::class, 'store'],
        ] as [$class, $method]) {
            $reflection = new ReflectionMethod($class, $method);
            $source = file($reflection->getDeclaringClass()->getFileName());
            $body = implode('', array_slice(
                $source,
                $reflection->getStartLine() - 1,
                $reflection->getEndLine() - $reflection->getStartLine() + 1
            ));

            $this->assertStringContainsString('normalizeTime($validated[\'time_to\'])', $body);
            $this->assertStringContainsString('normalizeTime($validated[\'time_from\'])', $body);
        }
    }
}
