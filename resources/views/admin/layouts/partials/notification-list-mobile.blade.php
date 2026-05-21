@if($notifications->count() > 0)
    @foreach($notifications as $notification)
        <a class="notification-item notification-mobile-item {{ $notification->is_read ? '' : 'notification-item-unread' }}"
            href="javascript:void(0)" data-notification-id="{{ $notification->pk }}">
            <div class="notification-item-body">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <span class="notification-item-title">{{ $notification->title ?? 'Notification' }}</span>
                    @if(empty($notification->is_read))
                    <span class="badge bg-danger notification-new-tag">New</span>
                    @endif
                </div>
                <p class="notification-item-message">{{ Str::limit(\App\Services\NotificationService::stripMessCombinedReceiptPayloadForDisplay($notification->message ?? ''), 80) }}</p>
                <span class="notification-item-time">{{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}</span>
            </div>
        </a>
    @endforeach
@else
    <div class="notification-empty-state">
        <i class="material-icons material-symbols-rounded">notifications_none</i>
        <span>No notifications</span>
    </div>
@endif
