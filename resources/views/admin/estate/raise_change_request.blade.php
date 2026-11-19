@extends('admin.layouts.master')

@section('title', 'Raise Change Request - Sargam')

@section('setup_content')
<div class="container-fluid rfe-page">
    <x-breadcrum title="Raise Change Request" />
    <x-session_message />

    <div class="card overflow-hidden rounded-3 ds-form-fields">
        <div class="card-body p-3 p-md-4">
            <div class="mb-4">
                <h1 class="h5 fw-bold mb-1">Change Request</h1>
                <p class="text-body-secondary small mb-0">Employee must already have a house allotted. Select the new (vacant) house and submit.</p>
            </div>

            {{-- Same partial the Change Request modal renders. --}}
            @include('admin.estate._raise_change_request_form', ['inModal' => false])
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
@endpush
