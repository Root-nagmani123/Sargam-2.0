<!DOCTYPE html>
<html lang="en" data-bs-theme="light" data-layout="vertical">
@php
    $sidebarMenus = $sidebarMenus ?? collect();
    $activeNavMeta = $activeNavMeta ?? null;
    if (! isset($activeNavTab)) {
        $nav = app(\App\Services\SidebarMenu\SidebarNavResolver::class)->resolve();
        $activeNavTab = $nav['nav_tab'];
        $activeCategoryId = $activeCategoryId ?? $nav['category_id'];
        $activeGroupId = $activeGroupId ?? $nav['group_id'];
        $activeNavMeta = $nav;
    } else {
        $activeNavTab = $activeNavTab ?? \App\Services\SidebarMenu\SidebarNavResolver::HOME_TAB;
        $activeNavMeta = $activeNavMeta ?? [
            'nav_tab' => $activeNavTab,
            'category_id' => $activeCategoryId ?? null,
            'category_slug' => null,
            'group_id' => $activeGroupId ?? null,
            'menu_id' => null,
        ];
    }
    $activeCategoryId = $activeCategoryId
        ?? request()->get('category')
        ?? ($activeNavMeta['category_id'] ?? null)
        ?? ($sidebarMenus->first()?->id);
    $activeGroupId = $activeGroupId ?? ($activeNavMeta['group_id'] ?? null);
    $activeMenuId = $activeNavMeta['menu_id'] ?? null;
    $isDashboardPage = request()->routeIs('admin.dashboard') || request()->routeIs('admin.dashboard.*') || request()->is('dashboard');
    $activeCategory = $sidebarMenus->firstWhere('id', $activeCategoryId)
        ?? $sidebarMenus->first();
    if ($activeCategory) {
        $activeCategoryId = $activeCategory->id;
    }
    $groups = $activeCategory ? $activeCategory->groups : collect([]);

    $routeMatcher = app(\App\Services\SidebarMenu\MenuRouteMatcher::class);
    $categoryLandingUrls = ['#home' => route('admin.dashboard')];
    foreach ($sidebarMenus as $cat) {
        $tabHash = $cat->slug === 'home' ? '#home' : '#tab-' . $cat->slug;
        if ($tabHash === '#home') {
            continue;
        }
        $firstMenu = \App\Models\SidebarMenu\Menu::query()
            ->where('is_active', 1)
            ->where(function ($q) use ($cat) {
                $q->where('category_id', $cat->id)
                    ->orWhereIn('group_id', $cat->groups->pluck('id'));
            })
            ->whereNotNull('route')
            ->where('route', '!=', '')
            ->where('route', '!=', '#')
            ->orderBy('order')
            ->first(['id', 'route']);
        if ($firstMenu) {
            $href = $routeMatcher->resolveHref($firstMenu->route, $firstMenu->id);
            if ($href && !str_contains($href, 'navigation-error')) {
                $categoryLandingUrls[$tabHash] = $href;
            }
        }
    }

    // Decouple the page's @section name from the tab it appears in. The active tab
    // is resolved from the matched menu's own category (i.e. wherever its sidebar
    // <li> lives), but a view only renders into the pane whose section name it
    // happened to pick. So a page that declares @section('content') while its <li>
    // sits in the Setup tab would activate the Setup tab but leave it blank (its
    // content stuck in the hidden Home pane). Here we detect the single content
    // block the view actually defined — preferring the one that matches the active
    // tab, then falling back to whichever of the five is non-empty — and render it
    // into the active pane only. Result: a page always appears in the tab its menu
    // item belongs to, whatever section name the view used.
    $tabPaneSections = [
        '#home' => 'content',
        '#tab-setup' => 'setup_content',
        '#tab-communications' => 'communications_content',
        '#tab-academics' => 'academics_content',
        '#tab-material-management' => 'material_management_content',
    ];
    $activeTabSection = $tabPaneSections[$activeNavTab] ?? 'content';
    $resolvedPaneSection = null;
    if (trim($__env->yieldContent($activeTabSection)) !== '') {
        $resolvedPaneSection = $activeTabSection;
    } else {
        foreach ($tabPaneSections as $candidate) {
            if (trim($__env->yieldContent($candidate)) !== '') {
                $resolvedPaneSection = $candidate;
                break;
            }
        }
    }
    $resolvedPaneSection = $resolvedPaneSection ?? $activeTabSection;
@endphp


<head>
    <!-- Set initial theme from localStorage before paint (avoids flash) -->
    <script>
        (function () {
            'use strict';
            try {
                var saved = localStorage.getItem('bsTheme');
                if (saved === 'dark' || saved === 'light') {
                    document.documentElement.setAttribute('data-bs-theme', saved);
                }
            } catch (e) { }
        })();
    </script>

    @include('admin.layouts.pre_header')
    <title>@yield('title') {{ env('APP_TITLE_SUFFIX') }} - Sargam 2.0 | Lal Bahadur Shastri National Academy of
        Administration</title>
    @section('css')
    <style>
        .nav-item .tab-item .active {
            background-color: #bbd9f7;
            border-radius: 10px;
            color: #ffffff !important;
            transition: all 0.3s ease-in-out;
        }

        .mini-nav {
            display: flex;
            flex-direction: column;
        }

        .mini-nav-ul {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .mini-bottom {
            margin-top: auto !important;
        }

        /* Remove default Bootstrap dropdown arrow */
        .dropdown-toggle-custom::after {
            display: none !important;
        }

        /* Custom arrow icon animation */
        .dropdown-toggle-custom .dropdown-arrow {
            transition: transform 0.25s ease;
        }

        /* Rotate arrow when open */
        .show>.dropdown-toggle-custom .dropdown-arrow {
            transform: rotate(180deg);
        }

        .my-filled-icon {
            font-variation-settings: 'FILL' 1;
            /* Sets the fill to its maximum value (1) */
            color: blue;
            /* You can also change the color of the icon */
        }

        .my-unfilled-icon {
            font-variation-settings: 'FILL' 0;
            /* Sets the fill to its minimum value (0) */
        }
    </style>
    <style>
        .calendar {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

    .calendar th {
        background: #f8f9fa;
        padding: 0.5rem;
        text-align: center;
        font-weight: 600;
    }

    .calendar td {
        width: 14.28%;
        height: 65px;
        padding: 0.5rem;
        vertical-align: top;
        border: 1px solid #e5e5e5;
        text-align: right;
        position: relative;
    }

        .holiday {
            background-color: #ffe5e5 !important;
            border-left: 4px solid #dc3545 !important;
            font-weight: 600;
        }

    .holiday span {
        font-size: 11px;
        display: block;
        color: #dc3545;
        text-align: left;
        margin-top: 0.25rem;
    }

    /* Basic container */
    .calendar-component {
        max-width: 100%;
        background: #fff;
        border-radius: 12px;
        padding: 1rem;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
    }

        .calendar-header .form-select {
            max-width: 120px;
            border-radius: 8px;
            border: 1px solid #b30000;
        }


        .calendar-table {
            border-collapse: separate;
            border-spacing: 6px;
            table-layout: fixed;
        }

    .calendar-table th {
        font-weight: 600;
        padding: 0.5rem;
    }

    .calendar-table td {
        padding: 0.5rem;
        vertical-align: middle;
        border: none;
        text-align: center;
    }



        .calendar-cell {
            border-radius: 8px;
            transition: background .12s ease;
        }

    .calendar-cell:hover {
        background: #f7f7f7;
    }

        .calendar-cell:focus {
            outline: 3px solid #004a93;
            outline-offset: 2px;
        }


        .calendar-cell .day-number {
            display: inline-block;
            min-width: 28px;
        }

        .calendar-cell.is-selected {
            border: 2px solid #b30000;
            font-weight: 700;
        }

        .calendar-cell.has-event {
            background: #b30000;
            color: #fff;
            border-radius: 8px;
            font-weight: 700;
        }


        /* Themes */
        .calendar-component[data-theme="gov-blue"] .calendar-header .form-select {
            border-color: #004a93;
        }

        .calendar-component[data-theme="gov-blue"] .calendar-cell.is-selected {
            border-color: #004a93;
        }


    /* Responsive behavior */
    @media (max-width: 480px) {
        .calendar-component {
            padding: 0.5rem;
            max-width: 100%;
        }

        .calendar-header {
            gap: 0.5rem;
        }

        .calendar-table th,
        .calendar-table td {
            padding: 0.5rem 0.25rem;
        }
    }

        /* Wrapper */
        .modern-bottom-dd {
            position: relative;
        }

        /* Label */
        .dd-label {
            font-size: 0.95rem;
            color: #000;
        }

    /* Trigger */
    .dd-trigger {
        border: none;
        border-bottom: 1px solid #4c8ec5;
        /* Soft Blue like screenshot */
        border-radius: 10px;
        background: transparent;
        padding: 0.5rem 0 0.75rem 0;
        font-weight: 600;
        font-size: 1rem;
        min-height: 44px;
        /* GIGW Minimum touch target */
        cursor: pointer;
        transition: all .25s ease;
    }

        /* Hover */
        .dd-trigger:hover {
            border-bottom-color: #004a93;
        }

        /* Focus visible for accessibility */
        .dd-trigger:focus-visible {
            outline: none;
            border-bottom-color: #004a93 !important;
            box-shadow: 0 2px 0 0 #004a93;
        }

        /* Dropdown arrow rotation */
        .dropdown.show .dd-icon svg {
            transform: rotate(180deg);
            transition: .25s;
        }

    /* Menu */
    .dd-menu {
        border-radius: 10px;
        padding: 0.5rem 0;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        animation: fadeIn .15s ease-out;
    }

    /* Menu Items */
    .dd-menu-item {
        padding: 0.5rem 1rem;
        min-height: 40px;
        font-weight: 500;
    }

        /* Hover */
        .dd-menu-item:hover {
            background: #e8f3ff;
            color: #004a93;
            border-radius: 6px;
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pagination .page-item.active .page-link.current-page {
            border: 2px solid #0d6efd !important;
            border-radius: 8px !important;
            color: #0d6efd !important;
            font-weight: 600;
            background: transparent !important;
        }

        .pagination .page-item.disabled .page-link {
            color: #aaa;
        }

    .pagination li {
        margin-right: 0.25rem;
    }

        .pagination .page-link:hover {
            color: #0d6efd;
        }

        .search-expand {
            position: relative;
        }

        .search-input {
            width: 0;
            opacity: 0;
            padding: 0;
            transition: width .35s ease, opacity .25s ease;
            border-radius: 50rem;
            border: 1px solid #ced4da;
        }

        /* Expanded state */
        .search-input.active {
            width: 200px;
            /* You can increase this */
            opacity: 1;
            padding: .375rem .75rem;
        }

        /* Sidebar toggle icon rotation */
        #sidebarToggleIcon {
            transition: transform 0.3s ease-in-out;
            display: inline-block;
        }

        #sidebarToggleIcon.rotated {
            transform: rotate(180deg);
        }
    </style>

    {{-- Page-specific styles stack --}}
    @stack('styles')
</head>

<body data-sidebartype="full" @class(['has-dynamic-sidebar', 'admin-mess-module' => request()->routeIs('admin.mess.*')])>
    @include('admin.layouts.partials.skeleton-shell')

    <div id="main-wrapper">
        @include('admin.layouts.header_new')
        <div class="page-wrapper">
            @include('admin.layouts.sidebar_new')
            <div class="body-wrapper">
                <main id="main-content" tabindex="-1" role="main">
                @if(request()->routeIs('admin.mess.*'))
                    <div class="container-fluid pt-0">
                        <div class="mess-dt-stale-hint alert alert-warning border-0 shadow-sm rounded-3 mb-3 align-items-center justify-content-between flex-wrap gap-2 no-print" role="status">
                            <span class="small mb-0">Table data may be outdated after a long idle period. Click refresh or apply filters again.</span>
                            <button type="button" class="btn btn-sm btn-warning" id="messDtStaleRefreshBtn">Refresh data</button>
                        </div>
                    </div>
                @endif
                <!-- Tab Content Container -->
                {{-- The page's content is rendered ONLY into the active pane (the tab
                     its sidebar <li> resolves to), using the section the view actually
                     defined ($resolvedPaneSection). Inactive panes stay empty so the
                     page never renders twice (which would duplicate element IDs and
                     break DataTables). See the @php block above for the resolution. --}}
                <div class="tab-content" id="mainNavbarContent">
                    <!-- Home Tab -->
                    <div class="tab-pane fade {{ ($activeNavTab ?? '#home') === '#home' ? 'show active' : '' }}" id="home" role="tabpanel">
                        @if(($activeNavTab ?? '#home') === '#home') @yield($resolvedPaneSection) @endif
                    </div>

                        <!-- Setup Tab -->
                        <div class="tab-pane fade {{ ($activeNavTab ?? '#home') === '#tab-setup' ? 'show active' : '' }}"
                            id="tab-setup" role="tabpanel">
                            @if(($activeNavTab ?? '#home') === '#tab-setup') @yield($resolvedPaneSection) @endif
                        </div>

                        <!-- Communications Tab -->
                        <div class="tab-pane fade {{ ($activeNavTab ?? '#home') === '#tab-communications' ? 'show active' : '' }}"
                            id="tab-communications" role="tabpanel">
                            @if(($activeNavTab ?? '#home') === '#tab-communications') @yield($resolvedPaneSection) @endif
                        </div>

                        <!-- Academics Tab -->
                        <div class="tab-pane fade {{ ($activeNavTab ?? '#home') === '#tab-academics' ? 'show active' : '' }}"
                            id="tab-academics" role="tabpanel">
                            @if(($activeNavTab ?? '#home') === '#tab-academics') @yield($resolvedPaneSection) @endif
                        </div>

                        <!-- Material Management Tab -->
                        <div class="tab-pane fade {{ ($activeNavTab ?? '#home') === '#tab-material-management' ? 'show active' : '' }}"
                            id="tab-material-management" role="tabpanel">
                            @if(($activeNavTab ?? '#home') === '#tab-material-management') @yield($resolvedPaneSection) @endif
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    @include('admin.layouts.footer')
    <script src="{{ asset('js/forms.js') }}"></script>
    <script src="{{ asset('admin_assets/js/sidebar-navigation-fixed.js') }}"></script>
    <script src="{{ asset('admin_assets/js/sidebar-panel-accordion.js') }}?v=2"></script>
    <script src="{{ asset('admin_assets/js/tab-persistence.js') }}"></script>
    <script src="{{ asset('admin_assets/js/nav-state.js') }}"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if(request()->routeIs('admin.mess.*'))
        @include('admin.mess.partials.smooth-scroll')
        @include('admin.mess.partials.column-manager-auto-init')
    @endif
    @stack('scripts')
    {{-- Renders page JS placed in @section('scripts') (plural). Without this,
         @section('scripts') is silently dropped (only @stack('scripts') and
         @yield('script') singular existed). Pairs with @push('scripts'); no
         page uses both, so there is no double-render. --}}
    @yield('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('searchToggle');
            const input = document.getElementById('searchInput');
            if (!toggle || !input) return;

            toggle.addEventListener('click', () => {
                input.classList.toggle('active');
                if (input.classList.contains('active')) {
                    input.focus();
                }
            });

            // Close on outside click
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.search-expand')) {
                    input.classList.remove('active');
                }
            });
        });
    </script>
    <script src="{{ asset('js/sargam-skeleton-loader.js') }}?v={{ @filemtime(public_path('js/sargam-skeleton-loader.js')) ?: time() }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            try {
            const sidebar = document.getElementById("main-wrapper");
            const toggleBtn = document.getElementById("headerCollapse");
            if (!sidebar) return;
            // Query all icons across all tabs (multiple instances due to tab structure)
            const icons = document.querySelectorAll("#sidebarToggleIcon");
            const body = document.body;
            const sidebarmenus = document.querySelectorAll(".sidebarmenu");
            const isDynamicSidebar = body.classList.contains('has-dynamic-sidebar');
            const isDashboard = {{ (request()->routeIs('admin.dashboard') || request()->is('dashboard')) ? 'true' : 'false' }};

            // Helper: Safely adjust all DataTables after layout changes
            function adjustAllDataTables() {
                try {
                    if (window.jQuery && $.fn && $.fn.dataTable) {
                        // Adjust columns for all visible tables and recalc responsive layout
                        const api = $.fn.dataTable.tables({ visible: true, api: true });
                        if (api && api.columns) {
                            api.columns.adjust();
                            // Recalculate Responsive extension if available
                            if (api.responsive && api.responsive.recalc) {
                                api.responsive.recalc();
                            }
                            // Only redraw for client-side tables to avoid extra server calls
                            if (api.settings) {
                                const settings = api.settings();
                                let clientSideExists = false;
                                for (let i = 0; i < settings.length; i++) {
                                    if (!settings[i].oFeatures.bServerSide) {
                                        clientSideExists = true;
                                        break;
                                    }
                                }
                                if (clientSideExists) api.draw(false);
                            }
                        }
                    }
                } catch (err) {
                    console.warn('DataTables adjust failed after sidebar toggle:', err);
                }
            }

            // One-time migration: reset all users to expanded sidebar
            try {
                if (!localStorage.getItem('SidebarType_migrated_v2')) {
                    localStorage.setItem('SidebarType', 'full');
                    localStorage.setItem('SidebarType_migrated_v2', '1');
                }
            } catch (e) { }

            function applySidebarVisualState(type) {
                if (isDynamicSidebar) {
                    sidebarmenus.forEach(function (el) {
                        el.classList.toggle('close', type === 'mini-sidebar');
                    });
                    return;
                }
                if (type === 'mini-sidebar') {
                    sidebar.classList.remove('show-sidebar');
                    sidebarmenus.forEach(function (el) { el.classList.add('close'); });
                    icons.forEach(function (icon) {
                        icon.textContent = 'keyboard_double_arrow_right';
                        icon.classList.remove('rotated');
                    });
                } else {
                    sidebar.classList.add('show-sidebar');
                    sidebarmenus.forEach(function (el) { el.classList.remove('close'); });
                    icons.forEach(function (icon) {
                        icon.textContent = 'keyboard_double_arrow_right';
                        icon.classList.add('rotated');
                    });
                }
            }

            // Apply saved sidebar type; default expanded
            let sidebarType = 'full';
            try {
                const savedType = localStorage.getItem('SidebarType');
                sidebarType = savedType || 'full';
                if (!savedType) {
                    localStorage.setItem('SidebarType', 'full');
                }
            } catch (e) { }

            // Dashboard/Home: expanded by default (legacy sidebars only)
            const routeTab = window.SARGAM_ACTIVE_NAV_TAB || '#home';
            if (!isDynamicSidebar && (isDashboard || routeTab === '#home')) {
                sidebarType = 'full';
                body.setAttribute('data-sidebartype', 'full');
                try { localStorage.setItem('SidebarType', 'full'); } catch (e) { }
            } else {
                body.setAttribute('data-sidebartype', sidebarType);
            }

            // Initialize sidebar state on page load
            sidebarType = body.getAttribute('data-sidebartype');
            console.log('Initial sidebar type:', sidebarType);
            console.log('Icon elements found:', icons.length);

            applySidebarVisualState(sidebarType);
            setTimeout(adjustAllDataTables, 300);

            // Sync all icon instances with data-sidebartype changes and adjust tables after toggle
            function syncIconWithSidebar(type) {
                if (isDynamicSidebar) {
                    return;
                }
                const allIcons = document.querySelectorAll("#sidebarToggleIcon");
                allIcons.forEach(function (icon) {
                    icon.textContent = "keyboard_double_arrow_right";
                    if (type === "full") {
                        icon.classList.add("rotated");
                    } else {
                        icon.classList.remove("rotated");
                    }
                });
                console.log('Synced', allIcons.length, 'icon(s) to type:', type);
            }

            const observer = new MutationObserver(function (mutations) {
                if (window.__sargamSuppressSidebarObserver) {
                    return;
                }
                for (const m of mutations) {
                    if (m.attributeName === 'data-sidebartype') {
                        const t = body.getAttribute('data-sidebartype');
                        if (isDynamicSidebar) {
                            try { localStorage.setItem('SidebarType', t); } catch (e) { }
                            applySidebarVisualState(t);
                            setTimeout(adjustAllDataTables, 300);
                            continue;
                        }
                        syncIconWithSidebar(t);
                        try { localStorage.setItem('SidebarType', t); } catch (e) { }
                        applySidebarVisualState(t);
                        setTimeout(adjustAllDataTables, 300);
                    }
                }
            });
            observer.observe(body, { attributes: true, attributeFilter: ['data-sidebartype'] });

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function (e) {
                    if (!body.classList.contains('has-dynamic-sidebar')) {
                        return;
                    }
                    e.preventDefault();
                    if (typeof window.toggleDynamicSidebarMenu === 'function') {
                        window.toggleDynamicSidebarMenu();
                    }
                });
            }
            } catch (sidebarInitErr) {
                console.error('Sidebar init failed:', sidebarInitErr);
            } finally {
                if (typeof window.hideSargamLoader === 'function') {
                    window.hideSargamLoader();
                }
            }
        });
    </script>
    <script src="{{ asset('admin_assets/js/sidebar-dynamic-toggle.js') }}"></script>

    <!-- Final safeguard: Force light mode on window load -->
    <script>
        window.addEventListener('load', function () {
            // Force light mode one final time after everything loads
            document.documentElement.setAttribute('data-bs-theme', 'light');
            document.documentElement.style.colorScheme = 'light';
            document.documentElement.style.setProperty('--bs-body-bg', '#fff', 'important');
            document.documentElement.style.setProperty('--bs-body-color', '#212529', 'important');

            // Remove any dark mode classes
            document.documentElement.classList.remove('dark');
            if (document.body) {
                document.body.classList.remove('dark');
                document.body.style.colorScheme = 'light';
            }

            // Force reflow to apply styles
            document.documentElement.offsetHeight;
        });
    </script>
    
    <!-- @sidebar  scripts -->
    <script>
        function navAjaxContext() {
            return {
                current_path: window.location.pathname.replace(/^\//, ''),
                current_route: window.SARGAM_CURRENT_ROUTE_NAME || ''
            };
        }

        function markActiveSidebarMenuLink() {
            if (window.SargamNavState && window.SargamNavState.markActiveSidebarLinks) {
                window.SargamNavState.markActiveSidebarLinks();
            }
        }
        window.markActiveSidebarMenuLink = markActiveSidebarMenuLink;

        function selectSidebarGroupVisual(groupId) {
            $('#sidebar-setup .mini-nav-item').removeClass('selected');
            $('.sidebar-group-link').removeClass('selected').attr('aria-selected', 'false');
            if (!groupId) {
                return;
            }
            var $link = $('.sidebar-group-link[data-id="' + groupId + '"]');
            if (!$link.length) {
                return;
            }
            $link.closest('.mini-nav-item').addClass('selected');
            $link.addClass('selected').attr('aria-selected', 'true');
        }
        window.selectSidebarGroupVisual = selectSidebarGroupVisual;

        function clearSidebarGroupSelectedVisual() {
            $('#sidebar-setup .mini-nav-item').removeClass('selected');
            $('.sidebar-group-link').removeClass('selected').attr('aria-selected', 'false');
        }
        window.clearSidebarGroupSelectedVisual = clearSidebarGroupSelectedVisual;

        function clearSidebarGroupSelection() {
            clearSidebarGroupSelectedVisual();
            $('#sidebar-title').text('').removeClass('border-bottom');
            $('#sidebarnav').html(
                '<li class="sidebar-item sidebar-empty-state list-unstyled">'
                + '<div class="px-3 py-4 text-center">'
                + '<i class="material-icons material-symbols-rounded sidebar-empty-icon mb-2" aria-hidden="true">info</i>'
                + '<span class="sidebar-empty-message small fw-medium d-block">No active menu</span>'
                + '</div></li>'
            );
        }
        window.clearSidebarGroupSelection = clearSidebarGroupSelection;

        /* Sidebar skeletons — #sidebarnav and #sidebar-groups are filled by AJAX
           and would otherwise sit blank (or hold the PREVIOUS group's menu,
           which is worse: it looks like the click did nothing) until the
           response lands. Markup mirrors admin/layouts partials so the swap to
           real items doesn't shift the layout. */
        function sargamSidebarMenuSkeleton(count) {
            var html = '';
            for (var i = 0; i < (count || 7); i++) {
                html += '<li class="sidebar-item list-unstyled" aria-hidden="true">'
                    + '<span class="ds-skel-menu-item">'
                    + '<span class="ds-skeleton"></span>'
                    + '<span class="ds-skeleton"></span>'
                    + '</span></li>';
            }
            return html;
        }

        function sargamSidebarGroupsSkeleton(count) {
            var html = '';
            for (var i = 0; i < (count || 5); i++) {
                html += '<li class="mini-nav-item" aria-hidden="true">'
                    + '<span class="ds-skel-rail-item">'
                    + '<span class="ds-skeleton"></span>'
                    + '<span class="ds-skeleton"></span>'
                    + '</span></li>';
            }
            return html;
        }

        function ensureDynamicSidebarNavVisible() {
            if (typeof window.setDynamicSidebarMenuExpanded === 'function') {
                window.setDynamicSidebarMenuExpanded(true, false);
            }
            document.querySelectorAll('#sidebar-setup .sidebarmenu .sidebar-nav').forEach(function (nav) {
                nav.classList.add('d-block', 'left-none');
                nav.style.display = 'block';
                nav.style.visibility = 'visible';
            });
        }
        window.ensureDynamicSidebarNavVisible = ensureDynamicSidebarNavVisible;

        function loadSidebarMenusForGroup(groupId, groupName) {
            if (!groupId) return;
            window.SARGAM_ACTIVE_GROUP_ID = groupId;
            selectSidebarGroupVisual(groupId);
            ensureDynamicSidebarNavVisible();
            if (groupName) {
                $('#sidebar-title').text(groupName).addClass('border-bottom');
            }
            if (window.SargamNavState) {
                var tabHash = window.SargamNavState.getActiveTabHash
                    ? window.SargamNavState.getActiveTabHash()
                    : (window.SARGAM_ACTIVE_NAV_TAB || '#home');
                window.SargamNavState.persistTabState(
                    tabHash,
                    window.SARGAM_ACTIVE_CATEGORY_ID,
                    groupId
                );
            }
            $('#sidebarnav').html(sargamSidebarMenuSkeleton(7));
            $.ajax({
                url: '{{ route("sidebar.menu") }}',
                type: 'GET',
                data: $.extend({ group_id: groupId }, navAjaxContext()),
                success: function (response) {
                    $('#sidebarnav').html(response);
                    markActiveSidebarMenuLink();
                    selectSidebarGroupVisual(groupId);
                    ensureDynamicSidebarNavVisible();
                },
                error: function (xhr) {
                    console.error(xhr.responseText);
                    // Never leave the skeleton up as if data were still coming.
                    clearSidebarGroupSelection();
                }
            });
        }
        window.loadSidebarMenusForGroup = loadSidebarMenusForGroup;

        function loadSidebarGroupsForCategory(categoryId, done) {
            if (!categoryId) {
                if (typeof done === 'function') done();
                return;
            }
            $('#sidebar-groups').html(sargamSidebarGroupsSkeleton(5));
            $.ajax({
                url: '{{ route("sidebar.groups") }}',
                type: 'GET',
                data: {
                    category_id: categoryId,
                    active_group_id: window.SARGAM_ACTIVE_GROUP_ID || null
                },
                success: function (response) {
                    $('#sidebar-groups').html(response);
                    if (window.SARGAM_ACTIVE_GROUP_ID) {
                        selectSidebarGroupVisual(window.SARGAM_ACTIVE_GROUP_ID);
                    }
                    if (typeof done === 'function') done();
                },
                error: function (xhr) {
                    console.error(xhr.responseText);
                    // Drop the skeleton; a stuck one implies data is still loading.
                    $('#sidebar-groups').empty();
                    if (typeof done === 'function') done();
                }
            });
        }
        window.loadSidebarGroupsForCategory = loadSidebarGroupsForCategory;

        // Header category tabs: nav-state.js (capture handler, no Bootstrap tab toggle)

        $(document).on('click', '.sidebar-group-link', function(e){
            e.preventDefault();
            var groupId = $(this).data('id');
            var groupName = $(this).data('name');
            selectSidebarGroupVisual(groupId);
            window.SARGAM_ACTIVE_GROUP_ID = groupId;
            loadSidebarMenusForGroup(groupId, groupName);
        });

        // Automatically pass scope parameter from URL to all DataTables ajax requests
        $(document).on('preXhr.dt', function(e, settings, data) {
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('scope')) {
                data.scope = urlParams.get('scope') || "";
            }
        });

        $(function () {
            var categoryId = window.SARGAM_ACTIVE_CATEGORY_ID;
            var groupId = window.SARGAM_ACTIVE_GROUP_ID;
            var routeTab = window.SARGAM_ACTIVE_NAV_TAB;

            if (routeTab && typeof window.showMainNavPane === 'function') {
                window.showMainNavPane(routeTab);
            }

            if (!categoryId) return;

            $('.sidebar-category-link').removeClass('active').attr('aria-selected', 'false');
            $('.sidebar-category-link[data-id="' + categoryId + '"]')
                .addClass('active')
                .attr('aria-selected', 'true');

            loadSidebarGroupsForCategory(categoryId, function () {
                if (groupId) {
                    selectSidebarGroupVisual(groupId);
                    loadSidebarMenusForGroup(groupId);
                    return;
                }

                // No specific group resolved for this page. This happens on child
                // pages whose URL isn't itself a menu and doesn't sit under the
                // parent menu's path (e.g. Assign Permission at /roles/{id} while
                // the "Roles" menu points at admin/roles). Keep the sidebar
                // populated instead of blank: prefer the group the user last had
                // open on this tab (continuity from the listing page they came
                // from), otherwise fall back to the first group in the category.
                var fallbackGroupId = null;
                if (window.SargamNavState && window.SargamNavState.getLastVisitedGroupId) {
                    fallbackGroupId = window.SargamNavState.getLastVisitedGroupId(routeTab || '#tab-setup');
                }

                var $fallback = fallbackGroupId
                    ? $('#sidebar-groups .sidebar-group-link[data-id="' + fallbackGroupId + '"]')
                    : $();
                if (!$fallback.length) {
                    $fallback = $('#sidebar-groups .sidebar-group-link').first();
                    fallbackGroupId = $fallback.data('id');
                }

                if ($fallback.length && fallbackGroupId) {
                    window.SARGAM_ACTIVE_GROUP_ID = fallbackGroupId;
                    selectSidebarGroupVisual(fallbackGroupId);
                    loadSidebarMenusForGroup(fallbackGroupId, $fallback.data('name'));
                } else {
                    clearSidebarGroupSelection();
                }
            });

            ensureDynamicSidebarNavVisible();
        });
    </script>
  @yield('script')

    @auth
    <script>
    (function () {
        var POPUP_KEY = 'fac_feedback_popup_' + (new Date().toDateString());
        var feedbackUrl = '{{ route('feedback.get.facultyInternalFeedback') }}';
        var countUrl    = '{{ route('feedback.faculty.pendingCount') }}';

        function loadFeedbackBell() {
            fetch(countUrl, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
                }
            })
            .then(function (r) { if (!r.ok) return null; return r.json(); })
            .then(function (data) {
                if (!data || !data.count) return;
                var count = data.count;
                var wrap  = document.getElementById('facultyFeedbackBellWrap');
                var badge = document.getElementById('facultyFeedbackBadge');
                var list  = document.getElementById('facultyFeedbackList');
                if (!wrap) return;
                wrap.classList.remove('d-none');
                badge.textContent = count > 9 ? '9+' : count;
                badge.style.display = '';
                list.innerHTML = '';
                (data.items || []).slice(0, 5).forEach(function (item) {
                    var li = document.createElement('li');
                    li.className = 'border-bottom';
                    li.innerHTML = '<a href="' + feedbackUrl + '" class="d-block px-3 py-2 text-decoration-none text-dark">' +
                        '<div class="fw-semibold text-truncate" style="font-size:13px;max-width:260px">' + (item.main_faculty_name || '') + '</div>' +
                        '<div class="text-muted text-truncate" style="font-size:12px;max-width:260px">' + (item.subject_topic || item.course_name || '') + '</div>' +
                        '<div class="text-muted" style="font-size:11px">' + (item.from_date || '') + ' &bull; ' + (item.class_session || '') + '</div>' +
                        '</a>';
                    list.appendChild(li);
                });
                if (!sessionStorage.getItem(POPUP_KEY) && typeof Swal !== 'undefined') {
                    sessionStorage.setItem(POPUP_KEY, '1');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Pending Feedback',
                        html: 'You have <strong>' + count + '</strong> pending feedback session' + (count > 1 ? 's' : '') + ' awaiting your response.',
                        confirmButtonText: 'Give Feedback Now',
                        confirmButtonColor: '#b30000',
                        showCancelButton: true,
                        cancelButtonText: 'Later',
                    }).then(function (result) {
                        if (result.isConfirmed) window.location.href = feedbackUrl;
                    });
                }
            })
            .catch(function (err) { console.warn('Faculty feedback bell error:', err); });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadFeedbackBell);
        } else {
            loadFeedbackBell();
        }
    })();
    </script>
    @endauth
</body>

</html>