{{-- resources/views/fc/report/by-service.blade.php --}}
@extends('admin.layouts.master')
@section('title','Service-wise Report')
@section('setup_content')
@include('admin.partials.choices-bootstrap5')
<div class="container-fluid px-3 choices-bs-scope">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0" style="color:#1a3c6e;"><i class="bi bi-briefcase me-2"></i>Service-wise Report</h4>
        <div class="d-flex gap-2">
            @include('fc.report.partials.scoped-form-back', ['scopedForm' => $scopedForm ?? null])
            <a href="{{ route('admin.reports.export', 'service') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV</a>
        </div>
    </div>

    <form method="GET" id="byServiceFilterForm" class="card border-0 shadow-sm mb-3 px-3 py-2">
        <div class="row g-2 align-items-end">
            @include('fc.report.partials.form-filter-select', ['forms' => $forms, 'selectId' => 'filter_form_id'])
            <div class="col-md-3">
                <label class="form-label small mb-1">Service</label>
                <select name="service_ids[]" id="filter_service_id" class="form-select form-select-sm" data-placeholder="All Services" multiple>
                    @foreach($services as $sv)
                        @php $serviceKey = $sv->pk ?? $sv->id; @endphp
                        <option value="{{ $serviceKey }}"
                            {{ in_array((string) $serviceKey, collect((array) request('service_ids', []))->map(fn($v) => (string) $v)->all(), true) ? 'selected' : '' }}>
                            {{ $sv->service_short_name ?? $sv->service_name }}
                        </option>
                    @endforeach
                </select>
                <div id="serviceSelectedChips" class="mt-1 d-flex flex-wrap gap-1"></div>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary px-3">Filter</button></div>
            <div class="col-auto"><button type="button" id="btnResetFilters" class="btn btn-sm btn-outline-secondary px-3">Reset</button></div>
        </div>
    </form>

    <div class="card border-0 shadow-sm" style="border-radius:8px;">
        <div class="table-responsive">
            <table id="byServiceReportTable" class="table table-hover table-sm mb-0" style="font-size:12px;">
                <thead class="table-dark">
                    <tr><th>#</th><th>Service</th><th>Code</th><th class="text-center">Total</th><th class="text-center">Male</th><th class="text-center">Female</th><th class="text-center">Submitted</th><th class="text-center">Docs Done</th><th class="text-center">% Done</th></tr>
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
    .choices-bs-scope .choices .choices__inner {
        min-height: 31px;
        padding-top: 0.2rem;
        padding-bottom: 0.2rem;
        font-size: 0.875rem;
    }
    .choices-bs-scope .choices[data-type*="select-multiple"] .choices__inner {
        min-height: 31px;
        max-height: 31px;
        overflow: hidden;
        padding-top: 0.1rem;
        padding-bottom: 0.1rem;
    }
    .choices-bs-scope #filter_service_id + .choices .choices__list--multiple {
        display: none !important;
    }
    .choices-bs-scope #filter_service_id + .choices .choices__input {
        margin: 0 !important;
        padding: 2px 8px !important;
    }
    .choices-bs-scope #filter_service_id + .choices .choices__list--dropdown .choices__item.is-selected,
    .choices-bs-scope #filter_service_id + .choices .choices__list[aria-expanded] .choices__item.is-selected {
        display: none !important;
    }
    #serviceSelectedChips .chip {
        background: #0ea5b9;
        color: #fff;
        border-radius: 999px;
        font-size: 10px;
        line-height: 1;
        padding: 5px 8px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    #serviceSelectedChips .chip button {
        border: 0;
        background: transparent;
        color: #fff;
        font-size: 11px;
        line-height: 1;
        cursor: pointer;
        padding: 0;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !$.fn.DataTable) return;

    const $form = $('#filter_form_id');
    const $service = $('#filter_service_id');
    const $serviceChips = $('#serviceSelectedChips');

    if (typeof window.initChoicesBootstrap5In === 'function') {
        window.initChoicesBootstrap5In(document.querySelector('.choices-bs-scope'));
    }
    const serviceElForChoices = $service.get(0);
    if (serviceElForChoices && serviceElForChoices._choicesBs) {
        serviceElForChoices._choicesBs.config.renderSelectedChoices = 'auto';
    }

    function renderServiceChips() {
        const selected = Array.from($service.find('option:selected')).filter(function (opt) {
            return (opt.value || '').trim() !== '';
        });
        const html = selected.map(function (opt) {
            const label = $('<div>').text(opt.textContent.trim()).html();
            const value = $('<div>').text(opt.value).html();
            return '<span class="chip">' + label +
                '<button type="button" class="js-remove-service-chip" data-value="' + value + '">&times;</button>' +
                '</span>';
        }).join('');
        $serviceChips.html(html);
    }

    function stripInlineServiceChoices() {
        const wrap = document.querySelector('#filter_service_id + .choices');
        if (!wrap) return;
        wrap.querySelectorAll('.choices__list--multiple .choices__item').forEach(function (el) {
            el.remove();
        });
    }

    function applyServiceSelection(values) {
        const normalized = (values || []).map(function (v) { return String(v); });
        const serviceEl = $service.get(0);
        if (serviceEl && serviceEl._choicesBs) {
            serviceEl._choicesBs.removeActiveItems();
            if (normalized.length) {
                serviceEl._choicesBs.setChoiceByValue(normalized);
            }
        } else {
            $service.find('option').prop('selected', false);
            normalized.forEach(function (v) {
                $service.find('option[value="' + v.replace(/"/g, '\\"') + '"]').prop('selected', true);
            });
        }
    }

    function deselectServiceValue(val) {
        const serviceEl = $service.get(0);
        if (serviceEl && serviceEl._choicesBs && typeof serviceEl._choicesBs.removeActiveItemsByValue === 'function') {
            serviceEl._choicesBs.removeActiveItemsByValue(String(val));
        }
        $service.find('option').each(function () {
            if (String(this.value) === String(val)) this.selected = false;
        });
    }

    const table = $('#byServiceReportTable').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        ajax: {
            url: "{{ route('admin.reports.service') }}",
            data: function (d) {
                d.form_id = $form.val() || '';
                d.service_ids = $service.val() || [];
            }
        },
        order: [[1, 'asc']],
        columns: [
            { data: null, name: 'rownum', orderable: false, searchable: false, className: 'px-3',
              render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
            { data: 'service_name', name: 'service_name', defaultContent: '—' },
            { data: 'service_code', name: 'service_code', className: 'text-center',
              render: function (data) { return '<span class="badge bg-primary-subtle text-primary" style="font-size:10px;">' + (data || '—') + '</span>'; } },
            { data: 'total', name: 'total', className: 'text-center fw-bold', defaultContent: 0 },
            { data: 'male', name: 'male', className: 'text-center text-primary', defaultContent: 0 },
            { data: 'female', name: 'female', className: 'text-center text-danger', defaultContent: 0 },
            { data: 'submitted', name: 'submitted', className: 'text-center text-success', defaultContent: 0 },
            { data: 'docs_done', name: 'docs_done', className: 'text-center', defaultContent: 0 },
            { data: 'pct', name: 'pct', orderable: false, searchable: false, className: 'text-center',
              render: function (data) {
                  const pct = Number(data || 0);
                  return '<div class="d-flex align-items-center gap-1">' +
                         '<div class="progress flex-grow-1" style="height:5px;min-width:40px;">' +
                         '<div class="progress-bar bg-success" style="width:' + pct + '%"></div></div>' +
                         '<span style="font-size:10px;">' + pct + '%</span></div>';
              } }
        ],
        language: {
            emptyTable: 'No data found.'
        }
    });

    $('#byServiceFilterForm').on('submit', function (e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $form.on('change', function () { table.ajax.reload(); });
    $service.on('change', function () {
        stripInlineServiceChoices();
        renderServiceChips();
        table.ajax.reload();
    });

    $serviceChips.on('click', '.js-remove-service-chip', function () {
        const val = String($(this).data('value'));
        deselectServiceValue(val);
        const next = (($service.val() || []).map(function (v) { return String(v); }))
            .filter(function (v) { return v !== val; });
        applyServiceSelection(next);
        $service.trigger('change');
    });

    $('#btnResetFilters').on('click', function () {
        $form.val('');
        const serviceEl = $service.get(0);
        if (serviceEl && serviceEl._choicesBs) serviceEl._choicesBs.removeActiveItems();
        else $service.val('');
        $service.find('option').prop('selected', false);
        renderServiceChips();
        $form.trigger('change');
        $service.trigger('change');
        table.search('').ajax.reload();
    });

    stripInlineServiceChoices();
    renderServiceChips();
});
</script>
@endpush
