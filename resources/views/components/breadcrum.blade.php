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
    
    {{-- Premium Card Design --}}
    <div class="modern-breadcrumb-card">
        
        {{-- Subtle Background Decoration --}}
        <div class="breadcrumb-decoration" aria-hidden="true">
            <div class="decoration-gradient"></div>
            <div class="decoration-pattern"></div>
        </div>
        
        {{-- Top Accent Line --}}
        <div class="breadcrumb-accent-line" aria-hidden="true"></div>
        
        {{-- Main Content Area --}}
        <div class="breadcrumb-content">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                
                {{-- Left Section: Navigation & Title --}}
                <div class="d-flex align-items-center gap-3 flex-grow-1 min-w-0">
                    
                    {{-- Elegant Back Button --}}
                    <a href="#"
                       onclick="event.preventDefault(); handleBackNavigation();"
                       class="modern-back-button"
                       title="Go back to previous page"
                       aria-label="Go back to previous page">
                        <span class="back-icon-wrapper">
                            <i class="material-icons material-symbols-rounded">arrow_back</i>
                        </span>
                        <span class="back-text">Back</span>
                        <span class="button-ripple"></span>
                    </a>
                    
                    {{-- Refined Separator --}}
                    <div class="breadcrumb-separator"></div>
                    
                    {{-- Title Section --}}
                    <div class="breadcrumb-title-wrapper">
                        <div class="title-indicator"></div>
                        <div class="title-content">
                            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                                <ol class="breadcrumb-trail">
                                    @foreach ($breadcrumbItems as $index => $item)
                                        @php $isLast = $index === count($breadcrumbItems) - 1; @endphp
                                        <li style="font-size: 16px;" class="trail-item {{ $isLast ? 'active' : '' }}" @if ($isLast) aria-current="page" @endif>
                                            @if (!$isLast && filled($item['url']))
                                                <a href="{{ $item['url'] }}" class="trail-link">{{ $item['label'] }}</a>
                                            @else
                                                <span class="{{ $isLast ? 'trail-current' : 'trail-label' }}">{{ $item['label'] }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            </nav>
                            <div class="title-underline"></div>
                        </div>
                    </div>
                </div>
                
                {{-- Right Section: Optional Status (can be removed if not needed) --}}
                <div class="breadcrumb-status d-none d-xl-flex">
                    <span class="status-badge">
                        <i class="material-icons material-symbols-rounded">check_circle</i>
                        <span>Active</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Elegant & Modern Styling --}}
<style>
    /* Main Wrapper */
    .modern-breadcrumb-wrapper {
        position: relative;
        animation: breadcrumbFadeIn 0.4s ease-out;
    }
    
    /* Premium Card */
    .modern-breadcrumb-card {
        position: relative;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 
            0 2px 8px rgba(0, 0, 0, 0.04),
            0 4px 16px rgba(0, 0, 0, 0.06),
            0 0 0 1px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .modern-breadcrumb-card:hover {
        box-shadow: 
            0 4px 16px rgba(0, 0, 0, 0.06),
            0 8px 24px rgba(0, 0, 0, 0.08),
            0 0 0 1px rgba(13, 110, 253, 0.15);
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
        background: radial-gradient(ellipse at top right, rgba(13, 110, 253, 0.05), transparent 70%);
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
            radial-gradient(circle at 20px 20px, rgba(13, 110, 253, 0.015) 1px, transparent 1px);
        background-size: 40px 40px;
    }
    
    /* Top Accent Line */
    .breadcrumb-accent-line {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #0d6efd 0%, #6366f1 50%, #8b5cf6 100%);
        transform-origin: left;
        animation: accentExpand 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Content Area */
    .breadcrumb-content {
        position: relative;
        padding: 1.5rem 1.75rem;
        z-index: 1;
    }
    
    @media (min-width: 768px) {
        .breadcrumb-content {
            padding: 1.75rem 2rem;
        }
    }
    
    @media (min-width: 1200px) {
        .breadcrumb-content {
            padding: 2rem 2.5rem;
        }
    }
    
    /* Modern Back Button */
    .modern-back-button {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1.25rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border: 1.5px solid #e9ecef;
        border-radius: 12px;
        color: #495057;
        font-weight: 600;
        font-size: 0.875rem;
        text-decoration: none;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }
    
    .modern-back-button:hover {
        background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
        border-color: #0d6efd;
        color: #ffffff;
        transform: translateX(-4px);
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
    }
    
    .modern-back-button:active {
        transform: translateX(-2px) scale(0.98);
    }
    
    .modern-back-button:focus-visible {
        outline: 2px solid #0d6efd;
        outline-offset: 2px;
    }
    
    .back-icon-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .back-icon-wrapper i {
        font-size: 1.125rem;
        font-weight: 500;
    }
    
    .modern-back-button:hover .back-icon-wrapper {
        transform: translateX(-3px);
    }
    
    .back-text {
        font-size: 0.875rem;
        letter-spacing: 0.01em;
    }
    
    @media (max-width: 575.98px) {
        .back-text {
            display: none;
        }
        .modern-back-button {
            padding: 0.625rem;
        }
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
    
    /* Elegant Separator */
    .breadcrumb-separator {
        width: 1px;
        height: 32px;
        background: linear-gradient(180deg, transparent 0%, #dee2e6 50%, transparent 100%);
        display: none;
    }
    
    @media (min-width: 992px) {
        .breadcrumb-separator {
            display: block;
        }
    }
    
    /* Title Wrapper */
    .breadcrumb-title-wrapper {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-grow: 1;
        min-width: 0;
    }
    
    /* Title Indicator */
    .title-indicator {
        width: 4px;
        height: 36px;
        background: linear-gradient(180deg, #0d6efd 0%, #6366f1 100%);
        border-radius: 4px;
        box-shadow: 0 0 12px rgba(13, 110, 253, 0.3);
        display: none;
        animation: indicatorPulse 2s ease-in-out infinite;
    }
    
    @media (min-width: 1024px) {
        .title-indicator {
            display: block;
        }
    }
    
    /* Title Content */
    .title-content {
        flex-grow: 1;
        min-width: 0;
    }

    .breadcrumb-nav {
        margin-bottom: 0.25rem;
    }

    .breadcrumb-trail {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.35rem;
        margin: 0 0 0.35rem;
        padding: 0;
        list-style: none;
        font-size: 0.8rem;
        font-weight: 500;
        color: #6c757d;
    }

    .trail-item {
        display: inline-flex;
        align-items: center;
    }

    .trail-item + .trail-item::before {
        content: ">";
        margin-right: 0.35rem;
        color: #9aa3ad;
        font-weight: 600;
    }

    .trail-link,
    .trail-label,
    .trail-current {
        color: inherit;
        text-decoration: none;
    }

    .trail-link {
        transition: color 0.2s ease;
    }

    .trail-link:hover,
    .trail-link:focus-visible {
        color: #0d6efd;
        text-decoration: underline;
        text-underline-offset: 0.15em;
    }

    .trail-item.active {
        color: #0d6efd;
        font-weight: 600;
    }
    
    .breadcrumb-title {
        margin: 0;
        font-size: clamp(1.125rem, 2vw, 1.625rem);
        font-weight: 700;
        color: #212529;
        line-height: 1.3;
        letter-spacing: -0.02em;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        transition: color 0.3s ease;
    }
    
    .modern-breadcrumb-card:hover .breadcrumb-title {
        color: #0d6efd;
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
    
    /* Status Badge */
    .breadcrumb-status {
        flex-shrink: 0;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, rgba(13, 110, 253, 0.08) 0%, rgba(99, 102, 241, 0.08) 100%);
        border: 1px solid rgba(13, 110, 253, 0.15);
        border-radius: 10px;
        color: #0d6efd;
        font-size: 0.8125rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        transition: all 0.3s ease;
    }
    
    .status-badge:hover {
        background: linear-gradient(135deg, rgba(13, 110, 253, 0.12) 0%, rgba(99, 102, 241, 0.12) 100%);
        border-color: rgba(13, 110, 253, 0.25);
    }
    
    .status-badge i {
        font-size: 1rem;
        font-weight: 500;
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
            box-shadow: 0 0 12px rgba(13, 110, 253, 0.3);
            opacity: 1;
        }
        50% {
            box-shadow: 0 0 20px rgba(13, 110, 253, 0.5);
            opacity: 0.85;
        }
    }
    
    /* Reduced Motion */
    @media (prefers-reduced-motion: reduce) {
        .modern-breadcrumb-wrapper,
        .modern-breadcrumb-card,
        .modern-back-button,
        .breadcrumb-title,
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
            background: white;
            box-shadow: none;
            border: 1px solid #dee2e6;
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
        .modern-breadcrumb-card {
            border-radius: 12px;
        }
        
        .breadcrumb-content {
            padding: 1.25rem 1rem;
        }
        
        .breadcrumb-title {
            font-size: 1.125rem;
        }

        .breadcrumb-trail {
            font-size: 0.75rem;
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
