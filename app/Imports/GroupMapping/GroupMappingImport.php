<?php

namespace App\Imports\GroupMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{ToCollection, WithHeadingRow, WithStartRow};
use App\Models\{StudentMaster, CourseGroupTypeMaster, GroupTypeMasterCourseMasterMap, StudentCourseGroupMap, StudentMasterCourseMap};

class GroupMappingImport implements ToCollection, WithHeadingRow, WithStartRow
{
    public $failures = [];
    public $courseType;

    public function __construct($courseType)
    {
        $this->courseType = $courseType;
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $collection)
    {
        $dataToInsert = [];
        $processedMappings = [];

        foreach ($collection as $index => $row) {

            $rowNumber = $index + 2;
            $data = array_map('trim', $row->toArray());

            $validator = Validator::make($data, [
                'name'       => 'required|string|max:255',
                'otcode'     => 'required|string|max:255',
                'group_name' => 'required|string|max:255',
                'group_type' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                $this->addFailure($rowNumber, $validator->errors()->all());
                continue;
            }

            /** ---------------- Student Lookup ---------------- */
            $students = StudentMaster::whereRaw(
                'LOWER(generated_OT_code) = ?',
                [strtolower($data['otcode'])]
            )->get();

            $studentMaster = null;

            foreach ($students as $student) {
                if (
                    StudentMasterCourseMap::where('student_master_pk', $student->pk)
                        ->where('active_inactive', 1)
                        ->exists()
                ) {
                    $studentMaster = $student;
                    break;
                }
            }

            if (!$studentMaster) {
                $this->addFailure($rowNumber, [
                    "Active student not found for OT code: {$data['otcode']}"
                ]);
                continue;
            }

            /** ---------------- Group Mapping ---------------- */
            $groupMap = GroupTypeMasterCourseMasterMap::whereRaw(
                'LOWER(group_name) = ?',
                [strtolower($data['group_name'])]
            )->first();

            if (!$groupMap) {
                $this->addFailure($rowNumber, [
                    "Group map not found for group name: {$data['group_name']}"
                ]);
                continue;
            }

            /** ---------------- Course Group Type ---------------- */
            $courseGroupType = CourseGroupTypeMaster::find($groupMap->type_name);

            if (!$courseGroupType) {
                $this->addFailure($rowNumber, [
                    "Course group type not found for ID: {$groupMap->type_name}"
                ]);
                continue;
            }

            /** ---------------- Duplicate Check (Sheet) ---------------- */
            $mappingKey = "{$studentMaster->pk}|{$groupMap->pk}";
            if (isset($processedMappings[$mappingKey])) {
                $this->addFailure($rowNumber, [
                    "Duplicate row for OT '{$data['otcode']}' and group '{$data['group_name']}'"
                ]);
                continue;
            }

            /** ---------------- Duplicate Check (DB) ---------------- */
            if (
                StudentCourseGroupMap::where('student_master_pk', $studentMaster->pk)
                    ->where('group_type_master_course_master_map_pk', $groupMap->pk)
                    ->exists()
            ) {
                $this->addFailure($rowNumber, [
                    "Mapping already exists for OT '{$data['otcode']}' and group '{$data['group_name']}'"
                ]);
                continue;
            }

            /** ---------------- Course Mapping Validation ---------------- */
            $existingCourseMap = DB::table('group_type_master_course_master_map')
                ->whereRaw('LOWER(group_name) = ?', [strtolower($data['group_name'])])
                ->whereRaw('LOWER(type_name) = ?', [strtolower($data['group_type'])])
                ->first();

            if (!$existingCourseMap ||
                strcasecmp($existingCourseMap->course_name, $this->courseType) !== 0
            ){
                $this->addFailure($rowNumber, [
                    "Course mismatch for group '{$data['group_name']}' and type '{$data['group_type']}' and course '{$existingCourseMap->course_name}'"
                ]);
                continue;
            }

            $processedMappings[$mappingKey] = true;

            $dataToInsert[] = [
                'student_master_pk'                      => $studentMaster->pk,
                'group_type_master_course_master_map_pk' => $groupMap->pk,
                'active_inactive'                        => 1,
                'created_date'                           => now(),
                'modified_date'                          => now(),
            ];
        }

        if (empty($this->failures) && !empty($dataToInsert)) {
            StudentCourseGroupMap::insert($dataToInsert);
        }
    }

    private function addFailure($rowNumber, array $errors)
    {
        $this->failures[] = [
            'row'    => $rowNumber,
            'errors' => $errors,
        ];
    }
}
