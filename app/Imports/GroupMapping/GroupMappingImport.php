<?php

namespace App\Imports\GroupMapping;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use App\Models\StudentMaster;
use App\Models\StudentCourseGroupMap;

class GroupMappingImport implements ToCollection, WithHeadingRow, WithStartRow
{
    public $failures = [];
    public $courseMasterPk;

    public function __construct($courseMasterPk)
    {
        $this->courseMasterPk = $courseMasterPk;
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

            /* ---------- Validation ---------- */
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

            /* ---------- Group Mapping ---------- */
            $groupData = DB::table('group_type_master_course_master_map as gtm')
                ->join(
                    'course_group_type_master as cgtm',
                    'gtm.type_name',
                    '=',
                    'cgtm.pk'
                )
                ->where('cgtm.type_name', $data['group_type'])
                ->where('gtm.group_name', $data['group_name'])
                ->where('gtm.course_name', $this->courseMasterPk)
                ->where('gtm.active_inactive', 1)
                ->select('gtm.pk', 'gtm.course_name')
                ->first();

            if (!$groupData) {
                $this->addFailure($rowNumber, ['Invalid group or group type']);
                continue;
            }

            /* ---------- Student Lookup ---------- */
            $studentMaster = StudentMaster::where('generated_OT_code', $data['otcode'])
                ->whereExists(function ($q) use ($groupData) {
                    $q->select(DB::raw(1))
                        ->from('student_master_course__map as smcm')
                        ->whereColumn('smcm.student_master_pk', 'student_master.pk')
                        ->where('smcm.course_master_pk', $groupData->course_name)
                        ->where('smcm.active_inactive', 1);
                })
                ->orderByDesc('pk')
                ->select('pk')
                ->first();

            if (!$studentMaster) {
                $this->addFailure($rowNumber, [
                    "Student not found or inactive for OT code: {$data['otcode']}"
                ]);
                continue;
            }

            /* ---------- Duplicate Check (Excel) ---------- */
            $mappingKey = "{$studentMaster->pk}|{$groupData->pk}";
            if (isset($processedMappings[$mappingKey])) {
                $this->addFailure($rowNumber, [
                    "Duplicate entry in Excel for OT {$data['otcode']} & group {$data['group_name']}"
                ]);
                continue;
            }

            /* ---------- Duplicate Check (DB) ---------- */
            if (
                StudentCourseGroupMap::where('student_master_pk', $studentMaster->pk)
                    ->where('group_type_master_course_master_map_pk', $groupData->pk)
                    ->exists()
            ) {
                $this->addFailure($rowNumber, [
                    "Mapping already exists for OT {$data['otcode']} & group {$data['group_name']}"
                ]);
                continue;
            }

            $processedMappings[$mappingKey] = true;

            $dataToInsert[] = [
                'student_master_pk'                      => $studentMaster->pk,
                'group_type_master_course_master_map_pk' => $groupData->pk,
                'active_inactive'                        => 1,
                'created_date'                           => now(),
                'modified_date'                          => now(),
            ];
        }

        /* ---------- Bulk Insert ---------- */
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
