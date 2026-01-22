@extends('admin.layouts.master')

@section('title', 'Possession Management')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Possession Management" />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="row">
                <div class="col-6"><h4>Possession Management</h4></div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-end mb-3">
                        <a href="{{ route('estate.possession.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                            <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 20px; vertical-align: middle;">add</i>
                            Add Possession
                        </a>
                    </div>
                </div>
            </div>
            <hr>
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            <div class="table-responsive">
                <table id="possessionTable" class="table table-striped table-bordered w-100">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Employee</th>
                            <th>Unit</th>
                            <th>Campus</th>
                            <th>Area</th>
                            <th>Block</th>
                            <th>Possession Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#possessionTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('estate.possession.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'employee_name', name: 'employee.name'},
            {data: 'unit_name', name: 'unit.unit_name'},
            {data: 'campus', name: 'unit.campus.campus_name'},
            {data: 'area', name: 'unit.area.area_name'},
            {data: 'block', name: 'unit.block.block_name'},
            {data: 'possession_date', name: 'possession_date'},
            {data: 'status', name: 'status'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });
    
    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        if(confirm('Are you sure you want to delete this possession record?')) {
            $.ajax({
                url: "{{ route('estate.possession.index') }}/" + id,
                type: 'DELETE',
                data: {_token: '{{ csrf_token() }}'},
                success: function(response) {
                    if(response.success) {
                        table.ajax.reload();
                        alert(response.message);
                    }
                }
            });
        }
    });
    
    $(document).on('click', '.vacate-btn', function() {
        var id = $(this).data('id');
        var vacationDate = prompt('Enter vacation date (YYYY-MM-DD):');
        if(vacationDate) {
            $.ajax({
                url: "{{ url('estate/possession') }}/" + id + "/vacate",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    vacation_date: vacationDate
                },
                success: function(response) {
                    if(response.success) {
                        table.ajax.reload();
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.message || 'Failed to vacate unit'));
                }
            });
        }
    });
});
</script>
@endpush
