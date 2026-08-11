<!-- Tab Content Container -->
<!-- //admin tabs -->
<div class="tab-content" id="sidebarTabContent">
    <div class="tab-pane fade show active" id="sidebar-setup" role="tabpanel" aria-labelledby="setup-tab"
        data-sidebar-layout="dynamic">
        <aside class="side-mini-panel with-vertical sidebar-google-style">
            <div class="vh-100 d-flex flex-column overflow-visible">
                <!-- ---------------------------------- -->
                <!-- Start Vertical Layout Sidebar -->
                <!-- ---------------------------------- -->
                <div class="iconbar flex-fill d-flex flex-column overflow-visible" style="min-height: 0;">
                    <div class="flex-fill d-flex flex-row overflow-visible" style="min-height: 0;">
                        <div class="mini-nav flex-fill d-flex flex-column" style="min-height: 0;">
                            <div class="sidebar-google-hamburger flex-shrink-0 px-1 pt-2 pb-1">
                                <button type="button"
                                    class="sidebar-menu-toggler w-100 d-flex flex-column align-items-center justify-content-center rounded-3 border-0 bg-transparent p-2"
                                    id="sidebarMenuCollapse" aria-label="Collapse sidebar menu" aria-expanded="true"
                                    aria-controls="sidebar-setup-menu">
                                    <span
                                        class="sidebar-google-icon-wrap d-flex align-items-center justify-content-center">
                                        <i class="material-icons material-symbols-rounded sidebar-menu-toggle-icon"
                                            id="sidebarToggleIcon" aria-hidden="true">left_panel_close</i>
                                    </span>
                                    <span class="sidebar-google-label">Close</span>
                                </button>
                            </div>
                            <ul class="mini-nav-ul simplebar-scrollable-y flex-fill" data-simplebar="init"
                                style="min-height: 0;">
                                <div class="simplebar-wrapper" style="margin: 0px;">
                                    <div class="simplebar-height-auto-observer-wrapper">
                                        <div class="simplebar-height-auto-observer"></div>
                                    </div>
                                    <div class="simplebar-mask">
                                        <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                                            <div class="simplebar-content-wrapper sidebar-groups" tabindex="0"
                                                role="region" aria-label="scrollable content"
                                                style="height: 100%; overflow: hidden scroll;">
                                                <div class="simplebar-content p-0" id="sidebar-groups">
                                                    <ul class="sidebar-groups-list">
                                                        @foreach ($groups as $group)
                                                        @php
                                                        $groupSelected = ($activeGroupId ?? null) == $group->id;
                                                        // Auto-select first group if none is active
                                                        if (!$groupSelected && !($activeGroupId ?? null) && $loop->first) {
                                                            $groupSelected = true;
                                                        }
                                                        @endphp
                                                        <li class="sidebar-group-item mini-nav-item py-2 {{ $groupSelected ? 'selected' : '' }}"
                                                            id="{{ $group->id }}" data-id="{{ $group->id }}">
                                                            <a href="javascript:void(0)"
                                                                class="d-flex flex-column align-items-center justify-content-center rounded-3 sidebar-group-link sidebar-google-item {{ $groupSelected ? 'selected' : '' }}"
                                                                data-id="{{ $group->id }}"
                                                                data-name="{{ $group->name }}"
                                                                aria-selected="{{ $groupSelected ? 'true' : 'false' }}">
                                                                <span
                                                                    class="sidebar-google-icon-wrap d-flex align-items-center justify-content-center">
                                                                    <i
                                                                        class="material-icons menu-icon material-symbols-rounded">{{ $group->icon }}</i>
                                                                </span>
                                                                <span
                                                                    class="sidebar-google-label">{{ $group->name }}</span>
                                                            </a>
                                                        </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="simplebar-placeholder"
                                        style="width: 80px; min-width: 80px; height: 537px;"></div>
                                </div>
                                <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
                                    <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
                                </div>
                                <div class="simplebar-track simplebar-vertical" style="visibility: visible;">
                                    <div class="simplebar-scrollbar"
                                        style="height: 75px; display: block; transform: translate3d(0px, 0px, 0px);">
                                    </div>
                                </div>
                            </ul>
                        </div>
                        <div class="sidebarmenu" id="sidebar-setup-menu" style="background:#ffffff !important;">
                            <nav class="sidebar-nav d-block left-none simplebar-scrollable-y" data-simplebar="init" style="background:#ffffff !important;">
                                <div class="simplebar-wrapper" style="margin: 0px;">
                                    <div class="simplebar-height-auto-observer-wrapper">
                                        <div class="simplebar-height-auto-observer">
                                        </div>
                                    </div>
                                    <div class="simplebar-mask">
                                        <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                                            <div class="simplebar-content-wrapper" tabindex="0" role="region"
                                                aria-label="scrollable content"
                                                style="height: 100%; overflow: hidden scroll;">
                                                <div class="simplebar-content" style="padding: 16px 12px 24px 12px;">
                                                    <h2 class="text-light fs-6 px-3 pb-2" id="sidebar-title"></h2>
                                                    <ul class="sidebar-menu" id="sidebarnav"></ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="simplebar-placeholder" style="width: 240px; height: 864px;"></div>
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <style>
        /* Google-style sidebar - clean, modern icon-above-text layout */
        #sidebar-setup .sidebar-google-style.side-mini-panel {
            width: auto;
            min-width: var(--sargam-sidebar-total-width, 365px);
            overflow: visible !important;
            transition: min-width 0.25s ease, width 0.25s ease;
        }

        body.has-dynamic-sidebar[data-sidebartype="mini-sidebar"] #sidebar-setup .sidebar-google-style.side-mini-panel {
            min-width: var(--sargam-sidebar-mini-width, 92px) !important;
            width: var(--sargam-sidebar-mini-width, 92px) !important;
        }

        #sidebar-setup .sidebar-google-style .mini-nav {
            background: #ffffff !important;
            border: none;
            border-right: 1px solid #eaecf0;
            border-top: 1px solid #eaecf0;
            padding: 4px 0;
            border-radius: 10px;
        }

        #sidebar-setup .sidebar-google-style .sidebar-google-hamburger {
            padding: 0;
            margin: 0 0 2px 0;
        }

        #sidebar-setup .sidebar-google-style .sidebar-google-hamburger .sidebartoggler,
        #sidebar-setup .sidebar-google-style .sidebar-google-hamburger .sidebar-menu-toggler {
            color: #374151 !important;
            cursor: pointer;
        }

        #sidebar-setup .sidebar-google-style .sidebar-google-hamburger .sidebar-menu-toggler:hover .material-icons,
        #sidebar-setup .sidebar-google-style .sidebar-google-hamburger .sidebar-menu-toggler:hover .sidebar-google-label {
            color: #111827 !important;
        }

        /* Keyboard highlight: applied when the toggle button is focused via Esc */
        #sidebar-setup .sidebar-google-style .sidebar-google-hamburger .sidebar-menu-toggler.is-key-highlight,
        #sidebar-setup .sidebar-google-style .sidebar-google-hamburger .sidebar-menu-toggler:focus-visible {
            outline: none;
        }

        #sidebar-setup .sidebar-google-style .sidebar-google-hamburger .sidebar-menu-toggler.is-key-highlight .sidebar-google-icon-wrap,
        #sidebar-setup .sidebar-google-style .sidebar-google-hamburger .sidebar-menu-toggler:focus-visible .sidebar-google-icon-wrap {
            background: #e0e7ff !important;
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.35);
        }

        #sidebar-setup .sidebar-google-style .sidebar-google-hamburger .sidebar-menu-toggler.is-key-highlight .material-icons,
        #sidebar-setup .sidebar-google-style .sidebar-google-hamburger .sidebar-menu-toggler.is-key-highlight .sidebar-google-label,
        #sidebar-setup .sidebar-google-style .sidebar-google-hamburger .sidebar-menu-toggler:focus-visible .material-icons,
        #sidebar-setup .sidebar-google-style .sidebar-google-hamburger .sidebar-menu-toggler:focus-visible .sidebar-google-label {
            color: #4338ca !important;
        }

        /* Collapse button icon-wrap - subtle grey box like reference */
        #sidebar-setup .sidebar-google-style .sidebar-google-hamburger .sidebar-google-icon-wrap {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #f3f4f6 !important;
            border: 1px solid #e5e7eb;
        }

        #sidebar-setup .sidebar-google-style .sidebar-menu-toggle-icon {
            transition: transform 0.25s ease;
        }

        #sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item {
            list-style: none;
            display: flex !important;
            justify-content: center !important;
            min-height: auto;
            overflow: visible;
            padding: 4px 0 !important;
        }

        #sidebar-setup .sidebar-google-style .mini-nav ul.mini-nav-ul {
            padding-inline-start: 0 !important;
            list-style: none !important;
        }

        #sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item>a {
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 8px 4px !important;
            margin: 0 6px !important;
            background: transparent !important;
            height: auto !important;
            min-height: auto;
            width: 100%;
            border-radius: 12px;
            transition: background-color 0.18s ease;
            text-decoration: none !important;
        }

        #sidebar-setup .sidebar-google-style .sidebar-google-item {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 5px;
            text-align: center !important;
        }

        /* Icon wrap - NO background for unselected items (matching reference) */
        #sidebar-setup .sidebar-google-style .sidebar-google-icon-wrap {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 40px;
            height: 40px;
            margin-inline: auto;
            border-radius: 10px;
            background: transparent;
            border: none;
            transition: all 0.18s ease;
        }

        #sidebar-setup .sidebar-google-style .sidebar-google-icon-wrap .material-icons {
            line-height: 1 !important;
            vertical-align: middle !important;
        }

        #sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item>a .material-icons {
            font-size: 22px !important;
            color: #374151 !important;
        }

        #sidebar-setup .sidebar-google-style .sidebar-google-label {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            font-size: 10.5px;
            font-weight: 500;
            text-align: center;
            line-height: 1.25;
            max-width: 72px;
            margin-top: 0;
            color: #374151 !important;
            word-break: break-word;
            letter-spacing: 0.01em;
        }

        /* Hover state - subtle background */
        #sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item>a:hover {
            background: #f9fafb !important;
        }

        #sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item>a:hover .material-icons {
            color: #111827 !important;
        }

        #sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item>a:hover .sidebar-google-label {
            color: #111827 !important;
        }

        #sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item>a:focus-visible {
            outline: 2px solid rgba(0, 74, 147, 0.35);
            outline-offset: 2px;
        }

        /* Selected / Active state - blue rounded square on icon only */
        #sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item.selected>a.sidebar-group-link {
            background: transparent !important;
        }

        #sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item.selected>a .sidebar-google-icon-wrap {
            background: #dbeafe !important;
            border: none;
            border-radius: 12px;
            padding: 0;
            box-shadow: 0 1px 4px rgba(59, 130, 246, 0.12);
            width: 44px;
            height: 44px;
            margin-inline: auto;
            flex-shrink: 0;
        }

        #sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item.selected>a .material-icons {
            color: #2563eb !important;
            font-size: 22px !important;
        }

        #sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item.selected>a .sidebar-google-label {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            color: #1d4ed8 !important;
            font-weight: 600;
        }

        #sidebar-setup .sidebar-google-style .mini-nav .mini-nav-item.selected>a:before {
            display: none !important;
        }

        /* ==========================================
           SIDEBAR MENU PANEL - Right side expanded menu
           Matches reference: clean, spacious, modern
           ========================================== */

        /* Menu panel container - FORCE white background at every level */
        #sidebar-setup .sidebar-google-style .sidebarmenu,
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-nav,
        #sidebar-setup .sidebar-google-style .sidebarmenu nav,
        #sidebar-setup .sidebar-google-style .sidebarmenu .simplebar-content-wrapper,
        #sidebar-setup .sidebar-google-style .sidebarmenu .simplebar-content,
        #sidebar-setup .sidebar-google-style .sidebarmenu .simplebar-wrapper,
        #sidebar-setup .sidebar-google-style .sidebarmenu .simplebar-mask,
        #sidebar-setup .sidebar-google-style .sidebarmenu .simplebar-offset,
        #sidebar-setup .sidebar-google-style #sidebar-setup-menu,
        #sidebar-setup aside.side-mini-panel .sidebarmenu,
        .sidebar-google-style .sidebarmenu,
        .sidebar-google-style .sidebarmenu *:not(i):not(span):not(a):not(li):not(ul):not(h2):not(button) {
            background: #ffffff !important;
            background-color: #ffffff !important;
        }

        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-nav {
            padding: 0 !important;
            background: #ffffff !important;
        }

        /* Section title - uppercase, muted, letter-spaced */
        #sidebar-setup .sidebar-google-style .sidebarmenu #sidebar-title,
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-menu-title,
        #sidebar-setup .sidebar-google-style .sidebarmenu h2,
        #sidebar-setup .sidebar-google-style .sidebarmenu .text-light {
            font-size: 0.6875rem !important;
            font-weight: 500 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.1em !important;
            color: #9ca3af !important;
            padding: 16px 16px 10px 16px !important;
            margin: 0 !important;
            border-bottom: 1px solid #f3f4f6 !important;
        }

        /* Menu list reset */
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-menu,
        #sidebar-setup .sidebar-google-style .sidebarmenu #sidebarnav {
            list-style: none !important;
            padding: 0 8px !important;
            margin: 0 !important;
        }

        /* Parent menu items */
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-item {
            margin: 1px 0 !important;
            list-style: none !important;
        }

        /* Sidebar links - parent level */
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            padding: 11px 12px !important;
            border-radius: 8px !important;
            color: #1f2937 !important;
            text-decoration: none !important;
            font-size: 0.8125rem !important;
            font-weight: 500 !important;
            transition: background-color 0.15s ease, color 0.15s ease !important;
            position: relative !important;
            line-height: 1.4 !important;
        }

        /* Sidebar link icons */
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link .material-icons,
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link .material-symbols-rounded,
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link i {
            font-size: 20px !important;
            color: #6b7280 !important;
            flex-shrink: 0 !important;
            width: 22px !important;
            text-align: center !important;
        }

        /* Sidebar link text — wrap to the next line when it exceeds the menu width
           (instead of truncating with an ellipsis); applies to normal & active alike. */
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link .hide-menu,
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link span:not(.material-icons) {
            flex: 1 !important;
            min-width: 0 !important;
            white-space: normal !important;
            overflow: visible !important;
            text-overflow: clip !important;
            overflow-wrap: anywhere !important;
            word-break: break-word !important;
        }

        /* Chevron/arrow for expandable items */
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link .arrow-icon,
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link .material-icons:last-child,
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link > .material-icons[style*="margin-left"],
        #sidebar-setup .sidebar-google-style .sidebarmenu .has-arrow::after {
            color: #9ca3af !important;
            font-size: 16px !important;
            margin-left: auto !important;
            flex-shrink: 0 !important;
            width: auto !important;
        }

        /* Hover state */
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link:hover {
            background: #f9fafb !important;
            color: #111827 !important;
        }

        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link:hover .material-icons,
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link:hover i {
            color: #374151 !important;
        }

        /* Active parent link */
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link.active {
            background: #eff6ff !important;
            color: #1d4ed8 !important;
        }

        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link.active .material-icons,
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link.active i {
            color: #2563eb !important;
        }

        /* Sub-menu / collapse area */
        #sidebar-setup .sidebar-google-style .sidebarmenu .collapse,
        #sidebar-setup .sidebar-google-style .sidebarmenu .collapsing {
            padding-left: 0 !important;
        }

        #sidebar-setup .sidebar-google-style .sidebarmenu .collapse ul,
        #sidebar-setup .sidebar-google-style .sidebarmenu .first-level {
            list-style: none !important;
            padding: 2px 0 2px 8px !important;
            margin: 0 !important;
        }

        /* Sub-menu items */
        #sidebar-setup .sidebar-google-style .sidebarmenu .collapse .sidebar-item,
        #sidebar-setup .sidebar-google-style .sidebarmenu .first-level .sidebar-item {
            margin: 1px 0 !important;
        }

        /* Sub-menu links */
        #sidebar-setup .sidebar-google-style .sidebarmenu .collapse .sidebar-link,
        #sidebar-setup .sidebar-google-style .sidebarmenu .first-level .sidebar-link {
            padding: 10px 14px 10px 14px !important;
            font-size: 0.8125rem !important;
            font-weight: 400 !important;
            color: #374151 !important;
            gap: 0 !important;
            margin-left: 35px !important;
        }

        /* Sub-menu link hover */
        #sidebar-setup .sidebar-google-style .sidebarmenu .collapse .sidebar-link:hover,
        #sidebar-setup .sidebar-google-style .sidebarmenu .first-level .sidebar-link:hover {
            background: #f3f4f6 !important;
            color: #111827 !important;
        }

        /* Active sub-menu item - blue pill highlight */
        #sidebar-setup .sidebar-google-style .sidebarmenu .collapse .sidebar-link.active,
        #sidebar-setup .sidebar-google-style .sidebarmenu .first-level .sidebar-link.active {
            background: #dbeafe !important;
            color: #1e40af !important;
            font-weight: 500 !important;
            width: auto !important;
            white-space: normal !important;
        }

        /* Remove any default bullets/markers on sub items */
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link::before {
            display: none !important;
        }

        /* Simplebar content padding adjustment */
        #sidebar-setup .sidebar-google-style .sidebarmenu .simplebar-content {
            padding: 12px 0 24px 12px !important;
        }

        /* Force override dark theme colors on sidebar menu text */
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link,
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link span,
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link .hide-menu,
        #sidebar-setup .sidebar-google-style .sidebarmenu a {
            color: #1f2937 !important;
        }

        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link i,
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link .material-icons {
            color: #4b5563 !important;
        }

        /* Active link overrides - must come after generic color rules */
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link.active,
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link.active span,
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link.active .hide-menu {
            color: #1e40af !important;
        }
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link.active i,
        #sidebar-setup .sidebar-google-style .sidebarmenu .sidebar-link.active .material-icons {
            color: #2563eb !important;
        }

        /* Ensure the simplebar wrapper also has white background */
        #sidebar-setup .sidebar-google-style .sidebarmenu .simplebar-wrapper {
            background: #ffffff !important;
        }

        /* ============ NUCLEAR OVERRIDE - Force white on entire sidebarmenu ============ */
        /* The theme applies dark navy via .side-mini-panel / .with-vertical selectors */
        aside.side-mini-panel.with-vertical.sidebar-google-style .sidebarmenu,
        aside.side-mini-panel.sidebar-google-style .sidebarmenu,
        .side-mini-panel.with-vertical .sidebarmenu,
        .side-mini-panel .sidebarmenu,
        [data-layout="vertical"] .sidebarmenu,
        html[data-layout="vertical"] body .sidebarmenu,
        .sidebar-google-style .sidebarmenu#sidebar-setup-menu,
        #sidebar-setup-menu,
        #sidebar-setup-menu nav,
        #sidebar-setup-menu .sidebar-nav {
            background: #ffffff !important;
            background-color: #ffffff !important;
        }

        /* Override nav.sidebar-nav background which theme sets to dark */
        .sidebar-google-style nav.sidebar-nav,
        #sidebar-setup nav.sidebar-nav,
        nav.sidebar-nav.d-block.left-none {
            background: #ffffff !important;
            background-color: #ffffff !important;
        }
        </style>

        <script>
        // Global function to collapse all menus
        function collapseAllMenus() {
            const allCollapses = document.querySelectorAll('.sidebarmenu .collapse');
            allCollapses.forEach(collapse => {
                const bsCollapse = bootstrap.Collapse.getInstance(collapse);
                if (bsCollapse) {
                    bsCollapse.hide();
                } else {
                    collapse.classList.remove('show');
                }

                // Update the toggle button arrow
                const collapseId = collapse.id;
                const toggleBtn = document.querySelector(
                    `[href="#${collapseId}"], [data-bs-target="#${collapseId}"]`);
                if (toggleBtn) {
                    toggleBtn.setAttribute('aria-expanded', 'false');
                    toggleBtn.classList.add('collapsed');
                    const icon = toggleBtn.querySelector('.material-icons');
                    if (icon && icon.textContent.includes('keyboard_arrow_up')) {
                        icon.textContent = 'keyboard_arrow_down';
                    }
                }
            });
        }

        // Add accordion behavior - when one opens, others close
        document.addEventListener('DOMContentLoaded', function() {
            const setupSidebar = document.getElementById('sidebar-setup');
            if (!setupSidebar) return;

            // Add accordion behavior to collapsible menus
            const collapseElements = setupSidebar.querySelectorAll('.sidebar-item [data-bs-toggle="collapse"]');
            collapseElements.forEach(trigger => {
                trigger.addEventListener('click', function(e) {
                    const targetId = this.getAttribute('href') || this.getAttribute(
                        'data-bs-target');
                    const targetCollapse = document.querySelector(targetId);

                    // Find all collapse elements in the same parent container
                    const parentNav = this.closest('.sidebar-nav');
                    if (parentNav) {
                        const allCollapses = parentNav.querySelectorAll('.collapse');
                        allCollapses.forEach(collapse => {
                            if (collapse !== targetCollapse && collapse.classList
                                .contains(
                                    'show')) {
                                const bsCollapse = bootstrap.Collapse.getInstance(
                                    collapse);
                                if (bsCollapse) {
                                    bsCollapse.hide();
                                }
                            }
                        });
                    }

                    // Rotate arrow icon
                    const icon = this.querySelector('.material-icons');
                    if (icon) {
                        setTimeout(() => {
                            if (targetCollapse.classList.contains('show')) {
                                icon.textContent = 'keyboard_arrow_up';
                            } else {
                                icon.textContent = 'keyboard_arrow_down';
                            }
                        }, 350);
                    }
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Scope to setup sidebar (in #sidebar-setup tab pane)
            const setupSidebar = document.getElementById('sidebar-setup');
            if (!setupSidebar) {
                return;
            }

            // Initialize mini-navbar functionality for setup ONLY
            const miniNavItems = setupSidebar.querySelectorAll('.mini-nav .mini-nav-item');
            const sidebarMenus = setupSidebar.querySelectorAll('.sidebarmenu nav');

            function markActiveLinks() {
                if (window.SargamNavState && window.SargamNavState.markActiveSidebarLinks) {
                    window.SargamNavState.markActiveSidebarLinks();
                }
            }

            // Function to keep sidebar menu visible for a few seconds
            function keepSidebarVisible(menuId, duration = 3000) {
                const targetMenu = document.getElementById(menuId);
                if (!targetMenu) return;
                let elapsed = 0;
                const interval = setInterval(function() {
                    if (!targetMenu.classList.contains('d-block')) {
                        targetMenu.classList.add('d-block');
                    }
                    if (targetMenu.style.display !== 'block') {
                        targetMenu.style.display = 'block';
                    }
                    elapsed += 200;
                    if (elapsed >= duration) {
                        clearInterval(interval);
                    }
                }, 200);
            }

            function isDynamicGroupSidebar() {
                return setupSidebar.hasAttribute('data-sidebar-layout') ||
                    setupSidebar.querySelector('.sidebar-group-link');
            }

            function showDynamicSidebarNavLocal() {
                if (typeof window.setDynamicSidebarMenuExpanded === 'function') {
                    window.setDynamicSidebarMenuExpanded(true, false);
                    return;
                }
                setupSidebar.querySelectorAll('.sidebarmenu .sidebar-nav').forEach(function(nav) {
                    nav.classList.add('d-block', 'left-none');
                    nav.style.display = 'block';
                    nav.style.visibility = 'visible';
                });
                document.body.setAttribute('data-sidebartype', 'full');
            }

            // Function to show sidebar menu and save state
            function showSidebarMenu(miniId) {
                if (isDynamicGroupSidebar()) {
                    showDynamicSidebarNavLocal();
                    return;
                }
                // Remove selected from all mini-nav-items
                miniNavItems.forEach(function(navItem) {
                    navItem.classList.remove('selected');
                });
                // Add selected only to the clicked/active one
                const selectedItem = document.getElementById(miniId);
                if (selectedItem) {
                    selectedItem.classList.add('selected');
                }
                sidebarMenus.forEach(function(nav) {
                    nav.classList.remove('d-block');
                    nav.style.display = 'none';
                });
                const targetMenuId = 'menu-right-' + miniId;
                const targetMenu = document.getElementById(targetMenuId);
                if (targetMenu) {
                    targetMenu.classList.add('d-block');
                    targetMenu.style.display = 'block';
                    document.body.setAttribute('data-sidebartype', 'full');
                    keepSidebarVisible(targetMenuId, 3000);
                }
                localStorage.setItem('selectedMiniNav', miniId);
            }

            // Legacy sidebars only: keep menu visible when theme toggles classes
            if (!isDynamicGroupSidebar()) {
                sidebarMenus.forEach(function(nav) {
                    const observer = new MutationObserver(function() {
                        if (nav.classList.contains('d-block') && nav.style.display !==
                            'block') {
                            nav.style.display = 'block';
                        }
                    });
                    observer.observe(nav, {
                        attributes: true,
                        attributeFilter: ['style', 'class']
                    });
                });
            }

            // Function to expand collapsed menus containing active links
            function expandActiveMenus() {
                sidebarMenus.forEach(function(nav) {
                    if (!nav.classList.contains('d-block') && nav.style.display !== 'block') {
                        return;
                    }
                    const activeLinks = nav.querySelectorAll('.sidebar-link.active');
                    activeLinks.forEach(function(activeLink) {
                        let parent = activeLink.closest('.collapse');
                        while (parent) {
                            parent.classList.add('show', 'in');
                            parent.style.display = 'block';
                            const collapseId = parent.id;
                            const toggleBtn = nav.querySelector(
                                `[href="#${collapseId}"], [data-bs-target="#${collapseId}"]`
                            );
                            if (toggleBtn) {
                                toggleBtn.setAttribute('aria-expanded', 'true');
                                toggleBtn.classList.remove('collapsed');
                            }
                            parent = parent.parentElement.closest('.collapse');
                        }
                    });
                });
            }

            // Mark active links first
            markActiveLinks();

            // Note: Mini-nav click handling is done globally by sidebar-navigation-fixed.js
            // No need to add event listeners here to avoid duplicate handlers

            // Function to restore sidebar menu visibility
            function restoreSidebarMenu() {
                if (isDynamicGroupSidebar()) {
                    // For dynamic sidebar: ensure first group is selected if none is
                    const hasSelected = setupSidebar.querySelector('.mini-nav .mini-nav-item.selected');
                    if (!hasSelected && miniNavItems.length > 0) {
                        miniNavItems[0].classList.add('selected');
                        const firstLink = miniNavItems[0].querySelector('.sidebar-group-link');
                        if (firstLink) firstLink.classList.add('selected');
                    }
                    // Ensure the sidebar menu panel is visible
                    showDynamicSidebarNavLocal();
                    return;
                }
                // Always remove selected from all mini-nav-items first
                miniNavItems.forEach(function(navItem) {
                    navItem.classList.remove('selected');
                });
                let activeMiniId = null;
                sidebarMenus.forEach(function(nav) {
                    const activeLink = nav.querySelector('.sidebar-link.active');
                    if (activeLink) {
                        const navId = nav.id;
                        activeMiniId = navId.replace('menu-right-', '');
                    }
                });
                if (activeMiniId) {
                    showSidebarMenu(activeMiniId);
                    setTimeout(function() {
                        expandActiveMenus();
                    }, 100);
                    // Only activate setup tab if it's not already active
                    setTimeout(function() {
                        const setupTabPane = document.getElementById('tab-setup');
                        if (setupTabPane && setupTabPane.classList.contains('active')) {
                            // Already on setup tab, just ensure it stays active
                            const setupTabLink = document.querySelector('a[href="#tab-setup"]');
                            if (setupTabLink) {
                                setupTabLink.classList.add('active');
                            }
                        }
                    }, 150);
                } else {
                    const savedMiniId = localStorage.getItem('selectedMiniNav');
                    if (savedMiniId && document.getElementById(savedMiniId)) {
                        showSidebarMenu(savedMiniId);
                        setTimeout(expandActiveMenus, 100);
                    } else {
                        // Only one selected from server, if any
                        const hasSelected = setupSidebar.querySelector('.mini-nav .mini-nav-item.selected');
                        if (hasSelected) {
                            // Remove selected from all, add only to this one
                            miniNavItems.forEach(function(navItem) {
                                navItem.classList.remove('selected');
                            });
                            hasSelected.classList.add('selected');
                            showSidebarMenu(hasSelected.id);
                            setTimeout(expandActiveMenus, 100);
                        } else if (miniNavItems.length > 0) {
                            showSidebarMenu(miniNavItems[0].id);
                        }
                    }
                }
            }

            // Initial restore on page load
            restoreSidebarMenu();

            // Listen for tab switches (Bootstrap) - any category tab
            document.querySelectorAll('[data-sargam-category-tab]').forEach(function(tabLink) {
                tabLink.addEventListener('click', function(e) {
                    setTimeout(restoreSidebarMenu, 150);
                });
            });
            document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(function(tabLink) {
                tabLink.addEventListener('shown.bs.tab', function(e) {
                    if (e.target.getAttribute('href') === '#tab-setup') {
                        setTimeout(restoreSidebarMenu, 100);
                    }
                });
            });

            // Listen for window focus
            window.addEventListener('focus', function() {
                setTimeout(restoreSidebarMenu, 100);
            });
        });
        </script>
    </div>
</div>
<script>
const sidebar = document.querySelector('.sidebarmenu .simplebar-content-wrapper');

// Restore scroll position on load
document.addEventListener('DOMContentLoaded', function() {
    const scrollPos = localStorage.getItem('sidebar-scroll');
    if (scrollPos && sidebar) {
        sidebar.scrollTop = parseInt(scrollPos, 10);
    }

});

// Save scroll position before unload
window.addEventListener('beforeunload', function() {
    if (sidebar) {
        localStorage.setItem('sidebar-scroll', sidebar.scrollTop);
    }
});
// Add on click
document.querySelectorAll('.mini-nav-item').forEach(item => {
    item.addEventListener('click', function() {
        localStorage.setItem('active-mini-nav', this.id);
    });
});

// On load
document.addEventListener('DOMContentLoaded', () => {
    // The server resolves the correct group for the current page
    // (SARGAM_ACTIVE_GROUP_ID). It MUST win over the last-clicked group cached in
    // localStorage — otherwise a group from a different category (e.g. the last
    // Time Table you opened) wrongly steals the highlight on this page.
    const serverGroup = (window.SARGAM_ACTIVE_GROUP_ID != null)
        ? String(window.SARGAM_ACTIVE_GROUP_ID)
        : null;
    const activeId = serverGroup || localStorage.getItem('active-mini-nav');
    if (activeId) {
        const activeEl = document.getElementById(activeId);
        // Only re-point the highlight when the target group exists in THIS
        // category's mini-nav; never blank out the server's selection otherwise.
        if (activeEl) {
            document.querySelectorAll('.mini-nav-item').forEach(item => item.classList.remove('selected'));
            activeEl.classList.add('selected');
            if (serverGroup) { try { localStorage.setItem('active-mini-nav', serverGroup); } catch (e) {} }
        }
    }
});
</script>
<!--  Sidebar End -->