<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration - Foundation Course | Lal Bahadur Shastri National Academy of Administration</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('admin_assets/images/logos/favicon.ico') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link href="{{asset('admin_assets/css/accesibility-style_v1.css')}}" rel="stylesheet">
    <!-- Icon library (Bootstrap Icons or Lucide) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    .sidebar {
        max-height: 100vh;
        background-color: transparent;
    }

    .sidebar .nav-pills .nav-link.active {
        font-weight: 500;
        background-color: #004a93;
        border: 1px solid #ddd;
        color: #fff;
        border-radius: 0.25rem;
        transition: background-color 0.3s, color 0.3s;
    }

    .sidebar .nav-link:hover {
        background-color: #004a93;
        color: #fff !important;
    }

    .sidebar .nav-link {
        color: #000;
        font-weight: 500;
        transition: background-color 0.3s, color 0.3s;
        background-color: #fff;
        border: 1px solid #ddd;
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

    .card {
        border-radius: 12px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
    }

    .form-label {
        font-weight: 500;
    }

    <style>.nav-item a span {
        font-size: 12px;
    }

    .nav .nav-item {
        margin-right: 10px;
    }
    </style>
</head>

<body>
    <!-- Top Blue Bar (Govt of India) -->
    <div class="top-header d-flex justify-content-between align-items-center d-none d-md-block">
        <div class="container">
            <div class="row">
                <div class="col-md-3 d-flex align-items-center">
                    <img src="https://upload.wikimedia.org/wikipedia/en/thumb/4/41/Flag_of_India.svg/800px-Flag_of_India.svg.png"
                        alt="GoI Logo" height="30">
                    <span class="ms-2" style="font-size: 14px;">Government of India</span>
                </div>
                <div class="col-md-9 text-end d-flex justify-content-end align-items-center">
                    <ul class="nav justify-content-end align-items-center">
                        <li class="nav-item"><a href="#content" class="text-white text-decoration-none"
                                style=" font-size: 12px;">Skip to Main Content</a></li>
                        <!-- <span class="text-muted me-3 ms-3">|</span>
                        <li class="nav-item"><a href="#" class="text-white text-decoration-none"><img
                                    src="{{ asset('images/text_to_speech.png') }}" alt="" width="20"><span
                                    class="ms-1" style=" font-size: 12px;">Screen Reader</span></a></li>
                        <span class="text-muted me-3 ms-3">|</span>
                        <li class="nav-item"><a href="#" class="text-white text-decoration-none me-3 ms-3"
                                style=" font-size: 12px;">A+</a></li>
                        <li class="nav-item"><a href="#" class="text-white text-decoration-none me-3 ms-3"
                                style=" font-size: 12px;">A</a></li>
                        <li class="nav-item"><a href="#" class="text-white text-decoration-none me-3 ms-3"
                                style=" font-size: 12px;">A-</a></li>
                        <span class="text-muted me-3 ms-3">|</span>
                        <li class="nav-item"><a href="#" class="text-white text-decoration-none"><img
                                    src="{{ asset('images/contrast.png') }}" alt="" width="20"></a></li>
                        <span class="text-muted me-3 ms-3">|</span>
                        <li class="nav-item"><a href="#" class="text-white text-decoration-none"><img
                                    src="{{ asset('images/Regular.png') }}" alt="" width="20">
                                <span><select name="lang" id="" class="form-select form-select-sm"
                                        style="width: 100px; display: inline-block; font-size: 14px;  background-color: transparent; border: none;color: #fff;font-size: 12px;">"
                                        <option value="">Language</option>
                                        <option value="en" selected>English</option>
                                    </select></span></a></li> -->
                        <span class="text-muted me-3 ">|</span>
                        <li class="nav-item"><a href="#" class="text-white text-decoration-none"
                                id="uw-widget-custom-trigger" contenteditable="false" style="cursor: pointer;"><img
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
                        <img src="https://i.pinimg.com/736x/a8/fa/ef/a8faef978e6230b6a12d1c29c62d5edf.jpg" alt="Logo 1"
                            height="40">
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
                            <!-- <li class="nav-item">
                                <a class="nav-link ms-4 me-4" href="#">About Us</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link ms-4 me-4" href="#">FAQs</a>
                            </li> -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                                    id="accountDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="material-icons" style="color: #004a93;">account_circle</i>
                                    <span class="ms-2">{{ Auth::user()->name }}</span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="accountDropdown">
                                    <!-- <li><a class="dropdown-item" href="#">Profile</a></li> -->
                                    <!-- <li><a class="dropdown-item" href="#">Settings</a></li> -->
                                    <li>
                                        <form action="{{ route('logout') }}" method="POST">
                                            @csrf
                                            <button class="dropdown-item" type="submit">Logout</button>
                                        </form>
                                    </li>
                                </ul>
                            </li>

                        </ul>
                    </div>
                </div>
            </nav>
        </div>
    </div>
    <main style="flex: 1;">
        <div class="container mt-5">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Home</li>
                    <li class="breadcrumb-item" aria-current="page" style="font-size: 20px;">Registration Form</li>
                </ol>
            </nav>
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-4 col-lg-3 sidebar" style="position: sticky; top: 150px; max-height: 100vh;">
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist"
                            aria-orientation="vertical">
                            <button class="nav-link active mb-4" id="tab-1-tab" data-bs-toggle="pill"
                                data-bs-target="#tab-1" type="button" role="tab" aria-controls="tab-1"
                                aria-selected="true">Descriptive Roll</button>
                            <button class="nav-link mb-4" id="tab-2-tab" data-bs-toggle="pill" data-bs-target="#tab-2"
                                type="button" role="tab" aria-controls="tab-2" aria-selected="true">Descriptive
                                Roll
                                II</button>
                            <button class="nav-link mb-4" id="tab-3-tab" data-bs-toggle="pill" data-bs-target="#tab-3"
                                type="button" role="tab" aria-controls="tab-3" aria-selected="true">Joining
                                Instructions</button>
                            <button class="nav-link mb-4" id="tab-4-tab" data-bs-toggle="pill" data-bs-target="#tab-4"
                                type="button" role="tab" aria-controls="tab-4" aria-selected="true">Joining
                                Documents</button>
                            <button class="nav-link mb-4" id="tab-5-tab" data-bs-toggle="pill" data-bs-target="#tab-5"
                                type="button" role="tab" aria-controls="tab-5" aria-selected="true">Bank
                                Details</button>
                            <button class="nav-link mb-4" id="tab-6-tab" data-bs-toggle="pill" data-bs-target="#tab-6"
                                type="button" role="tab" aria-controls="tab-6" aria-selected="true">Health Risk
                                Factors</button>
                            <button class="nav-link mb-4" id="tab-7-tab" data-bs-toggle="pill" data-bs-target="#tab-7"
                                type="button" role="tab" aria-controls="tab-7" aria-selected="true">Special
                                Assistance</button>
                            <button class="nav-link mb-4" id="tab-8-tab" data-bs-toggle="pill" data-bs-target="#tab-8"
                                type="button" role="tab" aria-controls="tab-8" aria-selected="true">Vision
                                Statements</button>
                            <button class="nav-link mb-4" id="tab-9-tab" data-bs-toggle="pill" data-bs-target="#tab-9"
                                type="button" role="tab" aria-controls="tab-9" aria-selected="true">Reports (Admin
                                Only)</button>
                        </div>


                    </div>
                </div>
                <!-- Form Content -->
                <div class="col-md-8 col-lg-9">
                    <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active" id="tab-1" role="tabpanel" aria-labelledby="tab-1-tab">
                            <!-- Personal Details -->
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title text-primary fw-bold" style="font-size: 24px;">
                                        Personal
                                        Details
                                    </h5>
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
                                            <input type="text" class="form-control"
                                                placeholder="Enter Passport Details">
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
                                                <button
                                                    class="btn btn-primary d-flex align-items-center justify-content-center"
                                                    type="reset"
                                                    style="background-color: #004a93; border: 1px solid #004a93;">
                                                    Next
                                                    <i class="material-icons ms-2"
                                                        style="color: #fff;">arrow_forward</i>
                                                </button>
                                            </div>
                                        </div>

                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab-2" role="tabpanel" aria-labelledby="tab-2-tab">
                            <!-- Personal Details -->
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title text-primary fw-bold" style="font-size: 24px;">
                                        Personal
                                        Details
                                    </h5>
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
                                            <input type="text" class="form-control"
                                                placeholder="Enter Passport Details">
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
                                                <button
                                                    class="btn btn-primary d-flex align-items-center justify-content-center"
                                                    type="reset"
                                                    style="background-color: #004a93; border: 1px solid #004a93;">
                                                    Next
                                                    <i class="material-icons ms-2"
                                                        style="color: #fff;">arrow_forward</i>
                                                </button>
                                            </div>
                                        </div>

                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab-3" role="tabpanel" aria-labelledby="tab-3-tab">
                            <!-- Personal Details -->
                            <div class="mb-3">
                                <h5 class="card-title text-primary fw-bold" style="font-size: 24px;">Joining Documents
                                </h5>
                                <p class="text-muted mb-4">Upload Documnet</p>
                            </div>
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title text-primary fw-bold mb-3" style="font-size: 20px;">
                                        Administration Section Related Documents</h5>


                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab-4" role="tabpanel" aria-labelledby="tab-4-tab">
                            <!-- Joining Details -->
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="fw-bold text-primary mb-3">Administration Section Related Documents</h5>

                                    <div class="table-responsive">
                                        <table
                                            class="table table-bordered align-middle table-hover table-striped text-nowrap">
                                            <thead class="table-light text-center">
                                                <tr>
                                                    <th class="col">Sr.No.</th>
                                                    <th class="col">Documents</th>
                                                    <th class="col">Uploads</th>
                                                    <th class="col">View Uploaded Forms</th>
                                                    <th class="col">Sample Document</th>
                                                    <th class="col">Downloads</th>
                                                    <th class="col">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>

                                                <!-- Example Row -->
                                                <tr>
                                                    <td class="text-center">1</td>
                                                    <td>
                                                        Family Details Form (Form - 3) of Rules 54(12) of CCS (Pension)
                                                        Rules, 1972
                                                    </td>
                                                    <td><input type="file" class="form-control"></td>
                                                    <td class="text-center"><span class="text-success">View Uploaded
                                                            Forms</span></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">Download
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">View
                                                            Sample</a></td>
                                                    Forms</a></td>
                                                    <td class="text-center"><span
                                                            class="badge bg-success">Completed</span>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td class="text-center">2</td>
                                                    <td colspan="5">
                                                        <strong>Declaration of Close Relation (two copies)</strong>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center"></td>
                                                    <td>
                                                        a) National of or are domiciled in other countries and
                                                        <br>
                                                        b) Residing in India, who are non-Indian origin
                                                    </td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">Download
                                                            Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0" <td
                                                            class="text-center"><a href="#" class="btn btn-link p-0"
                                                                style="text-decoration: none;">Download
                                                                Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">View
                                                            Sample</a></td>
                                                    <td><input type="file" class="form-control"></td>
                                                    <td class="text-center text-muted">No forms uploaded</td>
                                                    <td class="text-center"><span
                                                            class="badge bg-warning text-dark">Pending</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center">3</td>
                                                    <td>Dowry Declaration - Declaration under Rule 13 of CCS (Conduct)
                                                        Rule 1964 (two copies)</td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">Download
                                                            Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0" <td
                                                            class="text-center"><a href="#" class="btn btn-link p-0"
                                                                style="text-decoration: none;">Download
                                                                Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">View
                                                            Sample</a></td>
                                                    <td><input type="file" class="form-control"></td>
                                                    <td class="text-center"><span class="text-success">View Uploaded
                                                            Forms</span></td>
                                                    <td class="text-center"><span
                                                            class="badge bg-success">Completed</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center">4</td>
                                                    <td>Marital Status - Declaration under Rule 13 of CCS (Conduct) Rule
                                                        1964 (two copies)</td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">Download
                                                            Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0" <td
                                                            class="text-center"><a href="#" class="btn btn-link p-0"
                                                                style="text-decoration: none;">Download
                                                                Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">View
                                                            Sample</a></td>
                                                    <td><input type="file" class="form-control"></td>
                                                    <td class="text-center"><span class="text-success">View Uploaded
                                                            Forms</span></td>
                                                    <td class="text-center"><span
                                                            class="badge bg-success">Completed</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center">5</td>
                                                    <td>Home Town Declaration (two copies)</td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">Download
                                                            Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0" <td
                                                            class="text-center"><a href="#" class="btn btn-link p-0"
                                                                style="text-decoration: none;">Download
                                                                Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">View
                                                            Sample</a></td>
                                                    <td><input type="file" class="form-control"></td>
                                                    <td class="text-center"><span class="text-success">View Uploaded
                                                            Forms</span></td>
                                                    <td class="text-center"><span
                                                            class="badge bg-success">Completed</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>6</td>
                                                    <td colspan="6"><strong>
                                                            Declaration of Movable, Immovable and valuable property on
                                                            first appointment (two copies)
                                                        </strong></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center"></td>
                                                    <td>6-A: Statement of Immovable Property on first appointment</td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">Download
                                                            Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0" <td
                                                            class="text-center"><a href="#" class="btn btn-link p-0"
                                                                style="text-decoration: none;">Download
                                                                Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">View
                                                            Sample</a></td>
                                                    <td><input type="file" class="form-control"></td>
                                                    <td class="text-center"><span class="text-success">View Uploaded
                                                            Forms</span></td>
                                                    <td class="text-center"><span
                                                            class="badge bg-success">Completed</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center"></td>
                                                    <td>6-B: Statement of Movable Property on first appointment</td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">Download
                                                            Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0" <td
                                                            class="text-center"><a href="#" class="btn btn-link p-0"
                                                                style="text-decoration: none;">Download
                                                                Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">View
                                                            Sample</a></td>
                                                    <td><input type="file" class="form-control"></td>
                                                    <td class="text-center"><span class="text-success">View Uploaded
                                                            Forms</span></td>
                                                    <td class="text-center"><span
                                                            class="badge bg-success">Completed</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center"></td>
                                                    <td>6-C: Statement of Debts and Other Liabilitieson first
                                                        appointment</td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">Download
                                                            Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0" <td
                                                            class="text-center"><a href="#" class="btn btn-link p-0"
                                                                style="text-decoration: none;">Download
                                                                Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">View
                                                            Sample</a></td>
                                                    <td><input type="file" class="form-control"></td>
                                                    <td class="text-center"><span class="text-success">View Uploaded
                                                            Forms</span></td>
                                                    <td class="text-center"><span
                                                            class="badge bg-success">Completed</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td></td>
                                                    <td colspan="6"><strong>Surety Bond-for</strong></td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center"> </td>
                                                    <td>Surety Bond for IAS or IPS or IFoS (whichever is applicable)
                                                    </td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">Download
                                                            Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0" <td
                                                            class="text-center"><a href="#" class="btn btn-link p-0"
                                                                style="text-decoration: none;">Download
                                                                Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">View
                                                            Sample</a></td>
                                                    <td><input type="file" class="form-control"></td>
                                                    <td class="text-center"><span class="text-success">View Uploaded
                                                            Forms</span></td>
                                                    <td class="text-center"><span
                                                            class="badge bg-success">Completed</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center"></td>
                                                    <td>Surety Bond for other services (other than All India Services)
                                                        (if applicable)</td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">Download
                                                            Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0" <td
                                                            class="text-center"><a href="#" class="btn btn-link p-0"
                                                                style="text-decoration: none;">Download
                                                                Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">View
                                                            Sample</a></td>
                                                    <td><input type="file" class="form-control"></td>
                                                    <td class="text-center"><span class="text-success">View Uploaded
                                                            Forms</span></td>
                                                    <td class="text-center"><span
                                                            class="badge bg-success">Completed</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-center">8</td>
                                                    <td><strong>Other Documents</strong></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">Download
                                                            Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0" <td
                                                            class="text-center"><a href="#" class="btn btn-link p-0"
                                                                style="text-decoration: none;">Download
                                                                Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">View
                                                            Sample</a></td>
                                                    <td><input type="file" class="form-control"></td>
                                                    <td class="text-center"><span class="text-success">View Uploaded
                                                            Forms</span></td>
                                                    <td class="text-center"><span
                                                            class="badge bg-success">Completed</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td></td>
                                                    <td>Form of OATH / Affirmation</td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">Download
                                                            Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0" <td
                                                            class="text-center"><a href="#" class="btn btn-link p-0"
                                                                style="text-decoration: none;">Download
                                                                Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">View
                                                            Sample</a></td>
                                                    <td><input type="file" class="form-control"></td>
                                                    <td class="text-center"><span class="text-success">View Uploaded
                                                            Forms</span></td>
                                                    <td class="text-center"><span
                                                            class="badge bg-success">Completed</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td></td>
                                                    <td>Certificate of Assumption Of Charge</td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">Download
                                                            Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0" <td
                                                            class="text-center"><a href="#" class="btn btn-link p-0"
                                                                style="text-decoration: none;">Download
                                                                Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">View
                                                            Sample</a></td>
                                                    <td><input type="file" class="form-control"></td>
                                                    <td class="text-center"><span class="text-success">View Uploaded
                                                            Forms</span></td>
                                                    <td class="text-center"><span
                                                            class="badge bg-success">Completed</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="fw-bold text-primary mb-3">Administration Section Related Documents</h5>

                                    <div class="table-responsive">
                                        <table
                                            class="table table-bordered align-middle text-center table-hover table-striped text-nowrap">
                                            <thead class="table-light text-center">
                                                <tr>
                                                    <th class="col">Sr. No.</th>
                                                    <th class="col">Documents</th>
                                                    <th class="col">Downloads</th>
                                                    <th class="col">Sample</th>
                                                    <th class="col">Uploads</th>
                                                    <th class="col">View Uploaded</th>
                                                    <th class="col">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>

                                                <tr>
                                                    <td class="text-center">1</td>
                                                    <td colspan="6" class="text-start"><strong>Nomination for benefits
                                                            under the Central Government Employees Group Insurance
                                                            Scheme, 1980</strong></td>
                                                </tr>
                                                <tr>
                                                    <td></td>
                                                    <td>a) Form-7 (if Unmarried) or ii) Form-8 (if Married)</td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">Download
                                                            Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">View
                                                            Sample</a></td>
                                                    <td><input type="file" class="form-control"></td>
                                                    <td class="text-center"><span class="text-success">View Uploaded
                                                            Forms</span></td>
                                                    <td class="text-center"><span
                                                            class="badge bg-success">Completed</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>2</td>
                                                    <td>National Pensions System (NPS) - subscription Registration Form
                                                    </td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">Download
                                                            Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">View
                                                            Sample</a></td>
                                                    <td><input type="file" class="form-control"></td>
                                                    <td class="text-center"><span class="text-success">View Uploaded
                                                            Forms</span></td>
                                                    <td class="text-center"><span
                                                            class="badge bg-success">Completed</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>3</td>
                                                    <td>Employee Information Sheet Form</td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">Download
                                                            Forms</a></td>
                                                    <td class="text-center"><a href="#" class="btn btn-link p-0"
                                                            style="text-decoration: none;">View
                                                            Sample</a></td>
                                                    <td><input type="file" class="form-control"></td>
                                                    <td class="text-center"><span class="text-success">View Uploaded
                                                            Forms</span></td>
                                                    <td class="text-center"><span
                                                            class="badge bg-success">Completed</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab-9" role="tabpanel" aria-labelledby="tab-9-tab">
                            <!-- Reports (Admin Only) -->
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="fw-bold text-primary mb-3">Reports</h5>
                                    <p class="text-muted mb-4">This section is for administrative use only.</p>
                                    <div class="table-responsive">
                                        <table
                                            class="table table-bordered align-middle text-center table-hover table-striped text-nowrap">
                                            <thead class="table-light text-center">
                                                <tr>
                                                    <th class="col">Sr. No.</th>
                                                    <th class="col">OT Name</th>
                                                    <th class="col">Programme Structure</th>
                                                    <th class="col">Family Details DOC</th>
                                                    <th class="col">Close Relation Doc</th>
                                                    <th class="col">Dowry Declaration</th>
                                                    <th class="col">Marital Declaration</th>
                                                    <th class="col">Hometown Doc</th>
                                                    <th class="col">Immovable Property</th>
                                                    <th class="col">Debts And Liabilities</th>
                                                    <th class="col">Surety Bond (IAS/IPS)</th>
                                                    <th class="col">Surety Bond (Other Services)</th>
                                                    <th class="col">Oath Affirmation</th>
                                                    <th class="col">Certificate Assumption</th>
                                                    <th class="col">Married/Unmarried</th>
                                                    <th class="col">NPS Form</th>
                                                    <th class="col">Employee Information</th>
                                                    <th class="col">Status</th>
                                                    <th class="col">Remarks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Example Row -->
                                                <tr>
                                                    <td>1</td>
                                                    <td>Pratik Ashok Dhumal</td>
                                                    <td>1601339565</td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0 text-danger">Pending</a>
                                                    </td>
                                                    <td><a href="#" class="btn btn-success py-3 btn-sm">Success</a></td>
                                                    <td><textarea name="" id="" class="form-control"></textarea></td>
                                                </tr>
                                                <tr>
                                                    <td>1</td>
                                                    <td>Aditya Srivastava</td>
                                                    <td>1601339565</td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0">View</a></td>
                                                    <td><a href="#" class="btn btn-link p-0 text-danger">Pending</a>
                                                    </td>
                                                    <td><a href="#" class="btn btn-success py-3 btn-sm">Success</a></td>
                                                    <td><textarea name="" id="" class="form-control"></textarea></td>
                                                </tr>
                                                <!-- Add more rows as needed -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!-- Footer -->
    <!-- Footer -->
    <footer class="mt-4 text-white py-3" style="background-color: #004a93;">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <p class="mb-0" style="font-size: 14px;">&copy; {{ date('Y') }} Lal Bahadur Shastri National
                        Academy
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous">
    </script>
    <script src="{{ asset('admin_assets/js/google-translate.js') }}"></script>
    <script src="{{ asset('admin_assets/js/weights.js') }}"></script>

    <!-- google translate -->
    <!-- Google Translate Code -->
    <script type="text/javascript">
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'en'
        }, 'google_translate_element');
    }
    </script>
    <script type="text/javascript" src="js/google-translate.js"></script>
    <!-- End Google Translate Code -->

    <!-- accessibility -->
    <script src="js/weights.js"></script>
    <!-- font increase -->
    <script>
    var $affectedElements = $("*"); // Can be extended, ex. $("div, p, span.someClass")

    // Storing the original size in a data attribute so size can be reset
    $affectedElements.each(function() {
        var $this = $(this);
        $this.data("orig-size", $this.css("font-size"));
    });

    $("#btn-increase").click(function() {
        changeFontSize(1);
    })

    $("#btn-decrease").click(function() {
        changeFontSize(-1);
    })

    $("#btn-orig").click(function() {
        $affectedElements.each(function() {
            var $this = $(this);
            $this.css("font-size", $this.data("orig-size"));
        });
    })

    function changeFontSize(direction) {
        $affectedElements.each(function() {
            var $this = $(this);
            $this.css("font-size", parseInt($this.css("font-size")) + direction);
        });
    }
    </script>

    <!-- light dark theme -->

    <script>
    const checkbox = document.getElementById("checkbox");
    const isDarkMode = localStorage.getItem("darkMode") === "true";
    checkbox.checked = isDarkMode;
    const toggleDarkMode = () => {
        const isDarkMode = checkbox.checked;
        document.body.classList.toggle("dark", isDarkMode);
        localStorage.setItem("darkMode", isDarkMode);
    };
    checkbox.addEventListener("change", toggleDarkMode);
    toggleDarkMode();
    </script>

</body>

</html>