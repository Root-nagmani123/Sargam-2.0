{{-- Modern & Elegant Breadcrumb Component --}}
@props(['title' => 'Page', 'variant' => 'glass', 'items' => null, 'section' => null])
@php
    $variant = $variant ?? 'glass';
    $routeName = request()->route()?->getName();
    $requestPath = request()->path();

    $matchesSection = function (array $routePatterns = [], array $pathPatterns = []) use ($routeName, $requestPath) {
        return (!empty($routePatterns) && \Illuminate\Support\Str::is($routePatterns, $routeName ?? ''))
            || (!empty($pathPatterns) && \Illuminate\Support\Str::is($pathPatterns, $requestPath));
    };

    $resolvedSection = $section ?? (function () use ($matchesSection) {
        if ($matchesSection(['admin.mess.*'], ['admin/mess*'])) {
            return 'Mess Management';
        }

        if ($matchesSection(
            ['admin.security.*', 'admin.employee_idcard.*', 'admin.duplicate_idcard.*', 'admin.family_idcard.*'],
            ['security*', 'admin/employee-idcard*', 'admin/duplicate-idcard*', 'admin/family-idcard*']
        )) {
            return 'Security';
        }

        if ($matchesSection(
            [
                'admin.issue-management.*',
                'admin.issue-categories.*',
                'admin.issue-sub-categories.*',
                'admin.issue-priorities.*',
                'admin.issue-escalation-matrix.*',
                'issue-management.*',
                'issue-categories.*',
                'issue-sub-categories.*',
                'issue-priorities.*',
                'issue-escalation-matrix.*',
            ],
            ['issue-management*', 'issue-categories*', 'issue-sub-categories*', 'issue-priorities*', 'issue-escalation-matrix*']
        )) {
            return 'Centcom';
        }

        if ($matchesSection(['admin.estate.*'], ['admin/estate*'])) {
            return 'Estate Management';
        }

        if ($matchesSection(['forms.*'], ['forms*'])) {
            return 'FC Forms';
        }

        if ($matchesSection(
            [
                'calendar.*',
                'attendance.*',
                'send.notice.management.*',
                'memo.notice.management.*',
                'memo.discipline.*',
                'admin.memo-notice.*',
                'timetable-report.*',
                'feedback.get.*',
                'subject.*',
                'subject-module.*',
            ],
            ['calendar*', 'attendance*', 'memo/discipline*']
        )) {
            return 'Time Table';
        }

        if ($matchesSection(
            [
                'programme.*',
                'group.mapping.*',
                'master.course.group.type.*',
                'student.medical.exemption.*',
                'mdo-escrot-exemption.*',
                'master.exemption.*',
                'master.mdo_duty_type.*',
                'master.memo.type.master.*',
                'master.memo.conclusion.master.*',
                'course.memo.decision.*',
                'admin.feedback.*',
                'feedback.average*',
                'medical.exception.*',
                'ot.*',
                'faculty.mdo.*',
                'faculty.notice.*',
                'peer.*',
                'admin.course-repository.user.*',
            ]
        )) {
            return 'Academic';
        }

        if ($matchesSection(
            ['member.profile.edit', 'admin.dashboard*', 'admin.notice.*'],
            ['dashboard*', 'member/profile/edit*']
        )) {
            return 'General';
        }

        if ($matchesSection(
            [
                'member.*',
                'faculty.*',
                'master.employee.*',
                'master.department.*',
                'master.designation.*',
                'master.caste.category.*',
                'master.faculty.*',
                'admin.faculty.whos-who',
                'admin.roles.*',
                'admin.users.*',
                'admin.setup.quick_links.*',
                'admin.setup.useful_links.*',
                'course-repository.*',
            ],
            ['member*', 'faculty*', 'users*']
        )) {
            return 'Users';
        }

        if ($matchesSection(
            [
                'Venue-Master.*',
                'master.class.session.*',
                'stream.*',
                'master.country.*',
                'master.state.*',
                'master.district.*',
                'master.city.*',
                'master.hostel.*',
                'hostel.*',
            ]
        )) {
            return 'Master';
        }

        return 'General';
    })();

    $breadcrumbItems = collect(
        is_array($items) && count($items)
            ? array_values(array_filter($items, fn ($item) => filled($item)))
            : ['Home', $resolvedSection, $title]
    )
        ->map(function ($item) {
            if (is_array($item)) {
                return [
                    'label' => $item['label'] ?? $item['title'] ?? null,
                    'url' => $item['url'] ?? $item['href'] ?? null,
                ];
            }

            return [
                'label' => $item,
                'url' => null,
            ];
        })
        ->filter(fn ($item) => filled($item['label']))
        ->values()
        ->all();

    $pageHeading = $title;
    if (!empty($breadcrumbItems)) {
        $lastItem = $breadcrumbItems[count($breadcrumbItems) - 1];
        $pageHeading = $lastItem['label'] ?? $title;
    }
@endphp

<div class="modern-breadcrumb-wrapper mb-4" data-variant="{{ $variant }}">
    <div class="card modern-breadcrumb-card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div class="d-flex align-items-center gap-3 min-w-0 flex-grow-1">
                    <a href="#"
                       onclick="event.preventDefault(); handleBackNavigation();"
                       class="btn btn-link p-0 modern-back-button text-body-secondary d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                       title="Go back to previous page"
                       aria-label="Go back to previous page">
                        <i class="material-icons material-symbols-rounded modern-back-button-icon" aria-hidden="true">arrow_back</i>
                    </a>
                    <div class="min-w-0 flex-grow-1">
                        <nav aria-label="breadcrumb" class="breadcrumb-nav">
                            <ol class="breadcrumb mb-1 small flex-wrap"
                                style="--bs-breadcrumb-divider: '/'; --bs-breadcrumb-item-padding-x: 0.35rem;">
                                @foreach ($breadcrumbItems as $index => $item)
                                    @php $isLast = $index === count($breadcrumbItems) - 1; @endphp
                                    <li class="breadcrumb-item{{ $isLast ? ' active' : '' }}"
                                        @if ($isLast) aria-current="page" @endif>
                                        @if (!$isLast && filled($item['url']))
                                            <a href="{{ $item['url'] }}"
                                               class="link-secondary link-underline-opacity-0 link-underline-opacity-100-hover text-decoration-none">
                                                {{ $item['label'] }}
                                            </a>
                                        @else
                                            @if ($isLast)
                                                <span class="text-body fw-bold">{{ $item['label'] }}</span>
                                            @else
                                                <span class="text-body-secondary">{{ $item['label'] }}</span>
                                            @endif
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                        <h1 class="h4 fs-4 fw-bold text-dark mb-0 lh-sm text-truncate">{{ $pageHeading }}</h1>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0 modern-breadcrumb-clock text-primary fw-semibold lh-sm"
                     role="status"
                     aria-live="polite"
                     aria-atomic="true">
                    <i class="material-icons material-symbols-rounded fs-5" aria-hidden="true">schedule</i>
                    <span id="breadcrumb-live-time">—:—</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .modern-breadcrumb-wrapper .breadcrumb-item + .breadcrumb-item::before {
        color: var(--bs-secondary-color);
    }

    .modern-breadcrumb-wrapper .breadcrumb-item.active {
        color: inherit;
    }

    .modern-back-button {
        width: 2.25rem;
        height: 2.25rem;
        text-decoration: none;
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out;
    }

    .modern-back-button-icon {
        font-size: 1.35rem;
    }

    #breadcrumb-live-time {
        font-variant-numeric: tabular-nums;
        min-width: 2.75rem;
    }

    .modern-back-button:hover {
        color: var(--bs-primary) !important;
        background-color: var(--bs-tertiary-bg);
    }

    .modern-back-button:focus-visible {
        box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.35);
    }

    @media (prefers-reduced-motion: reduce) {
        .modern-back-button {
            transition: none;
        }
    }

    @media print {
        .modern-breadcrumb-card {
            box-shadow: none !important;
            border: 1px solid var(--bs-border-color) !important;
        }

        .modern-back-button,
        .modern-breadcrumb-clock {
            display: none !important;
        }
    }
</style>

<script>
// Keep a lightweight navigation history so "Back" goes to last click reliably.
(function initBreadcrumbBackStack() {
    const NAV_STACK_KEY = 'sargam_breadcrumb_back_stack_v1';
    const currentUrl = window.location.href;

    function isSameOrigin(url) {
        try {
            return new URL(url).origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    function safeParse(json, fallback) {
        try {
            const val = JSON.parse(json);
            return val ?? fallback;
        } catch (e) {
            return fallback;
        }
    }

    function getStack() {
        const raw = sessionStorage.getItem(NAV_STACK_KEY);
        return safeParse(raw, []);
    }

    function setStack(stack) {
        // Limit size to avoid unbounded growth.
        const trimmed = Array.isArray(stack) ? stack.slice(-20) : [];
        sessionStorage.setItem(NAV_STACK_KEY, JSON.stringify(trimmed));
    }

    try {
        if (!isSameOrigin(currentUrl)) return;
        const stack = getStack();
        const last = stack[stack.length - 1];
        if (last !== currentUrl) {
            // Avoid duplicates while preserving order.
            const deduped = stack.filter((u) => u !== currentUrl);
            deduped.push(currentUrl);
            setStack(deduped);
        }
    } catch (e) {
        // If storage is blocked, fall back to referrer/history logic.
    }
})();

function handleBackNavigation() {
    const NAV_STACK_KEY = 'sargam_breadcrumb_back_stack_v1';
    const currentUrl = window.location.href;

    function isSameOrigin(url) {
        try {
            return new URL(url).origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    function safeParse(json, fallback) {
        try {
            const val = JSON.parse(json);
            return val ?? fallback;
        } catch (e) {
            return fallback;
        }
    }

    function getStack() {
        const raw = sessionStorage.getItem(NAV_STACK_KEY);
        return safeParse(raw, []);
    }

    function setStack(stack) {
        const trimmed = Array.isArray(stack) ? stack.slice(-20) : [];
        sessionStorage.setItem(NAV_STACK_KEY, JSON.stringify(trimmed));
    }

    // Priority 0: Use our localStorage stack (best for "last click" behavior).
    try {
        if (isSameOrigin(currentUrl)) {
            const stack = getStack();
            if (Array.isArray(stack) && stack.length) {
                if (stack[stack.length - 1] === currentUrl) {
                    stack.pop();
                }
                const target = stack.length ? stack[stack.length - 1] : null;
                if (target && target !== currentUrl) {
                    setStack(stack);
                    window.location.href = target;
                    return;
                }
            }
        }
    } catch (e) {
        // Ignore and continue with fallbacks.
    }

    // Priority 1: Use document.referrer (most reliable for actual last click)
    const referrer = document.referrer;
    
    // Check if referrer exists and is not the current page
    if (referrer && referrer !== currentUrl && referrer.includes(window.location.hostname)) {
        window.location.href = referrer;
        return;
    }
    
    // Priority 2: Use Laravel's previous URL
    const previousUrl = "{{ url()->previous() }}";
    if (previousUrl && previousUrl !== currentUrl) {
        window.location.href = previousUrl;
        return;
    }
    
    // Priority 3: Fallback to browser history
    if (window.history.length > 1) {
        window.history.back();
        return;
    }
    
    // Priority 4: Default fallback - go to home/dashboard
    window.location.href = "{{ url('/') }}";
}

(function initBreadcrumbLiveClock() {
    const el = document.getElementById('breadcrumb-live-time');
    if (!el) return;

    function formatTime() {
        const d = new Date();
        return d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', hour12: false });
    }

    function tick() {
        el.textContent = formatTime();
    }

    tick();
    window.setInterval(tick, 30000);
})();
</script>
