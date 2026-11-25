<?php

namespace App\Http\Controllers\Admin\COE;

use App\Http\Controllers\Controller;
use App\Models\ExaminationDrive;
use App\Services\COE\ReexamService;
use App\Services\COE\ResultMarksException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * COE → Examination Drive Configuration → Re-exam (button + Create Re-exam popup).
 * Rules in ReexamService. Routes need coe_examination_drive or coe_coe_examination_drive
 * (Super Admin always passes).
 */
class ReexamController extends Controller
{
    public function __construct(private ReexamService $reexams)
    {
    }

    /**
     * Which of the listed drives show the Re-exam button. The drive list asks once per
     * page after it draws, so the list itself loads no marks.
     */
    public function ready(Request $request): JsonResponse
    {
        $ids = collect((array) $request->query('ids', []))->map(fn ($id) => (int) $id)->filter()->unique()->take(100)->values();
        if ($ids->isEmpty() || ! ReexamService::enabled()) {
            return response()->json(['ready' => []]);
        }
        $drives = ExaminationDrive::with(['examinationType', 'componentMaps.subject'])->whereIn('id', $ids)->get();
        $ready = array_keys(array_filter($this->reexams->readyMap($drives)));

        return response()->json(['ready' => array_values($ready)]);
    }

    /** The Create Re-exam list: OTs who failed or were absent, per subject. */
    public function show(int $id): JsonResponse
    {
        $drive = ExaminationDrive::with(['examinationType', 'componentMaps.subject'])->findOrFail($id);
        $sheet = $this->reexams->sheet($drive);

        return response()->json($sheet + [
            'drive' => filled($drive->drive_name) ? trim($drive->drive_name) : 'Examination Drive #'.$drive->id,
            'default_name' => $sheet['next_no'] ? ReexamService::defaultName($drive, $sheet['next_no']) : '',
        ]);
    }

    /** Re-exam Result (step 6): Pass / Fail per OT and subject, on the re-exam's own marks. */
    public function result(int $id): JsonResponse
    {
        $drive = ExaminationDrive::with('componentMaps.subject')->findOrFail($id);
        $result = $this->reexams->result($drive);
        $result['ots'] = array_values($result['ots']);

        return response()->json($result);
    }

    /** Edit Re-exam: only the name and the re-exam dates, before the CC has it (ReexamService::update). */
    public function update(Request $request, int $id): JsonResponse
    {
        $drive = ExaminationDrive::findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ], [
            'name.required' => 'Enter the re-exam name.',
            'start_date.required' => 'Select the re-exam start date.',
            'end_date.required' => 'Select the re-exam end date.',
            'end_date.after_or_equal' => 'The re-exam cannot end before it starts.',
        ]);

        try {
            $drive = $this->reexams->update($drive, $data['name'], $data['start_date'], $data['end_date'], $request->user());
        } catch (ResultMarksException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['id' => $drive->id, 'message' => "{$drive->drive_name} updated."]);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $drive = ExaminationDrive::findOrFail($id);
        $data = $request->validate([
            'keys' => ['required', 'array', 'min:1'],
            'keys.*' => ['required', 'string', 'regex:/^\d+:-?\d+$/'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'name' => ['nullable', 'string', 'max:150'],
        ], [
            'keys.required' => 'Tick at least one OT for the re-exam.',
            'keys.min' => 'Tick at least one OT for the re-exam.',
            'start_date.required' => 'Select the re-exam start date.',
            'end_date.required' => 'Select the re-exam end date.',
            'end_date.after_or_equal' => 'The re-exam cannot end before it starts.',
        ]);

        try {
            $result = $this->reexams->create($drive, $request->user(), $data['keys'], $data['start_date'], $data['end_date'], $data['name'] ?? null);
        } catch (ResultMarksException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'id' => $result['drive']->id,
            'message' => trim("{$result['drive']->drive_name} created for ".count($data['keys']).' OT-subject(s). '.$result['notice']),
        ]);
    }
}
