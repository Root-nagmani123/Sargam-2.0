@extends('admin.layouts.master')

@section('title', 'Change Request Details - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Change Request Details" />
    <x-session_message />

    {{-- Create new change request: goes to Raise Change Request form → new row in HAC Approved --}}
    @if(($homeRequestsForNewChange ?? collect())->isNotEmpty())
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden mb-4">
        <div class="card-header bg-info bg-opacity-10 border-0 py-3">
            <h2 class="h5 fw-bold mb-0 text-body">
                <i class="bi bi-plus-circle me-2"></i>Create new change request
            </h2>
            <p class="text-body-secondary small mb-0 mt-1">A new change request will appear in HAC Approved for Approve/Disapprove. Select a house request below and continue.</p>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-8 col-lg-6">
                    <label for="newChangeRequestHomeReq" class="form-label fw-semibold">House request (with current allotment)</label>
                    <select id="newChangeRequestHomeReq" class="form-select">
                        <option value="">— Select —</option>
                        @foreach($homeRequestsForNewChange as $hr)
                            <option value="{{ $hr->pk }}">
                                {{ $hr->req_id ?? 'Req-' . $hr->pk }} — {{ $hr->emp_name ?? '' }} ({{ $hr->employee_id ?? '' }}) — {{ $hr->current_alot ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-4">
                    <a href="#" id="btnContinueToRaiseChangeRequest" class="btn btn-info px-4" style="pointer-events: none;">Continue to Raise Change Request</a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-body p-4 p-lg-5">
            <h1 class="h4 fw-bold text-body mb-1">Change Request Details</h1>
            <p class="text-body-secondary small mb-4">Edit an existing <strong>pending</strong> change request below. Already approved/disapproved requests cannot be edited; use &quot;Create new change request&quot; above for another change.</p>

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

            @if(optional($detail)->change_ap_dis_status === 1)
                <div class="alert alert-success mb-4">This change request is already <strong>approved</strong>. Use &quot;Create new change request&quot; above to request another change.</div>
            @endif
            @if(optional($detail)->change_ap_dis_status === 2)
                <div class="alert alert-warning mb-4">This change request is already <strong>disapproved</strong>. Use &quot;Create new change request&quot; above to request another change.</div>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var sel = document.getElementById('newChangeRequestHomeReq');
    var btn = document.getElementById('btnContinueToRaiseChangeRequest');
    if (!sel || !btn) return;
    var urlTemplate = '{{ route("admin.estate.raise-change-request", ["id" => "__PK__"]) }}';
    sel.addEventListener('change', function() {
        var pk = this.value;
        if (pk) {
            btn.href = urlTemplate.replace('__PK__', pk);
            btn.style.pointerEvents = 'auto';
        } else {
            btn.href = '#';
            btn.style.pointerEvents = 'none';
        }
    });
});
</script>
@endpush

@endsection
