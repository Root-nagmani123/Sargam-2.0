<?php

namespace Tests\Unit;

use App\Models\WordOfTheDay;
use Carbon\Carbon;
use Tests\TestCase;

class WordOfTheDayRotationIndexTest extends TestCase
{
    public function test_rotation_index_is_zero_on_anchor_day(): void
    {
        $anchor = Carbon::parse('2026-01-01', 'UTC');
        $day = Carbon::parse('2026-01-01', 'UTC');
        $this->assertSame(0, WordOfTheDay::rotationIndexFromAnchor($day, $anchor));
    }

    public function test_rotation_index_increments_each_day(): void
    {
        $anchor = Carbon::parse('2026-01-01', 'UTC');
        $day = Carbon::parse('2026-01-05', 'UTC');
        $this->assertSame(4, WordOfTheDay::rotationIndexFromAnchor($day, $anchor));
    }

    public function test_rotation_index_never_negative(): void
    {
        $anchor = Carbon::parse('2026-06-01', 'UTC');
        $day = Carbon::parse('2026-01-01', 'UTC');
        $this->assertSame(0, WordOfTheDay::rotationIndexFromAnchor($day, $anchor));
    }
}
