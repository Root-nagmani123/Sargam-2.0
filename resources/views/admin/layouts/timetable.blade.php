<!DOCTYPE html>
<html lang="en" class="admin-force-light" style="height: 100%;">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
   <title>@yield('title') {{ env('APP_TITLE_SUFFIX') }} - Sargam 2.0 | Lal Bahadur Shastri National Academy of Administration</title>
   <link rel="stylesheet" href="{{asset('admin_assets/css/styles.css')}}">
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
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}?v={{ @filemtime(public_path('css/custom.css')) ?: time() }}" />
    <!-- Sargam Design System — must load LAST -->
    <link rel="stylesheet" href="{{ asset('css/sargam-app.css') }}?v={{ @filemtime(public_path('css/sargam-app.css')) ?: time() }}" />
</head>

<body class="admin-force-light bg-light" style="min-height: 100vh; display: flex; flex-direction: column; font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;">
    <!-- Top Blue Bar (Govt of India) -->
<!-- Government Header Strips -->
<div class="govt-header">
    <!-- Top Accessibility / Government of India Strip -->
    <div class="govt-header-top text-white py-2">
        <div class="container-fluid p-0 px-2">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <!-- Brand: Flag + Government of India -->
                <a href="https://www.india.gov.in/" target="_blank" rel="noopener"
                   class="d-inline-flex align-items-center gap-2 text-white text-decoration-none">
                    <img src="https://upload.wikimedia.org/wikipedia/en/thumb/4/41/Flag_of_India.svg/330px-Flag_of_India.svg.png"
                         alt="Flag of India" height="22" width="33"
                         class="d-inline-block">
                    <span class="fw-semibold lh-1">Government of India</span>
                </a>

                <!-- Accessibility toolbar -->
                <div class="d-flex align-items-center flex-wrap govt-acc-toolbar small">
                    <a href="#main-content" class="govt-acc-item text-white text-decoration-none">Skip to Main Content</a>
                    <span class="govt-divider"></span>
                    <button type="button" class="govt-acc-item btn btn-link text-white text-decoration-none d-inline-flex align-items-center gap-1" aria-label="Screen Reader Access">
                        <i class="bi bi-volume-up-fill"></i><span>Screen Reader</span>
                    </button>
                    <span class="govt-divider"></span>
                    <div class="govt-acc-item d-inline-flex align-items-center gap-1" role="group" aria-label="Adjust font size">
                        <button type="button" class="btn btn-link text-white text-decoration-none p-0 fw-semibold lh-1" aria-label="Decrease font size">A&#8722;</button>
                        <button type="button" class="btn btn-link text-white text-decoration-none fw-bold lh-1 govt-font-active" aria-label="Normal font size">A</button>
                        <button type="button" class="btn btn-link text-white text-decoration-none p-0 fw-semibold lh-1" aria-label="Increase font size">A+</button>
                    </div>
                    <span class="govt-divider"></span>
                    <button type="button" class="govt-acc-item btn btn-link text-white p-0 d-inline-flex align-items-center" aria-label="Toggle high contrast">
                        <i class="bi bi-circle-half"></i>
                    </button>
                    <span class="govt-divider"></span>
                    <div class="dropdown govt-acc-item">
                        <button class="btn btn-link text-white text-decoration-none p-0 dropdown-toggle d-inline-flex align-items-center gap-1"
                                type="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-globe2"></i><span>English</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow rounded-3" aria-labelledby="languageDropdown">
                            <li><a class="dropdown-item" href="#">English</a></li>
                            <li><a class="dropdown-item" href="#">हिंदी</a></li>
                        </ul>
                    </div>
                    <span class="govt-divider"></span>
                    <button type="button" class="govt-acc-item btn btn-link text-white text-decoration-none d-inline-flex align-items-center gap-1" aria-label="More options">
                        <i class="bi bi-universal-access"></i><span>More</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
    <!-- Sticky Brand Header -->
    <div class="header sticky-top bg-white shadow-sm border-bottom mb-3">
        <div class="container-fluid p-0 px-2">
            <nav class="navbar navbar-expand-lg navbar-light p-0">
                <a class="navbar-brand d-flex align-items-center gap-2 gap-md-3 me-2 p-0" href="#">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg"
                        alt="Emblem of India" height="56" class="d-inline-block">
                    <span class="vr d-none d-sm-inline-block align-self-center"></span>
                    <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="LBSNAA Logo" height="56" class="d-inline-block">
                </a>
                <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                    <ul class="navbar-nav align-items-lg-center gap-lg-1 mt-3 mt-lg-0">
                        <li class="nav-item">
                            <a class="nav-link fw-semibold text-dark px-lg-3" href="https://www.lbsnaa.gov.in/menu/about-lbsnaa"
                                target="_blank">About Us</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold text-dark px-lg-3" href="https://www.lbsnaa.gov.in/footer_menu/contact-us"
                                target="_blank">Contact Us</a>
                        </li>
                        @auth
                        <li class="nav-item">
                            <div class="position-relative d-none" id="facultyFeedbackBellWrap">
                                <button class="btn btn-link p-1 position-relative" id="facultyFeedbackBell"
                                        title="Pending Feedback" data-bs-toggle="dropdown" aria-expanded="false"
                                        style="color:#333">
                                    <i class="bi bi-bell-fill" style="font-size:20px"></i>
                                    <span id="facultyFeedbackBadge"
                                          class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                          style="font-size:10px;display:none"></span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0"
                                     id="facultyFeedbackDropdown"
                                     style="min-width:300px;max-width:340px;border-radius:12px;overflow:hidden">
                                    <div class="px-3 py-2 fw-semibold text-white d-flex align-items-center gap-2"
                                         style="background:#b30000;font-size:14px">
                                        Pending Feedback
                                    </div>
                                    <ul id="facultyFeedbackList" class="list-unstyled mb-0"
                                        style="max-height:280px;overflow-y:auto"></ul>
                                    <div class="p-2 border-top text-center">
                                        <a href="{{ route('feedback.get.facultyInternalFeedback') }}"
                                           class="btn btn-sm btn-outline-danger w-100" style="font-size:13px">
                                            View All Pending Feedback
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @endauth
                    </ul>
                </div>
            </nav>
        </div>
    </div>


    <!-- Main Content (OT student pages use @section('content'); calendar uses @section('setup_content')) -->
    <main id="main-content" class="flex-grow-1">
                   <div class="container-fluid p-0 px-2">
                     <x-session_message />
                     @yield('content')
                    @yield('setup_content')
                   </div>
    </main>

    <!-- Footer -->
    <footer class="mt-auto text-white py-2" style="background-color: #101808;">
        <div class="container-lg p-0">
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

    <style id="timetable-layout-header">
        :root {
            --govt-blue: #101828;
        }

        /* Top accessibility / Government of India strip */
        .govt-header-top {
            background-color: var(--govt-blue);
        }

        .govt-acc-toolbar {
            row-gap: .35rem;
        }

        .govt-acc-toolbar .govt-acc-item {
            padding-inline: .65rem;
            white-space: nowrap;
        }

        .govt-acc-toolbar .btn-link {
            font-size: inherit;
            opacity: .92;
            transition: opacity .15s ease, transform .15s ease;
        }

        .govt-acc-toolbar .btn-link:hover,
        .govt-acc-toolbar .btn-link:focus-visible {
            opacity: 1;
            transform: translateY(-1px);
        }

        .govt-acc-toolbar a.govt-acc-item {
            opacity: .92;
            transition: opacity .15s ease;
        }

        .govt-acc-toolbar a.govt-acc-item:hover {
            opacity: 1;
            text-decoration: underline !important;
        }

        .govt-divider {
            width: 1px;
            height: 18px;
            background-color: rgba(255, 255, 255, .45);
            display: inline-block;
        }

        .govt-font-active {
            border: 1px solid rgba(255, 255, 255, .6);
            border-radius: .25rem;
            padding: 0 .4rem !important;
        }

        /* Brand bar */
        .header .navbar-brand img {
            transition: transform .2s ease;
        }

        .header .navbar-brand:hover img {
            transform: translateY(-1px);
        }

        .govt-academy-name span:last-child {
            font-size: .8rem;
        }

        .header .nav-link {
            transition: color .15s ease;
        }

        .header .nav-link:hover {
            color: var(--govt-blue) !important;
        }

        @media (max-width: 991.98px) {
            .header .navbar-collapse {
                padding-top: .5rem;
            }
        }

        @media (max-width: 767.98px) {
            .govt-header-top .govt-acc-toolbar {
                width: 100%;
                justify-content: flex-start;
            }

            .header .navbar-brand img {
                height: 44px !important;
                width: auto !important;
            }

            .header .navbar-brand .vr {
                display: none;
            }

            .header .navbar-toggler {
                border-color: rgba(28, 79, 156, 0.35);
            }

            footer .row > div {
                text-align: center !important;
            }

            footer .list-unstyled {
                justify-content: center !important;
            }
        }
    </style>
    <style id="admin-timetable-light-last-resort">

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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

            // On feedback success, switch to the Submitted tab and refresh.
            @if (session('success'))
                setTimeout(function() {
                    const submittedTabEl = document.getElementById('submitted-tab');
                    if (submittedTabEl) {
                        const submittedTab = new bootstrap.Tab(submittedTabEl);
                        submittedTab.show();

                        setTimeout(function() {
                            window.location.reload();
                        }, 500);
                    }
                }, 1500);
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

    @stack('scripts')
    @yield('scripts')

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
                if (!data) return;
                var count = data.count || 0;
                var wrap  = document.getElementById('facultyFeedbackBellWrap');
                var badge = document.getElementById('facultyFeedbackBadge');
                var list  = document.getElementById('facultyFeedbackList');
                if (!wrap) return;
                if (count > 0) {
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