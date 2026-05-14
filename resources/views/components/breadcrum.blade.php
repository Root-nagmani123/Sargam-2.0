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
@endphp

<div class="modern-breadcrumb-wrapper mb-4" data-variant="{{ $variant }}">

    {{-- Card shell: Bootstrap 5.3 utilities + existing hooks for motion/print --}}
    <div class="card modern-breadcrumb-card border-0 shadow-sm rounded-4 overflow-hidden bg-body">

        {{-- Subtle Background Decoration --}}
        <div class="breadcrumb-decoration" aria-hidden="true">
            <div class="decoration-gradient"></div>
            <div class="decoration-pattern"></div>
        </div>

        {{-- Top Accent Line --}}
        <div class="breadcrumb-accent-line" aria-hidden="true"></div>

        {{-- Main Content Area --}}
        <div class="card-body breadcrumb-content position-relative p-3 p-md-4 px-lg-4 px-xl-5 py-lg-4">
            <div class="d-flex align-items-stretch justify-content-between flex-wrap gap-3">

                {{-- Left: Back + trail --}}
                <div class="d-flex align-items-center gap-2 gap-md-3 flex-grow-1 min-w-0">

                    {{-- Back: Bootstrap button + existing behavior --}}
                    <a href="#"
                       onclick="event.preventDefault(); handleBackNavigation();"
                       class="btn btn-outline-secondary btn-sm rounded-3 d-inline-flex align-items-center gap-2 modern-back-button position-relative overflow-hidden text-nowrap lh-sm flex-shrink-0"
                       title="Go back to previous page"
                       aria-label="Go back to previous page">
                        <span class="back-icon-wrapper d-inline-flex align-items-center justify-content-center">
                            <i class="material-icons material-symbols-rounded fs-6" aria-hidden="true">arrow_back</i>
                        </span>
                        <span class="back-text fw-semibold d-none d-sm-inline">Back</span>
                        <span class="button-ripple" aria-hidden="true"></span>
                    </a>

                    {{-- Vertical rule (Bootstrap 5.3) --}}
                    <div class="vr d-none d-lg-block opacity-25 align-self-stretch my-1 flex-shrink-0" role="presentation"></div>

                    {{-- Breadcrumb trail --}}
                    <div class="d-flex align-items-center gap-2 gap-xl-3 flex-grow-1 min-w-0 breadcrumb-title-wrapper">
                        <div class="title-indicator border-start border-4 border-primary rounded-1 d-none d-xl-block flex-shrink-0 shadow-sm" role="presentation"></div>
                        <div class="title-content flex-grow-1 min-w-0">
                            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                                <ol class="breadcrumb mb-1 mb-md-2 small fw-medium text-body-secondary flex-wrap"
                                    style="--bs-breadcrumb-divider: '>';">
                                    @foreach ($breadcrumbItems as $index => $item)
                                        @php $isLast = $index === count($breadcrumbItems) - 1; @endphp
                                        <li class="breadcrumb-item{{ $isLast ? ' active' : '' }}"
                                            @if ($isLast) aria-current="page" @endif>
                                            @if (!$isLast && filled($item['url']))
                                                <a href="{{ $item['url'] }}"
                                                   class="link-secondary link-offset-2 link-underline link-underline-opacity-0 link-underline-opacity-75-hover">
                                                    {{ $item['label'] }}
                                                </a>
                                            @else
                                                @if ($isLast)
                                                    <span class="text-primary fw-semibold">{{ $item['label'] }}</span>
                                                @else
                                                    <span>{{ $item['label'] }}</span>
                                                @endif
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            </nav>
                            <div class="title-underline rounded-pill" aria-hidden="true"></div>
                        </div>
                    </div>
                </div>

                {{-- Status pill --}}
                <div class="breadcrumb-status d-none d-xl-flex align-items-center flex-shrink-0">
                    <span class="badge rounded-pill text-primary-emphasis bg-primary-subtle border border-primary-subtle px-3 py-2 d-inline-flex align-items-center gap-2 fw-semibold lh-sm">
                        <i class="material-icons material-symbols-rounded fs-6" aria-hidden="true">check_circle</i>
                        <span>Active</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Complements Bootstrap 5.3: motion, accent, brand hover on back --}}
<style>
    /* Main Wrapper */
    .modern-breadcrumb-wrapper {
        position: relative;
        animation: breadcrumbFadeIn 0.4s ease-out;
    }

    /* Card: shadow lift on hover (utilities handle base look) */
    .modern-breadcrumb-card {
        position: relative;
        transition: box-shadow 0.35s cubic-bezier(0.4, 0, 0.2, 1), transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .modern-breadcrumb-card:hover {
        box-shadow: var(--bs-box-shadow-lg) !important;
        transform: translateY(-2px);
    }
    
    /* Background Decoration */
    .breadcrumb-decoration {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        pointer-events: none;
        z-index: 0;
    }
    
    .decoration-gradient {
        position: absolute;
        top: 0;
        right: 0;
        width: 40%;
        height: 100%;
        background: radial-gradient(ellipse at top right, rgba(1, 17, 41, 0.05), transparent 70%);
        opacity: 0;
        transition: opacity 0.4s ease;
    }
    
    .modern-breadcrumb-card:hover .decoration-gradient {
        opacity: 1;
    }
    
    .decoration-pattern {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: 
            radial-gradient(circle at 20px 20px, rgba(3, 20, 46, 0.01) 1px, transparent 1px);
        background-size: 40px 40px;
    }
    
    /* Top Accent Line */
    .breadcrumb-accent-line {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg,rgb(3, 35, 83) 0%,rgb(35, 36, 90) 50%,rgb(40, 26, 71) 100%);
        transform-origin: left;
        animation: accentExpand 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Stack above decoration */
    .breadcrumb-content {
        z-index: 1;
    }

    /* Back: brand hover while keeping btn semantics */
    .modern-back-button {
        transition: color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease,
            transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease;
    }

    .modern-back-button:hover {
        background: linear-gradient(135deg, rgb(1, 29, 70) 0%, rgb(29, 30, 70) 100%);
        border-color: var(--bs-primary);
        color: #fff;
        transform: translateX(-3px);
        box-shadow: 0 4px 12px rgba(3, 26, 61, 0.22);
    }

    .modern-back-button:active {
        transform: translateX(-1px) scale(0.98);
    }

    .modern-back-button:focus-visible {
        outline: 2px solid rgb(4, 27, 63);
        outline-offset: 2px;
    }
    
    .back-icon-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .modern-back-button:hover .back-icon-wrapper {
        transform: translateX(-3px);
    }
    
    .button-ripple {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.4) 0%, transparent 70%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .modern-back-button:hover .button-ripple {
        opacity: 0.2;
    }
    
    /* Title Wrapper */
    .breadcrumb-title-wrapper {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-grow: 1;
        min-width: 0;
    }
    
    .title-indicator {
        min-height: 2.25rem;
        animation: indicatorPulse 2s ease-in-out infinite;
    }

    /* Title Content */
    .title-content {
        flex-grow: 1;
        min-width: 0;
    }

    .breadcrumb-nav .breadcrumb {
        --bs-breadcrumb-item-padding-x: 0.35rem;
    }

    .breadcrumb-nav .breadcrumb-item + .breadcrumb-item::before {
        color: var(--bs-secondary-color);
        font-weight: 600;
    }

    /* Title Underline */
    .title-underline {
        height: 2px;
        width: 50px;
        background: linear-gradient(90deg, #0d6efd 0%, #6366f1 70%, transparent 100%);
        border-radius: 2px;
        margin-top: 0.5rem;
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .modern-breadcrumb-card:hover .title-underline {
        width: 80px;
    }
    
    .breadcrumb-status .badge {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .breadcrumb-status .badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(var(--bs-primary-rgb), 0.12);
    }

    /* Animations */
    @keyframes breadcrumbFadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes accentExpand {
        from {
            transform: scaleX(0);
        }
        to {
            transform: scaleX(1);
        }
    }
    
    @keyframes indicatorPulse {
        0%, 100% {
            box-shadow: 0 0 0 1px rgba(var(--bs-primary-rgb), 0.2);
            filter: brightness(1);
        }
        50% {
            box-shadow: 0 0 12px rgba(var(--bs-primary-rgb), 0.35);
            filter: brightness(1.02);
        }
    }
    
    /* Reduced Motion */
    @media (prefers-reduced-motion: reduce) {
        .modern-breadcrumb-wrapper,
        .modern-breadcrumb-card,
        .modern-back-button,
        .title-underline,
        .breadcrumb-accent-line,
        .title-indicator {
            animation: none !important;
            transition: none !important;
        }
    }
    
    /* Print Styles */
    @media print {
        .modern-breadcrumb-card {
            box-shadow: none !important;
            border: 1px solid var(--bs-border-color) !important;
            transform: none !important;
        }
        
        .breadcrumb-decoration,
        .breadcrumb-accent-line,
        .breadcrumb-status {
            display: none;
        }
        
        .modern-back-button {
            display: none;
        }
    }
    
    /* Responsive Mobile Refinements */
    @media (max-width: 767.98px) {
        .modern-breadcrumb-wrapper .modern-breadcrumb-card.rounded-4 {
            border-radius: var(--bs-border-radius-xl) !important;
        }

        .breadcrumb-nav .breadcrumb {
            font-size: 0.8125rem;
        }

        .title-underline {
            height: 2px;
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
</script>
