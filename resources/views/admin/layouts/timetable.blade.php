<!DOCTYPE html>
<html lang="en" style="height: 100%;">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Academic TimeTable - Sargam | Lal Bahadur Shastri National Academy of Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- Icon library (Bootstrap Icons or Lucide) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('admin_assets/css/accesibility-style_v1.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdn.ux4g.gov.in/UX4G@2.0.8/css/ux4g-min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #dcdcdc;
        }
        .star-rating {
            display: inline-flex;
            justify-content: flex-start;
        }

        .star-rating input[type="radio"] {
            display: none;
        }

        .star-rating label {
            font-size: 1.5rem;
            color: #ccc;
            cursor: pointer;
        }

        .star-rating input[type="radio"]:checked~label {
            color: #af2910;
        }

        .star-rating label:hover,
        .star-rating label:hover~label {
            color: #af2910;
        }

        /* Star Rating Style */
        .star-rating {
            position: relative;
            display: inline-flex;
        }

        .star-rating input {
            display: none;
        }

.star-rating label {
    font-size: 1.25rem;
    color: transparent;
    cursor: pointer;
    transition: color 0.2s ease-in-out;
    padding: 0 1px;
    -webkit-text-stroke: 2px #af2910;
    text-stroke: 2px #af2910;
}

.star-rating input:not(:checked)~label {
    color: transparent;
    -webkit-text-stroke: 2px #af2910;
    text-stroke: 2px #af2910;
}

        .table td,
        .table th {
            vertical-align: middle;
        }

        /* Tab Styles */
        .nav-tabs .nav-link {
            color: #495057;
            font-weight: 500;
            border: 1px solid transparent;
            border-radius: 0.375rem 0.375rem 0 0;
            padding: 0.75rem 1.5rem;
        }

        .nav-tabs .nav-link.active {
            color: #0d6efd;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }

        .nav-tabs .nav-link:hover {
            border-color: #e9ecef #e9ecef #dee2e6;
        }

        .tab-content {
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 0.375rem 0.375rem;
            padding: 1.5rem;
        }

        .star-rating-display {
            font-size: 1.25rem;
        }

        .remarks-text {
            font-size: 0.875rem;
            line-height: 1.4;
        }

        .tab-content {
            min-height: 400px;
        }

        /* Bulk Submit Button */
        .bulk-feedback-submit-btn {
            display: inline-flex;
            padding: 10px 24px;
            justify-content: center;
            align-items: center;
            gap: 8px;

            border-radius: 8px;
            background: var(--Surface-Action, #004A93);
            /* solid primary */
            border: 1px solid var(--Surface-Action, #004A93);

            color: #ffffff;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        .bulk-feedback-submit-btn:hover {
            background: var(--Surface-Action-Hover, #004384);
            border-color: var(--Surface-Action-Hover, #004384);
        }

        /* Individual Submit Button */
        /* Individual Submit Button Default */
        .individual-feedback-submit-btn {
            display: inline-flex;
            padding: 10px 24px;
            justify-content: center;
            align-items: center;
            gap: 8px;

            border-radius: 8px;
            /* fixed typo */
            border: 1px solid var(--Surface-Action, #004A93);
            background: #ffffff;
            /* default background */

            color: var(--Surface-Action, #004A93);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        /* Hover State */
        .individual-feedback-submit-btn:hover {
            border-color: var(--Surface-Action-Hover, #004384);
            background: var(--Information-50, #ECEDF8);
        }

        #table-loader {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.7);
            justify-content: center;
            align-items: center;
            z-index: 10;
            display: flex;
        }

        .spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
        .rating-legend {
    font-size: 0.875rem;
}

.rating-legend .legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 20px;
    background: #f8f9fa;
    border: 1px solid #e5e7eb;
}

.rating-legend .stars {
    color: #af2910;
    letter-spacing: 1px;
    font-size: 0.95rem;
}

.rating-legend .text {
    color: #374151;
    font-weight: 500;
}

    </style>
</head>
<x-session_message />

<body style="min-height: 100vh; display: flex; flex-direction: column;background-color: #dcdcdc;">
    <!-- Top Blue Bar (Govt of India) -->

    <!-- Sticky Header -->
    <div class="header sticky-top bg-white shadow-sm">
        <div class="container-fluid py-3    ">
            <nav class="navbar navbar-expand-lg">
                <div class="container-fluid px-0">
                    <a class="navbar-brand me-2" href="#">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg"
                            alt="Logo 1" height="80">
                    </a>
                    <span class="vr mx-2"></span>
                    <a class="navbar-brand" href="#">
                        <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="Logo 2" height="80">
                    </a>

                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                        <ul class="navbar-nav align-items-center">
                            <li class="nav-item">
                                <a class="nav-link ms-4 me-4" href="https://www.lbsnaa.gov.in/menu/about-lbsnaa"
                                    target="_blank">About Us</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link ms-4 me-4" href="https://www.lbsnaa.gov.in/footer_menu/contact-us"
                                    target="_blank">Contact</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>
    </div>


    <!-- Main Content -->
    @yield('content')

    <!-- Footer -->
    <footer class="mt-auto text-white py-3" style="background-color: #004a93;">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <p class="mb-0" style="font-size: 14px;">&copy; {{ date('Y') }} Lal Bahadur Shastri
                        National Academy
                        of Administration, Mussoorie, Uttarakhand</p>
                </div>
                <div class="col-md-4 text-end">
                    <ul class="list-unstyled d-flex justify-content-end mb-0">
                        <li class="me-3">
                            <a href="#" class="text-white text-decoration-none"
                                style="font-size: 14px; font-family: Inter;">Privacy Policy</a>
                        </li>
                        <li>
                            <a href="#" class="text-white text-decoration-none"
                                style="font-size: 14px; font-family: Inter;">Need Help</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JavaScript for Tab Functionality -->
    <script>
        $(document).ready(function() {
            // Initialize Bootstrap tabs
            const feedbackTabs = document.getElementById('feedbackTabs');
            const tab = new bootstrap.Tab(feedbackTabs.querySelector('button[data-bs-target="#pending-tab-pane"]'));

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
                $('.container.my-5').prepend(successAlert);

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
                $('.container.my-5').prepend(errorAlert);
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
