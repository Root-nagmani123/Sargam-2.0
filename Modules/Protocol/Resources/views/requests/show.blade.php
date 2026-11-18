@extends('admin.layouts.master')
@section('title', $protocolRequest->request_number)
@section('page_title', $protocolRequest->request_number . ' — ' . $protocolRequest->typeLabel())
@section('page_subtitle', 'Full request detail and approval history')

@section('content')
@include('protocol::partials.style')
  <div class="container-fluid protocol-page">
    <x-breadcrum
        title="{{ $protocolRequest->request_number . ' — ' . $protocolRequest->typeLabel() }}"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            $protocolRequest->request_number,
        ]"
    />

    @include('protocol::partials.request-hero', [
        'raisedBy' => trim(preg_replace('/\s+/', ' ', ($protocolRequest->employee->first_name ?? '') . ' ' . ($protocolRequest->employee->middle_name ?? '') . ' ' . ($protocolRequest->employee->last_name ?? ''))),
        'department' => $protocolRequest->employeeDepartment->department_name ?? null,
    ])

    @include('protocol::partials.request-detail')

    <div class="pr-card">
      <div class="pr-card__body">
        <h3 class="pr-card__title">Approval Log</h3>
        <x-protocol::timeline :logs="$protocolRequest->logs" />
      </div>
    </div>
  </div>
@endsection
