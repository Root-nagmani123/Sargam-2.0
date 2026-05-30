@extends('fc.layouts.master')

@section('title', 'Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <!-- Main Content Box -->
    <div class="academy-box">
        <div class="text-center mb-2">
            <img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="LBSNAA Logo" height="80">
            <h4 class="mt-3 fw-bold" style="color: #af2910; font-size: 20px;">Lal Bahadur Shastri National Academy of
                Administration</h4>
            <p class="text-muted" style="font-size: 16px;">Mussoorie, Uttarakhand</p>
        </div>
        <hr>
        <div class="container">
            <div class="text-center mb-4">
                <h5 class="text-primary fw-bold mt-4 mb-2">
                    <a href="#" class="text-decoration-none"
                        style="color: #004a93; font-size: 20px;">Congratulations</a>
                </h5>
                <h4 class="fw-semibold mt-2" style="font-size: 20px;">
                    {{ isset($data) ? $data->course_title ?? 'Foundation Course' : 'Foundation Course' }}
                </h4>
            </div>

            @if (! empty($programmeIntentLabel))
                <div class="alert alert-info border-0 shadow-sm py-2 px-3 small mb-4 mx-auto" style="max-width: 42rem;" role="status">
                    <span class="fw-semibold" style="color: #084298;">Programme link recognised.</span>
                    After you complete sign-in, you will continue to
                    <span class="fw-semibold">{{ $programmeIntentLabel }}</span>.
                </div>
            @endif

            <div class="row g-3">
                <!-- Course Duration -->
                <div class="col-md-6">
                    <div class="d-flex align-items-center border rounded-4 p-3 h-100">
                        <i class="material-icons menu-icon me-3 fs-3" style="color: #004a93;">calendar_today</i>
                        <div>
                            <h6 class="mb-1 fw-semibold">Course Duration</h6>
                            <div class="text-muted">
                                {{ $pathPage?->course_start_date?->format('F jS, Y') ?? '—' }}
                                –
                                {{ $pathPage?->course_end_date?->format('F jS, Y') ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Online Registration -->
                <div class="col-md-6">
                    <div class="d-flex align-items-center border rounded-4 p-3 h-100">
                        <i class="material-icons menu-icon me-3 fs-3" style="color: #004a93;">location_on</i>
                        <div>
                            <h6 class="mb-1 fw-semibold">Online Registration</h6>
                            <div class="text-muted">
                                {{ $pathPage?->registration_start_date?->format('F jS, Y') ?? '—' }}
                                –
                                {{ $pathPage?->registration_end_date?->format('F jS, Y') ?? '—' }}
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
                {{ isset($data) ? $data->coordinator_info ?? 'Additional Info' : 'Additional Info' }}
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
