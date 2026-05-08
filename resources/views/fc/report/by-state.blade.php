@extends('admin.layouts.master')
@section('title','State-wise Report')
@section('setup_content')
@include('admin.partials.choices-bootstrap5')
<div class="container-fluid px-3 choices-bs-scope">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0" style="color:#1a3c6e;"><i class="bi bi-geo-alt me-2"></i>State / Cadre-wise Report</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.reports.overview') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
            <a href="{{ route('admin.reports.export','state') }}" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV</a>
        </div>
    </div>

    <form method="GET" id="byStateFilterForm" class="card border-0 shadow-sm mb-3 px-3 py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Session</label>
                <select name="session_id" id="filter_state_session_id" class="form-select form-select-sm" data-placeholder="All Sessions">
                    <option value="">All Sessions</option>
                    @foreach($sessions as $s)<option value="{{ $s->id }}" {{ request('session_id')==$s->id?'selected':'' }}>{{ $s->session_name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">State</label>
                <select name="state_ids[]" id="filter_state_id" class="form-select form-select-sm" data-placeholder="All States" multiple>
                    @foreach($states as $st)
                        <option value="{{ $st->pk }}"
                            {{ in_array((string) $st->pk, collect((array) request('state_ids', []))->map(fn($v) => (string) $v)->all(), true) ? 'selected' : '' }}>
                            {{ $st->state_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary px-3">Filter</button></div>
            <div class="col-auto"><button type="button" id="btnResetStateFilters" class="btn btn-sm btn-outline-secondary px-3">Reset</button></div>
        </div>
    </form>

    <div class="card border-0 shadow-sm" style="border-radius:8px;">
        <div class="table-responsive">
            <table id="byStateReportTable" class="table table-hover table-sm mb-0" style="font-size:12px;">
                <thead class="table-dark">
                    <tr><th>#</th><th>State / Cadre</th><th>Code</th><th class="text-center">Total</th><th class="text-center">Male</th><th class="text-center">Female</th><th class="text-center">Submitted</th></tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_paginate,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_length {
        padding-top: 8px;
        padding-bottom: 8px;
    }
    .dataTables_wrapper .dataTables_filter input {
        margin-left: 6px;
        padding: 4px 8px;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 4px 10px !important;
        margin: 0 2px;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !$.fn.DataTable) return;
    const $session = $('#filter_state_session_id');
    const $state = $('#filter_state_id');

    if (typeof window.initChoicesBootstrap5In === 'function') {
        window.initChoicesBootstrap5In(document.querySelector('.choices-bs-scope'));
    }

    const table = $('#byStateReportTable').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        ajax: {
            url: "{{ route('admin.reports.state') }}",
            data: function (d) {
                d.session_id = $session.val() || '';
                d.state_ids = $state.val() || [];
            }
        },
        order: [[1, 'asc']],
        columns: [
            { data: null, name: 'rownum', orderable: false, searchable: false, className: 'px-3',
              render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
            { data: 'state_name', name: 'state_name', defaultContent: '—' },
            { data: 'state_code', name: 'state_code', className: 'text-center',
              render: function (data) { return '<span class="badge bg-secondary-subtle text-secondary" style="font-size:10px;">' + (data || '—') + '</span>'; } },
            { data: 'total', name: 'total', className: 'text-center fw-bold', defaultContent: 0 },
            { data: 'male', name: 'male', className: 'text-center text-primary', defaultContent: 0 },
            { data: 'female', name: 'female', className: 'text-center text-danger', defaultContent: 0 },
            { data: 'submitted', name: 'submitted', className: 'text-center text-success', defaultContent: 0 }
        ],
        language: { emptyTable: 'No data.' }
    });

    $('#byStateFilterForm').on('submit', function (e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $session.on('change', function () { table.ajax.reload(); });
    $state.on('change', function () { table.ajax.reload(); });

    $('#btnResetStateFilters').on('click', function () {
        const sessionEl = $session.get(0);
        const stateEl = $state.get(0);
        if (sessionEl && sessionEl._choicesBs) {
            sessionEl._choicesBs.removeActiveItems();
            sessionEl._choicesBs.setChoiceByValue('');
        } else {
            $session.val('');
        }
        if (stateEl && stateEl._choicesBs) {
            stateEl._choicesBs.removeActiveItems();
        } else {
            $state.val('');
        }
        $state.find('option').prop('selected', false);
        $session.trigger('change');
        $state.trigger('change');
        table.search('').ajax.reload();
    });
});
</script>
@endpush
