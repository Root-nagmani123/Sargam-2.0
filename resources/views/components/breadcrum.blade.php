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
    'buttonClass' => 'ux4g-btn-primary ux4g-btn-s d-inline-flex align-items-center gap-2',
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
                'roles.*',
                'users.*',
                'admin.setup.quick_links.*',
                'admin.setup.useful_links.*',
                'course-repository.*',
            ],
            ['member*', 'faculty*', 'users*', 'roles*']
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

    // ── DB-driven trail from the menu's ACTUAL placement (preferred) ──────────
    // Builds Home / Category / Group / …parent menus / MenuName straight from
    // sidebar_categories → menu_groups → menus. Skipped when the caller passes
    // an explicit $items array or $section (those keep full manual control).
    $trailSource = $defaultTrail;
    if ((! is_array($items) || ! count($items)) && is_null($section)) {
        try {
            $dbTrail = app(\App\Services\SidebarMenu\SidebarNavResolver::class)->breadcrumb();
        } catch (\Throwable $e) {
            $dbTrail = [];
        }

        // Trust the DB trail only when it resolved past Home; otherwise fall back
        // to the legacy section-based trail for pages not yet in the menu tree.
        if (is_array($dbTrail) && count($dbTrail) > 1) {
            // Append the page title as the active crumb when it differs from the
            // resolved menu name (e.g. Create/Edit sub-pages under a list menu).
            //
            // Compared against the WHOLE trail, not just its last crumb: on Roles the
            // menu tree is "Role & Permission > Roles" while the page is titled
            // "Role & Permission", so a last-crumb-only check appended it a second
            // time and the trail read "… / Role & Permission / Roles / Role &
            // Permission". A title already somewhere in the trail is the page we are
            // on — there is nothing to add.
            $alreadyInTrail = collect($dbTrail)->contains(
                fn ($crumb) => strcasecmp(trim((string) ($crumb['label'] ?? '')), $normalizedTitle) === 0
            );

            if ($normalizedTitle !== '' && ! $alreadyInTrail) {
                $dbTrail[] = ['label' => $normalizedTitle, 'url' => null];
            }

            $trailSource = $dbTrail;
        }
    }

    $breadcrumbItems = collect(
        is_array($items) && count($items)
            ? array_values(array_filter($items, fn ($item) => filled($item)))
            : $trailSource
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
    <div class="modern-breadcrumb-shell rounded-1 px-3 px-md-4 py-3">
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
                    {{-- UX4G Breadcrumb (icon/"/" divider variant). Page title is the page's <h1> (WCAG 1.3.1 / GIGW). --}}
                    <nav aria-label="Breadcrumb" class="ux4g-breadcrumb ux4g-breadcrumb-icon mb-1">
                        <ol class="ux4g-breadcrumb-list d-flex align-items-center flex-wrap mb-0 ps-0 modern-breadcrumb-trail">
                            @foreach ($breadcrumbItems as $index => $item)
                                @php $isLast = $index === count($breadcrumbItems) - 1; @endphp
                                <li class="ux4g-breadcrumb-item{{ $isLast ? ' active' : '' }}"
                                    @if ($isLast) aria-current="page" @endif>
                                    @if (!$isLast && filled($item['url']))
                                        <a href="{{ $item['url'] }}" class="ux4g-breadcrumb-link">{{ $item['label'] }}</a>
                                    @else
                                        <span>{{ $item['label'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                    <h1 class="modern-breadcrumb-title mb-0">{{ $title }}</h1>
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
        background: var(--ux4g-bg-neutral-elevated);
        border: 1px solid var(--ux4g-border-color-neutral-subtle);
    }

    /* Trail typography comes from UX4G (14px medium); only the divider gap is tightened for the header card. */
    .modern-breadcrumb-trail.ux4g-breadcrumb-list { gap: var(--ux4g-space-2) 0; }
    .ux4g-breadcrumb-icon .modern-breadcrumb-trail .ux4g-breadcrumb-item + .ux4g-breadcrumb-item::before {
        margin: 0 var(--ux4g-space-3);
        font-size: var(--ux4g-font-size-14);
    }

    .modern-breadcrumb-title {
        font-size: var(--ux4g-font-size-20);
        font-weight: var(--ux4g-font-weight-bold);
        line-height: 1.15;
        color: var(--ux4g-text-neutral-primary);
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

    .modern-breadcrumb-action .btn {
        min-height: 2.5rem;
        border-radius: var(--ux4g-radius-sm);
        font-weight: 600;
        padding-inline: 1.15rem;
    }

    @media (max-width: 767.98px) {
        .modern-breadcrumb-wrapper .modern-breadcrumb-shell.rounded-1 {
            border-radius: var(--ux4g-radius-md) !important;
        }

        .modern-breadcrumb-action {
            width: 100%;
        }

        .modern-breadcrumb-action > * {
            width: 100%;
        }
    }
</style>
