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
        $this->id = $id ? decrypt($id) : null;
    }

    public function collection()
    {
        $data = StudentCourseGroupMap::with([
            'student:pk,display_name,generated_OT_code', 
            'groupTypeMasterCourseMasterMap.courseGroup:pk,course_name,course_year',
            'groupTypeMasterCourseMasterMap.courseGroupType:pk,type_name',
            'groupTypeMasterCourseMasterMap.facility:venue_id,venue_name'
        ]);
        
        if ($this->id) {
            $data = $data->whereHas('groupTypeMasterCourseMasterMap', function ($q) {
                $q->where('pk', $this->id);
            });
        }

        $data = $data->get();


        return $data->map(function ($record) {
            $groupMap = $record->groupTypeMasterCourseMasterMap;
            $facility = $groupMap && $groupMap->facility ? $groupMap->facility->venue_name : '';
            $groupType = $groupMap && $groupMap->courseGroupType ? $groupMap->courseGroupType->type_name : '';

            return [
                'name'        => optional($record->student)->display_name ?? '',
                'otcode'      => optional($record->student)->generated_OT_code ?? '',
                'group_name'  => $groupMap ? $groupMap->group_name : '',
                'group_type'  => $groupType,
                'facility'    => $facility,
            ];
        });
    }

    public function headings(): array
    {
        return ['Name', 'OTCode', 'Group Name', 'Group Type', 'Facility'];
    }
}
