@extends('protocol::layouts.app')

@section('title', 'History Log')
@section('page_title', 'History Log')
@section('page_subtitle', 'Full audit trail — who approved / recommended each request, and on what date')

@section('content')

  <div class="card-clean p-3">
    <div class="section-title">History Log</div>
    <div class="section-sub mb-2">Full audit trail — who approved / recommended each request, and on what date</div>

    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr><th>Request ID</th><th>Type</th><th>Action</th><th>By</th><th>Role</th><th>Date</th><th>Remarks</th></tr>
        </thead>
        <tbody>
          @forelse ($logs as $log)
            <tr>
              <td class="fw-semibold">{{ $log->protocolRequest->request_number }}</td>
              <td><x-protocol::type-pill :type="$log->protocolRequest->request_type" /></td>
              <td>{{ ucfirst($log->action) }}</td>
              <td>{{ $log->actionBy->name ?? 'User #' . $log->action_by_id }}</td>
              <td class="text-muted small">{{ $log->action_by_role }}</td>
              <td class="text-muted small">{{ $log->action_at->format('d M Y, h:i A') }}</td>
              <td class="text-muted small">{{ $log->remarks ?? '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="7"><div class="empty-state"><i class="bi bi-clock-history"></i>No activity recorded yet.</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">{{ $logs->links() }}</div>
  </div>

@endsection
