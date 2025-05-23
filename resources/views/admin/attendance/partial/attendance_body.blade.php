@if(!empty($timetables))
@foreach($timetables as $item)
    @php
        $groups = collect();
        if (!empty($item->group_name)) {
            $groupIds = json_decode($item->group_name);
            $groups = \App\Models\GroupTypeMasterCourseMasterMap::whereIn('pk', $groupIds)->get();
        }
    @endphp

    @foreach($groups as $group)
        <tr>
            <td>{{ $offset + $loop->iteration }}</td>
            <td>{{ optional($item->courseGroupTypeMaster)->course_name ?? 'N/A' }}</td>
            <td>{{ $item->mannual_starttime ?? 'N/A' }}</td>
            <td>{{ optional($item->classSession)->start_time . ' - ' . optional($item->classSession)->end_time }}</td>
            <td>{{ optional($item->venue)->venue_name ?? 'N/A' }}</td>
            <td>{{ $item->subject_topic ?? 'N/A' }}</td>
            <td>{{ optional($item->faculty)->full_name ?? 'N/A' }}</td>
            <td><a href="#">Mark Attendance</a></td>
        </tr>
    @endforeach
@endforeach
@endif