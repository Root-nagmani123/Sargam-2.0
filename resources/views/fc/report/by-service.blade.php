{{-- resources/views/fc/report/by-service.blade.php --}}
@extends('admin.layouts.master')
@section('title','Service-wise Report')
@section('setup_content')
@include('admin.partials.choices-bootstrap5')
<div class="container-fluid px-3 choices-bs-scope">

    {{-- Page header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="fw-bold mb-0" style="color:#1a3c6e;">
            <i class="bi bi-briefcase me-2"></i>Service-wise Report
        </h4>
        <div class="d-flex gap-2 align-items-center">
            @include('fc.report.partials.scoped-form-back', ['scopedForm' => $scopedForm ?? null])
            <a href="{{ route('admin.reports.export', 'service') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}"
               class="btn btn-sm btn-success">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" id="byServiceFilterForm" class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3 px-3">
            <div class="row g-3 align-items-end">
                @include('fc.report.partials.form-filter-select', [
                    'forms'    => $forms,
                    'selectId' => 'filter_form_id',
                    'colClass' => 'col-md-3 col-sm-6',
                ])

                <div class="col-md-4 col-sm-6">
                    <label class="form-label small mb-1 fw-semibold text-secondary">
                        <i class="bi bi-briefcase me-1"></i>Service
                    </label>
                    <select name="service_ids[]" id="filter_service_id"
                            class="form-select form-select-sm"
                            data-placeholder="All Services" multiple>
                        @foreach($services as $sv)
                            @php $serviceKey = $sv->pk ?? $sv->id; @endphp
                            <option value="{{ $serviceKey }}"
                                {{ in_array((string) $serviceKey,
                                    collect((array) request('service_ids', []))->map(fn($v) => (string) $v)->all(),
                                    true) ? 'selected' : '' }}>
                                {{ $sv->service_short_name ?? $sv->service_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-auto d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary px-3">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                    <button type="button" id="btnResetFilters" class="btn btn-sm btn-outline-secondary px-3">
                        <i class="bi bi-x-circle me-1"></i>Reset
                    </button>
                </div>
            </div>

            {{-- Selected service chips --}}
            <div id="serviceSelectedChips" class="mt-2 d-flex flex-wrap gap-1"></div>
        </div>
    </form>

    {{-- Table --}}
    <div class="card border-0 shadow-sm" style="border-radius:8px;">
        <div class="table-responsive">
            <table id="byServiceReportTable"
                   class="table table-hover table-sm mb-0"
                   style="font-size:13px;">
                <thead class="table-dark">
                    <tr>
                        <th style="width:48px;" class="text-center">#</th>
                        <th>Service</th>
                        <th class="text-center" style="width:90px;">Total</th>
                        <th class="text-center" style="width:80px;">Male</th>
                        <th class="text-center" style="width:80px;">Female</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
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
    background: #1a3c6e;
    color: #fff;
    border-radius: 999px;
    font-size: 11px;
    line-height: 1;
    padding: 5px 10px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
#serviceSelectedChips .chip button {
    border: 0;
    background: transparent;
    color: rgba(255,255,255,.8);
    font-size: 13px;
    line-height: 1;
    cursor: pointer;
    padding: 0;
}
#serviceSelectedChips .chip button:hover {
    color: #fff;
}
#byServiceReportTable td {
    vertical-align: middle;
    padding-top: 8px;
    padding-bottom: 8px;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !$.fn.DataTable) return;

    const $form    = $('#filter_form_id');
    const $service = $('#filter_service_id');
    const $chips   = $('#serviceSelectedChips');

    if (typeof window.initChoicesBootstrap5In === 'function') {
        window.initChoicesBootstrap5In(document.querySelector('.choices-bs-scope'));
    }

    // Restore form dropdown selection from URL (Choices.js can override the HTML selected attr on init)
    const urlFormId = new URLSearchParams(window.location.search).get('form_id');
    if (urlFormId) {
        const formEl = $form.get(0);
        if (formEl && formEl._choicesBs) {
            formEl._choicesBs.setChoiceByValue(urlFormId);
        } else {
            $form.val(urlFormId);
        }
    }

    const serviceEl = $service.get(0);
    if (serviceEl && serviceEl._choicesBs) {
        serviceEl._choicesBs.config.renderSelectedChoices = 'auto';
    }

    function renderChips() {
        const selected = Array.from($service.find('option:selected'))
            .filter(function (opt) { return (opt.value || '').trim() !== ''; });
        $chips.html(selected.map(function (opt) {
            const label = $('<div>').text(opt.textContent.trim()).html();
            const value = $('<div>').text(opt.value).html();
            return '<span class="chip">' + label +
                '<button type="button" class="js-remove-chip" data-value="' + value + '">&#215;</button>' +
                '</span>';
        }).join(''));
    }

    function stripInlineChoices() {
        const wrap = document.querySelector('#filter_service_id + .choices');
        if (wrap) {
            wrap.querySelectorAll('.choices__list--multiple .choices__item').forEach(function (el) { el.remove(); });
        }
    }

    function applySelection(values) {
        const norm = (values || []).map(String);
        if (serviceEl && serviceEl._choicesBs) {
            serviceEl._choicesBs.removeActiveItems();
            if (norm.length) serviceEl._choicesBs.setChoiceByValue(norm);
        } else {
            $service.find('option').prop('selected', false);
            norm.forEach(function (v) {
                $service.find('option[value="' + v.replace(/"/g, '\\"') + '"]').prop('selected', true);
            });
        }
    }

    function deselect(val) {
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
        searching:  true,
        ajax: {
            url:  "{{ route('admin.reports.service') }}",
            data: function (d) {
                d.form_id     = $form.val()    || '';
                d.service_ids = $service.val() || [];
            }
        },
        order: [[1, 'asc']],
        columns: [
            {
                data: null, name: 'rownum', orderable: false, searchable: false, className: 'text-center text-muted',
                render: function (data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; }
            },
            { data: 'service_name', name: 'service_name', defaultContent: '—' },
            { data: 'total',  name: 'total',  className: 'text-center fw-bold',           defaultContent: 0 },
            { data: 'male',   name: 'male',   className: 'text-center text-primary',      defaultContent: 0 },
            { data: 'female', name: 'female', className: 'text-center text-danger',       defaultContent: 0 },
        ],
        language: { emptyTable: 'No records found.' },
    });

    $('#byServiceFilterForm').on('submit', function (e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $form.on('change', function () { table.ajax.reload(); });

    $service.on('change', function () {
        stripInlineChoices();
        renderChips();
        table.ajax.reload();
    });

    $chips.on('click', '.js-remove-chip', function () {
        const val = String($(this).data('value'));
        deselect(val);
        const remaining = (($service.val() || []).map(String)).filter(function (v) { return v !== val; });
        applySelection(remaining);
        $service.trigger('change');
    });

    $('#btnResetFilters').on('click', function () {
        $form.val('');
        if (serviceEl && serviceEl._choicesBs) serviceEl._choicesBs.removeActiveItems();
        $service.find('option').prop('selected', false);
        renderChips();
        table.search('').ajax.reload();
    });

    stripInlineChoices();
    renderChips();
});
</script>
@endpush
