<!-- Required meta tags -->
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="app-base-url" content="{{ url('') }}">
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
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<link rel="stylesheet" href="{{asset('admin_assets/libs/select2/dist/css/select2.min.css')}}">
<link rel="stylesheet" href="{{asset('admin_assets/css/plugins/datatable.min.css')}}">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
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

/* Override any dark mode media queries - force light mode */
@media (prefers-color-scheme: dark) {
  html,
  html[data-bs-theme="light"],
  html[data-bs-theme="dark"] {
    color-scheme: light !important;
    --bs-body-bg: #fff !important;
    --bs-body-color: #212529 !important;
  }
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

/* CRITICAL: Override Bootstrap's dark mode media query completely */
@media (prefers-color-scheme: dark) {
  *,
  :root,
  html,
  html[data-bs-theme],
  html[data-bs-theme="light"],
  html[data-bs-theme="dark"],
  body,
  body[data-bs-theme],
  body[data-bs-theme="light"],
  body[data-bs-theme="dark"],
  .card,
  .modal,
  .dropdown-menu,
  .popover,
  .tooltip,
  .offcanvas,
  .navbar,
  .nav,
  .btn,
  .form-control,
  .form-select,
  .table,
  .alert,
  .badge,
  .list-group,
  .pagination {
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
    background-color: #fff !important;
    color: #212529 !important;
  }
}
</style>

@yield('css')