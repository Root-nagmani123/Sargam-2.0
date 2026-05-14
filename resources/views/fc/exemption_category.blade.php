@extends('fc.layouts.master')

@section('title', 'Exemption Category - Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <main class="flex-grow-1">
        <div class="container py-4 py-md-5">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active" style="font-size: 20px;">Home</li>
                    <li class="breadcrumb-item" aria-current="page" style="font-size: 20px;">Exemption Category</li>
                </ol>
            </nav>

            <header class="text-center mb-4 mb-lg-5">
                <h1 class="h2 fw-bold text-primary mb-3">Select Exemption Category</h1>
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-8">
                        <p class="text-secondary small mb-0">
                            Choose the appropriate exemption category based on your circumstances. Each category has specific
                            requirements and documentation needs.
                        </p>
                    </div>
                </div>
            </header>

            <div class="row row-cols-1 row-cols-md-2 g-4 g-md-4">
                @foreach ($exemptions as $index => $item)
                    @php
                        $themes = [
                            [
                                'circle' => 'bg-primary-subtle',
                                'icon' => 'text-primary',
                                'btn' => 'btn-primary',
                                'bi' => 'bi-mortarboard-fill',
                            ],
                            [
                                'circle' => 'bg-success-subtle',
                                'icon' => 'text-success',
                                'btn' => 'btn-success',
                                'bi' => 'bi-file-earmark-text-fill',
                            ],
                            [
                                'circle' => 'bg-danger-subtle',
                                'icon' => 'text-danger',
                                'btn' => 'btn-danger',
                                'bi' => 'bi-heart-pulse',
                            ],
                            [
                                'circle' => 'bg-warning-subtle',
                                'icon' => 'text-warning-emphasis',
                                'btn' => 'btn-warning',
                                'bi' => 'bi-person-dash',
                            ],
                        ];
                        $theme = $themes[$index % count($themes)];
                    @endphp

                    <div class="col d-flex">
                        <div class="card h-100 w-100 border shadow-sm rounded-4">
                            <div class="card-body d-flex flex-column text-center px-4 pt-4 pb-0">
                                <div
                                    class="rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3 {{ $theme['circle'] }}"
                                    style="width: 4.5rem; height: 4.5rem;"
                                    aria-hidden="true">
                                    <i class="bi {{ $theme['bi'] }} fs-2 {{ $theme['icon'] }}"></i>
                                </div>
                                <h2 class="h5 fw-bold text-primary mb-2">{{ $item->Exemption_name }}</h2>
                                <div class="text-secondary small mb-4 flex-grow-1 exemption-card-desc">
                                    {!! $item->description !!}
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0 pt-0 px-4 pb-4">
                                <a href="{{ route('fc.exemption_application', $item->pk) }}"
                                    class="btn {{ $theme['btn'] }} w-100 rounded-3">
                                    Apply for Exemption
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if (!empty($notice?->description))
                <div class="mt-5 p-4 p-md-5 rounded-4 border border-primary border-opacity-25 bg-primary-subtle">
                    <div class="text-primary small">
                        {!! $notice->description !!}
                    </div>
                </div>
            @endif
        </div>
    </main>
@endsection
