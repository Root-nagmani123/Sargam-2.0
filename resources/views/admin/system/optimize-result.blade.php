@extends('admin.layouts.master')

@section('title', 'System Optimize - Sargam')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="System Optimize" />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="mb-3">Config, Route Cache &amp; Optimize</h4>
            @if($allOk)
                <div class="alert alert-success mb-3">Sab steps successfully complete. Config cache, route cache aur optimize sahi flow mein chale.</div>
            @else
                <div class="alert alert-warning mb-3">Kuch steps fail hue. Neeche details check karein.</div>
            @endif
            <ol class="list-group list-group-numbered">
                @foreach($steps as $step)
                    <li class="list-group-item d-flex justify-content-between align-items-start {{ $step['ok'] ? '' : 'list-group-item-danger' }}">
                        <div class="ms-2 me-auto">
                            <strong>{{ $step['name'] }}</strong>
                            <code class="d-block small mt-1">{{ $step['command'] }}</code>
                            @if(!empty($step['output']))
                                <pre class="small mb-0 mt-1 text-muted">{{ $step['output'] }}</pre>
                            @endif
                        </div>
                        <span class="badge {{ $step['ok'] ? 'bg-success' : 'bg-danger' }} rounded-pill">{{ $step['ok'] ? 'OK' : 'Fail' }}</span>
                    </li>
                @endforeach
            </ol>
            <div class="mt-3">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.dashboard') }}" class="btn btn-primary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>
@endsection
