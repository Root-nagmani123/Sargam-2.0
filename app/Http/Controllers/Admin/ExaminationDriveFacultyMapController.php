<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExaminationDrive;
use App\Models\ExaminationDriveComponentMap;
use App\Models\ExaminationDriveFacultyMap;
use App\Models\FacultyMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExaminationDriveFacultyMapController extends Controller
{
    public function show($driveId)
    {
        $drive = ExaminationDrive::with(['examinationType', 'term', 'course'])->findOrFail(decrypt($driveId));

        // Only subjects actually mapped to this drive (via Subject & Component Mapping) need a faculty.
        $subjects = ExaminationDriveComponentMap::with('subject')
            ->where('examination_drive_id', $drive->id)
            ->get()
            ->pluck('subject', 'subject_master_pk')
            ->unique('pk');

        $facultyOptions = FacultyMaster::where('active_inactive', 1)->orderBy('full_name')->pluck('full_name', 'pk');

        $existing = ExaminationDriveFacultyMap::where('examination_drive_id', $drive->id)
            ->pluck('faculty_master_pk', 'subject_master_pk');

        return view('admin.examination_drive.faculty_mapping', compact('drive', 'subjects', 'facultyOptions', 'existing'));
    }

    public function store(Request $request, $driveId)
    {
        $drive = ExaminationDrive::findOrFail(decrypt($driveId));

        $data = $request->validate([
            'rows'                       => 'required|array|min:1',
            'rows.*.subject_master_pk'   => ['required', Rule::exists('subject_master', 'pk')],
            'rows.*.faculty_master_pk'   => ['required', Rule::exists('faculty_master', 'pk')],
        ]);

        DB::transaction(function () use ($drive, $data) {
            foreach ($data['rows'] as $row) {
                ExaminationDriveFacultyMap::updateOrCreate(
                    [
                        'examination_drive_id' => $drive->id,
                        'subject_master_pk'    => $row['subject_master_pk'],
                    ],
                    ['faculty_master_pk' => $row['faculty_master_pk']]
                );
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Faculty mapping saved successfully.']);
    }
}
