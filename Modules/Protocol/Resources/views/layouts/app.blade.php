<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Protocol') | @yield('app_name', config('app.name'))</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('modules/protocol/css/protocol.css') }}">
@stack('styles')
</head>
<body>

{{--
    NOTE FOR INTEGRATION:
    This file is a complete, standalone layout so the module can be
    previewed/run on its own. When wiring into your existing software,
    either:
      (a) delete this file and change every "protocol::layouts.app" extend
          below to your host app's own master layout name, or
      (b) keep this file and simply @include your existing navbar/sidebar
          partials inside the blocks below.
--}}

<div class="brand-strip"></div>

@include('protocol::partials.sidebar')

<div id="protocol-main">
  <div id="protocol-topbar">
    <div>
      <h5 class="mb-0">@yield('page_title', 'Protocol')</h5>
      <div class="subtitle">@yield('page_subtitle', '')</div>
    </div>
    <div class="d-flex align-items-center gap-3">
      @auth
        <span class="text-muted small d-none d-md-inline">
          Logged in as <strong>{{ auth()->user()->name }}</strong>
        </span>
      @endauth
    </div>
  </div>

  <div class="protocol-content">
    @if (session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if ($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @yield('content')
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
