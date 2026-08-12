@extends('admin.layouts.master')
@section('title','Bank Details Report')
@section('setup_content')
<div class="container-fluid px-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0" style="color:#1a3c6e;"><i class="bi bi-bank me-2"></i>Bank Details Report</h4>
        <div class="d-flex gap-2">
            @include('fc.report.partials.scoped-form-back', ['scopedForm' => $scopedForm ?? null])
            <a href="{{ route('admin.reports.export','bank') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="btn btn-sm btn-success">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
            </a>
        </div>
    </div>

    <form method="GET" class="card border-0 shadow-sm mb-3 px-3 py-2">
        <div class="row g-2 align-items-end">
            @include('fc.report.partials.form-filter-select', ['forms' => $forms])
            <div class="col-md-2">
                <label class="form-label small mb-1">Status</label>
                <select name="bank_status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="filled"  {{ request('bank_status')=='filled'?'selected':'' }}>Filled</option>
                    <option value="missing" {{ request('bank_status')=='missing'?'selected':'' }}>Not Filled</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Name / Username"
                           value="{{ request('search') }}">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
                    <a href="{{ route('admin.reports.bank') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x"></i></a>
                </div>
            </div>
        </div>
    </form>

    <div class="card border-0 shadow-sm" style="border-radius:8px;">
        <div class="card-header bg-white border-bottom py-2 px-3 small fw-semibold">
            {{ $totalStudents }} students
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" style="font-size:12px;" id="bankDetailsTable">
                <thead class="table-dark">
                    <tr><th class="px-3">#</th><th>User ID</th><th>Full Name</th><th>Service</th><th>Bank Name</th><th>IFSC</th><th>Account No</th><th>Holder Name</th></tr>
                </thead>
                {{-- Rows come from the server-side DataTable (see script below). --}}
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    // Server-side: search, sort and paging are resolved in SQL. The page's own filter
    // form submits as a normal GET, so its values ride along in the URL.
    $('#bankDetailsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ request()->fullUrl() }}",
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'px-3' },
            { data: 'user_id_cell', name: 'user_id', orderable: false },
            { data: 'full_name', name: 'full_name', orderable: false },
            { data: 'service', name: 'service_code', orderable: false, searchable: false },
            { data: 'bank_name', name: 'b.bank_name', orderable: false },
            { data: 'ifsc', name: 'b.ifsc_code', orderable: false },
            { data: 'account', name: 'b.account_no', orderable: false },
            { data: 'account_holder_name', name: 'b.account_holder_name', orderable: false }
        ],
        order: [],
        pageLength: 50,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: 'Loading data…',
            emptyTable: 'No records found.'
        }
    });
});
</script>
@endpush
