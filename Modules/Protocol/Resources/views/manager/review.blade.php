@extends('admin.layouts.master')

@section('title', 'Final Decision — ' . $protocolRequest->request_number)
@section('page_title', 'Final Decision — ' . $protocolRequest->request_number)
@section('page_subtitle', 'Recommended by ' . ($protocolRequest->protocolStaff->name ?? '—'))

@section('content')
@include('protocol::partials.style')
  <div class="container-fluid profile-page">
    <x-breadcrum
        title="Final Decision"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            ['label' => 'Recommended to Me', 'url' => route('protocol.manager.queue')],
            $protocolRequest->request_number,
        ]"
    />

  <div class="card-clean p-4">

    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <div class="text-muted small">Raised by</div>
        <div class="fw-bold">{{ $protocolRequest->employee->name ?? '—' }}</div>
        <div class="text-muted small">{{ $protocolRequest->employee_department }} &middot; {{ $protocolRequest->created_at->format('d M Y') }}</div>
      </div>
      <x-protocol::status-badge :status="$protocolRequest->status" />
    </div>

    @include('protocol::partials.request-detail')

    <div class="fw-bold small text-uppercase text-muted mb-2" style="letter-spacing:.05em;">Approval Log</div>
    <x-protocol::timeline :logs="$protocolRequest->logs" />
    <hr class="my-4">

    <form method="POST" action="{{ route('protocol.manager.decide', $protocolRequest) }}" id="managerDecisionForm">
      @csrf
      <input type="hidden" name="decision" id="managerDecisionField" value="approve">

      <div class="mb-3">
        <label class="form-label">Remarks</label>
        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional note for the record"></textarea>
      </div>

      <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('protocol.manager.queue') }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-outline-danger" onclick="document.getElementById('managerDecisionField').value='reject'">
          <i class="bi bi-x-lg me-1"></i> Reject
        </button>
        <button type="submit" class="btn btn-green" onclick="document.getElementById('managerDecisionField').value='approve'">
          <i class="bi bi-check-lg me-1"></i> Approve
        </button>
      </div>
    </form>

  </div>
  </div>

@endsection
