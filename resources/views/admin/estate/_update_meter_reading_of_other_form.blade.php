{{-- Partial: Update Meter Reading (Others) — filter + readings grid.

     Rendered both as a full page (update_meter_reading_of_other.blade.php) and as
     the body of the Update Meter Reading modal on the Estate Possession for Others
     listing, so both entry points share one grid, one cascade and one save path.

     Every id / name / data-* attribute is deliberately unchanged from the original
     page — the grid builder, the per-row validation and the store endpoint all
     depend on them. --}}
@php
    $inModal = $inModal ?? false;
    $umrBillMonthDefault = $prefill['bill_month'] ?? '';
@endphp

<div class="epo-umr" id="otherMeterReadingRoot" data-in-modal="{{ $inModal ? '1' : '0' }}">
    <div class="{{ $inModal ? 'modal-body' : '' }} umr-form">
        <div class="alert alert-danger d-none js-umr-error" role="alert"></div>

        <form id="meterReadingFilterForm" novalidate>
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <label for="bill_month" class="form-label">Meter Change Month <span class="ds-req">*</span></label>
                    <input type="month" class="form-control" id="bill_month" name="bill_month" placeholder="Select Month"
                        max="{{ date('Y-m') }}" value="{{ old('reading_bill_month', $umrBillMonthDefault) }}" required>
                </div>
                <div class="col-md-6">
                    <label for="estate_name" class="form-label">Estate Name <span class="ds-req">*</span></label>
                    <select class="form-select" id="estate_name" name="estate_name">
                        <option value="">Select Estate</option>
                        @foreach($campuses as $c)
                            <option value="{{ $c->pk }}">{{ $c->campus_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="unit_name" class="form-label">Unit Name <span class="ds-req">*</span></label>
                    <select class="form-select" id="unit_name" name="unit_type_id">
                        <option value="">Select Unit</option>
                        @foreach($unitTypes ?? [] as $ut)
                            <option value="{{ $ut->pk }}"
                                @if(isset($prefill['estate_unit_type_master_pk']) && (int) $prefill['estate_unit_type_master_pk'] === (int) $ut->pk)
                                    selected
                                @elseif(! isset($prefill) && ($ut->unit_type ?? '') === 'Residential')
                                    selected
                                @endif
                            >{{ $ut->unit_type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="building" class="form-label">Building Name <span class="ds-req">*</span></label>
                    <select class="form-select" id="building" name="building">
                        <option value="">Select Building</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="unit_sub_type" class="form-label">Unit Sub-type <span class="ds-req">*</span></label>
                    <select class="form-select" id="unit_sub_type" name="unit_sub_type">
                        <option value="">Select Sub-type</option>
                        @foreach($unitSubTypes ?? [] as $ust)
                            <option value="{{ $ust->pk }}">{{ $ust->unit_sub_type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="meter_reading_date" class="form-label">Meter Reading Date <span class="ds-req">*</span></label>
                    <input type="date" class="form-control" id="meter_reading_date" name="reading_meter_reading_date"
                        placeholder="Select Date" value="{{ old('reading_meter_reading_date') }}" autocomplete="off">
                </div>
            </div>
        </form>

        <div id="noDataMessage" class="alert alert-warning mt-3 mb-0 d-none">
            No meter reading records found for the selected filters.
        </div>

        {{-- The grid the filters load. Hidden until Load Data returns rows. --}}
        <form id="meterReadingSaveForm" method="POST"
            action="{{ route('admin.estate.update-meter-reading-of-other.store') }}" class="d-none">
            @csrf
            <input type="hidden" name="reading_bill_month" id="reading_bill_month_hidden" value="">
            <input type="hidden" name="reading_meter_reading_date" id="reading_meter_reading_date_hidden" value="">

            <h2 class="umr-section-title mt-4">Allotment Details</h2>

            <div class="table-responsive umr-grid-wrap mt-2">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table umr-grid" id="updateMeterReadingOtherTable">
                        <thead>
                            <tr>
                                <th style="width:2.5rem;"><input type="checkbox" class="form-check-input" id="select_all" title="Select all"></th>
                                <th>House No.</th>
                                <th>Name &amp; ID</th>
                                <th>Last month date</th>
                                <th>Meter Number</th>
                                <th>Last month electric Meter reading</th>
                                <th>New Meter Number</th>
                                <th>New Meter Reading <span class="ds-req">*</span></th>
                                <th>Unit</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
            </div>
        </form>
    </div>

    <div class="{{ $inModal ? 'modal-footer' : 'd-flex justify-content-end gap-2 mt-4' }}">
        @if($inModal)
            <button type="button" class="btn ds-btn-cancel" data-bs-dismiss="modal">Cancel</button>
        @else
            <a href="{{ route('admin.estate.possession-for-others') }}" class="btn ds-btn-cancel">Cancel</a>
        @endif
        <button type="button" class="btn ds-btn-submit" id="loadMeterReadingsBtn">Load Data</button>
        <button type="button" class="btn ds-btn-submit d-none" id="saveMeterReadingsBtn">Save</button>
    </div>
</div>

<script>
(function() {
    // As a full page this markup renders BEFORE the layout's footer loads jQuery,
    // so defer until the document is parsed. Injected into the modal the document
    // is already complete, so run straight away.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

function init() {
    var root = document.getElementById('otherMeterReadingRoot');
    if (!root || root.dataset.umrBound === '1') return;
    root.dataset.umrBound = '1';

    var $root = $(root);
    var inModal = root.dataset.inModal === '1';
    var listUrl = @json(route('admin.estate.update-meter-reading-of-other.list'));
    var blocksUrl = @json(route('admin.estate.update-meter-reading-of-other.blocks'));
    var unitSubTypesUrl = @json(route('admin.estate.update-meter-reading-of-other.unit-sub-types'));
    var possessionPks = @json($possessionPks ?? '');
    var prefill = @json($prefill ?? null);
    // List Meter Reading's Edit link opens this screen with a reading_pk — only in
    // that flow is New Meter No. editable.
    var isListEditMode = !!(prefill && prefill.reading_pk);
    var newMeterNoLockAttr = isListEditMode ? '' : ' readonly';
    var newMeterNoPlaceholder = isListEditMode ? 'Enter new meter no.' : '';

    var lastInvalidReadingAlertAt = 0;
    window.otherMeterRowData = window.otherMeterRowData || {};

    var $err = $root.find('.js-umr-error');
    var $saveForm = $root.find('#meterReadingSaveForm');
    var $loadBtn = $root.find('#loadMeterReadingsBtn');
    var $saveBtn = $root.find('#saveMeterReadingsBtn');

    function showError(msg) {
        if (!msg) { $err.addClass('d-none').text(''); return; }
        $err.removeClass('d-none').text(msg);
        if (inModal) $err[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function $f(id) { return $root.find('#' + id); }
    function val(id) { var v = $f(id).val(); return (v === null || v === undefined) ? '' : v; }

    // The footer swaps Load Data for Save once there are rows to save, and back
    // again as soon as a filter changes — the grid on screen must always match
    // the filters above it.
    function showGrid(on) {
        $saveForm.toggleClass('d-none', !on);
        $saveBtn.toggleClass('d-none', !on);
        $loadBtn.toggleClass('d-none', on);
    }

    $root.on('change', '#bill_month, #estate_name, #unit_name, #building, #unit_sub_type', function() {
        if (!$saveForm.hasClass('d-none')) {
            showGrid(false);
            $root.find('#updateMeterReadingOtherTable tbody').html('');
        }
    });

    function parseBillMonthInput(v) {
        if (!v || v.length < 7) return { bill_month: null, bill_year: null };
        var parts = v.split('-');
        var year = parts[0] ? parseInt(parts[0], 10) : null;
        var month = parts[1] ? parseInt(parts[1], 10) : null;
        return { bill_month: (month >= 1 && month <= 12) ? month : null, bill_year: year };
    }

    /* ---------- Cascade: estate -> building -> unit sub type ---------- */
    $root.on('change', '#estate_name', function() {
        var campusId = val('estate_name');
        $f('building').html('<option value="">All</option>');
        $f('unit_sub_type').html('<option value="">All</option>');
        if (!campusId) return;
        $.get(blocksUrl, { campus_id: campusId }, function(res) {
            if (!res.status || !res.data) return;
            $.each(res.data, function(i, b) {
                $f('building').append($('<option>', { value: String(b.pk), text: b.block_name || '' }));
            });
            if (prefill && String(prefill.estate_campus_master_pk) === String(campusId)) {
                $f('building').val(String(prefill.estate_block_master_pk || '')).trigger('change');
            }
        });
    });

    $root.on('change', '#building', function() {
        var campusId = val('estate_name');
        var blockId = val('building');
        $f('unit_sub_type').html('<option value="">All</option>');
        if (!campusId || !blockId) return;
        $.get(unitSubTypesUrl, { campus_id: campusId, block_id: blockId }, function(res) {
            if (res.status && res.data) {
                $.each(res.data, function(i, u) {
                    $f('unit_sub_type').append($('<option>', { value: String(u.pk), text: u.unit_sub_type || '' }));
                });
            }
            if (prefill
                && String(prefill.estate_campus_master_pk) === String(campusId)
                && String(prefill.estate_block_master_pk) === String(blockId)
                && prefill.estate_unit_sub_type_master_pk) {
                $f('unit_sub_type').val(String(prefill.estate_unit_sub_type_master_pk));
            }
        });
    });

    /* ---------- Load Data ---------- */
    $loadBtn.on('click', function() {
        showError('');
        var billMonthVal = val('bill_month');
        var parsed = parseBillMonthInput(billMonthVal);
        if (!parsed.bill_month || !parsed.bill_year) {
            showError('Please select Meter Change Month.');
            return;
        }
        var today = new Date();
        var maxMonth = $f('bill_month').attr('max')
            || (today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0'));
        if (billMonthVal > maxMonth) {
            showError('Meter Change Month cannot be a future month. Please select the current month or earlier.');
            return;
        }

        var params = {
            bill_month: parsed.bill_month,
            bill_year: parsed.bill_year,
            campus_id: val('estate_name'),
            block_id: val('building'),
            unit_type_id: val('unit_name'),
            unit_sub_type_id: val('unit_sub_type')
        };
        if (possessionPks && String(possessionPks).trim() !== '') {
            params.possession_pks = String(possessionPks).trim();
        }
        if (prefill && prefill.reading_pk) {
            params.reading_pk = String(prefill.reading_pk);
        }

        $loadBtn.prop('disabled', true);
        // Load the grid by meter-change month + estate filters (the meter reading
        // date is for Save, not for the list).
        $.get(listUrl, params, function(res) {
            var tbody = $root.find('#updateMeterReadingOtherTable tbody');
            if (!res.status || !res.data || res.data.length === 0) {
                showGrid(false);
                $f('noDataMessage').removeClass('d-none');
                tbody.html('');
                return;
            }
            $f('noDataMessage').addClass('d-none');
            tbody.html('');
            window.otherMeterRowData = window.otherMeterRowData || {};

            function escAttr(s) {
                return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
            }
            function parseLastReading(v) {
                if (typeof v === 'number' && !isNaN(v)) return v;
                var n = parseInt(v, 10);
                return !isNaN(n) ? n : null;
            }

            var readingIdx = 0;
            res.data.forEach(function(row) {
                if (row.dual_meter && row.m1 && row.m2) {
                    var i0 = readingIdx++;
                    var i1 = readingIdx++;
                    var m1 = row.m1;
                    var m2 = row.m2;
                    var last1 = parseLastReading(m1.last_month_reading);
                    var last2 = parseLastReading(m2.last_month_reading);
                    var nm1 = String(m1.new_meter_no || '').trim() || String(m1.old_meter_no || '');
                    var nm2 = String(m2.new_meter_no || '').trim() || String(m2.old_meter_no || '');
                    var elec1 = (m1.last_month_reading != null && m1.last_month_reading !== '') ? m1.last_month_reading : 'N/A';
                    var elec2 = (m2.last_month_reading != null && m2.last_month_reading !== '') ? m2.last_month_reading : 'N/A';
                    window.otherMeterRowData[row.pk + '_1'] = { curr_month_elec_red: '', new_meter_no: nm1 };
                    window.otherMeterRowData[row.pk + '_2'] = { curr_month_elec_red: '', new_meter_no: nm2 };

                    tbody.append('<tr class="other-reading-row other-dual-stacked" data-dual="1" data-pk="' + row.pk + '">' +
                        '<td class="text-center align-middle position-relative">' +
                            '<input type="checkbox" class="form-check-input row-check row-check-master" name="readings[' + i0 + '][selected]" value="1" data-pair-sel-id="otherPairSel_' + i1 + '">' +
                            '<input type="checkbox" class="form-check-input other-pair-cb" name="readings[' + i1 + '][selected]" value="1" id="otherPairSel_' + i1 + '" tabindex="-1" aria-hidden="true">' +
                        '</td>' +
                        '<td>' + escAttr(row.house_no || 'N/A') + '</td>' +
                        '<td>' + escAttr(row.name || 'N/A') + '</td>' +
                        '<td class="text-nowrap">' + escAttr(row.last_reading_date || 'N/A') + '</td>' +
                        '<td class="other-dual-col">' +
                            '<div class="other-dual-seg" data-slot="1">' + escAttr(m1.old_meter_no || 'N/A') + '</div>' +
                            '<div class="other-dual-seg" data-slot="2">' + escAttr(m2.old_meter_no || 'N/A') + '</div>' +
                        '</td>' +
                        '<td class="other-dual-col">' +
                            '<div class="other-dual-seg" data-slot="1">' + escAttr(elec1) + '</div>' +
                            '<div class="other-dual-seg" data-slot="2">' + escAttr(elec2) + '</div>' +
                        '</td>' +
                        '<td class="other-dual-col other-dual-newmeter-col">' +
                            '<div class="other-dual-seg" data-slot="1">' +
                            '<input type="text" class="form-control form-control-sm new-meter-no" name="readings[' + i0 + '][new_meter_no]" value="' + escAttr(nm1) + '" placeholder="' + newMeterNoPlaceholder + '" inputmode="numeric" maxlength="50" data-old-meter-no="' + escAttr(m1.old_meter_no || '') + '"' + newMeterNoLockAttr + '>' +
                            '</div>' +
                            '<div class="other-dual-seg" data-slot="2">' +
                            '<input type="text" class="form-control form-control-sm new-meter-no" name="readings[' + i1 + '][new_meter_no]" value="' + escAttr(nm2) + '" placeholder="' + newMeterNoPlaceholder + '" inputmode="numeric" maxlength="50" data-old-meter-no="' + escAttr(m2.old_meter_no || '') + '"' + newMeterNoLockAttr + '>' +
                            '</div>' +
                        '</td>' +
                        '<td class="other-dual-col other-dual-reading-col">' +
                            '<div class="other-dual-seg" data-slot="1">' +
                            '<input type="number" class="form-control form-control-sm curr-reading" name="readings[' + i0 + '][curr_month_elec_red]" value="" min="0" step="1" placeholder="Meter Reading" inputmode="numeric" data-last-reading="' + (last1 !== null ? last1 : '') + '" data-existing-curr="">' +
                            '<input type="hidden" name="readings[' + i0 + '][pk]" value="' + row.pk + '">' +
                            '<input type="hidden" name="readings[' + i0 + '][meter_slot]" value="1">' +
                            '</div>' +
                            '<div class="other-dual-seg" data-slot="2">' +
                            '<input type="number" class="form-control form-control-sm curr-reading" name="readings[' + i1 + '][curr_month_elec_red]" value="" min="0" step="1" placeholder="Meter Reading" inputmode="numeric" data-last-reading="' + (last2 !== null ? last2 : '') + '" data-existing-curr="">' +
                            '<input type="hidden" name="readings[' + i1 + '][pk]" value="' + row.pk + '">' +
                            '<input type="hidden" name="readings[' + i1 + '][meter_slot]" value="2">' +
                            '</div>' +
                        '</td>' +
                        '<td class="other-dual-col other-dual-units small">' +
                            '<div class="other-dual-seg" data-slot="1"><span class="unit-cell">—</span></div>' +
                            '<div class="other-dual-seg" data-slot="2"><span class="unit-cell">—</span></div>' +
                        '</td>' +
                        '</tr>');
                    return;
                }

                var idx = readingIdx++;
                var lastReading = parseLastReading(row.last_month_reading);
                var existingCurrStored = (row.curr_month_reading !== null && row.curr_month_reading !== undefined && row.curr_month_reading !== '')
                    ? String(row.curr_month_reading) : '';
                var slot = row.meter_slot || 1;
                var oldMeterNoStr = (row.old_meter_no != null) ? String(row.old_meter_no).trim() : '';
                var apiNewMeterNo = (row.new_meter_no != null) ? String(row.new_meter_no).trim() : '';
                var newMeterNoPrefill = apiNewMeterNo !== ''
                    ? apiNewMeterNo
                    : (oldMeterNoStr !== '' && oldMeterNoStr !== 'N/A' ? oldMeterNoStr : '');
                window.otherMeterRowData[row.pk + '_' + slot] = { curr_month_elec_red: '', new_meter_no: newMeterNoPrefill };

                var oldDisp = (row.old_meter_no != null && String(row.old_meter_no).trim() !== '') ? row.old_meter_no : (row.meter_no || 'N/A');
                var lastElecDisp = (row.last_month_reading != null && row.last_month_reading !== '') ? row.last_month_reading : 'N/A';

                tbody.append('<tr class="other-reading-row other-reading-row-single" data-dual="0" data-pk="' + row.pk + '" data-meter-slot="' + slot + '">' +
                    '<td><input type="checkbox" class="form-check-input row-check row-check-master" name="readings[' + idx + '][selected]" value="1"></td>' +
                    '<td>' + escAttr(row.house_no || 'N/A') + '</td>' +
                    '<td>' + escAttr(row.name || 'N/A') + '</td>' +
                    '<td class="text-nowrap">' + escAttr(row.last_reading_date || 'N/A') + '</td>' +
                    '<td>' + escAttr(oldDisp) + '</td>' +
                    '<td>' + escAttr(lastElecDisp) + '</td>' +
                    '<td><input type="text" class="form-control form-control-sm new-meter-no" name="readings[' + idx + '][new_meter_no]" value="' + escAttr(newMeterNoPrefill) + '" placeholder="' + newMeterNoPlaceholder + '" inputmode="numeric" maxlength="50" data-old-meter-no="' + escAttr(oldMeterNoStr) + '"' + newMeterNoLockAttr + '></td>' +
                    '<td><input type="number" class="form-control form-control-sm curr-reading" name="readings[' + idx + '][curr_month_elec_red]" value="" min="0" step="1" placeholder="Meter Reading" inputmode="numeric" data-last-reading="' + (lastReading !== null ? lastReading : '') + '" data-existing-curr="' + existingCurrStored.replace(/"/g, '&quot;') + '">' +
                    '<input type="hidden" name="readings[' + idx + '][pk]" value="' + row.pk + '">' +
                    '<input type="hidden" name="readings[' + idx + '][meter_slot]" value="' + slot + '"></td>' +
                    '<td class="unit-cell">—</td>' +
                    '</tr>');
            });

            showGrid(true);
        }).fail(function() {
            showError('Failed to load data. Please try again.');
        }).always(function() {
            $loadBtn.prop('disabled', false);
        });
    });

    /* ---------- Selection ---------- */
    $root.on('change', '#select_all', function() {
        var on = $(this).prop('checked');
        $root.find('#updateMeterReadingOtherTable .row-check-master').each(function() {
            $(this).prop('checked', on);
            var sid = $(this).data('pair-sel-id');
            if (sid) $root.find('#' + sid).prop('checked', on);
        });
    });

    $root.on('change', '.row-check-master', function() {
        var sid = $(this).data('pair-sel-id');
        if (sid) $root.find('#' + sid).prop('checked', $(this).prop('checked'));
    });

    /* ---------- Units + per-row validation ---------- */
    function getCurrInputMinAllowed($input) {
        var lastVal = $input.data('last-reading');
        var existingVal = $input.data('existing-curr');
        var lastReading = (lastVal !== '' && lastVal !== undefined && !isNaN(parseFloat(lastVal))) ? parseFloat(lastVal) : null;
        var existingCurr = (existingVal !== '' && existingVal !== undefined && !isNaN(parseFloat(existingVal))) ? parseFloat(existingVal) : null;
        if (lastReading === null && existingCurr === null) return null;
        if (existingCurr !== null && lastReading !== null) return Math.max(lastReading, existingCurr);
        return existingCurr !== null ? existingCurr : lastReading;
    }

    function getNewMeterInputForReading($inp) {
        var $row = $inp.closest('tr');
        if ($row.hasClass('other-dual-stacked')) {
            var slot = $inp.closest('.other-dual-seg').data('slot');
            if (slot !== undefined && slot !== null) {
                return $row.find('.other-dual-newmeter-col .other-dual-seg[data-slot="' + slot + '"] .new-meter-no');
            }
        }
        return $row.find('.new-meter-no').first();
    }

    function isMeterNoChangedForReading($inp) {
        var $meterInp = getNewMeterInputForReading($inp);
        var oldNo = String($meterInp.data('old-meter-no') || '').trim();
        if (!oldNo || oldNo === 'N/A') return false;
        var newNo = String($meterInp.val() || '').trim();
        if (!newNo) return false;
        return newNo !== oldNo;
    }

    function isCurrReadingBelowMinAllowed($inp, currReading) {
        if (currReading === null || isNaN(currReading)) return false;
        var minAllowed = getCurrInputMinAllowed($inp);
        if (minAllowed === null || currReading >= minAllowed) return false;
        // Meter replaced: the new meter starts fresh, so a lower reading is valid.
        if (isMeterNoChangedForReading($inp)) return false;
        return true;
    }

    function syncRowDataFromInputs($row) {
        var pk = $row.data('pk');
        if (!pk) return;
        window.otherMeterRowData = window.otherMeterRowData || {};
        if ($row.hasClass('other-dual-stacked')) {
            ['1', '2'].forEach(function(slot) {
                var key = pk + '_' + slot;
                if (!window.otherMeterRowData[key]) window.otherMeterRowData[key] = { curr_month_elec_red: '', new_meter_no: '' };
                var $segN = $row.find('.other-dual-newmeter-col .other-dual-seg[data-slot="' + slot + '"]');
                var $segR = $row.find('.other-dual-reading-col .other-dual-seg[data-slot="' + slot + '"]');
                window.otherMeterRowData[key].new_meter_no = $segN.find('.new-meter-no').val() || '';
                window.otherMeterRowData[key].curr_month_elec_red = $segR.find('.curr-reading').val() || '';
            });
            return;
        }
        var keyFlat = pk + '_' + String($row.data('meter-slot') || '1');
        if (!window.otherMeterRowData[keyFlat]) window.otherMeterRowData[keyFlat] = { curr_month_elec_red: '', new_meter_no: '' };
        window.otherMeterRowData[keyFlat].curr_month_elec_red = $row.find('.curr-reading').first().val() || '';
        window.otherMeterRowData[keyFlat].new_meter_no = $row.find('.new-meter-no').first().val() || '';
    }

    function refreshUnitForReadingInput($inp) {
        var $row = $inp.closest('tr');
        var lastVal = $inp.data('last-reading');
        var lastReading = (lastVal !== '' && lastVal !== undefined && !isNaN(parseFloat(lastVal))) ? parseFloat(lastVal) : null;
        var currVal = $inp.val();
        var currReading = (currVal !== '' && currVal !== null && !isNaN(parseFloat(currVal))) ? parseFloat(currVal) : null;

        var unit = '';
        if (isMeterNoChangedForReading($inp)) {
            // Meter replaced: the new meter starts at 0, so the reading is the unit.
            if (currReading !== null) unit = currReading;
        } else if (lastReading !== null && currReading !== null && currReading >= lastReading) {
            unit = currReading - lastReading;
        }
        var unitText = unit === '' ? '—' : String(unit);
        if ($row.hasClass('other-dual-stacked')) {
            var slot = $inp.closest('.other-dual-seg').data('slot');
            if (slot !== undefined && slot !== null) {
                $row.find('.other-dual-units .other-dual-seg[data-slot="' + slot + '"] .unit-cell').text(unitText);
            }
        } else {
            $row.children('td.unit-cell').text(unitText);
        }
    }

    $root.on('input change', '.new-meter-no', function() {
        this.value = String(this.value || '').replace(/\D/g, '').slice(0, 50);
        var $meterInp = $(this);
        var $row = $meterInp.closest('tr');
        syncRowDataFromInputs($row);

        // A changed meter number changes what a unit is measured from.
        var $readingInp;
        if ($row.hasClass('other-dual-stacked')) {
            var slot = $meterInp.closest('.other-dual-seg').data('slot');
            $readingInp = $row.find('.other-dual-reading-col .other-dual-seg[data-slot="' + slot + '"] .curr-reading');
        } else {
            $readingInp = $row.find('.curr-reading').first();
        }
        if ($readingInp && $readingInp.length) refreshUnitForReadingInput($readingInp);
    });

    $root.on('keydown', '.new-meter-no', function(e) {
        if (['e', 'E', '+', '-', '.', ','].includes(e.key)) e.preventDefault();
    });

    $root.on('input', '.curr-reading', function() {
        var $inp = $(this);
        syncRowDataFromInputs($inp.closest('tr'));
        refreshUnitForReadingInput($inp);
    });

    var BELOW_MIN_MSG = 'Current Month Reading cannot be less than Last Month Meter Reading. '
        + 'If the meter was replaced or the saved reading is wrong, open this row from List Meter Reading → Edit.';

    $root.on('blur', '.curr-reading', function() {
        var $inp = $(this);
        var currVal = $inp.val();
        var currReading = (currVal !== '' && currVal !== null && !isNaN(parseFloat(currVal))) ? parseFloat(currVal) : null;
        if (isCurrReadingBelowMinAllowed($inp, currReading)) {
            lastInvalidReadingAlertAt = Date.now();
            showError(BELOW_MIN_MSG);
        }
    });

    /* ---------- Save ---------- */
    function validateBeforeSave() {
        if (!val('bill_month')) {
            showError('Please select Meter Change Month.');
            $f('bill_month').trigger('focus');
            return false;
        }
        var meterReadingDate = (val('meter_reading_date') || '').trim();
        if (!meterReadingDate) {
            showError('Meter reading date is mandatory. Please select Meter Reading Date before saving.');
            $f('meter_reading_date').trigger('focus');
            return false;
        }
        var $checked = $root.find('#updateMeterReadingOtherTable .row-check-master:checked');
        if ($checked.length === 0) {
            showError('Please select at least one record by ticking its checkbox before saving.');
            return false;
        }

        var hasEmptyReading = false;
        $checked.each(function() {
            $(this).closest('tr').find('.curr-reading').each(function() {
                var v = $(this).val();
                if (v === null || String(v).trim() === '') {
                    hasEmptyReading = true;
                    $(this).trigger('focus');
                    return false;
                }
            });
            if (hasEmptyReading) return false;
        });
        if (hasEmptyReading) {
            showError('Please fill Current Month Reading for all selected rows.');
            return false;
        }

        var hasInvalidReading = false;
        $checked.each(function() {
            $(this).closest('tr').find('.curr-reading').each(function() {
                var $inp = $(this);
                var currReading = parseFloat(String($inp.val()).trim());
                if (!isNaN(currReading) && isCurrReadingBelowMinAllowed($inp, currReading)) {
                    hasInvalidReading = true;
                    $inp.trigger('focus');
                    return false;
                }
            });
            if (hasInvalidReading) return false;
        });
        if (hasInvalidReading) {
            lastInvalidReadingAlertAt = Date.now();
            showError(BELOW_MIN_MSG);
            return false;
        }

        $f('reading_bill_month_hidden').val(val('bill_month'));
        $f('reading_meter_reading_date_hidden').val(meterReadingDate);
        return true;
    }

    $saveBtn.on('click', function() {
        showError('');
        if (!validateBeforeSave()) return;

        // Outside the modal this stays a plain POST — the page redirects as before.
        if (!inModal) {
            $saveForm[0].submit();
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Saving…');
        $.ajax({
            url: $saveForm.attr('action'),
            type: 'POST',
            data: $saveForm.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function(res) {
                document.dispatchEvent(new CustomEvent('epo:readings-saved', {
                    detail: { message: (res && res.message) || 'Meter readings updated successfully.' }
                }));
            },
            error: function(xhr) {
                var payload = xhr.responseJSON || {};
                showError(payload.message || 'Could not save the readings. Please try again.');
            },
            complete: function() { $btn.prop('disabled', false).text('Save'); }
        });
    });

    /* ---------- Prefill (opened from a possession row) ---------- */
    if (prefill) {
        if (prefill.bill_month) $f('bill_month').val(prefill.bill_month);
        if (prefill.estate_campus_master_pk) {
            $f('estate_name').val(String(prefill.estate_campus_master_pk)).trigger('change');
        }
    }
}
})();
</script>
