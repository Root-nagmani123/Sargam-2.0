<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Services\COE\MarksheetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * COE → Document Management → Marksheet Generation / Verified Marksheet.
 *
 * Marksheets of the OTs ranked in Merit & Ranking; Verify & Publish needs the
 * UDC, AAO and COE signatures in Signature Master. Rules: MarksheetService.
 */
class MarksheetController extends Controller
{
    public function __construct(private MarksheetService $marksheets)
    {
    }

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
        abort_if($codes === [], 404);

        $rows = array_values(array_filter($this->marksheets->courseRows($courseKey), fn ($m) => in_array($m['code'], $codes, true)));
        abort_if($rows === [], 404);

        return view('admin.coe.marksheet.verify', ['rows' => $rows, 'courseKey' => $courseKey]);
    }

    /** Verify & Publish (one, the ticked ones, or all on Verify Document). JSON. */
    public function publish(Request $request): JsonResponse
    {
        $data = $request->validate([
            'course' => ['required', 'string', 'regex:/^\d+$/'],
            'ots' => ['required', 'array', 'min:1'],
            'ots.*' => ['required', 'string', 'regex:/^\d+$/'],
        ]);

        $result = $this->marksheets->publish($request->user(), $data['course'], array_values(array_unique($data['ots'])));
        if ($result['error']) {
            return response()->json(['message' => $result['error']], 422);
        }

        $n = $result['published'];

        return response()->json([
            'message' => match (true) {
                $n === 0 => 'These marksheets were already published.',
                $n === 1 => '1 marksheet verified and published.',
                default => "{$n} marksheets verified and published.",
            },
            'published' => $n,
        ]);
    }

    /** View Marksheet — ?verify=1 when opened from "View & Verify". */
    public function show(Request $request, string $course, string $ot)
    {
        $marksheet = $this->marksheets->find($course, $ot);
        abort_if($marksheet === null, 404);

        return view('admin.coe.marksheet.show', [
            'm' => $marksheet,
            'subjects' => $marksheet['subjects'],
            'signatories' => MarksheetService::SIGNATORIES,
            'signatures' => $this->marksheets->signaturesFor($marksheet),
            'verifying' => $request->boolean('verify'),
        ]);
    }

    private function listing(Request $request, string $view, callable $keep)
    {
        $scope = $request->query('scope') === 'archived' ? 'archived' : 'active';
        $rows = array_values(array_filter($this->marksheets->rows($scope), $keep));

        return view($view, ['scope' => $scope, 'courses' => MarksheetService::courses($rows), 'rows' => $rows]);
    }
}
