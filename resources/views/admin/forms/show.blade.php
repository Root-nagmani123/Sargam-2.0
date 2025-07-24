<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration - Foundation Course | Lal Bahadur Shastri National Academy of Administration</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('admin_assets/images/logos/favicon.ico') }}">
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
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

        .nav-item a span {
            font-size: 12px;
        }

        .nav .nav-item {
            margin-right: 10px;
        }

        /* Additional styles from second template */
        .section-container {
            margin-bottom: 2rem;
        }

        .section-title {
            background-color: #004a93;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            color: #fff;
            font-weight: 600;
        }

        .dynamic-table {
            margin-top: 1rem;
        }

        .file-preview img {
            max-width: 100px;
            margin-right: 5px;
        }

        .form-actions {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>

<body>
    <!-- Top Blue Bar (Govt of India) -->
    <div class="top-header d-flex justify-content-between align-items-center d-none d-md-block">
        <div class="container-fluid">
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
                            alt="Logo 1" height="80">
                    </a>
                    <span class="vr mx-2"></span>
                    <a class="navbar-brand" href="#">
                        <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="Logo 2"
                            height="80">
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
       <div class="container-fluid py-4">
         <div class="row mb-4">
     <div class="row align-items-center">
                                    <!-- Left Logo -->
                                    <div class="col-md-2 text-start">
                                        <img src="{{ asset($data->logo1 ? 'storage/' . $data->logo1 : 'admin_assets/images/logos/logo.png') }}"
                                            alt="logo1" style="max-height: 80px;" class="">
                                    </div>

                                    <!-- Center Heading -->
                                    <div class="col-md-8 text-center">
                                        <h3 class="mb-1">{!! $data->heading ?? '<b>Main Heading Here</b>' !!}</h3>
                                        <div class="small fw-bold text-muted">
                                            {{ $data->sub_heading ?? 'Sub Heading Here' }}
                                        </div>
                                    </div>

                                    <!-- Right Logo -->
                                    <div class="col-md-2 text-end">
                                        <img src="{{ asset($data->logo2 ? 'storage/' . $data->logo2 : 'images/azadi.png') }}"
                                            alt="logo2" style="max-height: 80px;" class="img-fluid">
                                    </div>
                                </div>
</div>
       </div>
        <div class="container-fluid">
            
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Home</li>
                    <li class="breadcrumb-item" aria-current="page" style="font-size: 20px;">Registration Form</li>
                </ol>
            </nav>

            @if (session('success') || session('error'))
                <div class="container mt-3">
                    <div class="alert alert-{{ session('success') ? 'success' : 'danger' }} alert-dismissible fade show"
                        role="alert">
                        {{ session('success') ?? session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"
                            aria-label="Close"></button>
                    </div>
                </div>
            @endif

            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-4 col-lg-2 sidebar">
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist"
                        aria-orientation="vertical">
                        @if ($childForms->isEmpty())
                            {{-- No children: show parent as active --}}
                            <a class="nav-link active mb-4" href="{{ route('forms.show', $form->id) }}">
                                {{ $form->name }}
                            </a>
                        @else
                            {{-- Has children: show all child forms (siblings) --}}
                            @foreach ($childForms as $child)
                                <a class="nav-link mb-4 {{ $child->id == $form->id ? 'active' : '' }}"
                                    href="{{ route('forms.show', $child->id) }}">
                                    {{ $child->name }}
                                </a>
                            @endforeach
                            <a class="nav-link mb-4 {{ request()->routeIs('fc.joining.index') ? 'active' : '' }}"
                                href="{{ route('fc.joining.index', $form->id) }}">
                                Joining Documents
                            </a>

                            <!-- Static Form: Admin Report -->
                            <a class="nav-link mb-4 {{ request()->routeIs('admin.joining-documents.index') ? 'active' : '' }}"
                                href="{{ route('admin.joining-documents.index', $form->id) }}">
                                Report (Admin Only)
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Form Content -->
                <div class="col-md-8 col-lg-10">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">
                               

                                <!-- Bottom Row Logos (Digital India & Swachh Bharat) -->
                                <div class="row align-items-center">
                                    <div class="col-md-6 d-flex justify-content-start">
                                        <img src="{{ asset($data->logo3 ? 'storage/' . $data->logo3 : 'images/digital.png') }}"
                                            alt="logo3" style="max-height: 60px; margin-right: 20px;"
                                            class="img-fluid">
                                    </div>
                                    <div class="col-md-6 d-flex justify-content-end">
                                        <img src="{{ asset($data->logo4 ? 'storage/' . $data->logo4 : 'images/swachh.png') }}"
                                            alt="logo4" style="max-height: 60px; margin-left: 20px;"
                                            class="img-fluid">
                                    </div>
                                </div>
                            </div>



                            <div class="tab-content" id="v-pills-tabContent">
                                <div class="tab-pane fade show active" id="{{ route('forms.show', $form->id) }}"
                                    role="tabpanel" aria-labelledby="{{ route('forms.show', $form->id) }}-tab">
                                    @if ($form->description)
                                        <div class="description mb-3">
                                            {{-- {{ $form->description }} --}}
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('forms.submit', $form->id) }}"
                                        enctype="multipart/form-data">
                                        @csrf

                                        @foreach ($sections as $section)
                                            <div class="section-container mb-4">
                                                <div class="section-title py-2 fw-bold">{{ $section->section_title }}
                                                </div>

                                                @if (isset($fieldsBySection[$section->id]))
                                                    @php
                                                        $maxCol = 0;
                                                        foreach ($fieldsBySection[$section->id] as $row) {
                                                            $maxCol = max($maxCol, max(array_keys($row)));
                                                        }
                                                    @endphp

                                                    <table class="table table-bordered dynamic-table">
                                                        <thead>
                                                            <tr>
                                                                @for ($i = 0; $i <= $maxCol; $i++)
                                                                    @if (isset($headersBySection[$section->id][$i]))
                                                                        <th>{{ $headersBySection[$section->id][$i] }}
                                                                        </th>
                                                                        <input type="hidden"
                                                                            name="header_{{ $section->id }}_{{ $i }}"
                                                                            value="{{ $headersBySection[$section->id][$i] }}">
                                                                    @else
                                                                        <th></th>
                                                                    @endif
                                                                @endfor
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($fieldsBySection[$section->id] as $rowIndex => $row)
                                                                <tr id="row-{{ $rowIndex }}">
                                                                    @for ($i = 0; $i <= $maxCol; $i++)
                                                                        <td>
                                                                            @if (isset($row[$i]))
                                                                                @include(
                                                                                    'admin.forms.field-types',
                                                                                    [
                                                                                        'field' => $row[$i],
                                                                                        'value' =>
                                                                                            $submissions[
                                                                                                $row[$i]->formname
                                                                                            ]->fieldvalue ?? null,
                                                                                        'name' => "table_{$section->id}_{$rowIndex}_{$i}",
                                                                                    ]
                                                                                )
                                                                            @else
                                                                                &nbsp;
                                                                            @endif
                                                                        </td>
                                                                    @endfor
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                        <tfoot>
                                                            <tr>
                                                                <td colspan="{{ $maxCol + 1 }}">
                                                                    <button
                                                                        class="replicate-row btn btn-sm btn-success"
                                                                        onclick="replicateRow(event)">
                                                                        <img src="{{ asset('images/increase.png') }}"
                                                                            alt="Add Row"
                                                                            style="width: 15px; height: 15px;">
                                                                    </button>
                                                                    <button class="remove-row btn btn-sm btn-danger"
                                                                        onclick="removeRow(event)">
                                                                        <img src="{{ asset('images/decrease.png') }}"
                                                                            alt="Remove Row"
                                                                            style="width: 15px; height: 15px;">
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                @endif

                                                @if (isset($gridFields[$section->id]))
                                                    <div class="row">
                                                        @foreach ($gridFields[$section->id] as $field)
                                                            <div class="col-md-4 mb-3">
                                                                @include('admin.forms.field-types', [
                                                                    'field' => $field,
                                                                    'value' =>
                                                                        $submissions[$field->formname]->fieldvalue ?? null,
                                                                    'name' => "field_{$field->formname}",
                                                                ])
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach

                                        <div class="form-actions border-top pt-3">
                                            <div class="float-end">
                                                <button type="submit" class="btn btn-primary">Submit</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="mt-4 text-white py-3" style="background-color: #004a93;">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <p class="mb-0" style="font-size: 14px;">&copy; 2024 Lal Bahadur Shastri National Academy of
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

    <script>
        function previewImage(event, input) {
            const fileList = input.files;
            const previewContainer = document.getElementById(`file-preview-${input.id || input.name}`);

            if (!previewContainer) {
                console.error(`Preview container not found for ID: file-preview-${input.id || input.name}`);
                return;
            }

            previewContainer.innerHTML = '';

            if (fileList.length > 0) {
                Array.from(fileList).forEach(file => {
                    const fileName = file.name;
                    const fileExtension = fileName.split('.').pop().toLowerCase();
                    const fileUrl = URL.createObjectURL(file);

                    // Image Preview
                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = fileUrl;
                        img.style.width = '100px';
                        img.style.height = '100px';
                        img.style.margin = '5px';
                        img.style.display = 'inline-block';
                        previewContainer.appendChild(img);

                        // PDF Preview
                    } else if (file.type === 'application/pdf') {
                        const link = document.createElement('a');
                        link.href = fileUrl;
                        link.textContent = 'Preview PDF';
                        link.target = '_blank';
                        link.classList.add('btn', 'btn-danger', 'm-1');
                        previewContainer.appendChild(link);

                        // Other documents: DOC, DOCX, XLSX, PPT, TXT, ZIP, etc.
                    } else {
                        const link = document.createElement('a');
                        link.href = fileUrl;
                        link.textContent = `Download ${fileName}`;
                        link.setAttribute('download', fileName);
                        link.classList.add('btn', 'btn-secondary', 'm-1');
                        previewContainer.appendChild(link);
                    }
                });
            }
        }

        function replicateRow(event) {
            event.preventDefault();
            const table = event.target.closest('table').getElementsByTagName('tbody')[0];

            const lastRow = table.rows[table.rows.length - 1];
            const newRow = lastRow.cloneNode(true);

            const isDuplicate = checkDropdownDuplicates(newRow);
            if (isDuplicate) {
                resetRowInputs(lastRow);
            } else {
                const newRowIndex = table.rows.length;
                newRow.id = 'row-' + newRowIndex;

                const inputs = newRow.querySelectorAll('input, select, textarea');
                inputs.forEach(function(input) {
                    const match = input.name.match(/^table_(\d+)_\d+_(\d+)$/);
                    if (match) {
                        const sectionId = match[1];
                        const colIndex = match[2];
                        input.name = `table_${sectionId}_${newRowIndex}_${colIndex}`;
                        input.id = `table_${sectionId}_${newRowIndex}_${colIndex}`;
                    }
                });

                resetRowInputs(newRow);
                table.appendChild(newRow);
            }
        }

        function resetRowInputs(row) {
            row.querySelectorAll('input, select, textarea').forEach(input => {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
            });
        }

        function checkDropdownDuplicates(row) {
            const dropdowns = document.querySelectorAll('.dynamic-table tbody tr td:nth-child(1) select');
            const selectedValues = [];
            let isDuplicate = false;

            dropdowns.forEach(dropdown => {
                const selectedValue = dropdown.value;
                const selectedText = dropdown.options[dropdown.selectedIndex].text;
                if (selectedValue && selectedValues.includes(selectedValue)) {
                    alert(selectedValue + ' [' + selectedText + '] is already entered');
                    isDuplicate = true;
                } else {
                    selectedValues.push(selectedValue);
                }
            });

            return isDuplicate;
        }

        function removeRow(event) {
            event.preventDefault();
            const table = event.target.closest('table').getElementsByTagName('tbody')[0];
            if (table.rows.length > 1) {
                table.deleteRow(table.rows.length - 1);
            }
        }
    </script>
</body>

</html>
