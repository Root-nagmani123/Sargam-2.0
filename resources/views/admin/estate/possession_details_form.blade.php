@extends('admin.layouts.master')

@section('title', ($isEdit ?? false) ? 'Edit Possession Details - Sargam' : 'Add Possession Details - Sargam')

@php
    $estateSelfHomeTab = request('scope') === 'self'
        && (isEstateAuthority());
    $estateSelfQuery = $estateSelfHomeTab ? ['scope' => 'self'] : [];
@endphp
@section($estateSelfHomeTab ? 'content' : 'setup_content')
<div class="container-fluid rfe-page pd-page">
    <x-breadcrum :title="($isEdit ?? false) ? 'Edit Possession Details' : 'Add Possession Details'" />
    <x-session_message />

    <div class="card overflow-hidden rounded-1 ds-form-fields">
        <div class="card-body p-3 p-md-4">
            <div class="mb-4">
                <h1 class="h5 fw-bold mb-1">{{ ($isEdit ?? false) ? 'Edit Possession Request' : 'Add Possession Request' }}</h1>
                <p class="text-body-secondary small mb-0">Requester list contains only allotted users (from the HAC Approval flow).</p>
            </div>

            {{-- Same partial the Add Possession modal renders. --}}
            @include('admin.estate._possession_details_form', ['inModal' => false])
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
@endpush
