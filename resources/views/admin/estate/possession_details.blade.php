@extends('admin.layouts.master')

@section('title', 'Possession Details - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Possession Details"></x-breadcrum>
    <x-estate-workflow-stepper current="possession-details" />

    <div class="card border-0 shadow-sm rounded-3 border-start border-4 border-primary">
        <div class="card-body p-4 p-lg-5">
            <h1 class="h4 fw-semibold mb-1">Possession Details</h1>
            <p class="text-muted small mb-4">LBSNAA employee possession records (allotted via HAC Approved flow). This is different from <strong>Estate Possession for Other</strong> which lists possession for non-LBSNAA / other requesters.</p>
            <hr class="my-4">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <a href="{{ route('admin.estate.possession-for-others') }}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2">
                    <i class="bi bi-arrow-right-circle"></i>
                    <span>Go to Estate Possession for Other</span>
                </a>
                <a href="{{ route('admin.estate.change-request-hac-approved') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2">
                    <i class="bi bi-arrow-left-circle"></i>
                    <span>HAC Approved</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
