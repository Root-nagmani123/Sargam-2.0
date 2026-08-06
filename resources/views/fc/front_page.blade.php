@extends('fc.layouts.master')

@section('title', 'Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    @php
        // Display-only: raise the ordinal suffix (st/nd/rd/th) in formatted dates,
        // e.g. "June 27th, 2026" -> "June 27<sup>th</sup>, 2026". Does not change stored data.
        $supOrdinalDate = function ($date) {
            if (empty($date)) {
                return '—';
            }
            return preg_replace('/(\d+)(st|nd|rd|th)\b/i', '$1<sup>$2</sup>', $date->format('F jS, Y'));
        };
    @endphp

    <div class="fc-page fc-front-page">
        <div class="container">
            <div class="fc-card fc-card--tricolor">
                <div class="fc-card-body">

                    <!-- Academy masthead -->
                    <header class="fc-masthead">
                        <img src="{{ asset('images/lbsnaa_logo.jpg') }}"
                            alt="Lal Bahadur Shastri National Academy of Administration">
                        <h1 class="fc-masthead-name">Lal Bahadur Shastri National Academy of Administration</h1>
                        <p class="fc-masthead-place">Mussoorie, Uttarakhand</p>
                    </header>

                    <hr class="my-4">

                    <!-- Key dates -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="fc-info-tile">
                                <span class="fc-info-icon" aria-hidden="true">
                                    <i class="bi bi-calendar3"></i>
                                </span>
                                <div>
                                    <h2 class="fc-info-label">Course Duration</h2>
                                    <div class="fc-info-value">
                                        {!! $supOrdinalDate($pathPage?->course_start_date) !!}
                                        &ndash;
                                        {!! $supOrdinalDate($pathPage?->course_end_date) !!}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="fc-info-tile">
                                <span class="fc-info-icon" aria-hidden="true">
                                    <i class="bi bi-pencil-square"></i>
                                </span>
                                <div>
                                    <h2 class="fc-info-label">Online Registration</h2>
                                    <div class="fc-info-value">
                                        {!! $supOrdinalDate($pathPage?->registration_start_date) !!}
                                        &ndash;
                                        {!! $supOrdinalDate($pathPage?->registration_end_date) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Important updates -->
                    @if (isset($data) && !empty($data->important_updates))
                        <div class="fc-notice fc-rte mt-4">
                            {!! $data->important_updates !!}
                        </div>
                    @endif

                    <!-- Coordinator sign-off -->
                    <div class="fc-signature mt-5">
                        @if (isset($data) && !empty($data->coordinator_signature))
                            <img src="{{ asset('storage/' . $data->coordinator_signature) }}"
                                alt="Coordinator signature">
                        @endif

                        <p>
                            {{ isset($data) ? $data->coordinator_name ?? 'Coordinator Name' : 'Coordinator Name' }}<br>
                            {{ isset($data) ? $data->coordinator_designation ?? 'Coordinator Designation' : 'Coordinator Designation' }}<br>
                            {!! isset($data) && !empty($data->coordinator_info) ? $data->coordinator_info : 'Additional Info' !!}
                        </p>
                    </div>

                    <div class="text-center mt-4">
                        <a href="{{ route('fc.choose.path', $intentQuery ?? []) }}" class="btn btn-primary px-4">
                            <i class="bi bi-arrow-right-circle" aria-hidden="true"></i>
                            Click Here to Proceed
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    {{-- Page-scoped: only the width of this single-column letter page, which is
         wider than the default container. Everything else comes from the .fc-*
         component layer in sargam-app.css (docs/design.md rules 1 and 4). --}}
    <style>
        .fc-front-page .container { max-width: 1120px; }
    </style>
@endpush
