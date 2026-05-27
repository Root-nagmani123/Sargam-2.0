@extends('admin.layouts.master')

@section('title', 'Contractual Employee Utility - Sargam')

@section('content')
<div class="container-fluid px-2 px-sm-3 px-md-4 py-2">
    <x-breadcrum :title="'Contractual Employee Utility'" :items="['Home', 'Estate Management', 'Contractual Employee Utility']" />

    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <h2 class="h6 fw-semibold text-body mb-0 d-flex align-items-center gap-2">
                <i class="material-symbols-rounded fs-5">filter_list</i>
                Filters
            </h2>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="filter_employee_name" class="form-label fw-medium">Employee Name</label>
                    <select class="form-select" id="filter_employee_name">
                        <option value="">— All —</option>
                        @foreach($employeeNames ?? [] as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="filter_department" class="form-label fw-medium">Section</label>
                    <select class="form-select" id="filter_department">
                        <option value="">— All —</option>
                        @foreach($departments ?? [] as $dept)
                            <option value="{{ $dept }}">{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label for="filter_bill_month" class="form-label fw-medium">Month</label>
                    <select class="form-select" id="filter_bill_month">
                        <option value="">— All —</option>
                        @foreach($billMonths ?? [] as $m)
                            <option value="{{ $m }}">{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label for="filter_bill_year" class="form-label fw-medium">Year</label>
                    <select class="form-select" id="filter_bill_year">
                        <option value="">— All —</option>
                        @foreach($billYears ?? [] as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary btn-sm" id="btnApplyFilters">Apply</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnResetFilters">Reset</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3">
        <div>
            <h2 class="h4 fw-semibold text-body mb-1">Contractual Employee Utility</h2>
            <p class="text-body-secondary small mb-0">Contractual (Other) — month-wise billing from <code>estate_month_reading_details_other</code></p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2" id="btnPrintContractual">
                <i class="material-symbols-rounded">print</i>
                <span class="d-none d-md-inline">Print</span>
            </button>
            <a href="#" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2" id="btnExcelContractual">
                <i class="material-symbols-rounded">download</i>
                <span class="d-none d-md-inline">Excel</span>
            </a>
            <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2" id="btnPdfContractual">
                <i class="material-symbols-rounded">picture_as_pdf</i>
                <span class="d-none d-md-inline">PDF</span>
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-4">
            <div class="table-responsive">
                {!! $dataTable->table(['class' => 'table text-nowrap align-middle mb-0', 'aria-describedby' => 'contractual-utility-caption']) !!}
            </div>
            <div id="contractual-utility-caption" class="visually-hidden">Contractual employee utility list</div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>.ts-dropdown { z-index: 1060 !important; }</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
{!! $dataTable->scripts() !!}
<script>
(function() {
    var tableId = '#contractualEmployeeUtilityTable';
    var reportTitle = 'Contractual Employee Utility';
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrf ? csrf.getAttribute('content') : '';
    var inlineUrlBase = @json(url('admin/estate/contractual-employee-utility'));
    var exportBase = @json(route('admin.estate.contractual-employee-utility.export'));

    var filterIds = ['filter_employee_name', 'filter_department', 'filter_bill_month', 'filter_bill_year'];
    var tsCommon = {
        allowEmptyOption: true,
        create: false,
        dropdownParent: 'body',
        maxOptions: null,
        hideSelected: false,
        onInitialize: function() { this.activeOption = null; }
    };

    function initFilterTomSelect(el, placeholder) {
        if (!el || typeof TomSelect === 'undefined') return null;
        if (el.tomselect) {
            try { el.tomselect.destroy(); } catch (e) {}
        }
        return new TomSelect(el, Object.assign({}, tsCommon, { placeholder: placeholder || '— All —' }));
    }

    function getFilterVal(id) {
        var el = document.getElementById(id);
        if (el && el.tomselect) {
            var v = el.tomselect.getValue();
            return v ? String(v) : '';
        }
        return $('#' + id).val() || '';
    }

    function clearFilter(id) {
        var el = document.getElementById(id);
        if (el && el.tomselect) {
            el.tomselect.clear(true);
        } else if (el) {
            el.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof TomSelect === 'undefined') return;
        initFilterTomSelect(document.getElementById('filter_employee_name'), 'Search employee…');
        initFilterTomSelect(document.getElementById('filter_department'), 'Search section…');
        initFilterTomSelect(document.getElementById('filter_bill_month'), 'Select month');
        initFilterTomSelect(document.getElementById('filter_bill_year'), 'Select year');
    });

    function getTable() {
        if (!$.fn.DataTable.isDataTable(tableId)) return null;
        return $(tableId).DataTable();
    }

    $(tableId).on('preXhr.dt', function(e, settings, data) {
        data.filter_employee_name = getFilterVal('filter_employee_name');
        data.filter_department = getFilterVal('filter_department');
        data.filter_bill_month = getFilterVal('filter_bill_month');
        data.filter_bill_year = getFilterVal('filter_bill_year');
    });

    $('#btnApplyFilters').on('click', function() {
        var dt = getTable();
        if (dt) dt.ajax.reload(null, false);
    });

    $('#btnResetFilters').on('click', function() {
        filterIds.forEach(clearFilter);
        var dt = getTable();
        if (dt) dt.ajax.reload(null, false);
    });

    function exportQuery() {
        var p = new URLSearchParams();
        filterIds.forEach(function(id) {
            var v = getFilterVal(id);
            if (v) p.set(id, v);
        });
        var q = p.toString();
        return exportBase + (q ? '?' + q : '');
    }

    $('#btnExcelContractual').on('click', function(e) {
        e.preventDefault();
        window.location = exportQuery();
    });

    function buildPrintableTableHtml(tableElement) {
        var clone = tableElement.cloneNode(true);
        clone.classList.remove('dataTable');
        clone.removeAttribute('style');
        clone.querySelectorAll('input.contractual-inline').forEach(function(inp) {
            var td = inp.closest('td');
            if (td) td.textContent = inp.value;
        });
        return clone.outerHTML;
    }

    function openPrintWindow(tableHtml) {
        var win = window.open('', '_blank', 'width=1200,height=900');
        if (!win) {
            alert('Please allow popups to print this list.');
            return;
        }
        win.document.write(
            '<!doctype html><html><head><meta charset="utf-8"><title>' + reportTitle + '</title>' +
            '<style>@page{size:A4 landscape;margin:8mm;}body{font-family:Arial,sans-serif;font-size:10px;}' +
            'h2{text-align:center;font-size:16px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ccc;padding:4px;}</style></head><body>' +
            '<h2>' + reportTitle + '</h2>' + tableHtml + '</body></html>'
        );
        win.document.close();
        setTimeout(function() { win.focus(); win.print(); }, 250);
    }

    function printAllRows() {
        var dt = getTable();
        var el = document.querySelector(tableId);
        if (!dt || !el) return;
        var prevLen = dt.page.len();
        dt.page.len(-1).draw(false);
        setTimeout(function() {
            openPrintWindow(buildPrintableTableHtml(el));
            dt.page.len(prevLen).draw(false);
        }, 400);
    }

    $('#btnPrintContractual, #btnPdfContractual').on('click', printAllRows);

    var inlineTimers = {};
    $(document).on('change blur', '.contractual-inline', function() {
        var $el = $(this);
        var pk = $el.data('pk');
        var field = $el.data('field');
        if (!pk || !field) return;
        var key = pk + ':' + field;
        clearTimeout(inlineTimers[key]);
        inlineTimers[key] = setTimeout(function() {
            fetch(inlineUrlBase + '/' + pk + '/inline', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ field: field, value: $el.val() })
            }).then(function(r) { return r.json(); }).then(function(res) {
                if (!res.success) alert(res.message || 'Could not save.');
            }).catch(function() { alert('Could not save. Please try again.'); });
        }, 400);
    });
})();
</script>
@endpush
