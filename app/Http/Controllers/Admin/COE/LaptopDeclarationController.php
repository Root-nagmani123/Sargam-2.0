<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Models\COE\LaptopResponse;
use App\Services\COE\LaptopDeclarationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * COE › Examination Centre › Laptop Availability Form — the Officer Trainee's own side.
 *
 * The trainee confirms the four things the Examination Section already knows about them
 * (course, batch, name, roll number), answers whether a laptop is required and whether a
 * scriber is needed, and submits. The answer lands in the same coe_laptop_responses row
 * the COE Admin sees on "OT Laptop Status", stamped SOURCE_TRAINEE.
 *
 * ── It is a one-shot form ──
 * Once a status exists for the trainee — whether they submitted it or the COE Admin
 * recorded it — the form locks and shows what was recorded. A correction goes through the
 * Examination Section. The lock is enforced in store(), not only in the markup, because a
 * disabled field is a suggestion and a stale page is not.
 *
 * Every action resolves the trainee from the SESSION (user_credentials.user_category = 'S',
 * whose user_id is the student_master pk) and writes only against that participant. A
 * student_master_pk in the request is never read, which is what stops one trainee
 * declaring on another's behalf.
 */
class LaptopDeclarationController extends Controller
{
    /** The form, filled with the trainee's own record once one exists. */
    public function index()
    {
        $student = $this->resolveTrainee();
        if (! $student instanceof \stdClass) {
            return $student;
        }

        $declaration = $this->declarationFor($student);

        return view('admin.coe.laptop_declaration.index', [
            'student' => $student,
            'declaration' => $declaration,
            'blocked' => $this->blockedReason($declaration),
        ]);
    }

    /** Submit the trainee's own declaration. */
    public function store(Request $request, LaptopDeclarationService $declarations)
    {
        $student = $this->resolveTrainee();
        if (! $student instanceof \stdClass) {
            return $student;
        }

        // Re-checked here rather than trusted from the page: the form may have been open
        // since before the COE Admin recorded an answer.
        $blocked = $this->blockedReason($this->declarationFor($student));
        if ($blocked !== null) {
            return back()->with('error', $blocked);
        }

        $rules = $declarations->rules();
        // Additional Remarks is the COE Admin's field; it is not on this form, so it is
        // not accepted from it either.
        unset($rules['additional_remarks']);

        $validated = $request->validate($rules, $declarations->messages());

        // Disability Type is not asked here — the COE Admin sets it in Scribers Details.
        $declarations->record((int) $student->pk, $validated, LaptopResponse::SOURCE_TRAINEE);

        return redirect()
            ->route('admin.coe.laptop-declaration.index')
            ->with('success', 'Your laptop availability has been submitted.');
    }

    /**
     * Why the form cannot be filled in, in words the trainee can act on; null means go ahead.
     */
    private function blockedReason(?LaptopResponse $declaration): ?string
    {
        if ($declaration === null) {
            return null;
        }

        return (int) $declaration->source === LaptopResponse::SOURCE_TRAINEE
            ? 'You have already submitted your laptop availability. To change it, please contact the Examination Section.'
            : 'The Examination Section has already recorded your laptop availability. To change it, please contact them directly.';
    }

    /**
     * The logged-in Officer Trainee's own details — the four read-only fields the form
     * shows, resolved in one query.
     *
     * @return \stdClass|\Illuminate\Http\RedirectResponse
     */
    private function resolveTrainee()
    {
        $student = DB::table('user_credentials as uc')
            ->join('student_master as sm', 'sm.pk', '=', 'uc.user_id')
            ->leftJoin('course_master as c', 'c.pk', '=', 'sm.course_master_pk')
            ->leftJoin('coe_roll_numbers as rn', 'rn.student_master_pk', '=', 'sm.pk')
            ->where('uc.pk', Auth::id())
            ->where('uc.user_category', 'S')
            ->select([
                'sm.pk',
                'sm.display_name',
                'sm.first_name',
                'sm.middle_name',
                'sm.last_name',
                'sm.generated_OT_code',
                'sm.batch',
                'c.course_name',
                'c.couse_short_name',
                'rn.roll_number',
            ])
            ->first();

        if ($student === null) {
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'This page is only for Officer Trainees.');
        }

        return $student;
    }

    /** The trainee's answer, or null while nobody has recorded one. */
    private function declarationFor(\stdClass $student): ?LaptopResponse
    {
        return LaptopResponse::where('student_master_pk', $student->pk)->first();
    }
}
