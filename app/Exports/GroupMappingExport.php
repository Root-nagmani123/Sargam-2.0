<?php

namespace App\Exports;

use App\Models\StudentCourseGroupMap;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings};

class GroupMappingExport implements FromCollection, WithHeadings
{
    protected $id;

    public function __construct($id = null)
    {
        if ($id) {
            try {
                $this->id = decrypt($id);
            } catch (\Exception $e) {
                $this->id = null;
            }
        } else {
            $this->id = null;
        }
    }

    public function collection()
    {
        $data = StudentCourseGroupMap::with([
            'student:pk,display_name,generated_OT_code', 
            'groupTypeMasterCourseMasterMap.courseGroup:pk,course_name,course_year',
            'groupTypeMasterCourseMasterMap.courseGroupType:pk,type_name',
            'groupTypeMasterCourseMasterMap.facility:pk,full_name'
        ]);
        
        if ($this->id) {
            $data = $data->whereHas('groupTypeMasterCourseMasterMap', function ($q) {
                $q->where('pk', $this->id);
            });
        }

        $data = $data->get();


        return $data->map(function ($record) {
            $groupMap = $record->groupTypeMasterCourseMasterMap;
            $groupType = $groupMap && $groupMap->courseGroupType ? $groupMap->courseGroupType->type_name : '';

            return [
                'name'        => optional($record->student)->display_name ?? '',
                'otcode'      => optional($record->student)->generated_OT_code ?? '',
                'group_name'  => $groupMap ? $groupMap->group_name : '',
                'group_type'  => $groupType,
            ];
        });
    }

    public function headings(): array
    {
        return ['name', 'otcode', 'group_name', 'group_type'];
    }
}
