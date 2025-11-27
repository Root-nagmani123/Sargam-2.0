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
        max-width: 460px;
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
                        <div class="container-fluid p-4">
                            <h3>Setup Section</h3>
                            <p>Configuration and setup content will appear here.</p>
                        </div>
                    </div>

                    <!-- Communications Tab -->
                    <div class="tab-pane fade" id="tab-communications" role="tabpanel">
                        <div class="container-fluid p-4">
                            <h3>Communications</h3>
                            <p>Communication tools and settings will appear here.</p>
                        </div>
                    </div>

                    <!-- Academics Tab -->
                    <div class="tab-pane fade" id="tab-academics" role="tabpanel">
                        <div class="container-fluid p-4">
                            <h3>Academics</h3>
                            <p>Academic content and management will appear here.</p>
                        </div>
                    </div>

                    <!-- Material Management Tab -->
                    <div class="tab-pane fade" id="tab-material-management" role="tabpanel">
                        <div class="container-fluid p-4">
                            <h3>Material Management</h3>
                            <p>Material inventory and management will appear here.</p>
                        </div>
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