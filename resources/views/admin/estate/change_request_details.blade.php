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

            @if(($changeRequestOptions ?? collect())->isNotEmpty())
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="changeRequestSelector" class="form-label fw-semibold">Select Change Request</label>
                        <select id="changeRequestSelector" class="form-select" onchange="if(this.value){window.location.href=this.value;}">
                            @foreach($changeRequestOptions as $opt)
                                <option value="{{ route('admin.estate.change-request-details', ['id' => $opt->pk]) }}"
                                    {{ (int) ($selectedChangeRequestId ?? 0) === (int) $opt->pk ? 'selected' : '' }}>
                                    {{ $opt->estate_change_req_ID ?: ('Chg-Req-' . $opt->pk) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            @include('admin.estate._change_request_details_form', [
                'detail' => $detail ?? null,
                'inModal' => false,
                'formAction' => $formAction ?? '#',
                'estateCampuses' => $estateCampuses ?? collect(),
                'unitTypes' => $unitTypes ?? collect(),
                'buildings' => $buildings ?? collect(),
                'unitSubTypes' => $unitSubTypes ?? collect(),
                'houseOptions' => $houseOptions ?? collect(),
            ])
        </div>
    </div>
</div>

@endsection
