@extends('admin.layouts.master')

@section('title', 'Estate Bill Report for Print - Sargam')

@section('setup_content')
<style>
/* --- Screen: bill container --- */
.estate-bill-print { max-width: 210mm; margin: 0 auto; background: #f1f5f9; padding: 24px 0; }
@media print {
    .estate-bill-print { padding: 0; background: #fff; }
}
/* --- Print: hide nav/filters, show only bill --- */
@media print {
    body * { visibility: hidden; }
    .estate-bill-print.bill-page, .estate-bill-print.bill-page * { visibility: visible; }
    .estate-bill-print.bill-page { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 0; background: #fff; }
    .no-print { display: none !important; }
    .bill-doc {
        break-inside: avoid;
        page-break-inside: avoid;
        width: 100%;
        max-width: 206mm;
        margin: 0 auto;
        border-width: 1.5px;
    }
    @page { size: A4 portrait; margin: 2mm; }
    html, body { margin: 0 !important; padding: 0 !important; }
    body { background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .bill-doc { box-shadow: none !important; }
    .bill-page {
        font-size: 8.4pt;
        line-height: 1.12;
        width: 100%;
    }
    .bill-header { padding: 7px 88px 8px 10px; }
    .bill-header-main { width: 100%; max-width: 100%; margin: 0; }
    .bill-header::after { margin-top: 4px; height: 1px; }
    .bill-header .org-name { font-size: 9.1pt; margin-bottom: 1px; }
    .bill-header .org-sub { font-size: 7pt; }
    .bill-header .bill-title { font-size: 8.3pt; margin-top: 5px; letter-spacing: 0.06em; }
    .bill-badge { top: 5px; right: 7px; font-size: 6.2pt; padding: 2px 6px; }
    .bill-logo { width: 90px; height: 24px; margin-bottom: 3px; object-fit: contain; }
    .bill-meta-bar { padding: 5px 10px; gap: 4px; font-size: 7.1pt; border-bottom-width: 1px; }
    .bill-meta-bar .bill-no { font-size: 7.8pt; padding: 1px 6px; border-width: 1px; }
    .bill-consumer { padding: 5px 10px; border-left-width: 2px; }
    .bill-consumer-title { margin-bottom: 3px; padding-bottom: 2px; font-size: 7.1pt; border-bottom-width: 1px; }
    .bill-consumer-table td { padding: 2px 6px 2px 0; font-size: 7pt; }
    .bill-section-title { margin: 6px 10px 3px; padding: 2px 0 2px 5px; font-size: 7.1pt; border-left-width: 2px; }
    .bill-table-wrap { padding: 0 10px 2px; }
    .bill-table { margin-bottom: 4px; font-size: 6.9pt; }
    .bill-table th, .bill-table td { padding: 2px 4px; }
    .bill-total-wrap { padding: 0 10px 5px; }
    .bill-total-box { margin-top: 1px; padding: 5px 7px; border-width: 1.5px; }
    .bill-total-box::before { height: 1px; }
    .bill-total-label { font-size: 6.8pt; margin-bottom: 2px; }
    .bill-total-box .grand-total { font-size: 10pt; }
    .bill-amount-words, .bill-pay-by { margin-top: 2px; font-size: 6.3pt; line-height: 1.15; }
    .bill-footer { padding: 5px 10px 6px; font-size: 6.2pt; border-top-width: 1px; }
    .bill-footer .footer-note { margin-bottom: 2px; line-height: 1.15; }
    .bill-footer p { margin-bottom: 2px; }
    .bill-footer .sign-block { margin-top: 6px; gap: 10px; }
    .bill-footer .sign-line { width: 95px; padding-top: 2px; border-top-width: 1px; font-size: 6.2pt; }
    .bill-footer .sign-sub { font-size: 5.8pt; margin-top: 1px; }
}
/* --- Bill document frame --- */
.bill-page { font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif; font-size: 11pt; color: #1a202c; line-height: 1.45; }
.bill-doc {
    position: relative;
    border: 2px solid #1e3a5f;
    padding: 0;
    background: #fff;
    box-shadow: 0 4px 24px rgba(30, 58, 95, 0.12), 0 0 0 1px rgba(30, 58, 95, 0.06);
    overflow: hidden;
}
.bill-doc::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, #c9a227 0%, #e0b83d 50%, #c9a227 100%);
    z-index: 1;
}
/* --- Header --- */
.bill-header {
    background: #ffffff;
    color: #af2910;
    text-align: center;
    padding: 18px 110px 20px 24px;
    position: relative;
    box-sizing: border-box;
}
.bill-header-main {
    width: 100%;
    max-width: 100%;
    margin: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.bill-header-main > .bill-logo { flex-shrink: 0; }
.bill-header-main > .org-name,
.bill-header-main > .org-sub,
.bill-header-main > .bill-title {
    width: 100%;
    text-align: center;
    box-sizing: border-box;
}
.bill-header::after {
    content: ''; display: block; height: 4px;
    background: linear-gradient(90deg, transparent, #c9a227, transparent);
    margin-top: 14px; opacity: 0.9;
}
.bill-header .org-name {
    font-size: 14pt; font-weight: 700; letter-spacing: 0.04em; margin: 0 0 4px 0;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}
.bill-header .org-sub {
    font-size: 10.5pt; opacity: 0.92; margin: 0; letter-spacing: 0.06em;
    text-transform: uppercase;
}
.bill-header .bill-title {
    font-size: 13pt; font-weight: 700; margin: 14px 0 0 0;
    letter-spacing: 0.12em; text-transform: uppercase;
}
.bill-badge {
    position: absolute; top: 14px; right: 20px;
    font-size: 9pt; font-weight: 700; letter-spacing: 0.12em;
    background: #c9a227; color: #1a1a1a; padding: 6px 14px;
    border-radius: 0; box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.bill-logo {
    width: 158px; height: 48px;
    display: block;
    margin: 0 auto 8px;
    object-fit: contain;
}
/* --- Bill meta bar --- */
.bill-meta-bar {
    display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
    padding: 14px 24px; background: linear-gradient(to bottom, #f8f4e6 0%, #efe6c8 100%);
    border-bottom: 2px solid #dbc489;
    font-size: 11pt;
}
.bill-meta-bar .bill-no {
    font-weight: 800; color: #1e3a5f; font-size: 12pt;
    padding: 4px 12px; background: #fff; border: 1px solid #2c5282;
    letter-spacing: 0.04em;
}
.bill-meta-bar .bill-period { font-weight: 600; color: #2d3748; }
/* --- Consumer details --- */
.bill-consumer {
    margin: 0; padding: 18px 24px;
    border-bottom: 1px solid #e4dbbf;
    background: linear-gradient(to bottom, #fffef8 0%, #fbf7ea 100%);
    border-left: 4px solid #c9a227;
}
.bill-consumer-title {
    font-size: 10pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;
    color: #2c5282; margin: 0 0 12px 0; padding-bottom: 6px;
    border-bottom: 2px solid #cbd5e0;
}
.bill-consumer-table { width: 100%; border-collapse: collapse; }
.bill-consumer-table { table-layout: fixed; }
.bill-consumer-table td { padding: 6px 14px 6px 0; vertical-align: middle; font-size: 10.5pt; }
.bill-consumer-table .label { width: 26%; color: #4a5568; font-weight: 600; }
.bill-consumer-table .value { font-weight: 500; color: #1a202c; }
/* --- Section title --- */
.bill-section-title {
    font-size: 10.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
    color: #1e3a5f; margin: 20px 24px 10px; padding: 8px 0 8px 12px;
    border-left: 4px solid #c9a227; background: #fffaf0;
}
/* --- Tables --- */
.bill-table-wrap { padding: 0 24px 8px; }
.bill-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 10.5pt; }
.bill-table th, .bill-table td { border: 1px solid #a0aec0; padding: 10px 12px; text-align: left; }
.bill-table th {
    background: linear-gradient(to bottom, #1e3a5f 0%, #254d7a 100%);
    color: #fff; font-weight: 600; font-size: 10pt; letter-spacing: 0.02em;
}
.bill-table tbody tr:nth-child(even) { background: #f7fafc; }
.bill-table tbody tr:hover { background: #edf2f7; }
@media print { .bill-table tbody tr:hover { background: inherit; } }
.bill-table .text-right { text-align: right; }
.bill-table .amount { text-align: right; font-weight: 600; font-variant-numeric: tabular-nums; }
/* --- Total box --- */
.bill-total-wrap { padding: 0 24px 24px; }
.bill-total-box {
    border: 3px solid #1e3a5f; margin-top: 8px; padding: 20px 20px;
    background: linear-gradient(135deg, #fffdf6 0%, #f7efd9 100%);
    position: relative;
}
.bill-total-box::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #c9a227, #e0b83d, #c9a227);
}
.bill-total-label { font-size: 10pt; font-weight: 700; color: #4a5568; letter-spacing: 0.06em; margin-bottom: 6px; }
.bill-total-box .grand-total {
    font-size: 18pt; font-weight: 800; text-align: right; color: #1e3a5f;
    font-variant-numeric: tabular-nums; letter-spacing: 0.03em;
}
.bill-amount-words {
    font-size: 9.5pt; color: #4a5568; margin-top: 10px; text-align: right;
    padding-top: 8px; border-top: 1px dashed #a0aec0;
}
.bill-pay-by { font-size: 9pt; color: #718096; margin-top: 8px; text-align: right; }
/* --- Footer --- */
.bill-footer {
    margin: 0; padding: 20px 24px 24px;
    border-top: 2px solid #dccb96; background: linear-gradient(to bottom, #fffef8 0%, #f6f0de 100%);
    font-size: 9pt; color: #4a5568;
}
.bill-footer .footer-note { margin-bottom: 10px; line-height: 1.5; }
.bill-footer .sign-block { margin-top: 28px; display: flex; justify-content: flex-end; gap: 48px; flex-wrap: wrap; }
.bill-footer .sign-line {
    border-top: 2px solid #2c5282; width: 180px; padding-top: 6px;
    font-size: 9pt; font-weight: 700; text-align: center; color: #1e3a5f; letter-spacing: 0.02em;
}
.bill-footer .sign-sub { font-size: 8pt; margin-top: 2px; color: #718096; }
</style>
@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<style>
    .select2-container--open { z-index: 1060; } /* sirf khula dropdown modal ke upar; closed widget normal flow me (modal ke peeche) */
    .select2-container--default .select2-selection--single { min-height: calc(1.5em + 0.75rem + 2px); display: flex; align-items: center; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 1.5; padding-left: 0.25rem; }

    /* ── Estate Bill Report for Print — screen chrome only (namespaced
       .ebp-page, §7), built on the --ds-* tokens (design.md Layer A). Every
       element it styles carries `no-print`, so the A4 bill is unaffected. ── */
    .ebp-page .ebp-secondary-actions .btn i {
        font-size: 1rem;
        line-height: 1;
    }

    /* The filters sit on their own white bar, as they do on Generate Estate Bill. */
    .ebp-page .ebp-bar {
        padding: 0.75rem var(--ds-space-3, 1rem);
        background: var(--ds-surface, #fff);
        border: 1px solid var(--ds-line, #e5e7eb);
        border-radius: var(--ds-radius-2, 8px);
    }

    .ebp-page .programme-dt-filter-select .form-control,
    .ebp-page .programme-dt-filter-select .form-select {
        min-height: var(--ds-control-h, 2.5rem);
    }

    /* Employee names + IDs are long; give that one filter room to breathe. */
    .ebp-page .ebp-filter-wide {
        width: 260px;
        max-width: 100%;
    }

    /* Select2 replaces the <select>, so the height has to be re-applied to the
       widget it renders or the row loses its baseline. */
    .ebp-page .programme-dt-filter-select .select2-container--default .select2-selection--single {
        min-height: var(--ds-control-h, 2.5rem);
        border-color: var(--ds-line, #d0d5dd);
        border-radius: var(--ds-radius, 4px);
    }
</style>
@endpush
<div class="container-fluid ebp-page">
    <!-- Breadcrumb (hidden when printing) -->
    <div class="no-print">
        <x-breadcrum title="Estate Bill Report for Print"></x-breadcrum>
    </div>

    {{-- Screen chrome only — everything down to the bill doc carries `no-print`,
         so the A4 output is untouched by this row. The action sits above the bar,
         right-aligned, as the export row does on the index pages
         (new-design-index-page.md §1). --}}
    @if($bill)
    <div class="no-print d-flex flex-wrap justify-content-end gap-2 mb-3 ebp-secondary-actions">
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" onclick="window.print();"
            title="Print this bill">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print Bill</span>
        </button>
    </div>
    @endif

    <!-- Filters (hidden when printing) -->
    <div class="no-print ebp-bar mb-4">
        {{-- Toolbar: filters left · action right (§2). The placeholder option is
             the field's label, so the separate <label> elements are gone. --}}
        <form method="get" action="{{ route('admin.estate.reports.bill-report-print') }}"
            class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 programme-dt-toolbar">

            <div class="d-flex flex-wrap align-items-center gap-3">
                <span class="programme-dt-filters-label">Filter</span>

                <div class="programme-dt-filter-select">
                    @php
                        $empCat = old('employee_category', request('employee_category', 'LBSNAA'));
                    @endphp
                    <select class="form-select" id="employee_category" name="employee_category"
                        aria-label="Employee Category">
                        <option value="LBSNAA" {{ $empCat === 'LBSNAA' ? 'selected' : '' }}>LBSNAA</option>
                        <option value="Other Employee" {{ $empCat === 'Other Employee' ? 'selected' : '' }}>Other Employee</option>
                    </select>
                </div>

                <div class="programme-dt-filter-select">
                    <select class="form-select" id="month" name="month" aria-label="Month">
                        <option value="">Month</option>
                        @foreach($months as $m)
                            <option value="{{ $m }}" {{ old('month', request('month')) === $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="programme-dt-filter-select">
                    <select class="form-select" id="year" name="year" aria-label="Year">
                        <option value="">Year</option>
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ old('year', request('year')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="programme-dt-filter-select">
                    <select class="form-select" id="employee_type_pk" name="employee_type_pk" aria-label="Employee Type">
                        <option value="">Employee Type</option>
                        @foreach($employeeTypes as $et)
                            <option value="{{ $et->pk }}" {{ old('employee_type_pk', request('employee_type_pk')) == $et->pk ? 'selected' : '' }}>{{ $et->unit_sub_type }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="programme-dt-filter-select ebp-filter-wide">
                    <select class="form-select" id="employee_pk" name="employee_pk" aria-label="Employee">
                        <option value="">Employee</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->pk }}" {{ old('employee_pk', request('employee_pk')) == $emp->pk ? 'selected' : '' }}>{{ $emp->emp_name }} {{ $emp->employee_id ? '(' . trim($emp->employee_id) . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- GET-filtered page, so the reset is a plain link back to the
                     unfiltered route; the component (and its red) is the
                     programme-dt one, not disc-reset (§2). --}}
                <a href="{{ route('admin.estate.reports.bill-report-print') }}"
                    class="btn programme-dt-btn-reset d-inline-flex align-items-center justify-content-center">Remove
                    Filter</a>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                <button type="submit" class="btn programme-dt-btn-columns" title="Show the bill for these filters">
                    <span>Show Bill</span>
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
        </form>
    </div>

    @if($bill)
    @php
        $toWordsBelowThousand = function (int $n): string {
            $ones = [0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'];
            $tens = [2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty', 6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'];
            $parts = [];
            if ($n >= 100) { $parts[] = $ones[intdiv($n, 100)] . ' Hundred'; $n = $n % 100; }
            if ($n >= 20) { $parts[] = $tens[intdiv($n, 10)] . (($n % 10) ? ' ' . $ones[$n % 10] : ''); } elseif ($n > 0) { $parts[] = $ones[$n]; }
            return trim(implode(' ', array_filter($parts)));
        };
        $toWordsIndian = function (int $n) use ($toWordsBelowThousand): string {
            if ($n === 0) return 'Zero';
            $parts = [];
            $crore = intdiv($n, 10000000); $n %= 10000000;
            $lakh = intdiv($n, 100000); $n %= 100000;
            $thousand = intdiv($n, 1000); $n %= 1000;
            $rest = $n;
            if ($crore > 0) $parts[] = $toWordsBelowThousand($crore) . ' Crore';
            if ($lakh > 0) $parts[] = $toWordsBelowThousand($lakh) . ' Lakh';
            if ($thousand > 0) $parts[] = $toWordsBelowThousand($thousand) . ' Thousand';
            if ($rest > 0) $parts[] = $toWordsBelowThousand($rest);
            return trim(implode(' ', $parts));
        };
        $grandTotal = (float) ($bill->grand_total ?? 0);
        $rupees = (int) floor($grandTotal);
        $paise = (int) round(($grandTotal - $rupees) * 100);
        if ($paise === 100) { $rupees += 1; $paise = 0; }
        $rupeesWords = $toWordsIndian($rupees);
        $amountInWords = 'Rupees ' . $rupeesWords;
        if ($paise > 0) $amountInWords .= ' and ' . $toWordsIndian($paise) . ' Paise';
        $amountInWords .= ' only';
        $estateBillLogoSrc = is_file(public_path('admin_assets/images/logos/logo.png'))
            ? asset('admin_assets/images/logos/logo.png')
            : asset('admin_assets/images/logos/logo.svg');
    @endphp
    <!-- Bill for print (visible on screen and in print) -->
    <div class="estate-bill-print bill-page">
        <div class="bill-doc">
            <!-- Header -->
            <div class="bill-header">
                <span class="bill-badge">Consumer Copy</span>
                <div class="bill-header-main">
                    <img src="{{ $estateBillLogoSrc }}" alt="LBSNAA Official Logo" class="bill-logo">
                    <p class="org-name">Lal Bahadur Shastri National Academy of Administration</p>
                    <p class="org-sub">Mussoorie · Estate Section</p>
                    <h1 class="bill-title">Estate Bill — Electricity, Water &amp; Licence</h1>
                </div>
            </div>

            <!-- Bill No & Period -->
            <div class="bill-meta-bar">
                <span class="bill-no">Bill No.: {{ $bill->bill_no ?? '—' }}</span>
                <span class="bill-period">Billing Period: {{ $bill->from_date_formatted ?? '—' }} to {{ $bill->to_date_formatted ?? '—' }} · {{ $bill->bill_month ?? '' }} {{ $bill->bill_year ?? '' }}</span>
            </div>

            <!-- Consumer / Employee details -->
            <div class="bill-consumer">
                <p class="bill-consumer-title">Consumer / Employee Details</p>
                <table class="bill-consumer-table">
                    <colgroup>
                        <col style="width: 24%;">
                        <col style="width: 26%;">
                        <col style="width: 24%;">
                        <col style="width: 26%;">
                    </colgroup>
                    <tr>
                        <td class="label">Name of Employee</td>
                        <td class="value"><strong>{{ $bill->emp_name ?? '—' }}</strong></td>
                        <td class="label">Designation</td>
                        <td class="value">{{ $bill->emp_designation ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Employee Type / Unit</td>
                        <td class="value">{{ $bill->unit_sub_type ?? '—' }}</td>
                        <td class="label">House / Quarter No.</td>
                        <td class="value"><strong>{{ $bill->house_display ?? $bill->house_no ?? '—' }}</strong></td>
                    </tr>
                </table>
            </div>

            <!-- Meter & consumption -->
            <div class="bill-section-title">Meter &amp; Consumption Details</div>
            <div class="bill-table-wrap">
                <table class="bill-table">
                    <thead>
                        <tr>
                            <th>Meter No.</th>
                            <th class="text-right">Previous Reading</th>
                            <th class="text-right">Current Reading</th>
                            <th class="text-right">Units Consumed</th>
                            <th class="text-right">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $bill->meter_one ?? '—' }}</td>
                            <td class="text-right">{{ !empty($bill->meter_one_is_new) ? '—' : ($bill->last_month_elec_red ?? '—') }}</td>
                            <td class="text-right">{{ $bill->curr_month_elec_red ?? '—' }}</td>
                            <td class="text-right">{{ $bill->meter_one_consume_unit ?? '—' }}</td>
                            <td class="amount">₹ {{ number_format((float)($bill->meter_one_elec_charge ?? 0), 2) }}</td>
                        </tr>
                        @php
                            $hasSecondMeter = (isset($bill->meter_two) && trim((string)$bill->meter_two) !== '' && (int)$bill->meter_two !== 0)
                                || (int)($bill->meter_two_consume_unit ?? 0) > 0
                                || (float)($bill->meter_two_elec_charge ?? 0) > 0;
                        @endphp
                        @if($hasSecondMeter)
                        <tr>
                            <td>{{ $bill->meter_two ?? '—' }}</td>
                            <td class="text-right">{{ !empty($bill->meter_two_is_new) ? '—' : ($bill->last_month_elec_red2 ?? '—') }}</td>
                            <td class="text-right">{{ $bill->curr_month_elec_red2 ?? '—' }}</td>
                            <td class="text-right">{{ $bill->meter_two_consume_unit ?? '—' }}</td>
                            <td class="amount">₹ {{ number_format((float)($bill->meter_two_elec_charge ?? 0), 2) }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Charges breakdown -->
            <div class="bill-section-title">Charge Summary</div>
            <div class="bill-table-wrap">
                <table class="bill-table">
                    <thead>
                        <tr>
                            <th>Particulars</th>
                            <th class="text-right" style="width: 28%;">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Electricity Charges</td>
                            
                            <td class="amount">₹ {{ number_format((float)($bill->electricty_charges ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <td>Water Charges</td>
                            <td class="amount">₹ {{ number_format((float)($bill->water_charges ?? 0), 2) }}</td>
                        </tr>
                        <tr>
                            <td>Licence Fee</td>
                            <td class="amount">₹ {{ number_format((float)($bill->licence_fees ?? 0), 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Grand total -->
            <div class="bill-total-wrap">
                <div class="bill-total-box">
                    <div class="bill-total-label">Total Amount Payable</div>
                    <div class="grand-total">₹ {{ number_format($bill->grand_total ?? 0, 2) }}</div>
                    <div class="bill-amount-words">Amount in words: {{ $amountInWords }}</div>
                    <div class="bill-pay-by">Please pay as per institutional procedure. Quote Bill No. {{ $bill->bill_no ?? '—' }} when paying.</div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bill-footer">
                <p class="footer-note"><strong>Note:</strong> This is a computer-generated bill. Please pay the amount before the due date. For any discrepancy, contact the Estate Section, LBSNAA, Mussoorie.</p>
                <p class="mb-0">Payment may be made as per institutional procedure. Retain this copy for your records.</p>
                <div class="sign-block">
                    <div>
                        <div class="sign-line">Authorised Signatory</div>
                        <div class="sign-sub">Estate Section, LBSNAA</div>
                    </div>
                    <div>
                        <div class="sign-line">Date</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="no-print ds-empty-state">
        <i class="material-symbols-rounded" style="font-size: 3rem;">receipt_long</i>
        <p class="mb-1">No bill found</p>
        <p class="small mb-0">Select <strong>Month</strong>, <strong>Year</strong> and <strong>Employee</strong>
            above and click <strong>Show Bill</strong>, or open this page from <strong>Generate Estate Bill</strong>.
        </p>
    </div>
    @endif
</div>
@endsection

@push('scripts')
{{-- Select2 JS globally footer (admin.layouts.footer) se load hoti hai; yahan include ki zaroorat nahi. --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector("form[action=\"{{ route('admin.estate.reports.bill-report-print') }}\"]");
    var urlEmployees = "{{ route('admin.estate.reports.bill-report-print.employees') }}";
    // Ye filter form page-level hai (kisi modal ke andar nahi), isliye dropdownParent set nahi karte.
    var commonCfg = {
        allowClear: false,
        width: '100%',
    };

    // Safe destroy (agar Select2 laga ho to hi).
    function destroySelect2(el) {
        if (el && $(el).data('select2')) { try { $(el).select2('destroy'); } catch (e) {} }
    }

    function getFormSelectVal(id) {
        var el = document.getElementById(id);
        return el ? ($(el).val() || '') : '';
    }

    function loadEmployeesForCategory() {
        var category = getFormSelectVal('employee_category');
        var month = getFormSelectVal('month');
        var year = getFormSelectVal('year');
        var params = new URLSearchParams();
        params.set('employee_category', category || 'LBSNAA');
        if (month) params.set('month', month);
        if (year) params.set('year', year);
        fetch(urlEmployees + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                var empEl = document.getElementById('employee_pk');
                if (!empEl) return;
                destroySelect2(empEl);
                var html = '<option value="">Employee</option>';
                if (res.status && res.data && res.data.length) {
                    res.data.forEach(function (e) {
                        var label = (e.emp_name || '') + (e.employee_id ? ' (' + e.employee_id + ')' : '');
                        html += '<option value="' + e.pk + '">' + label.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</option>';
                    });
                }
                empEl.innerHTML = html;
                if (typeof $.fn.select2 !== 'undefined') {
                    $(empEl).select2(Object.assign({}, commonCfg, { placeholder: 'Employee' }));
                }
            })
            .catch(function () {
                var empEl = document.getElementById('employee_pk');
                if (empEl) destroySelect2(empEl);
                if (empEl) empEl.innerHTML = '<option value="">Employee</option>';
                if (empEl && typeof $.fn.select2 !== 'undefined') {
                    $(empEl).select2(Object.assign({}, commonCfg, { placeholder: 'Employee' }));
                }
            });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var base = "{{ route('admin.estate.reports.bill-report-print') }}";
            var names = ['employee_category', 'month', 'year', 'employee_type_pk', 'employee_pk'];
            var params = [];
            names.forEach(function (name) {
                var el = form.querySelector('[name="' + name + '"]');
                var val = el ? ($(el).val() || '') : '';
                if (val !== undefined && String(val).trim() !== '') {
                    params.push(encodeURIComponent(name) + '=' + encodeURIComponent(String(val).trim()));
                }
            });
            window.location = params.length > 0 ? base + '?' + params.join('&') : base;
            return false;
        });
    }

    if (typeof $.fn.select2 !== 'undefined') {
        var ids = [
            { id: 'employee_category', placeholder: 'Employee Category' },
            { id: 'month', placeholder: 'Month' },
            { id: 'year', placeholder: 'Year' },
            { id: 'employee_type_pk', placeholder: 'Employee Type' },
            { id: 'employee_pk', placeholder: 'Employee' }
        ];
        ids.forEach(function (item) {
            var el = document.getElementById(item.id);
            if (el && !$(el).data('select2')) {
                $(el).select2(Object.assign({}, commonCfg, { placeholder: item.placeholder }));
            }
        });

        // Category / Month / Year change hone par employee list dobara load karo.
        // Select2 native <select> par 'change' fire karta hai, isliye jQuery change binding kaafi hai.
        $('#employee_category, #month, #year').on('change', loadEmployeesForCategory);
    }
});
</script>
@endpush
