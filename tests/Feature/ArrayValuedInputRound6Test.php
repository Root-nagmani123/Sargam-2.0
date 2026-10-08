<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Three more PR-added lines cast or interpolated a request value with no scalar
 * check, so an array-valued parameter raised "Array to string conversion" and the
 * page returned 500 (PR #334 F-051, the F-025 / F-041 family).
 */
class ArrayValuedInputRound6Test extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function assertNotServerError($response): void
    {
        $this->assertLessThan(500, $response->getStatusCode(), 'an array-valued parameter must not cause a 500');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_ot_participants_page_with_an_array_house(): void
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

        $response = $this->as(User::findOrFail($pk), ['Super Admin'])
            ->get(route('admin.dashboard.ot-participants', ['house' => ['x']]));

        $this->assertNotServerError($response);
    }

    public function test_leave_on_behalf_list_with_an_array_search_value(): void
    {
        $response = $this->as($this->userWithRole('Super Admin'), ['Super Admin'])
            ->getJson(route('admin.leave-on-behalf.index', [
                'draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => ['x']],
            ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $this->assertNotServerError($response);
        // Yajra catches the exception and answers 200 with an "error" key, so
        // the status alone does not show the failure.
        $this->assertNull($response->json('error'), (string) $response->json('error'));
    }

    public function test_timetable_report_with_an_array_order_direction(): void
    {
        $response = $this->as($this->userWithRole('Super Admin'), ['Super Admin'])
            ->getJson(route('timetable-report.data', [
                'draw' => 1, 'start' => 0, 'length' => 10,
                'order' => [['column' => 0, 'dir' => ['asc']]],
            ]));

        $this->assertNotServerError($response);
    }
}
