@props(['logs'])

<div class="timeline">
  @foreach ($logs as $log)
    <div class="timeline-item done">
      <div class="t-title">
        {{ ucfirst($log->action) }}
        <span class="text-muted fw-normal">&mdash; {{ $log->actionBy->name ?? 'User #' . $log->action_by_id }} ({{ $log->action_by_role }})</span>
      </div>
      <div class="t-meta">{{ $log->action_at->format('d M Y, h:i A') }}</div>
      @if ($log->remarks)
        <div class="t-remark">{{ $log->remarks }}</div>
      @endif
    </div>
  @endforeach
</div>
