@extends('admin.layouts.master')

@section('title', 'Update Meter Reading of Other - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Update Meter Reading of Other"></x-breadcrum>
    <x-session_message />

    <div class="card border-0 shadow-sm rounded-3 border-start border-4 border-primary">
        <div class="card-body p-4 p-lg-5">
            <h2 class="h5 fw-semibold mb-1">Update Meter Reading of Other</h2>
            <p class="text-muted small mb-4">Please Update Meter Reading</p>
            <hr class="my-4">

            <form id="meterReadingFilterForm" class="needs-validation" novalidate>
                @csrf
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="bill_month" class="form-label">Bill Month <span class="text-danger">*</span></label>
                        <input type="month" class="form-control" id="bill_month" name="bill_month" placeholder="Select Bill Month" max="{{ date('Y-m') }}" required>
                        <div class="form-text">Select the billing month</div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="estate_name" class="form-label">Estate Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_name" name="estate_name">
                            <option value="">---Select---</option>
                            @foreach($campuses as $c)
                                <option value="{{ $c->pk }}">{{ $c->campus_name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Select Estate Name</div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="unit_name" class="form-label">Unit Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="unit_name" name="unit_type_id">
                            <option value="">---Select---</option>
                            @foreach($unitTypes ?? [] as $ut)
                                <option value="{{ $ut->pk }}"
                                    @if(isset($prefill['estate_unit_type_master_pk']) && (int)$prefill['estate_unit_type_master_pk'] === (int)$ut->pk)
                                        selected
                                    @elseif(!isset($prefill) && ($ut->unit_type ?? '') === 'Residential')
                                        selected
                                    @endif
                                >{{ $ut->unit_type }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Select Unit</div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="building" class="form-label">Building <span class="text-danger">*</span></label>
                        <select class="form-select" id="building" name="building">
                            <option value="">---Select---</option>
                        </select>
                        <div class="form-text">Select Building</div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="unit_sub_type" class="form-label">Unit Sub Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="unit_sub_type" name="unit_sub_type">
                            <option value="">---Select---</option>
                            @foreach($unitSubTypes ?? [] as $ust)
                                <option value="{{ $ust->pk }}">{{ $ust->unit_sub_type }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Select Unit Sub Type</div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="meter_reading_date" class="form-label">Meter Reading Date <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <select class="form-select" id="meter_reading_date" name="meter_reading_date">
                                <option value="">---Select---</option>
                            </select>
                            <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                        </div>
                        <div class="form-text">Meter Reading Date</div>
                    </div>
                    <div class="col-12">
                        <button type="button" class="btn btn-primary" id="loadMeterReadingsBtn">
                            <i class="bi bi-search me-2"></i>Load Data
                        </button>
                    </div>
                </div>
            </form>

            <form id="meterReadingSaveForm" method="POST" action="{{ route('admin.estate.update-meter-reading-of-other.store') }}" style="display:none;">
                @csrf
                <div class="table-responsive mt-4 rounded-3 overflow-hidden border">
                    <table class="table table-striped table-hover align-middle mb-0" id="updateMeterReadingOtherTable">
                        <thead class="table-primary">
                            <tr>
                                <th><input type="checkbox" class="form-check-input" id="select_all" aria-label="Select all"></th>
                                <th>House No.</th>
                                <th>Name</th>
                                <th>Last Month Electric Reading Date</th>
                                <th>Meter No.</th>
                                <th>Last Month Meter Reading</th>
                                <th>Current Month Reading <span class="text-danger">*</span></th>
                                <th>Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-success d-inline-flex align-items-center gap-2">
                        <i class="bi bi-save"></i> Save
                    </button>
                    <a href="{{ route('admin.estate.possession-for-others') }}" class="btn btn-secondary d-inline-flex align-items-center gap-2">
                        <i class="bi bi-x-lg"></i> Cancel
                    </a>
                </div>
            </form>

            <div id="noDataMessage" class="alert alert-warning mt-4" style="display:none;">
                No meter reading records found for the selected filters.
            </div>
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
<script>
$(document).ready(function() {
    const listUrl = "{{ route('admin.estate.update-meter-reading-of-other.list') }}";
    const blocksUrl = "{{ route('admin.estate.update-meter-reading-of-other.blocks') }}";
    const unitSubTypesUrl = "{{ route('admin.estate.update-meter-reading-of-other.unit-sub-types') }}";
    const meterReadingDatesUrl = "{{ route('admin.estate.update-meter-reading-of-other.meter-reading-dates') }}";
    const unitTypesByCampus = @json($unitTypesByCampus ?? []);
    const allUnitTypes = @json($unitTypes ?? []);
    const possessionPks = @json($possessionPks ?? '');
    const prefill = @json($prefill ?? null);

    let dataTable = null;
    window.otherMeterRowData = window.otherMeterRowData || {};

    var tsOpts = { allowEmptyOption: true, create: false, dropdownParent: 'body', maxOptions: null, hideSelected: false, onInitialize: function() { this.activeOption = null; } };
    function initTs(el, placeholder) {
        if (!el || typeof TomSelect === 'undefined') return null;
        if (el.tomselect) { try { el.tomselect.destroy(); } catch (e) {} }
        return new TomSelect(el, Object.assign({}, tsOpts, { placeholder: placeholder || '---Select---' }));
    }
    function getSelVal(el) { return (el && el.tomselect) ? el.tomselect.getValue() : $(el).val(); }

    var tsEstate = null, tsUnitName = null, tsBuilding = null, tsUnitSub = null, tsMeterDate = null;
    if (document.getElementById('estate_name')) tsEstate = initTs(document.getElementById('estate_name'), '---Select---');
    if (document.getElementById('unit_name')) tsUnitName = initTs(document.getElementById('unit_name'), '---Select---');
    if (document.getElementById('building')) tsBuilding = initTs(document.getElementById('building'), '---Select---');
    if (document.getElementById('unit_sub_type')) tsUnitSub = initTs(document.getElementById('unit_sub_type'), '---Select---');
    if (document.getElementById('meter_reading_date')) tsMeterDate = initTs(document.getElementById('meter_reading_date'), 'Select');

    function parseBillMonthInput(val) {
        if (!val || val.length < 7) return { bill_month: null, bill_year: null };
        const parts = val.split('-');
        const year = parts[0] ? parseInt(parts[0], 10) : null;
        const month = parts[1] ? parseInt(parts[1], 10) : null;
        return { bill_month: (month >= 1 && month <= 12) ? month : null, bill_year: year };
    }

    $('#bill_month').on('change', function() {
        const val = $(this).val();
        const { bill_month, bill_year } = parseBillMonthInput(val);
        var el = document.getElementById('meter_reading_date');
        if (tsMeterDate) { try { tsMeterDate.destroy(); } catch (e) {} tsMeterDate = null; }
        $('#meter_reading_date').html('<option value="">Select</option>');
        if (!bill_month || !bill_year) {
            if (el) tsMeterDate = initTs(el, 'Select');
            return;
        }
        $.get(meterReadingDatesUrl, { bill_month: bill_month, bill_year: bill_year }, function(res) {
            if (res.status && res.data && res.data.length) {
                res.data.forEach(function(d) {
                    $('#meter_reading_date').append('<option value="'+d.value+'">'+d.label+'</option>');
                });
            }
            if (el) tsMeterDate = initTs(el, 'Select');
            if (res.status && res.data && res.data.length === 1) {
                if (tsMeterDate) tsMeterDate.setValue(res.data[0].value, true);
                else $('#meter_reading_date').val(res.data[0].value);
            }
        });
    });

    $(document).on('change', '#estate_name', function() {
        const campusId = getSelVal(this);
        var elB = document.getElementById('building'), elSub = document.getElementById('unit_sub_type');
        if (tsBuilding) { try { tsBuilding.destroy(); } catch (e) {} tsBuilding = null; }
        if (tsUnitSub) { try { tsUnitSub.destroy(); } catch (e) {} tsUnitSub = null; }
        $('#building').html('<option value="">All</option>');
        $('#unit_sub_type').html('<option value="">All</option>');
        if (elB) tsBuilding = initTs(elB, 'All');
        if (elSub) tsUnitSub = initTs(elSub, 'All');
        if (!campusId) return;
        $.get(blocksUrl, { campus_id: campusId }, function(res) {
            if (res.status && res.data) {
                if (tsBuilding) { try { tsBuilding.destroy(); } catch (e) {} tsBuilding = null; }
                $('#building').html('<option value="">All</option>');
                $.each(res.data, function(i, b) {
                    $('#building').append('<option value="'+b.pk+'">'+b.block_name+'</option>');
                });
                if (elB) tsBuilding = initTs(elB, 'All');
                if (prefill && String(prefill.estate_campus_master_pk) === String(campusId) && tsBuilding) {
                    tsBuilding.setValue(String(prefill.estate_block_master_pk || ''), true);
                    $('#building').trigger('change');
                }
            }
        });
    });

    $(document).on('change', '#building', function() {
        const campusId = getSelVal(document.getElementById('estate_name'));
        const blockId = getSelVal(this);
        var elSub = document.getElementById('unit_sub_type');
        if (tsUnitSub) { try { tsUnitSub.destroy(); } catch (e) {} tsUnitSub = null; }
        $('#unit_sub_type').html('<option value="">All</option>');
        if (!campusId || !blockId) {
            if (elSub) tsUnitSub = initTs(elSub, 'All');
            return;
        }
        $.get(unitSubTypesUrl, { campus_id: campusId, block_id: blockId }, function(res) {
            if (res.status && res.data) {
                $.each(res.data, function(i, u) {
                    $('#unit_sub_type').append('<option value="'+u.pk+'">'+u.unit_sub_type+'</option>');
                });
            }
            if (elSub) tsUnitSub = initTs(elSub, 'All');
            if (prefill && String(prefill.estate_campus_master_pk) === String(campusId) && String(prefill.estate_block_master_pk) === String(blockId) && prefill.estate_unit_sub_type_master_pk && tsUnitSub) {
                tsUnitSub.setValue(String(prefill.estate_unit_sub_type_master_pk), true);
            }
        });
    });

    $('#loadMeterReadingsBtn').on('click', function() {
        if (dataTable) {
            dataTable.destroy();
            dataTable = null;
        }
        const billMonthVal = $('#bill_month').val();
        const { bill_month: billMonth, bill_year: billYear } = parseBillMonthInput(billMonthVal);
        if (!billMonth || !billYear) {
            alert('Please select Bill Month.');
            return;
        }
        const today = new Date();
        const maxMonth = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0');
        if (billMonthVal > maxMonth) {
            alert('Bill Month cannot be a future month. Please select current month or earlier.');
            return;
        }
        const params = {
            bill_month: billMonth,
            bill_year: billYear,
            meter_reading_date: getSelVal(document.getElementById('meter_reading_date')) || '',
            campus_id: getSelVal(document.getElementById('estate_name')) || '',
            block_id: getSelVal(document.getElementById('building')) || '',
            unit_type_id: getSelVal(document.getElementById('unit_name')) || '',
            unit_sub_type_id: getSelVal(document.getElementById('unit_sub_type')) || ''
        };
        // Load grid data by selected filters only (estate/unit/building/sub-type/date/month).
        $.get(listUrl, params, function(res) {
            if (!res.status || !res.data || res.data.length === 0) {
                $('#meterReadingSaveForm').hide();
                $('#noDataMessage').show();
                if (dataTable) {
                    dataTable.destroy();
                    dataTable = null;
                }
                $('#updateMeterReadingOtherTable tbody').html('');
                return;
            }
            $('#noDataMessage').hide();
            const tbody = $('#updateMeterReadingOtherTable tbody');
            tbody.html('');
            window.otherMeterRowData = window.otherMeterRowData || {};
            res.data.forEach(function(row, idx) {
                const lastReading = typeof row.last_month_reading === 'number' ? row.last_month_reading : (parseInt(row.last_month_reading, 10) || null);
                const currVal = (row.curr_month_reading !== null && row.curr_month_reading !== '' ? row.curr_month_reading : '');
                const rowKey = row.pk + '_' + (row.meter_slot || 1);
                window.otherMeterRowData[rowKey] = { curr_month_elec_red: currVal };
                const tr = '<tr data-last-reading="'+ (lastReading !== null ? lastReading : '') +'" data-existing-curr="'+ (currVal !== '' ? currVal : '') +'" data-pk="'+ row.pk +'" data-meter-slot="'+ (row.meter_slot || 1) +'">' +
                    '<td><input type="checkbox" class="form-check-input row-check" name="readings['+idx+'][selected]" value="1" aria-label="Select row"></td>' +
                    '<td>'+ (row.house_no || 'N/A') +'</td>' +
                    '<td>'+ (row.name || 'N/A') +'</td>' +
                    '<td>'+ (row.last_reading_date || 'N/A') +'</td>' +
                    '<td>'+ (row.meter_no || 'N/A') +'</td>' +
                    '<td>'+ (row.last_month_reading || 'N/A') +'</td>' +
                    '<td><input type="number" class="form-control form-control-sm curr-reading" name="readings['+idx+'][curr_month_elec_red]" value="'+ String(currVal).replace(/"/g, '&quot;') +'" min="0" step="1" placeholder="Enter">' +
                    '<input type="hidden" name="readings['+idx+'][pk]" value="'+row.pk+'">' +
                    '<input type="hidden" name="readings['+idx+'][meter_slot]" value="'+(row.meter_slot || 1)+'"></td>' +
                    '<td class="unit-cell">'+ (row.unit || 'N/A') +'</td>' +
                    '</tr>';
                tbody.append(tr);
            });
            $('#meterReadingSaveForm').show();
        }).fail(function() {
            alert('Failed to load data.');
        });
    });

    $('#select_all').on('change', function() {
        const checked = $(this).prop('checked');
        $('.row-check').prop('checked', checked).trigger('change');
    });

    function syncOtherRowDataFromInputs($row) {
        var pk = $row.data('pk');
        var meterSlot = $row.data('meter-slot') || 1;
        if (!pk) return;
        window.otherMeterRowData = window.otherMeterRowData || {};
        var key = pk + '_' + meterSlot;
        if (!window.otherMeterRowData[key]) {
            window.otherMeterRowData[key] = { curr_month_elec_red: '' };
        }
        window.otherMeterRowData[key].curr_month_elec_red = $row.find('.curr-reading').val() || '';
    }

    $(document).on('input change', '.curr-reading', function() {
        const $row = $(this).closest('tr');
        const lastVal = $row.data('last-reading');
        const existingVal = $row.data('existing-curr');

        const lastReading = (lastVal !== '' && lastVal !== undefined && !isNaN(parseFloat(lastVal))) ? parseFloat(lastVal) : null;
        const existingCurr = (existingVal !== '' && existingVal !== undefined && !isNaN(parseFloat(existingVal))) ? parseFloat(existingVal) : null;

        let currVal = $(this).val();
        let currReading = (currVal !== '' && currVal !== null && !isNaN(parseFloat(currVal))) ? parseFloat(currVal) : null;

        // Block user from entering value less than last month / existing current.
        let minAllowed = lastReading;
        if (existingCurr !== null) {
            minAllowed = (minAllowed !== null) ? Math.max(minAllowed, existingCurr) : existingCurr;
        }
        if (minAllowed !== null && currReading !== null && currReading < minAllowed) {
            currReading = minAllowed;
            currVal = String(minAllowed);
            $(this).val(currVal);
        }

        syncOtherRowDataFromInputs($row);

        let unit = 'N/A';
        if (lastReading !== null && currReading !== null && currReading >= lastReading) {
            unit = currReading - lastReading;
        }
        $row.find('.unit-cell').text(unit);
    });

    // Require current reading only for selected rows (gives immediate feedback).
    $(document).on('change', '.row-check', function() {
        const $row = $(this).closest('tr');
        const $input = $row.find('.curr-reading');
        if ($(this).prop('checked')) {
            $input.attr('required', 'required');
        } else {
            $input.removeAttr('required');
        }
    });

    $('#meterReadingSaveForm').on('submit', function(e) {
        const selectedCount = $('.row-check:checked').length;
        if (selectedCount === 0) {
            e.preventDefault();
            alert('Please select at least one record by clicking the checkbox before saving.');
            return;
        }
        // No DataTable pagination; submit normally.
    });

    // Prefill form when coming from Estate Possession for Other with possession_pks
    if (prefill) {
        if (prefill.bill_month) {
            $('#bill_month').val(prefill.bill_month).trigger('change');
        }
        if (tsEstate) tsEstate.setValue(String(prefill.estate_campus_master_pk || ''), true);
        else $('#estate_name').val(prefill.estate_campus_master_pk || '');
        $('#estate_name').trigger('change');
    }
});
</script>
@endpush
