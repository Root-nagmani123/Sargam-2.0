@extends('admin.layouts.master')

@section('title', 'Block Master')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Block Master" />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4>Block/Building Master</h4>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-end mb-3">
                        <a href="{{ route('estate.block.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                            <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 20px; vertical-align: middle;">add</i>
                            Add New Block
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
                <table id="blockTable" class="table table-striped table-bordered w-100">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Block Name</th>
                            <th>Description</th>
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
    var table = $('#blockTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('estate.block.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'block_name', name: 'block_name'},
            {data: 'description', name: 'description'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });

    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        if(confirm('Are you sure you want to delete this block?')) {
            $.ajax({
                url: "{{ route('estate.block.index') }}/" + id,
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
});
</script>
@endpush
