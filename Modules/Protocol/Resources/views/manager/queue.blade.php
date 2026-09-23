@extends('protocol::layouts.app')

@section('title', 'Recommended to Me')
@section('page_title', 'Recommended to Me')
@section('page_subtitle', 'Requests that Protocol Staff has recommended to you for final approval')

@section('content')

  <div class="card-clean p-3">
    <div class="section-title">Recommended to Me</div>
    <div class="section-sub mb-2">Requests that Protocol Staff has recommended to you for final approval</div>

    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr><th>Request ID</th><th>Type</th><th>Employee</th><th>Recommended By</th><th>Recommended On</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
          @forelse ($requests as $r)
            <tr>
              <td class="fw-semibold">{{ $r->request_number }}</td>
              <td><x-protocol::type-pill :type="$r->request_type" /></td>
              <td>{{ $r->employee->name ?? '—' }}</td>
              <td>{{ $r->protocolStaff->name ?? '—' }}</td>
              <td>{{ $r->recommended_at?->format('d M Y, h:i A') }}</td>
              <td><x-protocol::status-badge :status="$r->status" /></td>
              <td>
                <a href="{{ route('protocol.manager.review', $r) }}" class="btn btn-sm btn-green">Review &amp; Decide</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="7"><div class="empty-state"><i class="bi bi-check2-circle"></i>Nothing recommended to you right now.</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">{{ $requests->links() }}</div>
  </div>

@endsection
