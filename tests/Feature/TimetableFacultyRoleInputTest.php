<?php

namespace Tests\Feature;

use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * faculty_role is stored as posted and shown in the Timetable Session Report, a
 * DataTables grid that wrote it into the cell as HTML (PR #334 F-024). Each role
 * is now validated against the known roles, and the column renders as text.
 */
class TimetableFacultyRoleInputTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private const PAYLOAD = '<img src=x onerror=alert(1)>';

    public function test_creating_an_event_rejects_a_role_outside_the_known_roles(): void
    {
        $this->as($this->userWithRole('Super Admin'), ['Super Admin'])
            ->post(route('calendar.event.store'), ['faculty_role' => [self::PAYLOAD]])
            ->assertSessionHasErrors('faculty_role.0');
    }

    public function test_updating_an_event_rejects_a_role_outside_the_known_roles(): void
    {
        $timetablePk = \Illuminate\Support\Facades\DB::table('timetable')->value('pk');
        if ($timetablePk === null) {
            $this->markTestSkipped('no timetable row');
        }

        // Validation fails, so nothing is written.
        $this->as($this->userWithRole('Super Admin'), ['Super Admin'])
            ->post(route('calendar.event.update', encrypt((int) $timetablePk)), ['faculty_role' => [self::PAYLOAD]])
            ->assertSessionHasErrors('faculty_role.0');
    }

    public function test_a_known_role_passes_the_role_rule(): void
    {
        $this->as($this->userWithRole('Super Admin'), ['Super Admin'])
            ->post(route('calendar.event.store'), ['faculty_role' => ['Teaching']])
            ->assertSessionDoesntHaveErrors('faculty_role.0');
    }

    public function test_the_report_grid_renders_the_role_column_as_text(): void
    {
        $html = $this->as($this->userWithRole('Super Admin'), ['Super Admin'])
            ->get(route('timetable-report.index'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            "/data: 'faculty_role',[^}]*render: \\$\\.fn\\.dataTable\\.render\\.text\\(\\)/",
            $html
        );
    }
}
