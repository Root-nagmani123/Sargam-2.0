@extends('admin.layouts.master')
@section('title', 'Request For Family Id Card - Sargam | Lal Bahadur Shastri')
@section('setup_content')
<div class="container-fluid family-idcard-index-page">
    <x-breadcrum title="Request For Family Id Card"></x-breadcrum>

    <h5 class="fw-bold mb-1">Request For Family Id Card</h5>
    <p class="text-muted small mb-4">This page displays all Family ID Card added in the system, and provide options to manage records such as add, edit, delete, excel upload, excel download, print etc.</p>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filter Section -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light border-bottom">
            <h6 class="mb-0 fw-semibold">Filters & Search</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ request()->url() }}" id="filterForm" class="mb-0">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" name="search" id="search" class="form-control" placeholder="Search by Employee ID, Name, Relation..." value="{{ request('search', '') }}">
                    </div>
                   
                    <div class="col-md-2">
                        <label for="per_page" class="form-label">Show Entries</label>
                        <select name="per_page" id="per_page" class="form-select">
                            @foreach([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ request('per_page', 10) == $n ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">search</i> Search
                        </button>
                        <a href="{{ route('admin.family_idcard.index') }}" class="btn btn-outline-secondary">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">restart_alt</i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabs and Action Buttons -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
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
                <button class="btn btn-outline-success dropdown-toggle d-flex align-items-center gap-2 px-3 py-2" type="button" id="familyIdcardExportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="material-icons material-symbols-rounded" style="font-size:18px;">download</i>
                    Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2" aria-labelledby="familyIdcardExportDropdown">
                    <li><h6 class="dropdown-header text-muted small text-uppercase">Active (with Filters)</h6></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', array_merge(['tab' => 'active', 'format' => 'xlsx'], request()->query())) }}"><i class="material-icons material-symbols-rounded text-success" style="font-size:18px;">table_chart</i> Excel</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', array_merge(['tab' => 'active', 'format' => 'pdf'], request()->query())) }}"><i class="material-icons material-symbols-rounded text-danger" style="font-size:18px;">picture_as_pdf</i> PDF</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header text-muted small text-uppercase">Archive (with Filters)</h6></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', array_merge(['tab' => 'archive', 'format' => 'xlsx'], request()->query())) }}">Excel</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', array_merge(['tab' => 'archive', 'format' => 'pdf'], request()->query())) }}">PDF</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header text-muted small text-uppercase">All (with Filters)</h6></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', array_merge(['tab' => 'all', 'format' => 'xlsx'], request()->query())) }}">All - Excel</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', array_merge(['tab' => 'all', 'format' => 'pdf'], request()->query())) }}">All - PDF</a></li>
                </ul>
            </div>
            <a href="{{ route('admin.family_idcard.create') }}" class="btn btn-success btn-sm d-flex align-items-center gap-1" title="Add">
                <i class="material-icons material-symbols-rounded" style="font-size:20px;">add</i>
            </a>
            <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" title="Print" onclick="window.print();">
                <i class="material-icons material-symbols-rounded" style="font-size:20px;">print</i>
            </button>
        </div>
    </div>

    <!-- Tables -->
    <div class="card border-0 shadow-sm">
        <div class="tab-content">
            <!-- Active Tab -->
            <div class="tab-pane show active" id="active-panel" role="tabpanel" aria-labelledby="active-tab">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle family-idcard-table">
                        <thead>
                            <tr class="table-primary">
                                <th>S.NO.</th>
                                <th>REQUEST DATE</th>
                                <th>EMPLOYEE ID</th>
                                <th>EMPLOYEE NAME</th>
                                <th>DESIGNATION</th>
                                <th>DEPARTMENT</th>
                                <th>NO OF MEMBERS</th>
                                <th>ID TYPE</th>
                                <th class="text-end">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeRequests as $index => $req)
                                <tr>
                                    <td class="fw-medium">{{ $activeRequests->firstItem() + $index }}</td>
                                    <td>{{ $req->created_at ? (\Carbon\Carbon::parse($req->created_at)->format('d-m-Y')) : '--' }}</td>
                                    <td>{{ $req->employee_id ?? '--' }}</td>
                                    <td>{{ $req->employee_name ?? '--' }}</td>
                                    <td>{{ $req->designation ?? '--' }}</td>
                                    <td>{{ $req->section ?? '--' }}</td>
                                    <td><a href="{{ route('admin.family_idcard.members', $req->first_id) }}" class="text-primary fw-medium">{{ $req->member_count }}</a></td>
                                    <td>{{ $req->card_type ?? 'Family Card' }}</td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-1">
                                            <a href="{{ route('admin.family_idcard.members', $req->first_id) }}" class="btn btn-sm btn-outline-primary" title="View members"><i class="material-icons material-symbols-rounded" style="font-size:18px;">visibility</i></a>
                                            <a href="{{ route('admin.family_idcard.edit', $req->first_id) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="material-icons material-symbols-rounded" style="font-size:18px;">edit</i></a>
                                            <form action="{{ route('admin.family_idcard.destroy', $req->first_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to archive this request?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Archive"><i class="material-icons material-symbols-rounded" style="font-size:18px;">delete</i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="material-icons material-symbols-rounded d-block mb-2" style="font-size:48px; opacity:0.4;">inbox</i>
                                        <p class="mb-1">No family ID card requests found.</p>
                                        <a href="{{ route('admin.family_idcard.create') }}" class="btn btn-primary btn-sm mt-2">Add Request</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($activeRequests->count() > 0)
                    <div class="d-flex justify-content-between align-items-center px-3 py-3 border-top flex-wrap gap-2">
                        <div class="small text-muted">
                            Showing <strong>{{ $activeRequests->firstItem() ?? 0 }}</strong> to <strong>{{ $activeRequests->lastItem() ?? 0 }}</strong> of <strong>{{ $activeRequests->total() }}</strong> entries
                        </div>
                        <nav>{{ $activeRequests->links('pagination::bootstrap-5') }}</nav>
                    </div>
                @endif
            </div>

            <!-- Archive Tab -->
            <div class="tab-pane fade" id="archive-panel" role="tabpanel" aria-labelledby="archive-tab">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle family-idcard-table">
                        <thead>
                            <tr class="table-primary">
                                <th>S.NO.</th>
                                <th>REQUEST DATE</th>
                                <th>EMPLOYEE ID</th>
                                <th>EMPLOYEE NAME</th>
                                <th>DESIGNATION</th>
                                <th>DEPARTMENT</th>
                                <th>NO OF MEMBERS</th>
                                <th>ID TYPE</th>
                                <th class="text-end">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($archivedRequests as $index => $req)
                                <tr>
                                    <td class="fw-medium">{{ $archivedRequests->firstItem() + $index }}</td>
                                    <td>{{ $req->created_at ? (\Carbon\Carbon::parse($req->created_at)->format('d-m-Y')) : '--' }}</td>
                                    <td>{{ $req->employee_id ?? '--' }}</td>
                                    <td>{{ $req->employee_name ?? '--' }}</td>
                                    <td>{{ $req->designation ?? '--' }}</td>
                                    <td>{{ $req->section ?? '--' }}</td>
                                    <td><a href="{{ route('admin.family_idcard.members', $req->first_id) }}" class="text-primary fw-medium">{{ $req->member_count }}</a></td>
                                    <td>{{ $req->card_type ?? 'Family Card' }}</td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-1">
                                            <a href="{{ route('admin.family_idcard.members', $req->first_id) }}" class="btn btn-sm btn-outline-primary" title="View members"><i class="material-icons material-symbols-rounded" style="font-size:18px;">visibility</i></a>
                                            <form action="{{ route('admin.family_idcard.restore', $req->first_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Restore this request?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Restore"><i class="material-icons material-symbols-rounded" style="font-size:18px;">restore</i></button>
                                            </form>
                                            <form action="{{ route('admin.family_idcard.forceDelete', $req->first_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="material-icons material-symbols-rounded" style="font-size:18px;">delete_forever</i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">No archived requests.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($archivedRequests->count() > 0)
                    <div class="d-flex justify-content-between align-items-center px-3 py-3 border-top flex-wrap gap-2">
                        <div class="small text-muted">
                            Showing <strong>{{ $archivedRequests->firstItem() ?? 0 }}</strong> to <strong>{{ $archivedRequests->lastItem() ?? 0 }}</strong> of <strong>{{ $archivedRequests->total() }}</strong> entries
                        </div>
                        <nav>{{ $archivedRequests->links('pagination::bootstrap-5') }}</nav>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .family-idcard-tabs .nav-link {
        border-radius: 8px 8px 0 0;
        color: #6c757d;
        font-weight: 500;
        padding: 0.75rem 1.25rem;
    }
    .family-idcard-tabs .nav-link.active {
        background-color: #004a93;
        color: white;
    }
    .family-idcard-tabs .nav-link:hover {
        color: #004a93;
    }
    .family-idcard-table tbody tr:hover {
        background-color: #f8f9fa;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tabs - show Active tab by default
    const activeTabButton = document.getElementById('active-tab');
    if (activeTabButton) {
        const tab = new bootstrap.Tab(activeTabButton);
        tab.show();
        console.log('✅ Active tab initialized and shown');
    }

    // Auto-submit filter form when per_page changes
    const perPageSelect = document.getElementById('per_page');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    }
});
</script>
@endsection
