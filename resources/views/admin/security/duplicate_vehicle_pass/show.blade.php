@extends('admin.layouts.master')
@section('title', 'Duplicate Vehicle Pass Request - Sargam')
@section('setup_content')
<div class="container-fluid" id="duplicateVehiclePassPrint">
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
                <button type="button" class="btn btn-info" onclick="printDuplicateVehiclePass()">
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
        /* Hide navigation and action buttons */
        .no-print,
        .sidebar-nav,
        .topbar,
        .page-breadcrumb,
        .breadcrumb,
        .navbar,
        header,
        footer,
        .navbar-toggler,
        nav {
            display: none !important;
        }

        /* Use full width for the main print container */
        #duplicateVehiclePassPrint {
            width: 100% !important;
            margin: 0 !important;
            padding: 20px !important;
        }

        @page {
            margin: 1cm;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function printDuplicateVehiclePass() {
        var content = document.getElementById('duplicateVehiclePassPrint');
        if (!content) {
            window.print();
            return;
        }

        var printWindow = window.open('', '_blank', 'width=800,height=600');
        printWindow.document.open();
        printWindow.document.write(`<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Duplicate Vehicle Pass</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .card { border: 1px solid #000; padding: 16px; }
        .row { display: flex; flex-wrap: wrap; margin-bottom: 8px; }
        .col-md-6, .col-12 { flex: 0 0 50%; max-width: 50%; margin-bottom: 4px; }
        .col-12 { flex-basis: 100%; max-width: 100%; }
        strong { font-weight: 600; }
        .badge { border: 1px solid #000; padding: 4px 8px; display: inline-block; }
        h5 { margin-top: 0; margin-bottom: 12px; }
    </style>
</head>
<body>`);
        printWindow.document.write(content.innerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }
</script>
@endpush

@endsection
