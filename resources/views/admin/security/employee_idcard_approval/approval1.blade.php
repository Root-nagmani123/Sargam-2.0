@extends('admin.layouts.master')
@section('title', 'Approval I - Employee ID Card Requests')
@section('setup_content')
<div class="container-fluid">
    @include('components.breadcrum', ['title' => 'Approval I - Employee ID Card'])
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <h4 class="mb-0">Approval I</h4>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                   {{-- <a href="{{ route('admin.security.employee_idcard_approval.approval2') }}" class="btn btn-outline-primary btn-sm">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">arrow_forward</i>
                        Approval II
                    </a>
                    <a href="{{ route('admin.security.employee_idcard_approval.all') }}" class="btn btn-secondary btn-sm">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">list</i>
                        All Requests
                    </a> --}}
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light border-bottom">
                    <h6 class="mb-0 fw-semibold">Filters & Search</h6>
                </div>
                <div class="card-body">
                    <form id="approval1FilterForm" class="mb-0" onsubmit="return false;">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="approval1SearchFilter" class="form-label">Search</label>
                                <input type="text" name="search" id="approval1SearchFilter" class="form-control" placeholder="Search by Employee Name, ID Card No..." value="{{ request('search', '') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Request Date From</label>
                                <input type="date" name="date_from" id="approval1DateFromFilter" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Request Date To</label>
                                <input type="date" name="date_to" id="approval1DateToFilter" class="form-control" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-2 d-flex gap-2">
                                <button type="button" id="approval1FilterApply" class="btn btn-primary flex-grow-1">
                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;">search</i> Search
                                </button>
                                <button type="button" id="approval1FilterClear" class="btn btn-outline-secondary">
                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;">restart_alt</i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Export Buttons -->
            <div class="d-flex justify-content-end gap-2 mb-3">
                <div class="dropdown">
                    <button class="btn btn-outline-success dropdown-toggle d-flex align-items-center gap-2 px-3 py-2" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">download</i>
                        Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2" aria-labelledby="exportDropdown">
                        <li><h6 class="dropdown-header text-muted small text-uppercase">Export with Current Filters</h6></li>
                        <li><a id="exportExcelLink" class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.security.employee_idcard_approval.export', array_merge(['stage' => '1', 'format' => 'xlsx'], request()->query())) }}"><i class="material-icons material-symbols-rounded text-success" style="font-size:18px;">table_chart</i> Excel</a></li>
                        <li><a id="exportPdfLink" class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('admin.security.employee_idcard_approval.export', array_merge(['stage' => '1', 'format' => 'pdf'], request()->query())) }}"><i class="material-icons material-symbols-rounded text-danger" style="font-size:18px;">picture_as_pdf</i> PDF</a></li>
                    </ul>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                {!! $dataTable->table(['class' => 'table text-nowrap align-middle mb-0']) !!}
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rejection Reason</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small" id="rejectModalEmployeeName"></p>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Enter Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
{!! $dataTable->scripts() !!}
<script>
$(document).on('preXhr.dt', '#approval1Table', function (e, settings, data) {
    data.search_filter = $('#approval1SearchFilter').val() || '';
    data.date_from_filter = $('#approval1DateFromFilter').val() || '';
    data.date_to_filter = $('#approval1DateToFilter').val() || '';
});

$(document).ready(function () {
    var $table = $('#approval1Table');

    function reload() {
        if ($.fn.DataTable.isDataTable('#approval1Table')) {
            $table.DataTable().ajax.reload();
        }
    }

    function updateExportLinks() {
        var search = $('#approval1SearchFilter').val() || '';
        var dateFrom = $('#approval1DateFromFilter').val() || '';
        var dateTo = $('#approval1DateToFilter').val() || '';

        ['exportExcelLink', 'exportPdfLink'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) {
                return;
            }
            var url = new URL(el.href, window.location.origin);
            search ? url.searchParams.set('search', search) : url.searchParams.delete('search');
            dateFrom ? url.searchParams.set('date_from', dateFrom) : url.searchParams.delete('date_from');
            dateTo ? url.searchParams.set('date_to', dateTo) : url.searchParams.delete('date_to');
            el.href = url.toString();
        });
    }

    $('#approval1FilterApply').on('click', function () {
        updateExportLinks();
        reload();
    });

    $('#approval1SearchFilter').on('keypress', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            updateExportLinks();
            reload();
        }
    });

    $('#approval1FilterClear').on('click', function () {
        $('#approval1FilterForm')[0].reset();
        updateExportLinks();
        reload();
    });

    updateExportLinks();
});

// Reject button opens the modal. Delegated on document since rows are re-rendered by the DataTable ajax reload.
document.addEventListener('click', function (event) {
    var btn = event.target.closest('.reject-btn');
    if (!btn) {
        return;
    }
    document.getElementById('rejectModalEmployeeName').textContent = 'Rejecting: ' + (btn.dataset.name || '');
    document.getElementById('rejectForm').action = btn.dataset.url || '#';
    document.getElementById('rejection_reason').value = '';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
});
</script>
@endsection
