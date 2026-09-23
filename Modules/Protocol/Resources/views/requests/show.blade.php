@extends('admin.layouts.master')
@section('title', $protocolRequest->request_number)
@section('page_title', $protocolRequest->request_number . ' — ' . $protocolRequest->typeLabel())
@section('page_subtitle', 'Full request detail and approval history')

@section('content')
@include('protocol::partials.style')
  <div class="container-fluid profile-page">
    <x-breadcrum
        title="{{ $protocolRequest->request_number . ' — ' . $protocolRequest->typeLabel() }}"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            $protocolRequest->request_number,
        ]"
    />


    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <div class="text-muted small">Raised by</div>
        <div class="fw-bold">{{ $protocolRequest->employee->first_name . ' ' .$protocolRequest->employee->middle_name . ' ' .$protocolRequest->employee->last_name ?? '—' }}</div>
        <div class="text-muted small">{{ $protocolRequest->employeeDepartment->department_name ?? '—' }} &middot; {{ $protocolRequest->created_at->format('d M Y') }}</div>
      </div>
      <x-protocol::status-badge :status="$protocolRequest->status" />
    </div>

    @include('protocol::partials.request-detail')

    <div class="card-clean p-3">
      <div class="fw-bold small text-uppercase text-muted mb-2" style="letter-spacing:.05em;">Approval Log</div>
      <x-protocol::timeline :logs="$protocolRequest->logs" />
    </div>
  </div>
@endsection
