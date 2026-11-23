<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Services\COE\MarksheetService;
use App\Services\COE\ReexamMarksheetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * COE → Document Management → Marksheet Generation / Verified Marksheet → **Re-exam** tab.
 * Re-exam marksheets show only Pass / Fail; signed UDC → AAO → COE as the main marksheet.
 * Rules: ReexamMarksheetService. The main marksheet (MarksheetController) is unchanged.
 */
class ReexamMarksheetController extends Controller
{
    public function __construct(private ReexamMarksheetService $marksheets)
    {
    }

    public function generation(Request $request)
    {
        return $this->listing($request, false);
    }

    public function verified(Request $request)
    {
        return $this->listing($request, true);
    }

    /** Verify (one or the ticked ones of one re-exam). JSON. The COE's signature publishes. */
    public function publish(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reexam' => ['required', 'string', 'regex:/^\d+$/'],
            'ots' => ['required', 'array', 'min:1'],
            'ots.*' => ['required', 'string', 'regex:/^\d+$/'],
        ]);

        $result = $this->marksheets->verify($request->user(), $data['reexam'], array_values(array_unique($data['ots'])));
        if ($result['error']) {
            return response()->json(['message' => $result['error']], $result['as'] === null ? 403 : 422);
        }

        $sheets = fn (int $n) => $n === 1 ? '1 re-exam marksheet' : "{$n} re-exam marksheets";

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

    /** View Re-exam Marksheet — ?verify=1 shows Verify & Publish. */
    public function show(Request $request, string $reexam, string $ot)
    {
        $marksheet = $this->marksheets->find($reexam, $ot);
        abort_if($marksheet === null, 404);
        $marksheet = $this->withCanVerify($request, [$marksheet])[0];

        return view('admin.coe.marksheet.reexam_show', [
            'm' => $marksheet,
            'signatories' => MarksheetService::SIGNATORIES,
            'signatures' => app(MarksheetService::class)->signaturesFor($marksheet),
            'verifying' => $request->boolean('verify') && $marksheet['can_verify'],
        ]);
    }

    private function listing(Request $request, bool $verifiedOnly)
    {
        $rows = array_values(array_filter($this->marksheets->rows(), fn ($m) => ! $verifiedOnly || $m['published']));
        $rows = $this->withCanVerify($request, $rows);

        return view('admin.coe.marksheet.reexam_index', [
            'rows' => $rows,
            'verifiedOnly' => $verifiedOnly,
            'courses' => MarksheetService::courses($rows),
            'enabled' => ReexamMarksheetService::enabled(),
        ]);
    }

    /** As MarksheetController::withCanVerify: not published, not signed by this designation, signed by the one before. */
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
