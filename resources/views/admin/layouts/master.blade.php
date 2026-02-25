<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
    <!-- Set initial theme from localStorage before paint (avoids flash) -->
    <script>
        (function() {
            'use strict';
            try {
                var saved = localStorage.getItem('bsTheme');
                if (saved === 'dark' || saved === 'light') {
                    document.documentElement.setAttribute('data-bs-theme', saved);
                }
            } catch (e) {}
        })();
    </script>
    @include('admin.layouts.pre_header')
    <title>@yield('title') {{ env('APP_TITLE_SUFFIX') }} - Sargam 2.0 | Lal Bahadur Shastri National Academy of Administration</title>
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
        font-variation-settings: 'FILL'1;
        /* Sets the fill to its maximum value (1) */
        color: blue;
        /* You can also change the color of the icon */
    }

    .my-unfilled-icon {
        font-variation-settings: 'FILL'0;
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
        padding: 8px;
        text-align: center;
        font-weight: 600;
    }

    .calendar td {
        width: 14.28%;
        height: 65px;
        padding: 6px;
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
        margin-top: 4px;
    }

    /* Basic container */
    .calendar-component {
        max-width: 100%;
        background: #fff;
        border-radius: 12px;
        padding: 14px;
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
        padding: 8px 6px;
    }

    .calendar-table td {
        padding: 8px 6px;
        vertical-align: middle;
        border: none;
        text-align: center;
    }



    .calendar-cell {
        border-radius: 8px;
        transition: background .12s ease;
    }

    .calendar-cell:hover {
        background: #f2f2f2;
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
            padding: 10px;
            max-width: 100%;
        }

        .calendar-header {
            gap: .5rem;
        }

        .calendar-table th,
        .calendar-table td {
            padding: 6px 4px;
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
        padding: 8px 0 10px 0;
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
        padding: 6px 0;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        animation: fadeIn .15s ease-out;
    }

    /* Menu Items */
    .dd-menu-item {
        padding: 10px 14px;
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
        margin-right: 4px;
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

    /* Advanced Sargam 2.0 Loader - Bootstrap 5 */
    .sargam-loader {
        position: fixed;
        inset: 0;
        background: radial-gradient(ellipse at center, #ffffff 0%, #f0f7ff 50%, #e8f0fa 100%);
        z-index: 9999;
        transition: opacity 0.5s ease, visibility 0.5s ease;
        overflow: hidden;
    }

    .sargam-loader.hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    /* Floating particles */
    .sargam-loader-particles {
        position: absolute;
        inset: 0;
        pointer-events: none;
    }

    .sargam-loader-particle {
        position: absolute;
        width: 6px;
        height: 6px;
        background: linear-gradient(135deg, #004a93, #0d6efd);
        border-radius: 50%;
        opacity: 0.4;
        animation: sargamFloat 4s ease-in-out infinite;
    }

    .sargam-loader-particle:nth-child(1) { left: 15%; top: 20%; animation-delay: 0s; }
    .sargam-loader-particle:nth-child(2) { left: 85%; top: 25%; animation-delay: 0.5s; }
    .sargam-loader-particle:nth-child(3) { left: 75%; top: 75%; animation-delay: 1s; }
    .sargam-loader-particle:nth-child(4) { left: 20%; top: 80%; animation-delay: 1.5s; }
    .sargam-loader-particle:nth-child(5) { left: 50%; top: 15%; animation-delay: 2s; }
    .sargam-loader-particle:nth-child(6) { left: 10%; top: 50%; animation-delay: 2.5s; }
    .sargam-loader-particle:nth-child(7) { left: 90%; top: 55%; animation-delay: 3s; }
    .sargam-loader-particle:nth-child(8) { left: 45%; top: 85%; animation-delay: 3.5s; }

    @keyframes sargamFloat {
        0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.4; }
        25% { transform: translate(15px, -20px) scale(1.2); opacity: 0.7; }
        50% { transform: translate(-10px, 15px) scale(0.9); opacity: 0.5; }
        75% { transform: translate(-20px, -10px) scale(1.1); opacity: 0.6; }
    }

    /* Rotating rings container */
    .sargam-loader-rings {
        position: relative;
        width: 140px;
        height: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sargam-loader-ring {
        position: absolute;
        border-radius: 50%;
        border: 3px solid transparent;
    }

    .sargam-loader-ring-outer {
        width: 100%;
        height: 100%;
        border-top-color: #004a93;
        border-right-color: #0d6efd;
        border-bottom-color: #004a93;
        border-left-color: transparent;
        animation: sargamSpin 1.2s linear infinite;
    }

    .sargam-loader-ring-mid {
        width: 100px;
        height: 100px;
        border-top-color: transparent;
        border-right-color: #0d6efd;
        border-bottom-color: transparent;
        border-left-color: #004a93;
        animation: sargamSpin 1s linear infinite reverse;
    }

    .sargam-loader-ring-inner {
        width: 60px;
        height: 60px;
        border-top-color: #0d6efd;
        border-right-color: transparent;
        border-bottom-color: #004a93;
        border-left-color: transparent;
        animation: sargamSpin 0.8s linear infinite;
    }

    @keyframes sargamSpin {
        to { transform: rotate(360deg); }
    }

    /* Brand text with letter-by-letter animation */
    .sargam-loader-brand {
        display: inline-flex;
        gap: 2px;
        font-size: clamp(1.75rem, 5vw, 3rem);
        font-weight: 800;
        font-family: 'Poppins', 'Segoe UI', system-ui, sans-serif;
        letter-spacing: 0.02em;
    }

    .sargam-loader-brand span {
        display: inline-block;
        color: #004a93;
        background: linear-gradient(135deg, #004a93 0%, #0066cc 40%, #0d6efd 70%, #004a93 100%);
        background-size: 200% auto;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: sargamLetterPop 2s ease-in-out infinite;
        text-shadow: 0 0 30px rgba(0, 74, 147, 0.2);
    }

    .sargam-loader-brand span:nth-child(1) { animation-delay: 0s; }
    .sargam-loader-brand span:nth-child(2) { animation-delay: 0.05s; }
    .sargam-loader-brand span:nth-child(3) { animation-delay: 0.1s; }
    .sargam-loader-brand span:nth-child(4) { animation-delay: 0.15s; }
    .sargam-loader-brand span:nth-child(5) { animation-delay: 0.2s; }
    .sargam-loader-brand span:nth-child(6) { animation-delay: 0.25s; }
    .sargam-loader-brand span:nth-child(7) { animation-delay: 0.3s; min-width: 0.25em; }
    .sargam-loader-brand span:nth-child(8) { animation-delay: 0.35s; }
    .sargam-loader-brand span:nth-child(9) { animation-delay: 0.4s; }
    .sargam-loader-brand span:nth-child(10) { animation-delay: 0.45s; }

    @keyframes sargamLetterPop {
        0%, 100% { transform: translateY(0) scale(1); opacity: 1; }
        50% { transform: translateY(-4px) scale(1.05); opacity: 0.9; }
    }

    /* Segmented progress dots */
    .sargam-loader-dots {
        display: flex;
        gap: 8px;
        align-items: center;
        justify-content: center;
    }

    .sargam-loader-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: rgba(0, 74, 147, 0.2);
        animation: sargamDotPulse 1.4s ease-in-out infinite;
    }

    .sargam-loader-dot:nth-child(1) { animation-delay: 0s; }
    .sargam-loader-dot:nth-child(2) { animation-delay: 0.2s; }
    .sargam-loader-dot:nth-child(3) { animation-delay: 0.4s; }
    .sargam-loader-dot:nth-child(4) { animation-delay: 0.6s; }
    .sargam-loader-dot:nth-child(5) { animation-delay: 0.8s; }

    @keyframes sargamDotPulse {
        0%, 100% { transform: scale(0.8); background: rgba(0, 74, 147, 0.2); }
        50% { transform: scale(1.2); background: #0d6efd; }
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

</head>

<body data-sidebartype="full">
    <!-- Preloader - Advanced Sargam 2.0 Loader (Bootstrap 5) -->
    <div class="sargam-loader d-flex align-items-center justify-content-center" id="sargamLoader" role="status" aria-live="polite" aria-label="Loading Sargam 2.0">
        <div class="sargam-loader-particles">
            <span class="sargam-loader-particle"></span>
            <span class="sargam-loader-particle"></span>
            <span class="sargam-loader-particle"></span>
            <span class="sargam-loader-particle"></span>
            <span class="sargam-loader-particle"></span>
            <span class="sargam-loader-particle"></span>
            <span class="sargam-loader-particle"></span>
            <span class="sargam-loader-particle"></span>
        </div>
        <div class="sargam-loader-inner d-flex flex-column align-items-center gap-4 position-relative">
            <div class="sargam-loader-rings">
                <span class="sargam-loader-ring sargam-loader-ring-outer"></span>
                <span class="sargam-loader-ring sargam-loader-ring-mid"></span>
                <span class="sargam-loader-ring sargam-loader-ring-inner"></span>
            </div>
            <span class="sargam-loader-brand">
                <span>S</span><span>A</span><span>R</span><span>G</span><span>A</span><span>M</span><span> </span><span>2</span><span>.</span><span>0</span>
            </span>
            <div class="sargam-loader-dots">
                <span class="sargam-loader-dot" role="presentation"></span>
                <span class="sargam-loader-dot" role="presentation"></span>
                <span class="sargam-loader-dot" role="presentation"></span>
                <span class="sargam-loader-dot" role="presentation"></span>
                <span class="sargam-loader-dot" role="presentation"></span>
            </div>
        </div>
    </div>

    <div id="main-wrapper">
        @include('admin.layouts.header')
        <div class="page-wrapper">

            @include('admin.layouts.sidebar')
            <div class="body-wrapper">
                @if(session('low_stock_alert') && is_array(session('low_stock_alert')) && count(session('low_stock_alert')) > 0)
                    <div class="low-stock-alert-overlay" id="lowStockAlertOverlay" role="dialog" aria-modal="true" aria-labelledby="lowStockAlertTitle">
                        <div class="low-stock-alert-box">
                            <div class="low-stock-alert-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold" id="lowStockAlertTitle">Low Stock Alert!</h5>
                                <button type="button" class="btn-close" onclick="document.getElementById('lowStockAlertOverlay').style.display='none'" aria-label="Close"></button>
                            </div>
                            <div class="low-stock-alert-body">
                                <ul class="mb-0 ps-3">
                                    @foreach(session('low_stock_alert') as $row)
                                        <li class="mb-2">
                                            <strong>Item:</strong> {{ $row['item_name'] ?? '—' }}
                                            &nbsp;|&nbsp;
                                            <strong>Available:</strong> {{ number_format($row['remaining_quantity'] ?? 0, 2) }} {{ $row['unit'] ?? 'Unit' }}
                                            &nbsp;|&nbsp;
                                            <strong>Minimum Required:</strong> {{ number_format($row['alert_quantity'] ?? 0, 2) }} {{ $row['unit'] ?? 'Unit' }}
                                        </li>
                                    @endforeach
                                </ul>
                                <a href="{{ route('admin.mess.reports.stock-balance-till-date') }}" class="btn btn-danger mt-3">View Stock Balance</a>
                            </div>
                        </div>
                    </div>
                    <style>
                        .low-stock-alert-overlay {
                            position: fixed;
                            inset: 0;
                            z-index: 9999;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            background: rgba(0,0,0,0.4);
                            padding: 1rem;
                        }
                        .low-stock-alert-box {
                            background: #fff;
                            border-radius: 12px;
                            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                            max-width: 480px;
                            width: 100%;
                            overflow: hidden;
                        }
                        .low-stock-alert-header {
                            background: #dc3545;
                            color: #fff;
                            padding: 1rem 1.25rem;
                        }
                        .low-stock-alert-body {
                            padding: 1.25rem 1.5rem;
                            background: #fff5f5;
                        }
                    </style>
                @endif
                <main id="main-content" tabindex="-1" role="main">
                <!-- Tab Content Container -->
                <div class="tab-content" id="mainNavbarContent">
                    <!-- Home Tab -->
                    <div class="tab-pane fade {{ ($activeNavTab ?? '#home') === '#home' ? 'show active' : '' }}" id="home" role="tabpanel">
                        @yield('content')
                    </div>

                    <!-- Setup Tab -->
                    <div class="tab-pane fade {{ ($activeNavTab ?? '#home') === '#tab-setup' ? 'show active' : '' }}" id="tab-setup" role="tabpanel">
                        @yield('setup_content')
                    </div>

                    <!-- Communications Tab -->
                    <div class="tab-pane fade {{ ($activeNavTab ?? '#home') === '#tab-communications' ? 'show active' : '' }}" id="tab-communications" role="tabpanel">
                        @yield('communications_content')
                    </div>

                    <!-- Academics Tab -->
                    <div class="tab-pane fade {{ ($activeNavTab ?? '#home') === '#tab-academics' ? 'show active' : '' }}" id="tab-academics" role="tabpanel">
                        @yield('academics_content')
                    </div>

                    <!-- Material Management Tab -->
                    <div class="tab-pane fade {{ ($activeNavTab ?? '#home') === '#tab-material-management' ? 'show active' : '' }}" id="tab-material-management" role="tabpanel">
                        @yield('material_management_content')
                    </div>
                </div>
                </main>
            </div>
        </div>
    </div>

    @include('admin.layouts.footer')
     <script src="{{ asset('js/forms.js') }}"></script>
    <script src="{{ asset('admin_assets/js/sidebar-navigation-fixed.js') }}"></script>
    <script src="{{ asset('admin_assets/js/tab-persistence.js') }}"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('scripts')
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('searchToggle');
    const input  = document.getElementById('searchInput');
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
<script>
    window.addEventListener('load', function () {
        const loader = document.getElementById('sargamLoader');
        if (loader) {
            loader.classList.add('hidden');
            setTimeout(function () { loader.style.display = 'none'; }, 500);
        }
    });
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("main-wrapper");
    const toggleBtn = document.getElementById("headerCollapse");
    if (!sidebar || !toggleBtn) return;
    // Query all icons across all tabs (multiple instances due to tab structure)
    const icons = document.querySelectorAll("#sidebarToggleIcon");
    const body = document.body;
    const sidebarmenus = document.querySelectorAll(".sidebarmenu");
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

    // Apply saved sidebar type preference; default to collapsed on first login
    try {
        const savedType = localStorage.getItem('SidebarType');
        if (savedType) {
            body.setAttribute('data-sidebartype', savedType);
        } else {
            // Default to collapsed (mini-sidebar) for new users
            body.setAttribute('data-sidebartype', 'mini-sidebar');
            localStorage.setItem('SidebarType', 'mini-sidebar');
        }
    } catch (e) {}

    // Initialize collapsed state on page load
    const sidebarType = body.getAttribute("data-sidebartype");
    console.log('Initial sidebar type:', sidebarType);
    console.log('Icon elements found:', icons.length);
    
    if (sidebarType === "mini-sidebar") {
        // Sidebar should be collapsed - ensure main-wrapper doesn't have show-sidebar
        sidebar.classList.remove("show-sidebar");
        // Add close class to sidebarmenu elements
        sidebarmenus.forEach(function(el) {
            el.classList.add("close");
        });
        // Set all icon instances to expand (collapsed state)
        icons.forEach(function(icon) {
            icon.textContent = "keyboard_double_arrow_right";
            icon.classList.remove("rotated");
        });
        console.log('Set all icons to non-rotated (collapsed state)');
        // After initial collapse state, adjust DataTables to new layout
        setTimeout(adjustAllDataTables, 300);
    } else {
        // Sidebar should be expanded
        sidebar.classList.add("show-sidebar");
        sidebarmenus.forEach(function(el) {
            el.classList.remove("close");
        });
        // Set all icon instances to rotated (expanded state)
        icons.forEach(function(icon) {
            icon.textContent = "keyboard_double_arrow_right";
            icon.classList.add("rotated");
        });
        console.log('Set all icons to rotated (expanded state)');
        // After initial expanded state, adjust DataTables to new layout
        setTimeout(adjustAllDataTables, 300);
    }

    // Sync all icon instances with data-sidebartype changes and adjust tables after toggle
    function syncIconWithSidebar(type) {
        const allIcons = document.querySelectorAll("#sidebarToggleIcon");
        allIcons.forEach(function(icon) {
            icon.textContent = "keyboard_double_arrow_right";
            if (type === "full") {
                icon.classList.add("rotated");
            } else {
                icon.classList.remove("rotated");
            }
        });
        console.log('Synced', allIcons.length, 'icon(s) to type:', type);
    }

    const observer = new MutationObserver(function(mutations) {
        for (const m of mutations) {
            if (m.attributeName === 'data-sidebartype') {
                const t = body.getAttribute('data-sidebartype');
                syncIconWithSidebar(t);
                setTimeout(adjustAllDataTables, 300);
            }
        }
    });
    observer.observe(body, { attributes: true, attributeFilter: ['data-sidebartype'] });
});
</script>

  <!-- Final safeguard: Force light mode on window load -->
  <script>
    window.addEventListener('load', function() {
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
</body>

</html>