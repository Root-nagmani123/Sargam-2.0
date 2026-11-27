@php
    $fmt = \Modules\GuestTaClaim\Support\Format::class;
    $approved = $claim->histories->firstWhere('to_status', \Modules\GuestTaClaim\Enums\ClaimStatus::PendingAccounts->value);
    $prepared = $claim->histories->first();
    $paid = $claim->histories->firstWhere('to_status', \Modules\GuestTaClaim\Enums\ClaimStatus::Paid->value);
    $submittedDirectly = $approved?->action === 'Signed bill submitted directly to Accounts';
    $forPdf = $forPdf ?? false;
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $claim->claim_no }} – claim report</title>
<style>
    body{font-family:"Noto Sans",DejaVu Sans,Arial,sans-serif;color:#1c2333;font-size:13px;max-width:820px;margin:24px auto;padding:0 20px}
    h1{font-size:18px;text-align:center;margin:0}
    .c{text-align:center;color:#5a6275;margin:4px 0 18px}
    .kv{width:100%;border-collapse:collapse}.kv td{padding:4px 8px 4px 0;vertical-align:top;width:33%}.kv span{display:block;color:#5a6275;font-size:11.5px}
    table.t{width:100%;border-collapse:collapse;margin-top:14px}
    table.t th,table.t td{border:1px solid #c9cfd9;padding:6px 8px;text-align:left}
    table.t th{background:#f2f4f7;font-size:12px}
    .num{text-align:right!important;white-space:nowrap}
    .sign{width:100%;margin-top:56px;border-collapse:collapse;text-align:center;font-size:12px;color:#5a6275}
    .sign td{width:25%;padding:6px 10px}.sign div{border-top:1px solid #8a93a5;padding-top:6px}
    .bar{display:flex;gap:8px;justify-content:flex-end;margin-bottom:16px}
    .bar a,.bar button{font:inherit;font-size:13px;padding:7px 14px;border-radius:7px;border:1px solid #c9cfd9;background:#fff;color:#1c2333;text-decoration:none;cursor:pointer}
    .bar .p{background:#0b3b78;border-color:#0b3b78;color:#fff}
    .foot{margin-top:24px;color:#5a6275;font-size:11.5px}
    @media print{.bar{display:none}body{margin:0 auto}}
</style>
</head>
<body>
@unless ($forPdf)
    <div class="bar">
        <a href="{{ route('guest-ta-claim.claims.report', [$claim, 'download' => 'html']) }}">Download</a>
        @if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class))<a href="{{ route('guest-ta-claim.claims.report', [$claim, 'download' => 'pdf']) }}">Download PDF</a>@endif
        <button type="button" class="p" onclick="window.print()">Print</button>
    </div>
@endunless

<h1>Guest faculty honorarium and TA claim</h1>
<p class="c">{{ $claim->course_name }}, {{ $fmt::date($claim->period_from) }} to {{ $fmt::date($claim->period_to) }}</p>

<table class="kv">
    <tr><td><span>Claim no.</span><b>{{ $claim->claim_no }}</b></td><td><span>Name of the Guest Speaker</span><b>{{ $claim->faculty_name }}</b></td><td><span>Designation</span><b>{{ $claim->faculty_designation }}</b></td></tr>
    <tr><td><span>Official Address</span><b>{{ $claim->faculty_official_address }}</b></td><td><span>Basic Pay</span><b>{{ $claim->basic_pay !== null ? $fmt::money($claim->basic_pay) : 'Not provided' }}</b></td><td><span>Status</span><b>{{ $claim->status->label() }}</b></td></tr>
</table>

<h2>A. Travelling Allowance</h2>
<table class="t">
    <thead><tr><th>Departure date &amp; time</th><th>From</th><th>Arrival date &amp; time</th><th>To</th><th>Mode</th><th>Distance (km)</th><th>Fare paid</th><th>Fare admissible</th><th>Accommodation</th></tr></thead>
    <tbody>
    @forelse ($claim->travellingAllowances as $allowance)
        <tr>
            <td>{{ $fmt::dateTime($allowance->departure_at) }}</td><td>{{ $allowance->departure_from }}</td>
            <td>{{ $fmt::dateTime($allowance->arrival_at) }}</td><td>{{ $allowance->arrival_to }}</td>
            <td>{{ $allowance->travel_mode }}</td><td>{{ $allowance->distance_km }}</td>
            <td class="num">{{ $fmt::money($allowance->fare_paid) }}</td><td class="num">{{ $fmt::money($allowance->fare_admissible) }}</td>
            <td class="num">{{ $fmt::money($allowance->accommodation) }}</td>
        </tr>
    @empty
        <tr><td colspan="9">No journey rows recorded.</td></tr>
    @endforelse
    </tbody>
</table>

<h2>Journey declarations</h2>
<table class="t">
    <tr><td>Admissible mode/class journey numbers</td><td>{{ $claim->admissible_journey_numbers ?: 'Not provided' }}</td></tr>
    <tr><td>Higher mode/class journey numbers</td><td>{{ $claim->higher_class_journey_numbers ?: 'Not provided' }}</td></tr>
    <tr><td>Higher-class reimbursement</td><td>
        @if ($claim->higher_class_reimbursement_basis === 'admissible_rate') Pay at admissible rate
        @elseif ($claim->higher_class_reimbursement_basis === 'actual_fare_public_interest') Actual fare (essential/public interest)
        @else Not specified
        @endif
    </td></tr>
</table>

<table class="t">
    <thead><tr><th>#</th><th>Date</th><th>Time</th><th>Topic</th><th>Venue</th><th>Status</th></tr></thead>
    <tbody>
    @foreach ($claim->sessions as $i => $s)
        <tr><td>{{ $i + 1 }}</td><td>{{ $fmt::date($s->session_date) }}</td><td>{{ substr($s->start_time, 0, 5) }}–{{ substr($s->end_time, 0, 5) }}</td><td>{{ $s->topic }}</td><td>{{ $s->venue }}</td><td>{{ ucfirst($s->status) }}</td></tr>
    @endforeach
    </tbody>
</table>

<table class="t">
    <tr><td>Honorarium ({{ $claim->sessions_taken }} sessions × {{ $fmt::money($claim->honorarium_rate) }})</td><td class="num">{{ $fmt::money($claim->honorarium_amount) }}</td></tr>
    <tr><td>Fare admissible</td><td class="num">{{ $fmt::money($claim->travel_fare) }}</td></tr>
    <tr><td>Accommodation</td><td class="num">{{ $fmt::money($claim->local_conveyance) }}</td></tr>
    <tr><td>B. DA ({{ $claim->da_journey_days }} journey days + {{ $claim->da_stay_days }} stay days × {{ $fmt::money($claim->da_rate) }} × {{ $claim->da_percentage }}%)</td><td class="num">{{ $fmt::money($claim->da_amount) }}</td></tr>
    <tr><td><b>Grand total of TA &amp; DA</b></td><td class="num"><b>{{ $fmt::money((float) $claim->travel_fare + (float) $claim->local_conveyance + (float) $claim->da_amount) }}</b></td></tr>
    <tr><td><b>Total claimed</b></td><td class="num"><b>{{ $fmt::money($claim->total_amount) }}</b></td></tr>
    <tr><td><b>Amount paid</b> on {{ $fmt::dateTime($claim->paid_at) }}@if ($claim->paid_remark)<br><span style="color:#5a6275">{{ $claim->paid_remark }}</span>@endif</td><td class="num"><b>{{ $fmt::money($claim->paid_amount) }}</b></td></tr>
</table>

<table class="sign"><tr>
    <td><div>Signature of Guest Speaker</div></td>
    <td><div>Prepared by<br><b style="color:#1c2333">{{ $prepared?->user_name }}</b><br>TA & Training</div></td>
    <td><div>{{ $submittedDirectly ? 'Submitted to Accounts by' : 'Approved by' }}<br><b style="color:#1c2333">{{ $approved?->user_name }}</b><br>{{ $submittedDirectly ? 'TA & Training' : 'Course coordinator' }}</div></td>
    <td><div>Paid by<br><b style="color:#1c2333">{{ $paid?->user_name }}</b><br>Accounts</div></td>
</tr></table>

<p class="foot">Generated from Sargam 2.0 on {{ now()->format('d-m-Y H:i') }}.</p>
</body>
</html>
