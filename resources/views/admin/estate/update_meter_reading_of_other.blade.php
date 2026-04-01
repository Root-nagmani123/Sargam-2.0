@extends('admin.layouts.master')

@section('title', 'Update Meter Reading of Other - Sargam')

@section('setup_content')
@php
    $otherMeterReadingFlashParts = [];
    if (session('error')) {
        $otherMeterReadingFlashParts[] = trim((string) session('error'));
    }
    if ($errors->any()) {
        foreach ($errors->all() as $err) {
            $t = trim((string) $err);
            if ($t !== '' && ! in_array($t, $otherMeterReadingFlashParts, true)) {
                $otherMeterReadingFlashParts[] = $t;
            }
        }
    }
    $otherMeterReadingAlertMessage = ! empty($otherMeterReadingFlashParts)
        ? implode("\n\n", $otherMeterReadingFlashParts)
        : null;
@endphp
<div class="container-fluid">
    <x-breadcrum :title="'Update Meter Reading of Other'" :items="['Home', 'Estate Management', 'Update Meter Reading of Other']" />

    <div class="card shadow-sm">
        <div class="card-header bg-body-secondary bg-opacity-10 border-0 py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="card-title mb-0">Please Update Meter Reading (Other)</h5>
        </div>
        <div class="card-body p-4">
            <form id="meterReadingFilterForm" class="needs-validation" novalidate>
                @csrf
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="bill_month" class="form-label">Meter Change Month <span class="text-danger">*</span></label>
                        <input type="month" class="form-control" id="bill_month" name="bill_month" placeholder="Select month" max="{{ date('Y-m') }}" value="{{ old('reading_bill_month') }}" required>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Same as permanent estate: list uses the previous calendar month; rows with reading end date in this month are excluded.
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="estate_name" class="form-label">Estate Name <span class="text-danger">*</span></label>
                        <select class="form-select" id="estate_name" name="estate_name">
                            <option value="">---Select---</option>
                            @foreach($campuses as $c)
                                <option value="{{ $c->pk }}">{{ $c->campus_name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Estate Name
                        </small>
                    </div>
                    <div class="col-md-4">
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
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Unit
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="building" class="form-label">Building <span class="text-danger">*</span></label>
                        <select class="form-select" id="building" name="building">
                            <option value="">---Select---</option>
                        </select>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Select Building
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="unit_sub_type" class="form-label">Unit Sub Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="unit_sub_type" name="unit_sub_type">
                            <option value="">---Select---</option>
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
                        <input type="date" class="form-control @error('reading_meter_reading_date') is-invalid @enderror" id="meter_reading_date" name="reading_meter_reading_date" placeholder="Select date" value="{{ old('reading_meter_reading_date') }}" autocomplete="off" required>
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

            <form id="meterReadingSaveForm" method="POST" action="{{ route('admin.estate.update-meter-reading-of-other.store') }}" style="display:none;">
                @csrf
                <input type="hidden" name="reading_bill_month" id="reading_bill_month_hidden" value="">
                <input type="hidden" name="reading_meter_reading_date" id="reading_meter_reading_date_hidden" value="">

                <div class="table-responsive mt-4">
                    <table class="table table-bordered table-hover" id="updateMeterReadingOtherTable">
                        <thead class="table-primary">
                            <tr>
                                <th><input type="checkbox" class="form-check-input" id="select_all"></th>
                                <th>House No.</th>
                                <th>Name</th>
                                <th>Last Month Electric Reading Date</th>
                                <th>Old Meter No.</th>
                                <th>Last Month Meter Reading</th>
                                <th>New Meter No.</th>
                                <th>Current Month Reading <span class="text-danger">*</span></th>
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
                    <a href="{{ route('admin.estate.possession-for-others') }}" class="btn btn-outline-primary">Cancel</a>
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
@if ($otherMeterReadingAlertMessage !== null)
(function () {
    try { alert(@json($otherMeterReadingAlertMessage)); } catch (e) {}
})();
@endif
$(document).ready(function() {
    const listUrl = "{{ route('admin.estate.update-meter-reading-of-other.list') }}";
    const blocksUrl = "{{ route('admin.estate.update-meter-reading-of-other.blocks') }}";
    const unitSubTypesUrl = "{{ route('admin.estate.update-meter-reading-of-other.unit-sub-types') }}";
    const unitTypesByCampus = @json($unitTypesByCampus ?? []);
    const allUnitTypes = @json($unitTypes ?? []);
    const possessionPks = @json($possessionPks ?? '');
    const prefill = @json($prefill ?? null);

    let dataTable = null;
    let lastInvalidReadingAlertAt = 0;
    window.otherMeterRowData = window.otherMeterRowData || {};

    var tsOpts = { allowEmptyOption: true, create: false, dropdownParent: 'body', maxOptions: null, hideSelected: false, onInitialize: function() { this.activeOption = null; } };
    function initTs(el, placeholder) {
        if (!el || typeof TomSelect === 'undefined') return null;
        if (el.tomselect) { try { el.tomselect.destroy(); } catch (e) {} }
        return new TomSelect(el, Object.assign({}, tsOpts, { placeholder: placeholder || '---Select---' }));
    }
    function getSelVal(el) { return (el && el.tomselect) ? el.tomselect.getValue() : $(el).val(); }

    var tsEstate = null, tsUnitName = null, tsBuilding = null, tsUnitSub = null;
    if (document.getElementById('estate_name')) tsEstate = initTs(document.getElementById('estate_name'), '---Select---');
    if (document.getElementById('unit_name')) tsUnitName = initTs(document.getElementById('unit_name'), '---Select---');
    if (document.getElementById('building')) tsBuilding = initTs(document.getElementById('building'), '---Select---');
    if (document.getElementById('unit_sub_type')) tsUnitSub = initTs(document.getElementById('unit_sub_type'), '---Select---');

    function parseBillMonthInput(val) {
        if (!val || val.length < 7) return { bill_month: null, bill_year: null };
        const parts = val.split('-');
        const year = parts[0] ? parseInt(parts[0], 10) : null;
        const month = parts[1] ? parseInt(parts[1], 10) : null;
        return { bill_month: (month >= 1 && month <= 12) ? month : null, bill_year: year };
    }

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
            alert('Please select Meter Change Month.');
            return;
        }
        const today = new Date();
        const maxFromInput = $('#bill_month').attr('max');
        const maxMonth = maxFromInput || (today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0'));
        if (billMonthVal > maxMonth) {
            alert('Meter Change Month cannot be a future month. Please select current month or earlier.');
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
        if (possessionPks && String(possessionPks).trim() !== '') {
            params.possession_pks = String(possessionPks).trim();
        }
        // Load grid by meter-change month + estate filters (meter reading date is for Save, not list).
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
                // Same UX as permanent update-meter-reading: input starts blank; existing saved reading only on data-* for min validation.
                const existingCurrStored = (row.curr_month_reading !== null && row.curr_month_reading !== undefined && row.curr_month_reading !== '')
                    ? String(row.curr_month_reading) : '';
                const rowKey = row.pk + '_' + (row.meter_slot || 1);
                var oldMeterNoStr = (row.old_meter_no != null && row.old_meter_no !== undefined) ? String(row.old_meter_no).trim() : '';
                var apiNewMeterNo = (row.new_meter_no != null && row.new_meter_no !== undefined) ? String(row.new_meter_no).trim() : '';
                var newMeterNoPrefill = apiNewMeterNo !== '' ? apiNewMeterNo : (oldMeterNoStr !== '' && oldMeterNoStr !== 'N/A' ? oldMeterNoStr : '');
                window.otherMeterRowData[rowKey] = { curr_month_elec_red: '', new_meter_no: newMeterNoPrefill };
                const tr = '<tr data-last-reading="'+ (lastReading !== null ? lastReading : '') +'" data-existing-curr="'+ existingCurrStored.replace(/"/g, '&quot;') +'" data-pk="'+ row.pk +'" data-meter-slot="'+ (row.meter_slot || 1) +'">' +
                    '<td><input type="checkbox" class="form-check-input row-check" name="readings['+idx+'][selected]" value="1"></td>' +
                    '<td>'+ (row.house_no || 'N/A') +'</td>' +
                    '<td>'+ (row.name || 'N/A') +'</td>' +
                    '<td>'+ (row.last_reading_date || 'N/A') +'</td>' +
                    '<td>'+ (row.old_meter_no != null && String(row.old_meter_no).trim() !== '' ? row.old_meter_no : (row.meter_no || 'N/A')) +'</td>' +
                    '<td>'+ (row.last_month_reading || 'N/A') +'</td>' +
                    '<td><input type="text" class="form-control form-control-sm new-meter-no" name="readings['+idx+'][new_meter_no]" value="'+ String(newMeterNoPrefill).replace(/"/g, '&quot;') +'" placeholder="Enter new meter no." inputmode="numeric" maxlength="50"></td>' +
                    '<td><input type="number" class="form-control form-control-sm curr-reading" name="readings['+idx+'][curr_month_elec_red]" value="" min="0" step="1" placeholder="Enter" inputmode="numeric">' +
                    '<input type="hidden" name="readings['+idx+'][pk]" value="'+row.pk+'">' +
                    '<input type="hidden" name="readings['+idx+'][meter_slot]" value="'+(row.meter_slot || 1)+'"></td>' +
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

    function getOtherRowMinAllowed($row) {
        const lastVal = $row.data('last-reading');
        const existingVal = $row.data('existing-curr');
        const lastReading = (lastVal !== '' && lastVal !== undefined && !isNaN(parseFloat(lastVal))) ? parseFloat(lastVal) : null;
        const existingCurr = (existingVal !== '' && existingVal !== undefined && !isNaN(parseFloat(existingVal))) ? parseFloat(existingVal) : null;
        if (lastReading === null && existingCurr === null) return null;
        if (existingCurr !== null && lastReading !== null) return Math.max(lastReading, existingCurr);
        return existingCurr !== null ? existingCurr : lastReading;
    }

    function syncOtherRowDataFromInputs($row) {
        var pk = $row.data('pk');
        var meterSlot = $row.data('meter-slot') || 1;
        if (!pk) return;
        window.otherMeterRowData = window.otherMeterRowData || {};
        var key = pk + '_' + meterSlot;
        if (!window.otherMeterRowData[key]) {
            window.otherMeterRowData[key] = { curr_month_elec_red: '', new_meter_no: '' };
        }
        window.otherMeterRowData[key].curr_month_elec_red = $row.find('.curr-reading').val() || '';
        window.otherMeterRowData[key].new_meter_no = $row.find('.new-meter-no').val() || '';
    }

    $(document).on('input change', '.new-meter-no', function() {
        this.value = String(this.value || '').replace(/\D/g, '').slice(0, 50);
        syncOtherRowDataFromInputs($(this).closest('tr'));
    });

    $(document).on('keydown', '.new-meter-no', function(e) {
        if (['e', 'E', '+', '-', '.', ','].includes(e.key)) {
            e.preventDefault();
        }
    });

    $(document).on('input', '.curr-reading', function() {
        const $row = $(this).closest('tr');
        const lastVal = $row.data('last-reading');
        const lastReading = (lastVal !== '' && lastVal !== undefined && !isNaN(parseFloat(lastVal))) ? parseFloat(lastVal) : null;

        let currVal = $(this).val();
        let currReading = (currVal !== '' && currVal !== null && !isNaN(parseFloat(currVal))) ? parseFloat(currVal) : null;

        syncOtherRowDataFromInputs($row);

        let unit = '';
        if (lastReading !== null && currReading !== null && currReading >= lastReading) {
            unit = currReading - lastReading;
        }
        $row.find('.unit-cell').text(unit);
    });

    $(document).on('blur', '.curr-reading', function() {
        const $row = $(this).closest('tr');
        const currVal = $(this).val();
        const currReading = (currVal !== '' && currVal !== null && !isNaN(parseFloat(currVal))) ? parseFloat(currVal) : null;
        const minAllowed = getOtherRowMinAllowed($row);
        if (minAllowed !== null && currReading !== null && currReading < minAllowed) {
            lastInvalidReadingAlertAt = Date.now();
            alert('Current Month Reading cannot be less than Last Month Meter Reading.');
        }
    });

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

        let hasEmptyReading = false;
        $('.row-check:checked').each(function() {
            const $row = $(this).closest('tr');
            const $input = $row.find('.curr-reading');
            const currVal = $input.val();
            if (currVal === null || String(currVal).trim() === '') {
                hasEmptyReading = true;
                $input.trigger('focus');
                return false;
            }
        });
        if (hasEmptyReading) {
            e.preventDefault();
            alert('Please fill Current Month Reading for all selected rows.');
            return;
        }

        let hasInvalidReading = false;
        $('.row-check:checked').each(function() {
            const $row = $(this).closest('tr');
            const $input = $row.find('.curr-reading');
            const currReading = parseFloat(String($input.val()).trim(), 10);
            const minAllowed = getOtherRowMinAllowed($row);
            if (minAllowed !== null && !isNaN(currReading) && currReading < minAllowed) {
                hasInvalidReading = true;
                $input.trigger('focus');
                return false;
            }
        });
        if (hasInvalidReading) {
            e.preventDefault();
            const now = Date.now();
            if ((now - lastInvalidReadingAlertAt) > 800) {
                lastInvalidReadingAlertAt = now;
                alert('Current Month Reading cannot be less than Last Month Meter Reading.');
            }
            return;
        }

        $('#reading_bill_month_hidden').val($('#bill_month').val() || '');
        $('#reading_meter_reading_date_hidden').val(meterReadingDateSubmit);
    });

    // Prefill form when coming from Estate Possession for Other with possession_pks
    @if (! $errors->any())
    if (prefill) {
        if (prefill.bill_month) {
            $('#bill_month').val(prefill.bill_month).trigger('change');
        }
        if (tsEstate) tsEstate.setValue(String(prefill.estate_campus_master_pk || ''), true);
        else $('#estate_name').val(prefill.estate_campus_master_pk || '');
        $('#estate_name').trigger('change');
    }
    @endif
});
</script>
@endpush
