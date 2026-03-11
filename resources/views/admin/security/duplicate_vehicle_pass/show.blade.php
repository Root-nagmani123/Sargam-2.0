@extends('admin.layouts.master')
@section('title', 'Duplicate Vehicle Pass Request - Sargam')
@section('setup_content')
<div class="container-fluid duplicate-vehicle-pass-print-area" id="duplicateVehiclePassPrint">
    <div class="no-print mb-3">
        <x-breadcrum title="Duplicate Vehicle Pass Request"></x-breadcrum>
    </div>

    <div class="card border-0 shadow-sm mb-4 rounded-3" id="duplicateVehiclePassCard">
        <div class="card-body p-3 p-lg-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
                <div>
                    <h5 class="mb-1 fw-semibold">Request #{{ $req->vehicle_tw_pk }}</h5>
                    <p class="text-muted small mb-0">Detailed information for this duplicate vehicle pass request.</p>
                </div>
                <span class="badge px-3 py-2 {{ $req->status_text === 'Approved' ? 'bg-success' : ($req->status_text === 'Rejected' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ $req->status_text }}</span>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Vehicle Number</div>
                    <div class="fw-semibold">{{ $req->vehicle_no ?? '--' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Vehicle Pass No</div>
                    <div class="fw-semibold">{{ $req->vehicle_pass_no ?? '--' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">ID Card Number</div>
                    <div class="fw-semibold">{{ $req->employee_id_card ?? '--' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Employee Name</div>
                    <div class="fw-semibold">{{ $req->employee_name ?? '--' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Designation</div>
                    <div class="fw-semibold">{{ $req->designation ?? '--' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Department</div>
                    <div class="fw-semibold">{{ $req->department ?? '--' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Vehicle Type</div>
                    <div class="fw-semibold">{{ $req->vehicleType->vehicle_type ?? '--' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Start Date</div>
                    <div class="fw-semibold">{{ $req->veh_card_valid_from ? $req->veh_card_valid_from->format('d-m-Y') : '--' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">End Date</div>
                    <div class="fw-semibold">{{ $req->vech_card_valid_to ? $req->vech_card_valid_to->format('d-m-Y') : '--' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Reason For Duplicate</div>
                    <div class="fw-semibold">{{ $req->reason_for_duplicate ?? '--' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small mb-1">Request Date</div>
                    <div class="fw-semibold">{{ $req->created_date ? $req->created_date->format('d-m-Y H:i') : '--' }}</div>
                </div>
                <div class="col-12">
                    <div class="text-muted small mb-1">Uploaded Document</div>
                    @if($req->doc_upload)
                        <a href="{{ asset('storage/' . $req->doc_upload) }}" target="_blank" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">description</i>
                            <span>View / Download</span>
                        </a>
                    @else
                        <span class="text-muted">--</span>
                    @endif
                </div>
                @php $latestApproval = $req->approvals()->latest('created_date')->first(); @endphp
                @if($latestApproval?->veh_approval_remarks)
                    <div class="col-12">
                        <div class="text-muted small mb-1">Remarks</div>
                        <div class="fw-semibold">{{ $latestApproval->veh_approval_remarks }}</div>
                    </div>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2 mt-4 no-print">
                @if((int)$req->vech_card_status === 1)
                    <a href="{{ route('admin.security.duplicate_vehicle_pass.edit', encrypt($req->vehicle_tw_pk)) }}" class="btn btn-primary d-inline-flex align-items-center gap-1">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">edit</i>
                        Edit
                    </a>
                @endif
                <button type="button" class="btn btn-info d-inline-flex align-items-center gap-1" onclick="printDuplicateVehiclePass()">
                    <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">print</i>
                    Print
                </button>
                <a href="{{ route('admin.security.duplicate_vehicle_pass.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                    <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">arrow_back</i>
                    Back to List
                </a>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .print-only-header-lbsnaa {
        display: none;
    }

    @media print {
        /* Show only this print area (card content + LBSNAA header) */
        body * {
            visibility: hidden !important;
        }

        .duplicate-vehicle-pass-print-area,
        .duplicate-vehicle-pass-print-area * {
            visibility: visible !important;
        }

        .duplicate-vehicle-pass-print-area .no-print,
        .duplicate-vehicle-pass-print-area .no-print * {
            visibility: hidden !important;
            display: none !important;
        }

        .duplicate-vehicle-pass-print-area {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 20px !important;
            background: #fff !important;
        }

        .print-only-header-lbsnaa {
            display: block !important;
        }

        .duplicate-vehicle-pass-print-area .card {
            border: 1px solid #333 !important;
            box-shadow: none !important;
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
        var card = document.getElementById('duplicateVehiclePassCard');
        if (!card) {
            window.print();
            return;
        }

        var printWindow = window.open('', '_blank', 'width=900,height=650');
        printWindow.document.open();
        printWindow.document.write(`<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Duplicate Vehicle Pass</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            margin: 20px;
            background: #fff;
        }
        .print-header {
            text-align: center;
            margin-bottom: 16px;
        }
        .print-header-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin-bottom: 4px;
        }
        .print-header-logos img {
            height: 60px;
            object-fit: contain;
        }
        .print-header-title {
            font-weight: 600;
            margin: 0;
        }
        .print-header-subtitle {
            margin: 0;
            font-size: 0.8rem;
            color: #555;
        }
        .card {
            border: 1px solid #333;
            border-radius: 6px;
            padding: 16px 18px;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -8px;
        }
        .col-md-6 {
            box-sizing: border-box;
            padding: 4px 8px;
            width: 50%;
        }
        .col-12 {
            box-sizing: border-box;
            padding: 4px 8px;
            width: 100%;
        }
        .text-muted {
            color: #6c757d;
        }
        .small {
            font-size: 0.8rem;
        }
        .fw-semibold {
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            border: 1px solid #333;
        }
        h5 {
            margin: 0 0 8px 0;
        }
        @page {
            margin: 1cm;
        }
    </style>
</head>
<body>
    <div class="print-header">
        <div class="print-header-logos">
            <img src="https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg" alt="Ashoka Emblem">
            <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="LBSNAA Logo">
        </div>
        <p class="print-header-title">Lal Bahadur Shastri National Academy of Administration</p>
        <p class="print-header-subtitle">Duplicate Vehicle Pass Request Details</p>
    </div>`);
        printWindow.document.write(card.outerHTML);
        printWindow.document.write(`
</body>
</html>`);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }
</script>
@endpush

@endsection
