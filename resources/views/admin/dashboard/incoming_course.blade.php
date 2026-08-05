@extends('admin.layouts.master')

@section('title', 'Upcoming Course - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Upcoming Course"></x-breadcrum>

    <div class="card">
        <div class="card-body"> 
            <div class="datatables">
                <div class="table-responsive">
                    <table class="table" id="incoming_course">
                        <thead>
                            <tr>
                                <th scope="col">Sl. No.</th>
                                <th scope="col">Course Name</th>
                                <th scope="col">Short Name</th>
                                <th scope="col">Start Date</th>
                                <th scope="col">End Date</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($incoming_courses as $course)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $course->course_name }}</td>
                                <td>{{ $course->couse_short_name ?? '—' }}</td>
                                <td>{{ $course->start_year ? \Carbon\Carbon::parse($course->start_year)->format('d M Y') : '—' }}</td>
                                <td>{{ $course->end_date ? \Carbon\Carbon::parse($course->end_date)->format('d M Y') : '—' }}</td>
                                <td>
                                    <a href="{{ route('programme.show', encrypt($course->pk)) }}"
                                        class="btn btn-sm btn-primary" target="_blank">View Details</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var $table = $('#incoming_course');
    if (!$table.length || $.fn.dataTable.isDataTable($table[0])) {
        return;
    }

    $table.DataTable({
        responsive: false,
        autoWidth: false,
        order: [[3, 'asc']],
        language: {
            emptyTable: 'No upcoming courses found.'
        }
    });
});
</script>
@endpush
