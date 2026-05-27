@php
$avClasses = ['text-bg-primary', 'text-bg-info', 'text-bg-success', 'text-bg-warning', 'text-bg-danger', 'text-bg-secondary'];
$avClass = $avatarClass ?? $avClasses[($loopIndex ?? 0) % count($avClasses)];
$photo = !empty($employee->profile_picture) ? asset('storage/' . $employee->profile_picture) : null;
$email = trim((string) ($employee->email ?? ''));
$fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
$wishCount = $birthdayWishCounts[$employee->pk] ?? 0;
$hasContact = $email !== '' || !empty($employee->mobile) || !empty($employee->office_extension_no);
$showWish = $showWishButton ?? true;
$variant = $variant ?? 'today';
$wishLabel = ($variant === 'upcoming') ? 'Wish them Advanced' : 'Wish them';
$itemClasses = 'dashboard-birthday-item dashboard-birthday-item--' . $variant . ' rounded-3 border p-3';
@endphp
<article class="{{ $itemClasses }}" @if($hasContact) tabindex="0" @endif
    data-feed-search="{{ strtolower($fullName . ' ' . ($employee->designation_name ?? '') . ' ' . ($employee->birthday_date ?? '')) }}">
    <div class="dashboard-birthday-row d-flex align-items-center gap-3">
        <x-dashboard-birthday-avatar :photo="$photo" :name="$fullName" :color-class="$avClass"
            class="dashboard-birthday-avatar flex-shrink-0" />
        <div class="dashboard-birthday-info flex-grow-1 min-w-0">
            <p class="dashboard-birthday-name text-truncate mb-0 fw-semibold">{{ $fullName }}</p>
            <p class="dashboard-birthday-designation text-truncate mb-0 text-body-secondary small">
                {{ $employee->designation_name ?? '' }}</p>
            @if(!empty($employee->birthday_date) && $variant === 'upcoming')
            <span class="dashboard-birthday-upcoming-meta badge rounded-1 text-bg-light border text-primary fw-normal mt-1">
                <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>{{ $employee->birthday_date }}
                @if(isset($employee->days_away))
                · in {{ $employee->days_away }} {{ $employee->days_away === 1 ? 'day' : 'days' }}
                @endif
            </span>
            @endif
            @if($wishCount > 0)
            <span class="badge rounded-1 bg-success-subtle text-success border border-success-subtle dashboard-birthday-badge mt-1"
                title="{{ $wishCount }} wishes sent">
                <i class="bi bi-gift me-1" aria-hidden="true"></i>{{ $wishCount }}
            </span>
            @endif
        </div>
        @if($showWish)
        <button type="button"
            class="btn btn-sm dashboard-birthday-wish-btn btn-custom-wish flex-shrink-0 rounded-1 px-3"
            data-name="{{ $fullName }}"
            data-email="{{ $email }}"
            data-mobile="{{ $employee->mobile ?? '' }}"
            data-pk="{{ $employee->pk }}"
            title="Send birthday wish to {{ $fullName }}">{{ $wishLabel }}</button>
        @endif
    </div>
    @if($hasContact)
    <div class="dashboard-birthday-detail">
        <div class="d-flex flex-wrap gap-2 pt-1">
            @if(!empty($employee->mobile))
            <div class="dashboard-birthday-contact-pill">
                <i class="bi bi-telephone" aria-hidden="true"></i>
                <span>{{ $employee->mobile }}</span>
            </div>
            @endif
            @if($email !== '')
            <div class="dashboard-birthday-contact-pill">
                <i class="bi bi-envelope" aria-hidden="true"></i>
                <span>{{ $email }}</span>
            </div>
            @endif
            @if(!empty($employee->office_extension_no))
            <div class="dashboard-birthday-contact-pill">
                <i class="bi bi-telephone-outbound" aria-hidden="true"></i>
                <span>Ext {{ $employee->office_extension_no }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif
</article>
