@extends('admin.layouts.master')
@section('title', 'Vehicle Pass Request - Sargam')
@section('setup_content')
<div class="container-fluid vehicle-pass-index-page">
    <x-breadcrum title="Vehicle Pass Request"></x-breadcrum>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <ul class="nav nav-pills vehicle-pass-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-panel" type="button" role="tab" aria-controls="active-panel" aria-selected="true">
                    Active
                    @if($activePasses->total() > 0)
                        <span class="badge bg-white text-primary ms-1">{{ $activePasses->total() }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="archive-tab" data-bs-toggle="tab" data-bs-target="#archive-panel" type="button" role="tab" aria-controls="archive-panel" aria-selected="false">
                    Archive
                    @if($archivedPasses->total() > 0)
                        <span class="badge bg-secondary ms-1">{{ $archivedPasses->total() }}</span>
                    @endif
                </button>
            </li>
        </ul>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="dropdown">
                <button class="btn btn-outline-success dropdown-toggle d-flex align-items-center gap-2 px-4 py-2 rounded-pill shadow-sm" type="button" id="vehiclePassExportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">download</i>
                    Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2" aria-labelledby="vehiclePassExportDropdown">
                    <li><h6 class="dropdown-header text-muted small text-uppercase">Active Requests</h6></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.security.vehicle_pass.export', ['tab' => 'active', 'format' => 'xlsx']) }}">
                            <i class="material-icons material-symbols-rounded text-success" style="font-size:18px;">table_chart</i>
                            Export Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.security.vehicle_pass.export', ['tab' => 'active', 'format' => 'csv']) }}">
                            <i class="material-icons material-symbols-rounded text-info" style="font-size:18px;">description</i>
                            Export CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.security.vehicle_pass.export', ['tab' => 'active', 'format' => 'pdf']) }}">
                            <i class="material-icons material-symbols-rounded text-danger" style="font-size:18px;">picture_as_pdf</i>
                            Export PDF
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header text-muted small text-uppercase">Archived Requests</h6></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.security.vehicle_pass.export', ['tab' => 'archive', 'format' => 'xlsx']) }}">
                            <i class="material-icons material-symbols-rounded text-success" style="font-size:18px;">table_chart</i>
                            Export Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.security.vehicle_pass.export', ['tab' => 'archive', 'format' => 'csv']) }}">
                            <i class="material-icons material-symbols-rounded text-info" style="font-size:18px;">description</i>
                            Export CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.security.vehicle_pass.export', ['tab' => 'archive', 'format' => 'pdf']) }}">
                            <i class="material-icons material-symbols-rounded text-danger" style="font-size:18px;">picture_as_pdf</i>
                            Export PDF
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header text-muted small text-uppercase">All Requests</h6></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.security.vehicle_pass.export', ['tab' => 'all', 'format' => 'xlsx']) }}">
                            <i class="material-icons material-symbols-rounded text-success" style="font-size:18px;">table_chart</i>
                            Export Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.security.vehicle_pass.export', ['tab' => 'all', 'format' => 'csv']) }}">
                            <i class="material-icons material-symbols-rounded text-info" style="font-size:18px;">description</i>
                            Export CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.security.vehicle_pass.export', ['tab' => 'all', 'format' => 'pdf']) }}">
                            <i class="material-icons material-symbols-rounded text-danger" style="font-size:18px;">picture_as_pdf</i>
                            Export PDF
                        </a>
                    </li>
                </ul>
            </div>
            <a href="{{ route('admin.security.vehicle_pass.create') }}" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
                <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:20px;">add</i>
                Generate New Vehicle Pass
            </a>
        </div>
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
            <div class="tab-content">
                <div class="tab-pane fade show active" id="active-panel" role="tabpanel" aria-labelledby="active-tab">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle vehicle-pass-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">
                                        <input type="checkbox" class="form-check-input select-all-active" aria-label="Select all">
                                    </th>
                                    <th>S.No.</th>
                                    <th>Employee Name</th>
                                    <th>Vehicle Pass No.</th>
                                    <th>Vehicle Type</th>
                                    <th>Vehicle Number</th>
                                    <th>Uploaded Document</th>
                                    <th>Requested Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activePasses as $index => $pass)
                                    <tr>
                                        <td class="align-middle">
                                            <input type="checkbox" class="form-check-input row-select" value="{{ $pass->vehicle_tw_pk }}" aria-label="Select row">
                                        </td>
                                        <td class="fw-medium align-middle">{{ $activePasses->firstItem() + $index }}</td>
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
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        </td>
                                        <td class="align-middle">
                                            <div class="d-flex align-items-center gap-2">
                                                <a href="{{ route('admin.security.vehicle_pass.show', encrypt($pass->vehicle_tw_pk)) }}" class="text-primary" title="View" data-bs-toggle="tooltip">
                                                    <i class="material-icons material-symbols-rounded" style="font-size:22px;">visibility</i>
                                                </a>
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
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-5 text-muted">
                                            <i class="material-icons material-symbols-rounded d-block mb-2" style="font-size:48px; opacity:0.4;">directions_car</i>
                                            <p class="mb-1">No active vehicle pass requests found.</p>
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
                    <div class="d-flex justify-content-between align-items-center px-3 py-3 border-top flex-wrap gap-2">
                        <div class="small text-muted">
                            Showing <strong>{{ $activePasses->firstItem() ?? 0 }}</strong> to <strong>{{ $activePasses->lastItem() ?? 0 }}</strong> of <strong>{{ $activePasses->total() }}</strong> active requests
                        </div>
                        <nav>
                            {{ $activePasses->links('pagination::bootstrap-5') }}
                        </nav>
                    </div>
                </div>

                <div class="tab-pane fade" id="archive-panel" role="tabpanel" aria-labelledby="archive-tab">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle vehicle-pass-table">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Employee Name</th>
                                    <th>Vehicle Pass No.</th>
                                    <th>Vehicle Type</th>
                                    <th>Vehicle Number</th>
                                    <th>Uploaded Document</th>
                                    <th>Requested Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($archivedPasses as $index => $pass)
                                    <tr>
                                        <td class="fw-medium align-middle">{{ $archivedPasses->firstItem() + $index }}</td>
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
                                            @if($pass->vech_card_status == 2)
                                                <span class="badge bg-success">Approved</span>
                                            @else
                                                <span class="badge bg-danger">Rejected</span>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            <a href="{{ route('admin.security.vehicle_pass.show', encrypt($pass->vehicle_tw_pk)) }}" class="text-primary" title="View" data-bs-toggle="tooltip">
                                                <i class="material-icons material-symbols-rounded" style="font-size:22px;">visibility</i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <i class="material-icons material-symbols-rounded d-block mb-2" style="font-size:48px; opacity:0.4;">archive</i>
                                            <p class="mb-1">No archived vehicle pass requests found.</p>
                                            <small>Approved or rejected applications will appear here.</small>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center px-3 py-3 border-top flex-wrap gap-2">
                        <div class="small text-muted">
                            Showing <strong>{{ $archivedPasses->firstItem() ?? 0 }}</strong> to <strong>{{ $archivedPasses->lastItem() ?? 0 }}</strong> of <strong>{{ $archivedPasses->total() }}</strong> archived requests
                        </div>
                        <nav>
                            {{ $archivedPasses->links('pagination::bootstrap-5', ['pageName' => 'archive_page']) }}
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.vehicle-pass-index-page .card { border-radius: 0.5rem; overflow: hidden; }
.vehicle-pass-tabs .nav-link {
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-weight: 500;
    color: #6c757d;
    background: #e9ecef;
    border: none;
    margin-right: 0.25rem;
}
.vehicle-pass-tabs .nav-link:hover { color: #495057; background: #dee2e6; }
.vehicle-pass-tabs .nav-link.active {
    background: #004a93;
    color: #fff;
}
.vehicle-pass-table thead tr { background: #122442; color: #fff; }
.vehicle-pass-table thead th { font-weight: 600; font-size: 0.8125rem; padding: 0.75rem 1rem; border: none; }
.vehicle-pass-table tbody td { padding: 0.75rem 1rem; vertical-align: middle; border-bottom: 1px solid #f1f3f5; }
.vehicle-pass-table tbody tr:hover { background: #f8fafc; }
.vehicle-pass-index-page .pagination .page-item.active .page-link { background-color: #004a93; border-color: #004a93; color: #fff; }
.vehicle-pass-index-page .pagination .page-link { color: #004a93; }
.vehicle-pass-index-page .dropdown .btn-outline-success {
    border-color: #198754;
    color: #198754;
    transition: all 0.2s ease;
}
.vehicle-pass-index-page .dropdown .btn-outline-success:hover {
    background: #198754;
    color: #fff;
    transform: translateY(-1px);
}
.vehicle-pass-index-page .dropdown-menu .dropdown-item:hover {
    background: #f8fafc;
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var selectAll = document.querySelector('.select-all-active');
    var rowSelects = document.querySelectorAll('#active-panel .row-select');
    if (selectAll && rowSelects.length) {
        selectAll.addEventListener('change', function() {
            rowSelects.forEach(function(cb) { cb.checked = selectAll.checked; });
        });
    }
    var activeTab = document.getElementById('active-tab');
    var archiveTab = document.getElementById('archive-tab');
    var activePanel = document.getElementById('active-panel');
    var archivePanel = document.getElementById('archive-panel');
    if (activeTab && archiveTab && activePanel && archivePanel) {
        activeTab.classList.add('active');
        activeTab.setAttribute('aria-selected', 'true');
        archiveTab.classList.remove('active');
        archiveTab.setAttribute('aria-selected', 'false');
        activePanel.classList.add('show', 'active');
        archivePanel.classList.remove('show', 'active');
        if (window.location.hash === '#archive-panel') {
            history.replaceState(null, null, window.location.pathname + window.location.search);
        }
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
