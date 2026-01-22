@extends('admin.layouts.master')

@section('title', 'Electric Slab Master')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Electric Slab Master" />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="row">
                <div class="col-6"><h4>Electric Slab Master</h4></div>
                <div class="col-6">
                    <div class="d-flex justify-content-end align-items-end mb-3">
                        <a href="{{ route('estate.electric-slab.create') }}" class="btn btn-primary px-3 py-2 rounded-3 shadow-sm">
                            <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 20px; vertical-align: middle;">add</i>
                            Add Electric Slab
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
                <table id="electricSlabTable" class="table table-striped table-bordered w-100">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Slab Name</th>
                            <th>Units From</th>
                            <th>Units To</th>
                            <th>Rate per Unit</th>
                            <th>Fixed Charge</th>
                            <th>Effective From</th>
                            <th>Effective To</th>
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
    var table = $('#electricSlabTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('estate.electric-slab.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'slab_name', name: 'slab_name'},
            {data: 'units_from', name: 'units_from'},
            {data: 'units_to', name: 'units_to'},
            {data: 'rate_per_unit', name: 'rate_per_unit'},
            {data: 'fixed_charge', name: 'fixed_charge'},
            {data: 'effective_from', name: 'effective_from'},
            {data: 'effective_to', name: 'effective_to'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });
    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        if(confirm('Are you sure?')) {
            $.ajax({
                url: "{{ route('estate.electric-slab.index') }}/" + id,
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
