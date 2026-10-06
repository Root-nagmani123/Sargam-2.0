<?php

namespace Tests\Feature;

use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Array-valued year filters used to 500: ?year_field[]= was an "Illegal offset
 * type" TypeError in yearColumn(), and ?year[]= / ?notice_year[]= hit a string
 * cast (PR #334 F-025). They are now ignored like any unknown value.
 */
class NoticeYearFilterInputTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    public function test_the_notice_index_ignores_an_array_year_field(): void
    {
        $this->as($this->userWithRole('Super Admin'), ['Super Admin'])
            ->get(route('admin.notice.index') . '?year_field[]=created')
            ->assertOk();
    }

    public function test_the_notice_index_ignores_an_array_year(): void
    {
        $this->as($this->userWithRole('Super Admin'), ['Super Admin'])
            ->get(route('admin.notice.index') . '?year[]=2024')
            ->assertOk();
    }

    public function test_a_scalar_year_field_is_still_honoured(): void
    {
        $html = $this->as($this->userWithRole('Super Admin'), ['Super Admin'])
            ->get(route('admin.notice.index') . '?year_field=created&year=2024')
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/<option value="created"\s+selected/', $html);
    }

    public function test_the_feed_archive_ignores_an_array_notice_year(): void
    {
        $this->as($this->userWithRole('Super Admin'), ['Super Admin'])
            ->get(route('admin.dashboard.feed') . '?tab=notices&notice_scope=archive&notice_year[]=2024')
            ->assertOk();
    }
}
