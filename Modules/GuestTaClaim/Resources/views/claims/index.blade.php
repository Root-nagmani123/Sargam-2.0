@extends('admin.layouts.master')
@section('title', 'Claim Request')
@section('page_title', 'Guest Faculty TA Claim Requests')
@section('page_subtitle', 'Manage and track all TA claim requests submitted by guest faculty.')
@php
    $tabs = ['all' => 'All'] + collect(\Modules\GuestTaClaim\Enums\ClaimStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all();
@endphp
@section('content')
@include('GuestTaClaim::partials.style')
<div class="container-fluid profile-page">
    <x-breadcrum
      title="Claim Request"
      :items="[
          'Home',
          ['label' => 'Guest TA Claim', 'url' => route('protocol.dashboard')],
          'Claim Request',
        ]"
  />

    <div class="gtc-card">
        <h2>All claims</h2>
        <p class="gtc-sub">Open a claim to see its sessions, amounts and history.</p>
        <ul class="nav gtc-tabs mt-3 no-print">
            @foreach ($tabs as $key => $label)
                <li class="nav-item"><a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('guest-ta-claim.claims.index', ['tab' => $key]) }}">{{ $label }}
                    <span class="badge rounded-pill ms-1">{{ $key === 'all' ? $counts->sum() : ($counts[$key] ?? 0) }}</span></a></li>
            @endforeach
        </ul>
        @include('guesttaclaim::partials.claim-table', ['claims' => $claims, 'actions' => 'guesttaclaim::claims._actions'])
    </div>
</div>
@endsection
