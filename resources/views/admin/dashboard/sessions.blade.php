@extends('admin.layouts.master')

@section('title', 'Sessions - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Sessions"></x-breadcrum>
    
    <div class="card" >
        <div class="card-body">
            <h4>Session Details</h4>
            <hr class="my-2">
            
                <div class="datatables">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="sessionsTable">
                            <thead>
                                <tr>
                                    <th scope="col">Sl. No.</th>
                                    <th scope="col">Course Name</th>
                                    <th scope="col">Subject</th>
                                    <th scope="col">Module</th>
                                    <th scope="col">Topic</th>
                                    <th scope="col">Group</th>
                                    <th scope="col">Venue</th>
                                    <th scope="col">Session Time</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        if ($('#sessionsTable').length) {
            // Server-side: search, sort and paging are resolved in SQL.
            $('#sessionsTable').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    url: "{{ route('admin.dashboard.sessions') }}",
                    type: 'GET'
                },
                "columns": [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'course_name', name: 'course_name', orderable: false, searchable: false },
                    { data: 'subject_name', name: 'subject_name', orderable: false, searchable: false },
                    { data: 'module_name', name: 'module_name', orderable: false, searchable: false },
                    { data: 'topic', name: 'subject_topic' },
                    { data: 'group_names', name: 'group_names', orderable: false, searchable: false },
                    { data: 'venue_name', name: 'venue_name', orderable: false, searchable: false },
                    { data: 'session_time', name: 'session_time', orderable: false, searchable: false },
                    { data: 'session_date', name: 'START_DATE', searchable: false },
                    { data: 'status', name: 'status', orderable: false, searchable: false }
                ],
                "pageLength": 25,
                "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
                "order": [[8, "desc"]], // Sort by date descending
                "language": {
                    "processing": "Loading data…",
                    "emptyTable": "No sessions found.",
                    "zeroRecords": "No sessions found.",
                    "paginate": {
                        "previous": '<i class="material-icons material-symbols-rounded" style="font-size: 24px;">chevron_left</i>',
                        "next": '<i class="material-icons material-symbols-rounded" style="font-size: 24px;">chevron_right</i>'
                    },
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search sessions..."
                },
                "responsive": true,
                "autoWidth": false
            });
        }
    });
</script>
@endpush

@endsection

