@extends('admin.layouts.master')

@section('title', 'Review ' . $protocolRequest->request_number)
@section('page_title', 'Review ' . $protocolRequest->request_number)
@section('page_subtitle', $protocolRequest->typeLabel() . ' request from ' . ($protocolRequest->employee->name ?? '—'))

@section('content')
@include('protocol::partials.style')
  @php
    $isPending = $protocolRequest->status === \Modules\Protocol\Entities\ProtocolRequest::STATUS_PENDING;
    $isRecommendedForCurrentUser = $protocolRequest->status === \Modules\Protocol\Entities\ProtocolRequest::STATUS_RECOMMENDED
        && (int) $protocolRequest->recommended_to_id === (int) Auth::id();
    $canReviewRequest = $isPending || $isRecommendedForCurrentUser;
    $canRecommendRequest = $canReviewRequest;
  @endphp
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

    @if (session('success'))
      <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-end mb-3">
      <a href="{{ route('protocol.approval.review', ['protocolRequest' => $protocolRequest, 'pdf' => 1]) }}"
         class="btn btn-outline-primary"
         target="_blank"
         rel="noopener">
        <i class="bi bi-printer me-1" aria-hidden="true"></i>Download / Print PDF
      </a>
    </div>

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

    @if ($protocolRequest->status === \Modules\Protocol\Entities\ProtocolRequest::STATUS_APPROVED)
      <div class="alert-hint">
        <i class="bi bi-info-circle me-1"></i> This request is approved and can no longer be recommended.
      </div>
      @if (in_array($protocolRequest->request_type, ['vehicle', 'guesthouse'], true))
        @role(config('protocol.roles.protocol_staff'))
          <div class="pr-card mb-3">
            <div class="pr-card__body d-flex justify-content-between align-items-center">
              <div>
                <h3 class="pr-card__title mb-1">Assignment</h3>
                @if($protocolRequest->request_type === 'vehicle')
                  <p class="text-muted mb-0">Assign the driver  for this approved request.</p>
                @elseif($protocolRequest->request_type === 'guesthouse')
                  <p class="text-muted mb-0">Assign the guest house  for this approved request.</p>
                @endif
              </div>
              <button type="button" class="btn btn-green" data-bs-toggle="modal" data-bs-target="#resourceAssignmentModal">
                <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>
                {{ ($protocolRequest->request_type === 'vehicle' && $protocolRequest->vehicleDriver) || ($protocolRequest->request_type === 'guesthouse' && $protocolRequest->requestable?->assigned_guest_house) ? 'Update Assignment' : 'Assign' }}
              </button>
            </div>
          </div>
          @include('protocol::partials.request-assignment-modal', [
            'action' => route('protocol.approval.assign', $protocolRequest),
            'protocolRequest' => $protocolRequest,
            'GuestHouse' => $GuestHouse,
          ])
        @endrole
      @endif
    @elseif ($protocolRequest->status === \Modules\Protocol\Entities\ProtocolRequest::STATUS_REJECTED)
      <div class="alert-hint">
        <i class="bi bi-info-circle me-1"></i> This request was rejected and can no longer be reviewed.
      </div>
    @elseif ($canReviewRequest)
      @if ($isRecommendedForCurrentUser)
        <div class="alert-hint">
          <i class="bi bi-info-circle me-1"></i> This request was recommended to you for a final decision.
        </div>
      @endif
      <div class="pr-card">
        <div class="pr-card__body">
          <h3 class="pr-card__title">{{ $isPending ? 'Decision' : 'Final Decision' }}</h3>
          <form method="POST" action="{{ $isRecommendedForCurrentUser ? route('protocol.manager.decide', $protocolRequest) : route('protocol.approval.decide', $protocolRequest) }}" id="decisionForm">
          @csrf
          <input type="hidden" name="decision" id="decisionField" value="{{ old('decision', 'approve') }}">

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label" for="decisionRemarks">Remarks <span class="text-muted small">(required when rejecting)</span></label>
              <textarea name="remarks" id="decisionRemarks" maxlength="1000"
                class="form-control @error('remarks') is-invalid @enderror" rows="2"
                placeholder="Required for Reject, optional for Approve">{{ old('remarks') }}</textarea>
              <div class="invalid-feedback" id="decisionRemarksError">{{ $errors->first('remarks') ?: 'Remarks are required when rejecting a request.' }}</div>
            </div>
          </div>

          <div class="pr-form-footer">
            <a href="{{ route('protocol.requests.all') }}" class="btn pr-btn-cancel">Cancel</a>
            @if ($canRecommendRequest)
              <button type="button" class="btn btn-outline-saffron" data-bs-toggle="modal" data-bs-target="#recommendModal">
                <i class="bi bi-arrow-right-circle me-1"></i> Recommend
              </button>
            @endif
            <button type="submit" class="btn btn-outline-danger" onclick="document.getElementById('decisionField').value='reject'">
              <i class="bi bi-x-lg me-1"></i> Reject
            </button>
            <button type="submit" class="btn btn-green" onclick="document.getElementById('decisionField').value='approve'">
              <i class="bi bi-check-lg me-1"></i> Approve
            </button>
          </div>
          </form>
        </div>
      </div>
      <script>
        (function () {
          var form = document.getElementById('decisionForm');
          var remarks = document.getElementById('decisionRemarks');
          var error = document.getElementById('decisionRemarksError');

          form.addEventListener('submit', function (event) {
            if (document.getElementById('decisionField').value === 'reject' && remarks.value.trim() === '') {
              event.preventDefault();
              error.textContent = 'Remarks are required when rejecting a request.';
              remarks.classList.add('is-invalid');
              remarks.focus();
            }
          });
          remarks.addEventListener('input', function () {
            if (remarks.value.trim() !== '') remarks.classList.remove('is-invalid');
          });
        })();
      </script>
    @endif
  </div>

  @if ($canRecommendRequest)
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
                      @if($manager->user_credentials_pk != Auth::user()->pk)
                      <option value="{{ $manager->user_credentials_pk }}">{{ $manager->employee_name }}</option>
                      @endif
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
            <a href="{{ route('protocol.requests.all') }}" class="btn pr-btn-cancel">Cancel</a>
            <button type="submit" class="btn pr-btn-submit">
              <i class="bi bi-send me-1"></i> Submit Recommendation
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  @endif
@endsection
