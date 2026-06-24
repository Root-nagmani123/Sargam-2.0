{{-- Clean Bootstrap 5.3 Breadcrumb/Header Component --}}
@props([
    'title' => 'Page',
    'variant' => 'glass',
    'items' => null,
    'section' => null,
    'showBack' => null,
    'buttonText' => null,
    'buttonUrl' => null,
    'buttonId' => null,
    'buttonIcon' => 'add',
    'buttonClass' => 'btn btn-primary btn-sm d-inline-flex align-items-center gap-2',
])
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

    $defaultTrail = ['Home', $resolvedSection, $title];

    $normalizedTitle = trim((string) $title);
    $isInnerPage = \Illuminate\Support\Str::contains(strtolower($routeName ?? ''), ['create', 'edit', 'show', 'detail', 'view'])
        || \Illuminate\Support\Str::contains(strtolower($requestPath ?? ''), ['/create', '/edit', '/show', '/detail', '/view']);

    // Module-specific explicit parent labels (keeps exact naming where needed).
    $explicitParentTitle = null;
    if (\Illuminate\Support\Str::is(['programme.*', 'admin.programme.*'], $routeName ?? '')) {
        $explicitParentTitle = 'Course Master';
        $isInnerPage = $isInnerPage || \Illuminate\Support\Str::contains(strtolower($normalizedTitle), ['create', 'edit', 'course']);
    }

    if ($isInnerPage) {
        $derivedParent = trim((string) preg_replace(
            '/^(create|edit|add|update|view|show|details?|generate|request\s+for|request|log\s+new)\s+/i',
            '',
            $normalizedTitle
        ));
        $derivedParent = trim((string) preg_replace(
            '/\s+(create|edit|details?|detail|view|show)$/i',
            '',
            $derivedParent
        ));

        $parentTitle = $explicitParentTitle ?: $derivedParent;

        if (filled($parentTitle) && strcasecmp($parentTitle, $normalizedTitle) !== 0) {
            $defaultTrail = ['Home', $resolvedSection, $parentTitle, $normalizedTitle];
        }
    }

    $breadcrumbItems = collect(
        is_array($items) && count($items)
            ? array_values(array_filter($items, fn ($item) => filled($item)))
            : $defaultTrail
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
        ->map(function ($item) {
            if (strtolower($item['label']) === 'home' && is_null($item['url'])) {
                $item['url'] = route('admin.dashboard');
            }
            return $item;
        })
        ->values()
        ->all();

    $autoShowBack = \Illuminate\Support\Str::contains(strtolower($routeName ?? ''), ['create', 'edit', 'show', 'detail', 'view'])
        || count($breadcrumbItems) > 3;
    $showBackButton = is_null($showBack) ? $autoShowBack : (bool) $showBack;
    $hasSlotAction = trim((string) ($slot ?? '')) !== '';
    $hasButtonAction = filled($buttonText);
@endphp

<div class="modern-breadcrumb-wrapper mb-4" data-variant="{{ $variant }}">
    <div class="modern-breadcrumb-shell rounded-4 px-3 px-md-4 py-3">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div class="min-w-0 flex-grow-1 d-flex align-items-center gap-2 gap-md-3">
                @if($showBackButton)
                    <button type="button"
                            class="btn btn-sm d-inline-flex align-items-center justify-content-center flex-shrink-0 modern-breadcrumb-back"
                            onclick="window.history.back();"
                            aria-label="Go back">
                        <i class="material-icons material-symbols-rounded" aria-hidden="true">arrow_back</i>
                    </button>
                @endif
                <div class="min-w-0 flex-grow-1">
                    <nav aria-label="breadcrumb" class="mb-1">
                        <ol class="breadcrumb mb-0 small text-body-secondary align-items-center flex-wrap modern-breadcrumb-trail"
                            style="--bs-breadcrumb-divider: '/';">
                            @foreach ($breadcrumbItems as $index => $item)
                                @php $isLast = $index === count($breadcrumbItems) - 1; @endphp
                                <li class="breadcrumb-item{{ $isLast ? ' active' : '' }}"
                                    @if ($isLast) aria-current="page" @endif>
                                    @if (!$isLast && filled($item['url']))
                                        <a href="{{ $item['url'] }}"
                                           class="link-secondary text-decoration-none">
                                            {{ $item['label'] }}
                                        </a>
                                    @else
                                        <span class="{{ $isLast ? 'text-dark fw-medium' : '' }}">{{ $item['label'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                    <h4 class="modern-breadcrumb-title mb-0 text-dark">{{ $title }}</h4>
                </div>
            </div>

            @if($hasSlotAction || $hasButtonAction)
                <div class="d-flex align-items-center ms-auto modern-breadcrumb-action">
                    @if($hasSlotAction)
                        {{ $slot }}
                    @else
                        <a href="{{ $buttonUrl ?: 'javascript:void(0)' }}"
                           @if(filled($buttonId)) id="{{ $buttonId }}" @endif
                           class="{{ $buttonClass }}"
                           @if(filled($buttonId) && !filled($buttonUrl)) role="button" @endif>
                            @if(filled($buttonIcon))
                                <i class="material-icons material-symbols-rounded" style="font-size: 18px;" aria-hidden="true">{{ $buttonIcon }}</i>
                            @endif
                            <span>{{ $buttonText }}</span>
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .modern-breadcrumb-wrapper {
        position: relative;
    }

    .modern-breadcrumb-shell {
        background: #ffffff;
        border: 1px solid #eef1f4;
        transition: box-shadow 0.2s ease;
    }

    .modern-breadcrumb-shell:hover {
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
    }

    .modern-breadcrumb-trail {
        --bs-breadcrumb-item-padding-x: 0.35rem;
        font-size: 0.925rem;
    }

    .modern-breadcrumb-trail .breadcrumb-item + .breadcrumb-item::before {
        color: var(--bs-secondary-color);
        font-weight: 600;
    }

    .modern-breadcrumb-title {
        font-size: 1.25rem;
        font-weight: 700;
        line-height: 1.15;
        letter-spacing: -0.01em;
    }

    .modern-breadcrumb-back {
        width: 2rem;
        height: 2rem;
        color: #1f2937;
        border: 0 !important;
        background: transparent !important;
        border-radius: 0.25rem;
        padding: 0;
        margin-top: 0.1rem;
        line-height: 1;
    }

    .modern-breadcrumb-back i {
        font-size: 1.55rem;
        font-weight: 700;
    }

    .modern-breadcrumb-back:hover {
        color: var(--bs-primary);
        background: rgba(var(--bs-primary-rgb), 0.08) !important;
    }

    .modern-breadcrumb-back:focus-visible {
        box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.2);
    }

    .modern-breadcrumb-action .btn {
        min-height: 2.5rem;
        border-radius: 0.6rem;
        font-weight: 600;
        padding-inline: 1.15rem;
    }

    @media (prefers-reduced-motion: reduce) {
        .modern-breadcrumb-shell {
            transition: none !important;
        }
    }

    @media (max-width: 767.98px) {
        .modern-breadcrumb-wrapper .modern-breadcrumb-shell.rounded-4 {
            border-radius: var(--bs-border-radius-xl) !important;
        }

        .modern-breadcrumb-trail {
            font-size: 0.8125rem;
        }

        .modern-breadcrumb-action {
            width: 100%;
        }

        .modern-breadcrumb-action > * {
            width: 100%;
        }
    }
</style>
