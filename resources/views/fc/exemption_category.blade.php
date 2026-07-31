@extends('fc.layouts.master')

@section('title', 'Exemption Category - Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <div class="fc-page">
        <div class="container">
            <nav aria-label="Breadcrumb">
                <ol class="breadcrumb fc-breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('fc.choose.path') }}">Home</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Exemption Category</li>
                </ol>
            </nav>

            <header class="fc-page-head text-center">
                <h1 class="fc-page-title">Select Exemption Category</h1>
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-8">
                        <p class="fc-page-sub">
                            Choose the appropriate exemption category based on your circumstances. Each category has
                            specific requirements and documentation needs.
                        </p>
                    </div>
                </div>
            </header>

            @if ($exemptions->isEmpty())
                <div class="ds-empty-state">
                    <i class="bi bi-inbox fs-4 d-block mb-2" aria-hidden="true"></i>
                    No exemption categories are available at the moment.
                </div>
            @else
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    @foreach ($exemptions as $index => $item)
                        @php
                            // Accents cycle by position — the category list is CMS-driven,
                            // so the colour carries no meaning of its own.
                            $icons = ['bi-mortarboard-fill', 'bi-heart-pulse', 'bi-file-earmark-text-fill', 'bi-person-dash'];
                            $slot = $index % 4;
                        @endphp

                        <div class="col">
                            <div class="fc-choice fc-choice--i{{ $slot + 1 }}">
                                <div class="fc-choice-body text-center">
                                    <span class="fc-choice-icon mx-auto" aria-hidden="true">
                                        <i class="bi {{ $icons[$slot] }}"></i>
                                    </span>
                                    <h2 class="fc-choice-title">{{ $item->Exemption_name }}</h2>
                                    <div class="fc-choice-text fc-rte">
                                        {!! $item->description !!}
                                    </div>
                                </div>
                                <div class="fc-choice-foot">
                                    <a href="{{ route('fc.exemption_application', $item->pk) }}"
                                        class="btn btn-primary fc-btn-block">
                                        <i class="bi bi-file-earmark-check" aria-hidden="true"></i> Apply for Exemption
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- strip_tags guard: the CMS field can hold markup-only content (an empty
                 <p> or &nbsp;), which previously rendered as a bare blue panel. --}}
            @if (filled(trim(strip_tags($notice?->description ?? '', '<img>'))))
                <div class="fc-notice fc-rte mt-5">
                    {!! $notice->description !!}
                </div>
            @endif
        </div>
    </div>
@endsection
