<!DOCTYPE html>
<html lang="en" class="admin-force-light" style="height: 100%;">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
   <title>@yield('title') {{ env('APP_TITLE_SUFFIX') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://bootstrapdemos.adminmart.com/matdash/dist/assets/css/styles.css">
    <!-- Favicon icon-->
    <link rel="shortcut icon" type="image/ico" href="{{ asset('admin_assets/images/logos/favicon.ico') }}">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- jQuery Validation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    <!-- jQuery Steps -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-steps/1.1.0/jquery.steps.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-steps/1.1.0/jquery.steps.min.js"></script>
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('admin_assets/images/logos/favicon.ico') }}">
    <!-- unified bootstrap include -->
    <!-- Icon library (Bootstrap Icons or Lucide) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('admin_assets/css/accesibility-style_v1.css') }}?v={{ @filemtime(public_path('admin_assets/css/accesibility-style_v1.css')) ?: time() }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @include('components.fonts-sargam')
    <link rel="stylesheet" href="{{ asset('admin_assets/css/material-icons-local.css') }}" />
    <link href="https://cdn.ux4g.gov.in/UX4G@2.0.8/css/ux4g-min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}?v={{ @filemtime(public_path('css/custom.css')) ?: time() }}" />
</head>
<x-session_message />

<body class="admin-force-light bg-light" style="min-height: 100vh; display: flex; flex-direction: column; font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;">
    <!-- Top Blue Bar (Govt of India) -->
<!-- Government Header Strips -->
<div class="govt-header mb-2">
    <!-- Dark Blue Strip -->
    <div class="govt-header-top bg-primary text-white py-2 shadow-sm rounded-bottom-4">
        <div class="container-lg px-4">
            <div class="row align-items-center gx-3">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-3">
                        <img src="https://upload.wikimedia.org/wikipedia/en/thumb/4/41/Flag_of_India.svg/330px-Flag_of_India.svg.png" 
                             alt="India Emblem" 
                             height="20" 
                             class="d-inline-block rounded-circle border border-2 border-white bg-white me-2">
                        <span class="fw-bold fs-5 lh-1">भारत सरकार <span class="mx-1">|</span> Government of India</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-end gap-3 flex-wrap">
                        <a href="#main-content" class="text-white text-decoration-underline small fw-semibold link-light link-opacity-75-hover">Skip to content</a>
                        <div class="d-flex align-items-center gap-1">
                            <button class="btn btn-sm btn-outline-light rounded-pill px-2 fw-bold shadow-sm" aria-label="Increase font size">A+</button>
                            <button class="btn btn-sm btn-outline-light rounded-pill px-2 fw-bold shadow-sm" aria-label="Normal font size">A</button>
                            <button class="btn btn-sm btn-outline-light rounded-pill px-2 fw-bold shadow-sm" aria-label="Decrease font size">A-</button>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-light dropdown-toggle rounded-pill px-2 fw-semibold shadow-sm" 
                                    type="button" 
                                    id="languageDropdown" 
                                    data-bs-toggle="dropdown" 
                                    aria-expanded="false">
                                English
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow rounded-3" aria-labelledby="languageDropdown">
                                <li><a class="dropdown-item" href="#">English</a></li>
                                <li><a class="dropdown-item" href="#">हिंदी</a></li>
                            </ul>
                        </div>
                        <button class="btn btn-sm btn-outline-light rounded-circle d-flex align-items-center justify-content-center shadow-sm" aria-label="Search" style="width:2rem; height:2rem; transition: box-shadow 0.2s;">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    <!-- Sticky Header -->
    <div class="header sticky-top bg-white shadow rounded-bottom-4 mb-4">
        <div class="container-lg py-2 px-3">
            <nav class="navbar navbar-expand-lg navbar-light">
                <a class="navbar-brand me-2 p-0" href="#">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg"
                        alt="Logo 1" height="64" class="rounded bg-white border p-1 shadow-sm">
                </a>
                <span class="vr mx-2 d-none d-md-inline"></span>
                <a class="navbar-brand p-0" href="#">
                    <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="Logo 2" height="64" class="rounded bg-white border p-1 shadow-sm">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                    <ul class="navbar-nav align-items-center">
                        <li class="nav-item">
                            <a class="nav-link ms-4 me-4 fw-semibold text-primary-emphasis link-primary link-opacity-75-hover rounded-pill px-3 py-1" href="https://www.lbsnaa.gov.in/menu/about-lbsnaa"
                                target="_blank">About Us</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link ms-4 me-4 fw-semibold text-primary-emphasis link-primary link-opacity-75-hover rounded-pill px-3 py-1" href="https://www.lbsnaa.gov.in/footer_menu/contact-us"
                                target="_blank">Contact</a>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </div>


    <!-- Main Content (OT student pages use @section('content'); calendar uses @section('setup_content')) -->
    <main id="main-content" class="flex-grow-1">
        <div class="container-lg py-4">
            <div class="card shadow rounded-4 border-0 mb-4">
                <div class="card-body p-4">
                    @yield('content')
                    @yield('setup_content')
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="mt-auto text-white py-4 shadow-lg rounded-top-4" style="background-color: #004a93;">
        <div class="container-lg">
            <div class="row align-items-center gy-2">
                <div class="col-md-8 mb-2 mb-md-0">
                    <p class="mb-0 text-white small fw-semibold">&copy; {{ date('Y') }} Lal Bahadur Shastri National Academy of Administration, Mussoorie, Uttarakhand</p>
                </div>
                <div class="col-md-4">
                    <ul class="list-unstyled d-flex justify-content-md-end justify-content-center mb-0 gap-4">
                        <li>
                            <a href="#" class="text-white text-decoration-underline small fw-semibold link-light link-opacity-75-hover">Privacy Policy</a>
                        </li>
                        <li>
                            <a href="#" class="text-white text-decoration-underline small fw-semibold link-light link-opacity-75-hover">Need Help</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <style id="timetable-layout-mobile">
        @media (max-width: 767.98px) {
            .govt-header-top .row > div {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .govt-header-top .d-flex.justify-content-end {
                justify-content: flex-start !important;
                flex-wrap: wrap;
                gap: 0.5rem !important;
                margin-top: 0.5rem;
            }

            .govt-header-top .fw-semibold {
                font-size: 0.75rem;
            }

            .header .navbar-brand img {
                height: 44px !important;
                width: auto !important;
            }

            .header .navbar .vr {
                display: none;
            }

            .header .navbar-toggler {
                border-color: rgba(0, 74, 147, 0.35);
            }

            footer .row > div {
                text-align: center !important;
            }

            footer .d-flex.justify-content-end {
                justify-content: center !important;
            }
        }
    </style>
    <style id="admin-timetable-light-last-resort">
        html.admin-force-light,
        body.admin-force-light,
        html.admin-force-light .fc-event-card,
        html.admin-force-light .list-event-card,
        html.admin-force-light .timeline-event-card,
        html.admin-force-light .card,
        html.admin-force-light .dropdown-menu,
        html.admin-force-light .modal-content,
        html.admin-force-light .table,
        html.admin-force-light .form-control,
        html.admin-force-light .form-select {
            color-scheme: only light !important;
            background-color: #fff !important;
            color: #212529 !important;
            border-color: #dee2e6 !important;
        }

        html.admin-force-light .list-event-card .meta,
        html.admin-force-light .fc-event-card .meta-item,
        html.admin-force-light .timeline-event-card .meta {
            color: #6c757d !important;
        }
    </style>
    <script>
        (function() {
            if (typeof window.SargamStandaloneForceLight === 'function') {
                window.SargamStandaloneForceLight();
            }
        })();
    </script>

    <!-- Bootstrap JS (local) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <!-- JavaScript for Tab Functionality -->
    <script>
        $(document).ready(function() {
            // Initialize Bootstrap tabs
            const feedbackTabs = document.getElementById('feedbackTabs');
            const pendingTabBtn = feedbackTabs ? feedbackTabs.querySelector('button[data-bs-target="#pending-tab-pane"]') : null;
            if (pendingTabBtn) {
                new bootstrap.Tab(pendingTabBtn);
            }

            // Handle tab click events
            $('#submitted-tab').on('click', function() {
                // Tab switching handled by Bootstrap
            });

            // Handle form submission success
            @if (session('success'))
                const successAlert = `
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
                $('.session-feedback-main, .container.my-5').first().prepend(successAlert);

                setTimeout(function() {
                    const submittedTab = new bootstrap.Tab(document.getElementById('submitted-tab'));
                    submittedTab.show();

                    setTimeout(function() {
                        window.location.reload();
                    }, 500);
                }, 1500);
            @endif

            @if (session('error'))
                const errorAlert = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
                $('.session-feedback-main, .container.my-5').first().prepend(errorAlert);
            @endif

            // Add form validation
            $('#vertical-wizard').validate({
                rules: {
                    'timetable_pk[]': {
                        required: false
                    }
                },
                errorPlacement: function(error, element) {
                    if (element.attr("name").includes("presentation") || element.attr("name").includes(
                            "content")) {
                        error.insertAfter(element.closest('td'));
                    } else if (element.attr("name").includes("remarks")) {
                        error.insertAfter(element);
                    } else {
                        error.insertAfter(element);
                    }
                },
                submitHandler: function(form) {
                    // Show loader centered inside table
                    $('#table-loader').show();

                    // Disable all buttons while submitting
                    $(form).find('button[type="submit"], .btn-individual').prop('disabled', true);

                    form.submit();
                }
            });

            // Prevent double form submission
            $('#vertical-wizard').on('submit', function(e) {
                const submitBtn = $(this).find('button[type="submit"]');
                if (submitBtn.prop('disabled')) {
                    e.preventDefault();
                    return false;
                }
            });

            // Auto-load submitted tab if URL has hash
            if (window.location.hash === '#submitted') {
                const submittedTab = new bootstrap.Tab(document.getElementById('submitted-tab'));
                submittedTab.show();
            }

            // Update URL hash when tabs change
            $('button[data-bs-toggle="tab"]').on('click', function() {
                const tabId = $(this).attr('id');
                if (tabId === 'submitted-tab') {
                    window.location.hash = 'submitted';
                } else if (tabId === 'pending-tab') {
                    window.location.hash = 'pending';
                }
            });
        });

        // Individual row submission
        function submitIndividual(index) {
            // Show loader inside table
            $('#table-loader').show();

            // Disable all buttons
            $('.btn-individual, .btn-bulk').prop('disabled', true);

            // Create a temporary form for single row submission
            const form = $('<form>', {
                method: 'POST',
                action: '{{ route('feedback.submit.feedback') }}'
            }).append('@csrf');

            // Append only the row inputs
            $(`input[name^="timetable_pk"][name$="[${index}]"]`).clone().appendTo(form);
            $(`input[name^="topic_name"][name$="[${index}]"]`).clone().appendTo(form);
            $(`input[name^="faculty_pk"][name$="[${index}]"]`).clone().appendTo(form);
            $(`input[name^="Ratting_checkbox"][name$="[${index}]"]`).clone().appendTo(form);
            $(`input[name^="Remark_checkbox"][name$="[${index}]"]`).clone().appendTo(form);
            $(`input[name^="content"][name$="[${index}]"]:checked`).clone().appendTo(form);
            $(`input[name^="presentation"][name$="[${index}]"]:checked`).clone().appendTo(form);
            $(`textarea[name^="remarks"][name$="[${index}]"]`).clone().appendTo(form);

            $('body').append(form);
            setTimeout(function() {
                form.submit();
            }, 700); // 700ms delay
        }
    </script>

</body>

</html>
