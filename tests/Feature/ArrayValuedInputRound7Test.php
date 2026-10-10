<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\RollsBackAgainstAppDatabase;
use Tests\TestCase;

/**
 * Inputs beside the ones F-037 / F-051 guarded, in the same PR-edited methods, still
 * cast or interpolated an array-valued parameter, so ?x[]= returned 500 (PR #334
 * F-072). Each case pairs the array input with the same parameter as a plain string.
 */
class ArrayValuedInputRound7Test extends TestCase
{
    use RollsBackAgainstAppDatabase;

    private function assertNotServerError($response, string $what): void
    {
        $this->assertLessThan(500, $response->getStatusCode(), "{$what}: an array-valued parameter must not cause a 500");
        $this->assertSame(200, $response->getStatusCode(), $what);
    }

    /** A Super Admin who is not also an Officer Trainee (canUseOtParticipants() refuses those). */
    private function staffSuperAdmin(): User
    {
        $ot = DB::table('roles')->whereIn('name', ['Officer Trainee', 'Student-OT'])->pluck('id')->all();
        $pk = DB::table('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')
            ->where('r.name', 'Super Admin')->where('m.model_type', User::class)
            ->whereNotIn('m.model_id', DB::table('model_has_roles')->whereIn('role_id', $ot ?: [-1])->select('model_id'))
            ->orderBy('m.model_id')->value('m.model_id');
        if (! $pk) {
            $this->markTestSkipped('no Super Admin without an Officer Trainee role');
        }

        return User::findOrFail($pk);
    }

    public function test_ot_participants_with_an_array_house_faculty(): void
    {
        $admin = $this->staffSuperAdmin();

        foreach ([['x'], 'x'] as $value) {
            $label = 'house_faculty='.json_encode($value);
            $this->assertNotServerError(
                $this->as($admin, ['Super Admin'])->get(route('admin.dashboard.ot-participants', ['house_faculty' => $value])),
                "page {$label}"
            );
            $this->assertNotServerError(
                $this->as($admin, ['Super Admin'])->getJson(
                    route('admin.dashboard.ot-participants', ['house_faculty' => $value]),
                    ['X-Requested-With' => 'XMLHttpRequest']
                ),
                "ajax {$label}"
            );
        }
    }

    public function test_timetable_report_with_array_text_filters(): void
    {
        $admin = $this->staffWithRole('Super Admin');
        $grid = ['draw' => 1, 'start' => 0, 'length' => 10];

        foreach (['subject_topic', 'module_name', 'visible_columns'] as $field) {
            foreach ([['x'], 'x'] as $value) {
                $label = "{$field}=".json_encode($value);
                $response = $this->as($admin, ['Super Admin'])
                    ->getJson(route('timetable-report.data', $grid + [$field => $value]));
                $this->assertNotServerError($response, "data {$label}");
                $this->assertNull($response->json('error'), "data {$label}: ".(string) $response->json('error'));

                $this->assertNotServerError(
                    $this->as($admin, ['Super Admin'])->get(route('timetable-report.excel', [$field => $value])),
                    "excel {$label}"
                );
            }
        }

        $this->assertNotServerError(
            $this->as($admin, ['Super Admin'])->getJson(route('timetable-report.data', $grid + ['search' => ['value' => ['x']]])),
            'data search[value][]'
        );
    }

    public function test_faculty_feedback_view_export_and_print_with_an_array_faculty_name(): void
    {
        $admin = $this->staffWithRole('Super Admin');

        foreach ([['x'], 'x'] as $value) {
            $label = 'faculty_name='.json_encode($value);
            $this->assertNotServerError(
                $this->as($admin, ['Super Admin'])->post(route('admin.feedback.faculty_view'), ['faculty_name' => $value]),
                "view {$label}"
            );
            $this->assertNotServerError(
                $this->as($admin, ['Super Admin'])->post(route('admin.feedback.faculty_view.export'), [
                    'faculty_name' => $value, 'export_format' => 'excel',
                ]),
                "export {$label}"
            );
            $this->assertNotServerError(
                $this->as($admin, ['Super Admin'])->get(route('admin.feedback.faculty_view.print', ['faculty_name' => $value])),
                "print {$label}"
            );
        }
    }
}
