@extends('admin.layouts.master')
@section('title', 'Peer Evaluation Groups | Sargam User Panel')

@section('setup_content')
<div class="container-fluid">
    <div class="card p-3" >
        <h4>All Peer Evaluation Groups</h4>

        @if (($groupsCount ?? 0) === 0)
            <div class="alert alert-info">No evaluation groups available.</div>
        @else
            <div class="table-responsive mt-3">
                <table class="table table-bordered" id="userPeerGroupsTable">
                    <thead>
                        <tr>
                            <th>Group Name</th>
                            <th>Course Name</th>
                            <th>Event Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    {{-- Rows come from the server-side DataTable (see script below). --}}
                    <tbody></tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    if (!$('#userPeerGroupsTable').length) {
        return;
    }

    // Server-side: search, sort and paging are resolved in SQL.
    $('#userPeerGroupsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('peer.user_groups') }}",
            type: 'GET'
        },
        columns: [
            { data: 'group_name', name: 'group_name' },
            { data: 'course_name', name: 'course_name' },
            { data: 'event_name', name: 'event_name' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: 'Loading data…',
            emptyTable: 'No evaluation groups available.'
        }
    });
});
</script>
@endpush
