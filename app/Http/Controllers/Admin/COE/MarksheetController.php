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

    /**
     * Verify Document. ?course=…&ots[]=… — the marksheets ticked (Verify, Verify Selected);
     * ?course=…&first=… — View & Verify: every marksheet of the course the user may still verify, the clicked one first.
     */
    public function verify(Request $request)
    {
        $courseKey = (string) $request->query('course');
        $rows = $this->withCanVerify($request, $this->marksheets->courseRows($courseKey));

        if ($request->filled('first')) {
            $first = (string) $request->query('first');
            $rows = array_values(array_filter($rows, fn ($m) => $m['can_verify']));
            usort($rows, fn ($a, $b) => ($b['code'] === $first) <=> ($a['code'] === $first));
        } else {
            $codes = array_filter((array) $request->query('ots', []), 'is_string');
            $rows = array_values(array_filter($rows, fn ($m) => in_array($m['code'], $codes, true)));
        }
        abort_if($rows === [], 404);

        return view('admin.coe.marksheet.verify', ['rows' => $rows, 'courseKey' => $courseKey]);
    }

    /**
     * Verify (one, the ticked ones, or all on Verify Document). JSON. The UDC, AAO
     * or COE puts their signature on the marksheets; the COE's publishes them.
     */
    public function publish(Request $request): JsonResponse
    {
        $data = $request->validate([
            'course' => ['required', 'string', 'regex:/^\d+$/'],
            'ots' => ['required', 'array', 'min:1'],
            'ots.*' => ['required', 'string', 'regex:/^\d+$/'],
        ]);

        $result = $this->marksheets->verify($request->user(), $data['course'], array_values(array_unique($data['ots'])));
        if ($result['error']) {
            return response()->json(['message' => $result['error']], $result['as'] === null ? 403 : 422);
        }

        $sheets = fn (int $n) => $n === 1 ? '1 marksheet' : "{$n} marksheets";

        return response()->json([
            'message' => match (true) {
                $result['published'] > 0 => $sheets($result['published']).' signed and published.',
                $result['signed'] > 0 => "Your {$result['as']} signature was added to ".$sheets($result['signed']).'. It is published once the COE verifies it.',
                $result['as'] === 'COE' => 'These marksheets were already published.',
                default => "You have already signed these marksheets as {$result['as']}.",
            },
            'signed' => $result['signed'],
            'published' => $result['published'],
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
        $rows = $this->withCanVerify($request, array_values(array_filter($this->marksheets->rows($scope), $keep)));

        return view($view, ['scope' => $scope, 'courses' => MarksheetService::courses($rows), 'rows' => $rows]);
    }

    /**
     * `can_verify` on each row: not published, and not signed yet by the logged-in
     * user's designation (a UDC who verified it does not see Verify again).
     */
    private function withCanVerify(Request $request, array $rows): array
    {
        $as = MarksheetService::signerRole($request->user());

        return array_map(fn ($m) => $m + [
            'can_verify' => ! $m['published'] && ($as === null || ! isset($m['signatures'][$as])),
        ], $rows);
    }
}
