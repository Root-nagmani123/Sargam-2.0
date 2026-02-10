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
<!-- Bootstrap 5.3.6 (latest stable used across app) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous">
<!-- Core Css -->
<link rel="stylesheet" href="{{asset('admin_assets/css/styles.css')}}">
<link rel="stylesheet" href="{{asset('css/custom.css')}}">
<link rel="stylesheet" href="{{asset('admin_assets/css/dashboard-enhanced.css')}}">
<!-- CRITICAL: Force light mode CSS - must load AFTER Bootstrap CSS -->

<!-- DataTables 1.13.8 + Bootstrap 5 (latest) -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" crossorigin="anonymous">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<link rel="stylesheet" href="{{asset('admin_assets/libs/select2/dist/css/select2.min.css')}}">
<link rel="stylesheet" href="{{asset('admin_assets/css/plugins/datatable.min.css')}}">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" />
<!-- Breadcrumb Component CSS -->
<link rel="stylesheet" href="{{asset('css/breadcrumb.css')}}">
<!-- Sidebar Menu Enhanced CSS -->
<link rel="stylesheet" href="{{asset('css/sidebar-menu-enhanced.css')}}">
<!-- Admin responsive helpers (mobile/tablet only) -->
<link rel="stylesheet" href="{{asset('css/admin-responsive.css')}}">
<!-- Admin UI Enhancements - Bootstrap 5.3 -->
<link rel="stylesheet" href="{{asset('css/admin-ui-enhancements.css')}}">
<style>
.material-symbols-rounded {
  font-variation-settings:
  'FILL' 0,
  'wght' 400,
  'GRAD' 0,
  'opsz' 24
}

/* Color themes (Blue_Theme, Aqua_Theme, etc.) work in both light and dark mode */
/* No overrides - Bootstrap and styles.css handle both modes */
</style>

@yield('css')