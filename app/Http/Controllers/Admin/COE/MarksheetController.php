<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Services\COE\MarksheetService;
use App\Services\COE\ReexamMarksheetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * COE → Document Management → Marksheet Generation / Verified Marksheet.
 *
 * Marksheets of the OTs ranked in Merit & Ranking, one per OT per examination drive
 * (a "sheet": the drive id, or "c<course>" for a legacy course-wise one); Verify &
 * Publish needs the UDC, AAO and COE signatures in Signature Master. Rules: MarksheetService.
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
     * Verify Document. ?sheet=…&ots[]=… — the marksheets ticked (Verify, Verify Selected);
     * ?sheet=…&first=… — View & Verify: every marksheet of the drive the user may still verify, the clicked one first.
     */
    public function verify(Request $request)
    {
        $sheetKey = (string) $request->query('sheet');
        $rows = $this->withCanVerify($request, $this->marksheets->sheetRows($sheetKey));

        if ($request->filled('first')) {
            $first = (string) $request->query('first');
            $rows = array_values(array_filter($rows, fn ($m) => $m['can_verify']));
            usort($rows, fn ($a, $b) => ($b['code'] === $first) <=> ($a['code'] === $first));
        } else {
            $codes = array_filter((array) $request->query('ots', []), 'is_string');
            $rows = array_values(array_filter($rows, fn ($m) => in_array($m['code'], $codes, true)));
        }
        abort_if($rows === [], 404);

        return view('admin.coe.marksheet.verify', ['rows' => $rows, 'sheetKey' => $sheetKey]);
    }

    /**
     * Verify (one, the ticked ones, or all on Verify Document). JSON. The UDC, AAO
     * or COE puts their signature on the marksheets; the COE's publishes them.
     */
    public function publish(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sheet' => ['required', 'string', 'regex:/^c?\d+$/'],
            'ots' => ['required', 'array', 'min:1'],
            'ots.*' => ['required', 'string', 'regex:/^\d+$/'],
        ]);

        $result = $this->marksheets->verify($request->user(), $data['sheet'], array_values(array_unique($data['ots'])));
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
    public function show(Request $request, string $sheet, string $ot)
    {
        $marksheet = $this->marksheets->find($sheet, $ot);
        abort_if($marksheet === null, 404);

        return view('admin.coe.marksheet.show', [
            'm' => $marksheet,
            'subjects' => $marksheet['subjects'],
            'signatories' => MarksheetService::SIGNATORIES,
            'signatures' => $this->marksheets->signaturesFor($marksheet),
            'verifying' => $request->boolean('verify'),
        ]);
    }

    /**
     * Main and re-exam marksheets in one list (Figma). A re-exam marksheet (Pass / Fail only,
     * ReexamMarksheetService; `reexam_key` set, null on main rows) sits right after the OT's
     * marksheet of its main drive, else at the end; its tab follows its re-exam drive.
     * The Re-exam tab (ReexamMarksheetController) still lists them on their own.
     */
    private function listing(Request $request, string $view, callable $keep)
    {
        $scope = $request->query('scope') === 'archived' ? 'archived' : 'active';
        $rows = array_map(fn ($m) => $m + ['reexam_key' => null], array_values(array_filter($this->marksheets->rows($scope), $keep)));
        foreach (array_filter(app(ReexamMarksheetService::class)->scopeRows($scope), $keep) as $rx) {
            $at = null;
            foreach ($rows as $i => $m) {
                if ($m['sheet_key'] === ($rx['sheet_key'] ?? null) && $m['code'] === $rx['code']) {
                    $at = $i;
                }
            }
            array_splice($rows, $at === null ? count($rows) : $at + 1, 0, [$rx]);
        }
        $rows = $this->withCanVerify($request, $rows);

        return view($view, ['scope' => $scope, 'courses' => MarksheetService::courses($rows), 'rows' => $rows]);
    }

    /**
     * `can_verify` on each row: not published, not signed yet by the logged-in
     * user's designation (a UDC who verified it does not see Verify again), and
     * signed by the one before it (the AAO sees Verify only after the UDC, the COE
     * only after the AAO).
     */
    private function withCanVerify(Request $request, array $rows): array
    {
        $as = MarksheetService::signerRole($request->user());
        $before = $as === null ? null : MarksheetService::signsBefore($as);

        return array_map(fn ($m) => $m + [
            'can_verify' => ! $m['published'] && ($as === null || (! isset($m['signatures'][$as])
                && ($before === null || isset($m['signatures'][$before])))),
        ], $rows);
    }
}
