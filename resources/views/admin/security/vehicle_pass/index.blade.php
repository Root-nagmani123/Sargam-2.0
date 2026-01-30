@extends('admin.layouts.master')
@section('title', 'Vehicle Pass Request - Sargam')
@section('setup_content')
<div class="container-fluid vehicle-pass-index-page">
    <x-breadcrum title="Vehicle Pass Request"></x-breadcrum>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h2 class="h5 mb-0 fw-bold text-dark">Vehicle Pass Request</h2>
        <a href="{{ route('admin.security.vehicle_pass.create') }}" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
            <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:20px;">add</i>
            Generate New Vehicle Pass
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle vehicle-pass-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">
                                <input type="checkbox" class="form-check-input" id="selectAll" aria-label="Select all">
                            </th>
                            <th>S.No.</th>
                            <th>Employee Name</th>
                            <th>Vehicle Pass No. </th>
                            <th>Vehicle Type</th>
                            <th>Vehicle Number</th>
                            <th>Uploaded Document</th>
                            <th>Requested Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vehiclePasses as $index => $pass)
                            <tr>
                                <td class="align-middle">
                                    <input type="checkbox" class="form-check-input row-select" value="{{ $pass->vehicle_tw_pk }}" aria-label="Select row">
                                </td>
                                <td class="fw-medium align-middle">{{ $vehiclePasses->firstItem() + $index }}</td>
                                <td class="align-middle">{{ $pass->display_name }}</td>
                                <td class="align-middle">{{ $pass->vehicle_req_id ?? '--' }}</td>
                                <td class="align-middle">{{ $pass->vehicleType->vehicle_type ?? '--' }}</td>
                                <td class="align-middle">{{ $pass->vehicle_no ?? '--' }}</td>
                                <td class="align-middle">
                                    @if($pass->doc_upload)
                                        <a href="{{ asset('storage/' . $pass->doc_upload) }}" target="_blank" class="text-primary" title="View Document" data-bs-toggle="tooltip">
                                            <i class="material-icons material-symbols-rounded" style="font-size:22px;">picture_as_pdf</i>
                                        </a>
                                    @else
                                        <span class="text-muted">--</span>
                                    @endif
                                </td>
                                <td class="align-middle">{{ $pass->created_date ? $pass->created_date->format('d-m-Y H:i') : '--' }}</td>
                                <td class="align-middle">
                                    @if($pass->vech_card_status == 1)
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @elseif($pass->vech_card_status == 2)
                                        <span class="badge bg-success">Approved</span>
                                    @else
                                        <span class="badge bg-danger">Rejected</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="{{ route('admin.security.vehicle_pass.show', encrypt($pass->vehicle_tw_pk)) }}" class="text-primary" title="View" data-bs-toggle="tooltip">
                                            <i class="material-icons material-symbols-rounded" style="font-size:22px;">visibility</i>
                                        </a>
                                        @if($pass->vech_card_status == 1)
                                            <a href="{{ route('admin.security.vehicle_pass.edit', encrypt($pass->vehicle_tw_pk)) }}" class="text-success" title="Edit" data-bs-toggle="tooltip">
                                                <i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i>
                                            </a>
                                            <form action="{{ route('admin.security.vehicle_pass.delete', encrypt($pass->vehicle_tw_pk)) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this application?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link p-0 text-danger" title="Delete" data-bs-toggle="tooltip">
                                                    <i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="material-icons material-symbols-rounded d-block mb-2" style="font-size:48px; opacity:0.4;">directions_car</i>
                                    <p class="mb-1">No vehicle pass requests found.</p>
                                    <small>Click "Generate New Vehicle Pass" to create one.</small>
                                    <a href="{{ route('admin.security.vehicle_pass.create') }}" class="btn btn-primary btn-sm mt-2 rounded-pill px-3">
                                        <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:16px;">add</i>
                                        Generate New Vehicle Pass
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($vehiclePasses->hasPages())
                <div class="d-flex justify-content-between align-items-center px-3 py-3 border-top flex-wrap gap-2">
                    <div class="small text-muted">
                        Showing <strong>{{ $vehiclePasses->firstItem() ?? 0 }}</strong> to <strong>{{ $vehiclePasses->lastItem() ?? 0 }}</strong> of <strong>{{ $vehiclePasses->total() }}</strong> items
                    </div>
                    <nav>
                        {{ $vehiclePasses->links('pagination::bootstrap-5') }}
                    </nav>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.vehicle-pass-index-page .card { border-radius: 0.5rem; overflow: hidden; }
.vehicle-pass-table thead tr { background: #122442; color: #fff; }
.vehicle-pass-table thead th { font-weight: 600; font-size: 0.8125rem; padding: 0.75rem 1rem; border: none; }
.vehicle-pass-table tbody td { padding: 0.75rem 1rem; vertical-align: middle; border-bottom: 1px solid #f1f3f5; }
.vehicle-pass-table tbody tr:hover { background: #f8fafc; }
.vehicle-pass-index-page .pagination .page-item.active .page-link { background-color: #004a93; border-color: #004a93; color: #fff; }
.vehicle-pass-index-page .pagination .page-link { color: #004a93; }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var selectAll = document.getElementById('selectAll');
    var rowSelects = document.querySelectorAll('.row-select');
    if (selectAll && rowSelects.length) {
        selectAll.addEventListener('change', function() {
            rowSelects.forEach(function(cb) { cb.checked = selectAll.checked; });
        });
    }
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).forEach(function(el) {
            new bootstrap.Tooltip(el);
        });
    }
});
</script>
@endpush
@endsection
