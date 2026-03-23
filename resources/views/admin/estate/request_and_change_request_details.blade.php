@extends('admin.layouts.master')

@section('title', 'Request for House & Change Request Details - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Request for House & Change Request Details" />
    <x-estate-workflow-stepper current="request-for-estate" />
    <x-session_message />

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <h1 class="h4 fw-bold text-dark mb-0">Request for House & Change Request Details</h1>
        @php
            $isHacPersonOnly = hasRole('HAC Person') && !hasRole('Estate') && !hasRole('Admin') && !hasRole('Training-Induction') && !hasRole('Training-MCTP') && !hasRole('IST') && !hasRole('Staff') && !hasRole('Student-OT') && !hasRole('Doctor') && !hasRole('Guest Faculty') && !hasRole('Internal Faculty');
        @endphp
        <a href="{{ $isHacPersonOnly ? route('admin.estate.change-request-hac-approved') : route('admin.estate.request-for-estate') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> {{ $isHacPersonOnly ? 'Back to HAC Approved' : 'Back to Request For Estate' }}
        </a>
    </div>

    {{-- Section 1: Request for House (estate_home_request_details) --}}
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-primary bg-opacity-10 border-0 py-3">
            <h2 class="h5 fw-bold mb-0 text-body">
                <i class="bi bi-house-door me-2"></i> Request for House
            </h2>
        </div>
        <div class="card-body p-4">
            <p class="text-body-secondary small mb-4">Original house request details.</p>
            <div class="row g-3">
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Request ID</label>
                    <p class="mb-0 fw-medium">{{ $requestForHouse->req_id }}</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Request Date</label>
                    <p class="mb-0 fw-medium">{{ $requestForHouse->req_date }}</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Employee Name</label>
                    <p class="mb-0 fw-medium">{{ $requestForHouse->emp_name }}</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Employee ID</label>
                    <p class="mb-0 fw-medium">{{ $requestForHouse->employee_id }}</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Designation</label>
                    <p class="mb-0 fw-medium">{{ $requestForHouse->emp_designation }}</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Pay Scale</label>
                    <p class="mb-0 fw-medium">{{ $requestForHouse->pay_scale }}</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">DOJ (Pay Scale)</label>
                    <p class="mb-0 fw-medium">{{ $requestForHouse->doj_pay_scale }}</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">DOJ (Academy)</label>
                    <p class="mb-0 fw-medium">{{ $requestForHouse->doj_academic }}</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">DOJ (Service)</label>
                    <p class="mb-0 fw-medium">{{ $requestForHouse->doj_service }}</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Current Allotment</label>
                    <p class="mb-0 fw-medium">{{ $requestForHouse->current_alot }}</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Status of Request</label>
                    <p class="mb-0 fw-medium">{{ $requestForHouse->status }}</p>
                </div>
                <!-- <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">App Status</label>
                    <p class="mb-0 fw-medium">{{ $requestForHouse->app_status }}</p>
                </div> -->
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">HAC Status</label>
                    <p class="mb-0 fw-medium">{{ $requestForHouse->hac_status }}</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Forward Status</label>
                    <p class="mb-0 fw-medium">{{ $requestForHouse->f_status }}</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Change Status</label>
                    <p class="mb-0 fw-medium">{{ $requestForHouse->change_status }}</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Eligibility Type</label>
                    <p class="mb-0 fw-medium">{{ $requestForHouse->eligibility_label }}</p>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Remarks</label>
                    <p class="mb-0 fw-medium">{{ $requestForHouse->remarks }}</p>
                </div>
                @if(!empty($houseDetails))
                <div class="col-12">
                    <hr class="my-4">
                    <h3 class="h6 fw-bold text-dark mb-3">
                        <i class="bi bi-building me-2"></i> House Details
                    </h3>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Campus</label>
                    <p class="mb-0 fw-medium">{{ $houseDetails->campus_name ?? '—' }}</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Block / Building</label>
                    <p class="mb-0 fw-medium">{{ $houseDetails->block_name ?? '—' }}</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Unit Type</label>
                    <p class="mb-0 fw-medium">{{ $houseDetails->unit_type ?? '—' }}</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Unit Sub Type</label>
                    <p class="mb-0 fw-medium">{{ $houseDetails->unit_sub_type ?? '—' }}</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">House / Quarter No.</label>
                    <p class="mb-0 fw-medium">{{ $houseDetails->house_no ?? '—' }}</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Allotment Date</label>
                    <p class="mb-0 fw-medium">{{ $houseDetails->allotment_date ?? '—' }}</p>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Possession Date</label>
                    <p class="mb-0 fw-medium">{{ $houseDetails->possession_date ?? '—' }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Section 2: Change Request Details — hidden for HAC Person only --}}
    @php
        $showChangeRequestCard = ! (hasRole('HAC Person') && ! hasRole('Estate') && ! hasRole('Admin') && ! hasRole('Training-Induction') && ! hasRole('Training-MCTP') && ! hasRole('IST') && ! hasRole('Staff') && ! hasRole('Student-OT') && ! hasRole('Doctor') && ! hasRole('Guest Faculty') && ! hasRole('Internal Faculty'));
    @endphp
    @if($showChangeRequestCard)
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-info bg-opacity-10 border-0 py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h2 class="h5 fw-bold mb-0 text-body">
                <i class="bi bi-arrow-left-right me-2"></i> Change Request Details
            </h2>
            @php
                $hasCurrentAlot = trim((string) ($requestForHouse->current_alot ?? '')) !== '' && (string) ($requestForHouse->current_alot ?? '') !== '—';
                $hasAnyChangeRequest = ! $changeRequestDetails->isEmpty();
            @endphp
            @if($hasCurrentAlot && ! $hasAnyChangeRequest && (hasRole('Estate') || hasRole('Admin')))
                <a href="{{ route('admin.estate.raise-change-request', ['id' => $requestForHouse->pk]) }}" class="btn btn-info btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> Raise Change Request
                </a>
            @endif
        </div>
        <div class="card-body p-4">
            @if($changeRequestDetails->isEmpty())
                <p class="text-body-secondary mb-0">No change request has been raised for this house request. Change request is applicable only when the employee already has a house allotted (<code>current alot</code> is set). Use <strong>Raise Change Request</strong> above when the employee has a current allotment. <strong>If you have already returned the house</strong>, you cannot raise a change request for this request.</p>
            @else
                <p class="text-body-secondary small mb-4">Change request(s) linked to this house request from.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-semibold small text-uppercase">Change Req. ID</th>
                                <th class="fw-semibold small text-uppercase">Requested House</th>
                                <th class="fw-semibold small text-uppercase">Change Req. Date</th>
                                <th class="fw-semibold small text-uppercase">Campus</th>
                                <th class="fw-semibold small text-uppercase">Block</th>
                                <th class="fw-semibold small text-uppercase">Unit Type</th>
                                <th class="fw-semibold small text-uppercase">Unit Sub Type</th>
                                <th class="fw-semibold small text-uppercase">Status</th>
                                <th class="fw-semibold small text-uppercase">Remarks</th>
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
                                            $statusClass = match($chg->change_ap_dis_status) {
                                                1 => 'success',
                                                2 => 'danger',
                                                default => 'warning',
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">{{ $chg->change_ap_dis_status_label }}</span>
                                    </td>
                                    <td class="small">{{ \Illuminate\Support\Str::limit($chg->remarks, 40) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
