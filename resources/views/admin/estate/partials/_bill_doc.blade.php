@php
    $bill = $bill ?? null;

    $toWordsBelowThousand = function (int $n): string {
        $ones = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight',
            9 => 'Nine', 10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
        ];
        $tens = [2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty', 6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'];

        $parts = [];
        if ($n >= 100) {
            $parts[] = $ones[intdiv($n, 100)] . ' Hundred';
            $n = $n % 100;
        }
        if ($n >= 20) {
            $parts[] = $tens[intdiv($n, 10)] . (($n % 10) ? ' ' . $ones[$n % 10] : '');
        } elseif ($n > 0) {
            $parts[] = $ones[$n];
        }
        return trim(implode(' ', array_filter($parts)));
    };

    $toWordsIndian = function (int $n) use ($toWordsBelowThousand): string {
        if ($n === 0) {
            return 'Zero';
        }

        $parts = [];
        $crore = intdiv($n, 10000000);
        $n %= 10000000;
        $lakh = intdiv($n, 100000);
        $n %= 100000;
        $thousand = intdiv($n, 1000);
        $n %= 1000;
        $rest = $n;

        if ($crore > 0) {
            $parts[] = $toWordsBelowThousand($crore) . ' Crore';
        }
        if ($lakh > 0) {
            $parts[] = $toWordsBelowThousand($lakh) . ' Lakh';
        }
        if ($thousand > 0) {
            $parts[] = $toWordsBelowThousand($thousand) . ' Thousand';
        }
        if ($rest > 0) {
            $parts[] = $toWordsBelowThousand($rest);
        }

        return trim(implode(' ', $parts));
    };

    $grandTotal = (float) ($bill->grand_total ?? 0);
    $rupees = (int) floor($grandTotal);
    $paise = (int) round(($grandTotal - $rupees) * 100);
    if ($paise === 100) {
        $rupees += 1;
        $paise = 0;
    }

    $rupeesWords = $toWordsIndian($rupees);
    $amountInWords = 'Rupees ' . $rupeesWords;
    if ($paise > 0) {
        $amountInWords .= ' and ' . $toWordsIndian($paise) . ' Paise';
    }
    $amountInWords .= ' only';
@endphp
@if($bill)
<div class="bill-doc">
    <div class="bill-header">
        <span class="bill-badge">Consumer Copy</span>
        <div class="bill-emblem">LBSNAA</div>
        <p class="org-name">Lal Bahadur Shastri National Academy of Administration</p>
        <p class="org-sub">Mussoorie · Estate Section</p>
        <h1 class="bill-title">Estate Bill — Electricity, Water &amp; Licence</h1>
    </div>
    <div class="bill-meta-bar">
        <span class="bill-no">Bill No.: {{ $bill->bill_no ?? '—' }}</span>
        <span class="bill-period">Billing Period: {{ $bill->from_date_formatted ?? '—' }} to {{ $bill->to_date_formatted ?? '—' }} · {{ $bill->bill_month ?? '' }} {{ $bill->bill_year ?? '' }}</span>
    </div>
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
    <div class="bill-section-title">Meter &amp; Consumption Details</div>
    <div class="bill-table-wrap">
        <table class="bill-table">
            <thead>
                <tr>
                    <th>Meter No.</th>
                    <th class="text-right">Previous Reading</th>
                    <th class="text-right">Current Reading</th>
                    <th class="text-right">Units Consumed</th>
                    <th class="text-right">Amount (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $bill->meter_one ?? '—' }}</td>
                    <td class="text-right">{{ $bill->last_month_elec_red ?? '—' }}</td>
                    <td class="text-right">{{ $bill->curr_month_elec_red ?? '—' }}</td>
                    <td class="text-right">{{ $bill->meter_one_consume_unit ?? '—' }}</td>
                    <td class="amount">Rs. {{ number_format((float)($bill->meter_one_elec_charge ?? 0), 2) }}</td>
                </tr>
                @if(!empty($bill->meter_two) || isset($bill->meter_two_consume_unit) || (float)($bill->meter_two_elec_charge ?? 0) > 0)
                <tr>
                    <td>{{ $bill->meter_two ?? '—' }}</td>
                    <td class="text-right">{{ $bill->last_month_elec_red2 ?? '—' }}</td>
                    <td class="text-right">{{ $bill->curr_month_elec_red2 ?? '—' }}</td>
                    <td class="text-right">{{ $bill->meter_two_consume_unit ?? '—' }}</td>
                    <td class="amount">Rs. {{ number_format((float)($bill->meter_two_elec_charge ?? 0), 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    <div class="bill-section-title">Charge Summary</div>
    <div class="bill-table-wrap">
        <table class="bill-table">
            <thead>
                <tr>
                    <th>Particulars</th>
                    <th class="text-right" style="width: 28%;">Amount (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Electricity Charges</td>
                    <td class="amount">Rs. {{ number_format((float)($bill->electricty_charges ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <td>Water Charges</td>
                    <td class="amount">Rs. {{ number_format((float)($bill->water_charges ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <td>Licence Fee</td>
                    <td class="amount">Rs. {{ number_format((float)($bill->licence_fees ?? 0), 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="bill-total-wrap">
        <div class="bill-total-box">
            <div class="bill-total-label">Total Amount Payable</div>
            <div class="grand-total">Rs. {{ number_format($bill->grand_total ?? 0, 2) }}</div>
            <div class="bill-amount-words">Amount in words: {{ $amountInWords }}</div>
            <div class="bill-pay-by">Please pay as per institutional procedure. Quote Bill No. {{ $bill->bill_no ?? '—' }} when paying.</div>
        </div>
    </div>
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
@endif
