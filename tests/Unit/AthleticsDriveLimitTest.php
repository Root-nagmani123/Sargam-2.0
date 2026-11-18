<?php

namespace Tests\Unit;

use App\Models\Athletics\AthleticsDrive;
use Tests\TestCase;

/**
 * The nomination cap an OT's picks are checked against (AthleticsDrive::limitError).
 * Group events never count; "event" caps each individual category, "overall" caps
 * them together.
 */
class AthleticsDriveLimitTest extends TestCase
{
    private function drive(string $criteria, int $max): AthleticsDrive
    {
        return new AthleticsDrive(['max_criteria' => $criteria, 'max_nominations' => $max]);
    }

    private function picks(string ...$categories): array
    {
        return array_map(fn ($c) => ['category' => $c], $categories);
    }

    public function test_basis_on_event_caps_each_category_separately(): void
    {
        $drive = $this->drive('event', 2);

        $this->assertNull($drive->limitError($this->picks('track', 'track', 'field', 'field')));
        $this->assertSame('You can pick at most 2 track events.', $drive->limitError($this->picks('track', 'track', 'track')));
    }

    public function test_overall_caps_individual_categories_together(): void
    {
        $drive = $this->drive('overall', 3);

        $this->assertNull($drive->limitError($this->picks('track', 'field', 'field')));
        $this->assertNotNull($drive->limitError($this->picks('track', 'track', 'field', 'field')));
    }

    public function test_group_events_never_count(): void
    {
        $this->assertNull($this->drive('overall', 1)->limitError($this->picks('track', 'group', 'group', 'group')));
        $this->assertNull($this->drive('event', 1)->limitError($this->picks('group', 'group')));
    }

    public function test_gender_spellings_normalise(): void
    {
        $this->assertSame('male', AthleticsDrive::normaliseGender(' M '));
        $this->assertSame('female', AthleticsDrive::normaliseGender('Female'));
        $this->assertNull(AthleticsDrive::normaliseGender(null));
    }
}
