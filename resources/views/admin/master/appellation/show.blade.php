@extends('admin.layouts.master')

@section('title', 'View Appellation Master')

@section('setup_content')

<style>
/* Global Styles */
.page-wrapper {
    background: #fff;
    padding: 20px 40px;
    font-family: "Noto Sans", "Noto Sans Devanagari", Arial, sans-serif;
}

.section-title {
    font-weight: 600;
    margin-bottom: 12px;
    color: #003366;
    font-size: 18px;
    border-bottom: 2px solid #003366;
    padding-bottom: 4px;
}

.label-sm {
    font-size: 14px;
    font-weight: 500;
    color: #333;
}

.data-line {
    border-bottom: 1px solid #bbb;
    min-height: 26px;
    padding-bottom: 2px;
    font-size: 15px;
    padding-top: 4px;
}

.section-block {
    margin-bottom: 28px;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 4px;
    font-weight: 500;
    font-size: 13px;
}

.status-badge.active {
    background-color: #d4edda;
    color: #155724;
}

.status-badge.inactive {
    background-color: #f8d7da;
    color: #721c24;
}

/* Print Optimization */
@media print {
    body * {
        visibility: hidden !important;
    }
    .print-area, .print-area * {
        visibility: visible !important;
    }
    .print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 0 30px;
    }
    .no-print {
        display: none !important;
    }
}
</style>

<div class="container-fluid print-area">
    <!-- Header -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-12">
            <h3 class="mb-0">Appellation Master Details</h3>
            <small class="text-muted">View and manage appellation information</small>
        </div>
    </div>

    <!-- Main Information -->
    <div class="section-block">
        <div class="section-title">APPELLATION INFORMATION</div>

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="label-sm">Appellation Name</div>
                <div class="data-line">{{ $appellationMaster->appettation_name }}</div>
            </div>

            <div class="col-md-6">
                <div class="label-sm">Status</div>
                <div class="data-line">
                    <span class="status-badge {{ $appellationMaster->active_inactive == 1 ? 'active' : 'inactive' }}">
                        {{ $appellationMaster->active_inactive == 1 ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Timestamps -->
    <div class="section-block">
        <div class="section-title">RECORD INFORMATION</div>

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="label-sm">Created Date</div>
                <div class="data-line">{{ format_date($appellationMaster->created_date) }}</div>
            </div>

            <div class="col-md-6">
                <div class="label-sm">Last Modified Date</div>
                <div class="data-line">{{ format_date($appellationMaster->modified_date) }}</div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="no-print text-end mt-4">
        <a href="{{ route('master.appellation.master.index') }}" class="btn btn-secondary">
            <i class="material-icons" style="vertical-align: middle; margin-right: 5px;">arrow_back</i>
            Back
        </a>
        <a href="{{ route('master.appellation.master.edit', ['id' => encrypt($appellationMaster->pk)]) }}" class="btn btn-primary">
            <i class="material-icons" style="vertical-align: middle; margin-right: 5px;">edit</i>
            Edit
        </a>
        <button class="btn btn-info" onclick="window.print()">
            <i class="material-icons" style="vertical-align: middle; margin-right: 5px;">print</i>
            Print
        </button>
    </div>
</div>

@endsection
