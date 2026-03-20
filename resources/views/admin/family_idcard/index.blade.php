@extends('admin.layouts.master')
@section('title', 'Request For Family Id Card - Sargam | Lal Bahadur Shastri')
@section('setup_content')
<div class="container-fluid family-idcard-index-page family-idcard-print-area">
    {{-- Print-only header: LBSNAA theme + logo, visible only when printing --}}
    <div class="print-only-header family-idcard-print-header family-idcard-print-header-lbsnaa">
        <div class="family-idcard-print-header-top">
            <img src="{{ asset('admin_assets/images/logos/logo.svg') }}" alt="LBSNAA" class="family-idcard-print-logo">
            <div class="family-idcard-print-title-block">
                <h5 class="mb-0">Lal Bahadur Shastri National Academy of Administration</h5>
                <p class="mb-0 family-idcard-print-sub">Request For Family Id Card – List</p>
            </div>
        </div>
        <div class="family-idcard-print-meta">
            <span>Printed on: {{ now()->format('d-m-Y H:i') }}</span>
            <span class="print-tab-info" id="familyIdcardPrintTabInfo">Active requests</span>
        </div>
    </div>

    <div class="no-print">
        <x-breadcrum title="Request For Family Id Card"></x-breadcrum>
    </div>

    <h5 class="fw-bold mb-1 no-print">Request For Family Id Card</h5>
    <p class="text-muted small mb-4 no-print">This page displays all Family ID Card added in the system, and provide options to manage records such as add, edit, delete, excel upload, excel download, print etc.</p>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filter Section -->
    <div class="card border-0 shadow-sm mb-4 no-print">
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
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 no-print">
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
                @php
                    // Base export URL that already includes current filters (search, card_type, etc.)
                    $exportBase = route('admin.family_idcard.export', request()->query());
                @endphp
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2" aria-labelledby="familyIdcardExportDropdown">
                    <li><h6 class="dropdown-header text-muted small text-uppercase">Export current view</h6></li>
                    <li>
                        <a id="familyIdcardExportExcel"
                           class="dropdown-item d-flex align-items-center gap-2 py-2"
                           href="{{ $exportBase }}?tab=active&format=xlsx"
                           data-base="{{ $exportBase }}">
                            <i class="material-icons material-symbols-rounded text-success" style="font-size:18px;">table_chart</i>
                            Excel
                        </a>
                    </li>
                    <li>
                        <a id="familyIdcardExportPdf"
                           class="dropdown-item d-flex align-items-center gap-2 py-2"
                           href="{{ $exportBase }}?tab=active&format=pdf"
                           data-base="{{ $exportBase }}">
                            <i class="material-icons material-symbols-rounded text-danger" style="font-size:18px;">picture_as_pdf</i>
                            PDF
                        </a>
                    </li>
                </ul>
            </div>
            <a href="{{ route('admin.family_idcard.create') }}" class="btn btn-success  d-flex align-items-center gap-1" title="Add">
                <i class="material-icons material-symbols-rounded" style="font-size:20px;">add</i>
            </a>
            <button type="button" class="btn btn-primary family-idcard-print-btn d-flex align-items-center gap-2 px-3" title="Print" onclick="window.print();" aria-label="Print list">
                <i class="material-icons material-symbols-rounded" style="font-size:20px;">print</i>
                <span class="d-none d-sm-inline">Print</span>
            </button>
        </div>
    </div>

    <!-- Tables -->
    <div class="card border-0 shadow-sm">
        <div class="tab-content">
            <!-- Active Tab -->
            <div class="tab-pane show active" id="active-panel" role="tabpanel" aria-labelledby="active-tab">
                <div class="table-responsive">
                    <table class="table text-nowrap align-middle mb-0 family-idcard-table">
                        <thead>
                            <tr class="table-primary">
                                <th>S.No.</th>
                                <th>Request Date</th>
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                                <th>Designation</th>
                                <th>Department</th>
                                <th>No of Members</th>
                                <th>ID Type</th>
                                    <th class="family-idcard-actions-col">Actions</th>
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
                                    <td class="family-idcard-actions-col">
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('admin.family_idcard.members', $req->first_id) }}" class="btn  btn-outline-primary bg-transparent border-0 text-primary p-0" title="View members"><i class="material-icons material-symbols-rounded" style="font-size:18px;">visibility</i></a>
                                            <a href="{{ route('admin.family_idcard.edit', $req->first_id) }}" class="btn  btn-outline-primary bg-transparent border-0 text-primary p-0" title="Edit"><i class="material-icons material-symbols-rounded" style="font-size:18px;">edit</i></a>
                                            @php
                                                $canArchiveActive = ($req->can_delete ?? true) && (int) ($req->id_status ?? 1) !== 2;
                                                $archiveDisabledTitle = (int) ($req->id_status ?? 1) === 2
                                                    ? 'Approved request cannot be deleted'
                                                    : 'Cannot delete after approval has started';
                                            @endphp
                                            @if($canArchiveActive)
                                                <form action="{{ route('admin.family_idcard.destroy', $req->first_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to archive this request?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger bg-transparent border-0 text-primary p-0" title="Archive"><i class="material-icons material-symbols-rounded" style="font-size:18px;">delete</i></button>
                                                </form>
                                            @else
                                                <button type="button"
                                                        class="btn btn-outline-danger bg-transparent border-0 text-primary p-0"
                                                        title="{{ $archiveDisabledTitle }}"
                                                        disabled>
                                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;">delete</i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-body-secondary">
                                        <i class="material-icons material-symbols-rounded d-block mb-2" style="font-size:48px; opacity:0.4;">inbox</i>
                                        <p class="mb-1">No family ID card requests found.</p>
                                        <a href="{{ route('admin.family_idcard.create') }}" class="btn btn-primary  mt-2">Add Request</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($activeRequests->count() > 0)
                    <div class="d-flex justify-content-between align-items-center px-3 py-3 border-top flex-wrap gap-2 no-print">
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
                                <th>S.No.</th>
                                <th>Request Date</th>
                                <th>Employee ID</th>
                                <th>Employee Name</th>
                                <th>Designation</th>
                                <th>Department</th>
                                <th>No of Members</th>
                                <th>ID Type</th>
                                <th class="family-idcard-actions-col">Actions</th>
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
                                    <td class="family-idcard-actions-col">
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('admin.family_idcard.members', $req->first_id) }}" class="btn  btn-outline-primary bg-transparent border-0 text-primary p-0" title="View members"><i class="material-icons material-symbols-rounded" style="font-size:18px;">visibility</i></a>
                                            @if(($req->id_status ?? 1) === 3)
                                            <form action="{{ route('admin.family_idcard.restore', $req->first_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Restore this request?');">
                                                @csrf
                                                <button type="submit" class="btn  btn-outline-success bg-transparent border-0 text-primary p-0" title="Restore"><i class="material-icons material-symbols-rounded" style="font-size:18px;">restore</i></button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-body-secondary">No archived requests.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($archivedRequests->count() > 0)
                    <div class="d-flex justify-content-between align-items-center px-3 py-3 border-top flex-wrap gap-2 no-print">
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
    /* LBSNAA theme: primary blue */
    .family-idcard-print-btn {
        background-color: #004a93 !important;
        border-color: #004a93 !important;
        color: #fff !important;
    }
    .family-idcard-print-btn:hover {
        background-color: #003d7a !important;
        border-color: #003d7a !important;
        color: #fff !important;
    }

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
    /* Print-only header: hidden on screen */
    .family-idcard-print-header { display: none !important; }
    @media print {
        body * { visibility: hidden !important; }
        .family-idcard-print-area,
        .family-idcard-print-area * { visibility: visible !important; }
        .family-idcard-print-area .no-print,
        .family-idcard-print-area .no-print * { visibility: hidden !important; display: none !important; }
        /* Show only the tab panel that is active when print was triggered (set by beforeprint) */
        .family-idcard-print-area.printing-archive .tab-pane#active-panel { display: none !important; visibility: hidden !important; }
        .family-idcard-print-area.printing-active .tab-pane#archive-panel { display: none !important; visibility: hidden !important; }
        .family-idcard-print-area {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 12px !important;
            background: #fff !important;
        }
        .family-idcard-print-header {
            display: block !important;
            visibility: visible !important;
            margin-bottom: 14px !important;
            padding: 0 0 10px 0 !important;
            border-bottom: 2px solid #004a93 !important;
        }
        .family-idcard-print-header-lbsnaa .family-idcard-print-header-top {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            margin-bottom: 8px !important;
        }
        .family-idcard-print-header-lbsnaa .family-idcard-print-logo {
            height: 44px !important;
            width: auto !important;
            max-width: 140px !important;
            object-fit: contain !important;
        }
        .family-idcard-print-header-lbsnaa .family-idcard-print-title-block h5 {
            font-size: 14px !important;
            color: #004a93 !important;
            font-weight: 700 !important;
            margin: 0 !important;
        }
        .family-idcard-print-header-lbsnaa .family-idcard-print-sub {
            font-size: 12px !important;
            color: #000 !important;
            font-weight: 600 !important;
            margin-top: 2px !important;
        }
        .family-idcard-print-header-lbsnaa .family-idcard-print-meta {
            font-size: 10px !important;
            color: #333 !important;
            display: flex !important;
            justify-content: space-between !important;
            flex-wrap: wrap !important;
            gap: 4px !important;
        }
        .family-idcard-print-header p { font-size: 11px !important; margin: 2px 0 !important; color: #000 !important; }
        .family-idcard-print-area .card { border: 1px solid #000 !important; box-shadow: none !important; background: #fff !important; }
        .family-idcard-print-area .table-responsive { overflow: visible !important; }
        .family-idcard-print-area .family-idcard-table {
            width: 100% !important;
            font-size: 11px !important;
            border-collapse: collapse !important;
            color: #000 !important;
        }
        .family-idcard-print-area .family-idcard-table thead { display: table-header-group !important; }
        .family-idcard-print-area .family-idcard-table th,
        .family-idcard-print-area .family-idcard-table td {
            border: 1px solid #000 !important;
            padding: 5px 6px !important;
            color: #000 !important;
            background: #fff !important;
        }
        .family-idcard-print-area .family-idcard-table .table-primary th {
            background: #e9ecef !important;
            color: #000 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .family-idcard-print-area .family-idcard-table tbody tr { page-break-inside: avoid !important; }
        .family-idcard-print-area .family-idcard-table a { color: #000 !important; text-decoration: none !important; }
        .family-idcard-print-area .family-idcard-actions-col { display: none !important; }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tabs - show Active tab by default
    const activeTabButton = document.getElementById('active-tab');
    if (activeTabButton) {
        const tab = new bootstrap.Tab(activeTabButton);
        tab.show();
    }

    // Auto-submit filter form when per_page changes
    const perPageSelect = document.getElementById('per_page');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    }

    // Before print: set print header and mark which tab to show so only that tab prints
    window.addEventListener('beforeprint', function() {
        var container = document.querySelector('.family-idcard-print-area');
        var infoEl = document.getElementById('familyIdcardPrintTabInfo');
        var activePanel = document.getElementById('active-panel');
        var isActive = activePanel && activePanel.classList.contains('show') && activePanel.classList.contains('active');
        if (container) {
            container.classList.remove('printing-active', 'printing-archive');
            container.classList.add(isActive ? 'printing-active' : 'printing-archive');
        }
        if (infoEl) {
            if (isActive) {
                var first = {{ $activeRequests->firstItem() ?? 0 }};
                var last = {{ $activeRequests->lastItem() ?? 0 }};
                var total = {{ $activeRequests->total() }};
                infoEl.textContent = 'Active: Showing ' + first + ' to ' + last + ' of ' + total + ' entries';
            } else {
                var first = {{ $archivedRequests->firstItem() ?? 0 }};
                var last = {{ $archivedRequests->lastItem() ?? 0 }};
                var total = {{ $archivedRequests->total() }};
                infoEl.textContent = 'Archive: Showing ' + first + ' to ' + last + ' of ' + total + ' entries';
            }
        }
    });
    window.addEventListener('afterprint', function() {
        var container = document.querySelector('.family-idcard-print-area');
        if (container) container.classList.remove('printing-active', 'printing-archive');
    });

    // --- Export: respect currently visible tab (Active / Archive) ---
    (function () {
        var currentTab = 'active';
        var activeTabBtn = document.getElementById('active-tab');
        var archiveTabBtn = document.getElementById('archive-tab');

        function updateExportLinks() {
            var excelLink = document.getElementById('familyIdcardExportExcel');
            var pdfLink = document.getElementById('familyIdcardExportPdf');
            if (!excelLink || !pdfLink) return;

            [excelLink, pdfLink].forEach(function (link) {
                var base = link.getAttribute('data-base');
                if (!base) return;
                try {
                    var url = new URL(base, window.location.origin);
                    url.searchParams.set('tab', currentTab);
                    var fmt = link.id === 'familyIdcardExportPdf' ? 'pdf' : 'xlsx';
                    url.searchParams.set('format', fmt);
                    link.href = url.toString();
                } catch (e) {
                    // ignore
                }
            });
        }

        if (activeTabBtn) {
            activeTabBtn.addEventListener('shown.bs.tab', function () {
                currentTab = 'active';
                updateExportLinks();
            });
        }
        if (archiveTabBtn) {
            archiveTabBtn.addEventListener('shown.bs.tab', function () {
                currentTab = 'archive';
                updateExportLinks();
            });
        }

        updateExportLinks();
    })();
});
</script>
@endsection
