@extends('admin.layouts.master')

@php
    $estateBillPageLabel = (hasRole('Admin') || hasRole('Super Admin') || hasRole('Estate'))
        ? 'Generate Estate Bill'
        : 'My Estate Bill';
@endphp

@section('title', $estateBillPageLabel . ' - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="{{ $estateBillPageLabel }}"></x-breadcrum>
    <x-session_message />

    <div class="d-flex flex-column flex-md-row flex-wrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 fw-semibold mb-1">{{ $estateBillPageLabel }}</h1>
            <p class="text-muted small mb-0">
                @if($estateBillPageLabel === 'Generate Estate Bill')
                    View and generate estate bill summary. Select bill month and unit sub type, then use actions to notify or save as draft.
                @else
                    View your estate bill summary. Select bill month and (where available) unit sub type to review bills.
                @endif
            </p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <h2 class="h6 fw-semibold text-body mb-0 d-flex align-items-center gap-2">
                <i class="material-symbols-rounded fs-5">filter_list</i>
                Filters
            </h2>
        </div>
        <div class="card-body p-4">
            <form method="get" action="{{ route('admin.estate.generate-estate-bill') }}">
                @php
                    $showUnitSubTypeFilter = hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin');
                @endphp
                {{-- Same structure in every column: label row → control row (equal height inputs) → help row, so Check All + Show line up with Bill / Unit / Search --}}
                <div class="row g-3 g-md-4 align-items-start">
                    <div class="col-12 col-sm-6 {{ $showUnitSubTypeFilter ? 'col-lg-3' : 'col-lg-4' }}">
                        <label for="bill_month" class="form-label fw-medium mb-1">Bill Month <span class="text-danger">*</span></label>
                        <input type="month" class="form-control" id="bill_month" name="bill_month" value="{{ old('bill_month', $billMonth) }}" max="{{ date('Y-m') }}" required aria-describedby="bill_month_help">
                        <div id="bill_month_help" class="form-text small mb-0">Select the month for billing</div>
                    </div>
                    @if($showUnitSubTypeFilter)
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label for="unit_sub_type_pk" class="form-label fw-medium mb-1">Unit Sub Type</label>
                        <div class="estate-bill-unit-sub-ts">
                            <select class="form-select" id="unit_sub_type_pk" name="unit_sub_type_pk" aria-label="Select Unit Sub Type" aria-describedby="unit_sub_type_help">
                                <option value="">Select Unit Sub Type</option>
                                @foreach($unitSubTypes as $ust)
                                    <option value="{{ $ust->pk }}" {{ (string)$unitSubTypePk === (string)$ust->pk ? 'selected' : '' }}>{{ $ust->unit_sub_type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="unit_sub_type_help" class="form-text small mb-0">Filter by unit category</div>
                    </div>
                    @endif
                    <div class="col-12 col-sm-6 {{ $showUnitSubTypeFilter ? 'col-lg-3' : 'col-lg-4' }}">
                        <label for="search" class="form-label fw-medium mb-1">Search</label>
                        <input type="search" class="form-control" id="search" name="search" value="{{ old('search', $search ?? '') }}" placeholder="House, bill no., name…" autocomplete="off" aria-describedby="search_help" title="Also: month/year, designation, employee type, unit e.g. Type-(12)">
                        <div id="search_help" class="form-text small mb-0 text-muted">Bill, house, name, designation, type.</div>
                    </div>
                    <div class="col-12 col-sm-6 {{ $showUnitSubTypeFilter ? 'col-lg-3' : 'col-lg-4' }}">
                        <div class="form-label fw-medium mb-1 text-body invisible user-select-none" aria-hidden="true">Unit Sub Type</div>
                        <div class="d-flex flex-nowrap align-items-center gap-3 estate-bill-filter-actions-controls">
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
                        <div class="form-text small mb-0 invisible user-select-none" aria-hidden="true">Filter by unit category</div>
                    </div>
                </div>
                <div class="row g-3 mt-2 pt-3 border-top align-items-center">
                    <div class="col-12 d-flex gap-2 flex-wrap justify-content-sm-end align-items-center">
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
    <div class="d-flex flex-column flex-sm-row flex-wrap align-items-center justify-content-center justify-content-sm-between gap-3 mb-4">
        <p class="lead fw-semibold text-body mb-0 py-2 px-3 bg-body-secondary rounded-3 d-inline-block">LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION, MUSSOORIE — ESTATE BILL</p>
        <div class="d-flex gap-2 flex-wrap justify-content-center">
            <a href="{{ route('admin.estate.reports.bill-report-print-all', ['bill_month' => $billMonth, 'unit_sub_type_pk' => $unitSubTypePk]) }}" target="_blank" rel="noopener" id="btn_print_all" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1" title="View all bills in one page — print or download as PDF at once">
                <i class="material-symbols-rounded" style="font-size: 1rem;">print</i>
                Print All
            </a>
        </div>
    </div>

    <!-- Scrollable bill cards area -->
    <div class="bill-cards-wrapper border border-2 rounded-3 bg-body-secondary overflow-auto" style="max-height: 65vh;">
        <div class="p-3 p-md-4">
            @forelse($bills as $bill)
            <div class="card shadow-sm border-0 rounded-3 mb-3 bill-card" data-bill-no="{{ $bill->bill_no ?? '' }}" data-bill-month="{{ $bill->bill_month ?? '' }}" data-bill-year="{{ $bill->bill_year ?? '' }}">
                <div class="card-body p-4 position-relative">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3 pb-2 border-bottom">
                        <div class="form-check form-check-lg mb-0">
                            <input class="form-check-input bill-checkbox" type="checkbox" value="{{ $bill->pk }}" id="bill_{{ $bill->pk }}" data-bill-pk="{{ $bill->pk }}">
                            <label class="form-check-label text-muted small" for="bill_{{ $bill->pk }}">Select this bill</label>
                        </div>
                        <a href="{{ route('admin.estate.reports.bill-report-print') }}?bill_no={{ $bill->bill_no }}&month={{ $bill->bill_month }}&year={{ $bill->bill_year }}" target="_blank" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1" title="Print this bill">
                            <i class="material-symbols-rounded">print</i>
                            Print
                        </a>
                    </div>

                    <div class="row g-3 g-md-4 mb-3">
                        <div class="col-12 col-md-6">
                            <table class="table table-borderless table-sm mb-0">
                                <tbody>
                                    <tr><td class="text-muted pe-3 text-nowrap" style="width: 42%;">Bill No.</td><td class="fw-semibold">{{ $bill->bill_no ?? '—' }}</td></tr>
                                    <tr><td class="text-muted">Month</td><td>{{ $bill->bill_month ?? '' }} {{ $bill->bill_year ?? '' }}</td></tr>
                                    <tr><td class="text-muted">Emp Name</td><td><span class="text-primary fw-medium">{{ $bill->emp_name ?? '—' }}</span></td></tr>
                                    <tr><td class="text-muted">Designation</td><td>{{ $bill->emp_designation ?? '—' }}</td></tr>
                                    <tr><td class="text-muted">Employee Type</td><td><span class="badge text-bg-secondary">REGULAR</span></td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-12 col-md-6">
                            <table class="table table-borderless table-sm mb-0">
                                <tbody>
                                    <tr><td class="text-muted pe-3 text-nowrap" style="width: 42%;">House No.</td><td><span class="text-primary fw-medium">{{ $bill->house_display ?? '—' }}</span></td></tr>
                                    <tr><td class="text-muted">From Date</td><td>{{ $bill->from_date_formatted ?? '—' }}</td></tr>
                                    <tr><td class="text-muted">To Date</td><td>{{ $bill->to_date_formatted ?? '—' }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-2">
                        <h6 class="text-secondary small text-uppercase fw-semibold mb-2 opacity-75">Meter One</h6>
                        <div class="row row-cols-2 row-cols-md-5 g-2 g-md-3">
                            <div class="col"><span class="text-muted small d-block">Meter No.</span><span class="fw-medium">{{ $bill->meter_one ?? '—' }}</span></div>
                            <div class="col"><span class="text-muted small d-block">Previous Reading</span><span>{{ $bill->last_month_elec_red ?? '—' }}</span></div>
                            <div class="col"><span class="text-muted small d-block">Current Reading</span><span>{{ $bill->curr_month_elec_red ?? '—' }}</span></div>
                            <div class="col"><span class="text-muted small d-block">Consumed Unit</span><span>{{ $bill->meter_one_consume_unit ?? '—' }}</span></div>
                            <div class="col"><span class="text-muted small d-block">Electricity Bill</span><span>₹ {{ number_format((float)($bill->meter_one_elec_charge ?? 0), 2) }}</span></div>
                        </div>
                    </div>

                    @php
                        $hasMeterTwo = isset($bill->meter_two) && (int)$bill->meter_two !== 0;
                        $hasMeterTwoUnits = isset($bill->meter_two_consume_unit) && (int)$bill->meter_two_consume_unit > 0;
                    @endphp
                    @if($hasMeterTwo || $hasMeterTwoUnits)
                    <div class="border-top pt-3 mt-3">
                        <h6 class="text-secondary small text-uppercase fw-semibold mb-2 opacity-75">Meter Two</h6>
                        <div class="row row-cols-2 row-cols-md-5 g-2 g-md-3">
                            <div class="col"><span class="text-muted small d-block">Meter No.</span><span class="fw-medium">{{ $bill->meter_two ?? '—' }}</span></div>
                            <div class="col"><span class="text-muted small d-block">Previous Reading</span><span>{{ $bill->last_month_elec_red2 ?? '—' }}</span></div>
                            <div class="col"><span class="text-muted small d-block">Current Reading</span><span>{{ $bill->curr_month_elec_red2 ?? '—' }}</span></div>
                            <div class="col"><span class="text-muted small d-block">Consumed Unit</span><span>{{ $bill->meter_two_consume_unit ?? '—' }}</span></div>
                            <div class="col"><span class="text-muted small d-block">Electricity Bill</span><span>₹ {{ number_format((float)($bill->meter_two_elec_charge ?? 0), 2) }}</span></div>
                        </div>
                    </div>
                    @endif

                    <div class="border-top pt-3 mt-3">
                        <div class="row g-2 g-md-3 align-items-center flex-wrap">
                            <div class="col-12 col-md-auto">
                                <span class="text-muted small">Total Consumed Unit</span>
                                <strong class="d-inline-block ms-1">{{ $bill->total_consumed_unit ?? $bill->meter_one_consume_unit ?? 0 }}</strong>
                            </div>
                            <div class="col-12 col-md-auto"><span class="text-muted small">Total Electricity</span> <strong class="d-inline-block ms-1">₹ {{ number_format((float)($bill->electricty_charges ?? 0), 2) }}</strong></div>
                            <div class="col-12 col-md-auto"><span class="text-muted small">Licence Fee</span> <strong class="d-inline-block ms-1">₹ {{ number_format((float)($bill->licence_fees ?? 0), 2) }}</strong> <small class="text-muted">(not Outside Recovery)</small></div>
                            <div class="col-12 col-md-auto"><span class="text-muted small">Water Charge</span> <strong class="d-inline-block ms-1">₹ {{ number_format((float)($bill->water_charges ?? 0), 2) }}</strong></div>
                            <div class="col-12 col-md-auto"><span class="text-muted small">Grand Total</span> <strong class="text-primary fs-6 d-inline-block ms-1">₹ {{ number_format($bill->grand_total ?? 0, 2) }}</strong></div>
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <small class="text-muted fst-italic">(ESTATE SECTION)</small>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-5 px-3">
                <i class="material-symbols-rounded text-body-secondary mb-2" style="font-size: 3rem;">receipt_long</i>
                <p class="lead text-muted mb-1">No bills found</p>
                <p class="text-muted small mb-0">No bills are available for the selected month and unit sub type. Try changing the filters.</p>
            </div>
            @endforelse
        </div>
    </div>
    @endif
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .ts-dropdown { z-index: 1060 !important; }
    /*
     * Tom Select ships with Bootstrap-default padding (0.375rem); Sargam admin theme uses
     * .form-control padding 10px 16px and font-size 0.875rem — match so Unit Sub Type aligns with Bill Month / Search.
     */
    .estate-bill-unit-sub-ts .ts-wrapper.single {
        min-height: calc(0.875rem * 1.5 + 20px + 2 * var(--bs-border-width, 1px));
    }
    .estate-bill-unit-sub-ts .ts-wrapper.single .ts-control:not(.rtl) {
        padding-top: 10px !important;
        padding-bottom: 10px !important;
        padding-left: 16px !important;
        padding-right: 38px !important;
        font-size: 0.875rem !important;
        line-height: 1.5 !important;
        border-radius: 8px !important;
        min-height: calc(0.875rem * 1.5 + 20px + 2 * var(--bs-border-width, 1px));
    }
    .estate-bill-filter-actions-controls {
        min-height: calc(0.875rem * 1.5 + 20px + 2 * var(--bs-border-width, 1px));
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
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
                onInitialize: function () { this.activeOption = null; }
            });
        }
    }

    var checkAll = document.getElementById('check_all');
    var boxes = document.querySelectorAll('.bill-checkbox');
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            boxes.forEach(function (el) { el.checked = checkAll.checked; });
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
            return Array.prototype.filter.call(cards, function (card) {
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
        cards.forEach(function (card) {
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
        params.set('selected_pks', selectedPks.join(','));

        window.open(printAllUrl + '?' + params.toString(), '_blank', 'noopener');
    }
    var btnPrintSelected = document.getElementById('btn_print_selected');
    if (btnPrintSelected) {
        btnPrintSelected.addEventListener('click', function () {
            openSelectedPrintInSingleTab(getBillCardsToPrint(true));
        });
    }
    // Print All: opens the print-all page (all bills in one view with option to print or download PDF)
    // Link is used instead of button; no extra script needed.

    function getSelectedBillPks() {
        var pks = [];
        document.querySelectorAll('.bill-checkbox:checked').forEach(function (el) {
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
            setTimeout(function () { statusEl.style.display = 'none'; }, 4000);
        }
    }

    var btnNotify = document.getElementById('btn_notify_selected');
    if (btnNotify) {
        btnNotify.addEventListener('click', function () {
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
            pks.forEach(function (p) { params.append('pks[]', p); });
            params.append('_token', '{{ csrf_token() }}');
            if (billMonth && billMonth.value) params.append('bill_month', billMonth.value);
            if (unitSub && unitSub.value) params.append('unit_sub_type_pk', unitSub.value);
            fetch('{{ route("admin.estate.generate-estate-bill.verify-selected") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: params.toString()
            }).then(function (r) { return r.json(); }).then(function (res) {
                btnNotify.disabled = false;
                if (res.status && res.message) {
                    showStatusMessage(res.message, 'success');
                }
            }).catch(function () {
                btnNotify.disabled = false;
                showStatusMessage('Failed to verify bills.', 'error');
            });
        });
    }

    var btnDraft = document.getElementById('btn_save_as_draft');
    if (btnDraft) {
        btnDraft.addEventListener('click', function () {
            var pks = getSelectedBillPks();
            if (pks.length === 0) {
                showStatusMessage('Please select at least one bill to save as draft.', 'warning');
                return;
            }
            btnDraft.disabled = true;
            var form = document.querySelector('form[action*="generate-estate-bill"]');
            var billMonth = form ? form.querySelector('#bill_month') : null;
            var params = new URLSearchParams();
            pks.forEach(function (p) { params.append('pks[]', p); });
            params.append('_token', '{{ csrf_token() }}');
            if (billMonth && billMonth.value) params.append('bill_month', billMonth.value);
            fetch('{{ route("admin.estate.generate-estate-bill.save-as-draft") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: params.toString()
            }).then(function (r) { return r.json(); }).then(function (res) {
                btnDraft.disabled = false;
                if (res.status && res.message) {
                    showStatusMessage(res.message, 'success');
                    if (form && billMonth && billMonth.value) form.submit();
                }
            }).catch(function () {
                btnDraft.disabled = false;
                showStatusMessage('Failed to save as draft.', 'error');
            });
        });
    }

    // If opened from notification, jump to the specific bill.
    // Expected query params:
    // - bill_no, bill_print_month, bill_print_year (used to locate the bill card)
    // - open_estate_bill=1 (optional: also auto-open print in new tab)
    (function () {
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
            cards.forEach(function (card) {
                if (target) return;
                var cNo = (card.getAttribute('data-bill-no') || '').trim();
                var cMonth = (card.getAttribute('data-bill-month') || '').trim();
                var cYear = (card.getAttribute('data-bill-year') || '').trim();
                if (cNo === billNo && cMonth === billPrintMonth && cYear === billPrintYear) {
                    target = card;
                }
            });

            if (target && target.scrollIntoView) {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                target.classList.add('border', 'border-primary');
            }
        } catch (e) {}

        // Optional: also auto-open the specific print page.
        if (!shouldOpen || shouldOpen === '0' || shouldOpen === 'false') return;

        var printUrl = buildPrintUrl(billNo, billPrintMonth, billPrintYear);
        // Try open in a new tab; fallback to same-tab redirect when blocked.
        setTimeout(function () {
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
