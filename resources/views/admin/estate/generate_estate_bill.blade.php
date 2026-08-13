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
<div class="container-fluid geb-page">
    <x-breadcrum title="{{ $estateBillPageLabel }}"></x-breadcrum>
    <x-session_message />

    {{-- Actions sit above the card, right-aligned, as the export row does on the
         index pages (new-design-index-page.md §1). Print All only exists once a
         month is chosen, so it stays inside the @if. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 geb-secondary-actions">
        <div class="form-check mb-0 d-flex align-items-center gap-2">
            <input class="form-check-input mt-0" type="checkbox" id="check_all" name="check_all"
                aria-describedby="check_all_help">
            <label class="form-check-label geb-select-all" for="check_all">Select All</label>
            <span id="check_all_help" class="visually-hidden">Select or clear all bill checkboxes</span>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            {{-- Bulk actions are revealed by a selection; with nothing ticked the
                 row shows Print All alone, as the design does. --}}
            <button type="button" id="btn_print_selected"
                class="btn programme-dt-btn-columns border-0 text-primary geb-bulk d-none"
                title="Print selected bills in a single tab">
                <i class="bi bi-printer" aria-hidden="true"></i>
                <span>Print Selected</span>
            </button>
            @if($showUnitSubTypeFilter)
            <button type="button" id="btn_notify_selected"
                class="btn programme-dt-btn-columns border-0 text-primary geb-bulk d-none"
                title="Notify the selected bills">
                <i class="bi bi-send" aria-hidden="true"></i>
                <span>Notify Selected</span>
            </button>
            @endif
        @if($billMonth)
        <a href="{{ route('admin.estate.reports.bill-report-print-all', array_filter(['bill_month' => $billMonth, 'unit_sub_type_pk' => $unitSubTypePk, 'scope' => request('scope') === 'self' ? 'self' : null], static fn ($v) => $v !== null && $v !== '')) }}"
            target="_blank" rel="noopener" id="btn_print_all"
            class="btn programme-dt-btn-columns border-0 text-primary"
            title="View all bills in one page — print or download as PDF at once">
            <i class="bi bi-printer-fill" aria-hidden="true"></i>
            <span>Print All</span>
        </a>
        @endif
        </div>
    </div>

    <div class="geb-bar mb-3">
          {{-- Toolbar: filters left · Columns + search right (§2) --}}
            <form method="get" action="{{ route('admin.estate.generate-estate-bill') }}"
                class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 programme-dt-toolbar">
                @if(request('scope') === 'self')
                <input type="hidden" name="scope" value="self">
                @endif

                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>

                    <div class="programme-dt-filter-select">
                        <select class="form-select" id="bill_month" name="bill_month" aria-label="Bill Month"
                            data-max-date="{{ date('Y-m-d') }}">
                            <option value="">Bill Month</option>
                            @foreach($billMonthOptions ?? [] as $value => $label)
                            <option value="{{ $value }}" {{ (string) old('bill_month', $billMonth) === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($showUnitSubTypeFilter)
                    <div class="programme-dt-filter-select">
                        <select class="form-select" id="unit_sub_type_pk" name="unit_sub_type_pk"
                            aria-label="Unit Sub Type">
                            <option value="">Unit Sub Type</option>
                            @foreach($unitSubTypes as $ust)
                            <option value="{{ $ust->pk }}" {{ (string)$unitSubTypePk === (string)$ust->pk ? 'selected' : '' }}>{{ $ust->unit_sub_type }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- GET-filtered page, so the reset is a plain link back to the
                         unfiltered route; the component (and its red) is the
                         programme-dt one, not disc-reset (§2). --}}
                    <a href="{{ route('admin.estate.generate-estate-bill', request('scope') === 'self' ? ['scope' => 'self'] : []) }}"
                        class="btn programme-dt-btn-reset d-inline-flex align-items-center justify-content-center">Remove
                        Filter</a>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" data-bs-toggle="modal"
                        data-bs-target="#gebColumnModal" title="Show / hide bill fields">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    @if($showGenerateEstateBillSearch)
                    {{-- Toggle variant (§2): the icon reveals a GET search that keeps
                         the other filter state as hidden inputs. --}}
                    <button type="button" class="btn geb-search-toggle" id="gebSearchToggle"
                        aria-expanded="{{ filled($search ?? '') ? 'true' : 'false' }}" aria-controls="gebSearchWrap"
                        title="Search bills">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>
                    <div class="programme-dt-search geb-search {{ filled($search ?? '') ? '' : 'd-none' }}"
                        id="gebSearchWrap">
                        <input type="search" class="form-control" id="search" name="search"
                            value="{{ old('search', $search ?? '') }}" placeholder="Search" autocomplete="off"
                            aria-label="Search bills"
                            title="Bill no., house, name, designation, employee type, unit e.g. Type-(12)">
                    </div>
                    @endif
                </div>
            </form>  


    <div id="status-msg" class="mb-3" style="display: none;" aria-live="polite"></div>

    @if($billMonth)
    <div class="bill-cards-wrapper geb-cards">
            @forelse($bills as $bill)
            @php
                $gebMeterTwo = (isset($bill->meter_two) && (int) $bill->meter_two !== 0)
                    || (isset($bill->meter_two_consume_unit) && (int) $bill->meter_two_consume_unit > 0);
            @endphp
            <div class="geb-bill mb-3 bill-card" data-bill-no="{{ $bill->bill_no ?? '' }}"
                data-bill-month="{{ $bill->bill_month ?? '' }}" data-bill-year="{{ $bill->bill_year ?? '' }}">

                <div class="geb-bill__head">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="form-check mb-0 d-flex align-items-center gap-2">
                            <input class="form-check-input bill-checkbox mt-0" type="checkbox" value="{{ $bill->pk }}"
                                id="bill_{{ $bill->pk }}" data-bill-pk="{{ $bill->pk }}">
                            <label class="form-check-label geb-bill__no" for="bill_{{ $bill->pk }}">
                                Bill Number #{{ $bill->bill_no ?? '—' }}
                            </label>
                        </div>
                        <a href="{{ route('admin.estate.reports.bill-report-print') }}?bill_no={{ $bill->bill_no }}&month={{ $bill->bill_month }}&year={{ $bill->bill_year }}"
                            target="_blank" rel="noopener" class="btn geb-btn-print" title="Print this bill">
                            <i class="bi bi-printer" aria-hidden="true"></i>
                            <span>Print</span>
                        </a>
                    </div>

                    <div class="geb-facts geb-facts--head">
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">Request Date</span>
                            <span class="geb-fact__value">{{ $bill->req_date_formatted ?? '—' }}</span>
                        </div>
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">Employee Name</span>
                            <span class="geb-fact__value">{{ $bill->emp_name ?? '—' }}</span>
                        </div>
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">House Number</span>
                            <span class="geb-fact__value">{{ $bill->house_display ?? '—' }}</span>
                        </div>
                    </div>
                </div>

                <div class="geb-bill__body">
                    <div class="geb-facts">
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">Bill No.</span>
                            <span class="geb-fact__value">{{ $bill->bill_no ?? '—' }}</span>
                        </div>
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">Month</span>
                            <span class="geb-fact__value">{{ trim(($bill->bill_month ?? '') . ' ' . ($bill->bill_year ?? '')) ?: '—' }}</span>
                        </div>
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">Employee Name</span>
                            <span class="geb-fact__value">{{ $bill->emp_name ?? '—' }}</span>
                        </div>
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">Designation</span>
                            <span class="geb-fact__value">{{ $bill->emp_designation ?? '—' }}</span>
                        </div>
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">Employee Type</span>
                            <span class="geb-fact__value"><span class="geb-pill">Regular</span></span>
                        </div>
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">House No.</span>
                            <span class="geb-fact__value">{{ $bill->house_display ?? '—' }}</span>
                        </div>
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">From Date</span>
                            <span class="geb-fact__value">{{ $bill->from_date_formatted ?? '—' }}</span>
                        </div>
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">To Date</span>
                            <span class="geb-fact__value">{{ $bill->to_date_formatted ?? '—' }}</span>
                        </div>
                    </div>

                    <h6 class="geb-section">Meter 01</h6>
                    <div class="geb-facts">
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">Meter No.</span>
                            <span class="geb-fact__value">{{ $bill->meter_one ?? '—' }}</span>
                        </div>
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">Previous Reading</span>
                            <span class="geb-fact__value">{{ !empty($bill->meter_one_is_new) ? '—' : ($bill->last_month_elec_red ?? '—') }}</span>
                        </div>
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">Current Reading</span>
                            <span class="geb-fact__value">{{ $bill->curr_month_elec_red ?? '—' }}</span>
                        </div>
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">Consumed Unit</span>
                            <span class="geb-fact__value">{{ $bill->meter_one_consume_unit ?? '—' }}</span>
                        </div>
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">Electricity Bill</span>
                            <span class="geb-fact__value">₹ {{ number_format((float) ($bill->meter_one_elec_charge ?? 0), 2) }}</span>
                        </div>
                    </div>

                    @if($gebMeterTwo)
                    <h6 class="geb-section">Meter 02</h6>
                    <div class="geb-facts">
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">Meter No.</span>
                            <span class="geb-fact__value">{{ $bill->meter_two ?? '—' }}</span>
                        </div>
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">Previous Reading</span>
                            <span class="geb-fact__value">{{ !empty($bill->meter_two_is_new) ? '—' : ($bill->last_month_elec_red2 ?? '—') }}</span>
                        </div>
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">Current Reading</span>
                            <span class="geb-fact__value">{{ $bill->curr_month_elec_red2 ?? '—' }}</span>
                        </div>
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">Consumed Unit</span>
                            <span class="geb-fact__value">{{ $bill->meter_two_consume_unit ?? '—' }}</span>
                        </div>
                        <div class="geb-fact" data-fact>
                            <span class="geb-fact__label">Electricity Bill</span>
                            <span class="geb-fact__value">₹ {{ number_format((float) ($bill->meter_two_elec_charge ?? 0), 2) }}</span>
                        </div>
                    </div>
                    @endif

                    <h6 class="geb-section">Bill</h6>
                    <dl class="geb-totals">
                        <div class="geb-totals__row">
                            <dt>Total Consumed Unit</dt>
                            <dd>{{ $bill->total_consumed_unit ?? $bill->meter_one_consume_unit ?? 0 }}</dd>
                        </div>
                        <div class="geb-totals__row">
                            <dt>Total Electricity</dt>
                            <dd>₹ {{ number_format((float) ($bill->electricty_charges ?? 0), 2) }}</dd>
                        </div>
                        <div class="geb-totals__row">
                            <dt>Licence Fee</dt>
                            <dd>₹ {{ number_format((float) ($bill->licence_fees ?? 0), 2) }}</dd>
                        </div>
                        <div class="geb-totals__row">
                            <dt>Water Charge</dt>
                            <dd>₹ {{ number_format((float) ($bill->water_charges ?? 0), 2) }}</dd>
                        </div>
                        <div class="geb-totals__row geb-totals__row--grand">
                            <dt>Grand Total</dt>
                            <dd>₹ {{ number_format($bill->grand_total ?? 0, 2) }}</dd>
                        </div>
                    </dl>

                </div>
            </div>
            @empty
            <div class="ds-empty-state">
                <i class="material-symbols-rounded" style="font-size: 3rem;">receipt_long</i>
                <p class="mb-1">No bills found</p>
                <p class="small mb-0">No bills are available for the selected month and unit sub type. Try changing the
                    filters.</p>
            </div>
            @endforelse
    </div>
    @endif
        </div>

    {{-- Columns — this is a card list, so the toggles hide bill FIELDS rather
         than table columns. Choice is remembered by label, never by index
         (column-visibility.md §3). --}}
    <div class="modal fade" id="gebColumnModal" tabindex="-1" aria-labelledby="gebColumnModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="gebColumnModalLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="gebColumnToggleGrid"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-primary rounded-3 px-4"
                        data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<style>
    /* ── Generate Estate Bill — page-scoped chrome (namespaced .geb-page, §7),
       built on the --ds-* tokens (design.md Layer A). ── */
    .geb-page .geb-secondary-actions .btn i {
        font-size: 1rem;
        line-height: 1;
    }

    .geb-page .programme-dt-filter-select .form-control,
    .geb-page .programme-dt-filter-select .form-select {
        min-height: var(--ds-control-h, 2.5rem);
    }

    .geb-page .geb-search {
        width: 260px;
        max-width: 100%;
    }

    /* Search reveal button — square, brand-outlined, matches the Columns button height. */
    .geb-page .geb-search-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: var(--ds-control-h, 2.5rem);
        height: var(--ds-control-h, 2.5rem);
        padding: 0;
        color: var(--ds-primary, #004a93);
        background: var(--ds-surface, #fff);
        border: 1px solid var(--ds-line, #d0d5dd);
        border-radius: var(--ds-radius, 4px);
    }

    .geb-page .geb-search-toggle[aria-expanded="true"],
    .geb-page .geb-search-toggle:hover {
        border-color: var(--ds-primary, #004a93);
        background: #f2f7fc;
    }

    /* Select All and the filters each sit on their own white bar. */
    .geb-page .geb-bar {
        padding: 0.75rem var(--ds-space-3, 1rem);
        background: var(--ds-surface, #fff);
        border: 1px solid var(--ds-line, #e5e7eb);
        border-radius: var(--ds-radius-2, 8px);
    }

    .geb-page .geb-cards {
        display: block;
    }

    /* ── Bill card ── */
    .geb-page .geb-bill {
        overflow: hidden;
        background: var(--ds-surface, #fff);
        border: 1px solid var(--ds-line, #e5e7eb);
        border-radius: var(--ds-radius-2, 8px);
        box-shadow: var(--ds-shadow-sm, 0 1px 2px rgba(16, 24, 40, .06));
    }

    .geb-page .geb-bill__head {
        padding: var(--ds-space-3, 1rem);
        background: #e8f0fb;
        border-bottom: 1px solid var(--ds-line, #e5e7eb);
    }

    .geb-page .geb-bill__no {
        color: var(--ds-primary, #004a93);
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
    }

    .geb-page .geb-btn-print {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.3125rem 0.875rem;
        font-size: 0.8125rem;
        font-weight: 500;
        color: var(--ds-primary, #004a93);
        background: var(--ds-surface, #fff);
        border: 1px solid var(--ds-line, #d0d5dd);
        border-radius: var(--ds-radius, 4px);
    }

    .geb-page .geb-btn-print:hover {
        border-color: var(--ds-primary, #004a93);
        color: var(--ds-primary, #004a93);
    }

    .geb-page .geb-bill__body {
        padding: var(--ds-space-3, 1rem);
    }

    /* Label-over-value fact grid, shared by the head strip and the body.
       Fixed 5 columns (not auto-fit) so the body reads as the design does —
       Bill No. → Employee Type on row one, House No. / From / To on row two.
       auto-fit stretched all eight onto a single squeezed row. */
    .geb-page .geb-facts {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.875rem var(--ds-space-3, 1rem);
    }

    @media (max-width: 1199.98px) {
        .geb-page .geb-facts {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .geb-page .geb-facts {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    .geb-page .geb-facts--head,
    .geb-page .geb-bill__head .geb-facts--head {
        margin-top: 0.625rem;
        grid-template-columns: repeat(auto-fit, minmax(140px, max-content));
        gap: 0.25rem 2.5rem;
    }

    .geb-page .geb-fact {
        min-width: 0;
    }

    .geb-page .geb-fact__label {
        display: block;
        font-size: 0.6875rem;
        line-height: 1.3;
        color: var(--ds-ink-muted, #667085);
    }

    .geb-page .geb-fact__value {
        display: block;
        font-size: 0.8125rem;
        color: var(--ds-ink, #1f2937);
        word-break: break-word;
    }

    .geb-page .geb-pill {
        display: inline-block;
        padding: 0.125rem 0.5rem;
        border-radius: 999px;
        background: #ecfdf3;
        color: #067647;
        font-size: 0.6875rem;
        font-weight: 600;
    }

    .geb-page .geb-section {
        margin: var(--ds-space-3, 1rem) 0 0.5rem;
        padding-top: var(--ds-space-3, 1rem);
        border-top: 1px solid var(--ds-line, #e5e7eb);
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--ds-ink, #1f2937);
    }

    /* Totals read as a right-aligned ledger, Grand Total last. */
    .geb-page .geb-totals {
        margin: 0;
    }

    .geb-page .geb-totals__row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: var(--ds-space-3, 1rem);
        padding: 0.375rem 0;
        border-bottom: 1px solid var(--ds-line, #e5e7eb);
    }

    .geb-page .geb-totals__row dt {
        font-size: 0.8125rem;
        font-weight: 400;
        color: var(--ds-ink-muted, #667085);
    }

    .geb-page .geb-totals__row dt small {
        font-size: 0.6875rem;
    }

    .geb-page .geb-totals__row dd {
        margin: 0;
        font-size: 0.8125rem;
        color: var(--ds-ink, #1f2937);
    }

    .geb-page .geb-totals__row--grand {
        padding-top: 0.625rem;
        border-bottom: 0;
    }

    .geb-page .geb-totals__row--grand dt {
        font-weight: 700;
        color: var(--ds-ink, #1f2937);
    }

    .geb-page .geb-totals__row--grand dd {
        font-size: 0.9375rem;
        font-weight: 700;
        color: var(--ds-primary, #004a93);
    }


    .select2-container--open { z-index: 1060; } /* sirf khula dropdown modal ke upar; closed widget normal flow me (modal ke peeche) */
    .select2-container--default .select2-selection--single { min-height: calc(1.5em + 0.75rem + 2px); display: flex; align-items: center; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 1.5; padding-left: 0.25rem; }
</style>
@endpush

@push('scripts')
<script>
    // Design-chrome behaviour for this page. The bill/notify/print logic lives in
    // the main script below and is untouched.
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.querySelector('.geb-page .programme-dt-toolbar');

        // No Show button in the design — changing a filter reloads the list.
        ['bill_month', 'unit_sub_type_pk'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el && form) el.addEventListener('change', function () { form.submit(); });
        });

        // Search: the icon reveals the input; Enter submits the same GET form.
        var toggle = document.getElementById('gebSearchToggle');
        var wrap = document.getElementById('gebSearchWrap');
        if (toggle && wrap) {
            toggle.addEventListener('click', function () {
                var open = wrap.classList.toggle('d-none') === false;
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (open) { var i = wrap.querySelector('input'); if (i) i.focus(); }
            });
        }

        // Bulk actions appear only with a selection.
        var bulk = Array.prototype.slice.call(document.querySelectorAll('.geb-bulk'));
        function syncBulk() {
            var any = document.querySelectorAll('.bill-checkbox:checked').length > 0;
            bulk.forEach(function (b) { b.classList.toggle('d-none', !any); });
        }
        document.addEventListener('change', function (e) {
            if (e.target && (e.target.classList.contains('bill-checkbox') || e.target.id === 'check_all')) {
                setTimeout(syncBulk, 0);
            }
        });
        syncBulk();

        // ── Columns: hide/show bill FIELDS (no table here), remembered by label ──
        var KEY = 'sargam.generateEstateBill.hiddenFields.' + @json(auth()->id() ?? 'guest');
        function readHidden() {
            try { var r = window.localStorage.getItem(KEY); var a = r ? JSON.parse(r) : []; return Array.isArray(a) ? a : []; }
            catch (e) { return []; }
        }
        function saveHidden() {
            var hidden = [];
            document.querySelectorAll('#gebColumnToggleGrid .geb-field-toggle').forEach(function (cb) {
                if (!cb.checked) hidden.push(cb.dataset.label);
            });
            try { window.localStorage.setItem(KEY, JSON.stringify(hidden)); } catch (e) {}
        }
        function applyHidden() {
            var hidden = readHidden();
            document.querySelectorAll('.geb-fact[data-fact]').forEach(function (f) {
                var el = f.querySelector('.geb-fact__label');
                var label = el ? el.textContent.trim() : '';
                f.classList.toggle('d-none', label !== '' && hidden.indexOf(label) !== -1);
            });
        }

        var grid = document.getElementById('gebColumnToggleGrid');
        if (grid) {
            var seen = [];
            document.querySelectorAll('.bill-card .geb-fact[data-fact] .geb-fact__label').forEach(function (el) {
                var label = el.textContent.trim();
                if (!label || seen.indexOf(label) !== -1) return;
                seen.push(label);
            });
            var hidden = readHidden();
            seen.forEach(function (label, i) {
                var id = 'gebField_' + i;
                var cell = document.createElement('div');
                cell.className = 'col-12 col-sm-6 col-md-4';
                var lab = document.createElement('label');
                lab.className = 'colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100';
                lab.setAttribute('for', id);
                var cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.className = 'form-check-input m-0 geb-field-toggle';
                cb.id = id;
                cb.dataset.label = label;
                cb.checked = hidden.indexOf(label) === -1;
                cb.addEventListener('change', function () { saveHidden(); applyHidden(); });
                var span = document.createElement('span');
                span.textContent = label;
                lab.appendChild(cb); lab.appendChild(span); cell.appendChild(lab); grid.appendChild(cell);
            });
        }
        applyHidden();
    });
</script>
{{-- Select2 JS globally footer (admin.layouts.footer) se load hoti hai; yahan include ki zaroorat nahi. --}}
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

        if (typeof $.fn.select2 !== 'undefined') {
            var unitSubEl = document.getElementById('unit_sub_type_pk');
            if (unitSubEl && !$(unitSubEl).data('select2')) {
                $(unitSubEl).select2({
                    placeholder: '— Select Unit Sub Type —',
                    allowClear: false,
                    width: '100%'
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