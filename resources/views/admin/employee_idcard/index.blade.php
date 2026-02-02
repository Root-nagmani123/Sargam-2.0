@extends('admin.layouts.master')
@section('title', 'Employee ID Card Request - Sargam | Lal Bahadur Shastri')
@section('setup_content')
<div class="container-fluid idcard-index-page">
    <!-- Breadcrumb + Search (reference: Setup > User Management, search icon right) -->
   <x-breadcrum title="Request Employee ID Card"></x-breadcrum>

    <!-- Tabs + Generate Button Row (reference: Active/Archive tabs left, blue button right) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <ul class="nav nav-pills idcard-index-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-panel" type="button" role="tab" aria-controls="active-panel" aria-selected="true">
                    Active
                    @if($activeRequests->total() > 0)
                        <span class="badge bg-white text-primary ms-1">{{ $activeRequests->total() }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="archive-tab" data-bs-toggle="tab" data-bs-target="#archive-panel" type="button" role="tab" aria-controls="archive-panel" aria-selected="false">
                    Archive
                    @if($archivedRequests->total() > 0)
                        <span class="badge bg-secondary ms-1">{{ $archivedRequests->total() }}</span>
                    @endif
                </button>
            </li>
        </ul>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="dropdown">
                <button class="btn btn-outline-success dropdown-toggle d-flex align-items-center gap-2 px-4 py-2 rounded-pill shadow-sm" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">download</i>
                    Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2" aria-labelledby="exportDropdown">
                    <li><h6 class="dropdown-header text-muted small text-uppercase">Active Requests</h6></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.employee_idcard.export', ['tab' => 'active', 'format' => 'xlsx']) }}">
                            <i class="material-icons material-symbols-rounded text-success" style="font-size:18px;">table_chart</i>
                            Export Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.employee_idcard.export', ['tab' => 'active', 'format' => 'csv']) }}">
                            <i class="material-icons material-symbols-rounded text-info" style="font-size:18px;">description</i>
                            Export CSV
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header text-muted small text-uppercase">Archived Requests</h6></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.employee_idcard.export', ['tab' => 'archive', 'format' => 'xlsx']) }}">
                            <i class="material-icons material-symbols-rounded text-success" style="font-size:18px;">table_chart</i>
                            Export Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.employee_idcard.export', ['tab' => 'archive', 'format' => 'csv']) }}">
                            <i class="material-icons material-symbols-rounded text-info" style="font-size:18px;">description</i>
                            Export CSV
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header text-muted small text-uppercase">All Requests</h6></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.employee_idcard.export', ['tab' => 'all', 'format' => 'xlsx']) }}">
                            <i class="material-icons material-symbols-rounded text-success" style="font-size:18px;">table_chart</i>
                            Export Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.employee_idcard.export', ['tab' => 'all', 'format' => 'csv']) }}">
                            <i class="material-icons material-symbols-rounded text-info" style="font-size:18px;">description</i>
                            Export CSV
                        </a>
                    </li>
                </ul>
            </div>
            <a href="{{ route('admin.employee_idcard.create') }}" class="btn btn-outline-primary idcard-create-btn px-4 py-2 rounded-pill shadow-sm">
                Generate New ID Card
            </a>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm idcard-index-card">
        <div class="card-body p-0">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="active-panel" role="tabpanel" aria-labelledby="active-tab">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle idcard-index-table">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Request date</th>
                                    <th>Employee Name</th>
                                    <th>Designation</th>
                                    <th>ID Card No.</th>
                                    <th>ID Type</th>
                                    <th>Complete</th>
                                    <th>Valid From - To</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activeRequests as $index => $request)
                                    <tr data-request-id="{{ $request->id }}">
                                        <td class="fw-medium">{{ $activeRequests->firstItem() + $index }}</td>
                                        <td>{{ $request->created_at ? $request->created_at->format('d/m/Y') : '--' }}</td>
                                        <td>{{ $request->name }}</td>
                                        <td>{{ $request->designation ?? '--' }}</td>
                                        <td>{{ $request->id_card_no ?? '--' }}</td>
                                        <td>{{ $request->card_type ?? '--' }}</td>
                                        <td>{{ $request->complete ?? '--' }}</td>
                                        <td>{{ $request->valid_from ? $request->valid_from->format('d/m/Y') : '--' }} - {{ $request->valid_to ? $request->valid_to->format('d/m/Y') : '--' }}</td>
                                        <td>{{ $request->status ?? '--' }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2" role="group">
                                                <a href="{{ route('admin.employee_idcard.show', $request->id) }}" 
                                                   class="text-primary d-inline-flex align-items-center gap-1" title="View Details" data-bs-toggle="tooltip" data-bs-placement="top" title="View Details">
                                                    <i class="material-icons material-symbols-rounded">visibility</i>
                                                </a>
                                                <a href="{{ route('admin.employee_idcard.edit', $request->id) }}" 
                                                   class="text-primary d-inline-flex align-items-center gap-1" title="Edit" onclick="return confirm('Are you sure you want to edit this request?');" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                                                    <i class="material-icons material-symbols-rounded">edit</i>
                                                </a>
                                                <form action="{{ route('admin.employee_idcard.destroy', $request->id) }}" 
                                                      method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Are you sure you want to delete this request?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <a class="text-danger d-inline-flex align-items-center gap-1" title="Delete" onclick="return confirm('Are you sure you want to delete this request?');" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                                            <i class="material-icons material-symbols-rounded">delete</i>
                                                    </a>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="material-icons material-symbols-rounded d-block mb-2" style="font-size:48px; opacity:0.4;">inbox</i>
                                            <p class="mb-1">No active ID card requests found.</p>
                                            <small>Click "Generate New ID Card" to create one.</small>
                                            <a href="{{ route('admin.employee_idcard.create') }}" class="btn btn-primary btn-sm mt-2 rounded-pill px-3">
                                                <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:16px;">add</i>
                                                Create Request
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center px-3 py-3 border-top flex-wrap gap-2">
                        <div class="small text-muted">
                            Showing <strong>{{ $activeRequests->firstItem() ?? 0 }}</strong> to <strong>{{ $activeRequests->lastItem() ?? 0 }}</strong> of <strong>{{ $activeRequests->total() }}</strong> active requests
                        </div>
                        <nav>
                            {{ $activeRequests->links('pagination::bootstrap-5') }}
                        </nav>
                    </div>
                </div>

                <div class="tab-pane fade" id="archive-panel" role="tabpanel" aria-labelledby="archive-tab">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle idcard-index-table">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Request date</th>
                                    <th>Employee Name</th>
                                    <th>Designation</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($archivedRequests as $index => $request)
                                    <tr data-request-id="{{ $request->id }}">
                                        <td class="fw-medium">{{ $archivedRequests->firstItem() + $index }}</td>
                                        <td>{{ $request->created_at ? $request->created_at->format('d/m/Y') : '--' }}</td>
                                        <td>{{ $request->name }}</td>
                                        <td>{{ $request->designation ?? '--' }}</td>
                                        <td>
                                            @php
                                                $statusClass = match($request->status) {
                                                    'Pending' => 'warning',
                                                    'Approved' => 'success',
                                                    'Rejected' => 'danger',
                                                    'Issued' => 'primary',
                                                    default => 'secondary'
                                                };
                                                $statusIcon = match($request->status) {
                                                    'Pending' => 'schedule',
                                                    'Approved' => 'check_circle',
                                                    'Rejected' => 'cancel',
                                                    'Issued' => 'card_giftcard',
                                                    default => 'help'
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $statusClass }}">
                                                <i class="material-icons material-symbols-rounded" style="font-size:12px;">{{ $statusIcon }}</i>
                                                {{ $request->status }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm idcard-action-btns" role="group">
                                                <a href="{{ route('admin.employee_idcard.show', $request->id) }}" 
                                                   class="text-primary" title="View Details">
                                                    <i class="material-icons material-symbols-rounded">visibility</i>
                                                </a>
                                                <form action="{{ route('admin.employee_idcard.restore', $request->id) }}" 
                                                      method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Restore this request?');">
                                                    @csrf
                                                    <a class="text-success" title="Restore">
                                                        <i class="material-icons material-symbols-rounded">restore</i>
                                                    </a>
                                                </form>
                                                <form action="{{ route('admin.employee_idcard.forceDelete', $request->id) }}" 
                                                      method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Permanently delete this request? This action cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <a class="text-danger" title="Delete Permanently">
                                                        <i class="material-icons material-symbols-rounded">delete_forever</i>
                                                    </a>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="material-icons material-symbols-rounded d-block mb-2" style="font-size:48px; opacity:0.4;">archive</i>
                                            <p class="mb-1">No archived ID card requests found.</p>
                                            <small>Deleted records will appear here.</small>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center px-3 py-3 border-top flex-wrap gap-2">
                        <div class="small text-muted">
                            Showing <strong>{{ $archivedRequests->firstItem() ?? 0 }}</strong> to <strong>{{ $archivedRequests->lastItem() ?? 0 }}</strong> of <strong>{{ $archivedRequests->total() }}</strong> archived requests
                        </div>
                        <nav>
                            {{ $archivedRequests->links('pagination::bootstrap-5', ['pageName' => 'archive_page']) }}
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Request Employee ID Card - Match reference design */
.idcard-index-page .idcard-breadcrumb { font-size: 0.875rem; }
.idcard-index-page .breadcrumb-item.active { color: #004a93 !important; }
.idcard-page-title { font-size: 1.5rem; font-weight: 700; color: #212529; }

/* Tabs - Active: blue bg white text, Archive: light grey */
.idcard-index-tabs .nav-link {
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-weight: 500;
    color: #6c757d;
    background: #e9ecef;
    border: none;
    margin-right: 0.25rem;
}
.idcard-index-tabs .nav-link:hover { color: #495057; background: #dee2e6; }
.idcard-index-tabs .nav-link.active {
    background: #004a93;
    color: #fff;
}

/* Export dropdown */
.idcard-index-page .dropdown .btn-outline-success {
    border-color: #198754;
    color: #198754;
    transition: all 0.2s ease;
}
.idcard-index-page .dropdown .btn-outline-success:hover {
    background: #198754;
    color: #fff;
    transform: translateY(-1px);
}
.idcard-index-page .dropdown-menu .dropdown-item:hover {
    background: #f8fafc;
}
.idcard-index-page .dropdown-menu .dropdown-item:active {
    background: #e9ecef;
}

/* Generate New ID Card button */
.idcard-create-btn {
    transition: all 0.2s ease;
}
.idcard-create-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 74, 147, 0.3) !important;
}

/* Table action buttons */
.idcard-action-btns {
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.idcard-action-btns .btn {
    padding: 0.4rem 0.6rem;
    transition: all 0.2s ease;
}
.idcard-action-btns .btn:hover {
    transform: translateY(-1px);
}
.idcard-action-btns .btn i {
    font-size: 1.125rem;
}
.idcard-action-btns .btn-group .btn { border-radius: 0; }
.idcard-action-btns .btn-group .btn:first-child { border-radius: 0.5rem 0 0 0.5rem; }
.idcard-action-btns .btn-group .btn:last-child { border-radius: 0 0.5rem 0.5rem 0; }
.idcard-action-btns form { display: inline; }

/* Table - Dark blue header, white text */
.idcard-index-card { border-radius: 0.5rem; overflow: hidden; }
.idcard-index-table thead tr {
    background: #122442;
    color: #fff;
}
.idcard-index-table thead th {
    font-weight: 600;
    font-size: 0.8125rem;
    padding: 0.75rem 1rem;
    border: none;
}
.idcard-index-table tbody td {
    padding: 0.75rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f5;
}
.idcard-index-table tbody tr:hover { background: #f8fafc; }

/* Pagination - match reference */
.idcard-index-page .pagination .page-item.active .page-link {
    background-color: #004a93;
    border-color: #004a93;
    color: #fff;
}
.idcard-index-page .pagination .page-link { color: #004a93; }
.idcard-index-page .pagination .page-link:hover { color: #003a75; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ensure Active tab is active by default on page load
    const activeTab = document.getElementById('active-tab');
    const archiveTab = document.getElementById('archive-tab');
    const activePanel = document.getElementById('active-panel');
    const archivePanel = document.getElementById('archive-panel');
    if (activeTab && archiveTab && activePanel && archivePanel) {
        activeTab.classList.add('active');
        activeTab.setAttribute('aria-selected', 'true');
        archiveTab.classList.remove('active');
        archiveTab.setAttribute('aria-selected', 'false');
        activePanel.classList.add('show', 'active');
        archivePanel.classList.remove('show', 'active');
        // Clear URL hash that might switch to Archive
        if (window.location.hash === '#archive-panel') {
            history.replaceState(null, null, window.location.pathname + window.location.search);
        }
    }
});
</script>
@endsection
