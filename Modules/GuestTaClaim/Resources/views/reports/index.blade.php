@extends('admin.layouts.master')
@section('title', 'Reports')
@section('page_title', 'Reports')
@section('page_subtitle', 'Filter, then print or export. Paid claims also have a claim report.')
@section('content')
@include('guesttaclaim::partials.style')
<div class="container-fluid profile-page">
    <x-breadcrum
        title="Reports"
        :items="[
            'Home',
            ['label' => 'Guest TA Claim', 'url' => route('protocol.dashboard')],
            'Reports',
        ]"
    />
@php($fmt = \Modules\GuestTaClaim\Support\Format::class)
<div class="gtc-card">
    <h2>Claim register</h2>
    <p class="gtc-sub">Filter, then print or export. Paid claims also have a claim report.</p>
    <form method="GET" class="row g-3 align-items-end mt-1 no-print">
        <div class="col-lg-4 col-md-6"><label class="form-label" for="r_course">Course</label>
            <select class="form-select" id="r_course" name="course_id"><option value="">All courses</option>
                @foreach ($courses as $c)<option value="{{ $c['id'] }}" {{ ((string) request('course_id') === (string) $c['id']) ? 'selected' : '' }}>{{ $c['name'] }}</option>@endforeach
            </select></div>
        <div class="col-lg-3 col-md-6"><label class="form-label" for="r_status">Status</label>
            <select class="form-select" id="r_status" name="status"><option value="">All statuses</option>
                @foreach (\Modules\GuestTaClaim\Enums\ClaimStatus::cases() as $s)<option value="{{ $s->value }}" {{ (request('status') === $s->value) ? 'selected' : '' }}>{{ $s->label() }}</option>@endforeach
            </select></div>
        <div class="col-lg-2 col-6"><label class="form-label" for="r_from">Period from</label><input type="date" class="form-control" id="r_from" name="from" value="{{ request('from') }}"></div>
        <div class="col-lg-2 col-6"><label class="form-label" for="r_to">Period to</label><input type="date" class="form-control" id="r_to" name="to" value="{{ request('to') }}"></div>
        <div class="col-lg-1"><button class="btn btn-primary w-100">Filter</button></div>
    </form>
    <div class="gtc-kv gtc-section">
        <div><span>Claims</span><b>{{ $summary['count'] }}</b></div>
        <div><span>Total claimed</span><b>{{ $fmt::money($summary['claimed']) }}</b></div>
        <div><span>Total paid</span><b>{{ $fmt::money($summary['paid']) }}</b></div>
    </div>
    @include('guesttaclaim::partials.claim-table', ['claims' => $claims, 'actions' => 'guesttaclaim::reports._actions', 'empty' => 'No claims match these filters.'])
</div>
@endsection
