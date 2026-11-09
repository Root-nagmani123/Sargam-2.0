@extends('admin.layouts.master')

@section('title', 'Final Decision — ' . $protocolRequest->request_number)
@section('page_title', 'Final Decision — ' . $protocolRequest->request_number)
@section('page_subtitle', 'Recommended by ' . ($protocolRequest->protocolStaff->name ?? '—'))

@section('content')
@include('protocol::partials.style')
  <div class="container-fluid protocol-page">
    <x-breadcrum
        title="Final Decision"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            ['label' => 'Recommended to Me', 'url' => route('protocol.manager.queue')],
            $protocolRequest->request_number,
        ]"
    />

    @include('protocol::partials.request-hero', [
        'raisedBy' => $protocolRequest->employee->name ?? null,
        'department' => $protocolRequest->employee_department,
    ])

    @include('protocol::partials.request-detail')

    <div class="pr-card mb-3">
      <div class="pr-card__body">
        <h3 class="pr-card__title">Approval Log</h3>
        <x-protocol::timeline :logs="$protocolRequest->logs" />
      </div>
    </div>

    <div class="pr-card">
      <div class="pr-card__body">
        <h3 class="pr-card__title">Final Decision</h3>
        <form method="POST" action="{{ route('protocol.manager.decide', $protocolRequest) }}" id="managerDecisionForm">
          @csrf
          <input type="hidden" name="decision" id="managerDecisionField" value="approve">

          <div class="mb-3">
            <label class="form-label" for="managerRemarks">Remarks</label>
            <textarea name="remarks" id="managerRemarks" class="form-control" rows="2" placeholder="Optional note for the record"></textarea>
          </div>

          <div class="pr-form-footer">
            <a href="{{ route('protocol.manager.queue') }}" class="btn pr-btn-cancel">Cancel</a>
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
  </div>
@endsection
