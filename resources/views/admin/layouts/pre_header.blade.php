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
<link rel="stylesheet" href="{{asset('css/custom.css')}}">
<link rel="stylesheet" href="{{asset('admin_assets/css/dashboard-enhanced.css')}}">
<!-- CRITICAL: Force light mode CSS - must load AFTER Bootstrap CSS -->

<link rel="stylesheet" href="{{asset('admin_assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css')}}">
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

<style>
/* DataTables — compact ERP controls (Bootstrap 5 sm-equivalent: pagination-sm, form-select-sm, form-control-sm) */
.dataTables_wrapper .dataTables_paginate {
  margin-top: 0.5rem;
}

/* Match .pagination.pagination-sm */
.dataTables_wrapper .dataTables_paginate .pagination {
  --bs-pagination-padding-x: 0.5rem;
  --bs-pagination-padding-y: 0.25rem;
  --bs-pagination-font-size: 0.8125rem;
  --bs-pagination-border-radius: 0.25rem;
  gap: 0.2rem;
  align-items: center;
  margin-bottom: 0;
}

.dataTables_wrapper .dataTables_paginate .page-item {
  margin: 0 !important;
}

/* Tighter pill pagination (smaller than previous 2.1rem controls) */
.dataTables_wrapper .dataTables_paginate .page-link {
  min-width: 1.65rem;
  height: 1.65rem;
  padding: var(--bs-pagination-padding-y) var(--bs-pagination-padding-x);
  border-radius: 999px !important;
  border: 0 !important;
  background: #ffffff !important;
  color: #1f3f66 !important;
  font-weight: 600;
  font-size: var(--bs-pagination-font-size);
  line-height: 1.25;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  transition: background-color 0.15s ease, color 0.15s ease;
  box-shadow: 0 1px 2px rgba(13, 110, 253, 0.06);
}

.dataTables_wrapper .dataTables_paginate .page-link:hover {
  background: #eef5ff !important;
  color: #0d6efd !important;
}

.dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
  background: linear-gradient(135deg, #0d6efd, #0a58ca) !important;
  color: #ffffff !important;
  box-shadow: 0 2px 8px rgba(13, 110, 253, 0.2);
}

.dataTables_wrapper .dataTables_paginate .page-item.disabled .page-link {
  background: #f6f8fb !important;
  color: #9aa9bd !important;
  cursor: not-allowed;
  box-shadow: none;
  opacity: 0.85;
}

.dataTables_wrapper .dataTables_paginate .page-link:focus {
  box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.2) !important;
}

/* Row count selector — form-select-sm scale */
.dataTables_wrapper .dataTables_length {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.dataTables_wrapper .dataTables_length label {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  color: #1f3f66;
  font-weight: 600;
  font-size: 0.8125rem;
  margin: 0;
}

.dataTables_wrapper .dataTables_length select {
  min-width: 4.25rem;
  padding: 0.25rem 1.75rem 0.25rem 0.5rem;
  font-size: 0.8125rem;
  font-weight: 600;
  line-height: 1.5;
  min-height: calc(1.5em + 0.5rem + 2px);
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

/* Global search — form-control-sm scale */
.dataTables_wrapper .dataTables_filter {
  font-size: 0.8125rem;
}

.dataTables_wrapper .dataTables_filter label {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-weight: 600;
  color: #1f3f66;
  margin: 0;
  font-size: 0.8125rem;
}

.dataTables_wrapper .dataTables_filter input {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  font-size: 0.8125rem;
  line-height: 1.5;
  border-radius: var(--bs-border-radius-sm, 0.25rem);
  min-height: calc(1.5em + 0.5rem + 2px);
  margin-left: 0.35rem !important;
  max-width: 14rem;
}

/* Entries info — small muted text */
.dataTables_wrapper .dataTables_info {
  font-size: 0.8125rem;
  color: var(--bs-secondary-color);
  padding-top: 0.5rem;
  padding-bottom: 0.25rem;
}

/* Toolbar rows — less vertical slack */
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_paginate {
  margin-top: 0.35rem;
  margin-bottom: 0.35rem;
}

/* Sortable headers — tighter padding / arrows (Bootstrap table-sm-ish) */
.dataTables_wrapper table.dataTable > thead > tr > th,
.dataTables_wrapper table.dataTable > thead > tr > td {
  padding: 0.4rem 0.5rem;
  font-size: 0.8125rem;
  font-weight: 600;
}

.dataTables_wrapper table.dataTable thead > tr > th.sorting,
.dataTables_wrapper table.dataTable thead > tr > th.sorting_asc,
.dataTables_wrapper table.dataTable thead > tr > th.sorting_desc,
.dataTables_wrapper table.dataTable thead > tr > th.sorting_asc_disabled,
.dataTables_wrapper table.dataTable thead > tr > th.sorting_desc_disabled,
.dataTables_wrapper table.dataTable thead > tr > td.sorting,
.dataTables_wrapper table.dataTable thead > tr > td.sorting_asc,
.dataTables_wrapper table.dataTable thead > tr > td.sorting_desc,
.dataTables_wrapper table.dataTable thead > tr > td.sorting_asc_disabled,
.dataTables_wrapper table.dataTable thead > tr > td.sorting_desc_disabled {
  padding-right: 1.125rem !important;
}

.dataTables_wrapper table.dataTable thead > tr > th.sorting:before,
.dataTables_wrapper table.dataTable thead > tr > th.sorting:after,
.dataTables_wrapper table.dataTable thead > tr > th.sorting_asc:before,
.dataTables_wrapper table.dataTable thead > tr > th.sorting_asc:after,
.dataTables_wrapper table.dataTable thead > tr > th.sorting_desc:before,
.dataTables_wrapper table.dataTable thead > tr > th.sorting_desc:after,
.dataTables_wrapper table.dataTable thead > tr > th.sorting_asc_disabled:before,
.dataTables_wrapper table.dataTable thead > tr > th.sorting_asc_disabled:after,
.dataTables_wrapper table.dataTable thead > tr > th.sorting_desc_disabled:before,
.dataTables_wrapper table.dataTable thead > tr > th.sorting_desc_disabled:after,
.dataTables_wrapper table.dataTable thead > tr > td.sorting:before,
.dataTables_wrapper table.dataTable thead > tr > td.sorting:after,
.dataTables_wrapper table.dataTable thead > tr > td.sorting_asc:before,
.dataTables_wrapper table.dataTable thead > tr > td.sorting_asc:after,
.dataTables_wrapper table.dataTable thead > tr > td.sorting_desc:before,
.dataTables_wrapper table.dataTable thead > tr > td.sorting_desc:after,
.dataTables_wrapper table.dataTable thead > tr > td.sorting_asc_disabled:before,
.dataTables_wrapper table.dataTable thead > tr > td.sorting_asc_disabled:after,
.dataTables_wrapper table.dataTable thead > tr > td.sorting_desc_disabled:before,
.dataTables_wrapper table.dataTable thead > tr > td.sorting_desc_disabled:after {
  right: 0.35rem !important;
  font-size: 0.55em !important;
  line-height: 0.45rem !important;
}

.dataTables_wrapper table.dataTable > tbody > tr > th,
.dataTables_wrapper table.dataTable > tbody > tr > td {
  padding: 0.4rem 0.5rem;
  font-size: 0.8125rem;
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
  border-radius: 8px !important;
  background: linear-gradient(180deg, #ffffff 0%, #f2f7ff 100%) !important;
  color: #0f3f73 !important;
  box-shadow: 0 2px 6px rgba(13, 110, 253, 0.08) !important;
}

.dataTables_wrapper .dataTables_length select:focus {
  border-color: #0d6efd !important;
  box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.15) !important;
}
</style>

@yield('css')