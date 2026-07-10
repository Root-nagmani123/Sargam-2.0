@extends('admin.layouts.master')

@php
    $estateBillIsPersonalView = $estateBillIsPersonalView ?? false;
    $estateBillPageLabel = $estateBillIsPersonalView ? 'My Estate Bill' : 'Generate Estate Bill';
    $estateSelfHomeTab = request('scope') === 'self'
        && (isEstateAuthority());
    $showGenerateEstateBillSearch = $showGenerateEstateBillSearch ?? false;
    $showUnitSubTypeFilter = $showUnitSubTypeFilter ?? false;
    $genBillActionsCol = $showGenerateEstateBillSearch
        ? ($showUnitSubTypeFilter ? 'col-lg-3' : 'col-lg-4')
        : 'col-lg-9';
@endphp

@section('title', $estateBillPageLabel . ' - Sargam')

@section($estateSelfHomeTab ? 'content' : 'setup_content')
<div class="container-fluid estate-bill-page">
    <x-breadcrum title="{{ $estateBillPageLabel }}"></x-breadcrum>
    <x-session_message />

    <!-- Filters -->
    <div class="ds-card mb-4">
        <div class="ds-card-body p-4">
            <div class="im-card-head">
                <div>
                    <h1 class="h5 fw-bold mb-1">{{ $estateBillPageLabel }}</h1>
                    <p class="text-body-secondary small mb-0">
                        @if($estateBillIsPersonalView)
                        View your estate bill summary. Select bill month and (where available) unit sub type to review bills.
                        @else
                        View and generate estate bill summary. Select bill month and unit sub type, then use actions to notify or save as draft.
                        @endif
                    </p>
                </div>
            </div>
            <form method="get" action="{{ route('admin.estate.generate-estate-bill') }}" class="row g-3 g-md-4 align-items-end">
                @if(request('scope') === 'self')
                <input type="hidden" name="scope" value="self">
                @endif
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <label for="bill_month" class="form-label">Bill Month <span class="text-danger">*</span></label>
                    <input type="month" class="form-control" id="bill_month" name="bill_month" value="{{ old('bill_month', $billMonth) }}" max="{{ date('Y-m') }}" data-max-date="{{ date('Y-m-d') }}" required aria-describedby="bill_month_help">
                    <div id="bill_month_help" class="form-text small">Select the month for billing</div>
                </div>
                @if($showUnitSubTypeFilter)
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <label for="unit_sub_type_pk" class="form-label">Unit Sub Type </label>
                    <select class="form-select" id="unit_sub_type_pk" name="unit_sub_type_pk" aria-label="Select Unit Sub Type" aria-describedby="unit_sub_type_help">
                        <option value="">— Select Unit Sub Type —</option>
                        @foreach($unitSubTypes as $ust)
                        <option value="{{ $ust->pk }}" {{ (string)$unitSubTypePk === (string)$ust->pk ? 'selected' : '' }}>{{ $ust->unit_sub_type }}</option>
                        @endforeach
                    </select>
                    <div id="unit_sub_type_help" class="form-text small">Filter by unit category</div>
                </div>
                @endif
                @if($showGenerateEstateBillSearch)
                <div class="col-12 col-sm-6 col-md-6 {{ $showUnitSubTypeFilter ? 'col-lg-3' : 'col-lg-5' }}">
                    <label for="search" class="form-label">Search</label>
                    <input type="search" class="form-control" id="search" name="search" value="{{ old('search', $search ?? '') }}" placeholder="House, bill no., name…" autocomplete="off" aria-describedby="search_help" title="Also: month/year, designation, employee type, unit e.g. Type-(12)">
                    <div id="search_help" class="form-text small mb-0">Bill, house, name, designation, type.</div>
                </div>
                @endif
                <div class="col-12 col-sm-6 col-md-6 {{ $genBillActionsCol }}">
                    {{-- Match Search column: label + field + form-text so align-items-end lines up with the input row, not the hint --}}
                    <div class="form-label invisible user-select-none" aria-hidden="true">&nbsp;</div>
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="check_all" name="check_all" aria-describedby="check_all_help">
                            <label class="form-check-label text-nowrap" for="check_all">Check All</label>
                        </div>
                        <span id="check_all_help" class="visually-hidden">Select or clear all bill checkboxes</span>
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 flex-shrink-0 text-nowrap">
                            <i class="material-symbols-rounded" style="font-size: 1.1rem;">visibility</i>
                            Show
                        </button>
                    </div>
                    <div class="form-text small mb-0 invisible user-select-none" aria-hidden="true">&nbsp;</div>
                </div>
                <div class="col-12 mt-2 pt-3 border-top">
                    <div class="d-flex gap-2 flex-wrap justify-content-sm-end align-items-center">
                        <button type="button" id="btn_print_selected" class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-1" title="Print selected bills in a single tab">
                            <i class="material-symbols-rounded" style="font-size: 1rem;">print</i>
                            Print Selected
                        </button>
                        @if($showUnitSubTypeFilter)
                        <button type="button" id="btn_notify_selected" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">Notify Selected</button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="status-msg" class="mb-3" style="display: none;" aria-live="polite"></div>

    @if($billMonth)
    <!-- Section title and print actions -->
    <div class="im-bill-banner">
        <p class="im-bill-banner-title mb-0">LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION, MUSSOORIE — ESTATE BILL</p>
        <div class="d-flex gap-2 flex-wrap justify-content-center">
            <a href="{{ route('admin.estate.reports.bill-report-print-all', array_filter(['bill_month' => $billMonth, 'unit_sub_type_pk' => $unitSubTypePk, 'scope' => request('scope') === 'self' ? 'self' : null], static fn ($v) => $v !== null && $v !== '')) }}" target="_blank" rel="noopener" id="btn_print_all" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1" title="View all bills in one page — print or download as PDF at once">
                <i class="material-symbols-rounded" style="font-size: 1rem;">print</i>
                Print All
            </a>
        </div>
    </div>

    <!-- Scrollable bill cards area -->
    <div class="bill-cards-wrapper">
        <div class="p-3 p-md-4">
            @forelse($bills as $bill)
            <div class="im-bill-card bill-card" data-bill-no="{{ $bill->bill_no ?? '' }}" data-bill-month="{{ $bill->bill_month ?? '' }}" data-bill-year="{{ $bill->bill_year ?? '' }}">
                <div class="p-4 position-relative">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3 pb-2 border-bottom">
                        <div class="form-check form-check-lg mb-0">
                            <input class="form-check-input bill-checkbox" type="checkbox" value="{{ $bill->pk }}" id="bill_{{ $bill->pk }}" data-bill-pk="{{ $bill->pk }}">
                            <label class="form-check-label text-body-secondary small" for="bill_{{ $bill->pk }}">Select this bill</label>
                        </div>
                        <a href="{{ route('admin.estate.reports.bill-report-print') }}?bill_no={{ $bill->bill_no }}&month={{ $bill->bill_month }}&year={{ $bill->bill_year }}" target="_blank" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1" title="Print this bill">
                            <i class="material-symbols-rounded" style="font-size: 1rem;">print</i>
                            Print
                        </a>
                    </div>

                    <div class="row g-3 g-md-4 mb-3">
                        <div class="col-12 col-md-6">
                            <table class="table table-borderless table-sm mb-0 im-bill-kv">
                                <tbody>
                                    <tr>
                                        <td class="im-kv-label" style="width: 42%;">Bill No.</td>
                                        <td class="fw-semibold">{{ $bill->bill_no ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="im-kv-label">Month</td>
                                        <td>{{ $bill->bill_month ?? '' }} {{ $bill->bill_year ?? '' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="im-kv-label">Emp Name</td>
                                        <td><span class="text-primary fw-medium">{{ $bill->emp_name ?? '—' }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="im-kv-label">Designation</td>
                                        <td>{{ $bill->emp_designation ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="im-kv-label">Employee Type</td>
                                        <td><span class="badge text-bg-secondary">REGULAR</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-12 col-md-6">
                            <table class="table table-borderless table-sm mb-0 im-bill-kv">
                                <tbody>
                                    <tr>
                                        <td class="im-kv-label" style="width: 42%;">House No.</td>
                                        <td><span class="text-primary fw-medium">{{ $bill->house_display ?? '—' }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="im-kv-label">From Date</td>
                                        <td>{{ $bill->from_date_formatted ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="im-kv-label">To Date</td>
                                        <td>{{ $bill->to_date_formatted ?? '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="im-meter">
                        <h6 class="im-meter-title">Meter One</h6>
                        <div class="row row-cols-2 row-cols-md-5 g-2 g-md-3">
                            <div class="col"><span class="im-meter-label">Meter No.</span><span class="fw-medium">{{ $bill->meter_one ?? '—' }}</span></div>
                            <div class="col"><span class="im-meter-label">Previous Reading</span><span>{{ $bill->last_month_elec_red ?? '—' }}</span></div>
                            <div class="col"><span class="im-meter-label">Current Reading</span><span>{{ $bill->curr_month_elec_red ?? '—' }}</span></div>
                            <div class="col"><span class="im-meter-label">Consumed Unit</span><span>{{ $bill->meter_one_consume_unit ?? '—' }}</span></div>
                            <div class="col"><span class="im-meter-label">Electricity Bill</span><span>₹ {{ number_format((float)($bill->meter_one_elec_charge ?? 0), 2) }}</span></div>
                        </div>
                    </div>

                    @php
                    $hasMeterTwo = isset($bill->meter_two) && (int)$bill->meter_two !== 0;
                    $hasMeterTwoUnits = isset($bill->meter_two_consume_unit) && (int)$bill->meter_two_consume_unit > 0;
                    @endphp
                    @if($hasMeterTwo || $hasMeterTwoUnits)
                    <div class="im-meter">
                        <h6 class="im-meter-title">Meter Two</h6>
                        <div class="row row-cols-2 row-cols-md-5 g-2 g-md-3">
                            <div class="col"><span class="im-meter-label">Meter No.</span><span class="fw-medium">{{ $bill->meter_two ?? '—' }}</span></div>
                            <div class="col"><span class="im-meter-label">Previous Reading</span><span>{{ $bill->last_month_elec_red2 ?? '—' }}</span></div>
                            <div class="col"><span class="im-meter-label">Current Reading</span><span>{{ $bill->curr_month_elec_red2 ?? '—' }}</span></div>
                            <div class="col"><span class="im-meter-label">Consumed Unit</span><span>{{ $bill->meter_two_consume_unit ?? '—' }}</span></div>
                            <div class="col"><span class="im-meter-label">Electricity Bill</span><span>₹ {{ number_format((float)($bill->meter_two_elec_charge ?? 0), 2) }}</span></div>
                        </div>
                    </div>
                    @endif

                    <div class="im-bill-totals">
                        <div class="row g-2 g-md-3 align-items-center flex-wrap">
                            <div class="col-12 col-md-auto">
                                <span class="im-total-label">Total Consumed Unit</span>
                                <strong class="d-inline-block ms-1">{{ $bill->total_consumed_unit ?? $bill->meter_one_consume_unit ?? 0 }}</strong>
                            </div>
                            <div class="col-12 col-md-auto"><span class="im-total-label">Total Electricity</span> <strong class="d-inline-block ms-1">₹ {{ number_format((float)($bill->electricty_charges ?? 0), 2) }}</strong></div>
                            <div class="col-12 col-md-auto"><span class="im-total-label">Licence Fee</span> <strong class="d-inline-block ms-1">₹ {{ number_format((float)($bill->licence_fees ?? 0), 2) }}</strong> <small class="text-body-secondary">(not Outside Recovery)</small></div>
                            <div class="col-12 col-md-auto"><span class="im-total-label">Water Charge</span> <strong class="d-inline-block ms-1">₹ {{ number_format((float)($bill->water_charges ?? 0), 2) }}</strong></div>
                            <div class="col-12 col-md-auto ms-md-auto"><span class="im-total-label">Grand Total</span> <strong class="im-grand-total ms-1">₹ {{ number_format($bill->grand_total ?? 0, 2) }}</strong></div>
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <small class="text-body-secondary fst-italic">(ESTATE SECTION)</small>
                    </div>
                </div>
            </div>
            @empty
            <div class="im-empty">
                <i class="material-symbols-rounded" aria-hidden="true">receipt_long</i>
                <p class="mb-1 fw-semibold text-body-emphasis">No bills found</p>
                <p class="text-body-secondary small mb-0">No bills are available for the selected month and unit sub type. Try changing the filters.</p>
            </div>
            @endforelse
        </div>
    </div>
    @endif
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
/* =====================================================================
   Generate Estate Bill — page-scoped polish.
   Tokens/components come from sargam-app.css (--ds-*, .ds-*).
   Scoped to .estate-bill-page so nothing leaks to other pages.
   ===================================================================== */
.ts-dropdown { z-index: 1060 !important; }

/* Filter card header */
.estate-bill-page .im-card-head {
    margin-bottom: var(--ds-space-4);
    padding-bottom: var(--ds-space-3);
    border-bottom: 1px solid var(--ds-line);
}
.estate-bill-page .im-card-head h1 { color: var(--ds-ink); }

/* Labels + controls */
.estate-bill-page .form-label { font-size: 0.8125rem; font-weight: 600; color: var(--ds-ink); margin-bottom: 0.35rem; }
.estate-bill-page .form-text { font-size: 0.8125rem; color: var(--ds-ink-muted); }
.estate-bill-page .form-control,
.estate-bill-page .form-select { border-radius: var(--ds-radius-1); font-size: 0.9rem; }
.estate-bill-page .form-control:focus,
.estate-bill-page .form-select:focus { border-color: #86b7fe; box-shadow: var(--ds-focus-ring); }
.estate-bill-page .btn-primary { border-radius: var(--ds-radius-1); font-weight: 600; }

/* Bill banner */
.estate-bill-page .im-bill-banner {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    gap: var(--ds-space-3);
    margin-bottom: var(--ds-space-4);
}
@media (min-width: 576px) { .estate-bill-page .im-bill-banner { flex-direction: row; } }
.estate-bill-page .im-bill-banner-title {
    font-size: 0.9375rem;
    font-weight: 700;
    letter-spacing: 0.01em;
    color: var(--ds-ink);
    padding: 0.6rem 1rem;
    background: var(--ds-surface-2);
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-2);
}

/* Scroll area holding bill cards */
.estate-bill-page .bill-cards-wrapper {
    max-height: 65vh;
    overflow: auto;
    -webkit-overflow-scrolling: touch;
    background: var(--ds-surface-2);
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-2);
}

/* Individual bill card */
.estate-bill-page .im-bill-card {
    background: #fff;
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-2);
    box-shadow: var(--ds-shadow-sm);
    margin-bottom: var(--ds-space-3);
}
.estate-bill-page .im-bill-card:last-child { margin-bottom: 0; }
.estate-bill-page .im-bill-card.border-primary { box-shadow: 0 0 0 2px var(--bs-primary); }

/* Key/value info tables */
.estate-bill-page .im-bill-kv td { padding: 0.28rem 0.5rem 0.28rem 0; font-size: 0.9rem; color: var(--ds-ink); }
.estate-bill-page .im-kv-label { color: var(--ds-ink-muted); padding-right: 1rem !important; white-space: nowrap; }

/* Meter sections */
.estate-bill-page .im-meter {
    border-top: 1px solid var(--ds-line);
    padding-top: var(--ds-space-3);
    margin-top: var(--ds-space-3);
}
.estate-bill-page .im-meter-title {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--ds-ink-muted);
    margin-bottom: var(--ds-space-2);
}
.estate-bill-page .im-meter-label { display: block; font-size: 0.75rem; color: var(--ds-ink-muted); }
.estate-bill-page .im-meter .col span:last-child { font-size: 0.9rem; color: var(--ds-ink); }

/* Totals strip */
.estate-bill-page .im-bill-totals {
    margin-top: var(--ds-space-3);
    padding: var(--ds-space-3);
    background: var(--ds-surface-2);
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-1);
}
.estate-bill-page .im-total-label { font-size: 0.8125rem; color: var(--ds-ink-muted); }
.estate-bill-page .im-bill-totals strong { color: var(--ds-ink); font-size: 0.9rem; }
.estate-bill-page .im-grand-total { color: var(--bs-primary) !important; font-size: 1.05rem; font-weight: 700; }

/* Empty state (matches issue-management index) */
.estate-bill-page .im-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 3rem 1rem;
}
.estate-bill-page .im-empty i { font-size: 56px; color: #98a2b3; margin-bottom: 0.75rem; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var billMonthInput = document.getElementById('bill_month');
        if (billMonthInput) {
            var monthInputProbe = document.createElement('input');
            monthInputProbe.setAttribute('type', 'month');
            var supportsMonthInput = monthInputProbe.type === 'month';

            if (!supportsMonthInput) {
                var existingMonth = (billMonthInput.value || '').trim();
                var maxMonth = (billMonthInput.getAttribute('max') || '').trim();
                var maxYear = /^\d{4}-(0[1-9]|1[0-2])$/.test(maxMonth) ? parseInt(maxMonth.slice(0, 4), 10) : new Date().getFullYear();
                var maxMonthNumber = /^\d{4}-(0[1-9]|1[0-2])$/.test(maxMonth) ? parseInt(maxMonth.slice(5, 7), 10) : 12;
                var selectedYear = /^\d{4}-(0[1-9]|1[0-2])$/.test(existingMonth) ? parseInt(existingMonth.slice(0, 4), 10) : maxYear;
                var selectedMonth = /^\d{4}-(0[1-9]|1[0-2])$/.test(existingMonth) ? existingMonth.slice(5, 7) : '';
                var startYear = Math.min(2000, selectedYear);

                billMonthInput.setAttribute('type', 'hidden');

                var wrapper = document.createElement('div');
                wrapper.className = 'd-flex gap-2';

                var monthSelect = document.createElement('select');
                monthSelect.className = 'form-select';
                monthSelect.setAttribute('aria-label', 'Select Month');
                monthSelect.required = true;

                var yearSelect = document.createElement('select');
                yearSelect.className = 'form-select';
                yearSelect.setAttribute('aria-label', 'Select Year');
                yearSelect.required = true;

                var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                for (var i = 1; i <= 12; i++) {
                    var m = String(i).padStart(2, '0');
                    var monthOpt = document.createElement('option');
                    monthOpt.value = m;
                    monthOpt.textContent = monthNames[i - 1];
                    monthSelect.appendChild(monthOpt);
                }

                for (var y = maxYear; y >= startYear; y--) {
                    var yearOpt = document.createElement('option');
                    yearOpt.value = String(y);
                    yearOpt.textContent = String(y);
                    yearSelect.appendChild(yearOpt);
                }

                yearSelect.value = String(selectedYear);
                if (selectedMonth) monthSelect.value = selectedMonth;

                var syncBillMonthValue = function() {
                    var y = yearSelect.value;
                    var m = monthSelect.value;
                    if (!y || !m) {
                        billMonthInput.value = '';
                        return;
                    }
                    if (parseInt(y, 10) === maxYear && parseInt(m, 10) > maxMonthNumber) {
                        m = String(maxMonthNumber).padStart(2, '0');
                        monthSelect.value = m;
                    }
                    billMonthInput.value = y + '-' + m;
                };

                wrapper.appendChild(monthSelect);
                wrapper.appendChild(yearSelect);
                billMonthInput.insertAdjacentElement('afterend', wrapper);
                monthSelect.addEventListener('change', syncBillMonthValue);
                yearSelect.addEventListener('change', syncBillMonthValue);
                syncBillMonthValue();
            }
        }

        if (typeof TomSelect !== 'undefined') {
            var unitSubEl = document.getElementById('unit_sub_type_pk');
            if (unitSubEl && !unitSubEl.tomselect) {
                new TomSelect(unitSubEl, {
                    allowEmptyOption: true,
                    create: false,
                    dropdownParent: 'body',
                    placeholder: '— Select Unit Sub Type —',
                    maxOptions: null,
                    hideSelected: false,
                    onInitialize: function() {
                        this.activeOption = null;
                    }
                });
            }
        }

        var checkAll = document.getElementById('check_all');
        var boxes = document.querySelectorAll('.bill-checkbox');
        if (checkAll) {
            checkAll.addEventListener('change', function() {
                boxes.forEach(function(el) {
                    el.checked = checkAll.checked;
                });
            });
        }

        var basePrintUrl = '{{ route("admin.estate.reports.bill-report-print") }}';
        var printAllUrl = '{{ route("admin.estate.reports.bill-report-print-all") }}';

        function buildPrintUrl(billNo, month, year) {
            return basePrintUrl + '?bill_no=' + encodeURIComponent(billNo) + '&month=' + encodeURIComponent(month) + '&year=' + encodeURIComponent(year);
        }

        function getBillCardsToPrint(selectedOnly) {
            var cards = document.querySelectorAll('.bill-card');
            if (selectedOnly) {
                var checked = document.querySelectorAll('.bill-checkbox:checked');
                return Array.prototype.filter.call(cards, function(card) {
                    var cb = card.querySelector('.bill-checkbox');
                    return cb && cb.checked;
                });
            }
            return Array.prototype.slice.call(cards);
        }

        function openSelectedPrintInSingleTab(cards) {
            if (!cards.length) {
                alert('Please select at least one bill to print.');
                return;
            }
            var selectedPks = [];
            cards.forEach(function(card) {
                var cb = card.querySelector('.bill-checkbox');
                var v = cb ? parseInt(cb.value, 10) : 0;
                if (v > 0) selectedPks.push(v);
            });
            if (!selectedPks.length) {
                alert('Please select at least one bill to print.');
                return;
            }

            var form = document.querySelector('form[action*="generate-estate-bill"]');
            var billMonthEl = form ? form.querySelector('#bill_month') : null;
            var unitSubTypeEl = form ? form.querySelector('#unit_sub_type_pk') : null;
            var billMonth = billMonthEl ? (billMonthEl.value || '').trim() : '';
            var unitSubTypePk = unitSubTypeEl ? (unitSubTypeEl.value || '').trim() : '';

            var params = new URLSearchParams();
            if (billMonth) params.set('bill_month', billMonth);
            if (unitSubTypePk) params.set('unit_sub_type_pk', unitSubTypePk);
            var scopeSelfEl = form ? form.querySelector('input[name="scope"][value="self"]') : null;
            if (scopeSelfEl) params.set('scope', 'self');
            params.set('selected_pks', selectedPks.join(','));

            window.open(printAllUrl + '?' + params.toString(), '_blank', 'noopener');
        }
        var btnPrintSelected = document.getElementById('btn_print_selected');
        if (btnPrintSelected) {
            btnPrintSelected.addEventListener('click', function() {
                openSelectedPrintInSingleTab(getBillCardsToPrint(true));
            });
        }
        // Print All: opens the print-all page (all bills in one view with option to print or download PDF)
        // Link is used instead of button; no extra script needed.

        function getSelectedBillPks() {
            var pks = [];
            document.querySelectorAll('.bill-checkbox:checked').forEach(function(el) {
                var v = parseInt(el.value, 10);
                if (v > 0) pks.push(v);
            });
            return pks;
        }

        function showStatusMessage(msg, type) {
            type = type || 'success';
            var alertClass = type === 'success' ? 'alert-success' : (type === 'error' ? 'alert-danger' : 'alert-warning');
            var icon = type === 'success' ? 'check_circle' : (type === 'error' ? 'error' : 'info');
            var statusEl = document.getElementById('status-msg');
            if (statusEl) {
                statusEl.innerHTML = '<div class="alert ' + alertClass + ' alert-dismissible fade show shadow-sm" role="alert">' +
                    '<i class="material-icons material-symbols-rounded me-2">' + icon + '</i> ' + msg +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                statusEl.style.display = 'block';
                setTimeout(function() {
                    statusEl.style.display = 'none';
                }, 4000);
            }
        }

        var btnNotify = document.getElementById('btn_notify_selected');
        if (btnNotify) {
            btnNotify.addEventListener('click', function() {
                var pks = getSelectedBillPks();
                if (pks.length === 0) {
                    showStatusMessage('Please select at least one bill to verify.', 'warning');
                    return;
                }
                btnNotify.disabled = true;
                var form = document.querySelector('form[action*="generate-estate-bill"]');
                var billMonth = form ? form.querySelector('#bill_month') : null;
                var unitSub = form ? form.querySelector('#unit_sub_type_pk') : null;
                var params = new URLSearchParams();
                pks.forEach(function(p) {
                    params.append('pks[]', p);
                });
                params.append('_token', '{{ csrf_token() }}');
                if (billMonth && billMonth.value) params.append('bill_month', billMonth.value);
                if (unitSub && unitSub.value) params.append('unit_sub_type_pk', unitSub.value);
                fetch('{{ route("admin.estate.generate-estate-bill.verify-selected") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: params.toString()
                }).then(function(r) {
                    return r.json();
                }).then(function(res) {
                    btnNotify.disabled = false;
                    if (res.status && res.message) {
                        showStatusMessage(res.message, 'success');
                    }
                }).catch(function() {
                    btnNotify.disabled = false;
                    showStatusMessage('Failed to verify bills.', 'error');
                });
            });
        }

        var btnDraft = document.getElementById('btn_save_as_draft');
        if (btnDraft) {
            btnDraft.addEventListener('click', function() {
                var pks = getSelectedBillPks();
                if (pks.length === 0) {
                    showStatusMessage('Please select at least one bill to save as draft.', 'warning');
                    return;
                }
                btnDraft.disabled = true;
                var form = document.querySelector('form[action*="generate-estate-bill"]');
                var billMonth = form ? form.querySelector('#bill_month') : null;
                var params = new URLSearchParams();
                pks.forEach(function(p) {
                    params.append('pks[]', p);
                });
                params.append('_token', '{{ csrf_token() }}');
                if (billMonth && billMonth.value) params.append('bill_month', billMonth.value);
                fetch('{{ route("admin.estate.generate-estate-bill.save-as-draft") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: params.toString()
                }).then(function(r) {
                    return r.json();
                }).then(function(res) {
                    btnDraft.disabled = false;
                    if (res.status && res.message) {
                        showStatusMessage(res.message, 'success');
                        if (form && billMonth && billMonth.value) form.submit();
                    }
                }).catch(function() {
                    btnDraft.disabled = false;
                    showStatusMessage('Failed to save as draft.', 'error');
                });
            });
        }

        // If opened from notification, jump to the specific bill.
        // Expected query params:
        // - bill_no, bill_print_month, bill_print_year (used to locate the bill card)
        // - open_estate_bill=1 (optional: also auto-open print in new tab)
        (function() {
            var params = new URLSearchParams(window.location.search);
            var shouldOpen = params.get('open_estate_bill');

            var billNo = (params.get('bill_no') || '').trim();
            var billPrintMonth = (params.get('bill_print_month') || params.get('month') || '').trim();
            var billPrintYear = (params.get('bill_print_year') || params.get('year') || '').trim();

            var hasBillInfo = !!(billNo && billPrintMonth && billPrintYear);
            if (!hasBillInfo) return;

            // Best-effort scroll to the matching bill card (if present on the page).
            try {
                var cards = document.querySelectorAll('.bill-card');
                var target = null;
                cards.forEach(function(card) {
                    if (target) return;
                    var cNo = (card.getAttribute('data-bill-no') || '').trim();
                    var cMonth = (card.getAttribute('data-bill-month') || '').trim();
                    var cYear = (card.getAttribute('data-bill-year') || '').trim();
                    if (cNo === billNo && cMonth === billPrintMonth && cYear === billPrintYear) {
                        target = card;
                    }
                });

                if (target && target.scrollIntoView) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    target.classList.add('border', 'border-primary');
                }
            } catch (e) {}

            // Optional: also auto-open the specific print page.
            if (!shouldOpen || shouldOpen === '0' || shouldOpen === 'false') return;

            var printUrl = buildPrintUrl(billNo, billPrintMonth, billPrintYear);
            // Try open in a new tab; fallback to same-tab redirect when blocked.
            setTimeout(function() {
                var w = window.open(printUrl, '_blank', 'noopener');
                if (!w) {
                    window.location.href = printUrl;
                }
            }, 400);
        })();
    });
</script>
@endpush
@endsection
