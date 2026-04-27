<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Activity Tracker')</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        .tbl { font-family: Arial,sans-serif; border-collapse:collapse; width:90%; }
        .tbl td,.tbl th { border:1px solid #ddd; padding:8px; }
        .tbl tr:nth-child(even){ background:#f2f2f2; }
        .tbl tr:hover { background:#ddd; }
        .tbl th { padding-top:12px; padding-bottom:12px; text-align:left; background:#04AA6D; color:#fff; }
        .cell-done  { }
        .cell-empty { background-color:red; }
        .alert-flash { display:none; color:red; font-weight:bold; }
    </style>
    @stack('styles')
</head>
<body>
<div class="wrapper d-flex align-items-stretch">
    {{-- Sidebar --}}
    <nav id="sidebar">
        <div class="custom-menu">
            <button type="button" id="sidebarCollapse" class="btn btn-primary">
                <i class="fa fa-bars"></i>
            </button>
        </div>
        <div class="p-4">
            <h1>
                <a href="{{ route('home') }}" class="logo">
                    {{ auth()->user()->department ?? 'ADMIN' }}
                    <span>{{ auth()->user()->name }} ({{ auth()->user()->username }})</span>
                </a>
            </h1>
            <ul class="list-unstyled components mb-5">
                <li class="{{ request()->routeIs('home') ? 'active':'' }}">
                    <a href="{{ route('home') }}"><span class="fa fa-home mr-3"></span>Home</a>
                </li>
                @php $dept = strtolower(auth()->user()->department ?? ''); @endphp

                @if($dept === 'medical')
                    <li><a href="{{ route('medical.index') }}"><span class="fa fa-briefcase mr-3"></span>Report</a></li>
                    <li><a href="{{ route('status.medical') }}"><span class="fa fa-briefcase mr-3"></span>Show Status</a></li>

                @elseif($dept === 'administration')
                    <li><a href="{{ route('status.admin') }}"><span class="fa fa-briefcase mr-3"></span>Show Status</a></li>
                    <li><a href="{{ route('reports.service-wise') }}"><span class="fa fa-briefcase mr-3"></span>Service Wise Status</a></li>

                @elseif($dept === 'security')
                    <li><a href="{{ route('status.security') }}"><span class="fa fa-briefcase mr-3"></span>Show Status</a></li>

                @elseif($dept === 'it')
                    <li><a href="{{ route('status.it') }}"><span class="fa fa-briefcase mr-3"></span>Show Status</a></li>

                @elseif(in_array($dept, ['trg','training']))
                    <li><a href="{{ route('status.training') }}"><span class="fa fa-briefcase mr-3"></span>Show Status</a></li>

                @elseif(in_array($dept, ['mess','shop']))
                    <li><a href="{{ route('status.shop') }}"><span class="fa fa-briefcase mr-3"></span>Show Status</a></li>
                @endif
            </ul>
        </div>
    </nav>

    {{-- Main content --}}
    <div id="content" class="p-4 p-md-5 pt-5">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif
        @yield('content')
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/main.js') }}"></script>
<script>
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    $(document).ready(function(){ $('select').select2(); });
</script>
@stack('scripts')
</body>
</html>
