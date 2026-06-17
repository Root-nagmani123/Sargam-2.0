@extends('admin.layouts.master')

@section('title', 'Navigation Error')

@section('content')
<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-md-5 text-center">
                    @php
                        $messages = [
                            'missing_path' => 'Path is not added to this page.',
                            'invalid_route' => 'Incorrect route/path configured.',
                            'not_found' => 'Requested page not found.',
                        ];
                        $message = $messages[$reason ?? 'not_found'] ?? $messages['not_found'];
                    @endphp
                    <i class="material-icons material-symbols-rounded text-warning mb-3" style="font-size: 4rem;">error_outline</i>
                    <h1 class="h3 mb-3">{{ $message }}</h1>
                    @if (!empty($menu_id))
                        <p class="text-muted small mb-4">Menu reference ID: {{ $menu_id }}</p>
                    @endif
                    <p class="text-body-secondary mb-4">
                        The link may be misconfigured in the menu setup. Contact your administrator or return to the dashboard.
                    </p>
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
                        <button type="button" class="btn btn-outline-secondary" onclick="history.back()">Go Back</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
