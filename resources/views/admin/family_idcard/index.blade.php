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
                    <li><h6 class="dropdown-header text-muted small text-uppercase">Active</h6></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', ['tab' => 'active', 'format' => 'xlsx']) }}"><i class="material-icons material-symbols-rounded text-success" style="font-size:18px;">table_chart</i> Excel</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', ['tab' => 'active', 'format' => 'csv']) }}"><i class="material-icons material-symbols-rounded text-info" style="font-size:18px;">description</i> CSV</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', ['tab' => 'active', 'format' => 'pdf']) }}"><i class="material-icons material-symbols-rounded text-danger" style="font-size:18px;">picture_as_pdf</i> PDF</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header text-muted small text-uppercase">Archive</h6></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', ['tab' => 'archive', 'format' => 'xlsx']) }}">Excel</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', ['tab' => 'archive', 'format' => 'csv']) }}">CSV</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', ['tab' => 'archive', 'format' => 'pdf']) }}">PDF</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', ['tab' => 'all', 'format' => 'xlsx']) }}">All - Excel</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', ['tab' => 'all', 'format' => 'csv']) }}">All - CSV</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.family_idcard.export', ['tab' => 'all', 'format' => 'pdf']) }}">All - PDF</a></li>
                </ul>
            </div>
            <a href="{{ route('admin.family_idcard.create') }}" class="btn btn-success btn-sm d-flex align-items-center gap-1" title="Add">
                <i class="material-icons material-symbols-rounded" style="font-size:20px;">add</i>
            </a>
            <button type="button" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1" title="Delete selected" id="bulkDeleteBtn" style="display:none!important;">
                <i class="material-icons material-symbols-rounded" style="font-size:20px;">close</i>
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" title="Print" onclick="window.print();">
                <i class="material-icons material-symbols-rounded" style="font-size:20px;">print</i>
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="d-flex flex-wrap align-items-center gap-3 px-3 py-2 border-bottom bg-light">
                <form method="GET" action="{{ request()->url() }}" class="d-flex align-items-center gap-2" id="perPageForm">
                    <label class="mb-0 small text-muted">Show</label>
                    <select name="per_page" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                        @foreach([10, 25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ request('per_page', 10) == $n ? 'selected' : '' }}>{{ $n }} entries</option>
                        @endforeach
                    </select>
                    @if(request()->has('archive_page'))
                        <input type="hidden" name="archive_page" value="{{ request('archive_page') }}">
                    @endif
                </form>
                <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="showHideCols" data-bs-toggle="dropdown" aria-expanded="false">Show / hide columns</button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="showHideCols">
                            <li class="dropdown-item-text small text-muted">Toggle column visibility</li>
                            @foreach(['sno','request_date','employee_name','designation','department','no_of_member','id_type','actions'] as $col)
                                <li><label class="dropdown-item d-flex align-items-center gap-2 mb-0 cursor-pointer"><input type="checkbox" class="form-check-input col-toggle" data-col="{{ $col }}" checked> <span class="text-capitalize">{{ str_replace('_', ' ', $col) }}</span></label></li>
                            @endforeach
                        </ul>
                    </div>
                    <label class="mb-0 small text-muted">Search with in table:</label>
                    <input type="search" class="form-control form-control-sm" id="familyTableSearch" placeholder="Search..." style="max-width:200px;">
                </div>
            </div>

            <div class="tab-content">
                <div class="tab-pane show active" id="active-panel" role="tabpanel" aria-labelledby="active-tab">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle family-idcard-table" id="familyActiveTable">
                            <thead>
                                <tr class="table-primary">
                                    <th class="col-sno">S.NO.</th>
                                    <th class="col-request_date">REQUEST DATE</th>
                                    <th class="col-employee_name">EMPLOYEE NAME</th>
                                    <th class="col-designation">DESIGNATION</th>
                                    <th class="col-department">DEPARTMENT NAME</th>
                                    <th class="col-no_of_member">NO OF MEMBER CARD</th>
                                    <th class="col-id_type">ID TYPE</th>
                                    <th class="col-actions text-end">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activeRequests as $index => $req)
                                    <tr class="family-row" data-search="{{ strtolower(($req->employee_name ?? '') . ' ' . ($req->employee_id ?? '') . ' ' . ($req->designation ?? '') . ' ' . ($req->section ?? '') . ' ' . ($req->card_type ?? '') . ' ' . ($req->member_count ?? '')) }}">
                                        <td class="fw-medium col-sno">{{ $activeRequests->firstItem() + $index }}</td>
                                        <td class="col-request_date">{{ $req->created_at ? (\Carbon\Carbon::parse($req->created_at)->format('d-m-Y')) : '--' }}</td>
                                        <td class="col-employee_name">{{ $req->employee_name ?? $req->employee_id ?? '--' }}</td>
                                        <td class="col-designation">{{ $req->designation ?? '--' }}</td>
                                        <td class="col-department">{{ $req->section ?? '--' }}</td>
                                        <td class="col-no_of_member"><a href="{{ route('admin.family_idcard.members', $req->first_id) }}" class="text-primary fw-medium">{{ $req->member_count }}</a></td>
                                        <td class="col-id_type">{{ $req->card_type ?: 'Family Card' }}</td>
                                        <td class="col-actions text-end">
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
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="material-icons material-symbols-rounded d-block mb-2" style="font-size:48px; opacity:0.4;">inbox</i>
                                            <p class="mb-1">No family ID card requests found.</p>
                                            <a href="{{ route('admin.family_idcard.create') }}" class="btn btn-primary btn-sm mt-2">Add Request</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center px-3 py-3 border-top flex-wrap gap-2">
                        <div class="small text-muted">
                            Showing <strong>{{ $activeRequests->firstItem() ?? 0 }}</strong> to <strong>{{ $activeRequests->lastItem() ?? 0 }}</strong> of <strong>{{ $activeRequests->total() }}</strong> entries
                        </div>
                        <nav>{{ $activeRequests->links('pagination::bootstrap-5') }}</nav>
                    </div>
                </div>

                <div class="tab-pane fade" id="archive-panel" role="tabpanel" aria-labelledby="archive-tab">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle family-idcard-table" id="familyArchiveTable">
                            <thead>
                                <tr class="table-primary">
                                    <th class="col-sno">S.NO.</th>
                                    <th class="col-request_date">REQUEST DATE</th>
                                    <th class="col-employee_name">EMPLOYEE NAME</th>
                                    <th class="col-designation">DESIGNATION</th>
                                    <th class="col-department">DEPARTMENT NAME</th>
                                    <th class="col-no_of_member">NO OF MEMBER CARD</th>
                                    <th class="col-id_type">ID TYPE</th>
                                    <th class="col-actions text-end">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($archivedRequests as $index => $req)
                                    <tr class="family-row" data-search="{{ strtolower(($req->employee_name ?? '') . ' ' . ($req->employee_id ?? '') . ' ' . ($req->designation ?? '') . ' ' . ($req->section ?? '') . ' ' . ($req->card_type ?? '') . ' ' . ($req->member_count ?? '')) }}">
                                        <td class="fw-medium col-sno">{{ $archivedRequests->firstItem() + $index }}</td>
                                        <td class="col-request_date">{{ $req->created_at ? (\Carbon\Carbon::parse($req->created_at)->format('d-m-Y')) : '--' }}</td>
                                        <td class="col-employee_name">{{ $req->employee_name ?? $req->employee_id ?? '--' }}</td>
                                        <td class="col-designation">{{ $req->designation ?? '--' }}</td>
                                        <td class="col-department">{{ $req->section ?? '--' }}</td>
                                        <td class="col-no_of_member"><a href="{{ route('admin.family_idcard.members', $req->first_id) }}" class="text-primary fw-medium">{{ $req->member_count }}</a></td>
                                        <td class="col-id_type">{{ $req->card_type ?: 'Family Card' }}</td>
                                        <td class="col-actions text-end">
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
                                        <td colspan="8" class="text-center py-5 text-muted">No archived requests.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center px-3 py-3 border-top flex-wrap gap-2">
                        <div class="small text-muted">
                            Showing <strong>{{ $archivedRequests->firstItem() ?? 0 }}</strong> to <strong>{{ $archivedRequests->lastItem() ?? 0 }}</strong> of <strong>{{ $archivedRequests->total() }}</strong> entries
                        </div>
                        <nav>{{ $archivedRequests->links('pagination::bootstrap-5', ['pageName' => 'archive_page']) }}</nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.family-idcard-index-page .card { border-radius: 0.5rem; overflow: hidden; }
/* Ensure tab panes show/hide correctly; Active is visible on first load via .show class */
.family-idcard-index-page .tab-content .tab-pane.show { display: block !important; }
.family-idcard-index-page .tab-content .tab-pane:not(.show) { display: none !important; }
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
.family-idcard-tabs .nav-link.active { background: #004a93; color: #fff; }
.family-idcard-table thead tr.table-primary { background: #004a93 !important; color: #fff; border: none; }
.family-idcard-table thead th { font-weight: 700; font-size: 0.75rem; padding: 0.75rem 0.5rem; border: none; text-align: left; }
.family-idcard-table thead th.text-end { text-align: right; }
.family-idcard-table tbody td { padding: 0.65rem 0.5rem; vertical-align: middle; border-bottom: 1px solid #eee; font-size: 0.875rem; }
.family-idcard-table tbody tr:hover { background: #f8fafc; }
.family-idcard-index-page .pagination .page-item.active .page-link { background-color: #004a93; border-color: #004a93; color: #fff; }
.family-idcard-index-page .pagination .page-link { color: #004a93; }
@media print {
    .family-idcard-tabs, .btn, .nav, .breadcrumb, .family-idcard-index-page .d-flex.border-bottom, #perPageForm, #familyTableSearch, #showHideCols, .col-actions, .no-print { display: none !important; }
    .family-idcard-index-page .card { box-shadow: none; border: 1px solid #dee2e6; }
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Force Active tab content to show as soon as page loads (run once after other inits)
    (function ensureActiveTabVisible() {
        var activeTabBtn = document.getElementById('active-tab');
        var activePanel = document.getElementById('active-panel');
        var archivePanel = document.getElementById('archive-panel');
        if (!activePanel) return;
        if (typeof bootstrap !== 'undefined' && activeTabBtn) {
            try {
                var tab = new bootstrap.Tab(activeTabBtn);
                tab.show();
            } catch (e) {}
        }
        activePanel.classList.add('show', 'active');
        activePanel.style.display = 'block';
        if (archivePanel) {
            archivePanel.classList.remove('show', 'active');
            archivePanel.style.display = 'none';
        }
        if (activeTabBtn) {
            activeTabBtn.classList.add('active');
            activeTabBtn.setAttribute('aria-selected', 'true');
        }
        var archiveTabBtn = document.getElementById('archive-tab');
        if (archiveTabBtn) {
            archiveTabBtn.classList.remove('active');
            archiveTabBtn.setAttribute('aria-selected', 'false');
        }
        // When user switches tab, clear inline display so Bootstrap controls visibility
        [activeTabBtn, archiveTabBtn].forEach(function(btn) {
            if (btn) {
                btn.addEventListener('shown.bs.tab', function() {
                    if (activePanel) activePanel.style.display = '';
                    if (archivePanel) archivePanel.style.display = '';
                });
            }
        });
    })();
    // Run again after short delay in case another script resets tabs
    setTimeout(function() {
        var ap = document.getElementById('active-panel');
        var arp = document.getElementById('archive-panel');
        if (ap && arp && !arp.classList.contains('show')) {
            ap.classList.add('show', 'active');
            ap.style.display = 'block';
            arp.style.display = 'none';
        }
    }, 100);

    var searchInput = document.getElementById('familyTableSearch');
    var activeTable = document.getElementById('familyActiveTable');
    var archiveTable = document.getElementById('familyArchiveTable');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var q = this.value.trim().toLowerCase();
            var panels = [document.getElementById('active-panel'), document.getElementById('archive-panel')];
            panels.forEach(function(panel) {
                if (!panel) return;
                var rows = panel.querySelectorAll('tbody tr.family-row');
                rows.forEach(function(row) {
                    var text = row.getAttribute('data-search') || '';
                    row.style.display = q === '' || text.indexOf(q) !== -1 ? '' : 'none';
                });
            });
        });
    }
    document.querySelectorAll('.col-toggle').forEach(function(cb) {
        cb.addEventListener('change', function() {
            var col = this.getAttribute('data-col');
            var show = this.checked;
            document.querySelectorAll('.col-' + col).forEach(function(el) { el.style.display = show ? '' : 'none'; });
        });
    });
});
</script>
@endsection
