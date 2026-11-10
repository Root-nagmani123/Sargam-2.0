<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Services\COE\ResultMarksService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * COE → Result Management → Result Records and Marks Moderation.
 *
 * Every participant's mark per component, with the evaluator the drive maps to
 * them, and the marks moderated per SOP COE/11 (MarksModeration). Moderate Marks
 * (next to Download for the ticked / shown rows, or per row) asks for confirmation,
 * then moderates. Rules: ResultMarksService::moderationSheet() / moderate().
 */
class MarksModerationController extends Controller
{
    public function __construct(private ResultMarksService $results)
    {
    }

    /** ?scope=active|archived */
    public function index(Request $request)
    {
        $scope = $request->query('scope') === 'archived' ? 'archived' : 'active';
        $rows = $this->results->moderationSheet($scope);

        $options = fn (string $field) => collect($rows)->pluck($field)->filter()->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();

        return view('admin.coe.marks_moderation.index', [
            'scope' => $scope,
            'rows' => $rows,
            'courses' => $options('course'),
            'subjects' => $options('subject'),
            'components' => $options('component'),
            'moderationEnabled' => ResultMarksService::moderationEnabled(),
        ]);
    }

    public function moderate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'keys' => ['required', 'array', 'min:1', 'max:5000'],
            'keys.*' => ['string', 'regex:/^\d+:\d+:\d+\|\d+$/'],
        ]);

        $result = $this->results->moderate($request->user(), $data['keys']);
        if ($result['error'] !== null) {
            return response()->json(['message' => $result['error']], 422);
        }

        $count = $result['moderated'] === 1 ? '1 mark' : "{$result['moderated']} marks";

        return response()->json([
            'saved' => $result['saved'],
            'values' => $result['values'],
            'message' => match (true) {
                $result['moderated'] === 0 => 'These marks were already moderated.',
                ! $result['saved'] => "{$count} moderated, but not saved: the moderation columns are not on the marks table yet.",
                default => "{$count} moderated.",
            },
        ]);
    }
}
