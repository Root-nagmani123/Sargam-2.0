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

    // Auto-detect if back button should be shown (create/edit/show pages, not index)
    $showBack = \Illuminate\Support\Str::is(
        ['*.create', '*.edit', '*.show', '*.update', '*.view'],
        $routeName ?? ''
    );
@endphp

<div class="modern-breadcrumb-wrapper mb-3" data-variant="{{ $variant }}">
    <div class="bg-white rounded-1" style="padding: 1rem;">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div class="d-flex min-w-0 flex-grow-1" style="gap:.65rem;">
                @if($showBack)
                <a href="javascript:void(0)" onclick="handleBackNavigation()"
                   style="color:#333;text-decoration:none;display:inline-flex;align-items:center;line-height:1;margin-top:1.35rem;flex-shrink:0;"
                   aria-label="Go back" title="Go back">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                </a>
                @endif
                <div class="min-w-0">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 flex-wrap"
                            style="--bs-breadcrumb-divider: '/'; --bs-breadcrumb-item-padding-x: 0.3rem; font-size: .78rem;">
                            @foreach ($breadcrumbItems as $index => $item)
                                @php $isLast = $index === count($breadcrumbItems) - 1; @endphp
                                <li class="breadcrumb-item{{ $isLast ? ' active' : '' }}"
                                    @if ($isLast) aria-current="page" @endif>
                                    @if (!$isLast && filled($item['url']))
                                        <a href="{{ $item['url'] }}" style="color:#999;text-decoration:none;">
                                            {{ $item['label'] }}
                                        </a>
                                    @else
                                        @if ($isLast)
                                            <span style="color:#333;font-weight:600;">{{ $item['label'] }}</span>
                                        @else
                                            <span style="color:#999;">{{ $item['label'] }}</span>
                                        @endif
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                    <h1 style="font-size:1.25rem;font-weight:700;color:#222;margin:0;line-height:1.3;">{{ $pageHeading }}</h1>
                </div>
            </div>
            @if($slot->isNotEmpty())
            <div class="d-flex align-items-center gap-1 flex-shrink-0" style="margin-top:.75rem;">
                {{ $slot }}
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    .modern-breadcrumb-wrapper .breadcrumb {
        background: none;
        padding: 0;
    }

    .modern-breadcrumb-wrapper .breadcrumb-item + .breadcrumb-item::before {
        color: #bbb;
    }

    .modern-breadcrumb-wrapper .breadcrumb-item.active {
        color: inherit;
    }

    @media print {
        .modern-breadcrumb-card {
            box-shadow: none !important;
            border: 1px solid var(--bs-border-color) !important;
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
