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
    .bill-doc { break-inside: avoid; page-break-inside: avoid; }
    @page { size: A4; margin: 12mm; }
    body { background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .bill-doc { box-shadow: none !important; }
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
    background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 50%, #1a365d 100%);
    color: #fff;
    text-align: center;
    padding: 20px 24px 22px;
    position: relative;
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
    color: #f6e05e;
}
.bill-badge {
    position: absolute; top: 14px; right: 20px;
    font-size: 9pt; font-weight: 700; letter-spacing: 0.12em;
    background: #c9a227; color: #1a1a1a; padding: 6px 14px;
    border-radius: 0; box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.bill-emblem {
    width: 48px; height: 48px; border: 2px solid rgba(255,255,255,0.6);
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    margin: 0 auto 10px; font-size: 11pt; font-weight: 800; color: #f6e05e;
    background: rgba(255,255,255,0.08); letter-spacing: 0.02em;
}
/* --- Bill meta bar --- */
.bill-meta-bar {
    display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
    padding: 14px 24px; background: linear-gradient(to bottom, #edf2f7 0%, #e2e8f0 100%);
    border-bottom: 2px solid #cbd5e0;
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
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(to bottom, #fafbfc 0%, #f7fafc 100%);
    border-left: 4px solid #2c5282;
}
.bill-consumer-title {
    font-size: 10pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;
    color: #2c5282; margin: 0 0 12px 0; padding-bottom: 6px;
    border-bottom: 2px solid #cbd5e0;
}
.bill-consumer-table { width: 100%; border-collapse: collapse; }
.bill-consumer-table td { padding: 6px 14px 6px 0; vertical-align: middle; font-size: 10.5pt; }
.bill-consumer-table .label { width: 26%; color: #4a5568; font-weight: 600; }
.bill-consumer-table .value { font-weight: 500; color: #1a202c; }
/* --- Section title --- */
.bill-section-title {
    font-size: 10.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
    color: #1e3a5f; margin: 20px 24px 10px; padding: 8px 0 8px 12px;
    border-left: 4px solid #c9a227; background: #f8fafc;
}
/* --- Tables --- */
.bill-table-wrap { padding: 0 24px 8px; }
.bill-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 10.5pt; }
.bill-table th, .bill-table td { border: 1px solid #a0aec0; padding: 10px 12px; text-align: left; }
.bill-table th {
    background: linear-gradient(to bottom, #2c5282 0%, #2b6cb0 100%);
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
    background: linear-gradient(135deg, #ebf8ff 0%, #e2e8f0 100%);
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
    border-top: 2px solid #e2e8f0; background: linear-gradient(to bottom, #f7fafc 0%, #edf2f7 100%);
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
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
.ts-dropdown { z-index: 1060 !important; max-height: 220px !important; overflow-y: auto !important; }
</style>
@endpush
<div class="container-fluid">
    <!-- Breadcrumb (hidden when printing) -->
    <div class="no-print">
        <x-breadcrum title="Estate Bill Report for Print"></x-breadcrum>
    </div>

    <!-- Filters (hidden when printing) -->
    <div class="no-print card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <h2 class="h6 fw-semibold text-body mb-0 d-flex align-items-center gap-2">
                <i class="material-symbols-rounded fs-5">filter_list</i>
                Filters
            </h2>
        </div>
        <div class="card-body p-4">
            <form method="get" action="{{ route('admin.estate.reports.bill-report-print') }}" class="row g-3 g-md-4 align-items-end">
                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                    <label for="employee_category" class="form-label fw-medium">Employee Category</label>
                    <select class="form-select" id="employee_category" name="employee_category" aria-label="Employee Category">
                        @php
                            $empCat = old('employee_category', request('employee_category', 'LBSNAA'));
                        @endphp
                        <option value="LBSNAA" {{ $empCat === 'LBSNAA' ? 'selected' : '' }}>LBSNAA</option>
                        <option value="Other Employee" {{ $empCat === 'Other Employee' ? 'selected' : '' }}>Other Employee</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                    <label for="month" class="form-label fw-medium">Select Month</label>
                    <select class="form-select" id="month" name="month" aria-label="Select Month">
                        <option value="">— Select Month —</option>
                        @foreach($months as $m)
                            <option value="{{ $m }}" {{ old('month', request('month')) === $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                    <label for="year" class="form-label fw-medium">Select Year</label>
                    <select class="form-select" id="year" name="year" aria-label="Select Year">
                        <option value="">— Select Year —</option>
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ old('year', request('year')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <label for="employee_type_pk" class="form-label fw-medium">Select Employee Type</label>
                    <select class="form-select" id="employee_type_pk" name="employee_type_pk" aria-label="Select Employee Type">
                        <option value="">— Select Employee Type —</option>
                        @foreach($employeeTypes as $et)
                            <option value="{{ $et->pk }}" {{ old('employee_type_pk', request('employee_type_pk')) == $et->pk ? 'selected' : '' }}>{{ $et->unit_sub_type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <label for="employee_pk" class="form-label fw-medium">Select Employee</label>
                    <select class="form-select" id="employee_pk" name="employee_pk" aria-label="Select Employee">
                        <option value="">— Select Employee —</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->pk }}" {{ old('employee_pk', request('employee_pk')) == $emp->pk ? 'selected' : '' }}>{{ $emp->emp_name }} {{ $emp->employee_id ? '(' . trim($emp->employee_id) . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                        <i class="material-symbols-rounded" style="font-size: 1.1rem;">visibility</i>
                        Show Bill
                    </button>
                </div>
            </form>
        </div>
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
    @endphp
    <!-- Bill for print (visible on screen and in print) -->
    <div class="estate-bill-print no-print mb-4">
        <button type="button" class="btn btn-primary btn-sm mb-3 d-inline-flex align-items-center gap-1" onclick="window.print();" title="Print this bill">
            <i class="material-symbols-rounded" style="font-size: 1rem;">print</i>
            Print Bill
        </button>
    </div>

    <div class="estate-bill-print bill-page">
        <div class="bill-doc">
            <!-- Header -->
            <div class="bill-header">
                <span class="bill-badge">Consumer Copy</span>
                <div class="bill-emblem">LBS</div>
                <p class="org-name">Lal Bahadur Shastri National Academy of Administration</p>
                <p class="org-sub">Mussoorie · Estate Section</p>
                <h1 class="bill-title">Estate Bill — Electricity, Water &amp; Licence</h1>
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
                    <tr>
                        <td class="label">Name of Employee</td>
                        <td class="value" style="width: 24%;"><strong>{{ $bill->emp_name ?? '—' }}</strong></td>
                        <td class="label" style="width: 26%;">Designation</td>
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
                            <td class="text-right">{{ $bill->last_month_elec_red ?? '—' }}</td>
                            <td class="text-right">{{ $bill->curr_month_elec_red ?? '—' }}</td>
                            <td class="text-right">{{ $bill->meter_one_consume_unit ?? '—' }}</td>
                            <td class="amount">₹ {{ number_format((float)($bill->meter_one_elec_charge ?? 0), 2) }}</td>
                        </tr>
                        @if(!empty($bill->meter_two) || isset($bill->meter_two_consume_unit) || (float)($bill->meter_two_elec_charge ?? 0) > 0)
                        <tr>
                            <td>{{ $bill->meter_two ?? '—' }}</td>
                            <td class="text-right">{{ $bill->last_month_elec_red2 ?? '—' }}</td>
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
    <div class="no-print card shadow-sm">
        <div class="card-body text-center py-5">
            <p class="text-muted mb-0">No bill found. Select <strong>Month</strong>, <strong>Year</strong> and <strong>Employee</strong> above and click <strong>Show Bill</strong>, or use the link from <strong>Generate Estate Bill</strong> with bill_no, month and year.</p>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('form[action="{{ route('admin.estate.reports.bill-report-print') }}"]');
    var urlEmployees = '{{ route('admin.estate.reports.bill-report-print.employees') }}';
    var commonCfg = {
        allowEmptyOption: true,
        create: false,
        dropdownParent: 'body',
        maxOptions: null,
        hideSelected: false,
    };

    function getFormSelectVal(id) {
        var el = document.getElementById(id);
        return el && el.tomselect ? el.tomselect.getValue() : (el ? el.value : '');
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
                if (empEl.tomselect) { try { empEl.tomselect.destroy(); } catch (e) {} }
                var html = '<option value="">— Select Employee —</option>';
                if (res.status && res.data && res.data.length) {
                    res.data.forEach(function (e) {
                        var label = (e.emp_name || '') + (e.employee_id ? ' (' + e.employee_id + ')' : '');
                        html += '<option value="' + e.pk + '">' + label.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</option>';
                    });
                }
                empEl.innerHTML = html;
                if (typeof TomSelect !== 'undefined') {
                    new TomSelect(empEl, Object.assign({}, commonCfg, { placeholder: '— Select Employee —' }));
                }
            })
            .catch(function () {
                var empEl = document.getElementById('employee_pk');
                if (empEl && empEl.tomselect) { try { empEl.tomselect.destroy(); } catch (e) {} }
                if (empEl) empEl.innerHTML = '<option value="">— Select Employee —</option>';
                if (empEl && typeof TomSelect !== 'undefined') {
                    new TomSelect(empEl, Object.assign({}, commonCfg, { placeholder: '— Select Employee —' }));
                }
            });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var base = '{{ route('admin.estate.reports.bill-report-print') }}';
            var names = ['employee_category', 'month', 'year', 'employee_type_pk', 'employee_pk'];
            var params = [];
            names.forEach(function (name) {
                var el = form.querySelector('[name="' + name + '"]');
                var val = el && el.tomselect ? el.tomselect.getValue() : (el ? el.value : '');
                if (val !== undefined && String(val).trim() !== '') {
                    params.push(encodeURIComponent(name) + '=' + encodeURIComponent(String(val).trim()));
                }
            });
            window.location = params.length > 0 ? base + '?' + params.join('&') : base;
            return false;
        });
    }

    if (typeof TomSelect !== 'undefined') {
        var ids = [
            { id: 'employee_category', placeholder: 'Employee Category' },
            { id: 'month', placeholder: '— Select Month —' },
            { id: 'year', placeholder: '— Select Year —' },
            { id: 'employee_type_pk', placeholder: '— Select Employee Type —' },
            { id: 'employee_pk', placeholder: '— Select Employee —' }
        ];
        ids.forEach(function (item) {
            var el = document.getElementById(item.id);
            if (el && !el.tomselect) {
                new TomSelect(el, Object.assign({}, commonCfg, { placeholder: item.placeholder }));
            }
        });

        var catEl = document.getElementById('employee_category');
        var monthEl = document.getElementById('month');
        var yearEl = document.getElementById('year');
        function attachEmployeeReload() {
            if (catEl && catEl.tomselect) catEl.tomselect.on('change', loadEmployeesForCategory);
            if (monthEl && monthEl.tomselect) monthEl.tomselect.on('change', loadEmployeesForCategory);
            if (yearEl && yearEl.tomselect) yearEl.tomselect.on('change', loadEmployeesForCategory);
        }
        attachEmployeeReload();
    }
});
</script>
@endpush
