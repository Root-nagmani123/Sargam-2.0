<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Services\COE\DirectorAssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * COE → Examination Management → Evaluation & Marks (Training Section): the
 * Director Assessment. Table view to quick-enter the Director's marks, OT profile
 * to review one OT (faculty marks, notices & memos) and send it to COE.
 * Rules in DirectorAssessmentService.
 */
class DirectorAssessmentController extends Controller
{
    public function __construct(private DirectorAssessmentService $assessment)
    {
    }

    /** ?course=<pk>&drive=<id>&tab=pending|completed */
    public function index(Request $request)
    {
        return view('admin.coe.director_assessment.index', $this->assessment->sheet(
            $request->integer('course') ?: null,
            $request->integer('drive') ?: null,
        ) + [
            'tab' => $this->tab($request),
            'remarksMax' => DirectorAssessmentService::REMARKS_MAX,
        ]);
    }

    /** ?key=<column key>&student=<pk>&course=&drive=&tab= */
    public function review(Request $request)
    {
        $key = (string) $request->query('key', '');
        $review = preg_match('/^\d+:\d+:\d+$/', $key) && $request->integer('student')
            ? $this->assessment->review(
                $key,
                $request->integer('student'),
                $request->integer('course') ?: null,
                $request->integer('drive') ?: null,
                $this->tab($request) === 'completed',
            )
            : null;
        abort_if($review === null, 404);

        return view('admin.coe.director_assessment.review', $review + [
            'tab' => $this->tab($request),
            'filters' => array_filter([
                'course' => $request->integer('course') ?: null,
                'drive' => $request->integer('drive') ?: null,
                'tab' => $this->tab($request) === 'completed' ? 'completed' : null,
            ]),
            'remarksMax' => DirectorAssessmentService::REMARKS_MAX,
        ]);
    }

    /**
     * JSON {marks:{key:{student:value}}, remarks:{key:{student:text}}, send:["key|student"]}.
     * JSON, not form fields: a full table can pass PHP's max_input_vars.
     */
    public function save(Request $request): JsonResponse
    {
        $data = $request->validate([
            'marks' => ['array'],
            'remarks' => ['array'],
            'send' => ['array'],
            'send.*' => ['string', 'max:80'],
        ]);

        $result = $this->assessment->save($request->user(), $data['marks'] ?? [], $data['remarks'] ?? [], $data['send'] ?? []);
        if ($result['errors']) {
            return response()->json([
                'message' => $result['errors']['_'] ?? $result['message'] ?? 'Some entries are not valid. Fix the highlighted ones.',
                'errors' => array_diff_key($result['errors'], ['_' => true]),
            ], 422);
        }

        return response()->json([
            'message' => match (true) {
                $result['sent'] > 0 => $result['sent'] === 1 ? 'Director assessment sent to COE.' : "{$result['sent']} Director assessments sent to COE.",
                $result['saved'] > 0 => 'Saved as draft.',
                default => 'Nothing changed.',
            },
            'saved' => $result['saved'],
            'sent' => $result['sent'],
        ]);
    }

    private function tab(Request $request): string
    {
        return $request->query('tab') === 'completed' ? 'completed' : 'pending';
    }
}
