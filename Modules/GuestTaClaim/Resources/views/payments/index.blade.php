@extends('admin.layouts.master')
@section('title', 'Payments')
@section('page_title', 'Payments')
@section('page_subtitle', 'Manage payments for TA claim requests')
@section('content')
@include('guesttaclaim::partials.style')
<div class="container-fluid profile-page">
    <x-breadcrum
        title="Payments"
        :items="[
            'Home',
            ['label' => 'Guest TA Claim', 'url' => route('protocol.dashboard')],
            'Payments',
        ]"
    />
<div class="gtc-card">
    <h2>Payment requests</h2>
    <p class="gtc-sub">Claims approved by the course coordinator or submitted with a signed bill.</p>
    <ul class="nav gtc-tabs mt-3 no-print">
        <li class="nav-item"><a class="nav-link {{ $tab === 'pending' ? 'active' : '' }}" href="{{ route('guest-ta-claim.payments.index') }}">To be paid <span class="badge rounded-pill ms-1">{{ $counts['pending'] }}</span></a></li>
        <li class="nav-item"><a class="nav-link {{ $tab === 'paid' ? 'active' : '' }}" href="{{ route('guest-ta-claim.payments.index', ['tab' => 'paid']) }}">Paid <span class="badge rounded-pill ms-1">{{ $counts['paid'] }}</span></a></li>
    </ul>
    @include('guesttaclaim::partials.claim-table', ['claims' => $claims, 'actions' => 'guesttaclaim::payments._actions',
        'empty' => $tab === 'paid' ? 'No claims have been paid yet.' : 'No approved claims are waiting for payment.'])
</div>
@endsection
