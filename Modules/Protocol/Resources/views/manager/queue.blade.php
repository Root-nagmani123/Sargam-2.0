@extends('admin.layouts.master')

@section('title', 'Recommended to Me')
@section('page_title', 'Recommended to Me')
@section('page_subtitle', 'Requests that Protocol Staff has recommended to you for final approval')

@section('content')
@include('protocol::partials.style')
  <div class="container-fluid protocol-page">
    <x-breadcrum
        title="Recommended to Me"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            'Recommended to Me',
        ]"
    />

    <div class="card">
      <div class="card-body">
        <div class="programme-dt-toolbar mb-4">
          <div class="section-sub">Requests that Protocol Staff has recommended to you for final approval</div>
        </div>

        <div class="programme-dt-panel">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100 programme-dt-table">
              <thead>
                <tr><th>Request ID</th><th>Type</th><th>Employee</th><th>Recommended By</th><th>Recommended On</th><th>Status</th><th>Action</th></tr>
              </thead>
              <tbody>
                @forelse ($requests as $r)
                  <tr>
                    <td class="pr-col-id">{{ $r->request_number }}</td>
                    <td><x-protocol::type-pill :type="$r->request_type" /></td>
                    <td>{{ $r->employee->name ?? '—' }}</td>
                    <td>{{ $r->protocolStaff->name ?? '—' }}</td>
                    <td>{{ $r->recommended_at?->format('d M Y, h:i A') }}</td>
                    <td><x-protocol::status-badge :status="$r->status" /></td>
                    <td>
                      <div class="pr-act-group" role="group" aria-label="Row actions">
                        <a href="{{ route('protocol.manager.review', $r) }}" class="pr-act pr-act--review">
                          <span class="pr-act__icon"><i class="bi bi-check2-square" aria-hidden="true"></i></span>
                          <span class="pr-act__label">Review &amp; Decide</span>
                        </a>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="7"><div class="empty-state"><i class="bi bi-check2-circle"></i>Nothing recommended to you right now.</div></td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        @include('protocol::partials.paginator-footer', ['paginator' => $requests])
      </div>
    </div>
  </div>
@endsection
