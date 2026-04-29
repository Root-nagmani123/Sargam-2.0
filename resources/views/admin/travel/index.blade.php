@extends('admin.layouts.master')
@section('title', 'FC Travel Plans')

@section('setup_content')
<style>
    .travel-report-enhanced {
        font-size: 1.02rem;
    }
    .travel-report-enhanced .page-title {
        font-size: 1.45rem;
        font-weight: 700;
        color: #163b6d;
    }
    .travel-report-enhanced .container-fluid {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
    .travel-report-enhanced .summary-value {
        font-size: 2rem;
        font-weight: 800;
    }
    .travel-report-enhanced .summary-icon {
        font-size: 2rem !important;
    }
    .travel-report-enhanced .summary-label {
        font-size: .9rem;
        font-weight: 600;
    }
    .travel-report-enhanced .form-label {
        font-size: .88rem;
        font-weight: 700;
        color: #163b6d;
    }
    .travel-report-enhanced .form-select-sm,
    .travel-report-enhanced .form-control-sm {
        font-size: .9rem;
    }
    .travel-report-enhanced .table {
        font-size: 13px !important;
    }
    .travel-report-enhanced .dataTables_wrapper .row:first-child {
        margin-top: .75rem;
    }
</style>
<div class="travel-report-enhanced">
<div class="container-fluid px-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0 page-title">
            <i class="bi bi-train-front me-2"></i>FC Travel Plans
        </h4>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.travel.slots.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-clock-history me-1"></i>Manage arrival slots
            </a>
            <button type="button" class="btn btn-sm btn-success" id="btnExportJoining" title="Download styled Excel (.xlsx) with current filters">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Excel (filters)
            </button>
        </div>
    </div>

    <div class="row g-2 mb-3">
        @foreach([
            ['Total Plans', $summary['total'], 'bi-people-fill', '#1a3c6e'],
            ['Submitted', $summary['submitted'], 'bi-send-check-fill', '#198754'],
            ['Vehicle Required (Yes)', $summary['vehicle_yes'], 'bi-car-front-fill', '#0891b2'],
            ['Vehicle Required (No)', $summary['vehicle_no'], 'bi-car-front', '#d97706'],
        ] as [$l, $v, $ic, $c])
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center py-3" style="border-radius:8px;">
                    <i class="bi {{ $ic }} mb-1 summary-icon" style="color:{{ $c }}"></i>
                    <div class="summary-value" style="color:{{ $c }}">{{ $v }}</div>
                    <div class="small text-muted summary-label">{{ $l }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm mb-3 px-3 py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-0">Session</label>
                <select id="f_session_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}">{{ $s->session_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Slot</label>
                <select id="f_slot_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($slots as $sl)
                        <option value="{{ $sl->id }}">{{ $sl->slot_label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Mode</label>
                <select id="f_mode" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($modes as $m)
                        <option value="{{ $m }}">{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Academy vehicle</label>
                <select id="f_vehicle" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Arrival from</label>
                <input type="date" id="f_date_from" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Arrival to</label>
                <input type="date" id="f_date_to" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-sm btn-primary" id="btnApplyFilters">Apply filters</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnResetFilters">Reset</button>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:8px;">
        <div class="table-responsive">
            {!! $dataTable->table(['class' => 'table table-hover table-sm mb-0']) !!}
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
(function () {
    function applyAndReload() {
        if (window.jQuery && $.fn.DataTable && $.fn.DataTable.isDataTable('#fcTravelPlanReportTable')) {
            $('#fcTravelPlanReportTable').DataTable().ajax.reload();
        }
    }
    document.getElementById('btnApplyFilters')?.addEventListener('click', applyAndReload);
    document.getElementById('btnResetFilters')?.addEventListener('click', function () {
        document.getElementById('f_session_id').value = '';
        document.getElementById('f_slot_id').value = '';
        document.getElementById('f_mode').value = '';
        document.getElementById('f_vehicle').value = '';
        document.getElementById('f_date_from').value = '';
        document.getElementById('f_date_to').value = '';
        applyAndReload();
    });
    document.getElementById('btnExportJoining')?.addEventListener('click', function () {
        const p = new URLSearchParams();
        p.set('filter_session_id', document.getElementById('f_session_id')?.value || '');
        p.set('filter_slot_id', document.getElementById('f_slot_id')?.value || '');
        p.set('filter_mode', document.getElementById('f_mode')?.value || '');
        p.set('filter_vehicle', document.getElementById('f_vehicle')?.value || '');
        p.set('date_from', document.getElementById('f_date_from')?.value || '');
        p.set('date_to', document.getElementById('f_date_to')?.value || '');
        window.location.href = '{{ route('admin.travel.export.joining') }}?' + p.toString();
    });
})();
    </script>
@endpush
