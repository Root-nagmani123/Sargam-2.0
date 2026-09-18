<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComponentMaster;
use App\Models\ExaminationDrive;
use App\Models\ExaminationDriveComponentMap;
use App\Models\SubjectMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExaminationDriveComponentMapController extends Controller
{
    public function show($driveId)
    {
        $drive = ExaminationDrive::with(['examinationType', 'term', 'course'])->findOrFail(decrypt($driveId));

        $subjects = SubjectMaster::where('active_inactive', 1)->orderBy('subject_name')->pluck('subject_name', 'pk');
        $components = ComponentMaster::active()->orderBy('component_name')->pluck('component_name', 'pk');
        $sources = ExaminationDriveComponentMap::SOURCES;

        $rows = ExaminationDriveComponentMap::where('examination_drive_id', $drive->id)
            ->get(['subject_master_pk', 'component_master_pk', 'max_marks', 'passing_marks', 'source', 'weightage'])
            ->values();

        return view('admin.examination_drive.components', compact('drive', 'subjects', 'components', 'sources', 'rows'));
    }

    public function store(Request $request, $driveId)
    {
        $drive = ExaminationDrive::findOrFail(decrypt($driveId));

        $data = $request->validate([
            'rows'                        => 'required|array|min:1',
            'rows.*.subject_master_pk'    => ['required', Rule::exists('subject_master', 'pk')],
            'rows.*.component_master_pk'  => ['required', Rule::exists('component_master', 'pk')],
            'rows.*.max_marks'            => 'required|numeric|min:0|max:9999.99',
            'rows.*.passing_marks'        => 'required|numeric|min:0|max:9999.99',
            'rows.*.source'               => ['required', Rule::in(ExaminationDriveComponentMap::SOURCES)],
            'rows.*.weightage'            => 'required|numeric|min:0.01|max:100',
        ]);

        foreach ($data['rows'] as $i => $row) {
            if ((float) $row['passing_marks'] > (float) $row['max_marks']) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Row ' . ($i + 1) . ': Passing marks cannot exceed max marks.',
                ], 422);
            }
        }

        $totalWeightage = collect($data['rows'])->sum(fn ($row) => (float) $row['weightage']);
        if (round($totalWeightage, 2) !== 100.00) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Total weightage must equal 100%. Current total: ' . round($totalWeightage, 2) . '%.',
            ], 422);
        }

        DB::transaction(function () use ($drive, $data) {
            ExaminationDriveComponentMap::where('examination_drive_id', $drive->id)->delete();

            foreach ($data['rows'] as $row) {
                ExaminationDriveComponentMap::create([
                    'examination_drive_id' => $drive->id,
                    'subject_master_pk'    => $row['subject_master_pk'],
                    'component_master_pk'  => $row['component_master_pk'],
                    'max_marks'            => $row['max_marks'],
                    'passing_marks'        => $row['passing_marks'],
                    'source'               => $row['source'],
                    'weightage'            => $row['weightage'],
                ]);
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Subject & component mapping saved successfully.']);
    }
}
