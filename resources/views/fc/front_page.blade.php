@extends('fc.layouts.master')

@section('title', 'Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <style>
        /* Foundation course landing — page-scoped visual hierarchy + wider layout. */
        .fc-foundation.academy-box {
            max-width: 1320px;
        }
        .fc-foundation .fc-academy-name {
            font-size: clamp(1.5rem, 2.4vw, 2.1rem);
            color: #af2910;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: .25rem;
        }
        .fc-foundation .fc-academy-place {
            font-size: 1.05rem;
            color: #6c757d;
            margin-bottom: 0;
        }
        .fc-foundation .fc-eyebrow {
            font-size: clamp(1.25rem, 1.9vw, 1.6rem);
            color: #004a93;
            font-weight: 700;
            letter-spacing: .03em;
            margin-bottom: .15rem;
        }
        .fc-foundation .fc-course-title {
            font-size: clamp(1.15rem, 1.7vw, 1.5rem);
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0;
        }
        .fc-foundation .fc-info-card {
            background: #f8faff;
            border: 1px solid #e3ebf7 !important;
            transition: box-shadow .2s ease, transform .2s ease;
        }
        .fc-foundation .fc-info-card:hover {
            box-shadow: 0 .4rem 1rem rgba(0, 74, 147, .12);
            transform: translateY(-2px);
        }
        .fc-foundation .fc-info-label {
            font-size: 1.1rem;
            font-weight: 700;
            color: #004a93;
            margin-bottom: .2rem;
        }
        .fc-foundation .fc-info-value {
            font-size: 1.02rem;
            color: #475569;
        }
        .fc-foundation sup {
            font-size: .62em;
        }
    </style>

    <!-- Main Content Box -->
    <div class="academy-box fc-foundation">
        <div class="text-center mb-2">
            <img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="LBSNAA Logo" height="80">
            <h1 class="fc-academy-name mt-3">Lal Bahadur Shastri National Academy of Administration</h1>
            <p class="fc-academy-place">Mussoorie, Uttarakhand</p>
        </div>
        <hr>
        <div class="container-fluid px-0 px-md-2">
            <div class="text-center mb-4">
                <h2 class="fc-eyebrow mt-3">Congratulations</h2>
                <h3 class="fc-course-title">
                    {!! isset($data) && ! empty($data->course_title) ? $data->course_title : 'Foundation Course' !!}
                </h3>
            </div>

            @if (! empty($programmeIntentLabel))
                <div class="alert alert-info border-0 shadow-sm py-2 px-3 small mb-4 mx-auto" style="max-width: 42rem;" role="status">
                    <span class="fw-semibold" style="color: #084298;">Programme link recognised.</span>
                    After you complete sign-in, you will continue to
                    <span class="fw-semibold">{{ $programmeIntentLabel }}</span>.
                </div>
            @endif

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

            <div class="row g-3 justify-content-center">
                <!-- Course Duration -->
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-center fc-info-card border rounded-4 p-3 h-100">
                        <i class="material-icons menu-icon me-3 fs-3" style="color: #004a93;">calendar_today</i>
                        <div class="text-start">
                            <h4 class="fc-info-label">Course Duration</h4>
                            <div class="fc-info-value">
                                {!! $supOrdinalDate($pathPage?->course_start_date) !!}
                                –
                                {!! $supOrdinalDate($pathPage?->course_end_date) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Online Registration -->
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-center fc-info-card border rounded-4 p-3 h-100">
                        <i class="material-icons menu-icon me-3 fs-3" style="color: #004a93;">location_on</i>
                        <div class="text-start">
                            <h4 class="fc-info-label">Online Registration</h4>
                            <div class="fc-info-value">
                                {!! $supOrdinalDate($pathPage?->registration_start_date) !!}
                                –
                                {!! $supOrdinalDate($pathPage?->registration_end_date) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Online Exemption -->
                {{-- <div class="col-md-6">
                    <div class="d-flex align-items-center border rounded-4 p-3 h-100">
                        <i class="material-icons menu-icon me-3 fs-3" style="color: #004a93;">group</i>
                        <div>
                            <h6 class="mb-1 fw-semibold">Online Exemption</h6>
                            <div class="text-muted">Available after registration</div>
                        </div>
                    </div>
                </div> --}}

                <!-- Laptop Requirement -->
                {{-- <div class="col-md-6">
                    <div class="d-flex align-items-center border rounded-4 p-3 h-100">
                        <i class="material-icons menu-icon me-3 fs-3" style="color: #004a93;">laptop_windows</i>
                        <div>
                            <h6 class="mb-1 fw-semibold">Laptop Requirement</h6>
                            <div class="text-muted">Mandatory for all participants</div>
                        </div>
                    </div>
                </div> --}}
            </div>
        </div>

        <!-- Important Updates -->
        <div class="notice-box mt-4">
            @if (isset($data) && !empty($data->important_updates))
                <div class="important-updates-content">
                    {!! $data->important_updates !!}
                </div>
            @endif
        </div>

        {{-- <div class="text-center mt-4">
            <a href="{{ route('fc.choose.path') }}" class="btn btn-primary px-4"
                style="background-color: #004a93; border: #004a93;">
                Click Here to Proceed
            </a>
        </div> --}}
        {{-- <hr> --}}
         <!-- Signature Block -->
        <div class="signature mt-5 text-end">
            @if (isset($data) && !empty($data->coordinator_signature))
                <img src="{{ asset('storage/' . $data->coordinator_signature) }}" alt="Coordinator Signature" height="50"
                    class="mb-2">
            @endif


            <p class="text-muted mb-0">
                {{ isset($data) ? $data->coordinator_name ?? 'Coordinator Name' : 'Coordinator Name' }}<br>
                {{ isset($data) ? $data->coordinator_designation ?? 'Coordinator Designation' : 'Coordinator Designation' }}<br>
                {!! isset($data) && ! empty($data->coordinator_info) ? $data->coordinator_info : 'Additional Info' !!}
            </p>
        </div>
         <div class="text-center mt-4">
            <a href="{{ route('fc.choose.path', $intentQuery ?? []) }}" class="btn btn-primary px-4"
                style="background-color: #004a93; border: #004a93;">
                Click Here to Proceed
            </a>
        </div>

    </div>
@endsection
