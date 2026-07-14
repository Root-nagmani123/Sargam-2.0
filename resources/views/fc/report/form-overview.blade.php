@extends('admin.layouts.master')
@section('title', $form->form_name . ' — Report')

@push('styles')
<style>
    #foColMenu { max-height: 320px; overflow-y: auto; min-width: 220px; }
    #foColMenu .form-check-label { cursor: pointer; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid px-3">

    {{-- Page header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0" style="color:#1a3c6e;">
                <i class="bi bi-bar-chart-line me-2"></i>{{ $form->form_name }}
            </h4>
            <small class="text-muted">
                <code>{{ $form->form_slug }}</code> &nbsp;·&nbsp;
                {{ $totalSteps }} trackable step{{ $totalSteps !== 1 ? 's' : '' }} &nbsp;·&nbsp;
                Tracker table: <code>{{ $form->trackerStorageTable() }}</code>
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @php
                $formScopeQuery = http_build_query(['form_id' => $form->id]);
                $exportQuery = request()->getQueryString();
            @endphp
            <a href="{{ route('admin.reports.service') }}?{{ $formScopeQuery }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-briefcase me-1"></i>By Service</a>
            <a href="{{ route('admin.reports.state') }}?{{ $formScopeQuery }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-geo-alt me-1"></i>By State</a>
            <a href="{{ route('admin.reports.documents') }}?{{ $formScopeQuery }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-check me-1"></i>Documents</a>
            <a href="{{ route('admin.reports.bank') }}?{{ $formScopeQuery }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-bank me-1"></i>Bank Details</a>
            <a href="{{ route('admin.travel.index') }}?{{ $formScopeQuery }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-train-front me-1"></i>Travel Plans</a>
            <a href="{{ route('admin.reports.form.export', $form) }}" id="fcExportCsvBtn" data-base="{{ route('admin.reports.form.export', $form) }}" class="btn btn-sm btn-success">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
            </a>
            <a href="{{ route('admin.reports.form.export.pdf-zip', $form) }}" id="fcBulkZipBtn" data-base="{{ route('admin.reports.form.export.pdf-zip', $form) }}" class="btn btn-sm btn-danger"
               title="Download a ZIP of every listed student's registration profile PDF (respects the current filters)"
               onclick="(function(a){var i=a.querySelector('i'),o=i.className;i.className='spinner-border spinner-border-sm me-1';a.style.pointerEvents='none';a.style.opacity='.7';setTimeout(function(){i.className=o;a.style.pointerEvents='';a.style.opacity='';},8000);})(this);">
                <i class="bi bi-file-earmark-zip me-1"></i>Bulk PDF (ZIP)
            </a>
            <a href="{{ route('fc-reg.admin.forms.edit', $form) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-pencil-square me-1"></i>Edit Form
            </a>
            <a href="{{ route('fc-reg.admin.forms.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>All Forms
            </a>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-2 mb-3">
        {{-- Fixed cards --}}
        <div class="col-6 col-sm-4 col-md-3 col-xl-auto" style="flex:1 1 100px">
            <a href="{{ request()->url() }}?{{ http_build_query(array_merge(request()->except('status','page'), [])) }}"
               class="text-decoration-none">
            <div class="card border-0 shadow-sm text-center py-2 px-2 h-100" style="border-radius:8px;">
                <div><i class="bi bi-people-fill fs-4" style="color:#1a3c6e;"></i></div>
                <div class="fw-bold" style="font-size:1.3rem;color:#1a3c6e;">{{ number_format($summary['total']) }}</div>
                <div class="text-muted" style="font-size:.68rem;line-height:1.2;">Total Registered</div>
            </div></a>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-auto" style="flex:1 1 100px">
            <a href="{{ request()->url() }}?{{ http_build_query(array_merge(request()->except('status','page'), ['status'=>'COMPLETE'])) }}"
               class="text-decoration-none">
            <div class="card border-0 shadow-sm text-center py-2 px-2 h-100" style="border-radius:8px; {{ request('status')==='COMPLETE' ? 'border:2px solid #059669 !important;' : '' }}">
                <div><i class="bi bi-check-circle-fill fs-4" style="color:#059669;"></i></div>
                <div class="fw-bold" style="font-size:1.3rem;color:#059669;">{{ number_format($summary['complete']) }}</div>
                <div class="text-muted" style="font-size:.68rem;line-height:1.2;">Complete</div>
            </div></a>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-auto" style="flex:1 1 100px">
            <a href="{{ request()->url() }}?{{ http_build_query(array_merge(request()->except('status','page'), ['status'=>'INCOMPLETE'])) }}"
               class="text-decoration-none">
            <div class="card border-0 shadow-sm text-center py-2 px-2 h-100" style="border-radius:8px; {{ request('status')==='INCOMPLETE' ? 'border:2px solid #d97706 !important;' : '' }}">
                <div><i class="bi bi-hourglass-split fs-4" style="color:#d97706;"></i></div>
                <div class="fw-bold" style="font-size:1.3rem;color:#d97706;">{{ number_format($summary['incomplete']) }}</div>
                <div class="text-muted" style="font-size:.68rem;line-height:1.2;">Incomplete</div>
            </div></a>
        </div>
        {{-- One card per step --}}
        @foreach($steps as $step)
        <div class="col-6 col-sm-4 col-md-3 col-xl-auto" style="flex:1 1 100px">
            <div class="card border-0 shadow-sm text-center py-2 px-2 h-100" style="border-radius:8px;">
                <div><i class="bi {{ $step->icon ?? 'bi-check-circle-fill' }} fs-4" style="color:#7c3aed;"></i></div>
                <div class="fw-bold" style="font-size:1.3rem;color:#7c3aed;">
                    {{ number_format($summary[$step->tracker_column] ?? 0) }}
                </div>
                <div class="text-muted" style="font-size:.68rem;line-height:1.2;">{{ Str::limit($step->step_name, 18) }}</div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:8px;">
        <div class="card-body py-2 px-3">
            <form onsubmit="return false;" class="row g-2 align-items-end fc-overview-filter-form">
                <div class="col-sm-6 col-md-3 col-lg-2">
                    <label class="form-label small mb-1">Status</label>
                    <select id="f_status" name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="COMPLETE"   {{ request('status')=='COMPLETE'  ?'selected':'' }}>Complete</option>
                        <option value="INCOMPLETE" {{ request('status')=='INCOMPLETE'?'selected':'' }}>Incomplete</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-2">
                    <label class="form-label small mb-1">Service</label>
                    <select id="f_service_id" name="service_id" class="form-select form-select-sm">
                        <option value="">All Services</option>
                        @foreach($services as $sv)
                            @php $key = $sv->pk ?? $sv->id; @endphp
                            <option value="{{ $key }}" {{ (string)request('service_id') === (string)$key ? 'selected' : '' }}>
                                {{ $sv->service_short_name ?? $sv->service_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <label class="form-label small mb-1">Search</label>
                    <div class="input-group input-group-sm">
                        <input type="text" id="f_search" name="search" class="form-control"
                               placeholder="Name / Username / Mobile"
                               value="{{ request('search') }}">
                        <button type="button" id="fcOverviewSearchBtn" class="btn btn-primary btn-sm px-2"><i class="bi bi-search"></i></button>
                        <button type="button" id="fcOverviewClearBtn" class="btn btn-outline-secondary btn-sm px-2"><i class="bi bi-x"></i></button>
                    </div>
                </div>
                <div class="col-sm-6 col-md-2 col-lg-2 ms-lg-auto text-lg-end">
                    <label class="form-label small mb-1 d-none d-lg-block">&nbsp;</label>
                    <div class="dropdown" id="foColDropdown">
                        <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle d-inline-flex align-items-center gap-1"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Show / hide columns">
                            <i class="bi bi-layout-three-columns"></i> Columns
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end py-2" id="foColMenu"></ul>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table (Yajra DataTable — server-side AJAX) --}}
    <div class="card border-0 shadow-sm" style="border-radius:8px;">
        <div class="card-body p-3">
            <div class="table-responsive">
                {!! $dataTable->table(['class' => 'table table-hover table-sm mb-0', 'style' => 'font-size:12px;width:100%;', 'data-sargam-dt-ui' => 'false']) !!}
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
$(function () {
    var ajaxUrl = "{{ route('admin.reports.form', $form) }}";

    function fStatus()  { return $('#f_status').val() || ''; }
    function fService() { return $('#f_service_id').val() || ''; }
    function fSearch()  { return $('#f_search').val() || ''; }

    var table = $('#fcFormOverviewTable').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        ordering: true,
        order: [],
        autoWidth: false,
        responsive: false,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        language: {
            processing: 'Loading…',
            search: '',
            searchPlaceholder: 'Search',
            lengthMenu: 'Show _MENU_',
            info: 'Showing _START_–_END_ of _TOTAL_ students',
            infoEmpty: 'Showing 0 of 0 students',
            infoFiltered: '',
            emptyTable: 'No students found.',
            zeroRecords: 'No students found.',
            paginate: { previous: '‹', next: '›' }
        },
        ajax: {
            url: ajaxUrl,
            type: 'GET',
            data: function (d) {
                d.f_status     = fStatus();
                d.f_service_id = fService();
                d.f_search     = fSearch();
            }
        },
        columns: [
            { data: 'DT_RowIndex',    name: 'DT_RowIndex',    orderable: false, searchable: false, className: 'text-center', width: '40px' },
            { data: '{{ $userKey }}', name: '{{ $userKey }}', orderable: false },
            { data: 'full_name',      name: 'full_name' },
            { data: 'service_code',   name: 'service_code',   orderable: false, searchable: false },
            { data: 'cadre',          name: 'cadre',          orderable: false, searchable: false },
            { data: 'allotted_state', name: 'allotted_state', orderable: false, searchable: false },
            { data: 'mobile_no',      name: 'mobile_no',      orderable: false },
@foreach($steps as $step)
            { data: '{{ $step->tracker_column }}', name: '{{ $step->tracker_column }}', orderable: false, searchable: false, className: 'text-center' },
@endforeach
            { data: 'progress_bar',   name: 'progress_bar',   orderable: false, searchable: false, className: 'text-center' },
            { data: 'status',         name: 'status',         orderable: false, searchable: false, className: 'text-center' },
            { data: 'action',         name: 'action',         orderable: false, searchable: false, className: 'text-center' }
        ],
        dom: "<'row mb-2 align-items-center'<'col-sm-6'l><'col-sm-6'f>>rt<'row mt-2 align-items-center'<'col-sm-5'i><'col-sm-7'p>>"
    });

    // Keep the CSV / Bulk-ZIP exports in sync with the active filters (they read the
    // status / service_id / search params server-side, same as before).
    function syncExportLinks() {
        var qs = $.param({ status: fStatus(), service_id: fService(), search: fSearch() });
        $('#fcExportCsvBtn, #fcBulkZipBtn').each(function () {
            this.href = $(this).data('base') + '?' + qs;
        });
    }

    // Column show / hide menu (buttons.colVis isn't loaded app-wide, so build our own).
    function buildColMenu() {
        var $menu = $('#foColMenu').empty();
        table.columns().every(function () {
            var col   = this;
            var title = ($(col.header()).text() || '').trim();
            if (!title || title === '#') { return; } // skip the row-index & blank action columns
            var $li = $('<li class="px-3 py-1"><div class="form-check mb-0">' +
                '<input type="checkbox" class="form-check-input me-2"' + (col.visible() ? ' checked' : '') + '>' +
                '<label class="form-check-label">' + title + '</label></div></li>');
            $li.find('input').on('change', function () { col.visible($(this).prop('checked')); });
            $li.find('label').on('click', function (e) {
                e.preventDefault();
                var $cb = $(this).closest('.form-check').find('input');
                $cb.prop('checked', !$cb.prop('checked')).trigger('change');
            });
            $menu.append($li);
        });
    }

    var timer;
    function reload() { syncExportLinks(); table.ajax.reload(); }

    $('#f_status, #f_service_id').on('change', reload);
    $('#f_search').on('keyup', function (e) {
        if (e.which === 13) { reload(); return; }
        clearTimeout(timer);
        timer = setTimeout(reload, 400);
    });
    $('#fcOverviewSearchBtn').on('click', reload);
    $('#fcOverviewClearBtn').on('click', function () {
        $('#f_status, #f_service_id').val('');
        $('#f_search').val('');
        reload();
    });

    syncExportLinks();
    buildColMenu();
});
</script>
@endpush
