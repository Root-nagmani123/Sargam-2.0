@extends('admin.layouts.master')
@section('title', 'Approval I - Employee ID Card Requests')

@section('content')
<div class="container-fluid idcard-approval1-page py-3">
    <x-breadcrum title="Approval I - Employee ID Card">
        <a href="{{ route('admin.security.employee_idcard_approval.all') }}"
           class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 px-4 rounded-1">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">list</i>
            <span>All Requests</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    {{-- Export sits above the card, matching Approval II / III --}}
    <div class="d-flex justify-content-end align-items-center gap-2 mb-3">
        <div class="dropdown">
            <button class="btn programme-dt-btn-columns dropdown-toggle border-0 text-primary" type="button" id="a1ExportDropdown"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Export">
                <i class="bi bi-download" aria-hidden="true"></i> <span>Export</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2" aria-labelledby="a1ExportDropdown">
                <li><h6 class="dropdown-header text-muted small text-uppercase">Export with current filters</h6></li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                       href="{{ route('admin.security.employee_idcard_approval.export', array_merge(request()->query(), ['stage' => '1', 'format' => 'xlsx'])) }}">
                        <i class="bi bi-file-earmark-spreadsheet text-success" aria-hidden="true"></i> Download Excel
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                       href="{{ route('admin.security.employee_idcard_approval.export', array_merge(request()->query(), ['stage' => '1', 'format' => 'pdf'])) }}">
                        <i class="bi bi-file-earmark-pdf text-danger" aria-hidden="true"></i> Download PDF
                    </a>
                </li>
            </ul>
        </div>
    </div>

    {{-- Laravel paginates this list, so the global DataTables enhancer must keep its
         hands off the hand-written footer below (honoured on any ancestor). --}}
    <div class="card overflow-hidden rounded-3" data-sargam-dt-ui="false">
        <div class="card-body p-3 p-md-4">

            <form method="GET" action="{{ route('admin.security.employee_idcard_approval.approval1') }}" id="a1FilterForm"
                  class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">

                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>

                    <div class="programme-dt-filter-select">
                        <input type="date" name="date_from" class="form-control" aria-label="Request date from"
                               value="{{ request('date_from') }}">
                    </div>
                    <div class="programme-dt-filter-select">
                        <input type="date" name="date_to" class="form-control" aria-label="Request date to"
                               value="{{ request('date_to') }}">
                    </div>

                    <a href="{{ route('admin.security.employee_idcard_approval.approval1') }}"
                       class="btn programme-dt-btn-reset">Reset Filters</a>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <div class="programme-dt-search">
                        <input type="text" name="search" class="form-control" placeholder="Search"
                               aria-label="Search requests" value="{{ request('search', '') }}">
                    </div>
                    <button type="submit" class="btn btn-primary rounded-1 px-3">Search</button>
                </div>

                <input type="hidden" name="per_page" id="a1PerPageProxy" value="{{ request('per_page', 10) }}">
            </form>

            @include('admin.security.employee_idcard_approval._approval_table', ['requests' => $requests, 'approvalStage' => 1])

            {{-- Footer variant B: Laravel paginator, reusing the DataTables class
                 names so the programme-dt footer styling applies unchanged. --}}
            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                <div class="programme-dt-pagination">
                    {{ $requests->withQueryString()->links('vendor.pagination.custom') }}
                </div>
                <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <div class="dataTables_length">
                        <label class="mb-0">Showing
                            <select id="a1PerPage" class="form-select form-select-sm" aria-label="Rows per page">
                                @foreach([10, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" {{ (int) request('per_page', 10) === $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="dataTables_info">of {{ number_format($requests->total()) }} items</div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold">Rejection Reason</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body pt-0">
                    <p class="text-muted small" id="rejectModalEmployeeName"></p>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Enter Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-1 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-1 px-4">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.reject-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('rejectModalEmployeeName').textContent = 'Rejecting: ' + (this.dataset.name || '');
        document.getElementById('rejectForm').action = this.dataset.url || '#';
        document.getElementById('rejection_reason').value = '';
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    });
});

// The rows-per-page control lives in the footer; mirror it into the filter form
// so changing it keeps the current search and date filters.
document.getElementById('a1PerPage')?.addEventListener('change', function () {
    document.getElementById('a1PerPageProxy').value = this.value;
    document.getElementById('a1FilterForm').submit();
});
</script>
@endpush
