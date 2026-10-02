<?php

namespace Tests\Feature;

use App\Http\Requests\FacultyRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * faculty_master.abbreviation is VARCHAR(12) under STRICT_TRANS_TABLES: a longer
 * value used to reach the insert and fail the whole Faculty save with SQLSTATE
 * 22001. Both Faculty forms post to faculty/store, which validates with
 * FacultyRequest; the 422 it returns is shown against the field.
 */
class FacultyAbbreviationValidationTest extends TestCase
{
    private function abbreviationErrors(?string $value): array
    {
        $rules = (new FacultyRequest)->rules();

        return Validator::make(['abbreviation' => $value], ['abbreviation' => $rules['abbreviation']])
            ->errors()
            ->get('abbreviation');
    }

    public function test_thirteen_characters_are_refused(): void
    {
        $this->assertNotEmpty($this->abbreviationErrors(str_repeat('A', 13)));
    }

    public function test_twelve_characters_and_blank_are_accepted(): void
    {
        $this->assertSame([], $this->abbreviationErrors(str_repeat('A', 12)));
        $this->assertSame([], $this->abbreviationErrors(null));
    }
}
