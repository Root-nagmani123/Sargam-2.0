@extends('admin.layouts.master')
@section('title', 'Approvals')
@section('page_title', 'Approvals')
@section('page_subtitle', 'Manage approvals for TA claim requests')
@section('content')
@include('guesttaclaim::partials.style')
<div class="container-fluid profile-page">
    <x-breadcrum
        title="Approvals"
        :items="[
            'Home',
            ['label' => 'Guest TA Claim', 'url' => route('protocol.dashboard')],
            'Approvals',
        ]"
    />
    @include('guesttaclaim::partials.flash')
<div class="gtc-card">
    <h2>Claims for your courses</h2>
    <p class="gtc-sub">Approve to send a claim to Accounts, or return it to TA & Training with a remark.</p>
    <ul class="nav gtc-tabs mt-3 no-print">
        <li class="nav-item"><a class="nav-link {{ $tab === 'pending' ? 'active' : '' }}" href="{{ route('guest-ta-claim.approvals.index') }}">Waiting for you <span class="badge rounded-pill ms-1">{{ $pendingCount }}</span></a></li>
        <li class="nav-item"><a class="nav-link {{ $tab === 'done' ? 'active' : '' }}" href="{{ route('guest-ta-claim.approvals.index', ['tab' => 'done']) }}">Decided</a></li>
    </ul>
    @include('guesttaclaim::partials.claim-table', ['claims' => $claims, 'actions' => 'guesttaclaim::approvals._actions',
        'empty' => $tab === 'pending' ? 'Nothing is waiting for your approval.' : 'You haven’t decided any claims yet.'])
</div>
@include('guesttaclaim::partials.action-modals')
@endsection

@push('scripts')
    <script>{!! \Modules\GuestTaClaim\Support\Format::js() !!}</script>
@endpush
