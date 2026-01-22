@extends('admin.layouts.master')

@section('title', 'Campus Master')

@section('setup_content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Campus Master</h4>
                    <a href="{{ route('estate.campus.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus"></i> Add New Campus
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table id="campusTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Campus Name</th>
                                    <th>Description</th>
                                    <th>Created By</th>
                                    <th>Created Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#campusTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('estate.campus.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'campus_name', name: 'campus_name'},
            {data: 'description', name: 'description'},
            {data: 'created_by', name: 'created_by'},
            {data: 'created_date', name: 'created_date'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });

    // Delete functionality
    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        
        if(confirm('Are you sure you want to delete this campus?')) {
            $.ajax({
                url: "{{ route('estate.campus.index') }}/" + id,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if(response.success) {
                        table.ajax.reload();
                        alert(response.message);
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    alert('Error deleting campus');
                }
            });
        }
    });
});
</script>
@endpush
