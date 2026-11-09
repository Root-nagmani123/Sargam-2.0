@extends('admin.layouts.master')

@section('title', 'Review ' . $protocolRequest->request_number)
@section('page_title', 'Review ' . $protocolRequest->request_number)
@section('page_subtitle', $protocolRequest->typeLabel() . ' request from ' . ($protocolRequest->employee->name ?? '—'))

@section('content')
@include('protocol::partials.style')
  <div class="container-fluid protocol-page">
    <x-breadcrum
        title="Review Request"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            ['label' => 'Approval Queue', 'url' => route('protocol.approval.queue')],
            $protocolRequest->request_number,
        ]"
    />

    @include('protocol::partials.request-hero', [
        'raisedBy' => $protocolRequest->employee->name ?? null,
        'department' => $protocolRequest->employeeDepartment->department_name ?? null,
    ])

    @include('protocol::partials.request-detail')

    @if ($protocolRequest->logs->isNotEmpty())
      <div class="pr-card mb-3">
        <div class="pr-card__body">
          <h3 class="pr-card__title">Approval Log</h3>
          <x-protocol::timeline :logs="$protocolRequest->logs" />
        </div>
      </div>
    @endif

    {{-- Protocol Staff decide only on pending requests; later stages are read-only here --}}
    @if ($protocolRequest->status !== \Modules\Protocol\Entities\ProtocolRequest::STATUS_PENDING)
    <div class="alert-hint">
      <i class="bi bi-info-circle me-1"></i> This request is no longer awaiting Protocol Staff review ({{ $protocolRequest->current_stage }}).
    </div>
    @else
    <div class="pr-card">
      <div class="pr-card__body">
        <h3 class="pr-card__title">Decision</h3>
        <form method="POST" action="{{ route('protocol.approval.decide', $protocolRequest) }}" id="decisionForm">
          @csrf
          <input type="hidden" name="decision" id="decisionField" value="approve">

          <div class="row g-3">
            @if ($protocolRequest->request_type === 'guesthouse')
              <div class="col-md-4">
                <label class="form-label" for="assignedGuestHouse">Assign Guest House</label>
                <select name="assigned_guest_house" id="assignedGuestHouse" class="form-select" data-searchable="true" data-placeholder="Select guest house" data-allow-clear="true">
                  <option value="">-- select --</option>
                  @foreach ($GuestHouse as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                  @endforeach
                </select>
                <div class="form-text">Optional — you can assign the physical guest house now or leave it for later.</div>
              </div>
            @endif

            <div class="col-12">
              <label class="form-label" for="decisionRemarks">Remarks</label>
              <textarea name="remarks" id="decisionRemarks" class="form-control" rows="2" placeholder="Optional note for the record"></textarea>
            </div>
          </div>

          <div class="pr-form-footer">
            <a href="{{ route('protocol.requests.all') }}" class="btn pr-btn-cancel">Cancel</a>
            @if ($protocolRequest->status != 'recommended')
            <button type="button" class="btn btn-outline-saffron" data-bs-toggle="modal" data-bs-target="#recommendModal">
              <i class="bi bi-arrow-right-circle me-1"></i> Recommend
            </button>
            @endif
            <button type="submit" class="btn btn-green" onclick="document.getElementById('decisionField').value='approve'">
              <i class="bi bi-check-lg me-1"></i> Approve
            </button>
          </div>
        </form>
      </div>
    </div>
    @endif
  </div>

  <!-- Recommend Modal -->
  <div class="modal fade" id="recommendModal" tabindex="-1" aria-labelledby="recommendModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content protocol-modal">
        <div class="modal-header">
          <h5 class="modal-title" id="recommendModalLabel">Recommend Request</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <form method="POST" action="{{ route('protocol.approval.decide', $protocolRequest) }}">
          @csrf
          <input type="hidden" name="decision" value="recommend">

          <div class="modal-body">
            <div class="pr-field-card">
              <div class="mb-3">
                <label class="form-label" for="recommendedTo">Recommend To <span class="required-star">*</span></label>
                <select name="recommended_to_id" id="recommendedTo" class="form-select" data-searchable="true" data-placeholder="Select manager" required>
                  <option value="">-- Select Manager --</option>
                  @if ($managers)
                    @foreach ($managers as $manager)
                      <option value="{{ $manager->user_credentials_pk }}">{{ $manager->employee_name }}</option>
                    @endforeach
                  @endif
                </select>
              </div>

              <div>
                <label class="form-label" for="recommendRemarks">Remarks</label>
                <textarea name="remarks" id="recommendRemarks" class="form-control" rows="4" placeholder="Enter recommendation remarks..."></textarea>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn pr-btn-cancel" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn pr-btn-submit">
              <i class="bi bi-send me-1"></i> Submit Recommendation
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
