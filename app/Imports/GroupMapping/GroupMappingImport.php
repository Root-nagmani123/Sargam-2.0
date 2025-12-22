<?php

namespace App\Imports\GroupMapping;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{ToCollection, WithHeadingRow, WithStartRow};
use App\Models\{StudentMaster, CourseGroupTypeMaster, GroupTypeMasterCourseMasterMap, StudentCourseGroupMap,StudentMasterCourseMap};

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

        // 1️⃣ Validation
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

        // 2️⃣ Group + Course (single query)
        $groupData = DB::table('group_type_master_course_master_map as gtm')
            ->join(
                'course_group_type_master as cgtm',
                'gtm.type_name',
                '=',
                'cgtm.pk'
            )
            ->where('cgtm.type_name', $data['group_type'])
            ->where('gtm.group_name', $data['group_name'])
            ->where('gtm.active_inactive', 1)
            ->select('gtm.pk', 'gtm.course_name', 'gtm.type_name')
            ->first();

        if (!$groupData) {
            $this->addFailure($rowNumber, ["Invalid group or group type"]);
            continue;
        }

        // 3️⃣ Student lookup (SINGLE QUERY, no loop)
        $studentMaster = StudentMaster::where('generated_OT_code', $data['otcode'])
            ->whereExists(function ($q) use ($groupData) {
                $q->select(DB::raw(1))
                  ->from('student_master_course__map as smcm')
                  ->whereColumn('smcm.student_master_pk', 'student_master.pk')
                  ->where('smcm.course_master_pk', $groupData->course_name)
                  ->where('smcm.active_inactive', 1);
            })
            ->orderBy('pk', 'desc')
            ->select('pk')
            ->first();
            // print_r($studentMaster);

        if (!$studentMaster) {
            $this->addFailure($rowNumber, [
                "Student not found or inactive for OT code: {$data['otcode']}"
            ]);
            continue;
        }

        // 4️⃣ Duplicate check (within Excel)
        $mappingKey = "{$studentMaster->pk}|{$groupData->pk}";
        if (isset($processedMappings[$mappingKey])) {
            $this->addFailure($rowNumber, [
                "Duplicate entry in Excel for OT {$data['otcode']} & group {$data['group_name']}"
            ]);
            continue;
        }

        // 5️⃣ DB duplicate check
        $exists = StudentCourseGroupMap::where(
            'student_master_pk',
            $studentMaster->pk
        )
        ->where(
            'group_type_master_course_master_map_pk',
            $groupData->pk
        )
        ->exists();

        if ($exists) {
            $this->addFailure($rowNumber, [
                "Mapping already exists for OT {$data['otcode']} & group {$data['group_name']}"
            ]);
            continue;
        }

        $processedMappings[$mappingKey] = true;

        // 6️⃣ Prepare insert
        $dataToInsert[] = [
            'student_master_pk'                      => $studentMaster->pk,
            'group_type_master_course_master_map_pk' => $groupData->pk,
            'active_inactive'                        => 1,
            'created_date'                           => now(),
            'modified_date'                          => now(),
        ];
    }
    // print_r($dataToInsert);die;

    // 7️⃣ Bulk insert
    if (empty($this->failures) && !empty($dataToInsert)) {
        StudentCourseGroupMap::insert($dataToInsert);
    }
}

    public function collection_bkp(Collection $collection)
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
           $data_type_pk =  DB::table('group_type_master_course_master_map')
            ->join('course_group_type_master', 'group_type_master_course_master_map.type_name', '=', 'course_group_type_master.pk')
            ->where('course_group_type_master.type_name', $data['group_type'])
            ->where('group_type_master_course_master_map.active_inactive', 1)
           ->where('group_name', $data['group_name'])->select('group_type_master_course_master_map.type_name','group_type_master_course_master_map.group_name','group_type_master_course_master_map.course_name')->first();

          $data_student_pk = DB::table('student_master_course__map')->where('course_master_pk',$data_type_pk->course_name)->select('student_master_pk')->first();
        //   print_r($data_student_pk);
          // Lookup: StudentMaster
            // $studentMaster = StudentMaster::whereRaw('LOWER(generated_OT_code) = ?', [strtolower($data['otcode'])])
            //     ->select('pk')->first();
            // $studentMaster = StudentMaster::whereRaw('LOWER(generated_OT_code) = ?', [strtolower($data['otcode'])])
            //     ->select('pk')->get();
            $studentMaster = StudentMaster::whereRaw(
                'LOWER(generated_OT_code) = ?',
                [strtolower($data['otcode'])]
            )
            ->where('pk', $data_student_pk->student_master_pk)
            ->orderBy('pk', 'desc')   // last inserted record
            ->select('pk')
            ->get();

                // print_r($studentMaster);
            //     foreach ($studentMaster as $student) {
            //         $course_active_check_student = StudentMasterCourseMap::where('student_master_pk', $student['pk'])->where('active_inactive', 1)->exists();
            //         if ($course_active_check_student) {
            //             $studentMaster->pk = $student['pk'];
            //         }
            //     }
            //     // print_r($studentMaster);die;


            // if (!$studentMaster) {
            //     $this->addFailure($rowNumber, ["Student not found for OT code: {$data['otcode']}"]);
            //     continue;
            // }

            // // Lookup: GroupTypeMasterCourseMasterMap (lowercase match)
            // $groupMap = GroupTypeMasterCourseMasterMap::whereRaw('LOWER(group_name) = ?', [strtolower($data['group_name'])])->first();

            // if (!$groupMap) {
            //     $this->addFailure($rowNumber, ["Group map not found for group name: {$data['group_name']}"]);
            //     continue;
            // }

            // // Lookup: CourseGroupTypeMaster
            // $courseGroupType = CourseGroupTypeMaster::where('pk', $groupMap->type_name)->first();

            // if (!$courseGroupType) {
            //     $this->addFailure($rowNumber, ["Course group type not found for type name ID: {$groupMap->type_name}"]);
            //     continue;
            // }

            // // Compare group type (case-insensitive)
            // if (strcasecmp($courseGroupType->type_name, $data['group_type']) !== 0) {
            //     $this->addFailure($rowNumber, [
            //         "Group type mismatch: expected '{$courseGroupType->type_name}', got '{$data['group_type']}' for group '{$data['group_name']}'"
            //     ]);
            //     continue;
            // }

            // $mappingKey = "{$studentMaster->pk}|{$groupMap->pk}";

            // if (isset($processedMappings[$mappingKey])) {
            //     $this->addFailure($rowNumber, [
            //         "Duplicate row detected for student '{$data['otcode']}' and group '{$data['group_name']}' within the sheet"
            //     ]);
            //     continue;
            // }

            // $existingMapping = StudentCourseGroupMap::where('student_master_pk', $studentMaster->pk)
            //     ->where('group_type_master_course_master_map_pk', $groupMap->pk)
            //     ->exists();

            // if ($existingMapping) {
            //     $this->addFailure($rowNumber, [
            //         "Mapping already exists for student '{$data['otcode']}' and group '{$data['group_name']}'"
            //     ]);
            //     continue;
            // }

            // $processedMappings[$mappingKey] = true;

            // // Passed all checks → prepare insert
            // $dataToInsert[] = [
            //     'student_master_pk'                      => $studentMaster->pk,
            //     'group_type_master_course_master_map_pk' => $groupMap->pk,
            //     'active_inactive'                        => 1,
            //     'created_date'                           => now(),
            //     'modified_date'                          => now(),
            // ];
        }
        die;

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
