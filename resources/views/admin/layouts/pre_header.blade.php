<!-- Required meta tags -->
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="app-base-url" content="{{ url('') }}">
<meta name="app-base-path" content="{{ request()->getBaseUrl() }}">
<meta name="faculty-index-path" content="{{ route('faculty.index', [], false) }}">
<!-- Force light color scheme to prevent system dark mode -->
<meta name="color-scheme" content="light">

<!-- Favicon icon-->
<link rel="shortcut icon" type="image/ico" href="{{asset('admin_assets/images/logos/favicon.ico')}}">
<!-- Core Css -->
<link rel="stylesheet" href="{{asset('admin_assets/css/styles.css')}}">
<link rel="stylesheet" href="{{asset('css/custom.css')}}">
<link rel="stylesheet" href="{{asset('admin_assets/css/dashboard-enhanced.css')}}">
<!-- CRITICAL: Force light mode CSS - must load AFTER Bootstrap CSS -->

<link rel="stylesheet" href="{{asset('admin_assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css')}}">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" />
<!-- Breadcrumb Component CSS -->
<link rel="stylesheet" href="{{asset('css/breadcrumb.css')}}">
<!-- Sidebar Menu Enhanced CSS -->
<link rel="stylesheet" href="{{asset('css/sidebar-menu-enhanced.css')}}">
<style>
.material-symbols-rounded {
  font-variation-settings:
  'FILL' 0,
  'wght' 400,
  'GRAD' 0,
  'opsz' 24
}

/* Force light mode - prevent dark mode styles */
html[data-bs-theme="dark"],
html:not([data-bs-theme])[data-bs-theme="dark"],
html {
  color-scheme: light !important;
  --bs-body-bg: #fff !important;
  --bs-body-color: #212529 !important;
  --bs-emphasis-color: #000 !important;
  --bs-secondary-color: rgba(33, 37, 41, 0.75) !important;
  --bs-secondary-bg: #e9ecef !important;
  --bs-tertiary-color: rgba(33, 37, 41, 0.5) !important;
  --bs-tertiary-bg: #f8f9fa !important;
  --bs-border-color: #dee2e6 !important;
  --bs-border-color-translucent: rgba(0, 0, 0, 0.175) !important;
}

/* Prevent Bootstrap dark mode CSS variables from being applied */
[data-bs-theme="dark"] {
  color-scheme: light !important;
}
</style>

<!-- FINAL OVERRIDE: Force light mode after ALL CSS loads -->
<style id="final-light-mode-override">
/* This MUST be the last style block to override everything */
* {
  color-scheme: light !important;
}

html,
html[data-bs-theme],
html[data-bs-theme="light"],
html[data-bs-theme="dark"],
body,
body[data-bs-theme],
body[data-bs-theme="light"],
body[data-bs-theme="dark"] {
  color-scheme: light !important;
  --bs-body-bg: #fff !important;
  --bs-body-color: #212529 !important;
  --bs-emphasis-color: #000 !important;
  --bs-secondary-color: rgba(33, 37, 41, 0.75) !important;
  --bs-secondary-bg: #e9ecef !important;
  --bs-tertiary-color: rgba(33, 37, 41, 0.5) !important;
  --bs-tertiary-bg: #f8f9fa !important;
  --bs-border-color: #dee2e6 !important;
  --bs-border-color-translucent: rgba(0, 0, 0, 0.175) !important;
  --bs-link-color: #0d6efd !important;
  --bs-link-hover-color: #0a58ca !important;
  --bs-heading-color: inherit !important;
  background-color: #fff !important;
  color: #212529 !important;
}
</style>

<style>
/* DataTables pagination: custom pill design (scoped only to DataTables) */
.dataTables_wrapper .dataTables_paginate {
  margin-top: 0.75rem;
}

.dataTables_wrapper .dataTables_paginate .pagination {
  gap: 0.35rem;
  align-items: center;
}

.dataTables_wrapper .dataTables_paginate .page-item {
  margin: 0 !important;
}

.dataTables_wrapper .dataTables_paginate .page-link {
  min-width: 2.1rem;
  height: 2.1rem;
  padding: 0 0.65rem;
  border-radius: 999px !important;
  border: 0 !important;
  background: #ffffff !important;
  color: #1f3f66 !important;
  font-weight: 600;
  line-height: 2rem;
  text-align: center;
  transition: all 0.2s ease;
  box-shadow: 0 1px 2px rgba(13, 110, 253, 0.08);
}

.dataTables_wrapper .dataTables_paginate .page-link:hover {
  background: #eef5ff !important;
  color: #0d6efd !important;
  transform: translateY(-1px);
}

.dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
  background: linear-gradient(135deg, #0d6efd, #0a58ca) !important;
  color: #ffffff !important;
  box-shadow: 0 6px 16px rgba(13, 110, 253, 0.25);
}

.dataTables_wrapper .dataTables_paginate .page-item.disabled .page-link {
  background: #f6f8fb !important;
  color: #9aa9bd !important;
  cursor: not-allowed;
  box-shadow: none;
}

.dataTables_wrapper .dataTables_paginate .page-link:focus {
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.2) !important;
}

/* DataTables page-length selector variants */
.dataTables_wrapper .dataTables_length {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.dataTables_wrapper .dataTables_length label {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: #1f3f66;
  font-weight: 600;
  margin: 0;
}

.dataTables_wrapper .dataTables_length select {
  min-width: 80px;
  height: 2.1rem;
  font-size: 0.875rem;
  font-weight: 600;
  transition: all 0.2s ease;
}

/* 1) Pill style (default) */
.dataTables_wrapper.dt-length-style-pill .dataTables_length select {
  border-radius: 999px !important;
  border: 1px solid #d8e3f2 !important;
  background: #ffffff !important;
  color: #1f3f66 !important;
  box-shadow: 0 1px 2px rgba(13, 110, 253, 0.08);
}

/* 2) Underline style */
.dataTables_wrapper.dt-length-style-underline .dataTables_length select {
  border: 0 !important;
  border-bottom: 2px solid #c5d8f4 !important;
  border-radius: 0 !important;
  background: transparent !important;
  color: #0a3a6b !important;
  padding-left: 0.25rem;
  padding-right: 1.25rem;
  box-shadow: none !important;
}

.dataTables_wrapper.dt-length-style-underline .dataTables_length select:focus {
  border-bottom-color: #0d6efd !important;
  box-shadow: none !important;
}

/* 3) Minimal style */
.dataTables_wrapper.dt-length-style-minimal .dataTables_length select {
  border: 1px solid transparent !important;
  border-radius: 6px !important;
  background: #f5f8fc !important;
  color: #304b69 !important;
  box-shadow: none !important;
}

.dataTables_wrapper.dt-length-style-minimal .dataTables_length select:hover {
  border-color: #d1def1 !important;
}

/* 4) Boxed style */
.dataTables_wrapper.dt-length-style-boxed .dataTables_length select {
  border: 1px solid #9ec2f1 !important;
  border-radius: 10px !important;
  background: linear-gradient(180deg, #ffffff 0%, #f2f7ff 100%) !important;
  color: #0f3f73 !important;
  box-shadow: 0 4px 10px rgba(13, 110, 253, 0.1) !important;
}

.dataTables_wrapper .dataTables_length select:focus {
  border-color: #0d6efd !important;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15) !important;
}
</style>

@yield('css')