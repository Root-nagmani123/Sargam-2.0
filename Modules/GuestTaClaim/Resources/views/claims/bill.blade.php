@php
    $fmt = \Modules\GuestTaClaim\Support\Format::class;
    $allowances = $claim->travellingAllowances;
    $hasAllowances = $allowances->isNotEmpty();
    $farePaid = $hasAllowances ? (float) $allowances->sum('fare_paid') : (float) $claim->travel_fare;
    $fareAdmissible = $hasAllowances ? (float) $allowances->sum('fare_admissible') : (float) $claim->travel_fare;
    $accommodation = $hasAllowances ? (float) $allowances->sum('accommodation') : (float) $claim->local_conveyance;
    $journeyRows = max(7, $allowances->count());
    $amount = fn ($value) => str_replace('₹', '', $fmt::money($value));
    $money = fn ($value) => 'Rs. '.$amount($value);
    $daPercentage = (int) ($claim->da_percentage ?: 100);
    $journeyDa = (int) $claim->da_journey_days * (float) $claim->da_rate * $daPercentage / 100;
    $stayDa = (int) $claim->da_stay_days * (float) $claim->da_rate * $daPercentage / 100;
    $totalDa = $journeyDa + $stayDa;
    $totalTaDa = $fareAdmissible + $accommodation + $totalDa;
    $legacyMode = $claim->travel_mode !== 'Not applicable' ? $claim->travel_mode : '';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Travelling Allowance Bill — {{ $claim->claim_no }}</title>
    <style>
        @page{size:A4 portrait;margin:8mm 12mm}
        *{box-sizing:border-box}
        body{font-family:"Times New Roman",serif;font-size:9pt;color:#000;margin:0}
        .sheet{width:100%;min-height:281mm;position:relative}
        .toolbar{text-align:right;margin:0 0 5mm;font:14px Arial,sans-serif}
        .toolbar button{padding:6px 14px;border:1px solid #555;background:#fff;border-radius:4px;cursor:pointer}
        .heading{text-align:center;font-size:10pt;font-weight:bold;line-height:1.15}
        .fields{margin-top:5mm}
        .field{display:flex;align-items:flex-end;min-height:8mm;gap:3mm}
        .label{white-space:nowrap}
        .line{flex:1;min-height:6mm;border-bottom:1px solid #000;padding:0 1mm 1mm}
        .course .label{white-space:normal}
        h2{font-size:9pt;margin:4mm 0 1mm}
        table{width:100%;border-collapse:collapse;table-layout:fixed}
        th,td{border:1px solid #000;padding:1mm 1.2mm;vertical-align:middle}
        th{font-weight:bold;text-align:center}
        .travel{font-size:7.5pt}
        .travel th{height:10mm;line-height:1.05}
        .travel td{height:6mm}
        .travel .c1{width:12%}.travel .c2{width:10%}.travel .c3{width:12%}.travel .c4{width:10%}
        .travel .c5{width:17%}.travel .c6{width:11%}.travel .c7{width:13%}.travel .c8{width:15%}
        .num{text-align:right;white-space:nowrap}
        .section-title{text-align:left;font-style:italic}
        .da{font-size:8pt}
        .da td{height:6mm}
        .da-head td{height:6mm;font-weight:bold}
        .da-label{font-style:italic}
        .note{font-size:7.5pt}
        .totals{margin-top:0;font-size:8pt}
        .totals td{height:6mm}
        .declarations{display:flex;gap:5mm;margin-top:8mm;align-items:flex-start;font-size:8pt}
        .declaration-copy{flex:1;line-height:1.25}
        .signature{width:33%;text-align:center;font-style:italic;padding-top:7mm}
        .approval{position:absolute;right:0;bottom:7mm;text-align:right;font-size:8pt;white-space:nowrap}
        .selected{font-weight:bold}
        @media print{.toolbar{display:none}.sheet{break-inside:avoid}}
    </style>
</head>
<body>
<div class="sheet">
    <div class="toolbar"><button type="button" onclick="window.print()">Print</button></div>
    <div class="heading">Lal Bahadur Shastri National Academy of Administration, Mussoorie<br>Travelling Allowance Bill</div>
    <div class="fields">
        <div class="field"><span class="label">1&nbsp; Name of the Guest Speaker:</span><span class="line">{{ $claim->faculty_name }}</span></div>
        <div class="field"><span class="label">2&nbsp; Designation:</span><span class="line">{{ $claim->faculty_designation }}</span></div>
        <div class="field"><span class="label">3&nbsp; Official Address:</span><span class="line">{{ $claim->faculty_official_address }}</span></div>
        <div class="field"><span class="label">4&nbsp; Basic Pay (if in government service):</span><span class="line">Rs. {{ $claim->basic_pay !== null ? $amount($claim->basic_pay) : '' }}</span></div>
        <div class="field course"><span class="label">5&nbsp; Course/Workshop/Seminar for which invited:</span><span class="line"><b>{{ $claim->course_name }}</b></span></div>
    </div>
    <h2>A.&nbsp; Travelling Allowance:</h2>
    <table class="travel">
        <colgroup><col class="c1"><col class="c2"><col class="c3"><col class="c4"><col class="c5"><col class="c6"><col class="c7"><col class="c8"></colgroup>
        <thead><tr>
            <th>Departure<br>Date &amp; Time</th><th>From</th><th>Arrival<br>Date &amp; Time</th><th>To</th>
            <th>Mode of Travel<br>&amp; Class of Accommodation</th><th>Distance<br>In Kms.</th><th>Fare Paid</th><th>Fare Admissible</th>
        </tr></thead>
        <tbody>
        @for ($i = 0; $i < $journeyRows; $i++)
            @php($allowance = $allowances->get($i))
            @php($modeAndAccommodation = $allowance
                ? collect([$allowance->travel_mode, (float) $allowance->accommodation > 0 ? 'Accommodation: '.$money($allowance->accommodation) : null])->filter()->join(' / ')
                : ($i === 0 ? collect([$legacyMode, (float) $accommodation > 0 ? 'Accommodation: '.$money($accommodation) : null])->filter()->join(' / ') : ''))
            <tr>
                <td>{{ $allowance ? $fmt::dateTime($allowance->departure_at) : '' }}</td>
                <td>{{ $allowance ? $allowance->departure_from : ($i === 0 ? $claim->travel_from : '') }}</td>
                <td>{{ $allowance ? $fmt::dateTime($allowance->arrival_at) : '' }}</td>
                <td>{{ $allowance ? $allowance->arrival_to : '' }}</td>
                <td>{{ $modeAndAccommodation }}</td>
                <td class="num">{{ $allowance?->distance_km }}</td>
                <td class="num">{{ $allowance ? ((float) $allowance->fare_paid ? $amount($allowance->fare_paid) : '') : '' }}</td>
                <td class="num">{{ $allowance ? ((float) $allowance->fare_admissible ? $amount($allowance->fare_admissible) : '') : '' }}</td>
            </tr>
        @endfor
        <tr><td colspan="6"></td><td class="num"><b>Total&nbsp; {{ $amount($farePaid) }}</b></td><td class="num"><b>{{ $amount($fareAdmissible) }}</b></td></tr>
        </tbody>
    </table>
    <table class="da">
        <colgroup><col class="c1"><col class="c2"><col class="c3"><col class="c4"><col class="c5"><col class="c6"><col class="c7"><col class="c8"></colgroup>
        <tbody>
        <tr class="da-head"><td colspan="4" class="section-title">B. Dearness Allowance to be filled up by Accounts Section.</td><td>No. of days<br>Full&nbsp; 0.70</td><td>Rate</td><td>Total DA</td><td></td></tr>
        <tr><td colspan="4" class="da-label">1.&nbsp; DA for Journey period</td><td class="num">{{ $claim->da_journey_days }} / {{ $daPercentage === 100 ? 'Full' : '0.70' }}</td><td class="num">{{ $amount($claim->da_rate) }}</td><td class="num">{{ $amount($journeyDa) }}</td><td></td></tr>
        <tr><td colspan="4" class="da-label">2.&nbsp; DA for Stay in Academy</td><td class="num">{{ $claim->da_stay_days }} / {{ $daPercentage === 100 ? 'Full' : '0.70' }}</td><td class="num">{{ $amount($claim->da_rate) }}</td><td class="num">{{ $amount($stayDa) }}</td><td></td></tr>
        <tr><td colspan="8" class="note">DA will calculate with old rate/revised rates (Guest Speakers are requested to please tick the appropriate):
            {{ $claim->da_rate_basis === 'old' ? '☒' : '☐' }} Old rate&nbsp;&nbsp; {{ $claim->da_rate_basis === 'revised' ? '☒' : '☐' }} Revised rate</td></tr>
        </tbody>
    </table>
    <table class="totals"><tbody>
        <tr><td style="width:73%"></td><td><b>Total DA</b></td><td class="num">{{ $amount($totalDa) }}</td></tr>
        <tr><td></td><td><b>Grand Total of TA &amp; DA</b></td><td class="num"><b>{{ $amount($totalTaDa) }}</b></td></tr>
    </tbody></table>
    <div class="declarations">
        <div class="declaration-copy">
            <div>1.&nbsp; The journeys at Sl.No. <b>{{ $claim->admissible_journey_numbers ?: '________' }}</b> have been performed by admissible mode/class.</div>
            <div>2.&nbsp; The journeys at Sl.No. <b>{{ $claim->higher_class_journey_numbers ?: '________' }}</b> have been performed by a mode higher than admissible.</div>
            <div class="{{ $claim->higher_class_reimbursement_basis === 'admissible_rate' ? 'selected' : '' }}">Payment for these journeys may be made at the admissible rate.</div>
            <div style="text-align:center">OR</div>
            <div class="{{ $claim->higher_class_reimbursement_basis === 'actual_fare_public_interest' ? 'selected' : '' }}">The actual fare paid may be reimbursed as the journey was essential and in the Public interest.</div>
        </div>
        <div class="signature">Signature of Guest Speaker</div>
    </div>
    <div class="approval">Approved by CC/ACC</div>
</div>
</body>
</html>
