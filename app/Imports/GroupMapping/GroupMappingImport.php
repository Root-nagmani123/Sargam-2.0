<?php

namespace App\Imports\GroupMapping;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{ToCollection, WithHeadingRow, WithStartRow};
use App\Models\{StudentMaster, CourseGroupTypeMaster, GroupTypeMasterCourseMasterMap, StudentCourseGroupMap};

class GroupMappingImport implements ToCollection, WithHeadingRow, WithStartRow
{
    public $failures = [];

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

            // Validate Excel row
            $validator = Validator::make($data, [
                'name'        => 'required|string|max:255',
                'otcode'      => 'required|string|max:255',
                'group_name'  => 'required|string|max:255',
                'group_type'  => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                $this->addFailure($rowNumber, $validator->errors()->all());
                continue;
            }

            // Lookup: StudentMaster
            $studentMaster = StudentMaster::whereRaw('LOWER(generated_OT_code) = ?', [strtolower($data['otcode'])])
                ->select('pk')->first();

            if (!$studentMaster) {
                $this->addFailure($rowNumber, ["Student not found for OT code: {$data['otcode']}"]);
                continue;
            }

            // Lookup: GroupTypeMasterCourseMasterMap (lowercase match)
            $groupMap = GroupTypeMasterCourseMasterMap::whereRaw('LOWER(group_name) = ?', [strtolower($data['group_name'])])->first();

            if (!$groupMap) {
                $this->addFailure($rowNumber, ["Group map not found for group name: {$data['group_name']}"]);
                continue;
            }

            // Lookup: CourseGroupTypeMaster
            $courseGroupType = CourseGroupTypeMaster::where('pk', $groupMap->type_name)->first();

            if (!$courseGroupType) {
                $this->addFailure($rowNumber, ["Course group type not found for type name ID: {$groupMap->type_name}"]);
                continue;
            }

            // Compare group type (case-insensitive)
            if (strcasecmp($courseGroupType->type_name, $data['group_type']) !== 0) {
                $this->addFailure($rowNumber, [
                    "Group type mismatch: expected '{$courseGroupType->type_name}', got '{$data['group_type']}' for group '{$data['group_name']}'"
                ]);
                continue;
            }

            $mappingKey = "{$studentMaster->pk}|{$groupMap->pk}";

            if (isset($processedMappings[$mappingKey])) {
                $this->addFailure($rowNumber, [
                    "Duplicate row detected for student '{$data['otcode']}' and group '{$data['group_name']}' within the sheet"
                ]);
                continue;
            }

            $existingMapping = StudentCourseGroupMap::where('student_master_pk', $studentMaster->pk)
                ->where('group_type_master_course_master_map_pk', $groupMap->pk)
                ->exists();

            if ($existingMapping) {
                $this->addFailure($rowNumber, [
                    "Mapping already exists for student '{$data['otcode']}' and group '{$data['group_name']}'"
                ]);
                continue;
            }

            $processedMappings[$mappingKey] = true;

            // Passed all checks → prepare insert
            $dataToInsert[] = [
                'student_master_pk'                      => $studentMaster->pk,
                'group_type_master_course_master_map_pk' => $groupMap->pk,
                'active_inactive'                        => 1,
                'created_date'                           => now(),
                'modified_date'                          => now(),
            ];
        }

        // Bulk insert if no failures
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
