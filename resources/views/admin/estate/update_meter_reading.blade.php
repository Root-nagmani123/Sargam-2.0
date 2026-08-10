@extends('admin.layouts.master')

@section('title', 'Update Meter Reading - Sargam')

@php
    $estateSelfHomeTab = request('scope') === 'self'
        && (isEstateAuthority());

    $meterReadingPageFlashParts = [];
    if (session('error')) {
        $meterReadingPageFlashParts[] = trim((string) session('error'));
    }
    if ($errors->any()) {
        foreach ($errors->all() as $err) {
            $t = trim((string) $err);
            if ($t !== '' && ! in_array($t, $meterReadingPageFlashParts, true)) {
                $meterReadingPageFlashParts[] = $t;
            }
        }
    }
    $meterReadingPageAlertMessage = ! empty($meterReadingPageFlashParts)
        ? implode("\n\n", $meterReadingPageFlashParts)
        : null;
@endphp
@section($estateSelfHomeTab ? 'content' : 'setup_content')
<div class="container-fluid rfe-page umr-page">
    <x-breadcrum title="Update Meter Reading" :showBack="false" />

    <x-session_message />

    @if($meterReadingPageAlertMessage !== null)
        <div class="alert alert-danger alert-dismissible fade show rounded-1" role="alert">
            <span class="flex-grow-1">{!! nl2br(e($meterReadingPageAlertMessage)) !!}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card overflow-hidden rounded-1 ds-form-fields">
        <div class="card-body p-3 p-md-4">
            <div class="mb-4">
                <h1 class="h5 fw-bold mb-1">Update Meter Reading</h1>
                <p class="text-body-secondary small mb-0">Pick the month and location, then enter this month's readings.</p>
            </div>

            {{-- Same partial the Update Meter Reading modal renders. --}}
            @include('admin.estate._update_meter_reading_form', ['inModal' => false])
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/select2-theme.css') }}">
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
@endpush
