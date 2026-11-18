@extends('admin.layouts.master')

@section('title', 'Approval Queue')
@section('page_title', 'Approval Queue')
@section('page_subtitle', 'Requests awaiting your review as Protocol Staff')

@section('content')
@include('protocol::partials.style')
  <div class="container-fluid protocol-page">
    <x-breadcrum
        title="Approval Queue"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            'Approval Queue',
        ]"
    />

    <div class="card">
      <div class="card-body">
        <div class="programme-dt-toolbar mb-4">
          <div class="section-sub">Requests awaiting your review as Protocol Staff</div>
        </div>

        <div class="programme-dt-panel">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100 programme-dt-table">
              <thead>
                <tr><th>Request ID</th><th>Type</th><th>Employee</th><th>Department</th><th>Raised On</th><th>Status</th><th>Action</th></tr>
              </thead>
              <tbody>
                @forelse ($requests as $r)
                  <tr>
                    <td class="pr-col-id">{{ $r->request_number }}</td>
                    <td><x-protocol::type-pill :type="$r->request_type" /></td>
                    <td>{{ $r->employee->name ?? '—' }}</td>
                    <td class="text-muted small">{{ $r->employee_department }}</td>
                    <td>{{ $r->created_at->format('d M Y') }}</td>
                    <td><x-protocol::status-badge :status="$r->status" /></td>
                    <td>
                      <div class="pr-act-group" role="group" aria-label="Row actions">
                        <a href="{{ route('protocol.approval.review', $r) }}" class="pr-act pr-act--review">
                          <span class="pr-act__icon"><i class="bi bi-clipboard-check" aria-hidden="true"></i></span>
                          <span class="pr-act__label">Review</span>
                        </a>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="7"><div class="empty-state"><i class="bi bi-check2-circle"></i>No pending requests. Queue is clear.</div></td></tr>
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
