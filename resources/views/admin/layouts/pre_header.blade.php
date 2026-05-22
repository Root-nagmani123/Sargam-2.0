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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<x-fonts-sargam />
<!-- Core Css -->
<link rel="stylesheet" href="{{asset('admin_assets/css/styles.css')}}">
<link rel="stylesheet" href="{{ asset('css/custom.css') }}?v={{ @filemtime(public_path('css/custom.css')) ?: time() }}">
<link rel="stylesheet" href="{{asset('admin_assets/css/dashboard-enhanced.css')}}">
<!-- CRITICAL: Force light mode CSS - must load AFTER Bootstrap CSS -->

<link rel="stylesheet" href="{{asset('admin_assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css')}}">
<link rel="stylesheet" href="{{ asset('css/datatables-enhanced.css') }}?v={{ @filemtime(public_path('css/datatables-enhanced.css')) ?: time() }}">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="{{ asset('admin_assets/css/material-icons-local.css') }}" />
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

@yield('css')