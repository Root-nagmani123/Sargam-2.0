@extends('admin.layouts.master')
@section('title', 'Duplicate Vehicle Pass Request - Sargam')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Duplicate Vehicle Pass Request"></x-breadcrum>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
                <h5 class="mb-0">Request #{{ $req->vehicle_tw_pk }}</h5>
                <span class="badge {{ $req->status_text === 'Approved' ? 'bg-success' : ($req->status_text === 'Rejected' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ $req->status_text }}</span>
            </div>
            <div class="row g-3">
                <div class="col-md-6"><strong>Vehicle Number:</strong> {{ $req->vehicle_no ?? '--' }}</div>
                <div class="col-md-6"><strong>Vehicle Pass No:</strong> {{ $req->vehicle_pass_no ?? '--' }}</div>
                <div class="col-md-6"><strong>Id Card Number:</strong> {{ $req->employee_id_card ?? '--' }}</div>
                <div class="col-md-6"><strong>Employee Name:</strong> {{ $req->employee_name ?? '--' }}</div>
                <div class="col-md-6"><strong>Designation:</strong> {{ $req->designation ?? '--' }}</div>
                <div class="col-md-6"><strong>Department:</strong> {{ $req->department ?? '--' }}</div>
                <div class="col-md-6"><strong>Vehicle Type:</strong> {{ $req->vehicleType->vehicle_type ?? '--' }}</div>
                <div class="col-md-6"><strong>Start Date:</strong> {{ $req->veh_card_valid_from ? $req->veh_card_valid_from->format('d-m-Y') : '--' }}</div>
                <div class="col-md-6"><strong>End Date:</strong> {{ $req->vech_card_valid_to ? $req->vech_card_valid_to->format('d-m-Y') : '--' }}</div>
                <div class="col-md-6"><strong>Reason For Duplicate:</strong> {{ $req->reason_for_duplicate ?? '--' }}</div>
                <div class="col-md-6"><strong>Request Date:</strong> {{ $req->created_date ? $req->created_date->format('d-m-Y H:i') : '--' }}</div>
                <div class="col-12">
                    <strong>Uploaded Document:</strong>
                    @if($req->doc_upload)
                        <a href="{{ asset('storage/' . $req->doc_upload) }}" target="_blank" class="ms-2">Download</a>
                    @else
                        <span class="text-muted">--</span>
                    @endif
                </div>
                @php $latestApproval = $req->approvals()->latest('created_date')->first(); @endphp
                @if($latestApproval?->veh_approval_remarks)
                    <div class="col-12"><strong>Remarks:</strong> {{ $latestApproval->veh_approval_remarks }}</div>
                @endif
            </div>
            <div class="d-flex gap-2 mt-4 no-print">
                @if((int)$req->vech_card_status === 1)
                    <a href="{{ route('admin.security.duplicate_vehicle_pass.edit', encrypt($req->vehicle_tw_pk)) }}" class="btn btn-primary">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">edit</i>
                        Edit
                    </a>
                @endif
                <button type="button" class="btn btn-info" onclick="window.print()">
                    <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">print</i>
                    Print
                </button>
                <a href="{{ route('admin.security.duplicate_vehicle_pass.index') }}" class="btn btn-outline-secondary">
                    <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">arrow_back</i>
                    Back to List
                </a>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    @media print {
        /* Hide navigation, sidebar, and action buttons */
        .no-print,
        .sidebar-nav,
        .topbar,
        .page-breadcrumb,
        .breadcrumb,
        header,
        footer,
        .btn,
        button {
            display: none !important;
        }
        
        /* Full width for print */
        .page-wrapper {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .container-fluid {
            width: 100% !important;
            max-width: 100% !important;
            padding: 20px !important;
        }
        
        /* Card styling for print */
        .card {
            border: 2px solid #004a93 !important;
            box-shadow: none !important;
            page-break-inside: avoid;
        }
        
        .card-body {
            padding: 20px !important;
        }
        
        /* Make text darker for print */
        body {
            color: #000 !important;
            background: #fff !important;
        }
        
        /* Status badge print styling */
        .badge {
            border: 1px solid #000 !important;
            padding: 4px 8px !important;
        }
        
        /* Add page header */
        @page {
            margin: 1cm;
        }
        
        h5 {
            color: #004a93 !important;
            font-size: 18px !important;
            font-weight: bold !important;
        }
        
        strong {
            font-weight: 600 !important;
        }
    }
</style>
@endpush

@endsection
