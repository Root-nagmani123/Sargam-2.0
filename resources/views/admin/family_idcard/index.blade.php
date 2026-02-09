@extends('admin.layouts.master')
@section('title', 'Request Family ID Card - Sargam | Lal Bahadur Shastri')
@section('setup_content')
<div class="container-fluid family-idcard-index-page">
    <x-breadcrum title="Request Family ID Card"></x-breadcrum>
    <x-session_message />

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <ul class="nav nav-pills family-idcard-tabs" role="tablist">
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
                <button class="btn btn-outline-success dropdown-toggle d-flex align-items-center gap-2 px-4 py-2 rounded-pill shadow-sm" type="button" id="familyIdcardExportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">download</i>
                    Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2" aria-labelledby="familyIdcardExportDropdown">
                    <li><h6 class="dropdown-header text-muted small text-uppercase">Active Requests</h6></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', ['tab' => 'active', 'format' => 'xlsx']) }}">
                            <i class="material-icons material-symbols-rounded text-success" style="font-size:18px;">table_chart</i>
                            Export Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', ['tab' => 'active', 'format' => 'csv']) }}">
                            <i class="material-icons material-symbols-rounded text-info" style="font-size:18px;">description</i>
                            Export CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', ['tab' => 'active', 'format' => 'pdf']) }}">
                            <i class="material-icons material-symbols-rounded text-danger" style="font-size:18px;">picture_as_pdf</i>
                            Export PDF
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header text-muted small text-uppercase">Archived Requests</h6></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', ['tab' => 'archive', 'format' => 'xlsx']) }}">
                            <i class="material-icons material-symbols-rounded text-success" style="font-size:18px;">table_chart</i>
                            Export Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', ['tab' => 'archive', 'format' => 'csv']) }}">
                            <i class="material-icons material-symbols-rounded text-info" style="font-size:18px;">description</i>
                            Export CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', ['tab' => 'archive', 'format' => 'pdf']) }}">
                            <i class="material-icons material-symbols-rounded text-danger" style="font-size:18px;">picture_as_pdf</i>
                            Export PDF
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header text-muted small text-uppercase">All Requests</h6></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', ['tab' => 'all', 'format' => 'xlsx']) }}">
                            <i class="material-icons material-symbols-rounded text-success" style="font-size:18px;">table_chart</i>
                            Export Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', ['tab' => 'all', 'format' => 'csv']) }}">
                            <i class="material-icons material-symbols-rounded text-info" style="font-size:18px;">description</i>
                            Export CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', ['tab' => 'all', 'format' => 'pdf']) }}">
                            <i class="material-icons material-symbols-rounded text-danger" style="font-size:18px;">picture_as_pdf</i>
                            Export PDF
                        </a>
                    </li>
                </ul>
            </div>
            <a href="{{ route('admin.family_idcard.create') }}" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
                Generate New ID Card
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="active-panel" role="tabpanel" aria-labelledby="active-tab">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle family-idcard-table">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Request date</th>
                                    <th>Employee Name</th>
                                    <th>Designation</th>
                                    <th>Department</th>
                                    <th>Member Name</th>
                                    <th>ID Type</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activeRequests as $index => $req)
                                    <tr>
                                        <td class="fw-medium">{{ $activeRequests->firstItem() + $index }}</td>
                                        <td>{{ $req->created_at ? $req->created_at->format('d/m/Y') : '--' }}</td>
                                        <td>{{ $req->employee_name ?? $req->employee_id ?? '--' }}</td>
                                        <td>{{ $req->designation ?? '--' }}</td>
                                        <td>{{ $req->section ?? '--' }}</td>
                                        <td>{{ $req->name ?? '--' }}</td>
                                        <td>{{ $req->card_type ?? '--' }}</td>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-end gap-2">
                                                <a href="{{ route('admin.family_idcard.show', $req) }}" class="text-primary" title="View" data-bs-toggle="tooltip" data-bs-placement="top">
                                                    <i class="material-icons material-symbols-rounded">visibility</i>
                                                </a>
                                                <a href="{{ route('admin.family_idcard.edit', $req) }}" class="text-primary" title="Edit" data-bs-toggle="tooltip" data-bs-placement="top">
                                                    <i class="material-icons material-symbols-rounded">edit</i>
                                                </a>
                                                <form action="{{ route('admin.family_idcard.destroy', $req) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to archive this request?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link text-danger p-0 border-0" title="Archive">
                                                        <i class="material-icons material-symbols-rounded">delete</i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="material-icons material-symbols-rounded d-block mb-2" style="font-size:48px; opacity:0.4;">inbox</i>
                                            <p class="mb-1">No family ID card requests found.</p>
                                            <small>Click "Generate New ID Card" to create one.</small>
                                            <a href="{{ route('admin.family_idcard.create') }}" class="btn btn-primary btn-sm mt-2 rounded-pill px-3">
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
                        <table class="table table-hover mb-0 align-middle family-idcard-table">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Request date</th>
                                    <th>Employee Name</th>
                                    <th>Designation</th>
                                    <th>Member Name</th>
                                    <th>ID Type</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($archivedRequests as $index => $req)
                                    <tr>
                                        <td class="fw-medium">{{ $archivedRequests->firstItem() + $index }}</td>
                                        <td>{{ $req->created_at ? $req->created_at->format('d/m/Y') : '--' }}</td>
                                        <td>{{ $req->employee_name ?? $req->employee_id ?? '--' }}</td>
                                        <td>{{ $req->designation ?? '--' }}</td>
                                        <td>{{ $req->name ?? '--' }}</td>
                                        <td>{{ $req->card_type ?? '--' }}</td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('admin.family_idcard.show', $req) }}" class="btn btn-outline-primary" title="View">
                                                    <i class="material-icons material-symbols-rounded">visibility</i>
                                                </a>
                                                <form action="{{ route('admin.family_idcard.restore', $req->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Restore this request?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-success" title="Restore">
                                                        <i class="material-icons material-symbols-rounded">restore</i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.family_idcard.forceDelete', $req->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this request? This action cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger" title="Delete Permanently">
                                                        <i class="material-icons material-symbols-rounded">delete_forever</i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="material-icons material-symbols-rounded d-block mb-2" style="font-size:48px; opacity:0.4;">archive</i>
                                            <p class="mb-1">No archived family ID card requests found.</p>
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
.family-idcard-index-page .card { border-radius: 0.5rem; overflow: hidden; }
.family-idcard-tabs .nav-link {
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    font-weight: 500;
    color: #6c757d;
    background: #e9ecef;
    border: none;
    margin-right: 0.25rem;
}
.family-idcard-tabs .nav-link:hover { color: #495057; background: #dee2e6; }
.family-idcard-tabs .nav-link.active {
    background: #004a93;
    color: #fff;
}
.family-idcard-table thead tr { background: #122442; color: #fff; }
.family-idcard-table thead th { font-weight: 600; font-size: 0.8125rem; padding: 0.75rem 1rem; border: none; }
.family-idcard-table tbody td { padding: 0.75rem 1rem; vertical-align: middle; border-bottom: 1px solid #f1f3f5; }
.family-idcard-table tbody tr:hover { background: #f8fafc; }
.family-idcard-index-page .pagination .page-item.active .page-link { background-color: #004a93; border-color: #004a93; color: #fff; }
.family-idcard-index-page .pagination .page-link { color: #004a93; }
.family-idcard-index-page .dropdown .btn-outline-success {
    border-color: #198754;
    color: #198754;
    transition: all 0.2s ease;
}
.family-idcard-index-page .dropdown .btn-outline-success:hover {
    background: #198754;
    color: #fff;
    transform: translateY(-1px);
}
.family-idcard-index-page .dropdown-menu .dropdown-item:hover {
    background: #f8fafc;
}
.family-idcard-index-page .btn-group .btn { border-radius: 0; }
.family-idcard-index-page .btn-group .btn:first-child { border-radius: 0.375rem 0 0 0.375rem; }
.family-idcard-index-page .btn-group .btn:last-child { border-radius: 0 0.375rem 0.375rem 0; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
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
        if (window.location.hash === '#archive-panel') {
            history.replaceState(null, null, window.location.pathname + window.location.search);
        }
    }
});
</script>
@endsection
