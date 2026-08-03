@extends('admin.layouts.master')
@section('title', 'Vehicle Pass Request - Sargam')
@section('setup_content')
<div class="container-fluid vehicle-pass-index-page">
    <x-breadcrum title="Vehicle Pass Request"></x-breadcrum>
    <x-session_message />

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <ul class="nav nav-pills vehicle-pass-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-panel" type="button" role="tab" aria-controls="active-panel" aria-selected="true">
                    Active
                    @if($activePasses->count() > 0)
                        <span class="badge bg-white text-primary ms-1">{{ $activePasses->count() }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="archive-tab" data-bs-toggle="tab" data-bs-target="#archive-panel" type="button" role="tab" aria-controls="archive-panel" aria-selected="false">
                    Archive
                    @if($archivedPasses->count() > 0)
                        <span class="badge bg-secondary ms-1">{{ $archivedPasses->count() }}</span>
                    @endif
                </button>
            </li>
        </ul>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="dropdown">
                <button class="btn btn-outline-success dropdown-toggle d-flex align-items-center gap-2 px-4 py-2 rounded-1 shadow-sm" type="button" id="vehiclePassExportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
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
            <a href="{{ route('admin.security.vehicle_pass.create') }}" class="btn btn-primary px-4 py-2 rounded-1 shadow-sm">
               
                Request for Vehicle Pass
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="tab-content">
                <div class="tab-pane show active" id="active-panel" role="tabpanel" aria-labelledby="active-tab">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle vehicle-pass-table" id="activeVehiclePassTable">
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
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="archive-panel" role="tabpanel" aria-labelledby="archive-tab">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle vehicle-pass-table" id="archiveVehiclePassTable">
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
                            <tbody></tbody>
                        </table>
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
$(document).ready(function() {
    var indexUrl = '{{ route('admin.security.vehicle_pass.index') }}';

    var activeVehiclePassTable = $('#activeVehiclePassTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: indexUrl, type: 'GET', data: function (d) { d.tab = 'active'; } },
        columns: [
            { data: 'select', name: 'select', orderable: false, searchable: false },
            { data: 'sn', name: 'sn', orderable: false, searchable: false },
            { data: 'employee_name', name: 'employee_name' },
            { data: 'vehicle_req_id', name: 'vehicle_req_id' },
            { data: 'vehicle_type', name: 'vehicle_type' },
            { data: 'vehicle_no', name: 'vehicle_no' },
            { data: 'doc_upload', name: 'doc_upload', orderable: false, searchable: false },
            { data: 'created_date', name: 'created_date', orderable: false, searchable: false },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        ordering: false,
        language: {
            search: 'Search active requests:',
            zeroRecords: 'No active vehicle pass requests found.',
            emptyTable: 'No active vehicle pass requests found.'
        },
        drawCallback: function() {
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                [].slice.call(document.querySelectorAll('#active-panel [data-bs-toggle="tooltip"]')).forEach(function(el) {
                    bootstrap.Tooltip.getOrCreateInstance(el);
                });
            }
        }
    });

    var archiveVehiclePassTable = $('#archiveVehiclePassTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: indexUrl, type: 'GET', data: function (d) { d.tab = 'archive'; } },
        columns: [
            { data: 'sn', name: 'sn', orderable: false, searchable: false },
            { data: 'employee_name', name: 'employee_name' },
            { data: 'vehicle_req_id', name: 'vehicle_req_id' },
            { data: 'vehicle_type', name: 'vehicle_type' },
            { data: 'vehicle_no', name: 'vehicle_no' },
            { data: 'doc_upload', name: 'doc_upload', orderable: false, searchable: false },
            { data: 'created_date', name: 'created_date', orderable: false, searchable: false },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        ordering: false,
        language: {
            search: 'Search archived requests:',
            zeroRecords: 'No archived vehicle pass requests found.',
            emptyTable: 'No archived vehicle pass requests found.'
        },
        drawCallback: function() {
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                [].slice.call(document.querySelectorAll('#archive-panel [data-bs-toggle="tooltip"]')).forEach(function(el) {
                    bootstrap.Tooltip.getOrCreateInstance(el);
                });
            }
        }
    });

    // Select-all (delegated: rows are loaded via AJAX)
    $(document).on('change', '.select-all-active', function() {
        var checked = $(this).is(':checked');
        $('#active-panel .row-select').prop('checked', checked);
    });

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
        activePanel.style.display = 'block';
        archivePanel.classList.remove('show', 'active');
        archivePanel.style.display = 'none';
        if (window.location.hash === '#archive-panel') {
            history.replaceState(null, null, window.location.pathname + window.location.search);
        }

        // Keep only one list visible when switching tabs.
        activeTab.addEventListener('shown.bs.tab', function () {
            activePanel.style.display = 'block';
            archivePanel.style.display = 'none';
            activeVehiclePassTable.columns.adjust();
        });
        archiveTab.addEventListener('shown.bs.tab', function () {
            activePanel.style.display = 'none';
            archivePanel.style.display = 'block';
            archiveVehiclePassTable.columns.adjust();
        });
    }
});
</script>
@endpush
@endsection
