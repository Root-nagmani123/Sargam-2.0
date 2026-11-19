@extends('admin.layouts.master')

@section('title', (isset($record) && $record) ? 'Edit Possession Request' : 'Add Possession Request')

@section('setup_content')
<div class="container-fluid rfe-page epo-page">
    <x-breadcrum :title="(isset($record) && $record) ? 'Edit Possession Request' : 'Add Possession Request'" />
    <x-session_message />

    <div class="card overflow-hidden rounded-1 ds-form-fields">
        <div class="card-body p-3 p-md-4">
            <div class="mb-4">
                <h1 class="h5 fw-bold mb-1">{{ (isset($record) && $record) ? 'Edit Possession Request' : 'Add Possession Request' }}</h1>
                <p class="text-body-secondary small mb-0">Requester list contains estate requests raised for others.</p>
            </div>

            {{-- Same partial the Add Possession modal renders. --}}
            @include('admin.estate._possession_view_form', ['inModal' => false])
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
@endpush
