<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * On the normal OT/Participants view the select labelled "House" listed and
 * matched hostel rooms, while the grid's house column is the House Group — so
 * picking "GANG-116" returned rows whose House Group read "Nanda Devi"
 * (PR #334 F-043). The select now says what it filters, and the export's filter
 * line names it the same way.
 */
class OtParticipantsHouseFilterLabelTest extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function summary(array $query): string
    {
        $m = new \ReflectionMethod(UserController::class, 'otParticipantsFilterSummary');
        $m->setAccessible(true);

        return $m->invoke(app(UserController::class), Request::create('/', 'GET', $query));
    }

    public function test_the_normal_view_labels_the_room_select_as_a_hostel_room(): void
    {
        // A Super Admin who is not also an Officer Trainee (canUseOtParticipants() refuses those).
        $ot = DB::table('roles')->whereIn('name', ['Officer Trainee', 'Student-OT'])->pluck('id')->all();
        $pk = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
            ->where('r.name', 'Super Admin')->where('m.model_type', User::class)
            ->whereNotIn('m.model_id', DB::table('model_has_roles')->whereIn('role_id', $ot ?: [-1])->select('model_id'))
            ->orderBy('m.model_id')->value('m.model_id');
        if (! $pk) {
            $this->markTestSkipped('no Super Admin without an Officer Trainee role');
        }

        $html = $this->as(User::findOrFail($pk), ['Super Admin'])
            ->get(route('admin.dashboard.ot-participants'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('>House: All<', $html);
        if (! str_contains($html, 'id="houseFilter"')) {
            $this->markTestSkipped('no hostel-room options for this viewer, so the select is not rendered');
        }
        $this->assertStringContainsString('Hostel Room: All', $html);
        $this->assertStringContainsString('aria-label="Filter by hostel room"', $html);
    }

    public function test_the_export_filter_line_names_the_room_filter(): void
    {
        $this->assertStringContainsString('Hostel Room: GANG-116', $this->summary(['house' => 'GANG-116']));
    }

    public function test_the_export_filter_line_ignores_array_valued_filters(): void
    {
        $line = $this->summary([
            'cadre' => ['x'],
            'house' => ['x'],
            'house_group' => ['x'],
            'session' => ['x'],
            'course_id' => ['1'],
            'search' => ['value' => ['x']],
        ]);

        $this->assertSame('Status: Active', $line);
    }
}
