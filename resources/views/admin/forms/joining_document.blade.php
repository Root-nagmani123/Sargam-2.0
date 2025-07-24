<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration - Foundation Course | Lal Bahadur Shastri National Academy of Administration</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('admin_assets/images/logos/favicon.ico') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }

        .sidebar {
            padding: 15px;
            min-height: calc(100vh - 120px);
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
            margin-bottom: 10px;
        }

        .top-header {
            background-color: #004a93;
            color: white;
            padding: 5px 15px;
        }

        .document-section {
            margin-bottom: 30px;
        }

        .document-section h5 {
            color: #004a93;
            margin-bottom: 20px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border: 1px solid #dee2e6;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .badge {
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background-color: #28a745;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-primary {
            background-color: #004a93;
            border-color: #004a93;
        }

        .btn-outline-primary {
            color: #004a93;
            border-color: #004a93;
        }

        .btn-outline-primary:hover {
            background-color: #004a93;
            color: white;
        }

        footer {
            background-color: #004a93;
            padding: 1rem 0;
            color: #fff;
            margin-top: auto;
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
        <div class="container-fluid">
            <nav class="navbar navbar-expand-lg">
                <div class="container-fluid px-0">
                    <a class="navbar-brand me-2" href="#">
                        <img src="https://i.pinimg.com/736x/a8/fa/ef/a8faef978e6230b6a12d1c29c62d5edf.jpg"
                            alt="Logo 1" height="40">
                    </a>
                    <span class="vr mx-2"></span>
                    <a class="navbar-brand" href="#">
                        <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="Logo 2"
                            height="40">
                    </a>

                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                        data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                        aria-label="Toggle navigation">
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
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#"
                                    role="button" id="accountDropdown" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i class="material-icons" style="color: #004a93;">account_circle</i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="accountDropdown">
                                    <li>
                                        <form action="#" method="POST">
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
        <div class="container-fluid mt-3">
            <div class="row">
                <!-- Left Sidebar -->
                <div class="col-md-3 col-lg-2 sidebar">
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        @if ($childForms->isEmpty())
                            <a class="nav-link active mb-4" href="{{ route('forms.show', $form->id) }}">
                                {{ $form->name }}
                            </a>
                        @else
                            @foreach ($childForms as $child)
                                <a class="nav-link mb-4 {{ $child->id == $form->id ? 'active' : '' }}"
                                    href="{{ route('forms.show', $child->id) }}">
                                    {{ $child->name }}
                                </a>
                            @endforeach
                            <a class="nav-link mb-4 {{ request()->routeIs('fc.joining.index') ? 'active' : '' }}"
                                href="{{ route('fc.joining.index', ['formId' => $form->id]) }}">
                                Joining Documents
                            </a>
                            <a class="nav-link mb-4 {{ request()->routeIs('admin.joining-documents.index') ? 'active' : '' }}"
                                href="{{ route('admin.joining-documents.index', ['formId' => $form->id]) }}">
                                Report (Admin Only)
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="col-md-9 col-lg-10">

                    <x-session_message />
                    
                    <!-- Instruction Box and Content in Same Div -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="instruction-box">
                        <ul class="mb-0">
                            <li>
                                All the documents are compulsory to fill up and upload.
                                <ol class="mt-2">
                                    <li>Download the forms.</li>
                                    <li>Fill up all the required fields / details and duly sign the document and upload it.</li>
                                    <li>Only PDF format is allowed for upload.</li>
                                    <li>Maximum file size allowed is 1 MB.</li>
                                </ol>
                            </li>
                        </ul>
                    </div>
                        </div>
                    </div>

                    <form action="{{ route('fc.joining.upload') }}" method="POST" enctype="multipart/form-data">
             @csrf

             <!-- Administration Section Related Documents -->
             <div class="card mb-4" style="border-left:4px solid #004a93;">
                 <div class="card-body">
                     <h5 class="fw-bold text-primary mb-3">Administration Section Related Documents</h5>
                     <div class="table-responsive">
                         <table class="table table-bordered align-middle table-hover table-striped">
                             <thead class="table-light text-center">
                                 <tr>
                                     <th class="col">Sr.No.</th>
                                     <th class="col">Document Title</th>
                                     <th class="col">Upload</th>
                                     <th class="col">View Uploaded Forms</th>
                                     <th class="col">Sample Documents</th>
                                     <th class="col">Download Forms</th>
                                     <th class="col">Status</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 <tr>
                                     <td class="text-center">1</td>
                                     <td> Family Details Form (Form - 3) of Rules 54(12) of CCS (Pension) Rules, 1972</td>
                                     <td><input type="file" name="admin_family_details_form" class="form-control"></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_family_details_form))
                                             <a href="{{ asset('storage/' . $documents->admin_family_details_form) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_family_details1.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_family_details_form))
                                             <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_family_details_form)) }}"
                                                 download class="btn btn-sm btn-outline-primary">
                                                 <i class="bi bi-download"></i> Download
                                             </a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>

                                     <td class="text-center">
                                         @if (!empty($documents->admin_family_details_form))
                                             <span class="badge bg-success">Completed</span>
                                         @else
                                             <span class="badge bg-warning">Pending</span>
                                         @endif
                                     </td>
                                 </tr>

                                 <tr>
                                     <td class="text-center">2</td>
                                     <td colspan="6"><strong>Declaration of Close Relation (two copies)</strong></td>
                                 </tr>
                                 <tr>
                                     <td></td>
                                     <td>
                                         a) National of or are domiciled in other countries and
                                         <br>
                                         b) Residing in India, who are non-Indian origin
                                     </td>
                                     <td><input type="file" name="admin_close_relation_declaration" class="form-control">
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_close_relation_declaration))
                                             <a href="{{ asset('storage/' . $documents->admin_close_relation_declaration) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_close_relations_2.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_close_relation_declaration))
                                             <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_close_relation_declaration)) }}"
                                                 download class="btn btn-sm btn-outline-primary">
                                                 <i class="bi bi-download"></i> Download
                                             </a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_close_relation_declaration))
                                             <span class="badge bg-success">Completed</span>
                                         @else
                                             <span class="badge bg-warning">Pending</span>
                                         @endif
                                     </td>
                                 </tr>

                                 <tr>
                                     <td class="text-center">3</td>
                                     <td>Dowry Declaration - Declaration under Rule 13 of CCS (Conduct)
                                         Rule 1964 (two copies)</td>
                                     <td><input type="file" name="admin_dowry_declaration" class="form-control"></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_dowry_declaration))
                                             <a href="{{ asset('storage/' . $documents->admin_dowry_declaration) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_dowry_declaration3.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_dowry_declaration))
                                             <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_dowry_declaration)) }}"
                                                 download class="btn btn-sm btn-outline-primary">
                                                 <i class="bi bi-download"></i> Download
                                             </a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_dowry_declaration))
                                             <span class="badge bg-success">Completed</span>
                                         @else
                                             <span class="badge bg-warning">Pending</span>
                                         @endif
                                     </td>
                                 </tr>

                                 <tr>
                                     <td class="text-center">4</td>
                                     <td>Marital Status - Declaration under Rule 13 of CCS (Conduct) Rule
                                         1964 (two copies)</td>
                                     <td><input type="file" name="admin_marital_status" class="form-control"></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_marital_status))
                                             <a href="{{ asset('storage/' . $documents->admin_marital_status) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>

                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_marital_declaration4.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_marital_status))
                                             <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_marital_status)) }}"
                                                 download class="btn btn-sm btn-outline-primary">
                                                 <i class="bi bi-download"></i> Download
                                             </a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_marital_status))
                                             <span class="badge bg-success">Completed</span>
                                         @else
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>

                                 </tr>

                                 <tr>
                                     <td class="text-center">5</td>
                                     <td>Home Town Declaration (two copies)</td>
                                     <td><input type="file" name="admin_home_town_declaration" class="form-control">
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_home_town_declaration))
                                             <a href="{{ asset('storage/' . $documents->admin_home_town_declaration) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_home_town5.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_home_town_declaration))
                                             <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_home_town_declaration)) }}"
                                                 download class="btn btn-sm btn-outline-primary">
                                                 <i class="bi bi-download"></i> Download
                                             </a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_home_town_declaration))
                                             <span class="badge bg-success">Completed</span>
                                         @else
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>
                                 </tr>

                                 <tr>
                                     <td class="text-center">6</td>
                                     <td colspan="6"><strong> Declaration of Movable, Immovable and valuable property on
                                             first appointment (two copies)</strong></td>
                                 </tr>
                                 <tr>
                                     <td></td>
                                     <td>6-A: Statement of Immovable Property on first appointment</td>
                                     <td><input type="file" name="admin_property_immovable" class="form-control"></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_property_immovable))
                                             <a href="{{ asset('storage/' . $documents->admin_property_immovable) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_immovable_property6a.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_property_immovable))
                                             <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_property_immovable)) }}"
                                                 download class="btn btn-sm btn-outline-primary">
                                                 <i class="bi bi-download"></i> Download
                                             </a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_property_immovable))
                                             <span class="badge bg-success">Completed</span>
                                         @else
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>
                                 </tr>
                                 <tr>
                                     <td></td>
                                     <td>6-B: Statement of Movable Property on first appointment</td>
                                     <td><input type="file" name="admin_property_movable" class="form-control"></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_property_movable))
                                             <a href="{{ asset('storage/' . $documents->admin_property_movable) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_movable_property6b.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_property_movable))
                                             <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_property_movable)) }}"
                                                 download class="btn btn-sm btn-outline-primary">
                                                 <i class="bi bi-download"></i> Download
                                             </a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_property_movable))
                                             <span class="badge bg-success">Completed</span>
                                         @else
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>
                                 </tr>
                                 <tr>
                                     <td></td>
                                     <td>6-C: Statement of Debts and Other Liabilities on first
                                         appointment</td>
                                     <td><input type="file" name="admin_property_liabilities" class="form-control">
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_property_liabilities))
                                             <a href="{{ asset('storage/' . $documents->admin_property_liabilities) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_debts_other_liabilities6c.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_property_liabilities))
                                             <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_property_liabilities)) }}"
                                                 download class="btn btn-sm btn-outline-primary">
                                                 <i class="bi bi-download"></i> Download
                                             </a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_property_liabilities))
                                             <span class="badge bg-success">Completed</span>
                                         @else
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>
                                 </tr>

                                 <tr>
                                     <td class="text-center">7</td>
                                     <td colspan="6"><strong>Surety Bond-for</strong></td>
                                 </tr>
                                 <tr>
                                     <td></td>
                                     <td>Surety Bond for IAS or IPS or IFoS (whichever is applicable)</td>
                                     <td><input type="file" name="admin_bond_ias_ips_ifos" class="form-control"></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_bond_ias_ips_ifos))
                                             <a href="{{ asset('storage/' . $documents->admin_bond_ias_ips_ifos) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_surety_bond_iasips7a.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_bond_ias_ips_ifos))
                                             <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_bond_ias_ips_ifos)) }}"
                                                 download class="btn btn-sm btn-outline-primary">
                                                 <i class="bi bi-download"></i> Download
                                             </a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_bond_ias_ips_ifos))
                                             <span class="badge bg-success">Completed</span>
                                         @else
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>

                                 </tr>
                                 <tr>
                                     <td></td>
                                     <td>Surety Bond for other services (other than All India
                                         Services)
                                         (if applicable)</td>
                                     <td><input type="file" name="admin_bond_other_services" class="form-control">
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_bond_other_services))
                                             <a href="{{ asset('storage/' . $documents->admin_bond_other_services) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_surety_bond_other_services7b.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_bond_other_services))
                                             <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_bond_other_services)) }}"
                                                 download class="btn btn-sm btn-outline-primary">
                                                 <i class="bi bi-download"></i> Download
                                             </a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_bond_other_services))
                                             <span class="badge bg-success">Completed</span>
                                         @else
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>
                                 </tr>

                                 <tr>
                                     <td class="text-center">8</td>
                                     <td><strong>Other Documents</strong></td>
                                 </tr>
                                 <tr>
                                     <td></td>
                                     <td>Form of OATH / Affirmation</td>
                                     <td><input type="file" name="admin_oath_affirmation" class="form-control"></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_oath_affirmation))
                                             <a href="{{ asset('storage/' . $documents->admin_oath_affirmation) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_main_assumption_charge.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_oath_affirmation))
                                             <a href="{{ asset('storage/fc_joining_documents/' . $userId . '/' . basename($documents->admin_oath_affirmation)) }}"
                                                 download class="btn btn-sm btn-outline-primary">
                                                 <i class="bi bi-download"></i> Download
                                             </a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_oath_affirmation))
                                             <span class="badge bg-success">Completed</span>
                                         @else
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>
                                 </tr>
                                 <tr>
                                     <td></td>
                                     <td>Certificate of Assumption of Charge</td>
                                     <td><input type="file" name="admin_certificate_of_charge" class="form-control">
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_certificate_of_charge))
                                             <a href="{{ asset('storage/' . $documents->admin_certificate_of_charge) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center"><a
                                             href="{{ asset('admin_assets/sample/joining_documents/sample_main_assumption_charge.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_certificate_of_charge))
                                             <a href="{{ asset('storage/' . $documents->admin_certificate_of_charge) }}"
                                                 download class="btn btn-sm btn-outline-primary">
                                                 <i class="bi bi-download"></i> Download
                                             </a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->admin_certificate_of_charge))
                                             <span class="badge bg-success">Completed</span>
                                         @else
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>
                                 </tr>
                             </tbody>
                         </table>
                     </div>
                 </div>
             </div>

             <!-- Accounts Section Related Documents -->
             <div class="card" style="border-left:4px solid #004a93;">
                 <div class="card-body">
                     <h5 class="fw-bold text-primary mb-3">Accounts Section Related Documents</h5>
                     <div class="table-responsive">
                         <table
                             class="table table-bordered align-middle table-hover table-striped">
                             <thead class="table-light">
                                 <tr>
                                     <th class="col">Sr.No.</th>
                                     <th class="col">Document Title</th>
                                     <th class="col">Upload</th>
                                     <th class="col">View Uploaded Forms</th>
                                     <th class="col">Sample Documents</th>
                                     <th class="col">Download Forms</th>
                                     <th class="col">Status</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 <tr>
                                     <td class="text-start">1</td>
                                     <td class="text-start" colspan="6"><strong>Nomination for benefits
                                             under the Central Government Employees Group Insurance
                                             Scheme, 1980</strong></td>
                                 </tr>
                                 <tr>
                                     <td></td>
                                     <td>a) Form-7 (if Unmarried) or ii) Form-8 (if Married)</td>
                                     <td><input type="file" name="accounts_nomination_form" class="form-control"></td>
                                     <td class="text-center">
                                         @if (!empty($documents->accounts_nomination_form))
                                             <a href="{{ asset('storage/' . $documents->accounts_nomination_form) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td><a href="{{ asset('admin_assets/sample/joining_documents/sample_close_relations_2.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
                                     <td class="text-center">
                                         @if (!empty($documents->accounts_nomination_form))
                                             <a href="{{ asset('storage/' . $documents->accounts_nomination_form) }}"
                                                 download class="btn btn-sm btn-outline-primary">
                                                 <i class="bi bi-download"></i> Download
                                             </a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->accounts_nomination_form))
                                             <span class="badge bg-success">Completed</span>
                                         @else
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>
                                 </tr>
                                 <tr>
                                     <td>2</td>
                                     <td>National Pensions System (NPS) - subscription Registration Form</td>
                                     <td><input type="file" name="accounts_nps_registration" class="form-control">
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->accounts_nps_registration))
                                             <a href="{{ asset('storage/' . $documents->accounts_nps_registration) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td><a href="{{ asset('admin_assets/sample/joining_documents/sample_nps_form10.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
                                     <td>
                                         @if (!empty($documents->accounts_nps_registration))
                                             <a href="{{ asset('storage/' . $documents->accounts_nps_registration) }}"
                                                 download class="btn btn-sm btn-outline-primary">
                                                 <i class="bi bi-download"></i> Download
                                             </a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->accounts_nps_registration))
                                             <span class="badge bg-success">Completed</span>
                                         @else
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>

                                 </tr>
                                 <tr>
                                     <td>3</td>
                                     <td>Employee Information Sheet Form</td>
                                     <td><input type="file" name="accounts_employee_info_sheet" class="form-control">
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->accounts_employee_info_sheet))
                                             <a href="{{ asset('storage/' . $documents->accounts_employee_info_sheet) }}"
                                                 target="_blank" class="btn btn-link p-0 text-primary">View</a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td><a href="{{ asset('admin_assets/sample/joining_documents/sample_employee_information11.pdf') }}"
                                             class="btn btn-link p-0 text-primary" target="_blank">View Sample</a></td>
                                     <td>
                                         @if (!empty($documents->accounts_employee_info_sheet))
                                             <a href="{{ asset('storage/' . $documents->accounts_employee_info_sheet) }}"
                                                 download class="btn btn-sm btn-outline-primary">
                                                 <i class="bi bi-download"></i> Download
                                             </a>
                                         @else
                                             <span class="text-muted">No file uploaded</span>
                                         @endif
                                     </td>
                                     <td class="text-center">
                                         @if (!empty($documents->accounts_employee_info_sheet))
                                             <span class="badge bg-success">Completed</span>
                                         @else
                                             <span class="badge bg-warning ">Pending</span>
                                         @endif
                                     </td>
                                 </tr>
                             </tbody>
                         </table>
                     </div>
                 </div>
             </div>

             <!-- Submit Button -->
             <div class="text-end mb-4 mt-3">
                 <button type="submit" class="text btn btn-primary">Submit</button>
             </div>
         </form>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">© 2023 Lal Bahadur Shastri National Academy of Administration. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-white me-3">Privacy Policy</a>
                    <a href="#" class="text-white me-3">Terms of Service</a>
                    <a href="#" class="text-white">Contact Us</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
        
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const maxSize = 1024 * 1024; // 1MB
            const allowedType = 'application/pdf';

            document.querySelectorAll('input[type="file"]').forEach(input => {
                // Create error container just after each file input
                const errorDiv = document.createElement('div');
                errorDiv.className = 'text-danger mt-1 fw-semibold small';
                input.parentNode.appendChild(errorDiv);

                input.addEventListener('change', function() {
                    const file = this.files[0];
                    errorDiv.textContent = ''; // Clear old errors

                    if (file) {
                        if (file.size > maxSize) {
                            errorDiv.textContent = `"${file.name}" exceeds the 1MB size limit.`;
                            this.value = ''; // Clear file input
                            return;
                        }

                        if (file.type !== allowedType) {
                            errorDiv.textContent = `"${file.name}" must be a PDF file.`;
                            this.value = ''; // Clear file input
                            return;
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>