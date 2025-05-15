<?php

namespace App\Exports;

use App\Models\{GroupTypeMasterCourseMasterMap, StudentCourseGroupMap};
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings};

class GroupMappingExport implements FromCollection, WithHeadings
{
    protected $id;

    public function __construct($id)
    {
        $this->id = decrypt($id);
    }

    public function collection()
    {
        $data = StudentCourseGroupMap::with([
            'student:pk,display_name,generated_OT_code', 
            'groupTypeMasterCourseMasterMap.courseGroup:pk,course_name,course_year',
            'groupTypeMasterCourseMasterMap.courseGroupType:pk,type_name'
        ]);
        
        if ($this->id) {
            $data = $data->whereHas('groupTypeMasterCourseMasterMap', function ($q) {
                $q->where('pk', $this->id);
            });
        }

        $data = $data->get();


        return $data->map(function ($record) {
            return [
                'name'        => $record->student->display_name ?? '',
                'otcode'      => $record->student->generated_OT_code ?? '',
                'group_name'  => $record->groupTypeMasterCourseMasterMap->group_name ?? '',
                'group_type'  => $record->groupTypeMasterCourseMasterMap->courseGroupType->type_name ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return ['Name', 'OT Code', 'Group Name', 'Group Type'];
    }
}
