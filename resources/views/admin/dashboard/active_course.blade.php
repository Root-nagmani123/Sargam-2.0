@extends('admin.layouts.master')

@section('title', 'Active Course - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Active Course"></x-breadcrum>

    <div class="card">
        <div class="card-body">
            <div class="datatables">
                <div class="table-responsive">
                    <table class="table" id="active_course">
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
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection

@push('scripts')
<script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>

<script>
$(document).ready(function (){
    // Server-side: search, sort and paging are resolved in SQL.
    $('#active_course').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.dashboard.active_course') }}",
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'course_name', name: 'course_name' },
            { data: 'couse_short_name', name: 'couse_short_name' },
            { data: 'start_year', name: 'start_year', searchable: false },
            { data: 'end_date', name: 'end_date', searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [],
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: 'Loading data…',
            emptyTable: 'No active courses found.'
        }
    });
});
</script>
@endpush