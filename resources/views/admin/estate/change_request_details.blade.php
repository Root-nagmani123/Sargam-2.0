@extends('admin.layouts.master')

@section('title', 'Change Request Details - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Change Request Details" />
    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-body p-4 p-lg-5">
            <h1 class="h4 fw-bold text-body mb-1">Change Request Details</h1>
            <p class="text-body-secondary small mb-4">Please add change Details.</p>

            @include('admin.estate._change_request_details_form', ['detail' => $detail ?? null, 'inModal' => false, 'formAction' => '#'])
        </div>
    </div>
</div>
@endsection
