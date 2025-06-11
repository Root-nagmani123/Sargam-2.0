<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Foundation Course | Lal Bahadur Shastri National Academy of Administration</title>
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="{{asset('admin_assets/images/favicon.ico')}}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- Icon library (Bootstrap Icons or Lucide) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="asset/css/accesibility-style_v1.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #323232;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background: linear-gradient(to bottom, #f1f7fd 25%, #fff 75%);
        }

        .top-header {
            background-color: #004a93;
            color: white;
            padding: 5px 15px;
        }

        .academy-box {
            max-width: 900px;
            margin: 3rem auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            border-left: 4px solid #004a93;
            padding: 1rem;
            border-radius: 0.5rem;
        }

        .card-icon-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
        }

        .notice-box {
            background-color: #f1f5ff;
            padding: 1rem;
            border-radius: 0.5rem;
        }

        .footer-links {
            font-size: 0.9rem;
        }

        .btn-primary {
            background-color: #0d6efd;
        }

        .signature {
            text-align: right;
            font-size: 0.9rem;
            margin-top: 2rem;
        }

        footer {
            background-color: #004a93;
            padding: 1rem 0;
            font-size: 0.9rem;
            color: #fff;
        }

        p {
            font-size: 16px;
            line-height: 1.6;
        }

        .accordion-item {
            border: 0;
            border-bottom: 1px solid #dee2e6;
            border-radius: 0;
            border-radius: 0.5rem;
        }

        .accordion-button::after {
            content: '+';
            font-size: 1.2rem;
            font-weight: bold;
            color: #323232;
            background-image: none !important;
            transform: none !important;
        }

        .accordion-button:not(.collapsed)::after {
            content: '−';
            color: #323232;
        }

        .text-muted {
            color: #6c757d !important;
        }

        .text-primary {
            color: #004a93 !important;
        }

        .fw-semibold {
            font-weight: 600;
        }

        .fw-bold {
            font-weight: 700;
        }

        .vl {
            border-left: 1px solid #bdbdbd;
            margin-inline: 10px;
            height: 40px;
        }

        .header {
            background-color: #fff;
            padding: 10px 0;
            line-height: 1.6;
        }

        .icon-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background-color: #eaf4ff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .icon-circle img {
            width: 28px;
            height: 28px;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.05);
            background-color: #fff;
            height: 100%;
            border: 2px solid #e5e7eb;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        .card-header {
            background-color: transparent;
            border-bottom: none;
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-footer {
            background-color: transparent;
            border-top: none;
            padding: 1rem 1.5rem;
        }

        .custom-card ul {
            padding-left: 1.2rem;
        }

        .custom-btn {
            margin-top: 1.5rem;
            width: 100%;
        }

        ul li {
            color: #4b5563;
            font-size: 14px;
        }

        .sidebar {
            min-height: 100vh;
            background-color: #fff;
        }

        .nav-pills .nav-link.active {
            font-weight: 500;
            background-color: #004a93;
            border: 1px solid #ddd;
            color: #fff;
            border-radius: 0.25rem;
            transition: background-color 0.3s, color 0.3s;
        }

        .nav-link:hover {
            background-color: #004a93;
            color: #fff !important;
        }

        .nav-link {
            color: #000;
            font-weight: 500;
            transition: background-color 0.3s, color 0.3s;
            background-color: #fff;
            border: 1px solid #ddd;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        .form-label {
            font-weight: 500;
        }
    </style>
</head>

<body>

    <!-- Top Blue Bar (Govt of India) -->
    <div class="top-header d-flex justify-content-between align-items-center">
        <div class="container">
            <div class="row">
                <div class="col-md-3 d-flex align-items-center">
                    <img src="https://upload.wikimedia.org/wikipedia/en/thumb/4/41/Flag_of_India.svg/800px-Flag_of_India.svg.png" alt="GoI Logo" height="30">
                    <span class="ms-2" style="font-family: roboto; font-size: 14px;">Government of India</span>
                </div>
                <div class="col-md-9 text-end d-flex justify-content-end align-items-center">
                    <ul class="nav justify-content-end align-items-center">
                        <li class="nav-item"><a href="#" class="text-white text-decoration-none"
                                style="font-family: roboto; font-size: 12px;">Skip to Main Content</a></li>
                        <span class="text-muted me-3 ms-3">|</span>
                        <li class="nav-item"><a href="#" class="text-white text-decoration-none"><img
                                    src="asset/images/text_to_speech.png" alt="" height="20"><span class="ms-1"
                                    style="font-family: roboto; font-size: 12px;">Screen Reader</span></a></li>
                        <span class="text-muted me-3 ms-3">|</span>
                        <li class="nav-item"><a href="#" class="text-white text-decoration-none me-3 ms-3"
                                style="font-family: roboto; font-size: 12px;">A+</a></li>
                        <li class="nav-item"><a href="#" class="text-white text-decoration-none me-3 ms-3"
                                style="font-family: roboto; font-size: 12px;">A</a></li>
                        <li class="nav-item"><a href="#" class="text-white text-decoration-none me-3 ms-3"
                                style="font-family: roboto; font-size: 12px;">A-</a></li>
                        <span class="text-muted me-3 ms-3">|</span>
                        <li class="nav-item"><a href="#" class="text-white text-decoration-none"><img
                                    src="asset/images/Group 177.png" alt=""></a></li>
                        <span class="text-muted me-3 ms-3">|</span>
                        <li class="nav-item"><a href="#" class="text-white text-decoration-none"><img
                                    src="asset/images/Regular.png" alt="">
                                <span><select name="lang" id="" class="form-select form-select-sm"
                                        style="width: 100px; display: inline-block; font-size: 14px;  background-color: transparent; border: none;color: #fff;font-family: 'roboto', Courier, monospace;font-size: 12px;">"
                                        <option value="">Language</option>
                                        <option value="en" selected>English</option>
                                    </select></span></a></li>
                        <span class="text-muted me-3 ">|</span>
                        <li>
                            <a id="uw-widget-custom-trigger" contenteditable="false" style="cursor: pointer;">
                                <img src="asset/images/accessible.png" alt="" height="20"> <span class="text-white ms-1"
                                    style="font-family: roboto; font-size: 12px;">
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
                        <img src="https://i.pinimg.com/736x/a8/fa/ef/a8faef978e6230b6a12d1c29c62d5edf.jpg" alt="Logo 1" height="40">
                    </a>
                    <span class="vr mx-2"></span>
                    <a class="navbar-brand" href="#">
                        <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="Logo 2" height="40">
                    </a>

                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                        <ul class="navbar-nav align-items-center">
                            <li class="nav-item">
                                <a class="nav-link ms-4 me-4" href="#">About Us</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link ms-4 me-4" href="#">FAQs</a>
                            </li>
                            <li class="nav-item">
                                <a class="btn btn-outline-primary ms-4" href="#">Login</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>
    </div>


    <!-- Main Content Box -->
    <div class="academy-box">
        <div class="text-center mb-2">
            <img src="{{asset('images/lbsnaa_logo.jpg')}}" alt="LBSNAA Logo" height="80">
            <h4 class="mt-3 fw-bold" style="color: #af2910;font-size: 20px;">Lal Bahadur Shastri National Academy of
                Administration</h4>
            <p class="text-muted" style="font-size: 16px;">Mussoorie, Uttarakhand</p>
        </div>
        <hr>
        <div class="container">
            <div class="text-center mb-4">
                <h5 class="text-primary fw-bold mt-4 mb-2"><a href="#" class="text-decoration-none"
                        style="color: #004a93;font-size: 20px;">Congratulations</a></h5>
                <h4 class="fw-semibold mt-2" style="font-size: 20px;">99th Foundation Course for IAS/IPS/IFoS Officers
                </h4>
            </div>
            <div class="row g-3">
                <!-- Course Duration -->
                <div class="col-md-6">
                    <div class="d-flex align-items-center border rounded-4 p-3 h-100">
                        <i class="material-icons menu-icon me-3 fs-3" style="color: #004a93;">calendar_today</i>
                        <div>
                            <h6 class="mb-1 fw-semibold">Course Duration</h6>
                            <div class="text-muted">August 26th, 2024 – November 29th, 2024</div>
                        </div>
                    </div>
                </div>

                <!-- Online Registration -->
                <div class="col-md-6">
                    <div class="d-flex align-items-center border rounded-4 p-3 h-100">
                        <i class="material-icons menu-icon me-3 fs-3" style="color: #004a93;">location_on</i>
                        <div>
                            <h6 class="mb-1 fw-semibold">Online Registration</h6>
                            <div class="text-muted">July 18th, 2024 – July 28th, 2024</div>
                        </div>
                    </div>
                </div>

                <!-- Online Exemption -->
                <div class="col-md-6">
                    <div class="d-flex align-items-center border rounded-4 p-3 h-100">
                        <i class="material-icons menu-icon me-3 fs-3" style="color: #004a93;">group</i>

                        <div>
                            <h6 class="mb-1 fw-semibold">Online Exemption</h6>
                            <div class="text-muted">Available after registration</div>
                        </div>
                    </div>
                </div>

                <!-- Laptop Requirement -->
                <div class="col-md-6">
                    <div class="d-flex align-items-center border rounded-4 p-3 h-100">
                        <i class="material-icons menu-icon me-3 fs-3" style="color: #004a93;">laptop_windows</i>
                        <div>
                            <h6 class="mb-1 fw-semibold">Laptop Requirement</h6>
                            <div class="text-muted">Mandatory for all participants</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="notice-box mt-4">
            <p class="fw-bold" style="color: #004a93;">Important Notice:</p>
            <ul style="color: #004a93;">
                <li>You will be required to report at the Academy a couple of days prior for the completion of joining
                    formalities.</li>
                <li>All selected candidates must complete the online registration process. Please note that at this
                    point, it would suffice to fill in the Descriptive Roll component of the registration form.</li>
                <li>Please peruse these documents carefully and fill the registration form -</li>
                <ul class="list-unstyled">
                    <li>Annexure I - About the 99th Foundation Course</li>
                    <li>Annexure II - Joining formalities and information</li>
                    <li>Annexure III - Clothing, dress code and miscellaneous matters</li>
                    <li>Annexure IV - Subscription of Clubs Societies and Houses</li>
                    <li>Annexure V - Check list for submission of forms/ documents</li>
                </ul>
                <li>You are advised to visit <a href="https://www.lbsnaa.gov.in">https://www.lbsnaa.gov.in</a> regularly
                    for further updates.</li>
                <li>Exemption applications must be submitted with proper documentation</li>
                <li>Laptop is mandatory for all course activities</li>
                <li>Joining instructions will be provided upon successful registration</li>
            </ul>
        </div>
        <p class="text-center my-3">All eligible candidates are hereby directed to complete their registration or
            exemption application through
            the online portal.</p>
        <div class="text-center mt-4">
            <a href="#" class="btn btn-primary px-4" style="background-color: #004a93; border: #004a93;">Click Here to
                Proceed</a>
        </div>
        <hr>
        <div class="signature mt-5">
            <p class="text-muted">Shelesh Nawal<br>
                Deputy Director (Sr.)<br>
                Course Coordinator, 99th FC
            </p>
        </div>
    </div>

    <div class="container mt-5">
        <div class="text-center">
            <h4 style="color: #004a93; font-size: 30px; font-weight: 700;">Choose Your Path</h4>
            <p class="text-muted" style="font-size: 20px;">Please select the appropriate option based on your current
                status. </p>
        </div>
        <div class="container my-5">
            <div class="row g-4 mt-5">
                <!-- Register Card -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <div class="icon-circle" style="background-color: #dcfce7;">
                                <i class="material-icons menu-icon fs-3"
                                    style="color: #16a32a;transform: rotateY(180deg);">person_add</i>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="fw-bold" style="color: #004a93; font-weight: 600;">Register for Foundation Course
                            </h5>
                            <p class="text-muted">Registration for the 99th Foundation Course</p>

                            <p class="fw-semibold text-start mt-3"
                                style="color: #4b5563; font-weight: 700; font-size: 14px;">This path is for:</p>
                            <ul class="text-start">
                                <li>Newly selected IAS/IPS/IFoS officers</li>
                                <li>First-time course participants</li>
                                <li>Officers without prior exemptions <span class="text-danger">*</span></li>
                            </ul>

                            <p class="fw-semibold text-start"
                                style="color: #4b5563; font-weight: 700; font-size: 14px;">You will need:</p>
                            <ul class="text-start">
                                <li>Personal and educational documents</li>
                                <li>Bank account details</li>
                                <li>Medical information</li>
                                <li>Photo and signature uploads</li>
                            </ul>

                            <p class="fw-semibold text-start"
                                style="color: #4b5563; font-weight: 700; font-size: 14px;">Important Dates:</p>
                            <ul class="text-start">
                                <li>Registration opening date: <strong>18th July 2024</strong></li>
                                <li>Last date for registration: <strong>28th July 2024</strong></li>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-success custom-btn"
                                style="background-color: #16a32a; border: #16a32a;">Start Registration</button>
                        </div>
                    </div>
                </div>

                <!-- Exemption Card -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <div class="icon-circle" style="background-color: #fff4e5;">
                                <i class="material-icons menu-icon fs-2" style="color: #ea5803;">article</i>
                            </div>
                        </div>
                        <div class="card-body text-center">
                            <h5 class="fw-bold" style="color: #004a93; font-weight: 600;">Apply for Exemption</h5>
                            <p class="text-muted">Submit an exemption application if you qualify</p>

                            <p class="fw-semibold text-start mt-3"
                                style="color: #4b5563; font-weight: 700; font-size: 14px;">Available exemptions:</p>
                            <ul class="text-start">
                                <li>Appearing in CSE Mains 2024</li>
                                <li>Already attended Foundation Course</li>
                                <li>Medical grounds</li>
                                <li>Opting out after registration</li>
                            </ul>

                            <p class="fw-semibold text-start"
                                style="color: #4b5563; font-weight: 700; font-size: 14px;">You will need:</p>
                            <ul class="text-start">
                                <li>Valid reason for exemption</li>
                                <li>Supporting documents</li>
                                <li>Contact information</li>
                            </ul>

                            <p class="fw-semibold text-start"
                                style="color: #4b5563; font-weight: 700; font-size: 14px;">Important Dates:</p>
                            <ul class="text-start">
                                <li>Exemption applications open: <strong>18th July 2024</strong></li>
                                <li>Last date for registration: <strong>5th August 2024</strong></li>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-warning text-white custom-btn"
                                style="background-color: #ea5803; border: #ea5803;">Apply for Exemption</button>
                        </div>
                    </div>
                </div>

                <!-- Login Card -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <div class="icon-circle" style="background-color: #e5f2ff;">
                                <i class="material-icons menu-icon fs-2" style="color: #2563eb;">login</i>

                            </div>
                        </div>
                        <div class="card-body text-center">
                            <h5 class="fw-bold" style="color: #004a93; font-weight: 600;">Already Registered?</h5>
                            <p class="text-muted">Login to continue your application or view status</p>
                            <p class="fw-semibold text-start mt-3"
                                style="color: #4b5563; font-weight: 700; font-size: 14px;">Access your:</p>
                            <ul class="text-start">
                                <li style="color: #4b5563; font-size: 14px;">Saved registration progress</li>
                                <li style="color: #4b5563; font-size: 14px;">Application status</li>
                                <li style="color: #4b5563; font-size: 14px;">Document uploads</li>
                                <li style="color: #4b5563; font-size: 14px;">Submission history</li>
                            </ul>

                            <p class="fw-semibold text-start"
                                style="color: #4b5563; font-weight: 700; font-size: 14px;">You will need:</p>
                            <ul class="text-start">
                                <li style="color: #4b5563; font-size: 14px;">Your registered mobile number</li>
                                <li style="color: #4b5563; font-size: 14px;">Application reference ID</li>
                                <li style="color: #4b5563; font-size: 14px;">Web authentication code</li>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary custom-btn "
                                style="background-color: #2563eb; border: #2563eb;">Login to Dashboard</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4 mt-5">
                <div class="col-9">
                    <h4 style="color: #004a93; font-size: 30px; font-weight: 700;">Frequently Asked Questions</h4>
                    <span class="text-muted">Find your query from this list of frequently asked questions</span>
                </div>
                <div class="col-3 text-end">
                    <button class="btn btn-outline-primary">View All
                        FAQs</button>
                </div>
            </div>
            <div class="mt-5">
                <div class="accordion" id="accordionExample">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                Accordion Item #1
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <strong>This is the first item’s accordion body.</strong> It is shown by default, until
                                the collapse plugin adds the appropriate classes that we use to style each element.
                                These classes control the overall appearance, as well as the showing and hiding via CSS
                                transitions. You can modify any of this with custom CSS or overriding our default
                                variables. It’s also worth noting that just about any HTML can go within the
                                <code>.accordion-body</code>, though the transition does limit overflow.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                Accordion Item #2
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <strong>This is the second item’s accordion body.</strong> It is hidden by default,
                                until the collapse plugin adds the appropriate classes that we use to style each
                                element. These classes control the overall appearance, as well as the showing and hiding
                                via CSS transitions. You can modify any of this with custom CSS or overriding our
                                default variables. It’s also worth noting that just about any HTML can go within the
                                <code>.accordion-body</code>, though the transition does limit overflow.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                Accordion Item #3
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <strong>This is the third item’s accordion body.</strong> It is hidden by default, until
                                the collapse plugin adds the appropriate classes that we use to style each element.
                                These classes control the overall appearance, as well as the showing and hiding via CSS
                                transitions. You can modify any of this with custom CSS or overriding our default
                                variables. It’s also worth noting that just about any HTML can go within the
                                <code>.accordion-body</code>, though the transition does limit overflow.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container mt-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">Home</li>
                <li class="breadcrumb-item" aria-current="page">Library</li>
            </ol>
        </nav>
        <div class="text-center mt-5">
            <h4 style="color: #004a93; font-size: 30px; font-weight: 700;">Select Exemption Category</h4>
            <div class="col-8 mx-auto">
                <p class="text-muted" style="font-size: 16px;">Choose the appropriate exemption category based on your
                    circumstances. Each
                    category has specific requirements and documentation needs.</p>
            </div>
        </div>
        <div class="row mt-5 g-4">
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <div class="icon-circle" style="background-color: #e5f2ff;">
                            <i class="material-icons menu-icon fs-2" style="color: #2563eb;">school</i>
                        </div>
                    </div>
                    <div class="card-body text-center">
                        <h5 class="fw-bold text-center" style="color: #004a93; font-weight: 600;font-size: 20px;">
                            Exemption for CSE
                            Mains
                            2024</h5>
                        <span class="text-muted">For candidates appearing in Civil Services Mains Examination
                            2024</span>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-success custom-btn"
                            style="background-color: #2563eb; border: #2563eb;">Apply for Exemption</button>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <div class="icon-circle" style="background-color: #dcfce7;">
                            <i class="material-icons menu-icon fs-2" style="color: #16a32a;">article</i>
                        </div>
                    </div>
                    <div class="card-body text-center">
                        <h5 class="fw-bold text-center" style="color: #004a93; font-weight: 600;">Already Attended
                            Foundation Course</h5>
                        <span class="text-muted">For officers who have previously completed the Foundation Course</span>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-success custom-btn"
                            style="background-color: #16a32a; border: #16a32a;">Apply for Exemption</button>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <div class="icon-circle" style="background-color: #fee2e2;">
                            <i class="material-icons menu-icon fs-2" style="color: #dc2626;">medical_services</i>
                        </div>
                    </div>
                    <div class="card-body text-center">
                        <h5 class="fw-bold text-center" style="color: #004a93; font-weight: 600;">Medical Grounds</h5>
                        <span class="text-muted">For candidates unable to attend due to medical reasons</span>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-success custom-btn"
                            style="background-color: #dc2626; border: #dc2626;">Apply for Exemption</button>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <div class="icon-circle" style="background-color: #ffedd5;">
                            <i class="material-icons menu-icon fs-2" style="color: #ea580c;">person_remove</i>
                        </div>
                    </div>
                    <div class="card-body text-center">
                        <h5 class="fw-bold text-center" style="color: #004a93; font-weight: 600;">Exemption for CSE
                            Mains
                            2024</h5>
                        <span class="text-muted">For candidates appearing in Civil Services Mains Examination
                            2024</span>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-success custom-btn"
                            style="background-color: #ea580c; border: #ea580c;">Apply for Exemption</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="notice-box mt-4">
            <p class="fw-bold" style="color: #004a93;font-size: 24px;">Important Notice:</p>
            <div class="row">
                <div class="col-6">
                    <p class="fw-bold" style="color: #004a93;font-size: 14px;">Required Information</p>
                    <ul>
                        <li style="color: #004a93; font-size: 14px;">Valid reason for exemption</li>
                        <li style="color: #004a93; font-size: 14px;">Supporting documents</li>
                        <li style="color: #004a93; font-size: 14px;">Contact information</li>
                    </ul>
                </div>
                <div class="col-6">
                    <p class="fw-bold" style="color: #004a93;font-size: 14px;">Required Information</p>
                    <ul>
                        <li style="color: #004a93; font-size: 14px;">Valid reason for exemption</li>
                        <li style="color: #004a93; font-size: 14px;">Supporting documents</li>
                        <li style="color: #004a93; font-size: 14px;">Contact information</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">Home</li>
                <li class="breadcrumb-item active">Home</li>
                <li class="breadcrumb-item" aria-current="page">Library</li>
            </ol>
        </nav>
        <div class="card mt-4">
            <div class="card-header">
                <h4 class="fw-bold" style="color: #004a93; font-weight: 600; font-size: 24px;">Appearing in CSE Mains
                    2024</h4>
                <span class="text-muted">
                    Please fill in all required information for your exemption application.
                </span>
            </div>
            <div class="card-body">
                <form action="" method="post">
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="" class="form-label">Mobile Number <span
                                        class="text-danger">*</span></label>
                                <input type="number" name="" id="" class="form-control"
                                    placeholder="Enter your mobile number" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="" class="form-label">Mobile Number <span
                                        class="text-danger">*</span></label>
                                <input type="number" name="" id="" class="form-control"
                                    placeholder="Enter your mobile number" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="" class="form-label">Mobile Number <span
                                        class="text-danger">*</span></label>
                                <input type="number" name="" id="" class="form-control"
                                    placeholder="Enter your mobile number" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="" class="form-label">Mobile Number <span
                                        class="text-danger">*</span></label>
                                <input type="number" name="" id="" class="form-control"
                                    placeholder="Enter your mobile number" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="" class="form-label">Additional Remarks <span
                                    class="text-muted">(Optional)</span></label>
                            <textarea class="form-control" name="" id="" rows="3"
                                placeholder="Enter any additional remarks or information"></textarea>
                        </div>
                        <div class="col-12">
                            <label for="" class="form-label">Verification <span
                                    class="text-muted">(Optional)</span></label>
                            <textarea class="form-control" name="" id="" rows="3"
                                placeholder="Enter any additional remarks or information"></textarea>
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" value="" id="checkChecked" checked>
                            <label class="form-check-label" for="checkChecked"
                                style="color: #004a93;font-size: 14px;width: 750px;">
                                I hereby declare that the information provided above is true and correct. I understand
                                that any false information
                                may lead to rejection of my exemption application.
                            </label>
                        </div>
                    </div>

                </form>
            </div>
            <div class="card-footer text-center">
                <div class="d-flex gap-2 text-center justify-content-center">
                    <button class="btn btn-primary" type="button">Submit Application</button>
                    <button class="btn btn-danger" type="button">Cancel Application</button>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">Home</li>
                <li class="breadcrumb-item" aria-current="page" style="font-size: 20px;">Registration Form</li>
            </ol>
        </nav>
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-4 col-lg-3 sidebar">
                <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                    <button class="nav-link active mb-4">Descriptive Roll</button>
                    <button class="nav-link mb-4">Descriptive Roll II</button>
                    <button class="nav-link mb-4">Joining Instructions</button>
                    <button class="nav-link mb-4">Joining Documents</button>
                    <button class="nav-link mb-4">Bank Details</button>
                    <button class="nav-link mb-4">Health Risk Factors</button>
                    <button class="nav-link mb-4">Special Assistance</button>
                    <button class="nav-link mb-4">Vision Statements</button>
                    <button class="nav-link mb-4">Reports (Admin Only)</button>
                </div>
            </div>

            <!-- Form Content -->
            <div class="col-md-8 col-lg-9">
                <!-- Personal Details -->
                <div class="card mb-4 py-4">
                    <div class="card-body">
                        <h5 class="card-title text-primary fw-bold" style="font-size: 24px;">Personal Details</h5>
                        <p class="text-muted mb-4">Basic personal information</p>
                        <form class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" placeholder="Enter first name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" placeholder="Enter last name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Gender</label>
                                <select class="form-select">
                                    <option selected disabled>Select gender</option>
                                    <option>Male</option>
                                    <option>Female</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Marital Status</label>
                                <select class="form-select">
                                    <option selected disabled>Select status</option>
                                    <option>Single</option>
                                    <option>Married</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nationality</label>
                                <select class="form-select">
                                    <option selected disabled>Select state</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Religion</label>
                                <select class="form-select">
                                    <option selected disabled>Select your Religion</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Background</label>
                                <select class="form-select">
                                    <option selected disabled>Choose your background</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">PAN Number</label>
                                <input type="text" class="form-control" placeholder="Enter PAN number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Aadhaar Number</label>
                                <input type="text" class="form-control" placeholder="Enter Aadhaar number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Passport</label>
                                <input type="text" class="form-control" placeholder="Enter Passport Details">
                            </div>
                            <hr>
                            <div class="mb-3 d-flex justify-content-end">
                                <div class="d-flex align-items-center">
                                    <!-- Save Draft Button -->
                                    <button
                                        class="btn btn-outline-primary me-2 d-flex align-items-center justify-content-center"
                                        type="submit" style="color: #004a93; border: 1px solid #004a93;">
                                        <i class="material-icons me-2" style="color: #004a93;">save</i>
                                        Save Draft
                                    </button>

                                    <!-- Next Button -->
                                    <button class="btn btn-primary d-flex align-items-center justify-content-center"
                                        type="reset" style="background-color: #004a93; border: 1px solid #004a93;">
                                        Next
                                        <i class="material-icons ms-2" style="color: #fff;">arrow_forward</i>
                                    </button>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-4">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <p class="text-white" style="font-size: 14px;">&copy; 2024 Lal Bahadur Shastri National Academy of
                        Administration, Mussoorie,
                        Uttarakhand</p>
                </div>
                <div class="col-md-4 text-end">
                    <ul class="list-unstyled d-flex justify-content-end mb-0">
                        <li class="me-3"><a href="#" class="text-white text-decoration-none"
                                style="font-size: 14px; font-family: inter;">Privacy Policy</a></li>
                        <li class="me-3"><a href="#" class="text-white text-decoration-none"
                                style="font-size: 14px; font-family: inter;">Need Help</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO"
        crossorigin="anonymous"></script>

</body>

</html>