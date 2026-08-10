@extends('admin.layouts.master')

@section('title', 'View Request - Sargam')

@php
    // HAC Person (and nothing else) lands here from the HAC Approved list and
    // must not see / raise change requests.
    $isHacPersonOnly = hasRole('HAC Person')
        && ! hasRole('Estate Admin') && ! hasRole('Super Admin')
        && ! hasRole('Training Induction Admin') && ! hasRole('Training MCTP Admin')
        && ! hasRole('Training IST') && ! hasRole('Staff') && ! hasRole('Officer Trainee')
        && ! hasRole('Doctor') && ! hasRole('Guest Faculty') && ! hasRole('Internal Faculty');

    $backUrl = $isHacPersonOnly
        ? route('admin.estate.change-request-hac-approved')
        : route('admin.estate.request-for-estate');

    $currentAlot = trim((string) ($requestForHouse->current_alot ?? ''));
    $hasCurrentAlot = $currentAlot !== '' && $currentAlot !== '—';

    // "Request for Change" is only offered while there is no change request
    // pending on this house request — same rule the controller enforces.
    $hasPendingChange = $changeRequestDetails->contains(fn ($c) => (int) ($c->change_ap_dis_status ?? 0) === 0);

    // The controller fills missing values with an em dash; the design uses a
    // plain hyphen, so normalise both (and empty) to one placeholder.
    $show = fn ($value) => (blank($value) || trim((string) $value) === '—') ? '-' : $value;
    $canRaiseChange = ! $isHacPersonOnly && $hasCurrentAlot && ! $hasPendingChange;
@endphp

@section('setup_content')
<div class="container-fluid rfe-page rfe-view-page">
    <x-breadcrum title="View Request" :showBack="true" />

    <x-session_message />

    {{-- Identity banner: the one thing you need to know you're on the right record. --}}
    <div class="rfe-view-banner mb-3">
        <h2 class="rfe-view-banner-title">Request ID #{{ $requestForHouse->req_id }}</h2>
        <div class="rfe-view-banner-meta">
            <div>
                <span class="rfe-view-label">Request Date</span>
                <span class="rfe-view-value">{{ $requestForHouse->req_date }}</span>
            </div>
            <div>
                <span class="rfe-view-label">Employee Name</span>
                <span class="rfe-view-value">{{ $requestForHouse->emp_name }}</span>
            </div>
        </div>
    </div>

    {{-- Request for House (estate_home_request_details) --}}
    <div class="card rounded-1 mb-3">
        <div class="card-body p-3 p-md-4">
            <div class="rfe-view-grid">
                @foreach ([
                    'Request ID' => $requestForHouse->req_id,
                    'Request Date' => $requestForHouse->req_date,
                    'Employee Name' => $requestForHouse->emp_name,
                    'Employee ID' => $requestForHouse->employee_id,
                    'Designation' => $requestForHouse->emp_designation,
                    'Pay Scale' => $requestForHouse->pay_scale,
                    'DOJ (Pay Scale)' => $requestForHouse->doj_pay_scale,
                    'DOJ (Academy)' => $requestForHouse->doj_academic,
                    'DOJ (Service)' => $requestForHouse->doj_service,
                    'Current Allotment' => $requestForHouse->current_alot,
                    'Status of Request' => $requestForHouse->status,
                    'HAC Status' => $requestForHouse->hac_status,
                    'Forward Status' => $requestForHouse->f_status,
                    'Change Status' => $requestForHouse->change_status,
                    'Eligibility Type' => $requestForHouse->eligibility_label,
                    'Remarks' => $requestForHouse->remarks,
                ] as $label => $value)
                    <div class="rfe-view-field">
                        <span class="rfe-view-label">{{ $label }}</span>
                        <span class="rfe-view-value">{{ $show($value) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Location Details — only once a possession exists. --}}
    @if(!empty($houseDetails))
    <div class="card rounded-1 mb-3">
        <div class="card-body p-3 p-md-4">
            <h3 class="rfe-view-section-title">Location Details</h3>
            <div class="rfe-view-grid">
                @foreach ([
                    'Campus' => $houseDetails->campus_name,
                    'Block / Building' => $houseDetails->block_name,
                    'Unit Type' => $houseDetails->unit_type,
                    'Unit Sub Type' => $houseDetails->unit_sub_type,
                    'House / Quarter No' => $houseDetails->house_no,
                    'Allotment Date' => $houseDetails->allotment_date,
                    'Possession Date' => $houseDetails->possession_date,
                ] as $label => $value)
                    <div class="rfe-view-field">
                        <span class="rfe-view-label">{{ $label }}</span>
                        <span class="rfe-view-value">{{ $show($value) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Change Request Details --}}
    @unless($isHacPersonOnly)
    <div class="card rounded-1">
        <div class="card-body p-3 p-md-4">
            <h3 class="rfe-view-section-title">Change Request Details</h3>

            @if($changeRequestDetails->isEmpty())
                <div class="rfe-view-empty">
                    <p class="rfe-view-empty-text">
                        @if($canRaiseChange)
                            No data to show here, You haven't request for change yet.
                        @elseif(! $hasCurrentAlot)
                            No data to show here. A change request can only be raised once a house is allotted.
                        @else
                            No data to show here.
                        @endif
                    </p>
                    @if($canRaiseChange)
                        <a href="{{ route('admin.estate.raise-change-request', ['id' => $requestForHouse->pk]) }}"
                            class="btn ds-btn-submit btn-raise-change-request" data-id="{{ $requestForHouse->pk }}">
                            Request for Change
                        </a>
                    @endif
                </div>
            @else
                <div class="programme-dt-panel mt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                            <thead>
                                <tr>
                                    <th>Change Req. ID</th>
                                    <th>Requested House</th>
                                    <th>Change Req. Date</th>
                                    <th>Campus</th>
                                    <th>Block</th>
                                    <th>Unit Type</th>
                                    <th>Unit Sub Type</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($changeRequestDetails as $chg)
                                    <tr>
                                        <td>{{ $chg->estate_change_req_ID }}</td>
                                        <td>{{ $chg->change_house_no }}</td>
                                        <td>{{ $chg->change_req_date }}</td>
                                        <td>{{ $chg->campus_name }}</td>
                                        <td>{{ $chg->block_name }}</td>
                                        <td>{{ $chg->unit_type }}</td>
                                        <td>{{ $chg->unit_sub_type }}</td>
                                        <td>
                                            @php
                                                $tone = match ((int) $chg->change_ap_dis_status) {
                                                    1 => 'allotted',
                                                    2 => 'rejected',
                                                    default => 'pending',
                                                };
                                            @endphp
                                            <span class="badge rounded-1 programme-status-badge rfe-status rfe-status--{{ $tone }}">
                                                {{ $chg->change_ap_dis_status_label }}
                                            </span>
                                        </td>
                                        <td>{{ $show(\Illuminate\Support\Str::limit($chg->remarks, 40)) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($canRaiseChange)
                    <div class="d-flex justify-content-end mt-3">
                        <a href="{{ route('admin.estate.raise-change-request', ['id' => $requestForHouse->pk]) }}"
                            class="btn ds-btn-submit btn-raise-change-request" data-id="{{ $requestForHouse->pk }}">
                            Request for Change
                        </a>
                    </div>
                @endif
            @endif
        </div>
    </div>

    @include('admin.estate._raise_change_request_modal')
    @endunless
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
@endpush

@push('scripts')
<script>
$(function() {
    // A raised change request changes this page's data, so reload it.
    document.addEventListener('rfe:change-request-created', function() {
        window.location.reload();
    });
    document.addEventListener('rfe:change-request-error', function(e) {
        alert(e.detail.message);
    });
});
</script>
@endpush
