@php
    $avClasses = ['text-bg-primary', 'text-bg-info', 'text-bg-success', 'text-bg-warning', 'text-bg-danger', 'text-bg-secondary'];
    $avClass = $avClasses[($loopIndex ?? 0) % count($avClasses)];
    $photo = !empty($person->profile_picture) ? asset('storage/' . $person->profile_picture) : null;
    $email = trim((string) ($person->email ?? ''));
    $fullName = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));
    $wishBtnLabel = $wishBtnLabel ?? 'Wish them';
    $showBirthdayDate = $showBirthdayDate ?? false;
    $searchText = mb_strtolower($fullName . ' ' . ($person->designation_name ?? '') . ' ' . ($person->birthday_date ?? '') . ' ' . $email . ' ' . ($person->mobile ?? ''));
@endphp

<div class="card dashboard-birthday-item mb-0" data-comms-searchable data-search-text="{{ e($searchText) }}">
    <div class="card-body">
        <div class="dashboard-birthday-row-main">
            <div class="dashboard-birthday-user">
                <div class="dashboard-birthday-avatar-wrap position-relative">
                    @if($photo)
                    <img src="{{ $photo }}" alt=""
                        class="rounded-circle object-fit-cover dashboard-birthday-avatar"
                        width="48" height="48" loading="lazy"
                        onerror="this.classList.add('d-none'); var f=this.nextElementSibling; if(f){ f.classList.remove('d-none'); f.classList.add('d-inline-flex'); }">
                    <div class="rounded-circle {{ $avClass }} fw-semibold d-none align-items-center justify-content-center dashboard-birthday-avatar"
                        aria-hidden="true">
                        {{ strtoupper(substr((string)($person->first_name ?? ''), 0, 1)) }}
                    </div>
                    @else
                    <div class="rounded-circle {{ $avClass }} fw-semibold d-inline-flex align-items-center justify-content-center dashboard-birthday-avatar">
                        {{ strtoupper(substr((string)($person->first_name ?? ''), 0, 1)) }}
                    </div>
                    @endif
                </div>
                <div class="min-w-0">
                    <div class="dashboard-birthday-name text-truncate" title="{{ $fullName }}">{{ $fullName }}</div>
                    <div class="dashboard-birthday-designation text-truncate mb-0" title="{{ $person->designation_name ?? '' }}">
                        {{ $person->designation_name ?? '' }}
                        @if($showBirthdayDate && !empty($person->birthday_date))
                        · {{ $person->birthday_date }}
                        @endif
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.birthday-wish.index') }}"
                class="btn btn-sm btn-outline-primary rounded-1 flex-shrink-0 dashboard-birthday-btn-wish"
                title="Send a birthday wish">
                {{ $wishBtnLabel }}
            </a>
        </div>
        @if($email !== '' || !empty($person->mobile) || !empty($person->office_extension_no))
        <div class="dashboard-birthday-contact-block">
            <div class="dashboard-birthday-contact-row">
                @if(!empty($person->mobile))
                <span class="dashboard-birthday-contact-pill" title="{{ $person->mobile }}">
                    <span class="material-icons material-symbols-rounded" aria-hidden="true">call</span>
                    <span class="text-truncate">{{ $person->mobile }}</span>
                </span>
                @endif
                @if($email !== '')
                <span class="dashboard-birthday-contact-pill" title="{{ $email }}">
                    <span class="material-icons material-symbols-rounded" aria-hidden="true">mail</span>
                    <span class="text-truncate">{{ $email }}</span>
                </span>
                @endif
                @if(!empty($person->office_extension_no))
                <span class="dashboard-birthday-contact-pill" title="Office extension">
                    <span class="material-icons material-symbols-rounded" aria-hidden="true">local_phone</span>
                    <span class="text-truncate">Ext {{ $person->office_extension_no }}</span>
                </span>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
