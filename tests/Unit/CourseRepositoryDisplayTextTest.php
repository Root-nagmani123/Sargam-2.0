<?php

namespace Tests\Unit;

use App\Support\CourseRepositorySearch;
use PHPUnit\Framework\TestCase;

class CourseRepositoryDisplayTextTest extends TestCase
{
    public static function displayValues(): array
    {
        return [
            'truncated course' => [' Phase-II-202 ', null, 'Phase-II-2024', 'Phase-II-2024'],
            'truncated author' => ['Dr Zq', null, 'Dr Zq Faculty', 'Dr Zq Faculty'],
            'literal wildcard prefix' => ['Phase_%', null, 'Phase_% 2024', 'Phase_% 2024'],
            'unicode prefix' => ["\u{0939}\u{093F}", null, "\u{0939}\u{093F}\u{0902}\u{0926}\u{0940}", "\u{0939}\u{093F}\u{0902}\u{0926}\u{0940}"],
            'unrelated keyword' => ['Current course', null, 'Stale course', 'Current course'],
            'shorter keyword' => ['Course 2024', null, 'Course', 'Course 2024'],
            'same text' => ['Course', null, 'Course', 'Course'],
            'case differs' => ['Law', null, 'law and policy', 'Law'],
            'numeric pk resolves' => ['123', 'Master name', 'Keyword name', 'Master name'],
            'faculty code resolves' => ['GUE-990090', 'Dr Real Faculty', 'Stale Faculty', 'Dr Real Faculty'],
            'unknown numeric pk' => ['999999999', null, 'Keyword name', 'Keyword name'],
            'empty' => ['', null, null, null],
        ];
    }

    /** @dataProvider displayValues */
    public function test_display_value_selection($stored, $resolved, $keyword, $expected): void
    {
        $method = new \ReflectionMethod(CourseRepositorySearch::class, 'pickText');
        $method->setAccessible(true);
        $this->assertSame($expected, $method->invoke(null, $stored, $resolved, $keyword));
    }
}
