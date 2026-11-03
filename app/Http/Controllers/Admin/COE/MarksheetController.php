<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Admin\COE\Concerns\SampleResultData;
use App\Http\Controllers\Controller;
use App\Models\COE\SignatureMaster;
use Illuminate\Http\Request;

/**
 * COE → Document Management → Marksheet Generation / Verified Marksheet.
 *
 * Design-first (see SampleResultData): marksheets are built from the sample
 * results, and Verify & Publish is not saved anywhere yet. The signatures at the
 * foot of a marksheet are real — they come from Signature Master.
 */
class MarksheetController extends Controller
{
    use SampleResultData;

    /** Signature Master designation => the caption it signs under. */
    private const SIGNATORIES = [
        'UDC' => 'Prepared By',
        'AAO' => 'Compared By',
        'COE' => 'Controller of Examination',
    ];

    public function generation(Request $request)
    {
        return $this->listing($request, 'admin.coe.marksheet.generation', fn ($m) => true);
    }

    public function verified(Request $request)
    {
        return $this->listing($request, 'admin.coe.marksheet.verified', fn ($m) => $m['published']);
    }

    /** Verify Document: the marksheets ticked on Marksheet Generation (?course=…&ots[]=…). */
    public function verify(Request $request)
    {
        $courseKey = (string) $request->query('course');
        $codes = array_filter((array) $request->query('ots', []), 'is_string');
        abort_unless(isset($this->resultCourses()[$courseKey]) && $codes !== [], 404);

        $rows = array_values(array_filter($this->marksheets($courseKey), fn ($m) => in_array($m['code'], $codes, true)));
        abort_if($rows === [], 404);

        return view('admin.coe.marksheet.verify', ['rows' => $rows]);
    }

    /** View Marksheet — ?verify=1 when opened from "View & Verify". */
    public function show(Request $request, string $course, string $ot)
    {
        $marksheet = $this->findMarksheet($course, $ot);

        $signatures = SignatureMaster::query()
            ->where('active_inactive', 1)
            ->whereIn('designation_name', array_keys(self::SIGNATORIES))
            ->get()
            ->keyBy('designation_name');

        return view('admin.coe.marksheet.show', [
            'm' => $marksheet,
            'subjects' => $this->resultSubjects(),
            'signatories' => self::SIGNATORIES,
            'signatures' => $signatures,
            'verifying' => $request->boolean('verify'),
        ]);
    }

    private function listing(Request $request, string $view, callable $keep)
    {
        $scope = $request->query('scope') === 'archived' ? 'archived' : 'active';
        $courses = $this->scopedCourses($scope);

        $rows = [];
        foreach (array_keys($courses) as $key) {
            array_push($rows, ...array_filter($this->marksheets($key), $keep));
        }

        return view($view, ['scope' => $scope, 'courses' => $courses, 'rows' => $rows]);
    }
}
