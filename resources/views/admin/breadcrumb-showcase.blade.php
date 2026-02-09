@extends('admin.layouts.master')

@section('title', 'Breadcrumb Design Showcase')

@section('setup_content')
<div class="container-fluid">
    <div class="mb-4">
        <h2 class="h4 fw-bold text-primary mb-2">Breadcrumb Design Showcase</h2>
        <p class="text-body-secondary mb-0">Choose a variant for your pages. Use: <code>&lt;x-breadcrum title="Page Title" variant="glass" /&gt;</code></p>
    </div>

    @php
        $variants = [
            'glass' => ['Glassmorphism', 'Premium elevated card with subtle blur and gradients (default)'],
            'minimal' => ['Minimal', 'Clean flat design, no heavy decoration'],
            'pill' => ['Pill/Chip', 'Rounded pill-style segments with slash dividers'],
            'stepper' => ['Stepper', 'Step indicator style with connecting lines'],
            'underline' => ['Underline', 'Minimal with bottom border accent'],
            'compact' => ['Compact', 'Smaller, tighter spacing for dense layouts'],
        ];
    @endphp

    @foreach($variants as $key => $info)
    <div class="card border shadow-sm mb-4 rounded-3 overflow-hidden">
        <div class="card-header bg-body-tertiary py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="badge bg-primary me-2">{{ $key }}</span>
                <strong>{{ $info[0] }}</strong>
            </div>
            <code class="small text-body-secondary">variant="{{ $key }}"</code>
        </div>
        <div class="card-body p-4 bg-body">
            <p class="text-muted small mb-3">{{ $info[1] }}</p>
            <x-breadcrum title="Country List" :variant="$key" />
        </div>
    </div>
    @endforeach

    <div class="card border-primary mt-4">
        <div class="card-header bg-primary text-white">
            <strong>Usage</strong>
        </div>
        <div class="card-body">
            <p class="mb-2">Default (glass):</p>
            <pre class="bg-dark text-light p-3 rounded mb-3"><code>&lt;x-breadcrum title="Country List" /&gt;</code></pre>
            <p class="mb-2">With variant:</p>
            <pre class="bg-dark text-light p-3 rounded"><code>&lt;x-breadcrum title="Country List" variant="minimal" /&gt;</code></pre>
        </div>
    </div>
</div>
@endsection
