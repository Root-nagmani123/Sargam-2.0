<?php

namespace App\Exports;

use App\Models\{StudentMaster, CourseGroupTypeMaster, GroupTypeMasterCourseMasterMap, StudentCourseGroupMap};
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings};

class GroupMappingExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $data = StudentCourseGroupMap::with(['groupMap'])->get();
        dd($data->toArray());
        // return StudentCourseGroupMap::with([
        //         'student:id,generated_OT_code,name',
        //         'groupMap:id,group_name,type_name',
        //         'groupMap.courseGroupType:id,type_name'
        //     ])
        //     ->get()
        //     ->map(function ($record) {
        //         return [
        //             'name'        => $record->student->name ?? '',
        //             'otcode'      => $record->student->generated_OT_code ?? '',
        //             'group_name'  => $record->groupMap->group_name ?? '',
        //             'group_type'  => $record->groupMap->courseGroupType->type_name ?? '',
        //         ];
        //     });
    }

    public function headings(): array
    {
        return ['name', 'otcode', 'group_name', 'group_type'];
    }
}
