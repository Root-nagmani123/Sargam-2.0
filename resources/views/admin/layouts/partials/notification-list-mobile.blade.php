@if($notifications->count() > 0)
    @foreach($notifications as $notification)
        <div class="notification-list-item mb-2">
            <a class="card border rounded-2 text-decoration-none notification-item notification-mobile-item {{ $notification->is_read ? '' : 'notification-item-unread' }}"
                href="javascript:void(0)" data-notification-id="{{ $notification->pk }}">
                <div class="card-body p-3 notification-item-body">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <h6 class="card-title mb-0 fw-bold text-primary notification-item-title">
                            {{ $notification->title ?? 'Notification' }}
                        </h6>
                        @if(empty($notification->is_read))
                        <span class="badge bg-danger notification-new-tag">New</span>
                        @endif
                    </div>
                    <p class="card-text text-muted small mb-2 mt-2 notification-item-message">
                        {{ Str::limit(\App\Services\NotificationService::stripMessCombinedReceiptPayloadForDisplay($notification->message ?? ''), 120) }}
                    </p>
                    <small class="text-secondary notification-item-time">
                        {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}
                    </small>
                </div>
            </a>
        </div>
    @endforeach
@else
    <div class="notification-empty-state text-center text-muted py-5">
        <i class="material-icons material-symbols-rounded d-block mb-2 opacity-50">notifications_none</i>
        <span class="small">No notifications</span>
    </div>
@endif
