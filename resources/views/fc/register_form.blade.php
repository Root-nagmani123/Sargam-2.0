<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration - Foundation Course | Lal Bahadur Shastri National Academy of Administration</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{asset('admin_assets/images/logos/favicon.ico')}}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
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
                        <span class="text-muted me-3 ms-3">|</span>
                        <li class="nav-item"><a href="#" class="text-white text-decoration-none"><img
                                    src="{{ asset('images/text_to_speech.png') }}" alt="" width="20"><span class="ms-1"
                                    style=" font-size: 12px;">Screen Reader</span></a></li>
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
                                    </select></span></a></li>
                        <span class="text-muted me-3 ">|</span>
                        <li>
                            <a id="uw-widget-custom-trigger" contenteditable="false" style="cursor: pointer;">
                        <li class="nav-item"><a href="#" class="text-white text-decoration-none"><img
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
                <div class="col-md-4 col-lg-3 sidebar">
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        <button class="nav-link active mb-4" id="tab-1-tab" data-bs-toggle="pill"
                            data-bs-target="#tab-1" type="button" role="tab" aria-controls="tab-1"
                            aria-selected="true">Descriptive Roll</button>
                        <button class="nav-link mb-4" id="tab-2-tab" data-bs-toggle="pill" data-bs-target="#tab-2"
                            type="button" role="tab" aria-controls="tab-2" aria-selected="true">Descriptive Roll
                            II</button>
                        <button class="nav-link mb-4" id="tab-3-tab" data-bs-toggle="pill" data-bs-target="#tab-3"
                            type="button" role="tab" aria-controls="tab-3" aria-selected="true">Joining
                            Instructions</button>
                        <button class="nav-link mb-4" id="tab-4-tab" data-bs-toggle="pill" data-bs-target="#tab-4"
                            type="button" role="tab" aria-controls="tab-4" aria-selected="true">Joining
                            Documents</button>
                        <button class="nav-link mb-4" id="tab-5-tab" data-bs-toggle="pill" data-bs-target="#tab-5"
                            type="button" role="tab" aria-controls="tab-5" aria-selected="true">Bank Details</button>
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

                <!-- Form Content -->
                <div class="col-md-8 col-lg-9">
                    <div class="tab-content" id="v-pills-tabContent">
                        <div class="tab-pane fade show active" id="tab-1" role="tabpanel" aria-labelledby="tab-1-tab">
                            <!-- Personal Details -->
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title text-primary fw-bold" style="font-size: 24px;">Personal
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
                                    <h5 class="card-title text-primary fw-bold" style="font-size: 24px;">Personal
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
                    <p class="mb-0" style="font-size: 14px;">&copy; {{date('Y')}} Lal Bahadur Shastri National Academy of
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous">
    </script>

</body>

</html>