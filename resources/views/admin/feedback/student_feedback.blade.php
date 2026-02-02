<!DOCTYPE html>
<html lang="en" style="height: 100%;">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="description" content="Submit and view session feedback - Lal Bahadur Shastri National Academy of Administration" />
    <meta name="theme-color" content="#004a93" />
    <title>Feedback Form - Sargam | Lal Bahadur Shastri National Academy of Administration</title>
    <link rel="stylesheet" href="https://bootstrapdemos.adminmart.com/matdash/dist/assets/css/styles.css">
    <link rel="shortcut icon" type="image/ico" href="{{ asset('admin_assets/images/logos/favicon.ico') }}">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-steps/1.1.0/jquery.steps.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-steps/1.1.0/jquery.steps.min.js"></script>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('admin_assets/images/logos/favicon.ico') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="{{ asset('admin_assets/css/accesibility-style_v1.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdn.ux4g.gov.in/UX4G@2.0.8/css/ux4g-min.css" rel="stylesheet">

    <style>
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
            min-height: 280px;
        }
        @media (min-width: 768px) {
            .tab-content { min-height: 400px; }
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

        /* Touch target min 44px (GIGW/WCAG) */
        .min-h-44 { min-height: 44px; }
        /* GIGW / WCAG: Focus visible for keyboard users */
        .nav-link:focus-visible,
        .btn:focus-visible,
        .form-control:focus-visible,
        .form-select:focus-visible,
        .star-rating label:focus-within,
        .individual-feedback-submit-btn:focus-visible,
        .bulk-feedback-submit-btn:focus-visible {
            outline: 3px solid #004A93;
            outline-offset: 2px;
        }
        .skip-link {
            position: absolute;
            left: -9999px;
            z-index: 9999;
            padding: 0.75rem 1rem;
            background: #004A93;
            color: #fff;
            font-weight: 500;
        }
        .skip-link:focus {
            left: 0.5rem;
            top: 0.5rem;
        }
        /* Responsive: touch targets min 44px (GIGW) */
        @media (max-width: 767.98px) {
            .star-rating label { font-size: 1.5rem; padding: 0.25rem; min-width: 2.75rem; min-height: 2.75rem; display: inline-flex; align-items: center; justify-content: center; }
            .individual-feedback-submit-btn, .bulk-feedback-submit-btn { min-height: 44px; padding: 12px 20px; }
            .nav-tabs .nav-link { padding: 0.75rem 1rem; font-size: 0.9rem; }
            .rating-legend .legend-item { padding: 8px 12px; }
        }
        /* Table responsive wrapper */
        .table-responsive-cards { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        @media (max-width: 991.98px) {
            .table-responsive-cards { margin-left: -0.75rem; margin-right: -0.75rem; padding-left: 0.75rem; padding-right: 0.75rem; }
        }
        .card-header-tabs .nav-link { white-space: nowrap; }
        @media (max-width: 575.98px) {
            .card-header-tabs { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 2px; }
            .card-header-tabs .nav-item { flex: 0 0 auto; }
        }
        /* Header: Digital India + Developed by NeGD */
        .header-digital-negd {
            display: flex;
            align-items: center;
            gap: 0.5rem 0.75rem;
            padding: 0.35rem 0.75rem;
            background: linear-gradient(135deg, rgba(0, 74, 147, 0.08) 0%, rgba(0, 74, 147, 0.04) 100%);
            border-radius: 0.5rem;
            border: 1px solid rgba(0, 74, 147, 0.2);
        }
        .header-digital-negd img { height: 32px; width: auto; }
        .header-digital-negd .negd-badge { font-size: 0.7rem; color: #004a93; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
        @media (max-width: 991.98px) {
            .header-digital-negd .negd-badge { font-size: 0.65rem; }
            .header-digital-negd img { height: 28px; }
        }
        @media (max-width: 575.98px) {
            .header-digital-negd .negd-badge { display: none; }
            .header-digital-negd img { height: 26px; }
        }
        /* Footer: NeGD credit bar */
        .footer-negd-credit {
            background: rgba(0, 26, 61, 0.95);
            padding: 0.5rem 0;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .footer-negd-credit a {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            font-size: 0.8125rem;
            transition: opacity 0.2s;
        }
        .footer-negd-credit a:hover { color: #fff; opacity: 0.95; }
        .footer-negd-credit img { height: 22px; width: auto; }
    </style>
</head>
<x-session_message />

<body style="min-height: 100vh; display: flex; flex-direction: column;" class="d-flex flex-column">
    <a href="#content" class="skip-link text-decoration-none">Skip to main content</a>
    <!-- Top Blue Bar (Govt of India) - GIGW compliant -->
    <header class="top-header d-flex justify-content-between align-items-center d-none d-md-flex py-2" style="background-color: #004a93;" role="banner">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-3 d-flex align-items-center">
                    <img src="https://upload.wikimedia.org/wikipedia/en/thumb/4/41/Flag_of_India.svg/800px-Flag_of_India.svg.png"
                        alt="Government of India emblem" height="30" width="45" loading="lazy">
                    <span class="ms-2 text-white small">Government of India</span>
                </div>
                <div class="col-md-9 text-md-end d-flex justify-content-end align-items-center">
                    <nav aria-label="Utility navigation">
                        <ul class="nav justify-content-end align-items-center list-unstyled mb-0">
                        <li class="nav-item"><a href="#content" class="text-white text-decoration-none small"
                                id="skip-content-link">Skip to Main Content</a></li>
                        <span class="text-muted me-3 ms-3">|</span>
                        <li class="nav-item"><a class="text-decoration-none" id="uw-widget-custom-trigger"
                                contenteditable="false" style="cursor: pointer;"><img
                                    src="{{ asset('images/accessible.png') }}" alt="" width="20">
                                <span class="text-white ms-1 small">More</span>
                            </a>
                        </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <!-- Sticky Header: GoI Emblem | LBSNAA | Digital India (Developed by NeGD) -->
    <div class="header sticky-top bg-white shadow-sm" role="banner">
        <div class="container-fluid py-2 py-md-3">
            <nav class="navbar navbar-expand-lg">
                <div class="container-fluid px-0 flex-wrap">
                    <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
                        <a class="navbar-brand me-2" href="https://www.lbsnaa.gov.in/" target="_blank" rel="noopener noreferrer" aria-label="State Emblem of India">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg"
                                alt="State Emblem of India" height="56" width="56" class="d-none d-sm-block" loading="lazy">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg"
                                alt="State Emblem of India" height="40" width="40" class="d-block d-sm-none" loading="lazy">
                        </a>
                        <span class="vr mx-1 mx-md-2 d-none d-sm-inline" aria-hidden="true"></span>
                        <a class="navbar-brand py-0" href="https://www.lbsnaa.gov.in/" target="_blank" rel="noopener noreferrer" aria-label="LBSNAA - Lal Bahadur Shastri National Academy of Administration">
                            <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="LBSNAA" height="56" width="auto" class="d-none d-sm-block" style="max-height: 64px;" loading="lazy">
                            <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="LBSNAA" height="40" width="auto" class="d-block d-sm-none" style="max-height: 48px;" loading="lazy">
                        </a>
                    </div>
                    <a href="https://digitalindia.gov.in/" target="_blank" rel="noopener noreferrer" class="header-digital-negd text-decoration-none order-2 order-lg-2 mt-2 mt-lg-0 ms-lg-auto me-lg-2 flex-shrink-0" aria-label="Digital India - Website developed by NeGD">
                        <img src="{{ asset('images/digital.png') }}" alt="Digital India" loading="lazy"
                            onerror="this.src='https://upload.wikimedia.org/wikipedia/en/thumb/9/95/Digital_India_logo.svg/400px-Digital_India_logo.svg.png'">
                        <span class="negd-badge">Developed by NeGD</span>
                    </a>
                    <button class="navbar-toggler border-2 order-first order-lg-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                        aria-controls="navbarNav" aria-expanded="false" aria-label="Open main menu">
                        <span class="navbar-toggler-icon" aria-hidden="true"></span>
                    </button>

                    <div class="collapse navbar-collapse justify-content-end" id="navbarNav" role="navigation" aria-label="Main navigation">
                        <ul class="navbar-nav align-items-center">
                            <li class="nav-item">
                                <a class="nav-link ms-2 ms-md-4 me-2 me-md-4 py-2" href="https://www.lbsnaa.gov.in/menu/about-lbsnaa"
                                    target="_blank" rel="noopener noreferrer">About Us</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link ms-2 ms-md-4 me-2 me-md-4 py-2" href="https://www.lbsnaa.gov.in/footer_menu/contact-us"
                                    target="_blank" rel="noopener noreferrer">Contact</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>
    </div>

    <!-- Accessibility Panel -->
    <div class="uwaw uw-light-theme gradient-head uwaw-initial paid_widget" id="uw-main">
        <div class="relative second-panel">
            <h3>Accessibility options by LBSNAA</h3>
            <div class="uwaw-close" onclick="closeMain()"></div>
        </div>
        <div class="uwaw-body">
            <div class="lang">
                <div class="lang_head">
                    <i></i>
                    <span>Language</span>
                </div>
                <div class="language_drop" id="google_translate_element">
                    <!-- google translate list coming inside here -->
                </div>
            </div>
            <div class="h-scroll">
                <div class="uwaw-features">
                    <!-- Accessibility features remain the same -->
                    <div class="uwaw-features__item reset-feature" id="featureItem_sp">
                        <button id="speak" class="uwaw-features__item__i"
                            data-uw-reader-content="Enable the UserWay screen reader"
                            aria-label="Enable the UserWay screen reader" aria-pressed="false">
                            <span class="uwaw-features__item__icon">
                                <span class="icon icon-speaker"> </span>
                            </span>
                            <span class="uwaw-features__item__name">Screen Reader</span>
                            <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon_sp"
                                style="display: none">
                            </span>
                        </button>
                    </div>
                    <!-- Other accessibility features... -->
                </div>
            </div>
        </div>
        <div class="reset-panel">
            <div class="copyrights-accessibility">
                <button class="btn-reset-all" id="reset-all" onclick="resetAll()">
                    <span class="reset-icon"> </span>
                    <span class="reset-btn-text">Reset All Settings</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content - GIGW: landmark and skip target -->
    <main id="content" class="container-fluid my-3 my-md-4 my-lg-5 px-3 px-md-4" role="main" tabindex="-1">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-header text-center rounded-0 py-3 py-md-4" style="background-color: #591512;">
                <h1 class="h4 mb-0 text-white fw-bold" style="font-family:Inter;">Session Feedbacks</h1>
            </div>

            <!-- Date Filter - responsive -->
            <div class="card-header bg-light border-bottom px-3 px-md-4 py-3">
                <div class="row align-items-end g-2 g-md-3">
                    <div class="col-12 col-sm-auto">
                        <label for="date-filter" class="form-label mb-0 fw-semibold small text-body-secondary">
                            <i class="bi bi-calendar-event me-1" aria-hidden="true"></i>Filter by date
                        </label>
                        <input type="date" class="form-control mt-1 w-100" id="date-filter"
                            style="max-width: 220px;" aria-describedby="date-filter-desc">
                        <span id="date-filter-desc" class="visually-hidden">Select a date to show only feedback for that day</span>
                    </div>
                    <div class="col-12 col-sm-auto ms-sm-auto text-start text-sm-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary min-h-44" id="clear-date-filter"
                            style="display: none;" aria-label="Clear date filter">
                            <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Clear filter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation - scrollable on small screens -->
            <div class="card-header bg-light border-bottom-0 px-0">
                <ul class="nav nav-tabs card-header-tabs border-0 nav-fill px-3 px-md-4" id="feedbackTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-0 py-3" id="pending-tab" data-bs-toggle="tab"
                            data-bs-target="#pending-tab-pane" type="button" role="tab"
                            aria-controls="pending-tab-pane" aria-selected="true">
                            <i class="bi bi-clock me-1 me-md-2" aria-hidden="true"></i><span class="d-none d-sm-inline">Pending </span>Feedback
                            <span class="badge bg-danger ms-1 ms-md-2" id="pending-count" aria-live="polite">{{ $pendingData->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-0 py-3" id="submitted-tab" data-bs-toggle="tab"
                            data-bs-target="#submitted-tab-pane" type="button" role="tab"
                            aria-controls="submitted-tab-pane" aria-selected="false">
                            <i class="bi bi-check-circle me-1 me-md-2" aria-hidden="true"></i><span class="d-none d-sm-inline">Submitted </span>Feedback
                            <span class="badge bg-success ms-1 ms-md-2" id="submitted-count" aria-live="polite">{{ $submittedData->count() }}</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tabs Content -->
            <div class="tab-content bg-white" id="feedbackTabsContent">
                <!-- Pending Feedback Tab -->
                <div class="tab-pane fade show active" id="pending-tab-pane" role="tabpanel"
                    aria-labelledby="pending-tab" tabindex="0">
                    @if ($pendingData->count() > 0)
                        <form id="vertical-wizard" method="POST" action="{{ route('feedback.submit.feedback') }}" novalidate>
                            @csrf
                            <div class="card-body mb-0 mb-md-4 p-3 p-md-4">
                                <div class="rating-legend d-flex flex-wrap gap-2 gap-md-3 align-items-center mt-2 mb-3 px-0" role="group" aria-label="Rating scale legend">
                                    <span class="visually-hidden">Rating scale: 5 stars Excellent, 4 Very Good, 3 Good, 2 Average, 1 Below Average</span>
                                        <span class="legend-item">
                                            <span class="stars">★★★★★</span>
                                            <span class="text">Excellent</span>
                                        </span>
                                        <span class="legend-item">
                                            <span class="stars">★★★★</span>
                                            <span class="text">Very Good</span>
                                        </span>
                                        <span class="legend-item">
                                            <span class="stars">★★★</span>
                                            <span class="text">Good</span>
                                        </span>
                                        <span class="legend-item">
                                            <span class="stars">★★</span>
                                            <span class="text">Average</span>
                                        </span>
                                        <span class="legend-item">
                                            <span class="stars">★</span>
                                            <span class="text">Below Average</span>
                                        </span>
                                </div>
                                <div class="table-responsive table-responsive-cards">
                                    <table class="table table-hover align-middle mb-0 rounded" role="grid" aria-label="Pending feedback sessions">
                                        <thead class="bg-danger text-white">
                                            <tr>
                                                <th scope="col" class="text-center text-white">S.No.</th>
                                                <th scope="col" class="text-center text-white">Date &amp; Time</th>
                                                <th scope="col" class="text-center text-white">Topic</th>
                                                <th scope="col" class="text-center text-white">Faculty</th>
                                                <th scope="col" class="text-center text-white">Content rating</th>
                                                <th scope="col" class="text-center text-white">Presentation rating</th>
                                                <th scope="col" class="text-center text-white">Remarks</th>
                                                <th scope="col" class="text-center text-white">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="pending-feedback-body">
                                            @php $pendingIndex = 0; @endphp
                                            @foreach ($pendingData as $feedback)
                                                @if ($feedback->feedback_checkbox == 1)
                                                    <tr class="text-center"
                                                        data-feedback-date="{{ \Carbon\Carbon::parse($feedback->from_date)->format('Y-m-d') }}">
                                                        <td class="text-center">{{ ++$pendingIndex }}</td>
                                                        <td>
                                                            {{ \Carbon\Carbon::parse($feedback->from_date)->format('d-m-Y') }}
                                                            <br>
                                                            <small
                                                                class="text-muted">{{ $feedback->class_session }}</small>
                                                        </td>
                                                        <td>{{ $feedback->subject_topic }}</td>
                                                        <td>{{ $feedback->faculty_name }}</td>

                                                        {{-- Content Rating --}}
                                                        <td>
                                                            @if ($feedback->Ratting_checkbox == 1)
                                                                <div class="star-rating d-inline-flex flex-row-reverse" role="group" aria-label="Rate content for row {{ $pendingIndex }}">
                                                                    @for ($i = 5; $i >= 1; $i--)
                                                                        <input type="radio"
                                                                            id="content-{{ $i }}-{{ $loop->index }}"
                                                                            name="content[{{ $loop->index }}]"
                                                                            value="{{ $i }}"
                                                                            aria-label="Content rating {{ $i }} star{{ $i > 1 ? 's' : '' }}"
                                                                            {{ old('content.' . $loop->index) == $i ? 'checked' : '' }}>
                                                                        <label for="content-{{ $i }}-{{ $loop->index }}">&#9733;</label>
                                                                    @endfor
                                                                </div>
                                                            @endif
                                                        </td>

                                                        {{-- Presentation Rating --}}
                                                        <td>
                                                            @if ($feedback->Ratting_checkbox == 1)
                                                                <div class="star-rating d-inline-flex flex-row-reverse" role="group" aria-label="Rate presentation for row {{ $pendingIndex }}">
                                                                    @for ($i = 5; $i >= 1; $i--)
                                                                        <input type="radio"
                                                                            id="presentation-{{ $i }}-{{ $loop->index }}"
                                                                            name="presentation[{{ $loop->index }}]"
                                                                            value="{{ $i }}"
                                                                            aria-label="Presentation rating {{ $i }} star{{ $i > 1 ? 's' : '' }}"
                                                                            {{ old('presentation.' . $loop->index) == $i ? 'checked' : '' }}>
                                                                        <label for="presentation-{{ $i }}-{{ $loop->index }}">&#9733;</label>
                                                                    @endfor
                                                                </div>
                                                            @endif
                                                        </td>

                                                        {{-- Remarks --}}
                                                        <td style="min-width: 160px;">
                                                            @if ($feedback->Remark_checkbox == 1)
                                                                <label for="remarks-{{ $loop->index }}" class="visually-hidden">Remarks for this session</label>
                                                                <textarea class="form-control form-control-sm" id="remarks-{{ $loop->index }}" name="remarks[{{ $loop->index }}]" rows="2"
                                                                    placeholder="Enter remarks (optional)">{{ old('remarks.' . $loop->index) }}</textarea>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <button type="button"
                                                                onclick="submitIndividual({{ $loop->index }})"
                                                                class="individual-feedback-submit-btn"
                                                                aria-label="Submit feedback for row {{ $pendingIndex }}">
                                                                Submit
                                                            </button>
                                                        </td>

                                                        <!-- Hidden Inputs -->
                                                        <input type="hidden"
                                                            name="timetable_pk[{{ $loop->index }}]"
                                                            value="{{ $feedback->timetable_pk . '_' . $feedback->faculty_pk }}">
                                                        <input type="hidden" name="faculty_pk[{{ $loop->index }}]"
                                                            value="{{ $feedback->faculty_pk }}">
                                                        <input type="hidden"
                                                            name="original_timetable_pk[{{ $loop->index }}]"
                                                            value="{{ $feedback->timetable_pk }}">
                                                        <input type="hidden" name="topic_name[{{ $loop->index }}]"
                                                            value="{{ $feedback->subject_topic }}">
                                                        <input type="hidden"
                                                            name="Ratting_checkbox[{{ $loop->index }}]"
                                                            value="{{ $feedback->Ratting_checkbox }}">
                                                        <input type="hidden"
                                                            name="Remark_checkbox[{{ $loop->index }}]"
                                                            value="{{ $feedback->Remark_checkbox }}">
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <div id="table-loader" class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-flex justify-content-center align-items-center" style="display: none; z-index: 10;" role="status" aria-live="polite" aria-label="Submitting feedback">
                                        <div class="text-center">
                                            <svg class="spinner" width="32" height="32" viewBox="0 0 50 50" aria-hidden="true">
                                                <circle cx="25" cy="25" r="20" stroke="#004A93" stroke-width="5" fill="transparent" />
                                            </svg>
                                            <p class="mt-2 fw-medium">Submitting...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-3 mb-3 mb-md-4 me-0 me-md-4 px-3 px-md-0">
                                <button type="submit" class="bulk-feedback-submit-btn w-100 w-sm-auto min-h-44" aria-label="Submit all feedback">
                                    <i class="bi bi-send me-1" aria-hidden="true"></i>Submit All Feedback
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="text-center py-5 px-3">
                            <i class="bi bi-check-circle text-success display-4" aria-hidden="true"></i>
                            <h2 class="h5 mt-3">No pending feedback</h2>
                            <p class="text-body-secondary mb-0">All feedback has been submitted.</p>
                        </div>
                    @endif
                </div>

                <!-- Submitted Feedback Tab -->
                <div class="tab-pane fade" id="submitted-tab-pane" role="tabpanel" aria-labelledby="submitted-tab" tabindex="0">
                    <div class="card-body mb-0 mb-md-4 p-3 p-md-4">
                        <div class="table-responsive table-responsive-cards">
                            <table class="table table-bordered align-middle mb-0" role="grid" aria-label="Submitted feedback history">
                                <thead class="bg-success text-white">
                                    <tr>
                                        <th scope="col" class="text-center text-white">S.No.</th>
                                        <th scope="col" class="text-center text-white">Date &amp; Time</th>
                                        <th scope="col" class="text-center text-white">Topic</th>
                                        <th scope="col" class="text-center text-white">Faculty</th>
                                        <th scope="col" class="text-center text-white">Content</th>
                                        <th scope="col" class="text-center text-white">Presentation</th>
                                        <th scope="col" class="text-center text-white">Remarks</th>
                                        <th scope="col" class="text-center text-white">Submitted On</th>
                                    </tr>
                                </thead>
                                <tbody id="submitted-feedback-body">
                                    @if ($submittedData->count() > 0)
                                        @php $submittedIndex = 0; @endphp
                                        @foreach ($submittedData as $feedback)
                                            <tr class="text-center"
                                                data-feedback-date="{{ \Carbon\Carbon::parse($feedback->from_date)->format('Y-m-d') }}">
                                                <td class="text-center">{{ ++$submittedIndex }}</td>
                                                <td>
                                                    {{ \Carbon\Carbon::parse($feedback->from_date)->format('d-m-Y') }}
                                                    <br>
                                                    <small class="text-muted">{{ $feedback->class_session }}</small>
                                                </td>
                                                <td>{{ $feedback->subject_topic }}</td>
                                                <td>{{ $feedback->faculty_name }}</td>

                                                {{-- Content Rating --}}
                                                <td>
                                                    @if ($feedback->Ratting_checkbox == 1 && $feedback->content)
                                                        <div class="star-rating-display">
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                @if ($i <= $feedback->content)
                                                                    <span class="text-warning">★</span>
                                                                @else
                                                                    <span class="text-secondary">★</span>
                                                                @endif
                                                            @endfor
                                                            <br>
                                                            <small
                                                                class="text-muted">({{ $feedback->content }}/5)</small>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>

                                                {{-- Presentation Rating --}}
                                                <td>
                                                    @if ($feedback->Ratting_checkbox == 1 && $feedback->presentation)
                                                        <div class="star-rating-display">
                                                            @for ($i = 1; $i <= 5; $i++)
                                                                @if ($i <= $feedback->presentation)
                                                                    <span class="text-warning">★</span>
                                                                @else
                                                                    <span class="text-secondary">★</span>
                                                                @endif
                                                            @endfor
                                                            <br>
                                                            <small
                                                                class="text-muted">({{ $feedback->presentation }}/5)</small>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>

                                                {{-- Remarks --}}
                                                <td style="min-width: 180px;">
                                                    @if ($feedback->Remark_checkbox == 1 && $feedback->remark)
                                                        <div class="remarks-text"
                                                            style="max-height: 60px; overflow-y: auto;">
                                                            {{ $feedback->remark }}
                                                        </div>
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>

                                                {{-- Submitted Date --}}
                                                <td>
                                                    {{ \Carbon\Carbon::parse($feedback->created_date)->format('d-m-Y') }}
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ \Carbon\Carbon::parse($feedback->created_date)->format('h:i A') }}
                                                    </small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="8" class="text-center py-5 px-3">
                                                <i class="bi bi-inbox text-body-secondary display-4" aria-hidden="true"></i>
                                                <h2 class="h5 mt-3">No submitted feedback yet</h2>
                                                <p class="text-body-secondary mb-0">Your submitted feedback will appear here.</p>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
        </div>
    </div>
    </main>

    <!-- Footer - responsive and GIGW -->
    <footer class="mt-auto text-white" style="background-color: #004a93;" role="contentinfo">
        <div class="container-fluid px-3 px-md-4 py-3 py-md-4">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-8 order-2 order-md-1 text-center text-md-start">
                    <p class="mb-0 small">&copy; {{ date('Y') }} Lal Bahadur Shastri National Academy of Administration, Mussoorie, Uttarakhand</p>
                </div>
                <div class="col-12 col-md-4 order-1 order-md-2">
                    <ul class="list-unstyled d-flex flex-wrap justify-content-center justify-content-md-end gap-2 gap-md-3 mb-0">
                        <li>
                            <a href="#" class="text-white text-decoration-none small">Privacy Policy</a>
                        </li>
                        <li>
                            <a href="#" class="text-white text-decoration-none small">Need Help</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- NeGD credit - Developed by National e-Governance Division -->
        <div class="footer-negd-credit">
            <div class="container-fluid px-3 px-md-4 text-center">
                <a href="https://negd.gov.in/" target="_blank" rel="noopener noreferrer" aria-label="Powered by National e-Governance Division, MeitY">
                    <img src="{{ asset('images/negd.png') }}" alt="NeGD - National e-Governance Division" loading="lazy" onerror="this.style.display='none'">
                    <span>Powered by <strong>National e-Governance Division (NeGD)</strong>, MeitY</span>
                </a>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <!-- Accessibility Widget -->
    <script src="https://cdn.ux4g.gov.in/tools/accessibility-widget.js" async></script>

    <!-- JavaScript for Tab Functionality -->
    <script>
    $(document).ready(function() {
        // Initialize Bootstrap tabs
        const feedbackTabs = document.getElementById('feedbackTabs');
        const tab = new bootstrap.Tab(feedbackTabs.querySelector('button[data-bs-target="#pending-tab-pane"]'));

        // Add form validation - PLACE IT HERE
        $('#vertical-wizard').validate({
            rules: {
                'timetable_pk[]': {
                    required: true
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
                $('#table-loader').css('display', 'flex').show();

                // Disable all buttons while submitting
                $(form).find('button[type="submit"], .individual-feedback-submit-btn').prop('disabled', true);
                
                // Filter out rows that don't have any feedback
                let hasFeedback = false;
                $('tr[data-feedback-date]').each(function() {
                    const contentChecked = $(this).find('input[name^="content"]:checked').length;
                    const presentationChecked = $(this).find('input[name^="presentation"]:checked').length;
                    const remarks = $(this).find('textarea[name^="remarks"]').val().trim();
                    
                    if (contentChecked > 0 || presentationChecked > 0 || remarks !== '') {
                        hasFeedback = true;
                    }
                });
                
                if (!hasFeedback) {
                    $('#table-loader').hide();
                    $(form).find('button[type="submit"], .individual-feedback-submit-btn').prop('disabled', false);
                    alert('Please provide feedback for at least one session.');
                    return false;
                }

                form.submit();
            }
        });

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
            $('#content').prepend(successAlert);

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
            $('#content').prepend(errorAlert);
        @endif

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

        // Date Filter Functionality
        $('#date-filter').on('change', function() {
            const selectedDate = $(this).val();
            filterByDate(selectedDate);
            
            // Show/hide clear button
            if (selectedDate) {
                $('#clear-date-filter').show();
            } else {
                $('#clear-date-filter').hide();
            }
        });

        // Clear Date Filter
        $('#clear-date-filter').on('click', function() {
            $('#date-filter').val('');
            filterByDate('');
            $(this).hide();
        });

        // Function to filter rows by date
        function filterByDate(selectedDate) {
            // Get active tab
            const activeTab = $('.tab-pane.active');
            const tbody = activeTab.find('tbody');
            
            let visibleCount = 0;
            const dataRows = tbody.find('tr[data-feedback-date]');
            const emptyStateRow = tbody.find('tr:not([data-feedback-date])');
            
            if (selectedDate) {
                // Filter rows based on selected date
                dataRows.each(function() {
                    const rowDate = $(this).attr('data-feedback-date');
                    if (rowDate === selectedDate) {
                        $(this).show();
                        visibleCount++;
                    } else {
                        $(this).hide();
                    }
                });
                
                // Hide empty state row if we have matching rows, show it if no matches
                if (visibleCount > 0) {
                    emptyStateRow.hide();
                } else {
                    emptyStateRow.show();
                }
            } else {
                // Show all data rows
                dataRows.each(function() {
                    $(this).show();
                    visibleCount++;
                });
                
                // Show empty state only if there are no data rows at all
                if (dataRows.length === 0) {
                    emptyStateRow.show();
                } else {
                    emptyStateRow.hide();
                }
            }
            
            // Update badge counts based on active tab
            if (activeTab.attr('id') === 'pending-tab-pane') {
                $('#pending-count').text(visibleCount);
            } else if (activeTab.attr('id') === 'submitted-tab-pane') {
                $('#submitted-count').text(visibleCount);
            }
        }

        // Re-apply filter when tab changes
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function() {
            const selectedDate = $('#date-filter').val();
            if (selectedDate) {
                filterByDate(selectedDate);
            }
        });
    });

    // Individual row submission
    function submitIndividual(index) {
        // Show loader inside table
        $('#table-loader').css('display', 'flex').show();
        
        // Disable all buttons
        $('.individual-feedback-submit-btn, .bulk-feedback-submit-btn').prop('disabled', true);
        
        // Create a temporary form for single row submission
        const form = $('<form>', {
            method: 'POST',
            action: '{{ route('feedback.submit.feedback') }}',
            id: 'individual-form-' + index
        }).append('@csrf');
        
        // Add a hidden input to indicate this is an individual submission
        form.append('<input type="hidden" name="submit_index" value="' + index + '">');
        
        // Append only the row inputs for this specific index
        $(`input[name="timetable_pk[${index}]"]`).clone().appendTo(form);
        $(`input[name="faculty_pk[${index}]"]`).clone().appendTo(form);
        $(`input[name="original_timetable_pk[${index}]"]`).clone().appendTo(form);
        $(`input[name="topic_name[${index}]"]`).clone().appendTo(form);
        $(`input[name="Ratting_checkbox[${index}]"]`).clone().appendTo(form);
        $(`input[name="Remark_checkbox[${index}]"]`).clone().appendTo(form);
        
        // Get checked radio buttons
        const contentChecked = $(`input[name="content[${index}]"]:checked`);
        if (contentChecked.length > 0) {
            contentChecked.clone().appendTo(form);
        }
        
        const presentationChecked = $(`input[name="presentation[${index}]"]:checked`);
        if (presentationChecked.length > 0) {
            presentationChecked.clone().appendTo(form);
        }
        
        // Get remarks textarea
        $(`textarea[name="remarks[${index}]"]`).clone().appendTo(form);
        
        $('body').append(form);
        
        // Submit the form
        setTimeout(function() {
            form.submit();
        }, 100);
    }
</script>

</body>

</html>
