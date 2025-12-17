<!DOCTYPE html>
<html lang="en" style="height: 100%;">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Feedback Form - Sargam | Lal Bahadur Shastri National Academy of Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://bootstrapdemos.adminmart.com/matdash/dist/assets/css/styles.css">
    <!-- Favicon icon-->
    <link rel="shortcut icon" type="image/ico" href="{{asset('admin_assets/images/logos/favicon.ico')}}">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- jQuery Validation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>

    <!-- jQuery Steps -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-steps/1.1.0/jquery.steps.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-steps/1.1.0/jquery.steps.min.js"></script>
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="{{asset('admin_assets/images/logos/favicon.ico')}}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- Icon library (Bootstrap Icons or Lucide) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{asset('admin_assets/css/accesibility-style_v1.css')}}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">
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
        color: #ffce54;
        cursor: pointer;
        transition: color 0.2s ease-in-out;
        padding: 0 1px;
    }

    .star-rating input:not(:checked)~label {
        color: #ffe8a1;
    }

    .table td,
    .table th {
        vertical-align: middle;
    }
    </style>
</head>
<x-session_message />


<body style="min-height: 100vh; display: flex; flex-direction: column;">
    <!-- Top Blue Bar (Govt of India) -->
    <div class="top-header d-flex justify-content-between align-items-center d-none d-md-block py-2"
        style="background-color: #004a93;">
        <div class="container">
            <div class="row">
                <div class="col-md-3 d-flex align-items-center">
                    <img src="https://upload.wikimedia.org/wikipedia/en/thumb/4/41/Flag_of_India.svg/800px-Flag_of_India.svg.png"
                        alt="GoI Logo" height="30">
                    <span class="ms-2 text-white" style="font-size: 14px;">Government of India</span>
                </div>
                <div class="col-md-9 text-end d-flex justify-content-end align-items-center">
                    <ul class="nav justify-content-end align-items-center">
                        <li class="nav-item"><a href="#content" class="text-white text-decoration-none"
                                style=" font-size: 12px;">Skip to Main Content</a></li>
                        <span class="text-muted me-3 ms-3">|</span>
                        <li class="nav-item"><a class="text-decoration-none" id="uw-widget-custom-trigger"
                                contenteditable="false" style="cursor: pointer;"><img
                                    src="{{ asset('images/accessible.png') }}" alt="" width="20">
                                <span class="text-white ms-1" style=" font-size: 12px;">
                                    More
                                </span>
                            </a>

                        </li>

                    </ul>
                </div>
            </div>
        </div>
    </div>
    <!-- Sticky Header -->
    <div class="header sticky-top bg-white shadow-sm">
        <div class="container">
            <nav class="navbar navbar-expand-lg">
                <div class="container-fluid px-0">
                    <a class="navbar-brand me-2" href="#">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg" alt="Logo 1"
                            height="80">
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
    <!-- accessibility html -->
    <!-- accessibility panel -->
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

                    <div class="uwaw-features__item reset-feature" id="featureItem">
                        <button id="btn-s9" class="uwaw-features__item__i"
                            data-uw-reader-content="Enable the UserWay screen reader"
                            aria-label="Enable the UserWay screen reader" aria-pressed="false">
                            <span class="uwaw-features__item__icon">
                                <span class="icon icon-bigger-text"> </span> </span><span
                                class="uwaw-features__item__name">Bigger
                                Text</span>
                            <div class="uwaw-features__item__steps reset-steps" id="featureSteps">
                                <!-- Steps span tags will be dynamically added here -->
                            </div>
                            <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon"
                                style="display: none">
                            </span>
                        </button>
                    </div>

                    <div class="uwaw-features__item reset-feature" id="featureItem-st">
                        <button id="btn-small-text" class="uwaw-features__item__i"
                            data-uw-reader-content="Enable the UserWay screen reader"
                            aria-label="Enable the UserWay screen reader" aria-pressed="false">
                            <span class="uwaw-features__item__icon">
                                <span class="icon icon-small-text"> </span> </span><span
                                class="uwaw-features__item__name">Small
                                Text</span>
                            <div class="uwaw-features__item__steps reset-steps" id="featureSteps-st">
                                <!-- Steps span tags will be dynamically added here -->
                            </div>
                            <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon-st"
                                style="display: none">
                            </span>
                        </button>
                    </div>

                    <div class="uwaw-features__item reset-feature" id="featureItem-lh">
                        <button id="btn-s12" class="uwaw-features__item__i"
                            data-uw-reader-content="Enable the UserWay screen reader"
                            aria-label="Enable the UserWay screen reader" aria-pressed="false">
                            <span class="uwaw-features__item__icon">
                                <span class="icon icon-line-hight"> </span>
                            </span>
                            <span class="uwaw-features__item__name">Line Height</span>
                            <div class="uwaw-features__item__steps reset-steps" id="featureSteps-lh">
                                <!-- Steps span tags will be dynamically added here -->
                            </div>
                            <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon-lh"
                                style="display: none">
                            </span>
                        </button>
                    </div>

                    <div class="uwaw-features__item reset-feature" id="featureItem-ht">
                        <button id="btn-s10" onclick="highlightLinks()" class="uwaw-features__item__i"
                            data-uw-reader-content="Enable the UserWay screen reader"
                            aria-label="Enable the UserWay screen reader" aria-pressed="false">
                            <span class="uwaw-features__item__icon">
                                <span class="icon icon-highlight-links"> </span>
                            </span>
                            <span class="uwaw-features__item__name">Highlight Links</span>
                            <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon-ht"
                                style="display: none">
                            </span>
                        </button>
                    </div>

                    <div class="uwaw-features__item reset-feature" id="featureItem-ts">
                        <button id="btn-s13" onclick="increaseAndReset()" class="uwaw-features__item__i"
                            data-uw-reader-content="Enable the UserWay screen reader"
                            aria-label="Enable the UserWay screen reader" aria-pressed="false">
                            <span class="uwaw-features__item__icon">
                                <span class="icon icon-text-spacing"> </span>
                            </span>
                            <span class="uwaw-features__item__name">Text Spacing</span>
                            <div class="uwaw-features__item__steps reset-steps" id="featureSteps-ts">
                                <!-- Steps span tags will be dynamically added here -->
                            </div>
                            <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon-ts"
                                style="display: none">
                            </span>
                        </button>
                    </div>

                    <div class="uwaw-features__item reset-feature" id="featureItem-df">
                        <button id="btn-df" onclick="toggleFontFeature()" class="uwaw-features__item__i"
                            data-uw-reader-content="Enable the UserWay screen reader"
                            aria-label="Enable the UserWay screen reader" aria-pressed="false">
                            <span class="uwaw-features__item__icon">
                                <span class="icon icon-dyslexia-font"> </span>
                            </span>
                            <span class="uwaw-features__item__name">Dyslexia Friendly</span>
                            <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon-df"
                                style="display: none">
                            </span>
                        </button>
                    </div>

                    <div class="uwaw-features__item reset-feature" id="featureItem-hi">
                        <button id="btn-s11" onclick="toggleImages()" class="uwaw-features__item__i"
                            data-uw-reader-content="Enable the UserWay screen reader"
                            aria-label="Enable the UserWay screen reader" aria-pressed="false">
                            <span class="uwaw-features__item__icon">
                                <span class="icon icon-hide-images"> </span>
                            </span>
                            <span class="uwaw-features__item__name">Hide Images</span>
                            <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon-hi"
                                style="display: none">
                            </span>
                        </button>
                    </div>

                    <div class="uwaw-features__item reset-feature" id="featureItem-Cursor">
                        <button id="btn-cursor" onclick="toggleCursorFeature()" class="uwaw-features__item__i"
                            data-uw-reader-content="Enable the UserWay screen reader"
                            aria-label="Enable the UserWay screen reader" aria-pressed="false">
                            <span class="uwaw-features__item__icon">
                                <span class="icon icon-cursor"> </span>
                            </span>
                            <span class="uwaw-features__item__name">Cursor</span>
                            <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon-cursor"
                                style="display: none">
                            </span>
                        </button>
                    </div>

                    <div class="uwaw-features__item reset-feature" id="featureItem-ht-dark">
                        <button id="dark-btn" class="uwaw-features__item__i"
                            data-uw-reader-content="Enable the UserWay screen reader"
                            aria-label="Enable the UserWay screen reader" aria-pressed="false">
                            <span class="uwaw-features__item__name">
                                <span class="light_dark_icon">
                                    <input type="checkbox" class="light_mode uwaw-featugres__item__i" id="checkbox" />
                                    <label for="checkbox" class="checkbox-label">
                                        <!-- <i class="fas fa-moon-stars"></i> -->
                                        <i class="fas fa-moon-stars">
                                            <span class="icon icon-moon"></span>
                                        </i>
                                        <i class="fas fa-sun">
                                            <span class="icon icon-sun"></span>
                                        </i>
                                        <span class="ball"></span>
                                    </label>
                                </span>
                                <span class="uwaw-features__item__name">Light-Dark</span>
                            </span>
                            <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon-ht-dark"
                                style="display: none; pointer-events: none">
                            </span>
                        </button>
                    </div>

                    <!-- Invert Colors Widget -->

                    <div class="uwaw-features__item reset-feature" id="featureItem-ic">
                        <button id="btn-invert" class="uwaw-features__item__i"
                            data-uw-reader-content="Enable the UserWay screen reader"
                            aria-label="Enable the UserWay screen reader" aria-pressed="false">
                            <span class="uwaw-features__item__icon">
                                <span class="icon icon-invert"> </span>
                            </span>
                            <span class="uwaw-features__item__name">Invert Colors</span>
                            <span class="tick-active uwaw-features__item__enabled reset-tick" id="tickIcon-ic"
                                style="display: none">
                            </span>
                        </button>
                    </div>
                </div>
            </div>
            <!-- Reset Button -->

        </div>
        <div class="reset-panel">

            <!-- copyright accessibility bar -->
            <div class="copyrights-accessibility">
                <button class="btn-reset-all" id="reset-all" onclick="resetAll()">
                    <span class="reset-icon"> </span>
                    <span class="reset-btn-text">Reset All Settings</span>
                </button>
            </div>
        </div>
    </div>
    <div class="container my-5">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header text-center rounded-top-4 mb-2" style="background-color: #591512;">
                <h4 class="mb-0 text-white" style="font-family:Inter;font-weight:700;">Pending Faculty Feedbacks</h4>
            </div>
            <form id="vertical-wizard" method="POST" action="{{ route('feedback.submit.feedback') }}">
                @csrf
                <div class="card-body mb-4 p-0" >
                    <div class="table-responsive">
                        <table class="table rounded-3 overflow-hidden align-middle mb-0 table-bordered">
                            <thead class="bg-danger text-white">
                                <tr>
                                    <th class="text-center text-white">S.No.</th>
                                    <th class="text-center text-white">Date &amp; Time</th>
                                    <th class="text-center text-white">Topic Detail</th>
                                    <th class="text-center text-white">Faculty Name</th>
                                    <th class="text-center text-white">Q. How did you like the Content?</th>
                                    <th class="text-center text-white">Q. How did you like the Presentation?</th>
                                    <th class="text-center text-white">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data as $index => $feedback)
                                @if ($feedback->feedback_checkbox == 1)
                                <tr class="text-center">
                                    <td class="text-center">{{ $index + 1 }}</td>
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
                                        <div class="star-rating d-inline-flex flex-row-reverse">
                                            @for ($i = 1; $i <= 5; $i++)
                                            <input type="radio" id="content-{{ $i }}-{{ $index }}"
                                                name="content[{{ $index }}]" value="{{ $i }}"
                                                {{ old('content.' . $index) == $i ? 'checked' : '' }} required>
                                            <label for="content-{{ $i }}-{{ $index }}">&#9733;</label>
                                            @endfor
                                        </div>
                                        @endif
                                    </td>

                                    {{-- Presentation Rating --}}
                                    <td>
                                        @if ($feedback->Ratting_checkbox == 1)
                                        <div class="star-rating d-inline-flex flex-row-reverse">
                                            @for ($i = 1; $i <= 5; $i++)
                                            <input type="radio" id="presentation-{{ $i }}-{{ $index }}"
                                                name="presentation[{{ $index }}]" value="{{ $i }}"
                                                {{ old('presentation.' . $index) == $i ? 'checked' : '' }} required>
                                            <label for="presentation-{{ $i }}-{{ $index }}">&#9733;</label>
                                            @endfor
                                        </div>
                                        @endif
                                    </td>

                                    {{-- Remarks --}}
                                    <td style="min-width: 180px;">
                                        @if ($feedback->Remark_checkbox == 1)
                                        <textarea class="form-control form-control-sm" name="remarks[{{ $index }}]"
                                            rows="2" placeholder="Enter remarks..."
                                            required>{{ old('remarks.' . $index) }}</textarea>
                                        @endif
                                    </td>
                                    <input type="hidden" name="timetable_pk[{{ $index }}]"
                                        value="{{ $feedback->timetable_pk }}">
                                        <input type="hidden" name="topic_name[{{ $index }}]" value="{{ $feedback->subject_topic }}">
<input type="hidden" name="faculty_pk[{{ $index }}]" value="{{ $feedback->faculty_pk ?? '' }}">
<input type="hidden" name="Ratting_checkbox[{{ $index }}]" value="{{ $feedback->Ratting_checkbox }}">
<input type="hidden" name="Remark_checkbox[{{ $index }}]" value="{{ $feedback->Remark_checkbox }}">

                                </tr>
                                @endif
                                @endforeach
                                @if($data->count() == 0)
                                <tr>
                                    <td colspan="7" class="text-center">No feedback available.</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($data->count() > 0)
                <div class="text-end mt-3 mb-4 me-4">
                    <button type="submit" class="btn btn-primary px-4 rounded-pill"
                        style="background-color: #004a93;border-color: #004a93;">Submit Feedback</button>
                </div>
                @endif
            </form>
        </div>
    </div>
    <!-- Footer -->
    <!-- Footer -->
    <footer class="mt-auto text-white py-3" style="background-color: #004a93;">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <p class="mb-0" style="font-size: 14px;">&copy; {{date('Y')}} Lal Bahadur Shastri National Academy
                        of
                        Administration, Mussoorie, Uttarakhand</p>
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
    <script src="https://cdn.ux4g.gov.in/tools/accessibility-widget.js" async></script>
</body>

</html>