@extends('admin.layouts.master')

@section('title', 'Update Meter Reading of Other')

@section('setup_content')
<div class="container-fluid rfe-page epo-page">
    <x-breadcrum title="Update Meter Reading of Other" :showBack="false" />

    <x-session_message />

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-1" role="alert">
            <span class="flex-grow-1">{!! nl2br(e(implode("\n", $errors->all()))) !!}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card overflow-hidden rounded-1 ds-form-fields">
        <div class="card-body p-3 p-md-4">
            <div class="mb-4">
                <h1 class="h5 fw-bold mb-1">Update Meter Reading</h1>
                <p class="text-body-secondary small mb-0">Pick the change month and the estate filters, then load the allotments to update.</p>
            </div>

            {{-- Same partial the Update Meter Reading modal renders. --}}
            @include('admin.estate._update_meter_reading_of_other_form', ['inModal' => false])
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
@endpush
