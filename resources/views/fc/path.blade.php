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
    <main style="flex: 1;">
        <div class="container mt-5">
            <div class="text-center">
                <h4 style="color: #004a93; font-size: 30px; font-weight: 700;">Choose Your Path</h4>
                <p class="text-muted" style="font-size: 20px;">
                    Please select the appropriate option based on your current status.
                </p>
            </div>

            <div class="container my-5">
                <div class="row g-4 mt-5">
                    <!-- Register Card -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="icon-circle mb-3" style="background-color: #dcfce7;">
                                    <i class="material-icons menu-icon fs-3"
                                        style="color: #16a32a; transform: rotateY(180deg);">person_add</i>
                                </div>
                                <h5 class="fw-bold text-center" style="color: #004a93;">Register for Foundation Course</h5>

                                {!! $pathPage->register_course ?? '' !!}
                            </div>

                            <div class="card-footer bg-white border-top-0">
                                @if (\Carbon\Carbon::today()->between($regStart, $regEnd))
                                    <a href="{{ route('verify.authindex') }}" class="btn btn-success custom-btn w-100"
                                        style="background-color: #16a32a; border-color: #16a32a;">
                                        Start Registration
                                    </a>
                                @else
                                    <button class="btn btn-secondary w-100" disabled>Registration Closed</button>
                                @endif
                            </div>

                        </div>
                    </div>

                    <!-- Exemption Card -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="icon-circle mb-3" style="background-color: #fff4e5;">
                                    <i class="material-icons menu-icon fs-3" style="color: #ea5803;">article</i>
                                </div>
                                <h5 class="fw-bold text-center" style="color: #004a93;">Apply for Exemption</h5>

                                {!! $pathPage->apply_exemption ?? '' !!}

                            </div>
                            <div class="card-footer bg-white border-top-0">
                                @if (\Carbon\Carbon::today()->between($exStart, $exEnd))
                                    <a href="{{ route('fc.exemption_category.index') }}"
                                        class="btn btn-warning custom-btn w-100 text-white"
                                        style="background-color: #ea5803; border-color: #ea5803;">
                                        Apply for Exemption
                                    </a>
                                @else
                                    <button class="btn btn-secondary custom-btn w-100" disabled>
                                        Exemption Closed
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Login Card -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="icon-circle mb-3" style="background-color: #e5f2ff;">
                                    <i class="material-icons menu-icon fs-3" style="color: #2563eb;">login</i>
                                </div>
                                <h5 class="fw-bold text-center" style="color: #004a93;">Already Registered?</h5>

                                {!! $pathPage->already_registered ?? '' !!}
                            </div>
                            <div class="card-footer bg-white border-top-0">
                                <a href="{{ route('fc.login') }}" class="btn btn-primary custom-btn w-100"
                                    style="background-color: #2563eb; border-color: #2563eb;">
                                    Login to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Guidelines Card -->
                    <div class="col-md-12 md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="icon-circle mb-3" style="background-color: #e6fffa;">
                                    <i class="material-icons menu-icon fs-3" style="color: #14b8a6;">info</i>
                                </div>
                                {{-- <h5 class="fw-bold text-center" style="color: #004a93;">Guidelines</h5> --}}
                                {!! $pathPage->guidelines ?? '' !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- FAQ Section -->
            <div class="row g-4 mt-5">
                <div class="col-9">
                    <h4 style="color: #004a93; font-size: 30px; font-weight: 700;">Frequently Asked Questions</h4>
                    <span class="text-muted">Find your query from this list of frequently asked questions</span>
                </div>
                <div class="col-3 text-end">
                    <a href="{{ route('fc.faqs.all') }}" class="btn btn-outline-primary">View All FAQs</a>
                </div>
            </div>

            @if ($pathPage->faqs && $pathPage->faqs->count())
                <div class="mt-4">
                    <div class="accordion" id="faqAccordion">
                        @foreach ($pathPage->faqs as $index => $faq)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading{{ $index }}">
                                    <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}"
                                        aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                                        aria-controls="collapse{{ $index }}">
                                        {{ $faq->header }}
                                    </button>
                                </h2>
                                <div id="collapse{{ $index }}"
                                    class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
                                    aria-labelledby="heading{{ $index }}" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        {!! $faq->content !!}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="mt-3 text-muted">No FAQs available at the moment.</p>
            @endif
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
