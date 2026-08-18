@extends('fc.layouts.master')

@section('title', 'Select Exemption Category - Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <main id="content" class="flex-grow-1 py-4 py-md-5">
        <div class="container" style="max-width: 960px;">

            <header class="text-center mb-4 mb-md-5">
                <h1 class="h3 fw-bold text-primary mb-2">{{ $exemption->Exemption_name }}</h1>
                <p class="text-muted mb-0">
                    You initially registered for the Foundation Course. Please select the exemption
                    category you now wish to apply for — you will be taken to that category's application form.
                </p>
            </header>

            @if ($otherCategories->isEmpty())
                <div class="alert alert-warning text-center" role="alert">
                    No exemption categories are currently available. Please contact the Academy office.
                </div>
            @else
                <div class="row row-cols-1 row-cols-md-3 g-4 g-md-4 justify-content-center">
                    @foreach ($otherCategories as $index => $item)
                        @php
                            $themes = [
                                ['circle' => 'bg-primary-subtle', 'icon' => 'text-primary',          'btn' => 'btn-primary', 'bi' => 'bi-mortarboard-fill'],
                                ['circle' => 'bg-success-subtle', 'icon' => 'text-success',          'btn' => 'btn-success', 'bi' => 'bi-heart-pulse'],
                                ['circle' => 'bg-danger-subtle',  'icon' => 'text-danger',           'btn' => 'btn-danger',  'bi' => 'bi-file-earmark-text-fill'],
                                ['circle' => 'bg-warning-subtle', 'icon' => 'text-warning-emphasis', 'btn' => 'btn-warning', 'bi' => 'bi-person-dash'],
                            ];
                            $theme = $themes[$index % count($themes)];
                        @endphp

                        <div class="col d-flex">
                            <div class="card h-100 w-100 border shadow-sm rounded-4">
                                <div class="card-body d-flex flex-column text-center px-4 pt-4 pb-0">
                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3 {{ $theme['circle'] }}"
                                        style="width: 4.5rem; height: 4.5rem;" aria-hidden="true">
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
            @endif

            <div class="text-center mt-4 mt-md-5">
                <a href="{{ route('fc.exemption_category.index', request()->only('form')) }}"
                    class="btn btn-outline-secondary rounded-3 px-4">
                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Back to all categories
                </a>
            </div>

        </div>
    </main>
@endsection
