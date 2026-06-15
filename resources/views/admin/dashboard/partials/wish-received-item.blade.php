@php
$wishSenderName = 'System';
if ($wish->sender) {
    $wishSenderName = trim(($wish->sender->first_name ?? '') . ' ' . ($wish->sender->last_name ?? ''));
    if ($wishSenderName === '') {
        $wishSenderName = $wish->sender->name ?? 'System';
    }
}
if ($wishSenderName === 'System' && !empty($wish->message) && preg_match('/^(.+?)\s+wished you/i', $wish->message, $wishMatches)) {
    $wishSenderName = trim($wishMatches[1]);
}
$wishMessage = \App\Services\NotificationService::stripMessCombinedReceiptPayloadForDisplay($wish->message ?? '');
$wishSenderPk = $wish->sender_user_id ?? null;
$wishSenderEmail = $wish->sender->email ?? '';
$wishSenderMobile = $wish->sender->mobile ?? $wish->sender->phone ?? '';
$layout = $layout ?? 'feed';
@endphp

@if($layout === 'feed')
@php
$feedWishSearch = strtolower(($wish->title ?? '') . ' ' . $wishMessage . ' ' . $wishSenderName);
@endphp
<article
    class="dashboard-feed-expanded-card dashboard-feed-wish-card dashboard-notification-item {{ empty($wish->is_read) ? 'dashboard-feed-expanded-card--unread' : '' }}"
    data-notification-id="{{ $wish->pk }}"
    data-feed-search="{{ $feedWishSearch }}">
    <div class="dashboard-feed-expanded-card__head">
        <p class="dashboard-feed-expanded-card__title mb-0">{{ $wish->title ?? 'Birthday wish' }}</p>
        <span class="dashboard-feed-expanded-card__meta">~by <strong>{{ $wishSenderName }}</strong>
            on {{ $wish->created_at ? \Carbon\Carbon::parse($wish->created_at)->format('d/m/Y h:i A') : '—' }}</span>
    </div>
    <p class="dashboard-feed-expanded-card__body mb-0">{{ $wishMessage }}</p>
    @if($wishSenderPk)
    <div class="dashboard-feed-wish-card__actions">
        <button type="button"
            class="btn btn-sm btn-outline-primary rounded-1 btn-wish-reply"
            data-pk="{{ $wishSenderPk }}"
            data-name="{{ $wishSenderName }}"
            data-email="{{ $wishSenderEmail }}"
            data-mobile="{{ $wishSenderMobile }}"
            data-notification-id="{{ $wish->pk }}">
            <i class="bi bi-reply-fill me-1" aria-hidden="true"></i>Reply
        </button>
    </div>
    @endif
</article>
@else
@php
$wishTime = isset($wish->created_at) ? \Carbon\Carbon::parse($wish->created_at) : null;
@endphp
<article class="dashboard-birthday-wish-item rounded-3 p-3">
    <div class="d-flex gap-3 align-items-start">
        <span class="wish-icon-wrap" aria-hidden="true">
            <i class="bi bi-gift-fill"></i>
        </span>
        <div class="min-w-0 flex-grow-1">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                <h6 class="mb-0 fw-semibold text-dark">{{ $wishSenderName }}</h6>
                @if($wishTime)
                <time class="small text-body-secondary" datetime="{{ $wishTime->toIso8601String() }}">
                    {{ $wishTime->diffForHumans() }}
                </time>
                @endif
            </div>
            <p class="mb-0 small text-body-secondary">
                {{ $wishMessage ?: ($wish->title ?? 'Sent you a birthday wish!') }}</p>
            @if($wishSenderPk)
            <div class="dashboard-birthday-wish-item__actions mt-2">
                <button type="button"
                    class="btn btn-sm btn-outline-primary rounded-1 btn-wish-reply"
                    data-pk="{{ $wishSenderPk }}"
                    data-name="{{ $wishSenderName }}"
                    data-email="{{ $wishSenderEmail }}"
                    data-mobile="{{ $wishSenderMobile }}"
                    data-notification-id="{{ $wish->pk }}">
                    <i class="bi bi-reply-fill me-1" aria-hidden="true"></i>Reply
                </button>
            </div>
            @endif
        </div>
    </div>
</article>
@endif
