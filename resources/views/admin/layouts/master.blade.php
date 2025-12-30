<!DOCTYPE html>
<html lang="zxx">

<head>
    @include('admin.layouts.pre_header')
    <title>@yield('title') {{ env('APP_TITLE_SUFFIX') }}</title>
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
    border-bottom: 1px solid #4c8ec5; /* Soft Blue like screenshot */
    border-radius: 10px;
    background: transparent;
    padding: 8px 0 10px 0;
    font-weight: 600;
    font-size: 1rem;
    min-height: 44px; /* GIGW Minimum touch target */
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
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
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
    from { opacity: 0; transform: translateY(-4px); }
    to   { opacity: 1; transform: translateY(0); }
}
.pagination .page-link {
    border: none !important;
    padding: 4px 10px;
    font-size: 14px;
    color: #3a3a3a;
    background: transparent;
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
    width: 200px;           /* You can increase this */
    opacity: 1;
    padding: .375rem .75rem;
}
.alphabet-loader {
    position: fixed;
    inset: 0;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.alphabet-loader .letters {
    display: flex;
    gap: 8px;
}

.alphabet-loader .letters span {
    font-size: 32px;
    font-weight: 700;
    font-family: 'Poppins', sans-serif;
    color: #004a93;
    opacity: 0.2;
    animation: pulseText 1.2s infinite ease-in-out;
}

.alphabet-loader .letters span:nth-child(1) { animation-delay: 0s; }
.alphabet-loader .letters span:nth-child(2) { animation-delay: 0.1s; }
.alphabet-loader .letters span:nth-child(3) { animation-delay: 0.2s; }
.alphabet-loader .letters span:nth-child(4) { animation-delay: 0.3s; }
.alphabet-loader .letters span:nth-child(5) { animation-delay: 0.4s; }
.alphabet-loader .letters span:nth-child(6) { animation-delay: 0.5s; }
.alphabet-loader .letters span:nth-child(7) { animation-delay: 0.6s; }
.alphabet-loader .letters span:nth-child(8) { animation-delay: 0.7s; }
.alphabet-loader .letters span:nth-child(9) { animation-delay: 0.8s; }
.alphabet-loader .letters span:nth-child(10) { animation-delay: 0.9s; }

@keyframes pulseText {
    0% { opacity: 0.2; transform: translateY(0); }
    50% { opacity: 1; transform: translateY(-6px); }
    100% { opacity: 0.2; transform: translateY(0); }
}


    </style>

</head>

<body data-sidebartype="mini-sidebar">
    <!-- Preloader -->
<div class="alphabet-loader" id="alphabetLoader">
    <div class="letters">
        <span>S</span>
        <span>A</span>
        <span>R</span>
        <span>G</span>
        <span>A</span>
        <span>M</span>
        <span>&nbsp;</span>
        <span>2</span>
        <span>.</span>
        <span>0</span>
    </div>
</div>

    <div id="main-wrapper">
         @include('admin.layouts.header')
        <div class="page-wrapper">

           @include('admin.layouts.sidebar')
            @include('admin.layouts.aside')
            <div class="body-wrapper">
                <!-- Tab Content Container -->
                <div class="tab-content" id="mainNavbarContent">
                    <!-- Home Tab -->
                    <div class="tab-pane fade show active" id="home" role="tabpanel">
                                @yield('content')
                    </div>

                    <!-- Setup Tab -->
                    <div class="tab-pane fade" id="tab-setup" role="tabpanel">
                        @yield('setup_content')
                    </div>

                    <!-- Communications Tab -->
                    <div class="tab-pane fade" id="tab-communications" role="tabpanel">
                        @yield('communications_content')
                    </div>

                    <!-- Academics Tab -->
                    <div class="tab-pane fade" id="tab-academics" role="tabpanel">
                        @yield('academics_content')
                    </div>

                    <!-- Material Management Tab -->
                    <div class="tab-pane fade" id="tab-material-management" role="tabpanel">
                        @yield('material_management_content')
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.layouts.footer')
    <script src="{{ asset('js/forms.js') }}"></script>
    <script src="{{ asset('admin_assets/js/sidebar-navigation-fixed.js') }}"></script>
    <script src="{{ asset('admin_assets/js/tab-persistence.js') }}"></script>
    @stack('scripts')
    <script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('searchToggle');
    const input  = document.getElementById('searchInput');

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
        const loader = document.getElementById('alphabetLoader');
        if (loader) {
            loader.style.opacity = "0";
            setTimeout(() => loader.style.display = "none", 300);
        }
    });
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("main-wrapper");
    const toggleBtn = document.getElementById("headerCollapse");
    const icon = document.getElementById("sidebarToggleIcon");
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
    if (sidebarType === "mini-sidebar") {
        // Sidebar should be collapsed - ensure main-wrapper doesn't have show-sidebar
        sidebar.classList.remove("show-sidebar");
        // Add close class to sidebarmenu elements
        sidebarmenus.forEach(function(el) {
            el.classList.add("close");
        });
        // Set icon to expand (collapsed state)
        if (icon) icon.textContent = "keyboard_double_arrow_right";
        // After initial collapse state, adjust DataTables to new layout
        setTimeout(adjustAllDataTables, 300);
    } else {
        // Sidebar should be expanded
        sidebar.classList.add("show-sidebar");
        sidebarmenus.forEach(function(el) {
            el.classList.remove("close");
        });
        if (icon) icon.textContent = "keyboard_double_arrow_left";
        // After initial expanded state, adjust DataTables to new layout
        setTimeout(adjustAllDataTables, 300);
    }

    if (toggleBtn) {
        toggleBtn.addEventListener("click", function () {
            // Toggle show-sidebar class on main-wrapper
            sidebar.classList.toggle("show-sidebar");

            // Toggle close class on sidebarmenu elements
            sidebarmenus.forEach(function(el) {
                el.classList.toggle("close");
            });

            // Toggle data-sidebartype on body
            const currentType = body.getAttribute("data-sidebartype");
            if (currentType === "mini-sidebar") {
                body.setAttribute("data-sidebartype", "full");
                try { localStorage.setItem('SidebarType', 'full'); } catch (e) {}
                if (icon) icon.textContent = "keyboard_double_arrow_left";   // collapse icon
            } else {
                body.setAttribute("data-sidebartype", "mini-sidebar");
                try { localStorage.setItem('SidebarType', 'mini-sidebar'); } catch (e) {}
                if (icon) icon.textContent = "keyboard_double_arrow_right";  // expand icon
            }

            // Adjust DataTables after the sidebar transition to fix header widths
            // Use a small delay to allow CSS transitions to finish
            setTimeout(adjustAllDataTables, 300);
        });
    }
});
</script>

</body>

</html>
