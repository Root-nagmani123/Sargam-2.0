@extends('fc.layouts.master')

@section('title', 'Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    @php

        $regStart = $pathPage->registration_start_date
            ? \Carbon\Carbon::parse($pathPage->registration_start_date)
            : null;
        $regEnd = $pathPage->registration_end_date ? \Carbon\Carbon::parse($pathPage->registration_end_date) : null;

        $exStart = $pathPage->exemption_start_date ? \Carbon\Carbon::parse($pathPage->exemption_start_date) : null;
        $exEnd = $pathPage->exemption_end_date ? \Carbon\Carbon::parse($pathPage->exemption_end_date) : null;

    @endphp

    <main id="content" class="flex-grow-1 py-4 py-md-5">
        <div class="container">
            <header class="text-center mb-4 mb-lg-5">
                <h1 class="h2 fw-bold text-primary mb-3">Choose Your Path</h1>
                <p class="fs-6 mb-0 col-lg-8 mx-auto">
                    Please select the appropriate option based on your current status.
                </p>
            </header>

            <div class="row row-cols-1 row-cols-lg-3 g-4 g-lg-4 mb-5">
                <!-- Register Card -->
                <div class="col d-flex">
                    <div class="card h-100 w-100 border-0 shadow-lg rounded-4">
                        <div class="card-body p-4 d-flex flex-column text-start">
                            <div
                                class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 bg-success-subtle align-self-start"
                                style="width: 3.5rem; height: 3.5rem;" aria-hidden="true">
                                <i class="bi bi-person-plus-fill fs-3 text-success"></i>
                            </div>
                            <h2 class="h5 fw-bold text-primary mb-3">Register for Foundation Course</h2>
                            <div class="path-card-content small flex-grow-1">
                                {!! $pathPage->register_course ?? '' !!}
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 pt-0 px-4 pb-4">
                            @if ($showRegistration)
                                <a href="{{ route('verify.authindex') }}" class="btn btn-success w-100 rounded-3 py-2 fw-semibold"
                                    style="background-color: #16a32a; border-color: #16a32a;">
                                    Start Registration
                                </a>
                            @else
                                <button type="button" class="btn btn-secondary w-100 rounded-3 py-2 fw-semibold" disabled>
                                    Registration Closed
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Exemption Card -->
                <div class="col d-flex">
                    <div class="card h-100 w-100 border-0 shadow-lg rounded-4">
                        <div class="card-body p-4 d-flex flex-column text-start">
                            <div
                                class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 bg-warning-subtle align-self-start"
                                style="width: 3.5rem; height: 3.5rem;" aria-hidden="true">
                                <i class="bi bi-file-earmark-text-fill fs-3 text-warning-emphasis"></i>
                            </div>
                            <h2 class="h5 fw-bold text-primary mb-3">Apply for Exemption</h2>
                            <div class="path-card-content small flex-grow-1">
                                {!! $pathPage->apply_exemption ?? '' !!}
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 pt-0 px-4 pb-4">
                            @if ($showExemption)
                                <a href="{{ route('fc.exemption_category.index') }}"
                                    class="btn btn-warning w-100 rounded-3 py-2 fw-semibold "
                                    style="background-color: #ea5803; border-color: #ea5803;">
                                    Apply for Exemption
                                </a>
                            @else
                                <button type="button" class="btn btn-secondary w-100 rounded-3 py-2 fw-semibold" disabled>
                                    Exemption Closed
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Login Card -->
                <div class="col d-flex">
                    <div class="card h-100 w-100 border-0 shadow-lg rounded-4">
                        <div class="card-body p-4 d-flex flex-column text-start">
                            <div
                                class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 bg-primary-subtle align-self-start"
                                style="width: 3.5rem; height: 3.5rem;" aria-hidden="true">
                                <i class="bi bi-box-arrow-in-right fs-3 text-primary"></i>
                            </div>
                            <h2 class="h5 fw-bold text-primary mb-3">Already Registered?</h2>
                            <div class="path-card-content small flex-grow-1">
                                {!! $pathPage->already_registered ?? '' !!}
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 pt-0 px-4 pb-4">
                            <a href="{{ route('fc.login') }}" class="btn btn-primary w-100 rounded-3 py-2 fw-semibold"
                                style="background-color: #2563eb; border-color: #2563eb;">
                                Login to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Guidelines -->
            <section class="mb-5" aria-labelledby="path-guidelines-heading">
                <h2 id="path-guidelines-heading" class="h4 fw-bold text-primary mb-3">
                    Guidelines for Registration &amp; Exemption
                </h2>
                <div class="card border border-primary border-opacity-25 bg-primary-subtle bg-opacity-10 rounded-4 shadow-sm">
                    <div class="card-body p-4 path-card-content">
                        {!! $pathPage->guidelines ?? '' !!}
                    </div>
                </div>
            </section>

            <!-- FAQ Section -->
            <section class="pt-2" aria-labelledby="path-faq-heading">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-md-between gap-3 mb-3">
                    <div>
                        <h2 id="path-faq-heading" class="h4 fw-bold text-primary mb-1">Frequently Asked Questions</h2>
                        <p class="-50 small mb-0">Find your query from this list of frequently asked questions</p>
                    </div>
                    <a href="{{ route('fc.faqs.all') }}"
                        class="btn btn-outline-primary btn-sm rounded-3 px-3 align-self-start align-self-md-center">
                        View All FAQs
                    </a>
                </div>

                @if ($pathPage->faqs && $pathPage->faqs->count())
                    <div class="accordion accordion-flush path-page-faq" id="faqAccordion">
                        @foreach ($pathPage->faqs as $index => $faq)
                            <div class="accordion-item border-start-0 border-end-0">
                                <h3 class="accordion-header m-0" id="heading{{ $index }}">
                                    <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }} shadow-none rounded-0"
                                        type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}"
                                        aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                                        aria-controls="collapse{{ $index }}">
                                        {{ $faq->header }}
                                    </button>
                                </h3>
                                <div id="collapse{{ $index }}"
                                    class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
                                    aria-labelledby="heading{{ $index }}" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body pt-0 small">
                                        {!! $faq->content !!}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="-50 small mb-0">No FAQs available at the moment.</p>
                @endif
            </section>
        </div>
    </main>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @if (session('warning'))
        <script>
            Swal.fire({
                title: 'Warning',
                text: '{{ session('warning') }}',
                icon: 'warning',
                confirmButtonColor: '#004a93',
                confirmButtonText: 'OK'
            });
        </script>
    @endif


    @if ($errors->any())
        <script>
            let errorMessages = '';
            @foreach ($errors->all() as $error)
                errorMessages += `{{ $error }}\n`;
            @endforeach

            Swal.fire({
                title: 'Validation Error',
                text: errorMessages.trim(),
                icon: 'error',
                confirmButtonColor: '#004a93',
                confirmButtonText: 'OK'
            });
        </script>
    @endif
@endpush
