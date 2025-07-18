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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
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
            background-color: #f8f9fa;
            min-height: calc(100vh - 120px);
            border-right: 1px solid #dee2e6;
        }
        
        .sidebar .nav-pills .nav-link.active {
            font-weight: 500;
            background-color: #004a93;
            border: 1px solid #ddd;
            color: #fff;
            border-radius: 0.25rem;
        }
        
        .sidebar .nav-link:hover {
            background-color: #004a93;
            color: #fff !important;
        }
        
        .sidebar .nav-link {
            color: #000;
            font-weight: 500;
            background-color: #fff;
            border: 1px solid #ddd;
            margin-bottom: 10px;
        }
        
        .top-header {
            background-color: #004a93;
            color: white;
            padding: 5px 15px;
        }
        
        .main-content-container {
            padding: 20px;
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
        
        footer {
            background-color: #004a93;
            padding: 1rem 0;
            color: #fff;
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
                        <li class="nav-item">
                            <a href="#content" class="text-white text-decoration-none" style="font-size: 12px;">Skip to Main Content</a>
                        </li>
                        <span class="text-muted mx-3">|</span>
                        <li class="nav-item">
                            <a href="#" class="text-white text-decoration-none">
                                <img src="{{ asset('images/text_to_speech.png') }}" alt="" width="20">
                                <span class="ms-1" style="font-size: 12px;">Screen Reader</span>
                            </a>
                        </li>
                        <span class="text-muted mx-3">|</span>
                        <li class="nav-item"><a href="#" class="text-white text-decoration-none mx-2" style="font-size: 12px;">A+</a></li>
                        <li class="nav-item"><a href="#" class="text-white text-decoration-none mx-2" style="font-size: 12px;">A</a></li>
                        <li class="nav-item"><a href="#" class="text-white text-decoration-none mx-2" style="font-size: 12px;">A-</a></li>
                        <span class="text-muted mx-3">|</span>
                        <li class="nav-item">
                            <a href="#" class="text-white text-decoration-none">
                                <img src="{{ asset('images/contrast.png') }}" alt="" width="20">
                            </a>
                        </li>
                        <span class="text-muted mx-3">|</span>
                        <li class="nav-item">
                            <a href="#" class="text-white text-decoration-none">
                                <img src="{{ asset('images/Regular.png') }}" alt="" width="20">
                                <select name="lang" class="form-select form-select-sm d-inline-block" style="width: 100px; background-color: transparent; border: none; color: #fff; font-size: 12px;">
                                    <option value="">Language</option>
                                    <option value="en" selected>English</option>
                                </select>
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
                        <img src="https://i.pinimg.com/736x/a8/fa/ef/a8faef978e6230b6a12d1c29c62d5edf.jpg" alt="Logo 1" height="40">
                    </a>
                    <span class="vr mx-2"></span>
                    <a class="navbar-brand" href="#">
                        <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="Logo 2" height="40">
                    </a>

                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                        <ul class="navbar-nav align-items-center">
                            <li class="nav-item">
                                <a class="nav-link mx-3" href="#">About Us</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link mx-3" href="#">FAQs</a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="material-icons" style="color: #004a93;">account_circle</i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
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
                            <a class="nav-link active mb-3" href="{{ route('forms.show', $form->id) }}">
                                {{ $form->name }}
                            </a>
                        @else
                            @foreach ($childForms as $child)
                                <a class="nav-link mb-3 {{ $child->id == $form->id ? 'active' : '' }}" 
                                   href="{{ route('forms.show', $child->id) }}">
                                    {{ $child->name }}
                                </a>
                            @endforeach
                            <a class="nav-link mb-3 {{ request()->routeIs('fc.joining.index') ? 'active' : '' }}" 
                               href="{{ route('fc.joining.index', ['formId' => $form->id]) }}">
                                Joining Documents
                            </a>
                            <a class="nav-link mb-3 {{ request()->routeIs('admin.joining-documents.index') ? 'active' : '' }}" 
                               href="{{ route('admin.joining-documents.index', ['formId' => $form->id]) }}">
                                Report (Admin Only)
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="col-md-9 col-lg-10 main-content-container">
                    <!-- Filter Section -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label">Search OT Name</label>
                                    <input type="text" name="search" class="form-control" placeholder="Search OT Name..." value="{{ request('search') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="">-- All Status --</option>
                                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Complete</option>
                                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Pending</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex gap-2 mt-2 mt-md-0">
                                    <button class="btn btn-primary" type="submit">Filter</button>
                                    <a href="{{ route('admin.joining-documents.index', ['formId' => $form->id]) }}" class="btn btn-secondary">Reset</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Report Table -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped align-middle text-center">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Serial Number</th>
                                            <th>OT Name</th>
                                            <th>Programme Structure</th>
                                            @foreach ($fields as $label)
                                                <th>{{ $label }}</th>
                                            @endforeach
                                            <th>Check Status</th>
                                            <th>Download All</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($students as $index => $student)
                                            @php
                                                $upload = $uploads[$student->id] ?? null;
                                            @endphp
                                            <tr>
                                                <td>{{ ($students->currentPage() - 1) * $students->perPage() + $loop->iteration }}</td>
                                                <td>{{ $student->name }}</td>
                                                <td>{{ $student->id }}</td>

                                                @foreach ($fields as $fieldKey => $fieldLabel)
                                                    <td>
                                                        @if ($upload && !empty($upload->$fieldKey))
                                                            <a href="{{ asset('storage/' . $upload->$fieldKey) }}" target="_blank" class="btn btn-link p-0 text-primary">View</a> |
                                                            <a href="{{ asset('storage/' . $upload->$fieldKey) }}" download class="btn btn-link p-0 text-primary">Download</a>
                                                        @else
                                                            <span class="text-danger">Pending</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                                <td>
                                                    @php
                                                        $allDone = $upload && collect($fields)->every(fn($label, $key) => !empty($upload->$key));
                                                    @endphp
                                                    <span class="badge {{ $allDone ? 'bg-success' : 'bg-warning text-dark' }}">
                                                        {{ $allDone ? 'Success' : 'Pending' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('admin.joining-documents.download-all', $student->id) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="fa fa-download"></i> Download All
                                                    </a>
                                                </td>
                                                <td style="min-width: 250px;">
                                                    <form method="POST" action="{{ route('admin.joining-documents.save-remark', $student->id) }}">
                                                        @csrf
                                                        <textarea name="remark" class="form-control form-control-sm text-center" rows="1"
                                                            style="width: 100%; min-height: 60px; resize: vertical;"
                                                            onchange="this.form.submit()"
                                                            placeholder="Enter remarks">{{ $upload->remark ?? '' }}</textarea>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-end mt-4">
                                {!! $students->links() !!}
                            </div>
                        </div>
                    </div>
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
        integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk"
        crossorigin="anonymous"></script>
</body>
</html>