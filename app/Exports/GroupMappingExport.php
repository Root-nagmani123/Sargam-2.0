<?php

namespace App\Exports;

use App\Models\{GroupTypeMasterCourseMasterMap, StudentCourseGroupMap};
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings};

class GroupMappingExport implements FromCollection, WithHeadings
{
    protected $id;

    public function __construct($id = null)
    {
        $this->id = $id ? decrypt($id) : null;
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
                'name'        => optional($record->student)->display_name ?? '',
                'otcode'      => optional($record->student)->generated_OT_code ?? '',
                'group_name'  => optional($record->groupTypeMasterCourseMasterMap)->group_name ?? '',
                'group_type'  => optional($record->groupTypeMasterCourseMasterMap->courseGroupType)->type_name ?? '',
                'counsellor_code' => $record->counsellor_code ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return ['Name', 'OTCode', 'Group Name', 'Group Type', 'Counsellor Code'];
    }
}
