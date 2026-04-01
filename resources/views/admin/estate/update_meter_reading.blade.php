@extends('admin.layouts.master')

@section('title', 'Update Meter Reading - Sargam')

@section('setup_content')
@php
    $meterReadingPageFlashParts = [];
    if (session('error')) {
        $meterReadingPageFlashParts[] = trim((string) session('error'));
    }
    if ($errors->any()) {
        foreach ($errors->all() as $err) {
            $t = trim((string) $err);
            if ($t !== '' && ! in_array($t, $meterReadingPageFlashParts, true)) {
                $meterReadingPageFlashParts[] = $t;
            }
        }
    }
    $meterReadingPageAlertMessage = ! empty($meterReadingPageFlashParts)
        ? implode("\n\n", $meterReadingPageFlashParts)
        : null;
    $meterReadingBillMonthDefault = (isset($prefill['bill_month']) && $prefill['bill_month'] <= date('Y-m')) ? $prefill['bill_month'] : '';
    $meterReadingDateDefault = isset($prefill['meter_reading_date']) ? $prefill['meter_reading_date'] : '';
@endphp
<div class="container-fluid">
    <!-- Breadcrumb -->
<x-breadcrum :title="'Update Meter Reading'" :items="['Home', 'Estate Management', 'Update Meter Reading']" />  

    <!-- Page Title -->
    <div class="card shadow-sm">
        <div class="card-header bg-body-secondary bg-opacity-10 border-0 py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="card-title">Please Update Meter Reading</h5>
    
        </div>
        <div class="card-body p-4">
            <form>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="bill_month" class="form-label">Meter Change Month <span class="text-danger">*</span></label>
                        <input type="month" class="form-control" id="bill_month" name="bill_month" placeholder="Select month" max="{{ date('Y-m') }}" value="{{ old('reading_bill_month', $meterReadingBillMonthDefault) }}" required>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Meter Change Month
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="estate_name" class="form-label">Estate Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_name" name="estate_name">
                            <option value="">Select</option>
                            @foreach($campuses ?? [] as $c)
                                <option value="{{ $c->pk }}" {{ isset($prefill['campus_id']) && (int)$prefill['campus_id'] === (int)$c->pk ? 'selected' : '' }}>{{ $c->campus_name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Estate Name
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="unit_name" class="form-label">Unit Name</label>
                        <select class="form-select" id="unit_name" name="unit_type_id">
                            <option value="">Select</option>
                            @foreach($unitTypes ?? [] as $ut)
                                <option value="{{ $ut->pk }}" {{ (isset($prefill['unit_type_id']) && (int)$prefill['unit_type_id'] === (int)$ut->pk) ? 'selected' : (($ut->unit_type ?? '') == 'Residential' && !isset($prefill) ? 'selected' : '') }}>{{ $ut->unit_type }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Unit
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="building" class="form-label">Building</label>
                        <select class="form-select" id="building" name="building">
                            <option value="">Select</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Building
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="unit_sub_type" class="form-label">Unit Sub Type</label>
                        <select class="form-select" id="unit_sub_type" name="unit_sub_type">
                            <option value="">Select</option>
                            @foreach($unitSubTypes ?? [] as $ust)
                                <option value="{{ $ust->pk }}">{{ $ust->unit_sub_type }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Unit Sub Type
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="meter_reading_date" class="form-label">Meter Reading Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="meter_reading_date" name="meter_reading_date" placeholder="Select date" value="{{ old('reading_current_date', $meterReadingDateDefault) }}" required>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i>
                        </small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary" id="loadMeterReadingsBtn">
                            <i class="bi bi-search me-2"></i>Load Data
                        </button>
                    </div>
                </div>
            </form>

            <form id="meterReadingSaveForm" method="POST" action="{{ route('admin.estate.update-meter-reading.store') }}" style="display:none;">
                @csrf
                <input type="hidden" name="reading_bill_month" id="reading_bill_month" value="">
                <input type="hidden" name="reading_current_date" id="reading_current_date" value="">
                <input type="hidden" name="reading_campus_id" id="reading_campus_id" value="">
                <input type="hidden" name="reading_block_id" id="reading_block_id" value="">
                <input type="hidden" name="reading_unit_type_id" id="reading_unit_type_id" value="">
                <input type="hidden" name="reading_unit_sub_type_id" id="reading_unit_sub_type_id" value="">

                <div class="table-responsive mt-4">
                    <table class="table table-bordered table-hover" id="updateMeterReadingTable">
                        <thead class="table-primary">
                            <tr>
                                <th><input type="checkbox" class="form-check-input" id="select_all"></th>
                                <th>House No.</th>
                                <th>Name</th>
                                <th>Last Month Electric Reading Date</th>
                                <th>Old Meter No.</th>
                                <th>Electric Meter Reading</th>
                                <th>New Meter No.</th>
                                <th>New Meter Reading <span class="text-danger">*</span></th>
                                <th>Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-danger mb-4">
                    <small>*Required Fields: All marked fields are mandatory</small>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save
                    </button>
                    <a href="{{ route('admin.estate.update-meter-no') }}" class="btn btn-outline-primary">Cancel</a>
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
<style>
.ts-dropdown { z-index: 1060 !important; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
@if ($meterReadingPageAlertMessage !== null)
(function () {
    try { alert(@json($meterReadingPageAlertMessage)); } catch (e) {}
})();
@endif
$(document).ready(function() {
    const listUrl = "{{ route('admin.estate.update-meter-reading.list') }}";
    const blocksUrl = "{{ route('admin.estate.update-meter-reading.blocks') }}";
    const unitSubTypesUrl = "{{ route('admin.estate.update-meter-reading.unit-sub-types') }}";
    const prefill = @json($prefill ?? null);

    // For this grid we avoid DataTables to keep typing smooth and prevent focus jumps.
    let dataTable = null;
    let lastInvalidReadingAlertAt = 0;

    var tsOpts = { allowEmptyOption: true, create: false, dropdownParent: 'body', maxOptions: null, hideSelected: false, onInitialize: function() { this.activeOption = null; } };
    function initTs(el, placeholder) {
        if (!el || typeof TomSelect === 'undefined') return null;
        if (el.tomselect) { try { el.tomselect.destroy(); } catch (e) {} }
        return new TomSelect(el, Object.assign({}, tsOpts, { placeholder: placeholder || 'Select' }));
    }
    function getSelVal(el) { return (el && el.tomselect) ? el.tomselect.getValue() : $(el).val(); }

    var tsEstate = null, tsUnitName = null, tsBuilding = null, tsUnitSub = null;
    if (document.getElementById('estate_name')) tsEstate = initTs(document.getElementById('estate_name'), 'Select');
    if (document.getElementById('unit_name')) tsUnitName = initTs(document.getElementById('unit_name'), 'Select');
    if (document.getElementById('building')) tsBuilding = initTs(document.getElementById('building'), 'Select');
    if (document.getElementById('unit_sub_type')) tsUnitSub = initTs(document.getElementById('unit_sub_type'), 'Select');

    function loadBuildingsThenPrefill(campusId, blockId, unitSubTypeId, thenLoadData) {
        var elB = document.getElementById('building'), elSub = document.getElementById('unit_sub_type');
        if (tsBuilding) { try { tsBuilding.destroy(); } catch (e) {} tsBuilding = null; }
        if (tsUnitSub) { try { tsUnitSub.destroy(); } catch (e) {} tsUnitSub = null; }
        $('#building').html('<option value="">Select</option>');
        $('#unit_sub_type').html('<option value="">All</option>');
        if (elB) tsBuilding = initTs(elB, 'Select');
        if (elSub) tsUnitSub = initTs(elSub, 'All');
        if (!campusId) { if (thenLoadData) $('#loadMeterReadingsBtn').click(); return; }
        $.get(blocksUrl, { campus_id: campusId }, function(res) {
            if (res.status && res.data) {
                if (tsBuilding) { try { tsBuilding.destroy(); } catch (e) {} tsBuilding = null; }
                $('#building').html('<option value="">Select</option>');
                $.each(res.data, function(i, b) {
                    var sel = (blockId && String(b.pk) === String(blockId)) ? ' selected' : '';
                    $('#building').append('<option value="'+b.pk+'"'+sel+'>'+b.block_name+'</option>');
                });
                if (elB) tsBuilding = initTs(elB, 'Select');
                if (blockId && tsBuilding) tsBuilding.setValue(String(blockId), true);
            }
            if (blockId && unitSubTypeId != null) {
                loadUnitSubTypesThenPrefill(campusId, blockId, unitSubTypeId, thenLoadData);
            } else if (thenLoadData) {
                $('#loadMeterReadingsBtn').click();
            }
        });
    }

    function loadUnitSubTypesThenPrefill(campusId, blockId, unitSubTypeId, thenLoadData) {
        var elSub = document.getElementById('unit_sub_type');
        if (tsUnitSub) { try { tsUnitSub.destroy(); } catch (e) {} tsUnitSub = null; }
        $('#unit_sub_type').html('<option value="">All</option>');
        if (elSub) tsUnitSub = initTs(elSub, 'All');
        if (!campusId || !blockId) { if (thenLoadData) $('#loadMeterReadingsBtn').click(); return; }
        $.get(unitSubTypesUrl, { campus_id: campusId, block_id: blockId }, function(res) {
            if (tsUnitSub) { try { tsUnitSub.destroy(); } catch (e) {} tsUnitSub = null; }
            $('#unit_sub_type').html('<option value="">All</option>');
            if (res.status && res.data) {
                $.each(res.data, function(i, u) {
                    var sel = (unitSubTypeId && String(u.pk) === String(unitSubTypeId)) ? ' selected' : '';
                    $('#unit_sub_type').append('<option value="'+u.pk+'"'+sel+'>'+u.unit_sub_type+'</option>');
                });
            }
            if (elSub) tsUnitSub = initTs(elSub, 'All');
            if (unitSubTypeId != null && unitSubTypeId !== '' && tsUnitSub) tsUnitSub.setValue(String(unitSubTypeId), true);
            if (thenLoadData) $('#loadMeterReadingsBtn').click();
        });
    }

    $(document).on('change', '#estate_name', function() {
        const campusId = getSelVal(this);
        var elB = document.getElementById('building'), elSub = document.getElementById('unit_sub_type');
        if (tsBuilding) { try { tsBuilding.destroy(); } catch (e) {} tsBuilding = null; }
        if (tsUnitSub) { try { tsUnitSub.destroy(); } catch (e) {} tsUnitSub = null; }
        $('#building').html('<option value="">Select</option>');
        $('#unit_sub_type').html('<option value="">All</option>');
        if (elB) tsBuilding = initTs(elB, 'Select');
        if (elSub) tsUnitSub = initTs(elSub, 'All');
        if (!campusId) return;
        $.get(blocksUrl, { campus_id: campusId }, function(res) {
            if (res.status && res.data) {
                if (tsBuilding) { try { tsBuilding.destroy(); } catch (e) {} tsBuilding = null; }
                $('#building').html('<option value="">Select</option>');
                $.each(res.data, function(i, b) {
                    $('#building').append('<option value="'+b.pk+'">'+b.block_name+'</option>');
                });
                if (elB) tsBuilding = initTs(elB, 'Select');
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
            if (tsUnitSub) { try { tsUnitSub.destroy(); } catch (e) {} tsUnitSub = null; }
            $('#unit_sub_type').html('<option value="">All</option>');
            if (res.status && res.data) {
                $.each(res.data, function(i, u) {
                    $('#unit_sub_type').append('<option value="'+u.pk+'">'+u.unit_sub_type+'</option>');
                });
            }
            if (elSub) tsUnitSub = initTs(elSub, 'All');
        });
    });

    @if (! $errors->any())
    if (prefill && prefill.bill_month) {
        $('#bill_month').val(prefill.bill_month);
        if (prefill.campus_id) {
            if (tsEstate) tsEstate.setValue(String(prefill.campus_id), true);
            else $('#estate_name').val(prefill.campus_id);
            loadBuildingsThenPrefill(prefill.campus_id, prefill.block_id, prefill.unit_sub_type_id, true);
        } else {
            $('#loadMeterReadingsBtn').click();
        }
    }
    @endif

    $('#loadMeterReadingsBtn').on('click', function() {
        const billMonthVal = $('#bill_month').val();
        if (!billMonthVal) {
            alert('Please select Meter Change Month.');
            $('#bill_month').trigger('focus');
            return;
        }
        var monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        var parts = billMonthVal.split('-');
        var billYear = '';
        var billMonth = '';
        if (parts.length === 2) {
            billYear = parts[0];
            var mIdx = parseInt(parts[1], 10);
            billMonth = (mIdx >= 1 && mIdx <= 12) ? monthNames[mIdx - 1] : '';
        }
        const today = new Date();
        const maxFromInput = $('#bill_month').attr('max');
        const maxMonth = maxFromInput || (today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0'));
        if (billMonthVal > maxMonth) {
            alert('Meter Change Month cannot be a future month. Please select current month or earlier.');
            $('#bill_month').trigger('focus');
            return;
        }
        if (!billMonth || !billYear) {
            alert('Invalid Meter Change Month.');
            $('#bill_month').trigger('focus');
            return;
        }
        const meterReadingDateValLoad = ($('#meter_reading_date').val() || '').trim();
        if (!meterReadingDateValLoad) {
            alert('Please select Meter Reading Date.');
            $('#meter_reading_date').trigger('focus');
            return;
        }
        const params = {
            bill_month: billMonth,
            bill_year: billYear,
            campus_id: getSelVal(document.getElementById('estate_name')) || '',
            block_id: getSelVal(document.getElementById('building')) || '',
            unit_type_id: getSelVal(document.getElementById('unit_name')) || '',
            unit_sub_type_id: getSelVal(document.getElementById('unit_sub_type')) || ''
        };
        $.get(listUrl, params, function(res) {
            if (!res.status || !res.data || res.data.length === 0) {
                $('#meterReadingSaveForm').hide();
                $('#noDataMessage').show();
                if (dataTable) {
                    dataTable.destroy();
                    dataTable = null;
                }
                $('#updateMeterReadingTable tbody').html('');
                return;
            }
            $('#noDataMessage').hide();
            const tbody = $('#updateMeterReadingTable tbody');
            tbody.html('');
            window.meterReadingRowData = window.meterReadingRowData || {};
            res.data.forEach(function(row, idx) {
                var oldMeterNoStr = (row.old_meter_no != null && row.old_meter_no !== undefined) ? String(row.old_meter_no).trim() : '';
                var apiNewMeterNo = (row.new_meter_no != null && row.new_meter_no !== undefined) ? String(row.new_meter_no).trim() : '';
                // Prefer saved new meter on record; otherwise prefill same as Old Meter No. (user may edit before save).
                var newMeterNo = apiNewMeterNo !== '' ? apiNewMeterNo : (oldMeterNoStr !== '' && oldMeterNoStr !== 'N/A' ? oldMeterNoStr : '');
                // Electric column shows saved curr_month_elec_red; New Meter Reading stays empty until user enters (saved to curr on submit).
                var newMeterReading = '';
                var baselineMin = (row.baseline_min_reading !== undefined && row.baseline_min_reading !== null && row.baseline_min_reading !== '') ? String(row.baseline_min_reading) : '';
                const meterSlot = row.meter_slot || 1;
                const rowKey = row.pk + '_' + meterSlot;
                window.meterReadingRowData[rowKey] = {
                    pk: row.pk,
                    meter_slot: meterSlot,
                    new_meter_no: newMeterNo,
                    curr_month_elec_red: '',
                    original_curr_month_elec_red: ''
                };
                const tr = '<tr data-last-reading="'+ baselineMin.replace(/"/g, '&quot;') +'" data-existing-curr="" data-pk="'+ row.pk +'" data-meter-slot="'+ meterSlot +'">' +
                    '<td><input type="checkbox" class="form-check-input row-check" name="readings['+idx+'][selected]" value="1"></td>' +
                    '<td>'+ (row.house_no || 'N/A') +'</td>' +
                    '<td>'+ (row.name || 'N/A') +'</td>' +
                    '<td>'+ (row.last_reading_date || 'N/A') +'</td>' +
                    '<td>'+ (row.old_meter_no || 'N/A') +'</td>' +
                    '<td>'+ (row.electric_meter_reading ?? 'N/A') +'</td>' +
                    '<td><input type="text" class="form-control form-control-sm new-meter-no" name="readings['+idx+'][new_meter_no]" value="'+ newMeterNo.replace(/"/g, '&quot;') +'" placeholder="Enter new meter no." inputmode="numeric" maxlength="50"></td>' +
                    '<td><input type="number" class="form-control form-control-sm new-meter-reading" name="readings['+idx+'][curr_month_elec_red]" value="'+ String(newMeterReading).replace(/"/g, '&quot;') +'" min="0" placeholder="Enter" step="1" inputmode="numeric">' +
                    '<input type="hidden" name="readings['+idx+'][pk]" value="'+row.pk+'">' +
                    '<input type="hidden" name="readings['+idx+'][meter_slot]" value="'+ meterSlot +'"></td>' +
                    '<td class="unit-cell"></td>' +
                    '</tr>';
                tbody.append(tr);
            });
            $('#meterReadingSaveForm').show();
        }).fail(function() {
            alert('Failed to load data.');
        });
    });

    $('#select_all').on('change', function() {
        $('.row-check').prop('checked', $(this).prop('checked'));
    });

    function getRegularRowMinAllowed($row) {
        const lastVal = $row.data('last-reading');
        const lastReading = (lastVal !== '' && lastVal !== undefined && !isNaN(parseFloat(lastVal))) ? parseFloat(lastVal) : null;
        return lastReading;
    }

    $('#meterReadingSaveForm').on('submit', function(e) {
        const billMonthVal = $('#bill_month').val();
        if (!billMonthVal) {
            e.preventDefault();
            alert('Please select Meter Change Month.');
            $('#bill_month').trigger('focus');
            return;
        }
        const meterReadingDateSubmit = ($('#meter_reading_date').val() || '').trim();
        if (!meterReadingDateSubmit) {
            e.preventDefault();
            alert('Please select Meter Reading Date.');
            $('#meter_reading_date').trigger('focus');
            return;
        }

        const selectedCount = $('.row-check:checked').length;
        if (selectedCount === 0) {
            e.preventDefault();
            alert('Please select at least one record by clicking the checkbox before saving.');
            return;
        }

        let hasInvalidReading = false;
        $('.row-check:checked').each(function() {
            const $row = $(this).closest('tr');
            const $input = $row.find('.new-meter-reading');
            const currVal = $input.val();
            const currReading = (currVal !== '' && currVal !== null && !isNaN(parseFloat(currVal))) ? parseFloat(currVal) : null;
            const minAllowed = getRegularRowMinAllowed($row);
            if (minAllowed !== null && currReading !== null && currReading < minAllowed) {
                hasInvalidReading = true;
                $input.focus();
                return false;
            }
        });
        if (hasInvalidReading) {
            e.preventDefault();
            const now = Date.now();
            if ((now - lastInvalidReadingAlertAt) > 800) {
                lastInvalidReadingAlertAt = now;
                alert('New Meter Reading cannot be less than the minimum allowed baseline for this row (same as server validation).');
            }
            return;
        }

        $('#reading_bill_month').val(billMonthVal);
        $('#reading_current_date').val(meterReadingDateSubmit);
        $('#reading_campus_id').val(getSelVal(document.getElementById('estate_name')) || '');
        $('#reading_block_id').val(getSelVal(document.getElementById('building')) || '');
        $('#reading_unit_type_id').val(getSelVal(document.getElementById('unit_name')) || '');
        $('#reading_unit_sub_type_id').val(getSelVal(document.getElementById('unit_sub_type')) || '');
    });

    function getRowKey($row) {
        var pk = $row.data('pk');
        var meterSlot = $row.data('meter-slot');
        if (pk == null || meterSlot == null) return null;
        return pk + '_' + meterSlot;
    }

    function syncRowDataFromInputs($row) {
        var key = getRowKey($row);
        if (!key || !window.meterReadingRowData) return;
        if (!window.meterReadingRowData[key]) {
            window.meterReadingRowData[key] = {
                pk: $row.data('pk'),
                meter_slot: $row.data('meter-slot'),
                new_meter_no: '',
                curr_month_elec_red: '',
                original_curr_month_elec_red: ''
            };
        }
        window.meterReadingRowData[key].new_meter_no = $row.find('.new-meter-no').val() || '';
        window.meterReadingRowData[key].curr_month_elec_red = $row.find('.new-meter-reading').val() || '';
    }

    $(document).on('input', '.new-meter-reading', function() {
        this.value = String(this.value || '').replace(/\D/g, '').slice(0, 20);
        const $row = $(this).closest('tr');
        const minBaseline = getRegularRowMinAllowed($row);

        let currVal = $(this).val();
        let currReading = (currVal !== '' && currVal !== null && !isNaN(parseFloat(currVal))) ? parseFloat(currVal) : null;

        syncRowDataFromInputs($row);

        let unit = '';
        if (minBaseline !== null && currReading !== null && currReading >= minBaseline) {
            unit = currReading - minBaseline;
        }
        $row.find('.unit-cell').text(unit);
    });

    $(document).on('blur', '.new-meter-reading', function() {
        const $row = $(this).closest('tr');
        const currVal = $(this).val();
        const currReading = (currVal !== '' && currVal !== null && !isNaN(parseFloat(currVal))) ? parseFloat(currVal) : null;
        const minAllowed = getRegularRowMinAllowed($row);

        if (minAllowed !== null && currReading !== null && currReading < minAllowed) {
            lastInvalidReadingAlertAt = Date.now();
            alert('New Meter Reading cannot be less than the minimum allowed baseline for this row (same as server validation).');
        }
    });

    $(document).on('input change', '.new-meter-no', function() {
        this.value = String(this.value || '').replace(/\D/g, '').slice(0, 50);
        syncRowDataFromInputs($(this).closest('tr'));
    });

    $(document).on('keydown', '.new-meter-no', function(e) {
        if (['e', 'E', '+', '-', '.', ','].includes(e.key)) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
