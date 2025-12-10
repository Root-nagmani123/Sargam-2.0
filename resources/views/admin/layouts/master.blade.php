<!DOCTYPE html>
<html lang="zxx">

<head>
    @include('admin.layouts.pre_header')
    <title>@yield('title') {{ env('APP_TITLE_SUFFIX') }}</title>
    @section('css')
    <style>
    /* ========================================
       Enhanced Mini-Nav Styling (GIGW Compliant)
       ======================================== */
    
    .mini-nav-item {
        transition: all 0.3s ease;
        border-radius: 8px;
        margin: 4px 0;
    }

    .mini-nav-item .mini-nav-link {
        padding: 12px 16px;
        border-radius: 8px;
        transition: all 0.3s ease;
        color: #4b5563;
        font-weight: 500;
        position: relative;
    }

    .mini-nav-item .mini-nav-link:hover {
        background-color: #f0f3f7;
        color: #004a93;
        transform: translateX(4px);
        box-shadow: 0 2px 8px rgba(0, 74, 147, 0.1);
    }

    .mini-nav-item .mini-nav-link:focus {
        outline: 2px solid #004a93;
        outline-offset: -2px;
        background-color: #e8eef7;
    }

    .mini-nav-item.selected .mini-nav-link {
        background: linear-gradient(135deg, #004a93 0%, #0066cc 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(0, 74, 147, 0.25);
        font-weight: 600;
    }

    .mini-nav-item.selected .mini-nav-link::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 24px;
        background: white;
        border-radius: 0 2px 2px 0;
    }

    .mini-nav-item .menu-icon {
        font-size: 24px !important;
        transition: transform 0.3s ease;
    }

    .mini-nav-item:hover .menu-icon {
        transform: scale(1.1) translateX(2px);
    }

    .mini-nav-item.selected .menu-icon {
        transform: scale(1.15);
    }

    .mini-nav-title {
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    /* Accessibility */
    .mini-nav-item .mini-nav-link:focus-visible {
        outline: 3px solid #004a93;
        outline-offset: 2px;
    }

    /* Responsive */
    @media (max-width: 576px) {
        .mini-nav-item .mini-nav-link {
            padding: 10px 12px;
        }

        .mini-nav-title {
            font-size: 0.85rem;
        }

        .menu-icon {
            font-size: 20px !important;
        }
    }
    </style>
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

/* ========================================
   Sidebar Menu Items Styling (GIGW Compliant)
   ======================================== */

.sidebar-item {
    transition: all 0.3s ease;
    border-radius: 8px;
    min-height: 44px;
    display: flex;
    align-items: center;
}

.sidebar-item:hover {
    background-color: #f0f3f7;
    transform: translateX(4px);
}

.sidebar-item:focus-within {
    outline: 2px solid #004a93;
    outline-offset: -2px;
    background-color: #e8eef7;
}

.sidebar-link {
    color: #4b5563;
    font-weight: 500;
    padding: 10px 14px;
    text-decoration: none;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 12px;
    border-radius: 6px;
    min-height: 40px;
    width: 100%;
}

.sidebar-link:hover {
    background-color: #e8eef7;
    color: #004a93;
    box-shadow: inset 4px 0 0 #004a93;
}

.sidebar-link:focus {
    outline: 2px solid #004a93;
    outline-offset: -2px;
    color: #004a93;
}

.sidebar-link.active {
    background: linear-gradient(90deg, #004a93 0%, #0066cc 100%);
    color: white;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0, 74, 147, 0.15);
}

.sidebar-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: white;
    border-radius: 0 2px 2px 0;
}

/* Section Title Styling */
.nav-section {
    padding: 12px 16px 8px;
    font-size: 0.9rem;
    font-weight: 600;
    color: #004a93;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 8px;
}

/* Group Headers */
.sidebar-group {
    margin-bottom: 12px;
}

.nav-small-cap {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 6px;
    font-weight: 600;
    color: #004a93;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    cursor: pointer;
    background: linear-gradient(135deg, rgba(0, 74, 147, 0.08) 0%, rgba(0, 102, 204, 0.05) 100%);
    border: 1px solid rgba(0, 74, 147, 0.1);
}

.nav-small-cap:hover {
    background: linear-gradient(135deg, rgba(0, 74, 147, 0.15) 0%, rgba(0, 102, 204, 0.1) 100%);
    box-shadow: 0 2px 8px rgba(0, 74, 147, 0.1);
}

.nav-small-cap:focus {
    outline: 2px solid #004a93;
    outline-offset: -2px;
}

.menu-icon {
    font-size: 20px;
    transition: transform 0.3s ease;
}

.sidebar-link:hover .menu-icon {
    transform: scale(1.1) translateX(2px);
}

.sidebar-link.active .menu-icon {
    transform: scale(1.15);
}

/* Hide Menu */
.hide-menu {
    font-weight: 500;
}

/* Collapse Items */
.collapse {
    transition: all 0.3s ease;
}

.collapse.show ~ .menu-icon {
    transform: rotate(180deg);
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


    </style>

</head>

<body>
    <!-- Preloader -->
    <div class="preloader">
        <img src="{{ asset('admin_assets/images/logos/favicon.ico') }}" alt="loader" class="lds-ripple img-fluid">
    </div>
    <div class="loading d-none" id="ajaxLoader">Loading&#8230;</div>
    <div id="main-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-wrapper">
            @include('admin.layouts.header')
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
</body>

</html>