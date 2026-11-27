@extends('admin.layouts.master')
@section('title', 'Guest TA Claim Dashboard')
@section('page_title', 'Guest Faculty TA Claim Dashboard')
@section('page_subtitle', 'Keep track of all your claims and their current status.')
@section('content')
@include('GuestTaClaim::partials.style')
@php
    $fmt = \Modules\GuestTaClaim\Support\Format::class;
    $hour = now()->hour;
    $listRoute = Gate::any(['gtc.claim.create', 'gtc.claim.forward', 'gtc.claim.report']) ? 'guest-ta-claim.claims.index' : null;
    $stats = [
        ['Drafts', str_pad($counts[\Modules\GuestTaClaim\Enums\ClaimStatus::Draft->value] ?? 0, 2, '0', STR_PAD_LEFT), 'bi-file-earmark', 't-grey', \Modules\GuestTaClaim\Enums\ClaimStatus::Draft],
        ['With course coordinator', str_pad($counts[\Modules\GuestTaClaim\Enums\ClaimStatus::PendingCc->value] ?? 0, 2, '0', STR_PAD_LEFT), 'bi-hourglass-split', 't-amber', \Modules\GuestTaClaim\Enums\ClaimStatus::PendingCc],
        ['Returned for correction', str_pad($counts[\Modules\GuestTaClaim\Enums\ClaimStatus::Returned->value] ?? 0, 2, '0', STR_PAD_LEFT), 'bi-arrow-counterclockwise', 't-red', \Modules\GuestTaClaim\Enums\ClaimStatus::Returned],
        ['With accounts', str_pad($counts[\Modules\GuestTaClaim\Enums\ClaimStatus::PendingAccounts->value] ?? 0, 2, '0', STR_PAD_LEFT), 'bi-wallet2', 't-blue', \Modules\GuestTaClaim\Enums\ClaimStatus::PendingAccounts],
        ['Paid', $fmt::money($paidTotal), 'bi-check2-circle', 't-green', \Modules\GuestTaClaim\Enums\ClaimStatus::Paid],
    ];
@endphp
<div class="container-fluid profile-page">
<div class="gtc-card gtc-pagehead">
    <div>
        <div class="gtc-sub">Good {{ $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening') }},
            <b class="text-primary">{{ auth()->user()->{config('guest-ta-claim.user_name_column', 'name')} }}</b></div>
        <h1>Guest TA Claim</h1>
    </div>
    <div class="d-flex align-items-center gap-2 text-primary">
        <i class="bi bi-clock fs-3"></i>
        <span><b class="fs-5">{{ now()->format('H:i') }}</b><br><span class="gtc-sub">{{ now()->format('l, j F Y') }}</span></span>
    </div>
</div>

<div class="gtc-stats">
    @foreach ($stats as [$label, $value, $icon, $tone, $status])
        <a class="gtc-stat" href="{{ $listRoute ? route($listRoute, ['tab' => $status->value]) : '#' }}">
            <span class="gtc-tile {{ $tone }}"><i class="bi {{ $icon }}"></i></span>
            <span><span class="lbl">{{ $label }}</span><br><span class="val">{{ $value }}</span></span>
        </a>
    @endforeach
</div>

<div class="gtc-card">
    <h2>How a claim moves</h2>
    <p class="gtc-sub">Each stage is handled by the people mapped to it under Assign permission.</p>
    <div class="gtc-flow">
        <div><b><i>1</i>TA & Training</b><p>Selects course and dates, reviews the faculty-wise timetable, edits sessions, fills the claim and forwards it.</p></div>
        <div><b><i>2</i>Course coordinator</b><p>Checks the sessions and amounts, then approves or returns with a remark.</p></div>
        <div><b><i>3</i>Accounts</b><p>Opens the request details and marks it paid, with an optional remark and amount.</p></div>
        <div><b><i>4</i>Report</b><p>TA & Training sees the claim as approved and generates the claim report.</p></div>
    </div>
</div>

<div class="gtc-card">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div><h2>Recent claims</h2><p class="gtc-sub">Claims you can see in your role</p></div>
        @can('gtc.claim.create')
            <a href="{{ route('guest-ta-claim.claims.create') }}" class="btn btn-primary"><i class="bi bi-file-earmark-plus"></i> New claim</a>
        @endcan
    </div>
    @include('guesttaclaim::partials.claim-table', ['claims' => $recent])
</div>
</div>
@endsection
