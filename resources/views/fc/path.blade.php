@extends('fc.layouts.master')

@section('title', 'Foundation Course | Lal Bahadur Shastri National Academy of Administration')

@section('content')
    <div class="fc-page">
        <div class="container">

            <!-- Guidelines -->
            <section class="mb-5" aria-labelledby="path-guidelines-heading">
                <h2 id="path-guidelines-heading" class="fc-section-title">
                    Guidelines for Registration &amp; Exemption
                </h2>
                <div class="fc-card fc-card--tricolor">
                    <div class="fc-card-body fc-rte">
                        {!! $pathPage->guidelines ?? '' !!}
                    </div>
                </div>
            </section>

            <!-- Choice of path -->
            <section class="mb-5" aria-labelledby="path-choice-heading">
                <header class="fc-page-head text-center">
                    <h1 id="path-choice-heading" class="fc-page-title">How Would You Like to Proceed?</h1>
                    <p class="fc-page-sub">Please select the appropriate option based on your current status.</p>
                </header>

                <div class="row row-cols-1 row-cols-lg-3 g-4">
                    <!-- Register -->
                    <div class="col">
                        <div class="fc-choice fc-choice--register">
                            <div class="fc-choice-body">
                                <span class="fc-choice-icon" aria-hidden="true">
                                    <i class="bi bi-person-plus-fill"></i>
                                </span>
                                <h2 class="fc-choice-title">Register for Foundation Course</h2>
                                <div class="fc-choice-text fc-rte">
                                    {!! $pathPage->register_course ?? '' !!}
                                </div>
                            </div>
                            <div class="fc-choice-foot">
                                @if ($showRegistration)
                                    <a href="{{ route('verify.authindex', $intentQuery ?? []) }}"
                                        class="btn btn-success fc-btn-block">
                                        <i class="bi bi-arrow-right-circle" aria-hidden="true"></i> Start Registration
                                    </a>
                                @else
                                    <button type="button" class="btn btn-secondary fc-btn-block" disabled>
                                        Registration Closed
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Exemption -->
                    <div class="col">
                        <div class="fc-choice fc-choice--exempt">
                            <div class="fc-choice-body">
                                <span class="fc-choice-icon" aria-hidden="true">
                                    <i class="bi bi-file-earmark-text-fill"></i>
                                </span>
                                <h2 class="fc-choice-title">Apply for Exemption</h2>
                                <div class="fc-choice-text fc-rte">
                                    {!! $pathPage->apply_exemption ?? '' !!}
                                </div>
                            </div>
                            <div class="fc-choice-foot">
                                @if ($showExemption)
                                    <a href="{{ route('fc.exemption_category.index', $intentQuery ?? []) }}"
                                        class="btn btn-warning fc-btn-block">
                                        <i class="bi bi-file-earmark-check" aria-hidden="true"></i> Apply for Exemption
                                    </a>
                                @else
                                    <button type="button" class="btn btn-secondary fc-btn-block" disabled>
                                        Exemption Closed
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Login -->
                    <div class="col">
                        <div class="fc-choice fc-choice--login">
                            <div class="fc-choice-body">
                                <span class="fc-choice-icon" aria-hidden="true">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                </span>
                                <h2 class="fc-choice-title">Login</h2>
                                <div class="fc-choice-text fc-rte">
                                    {!! $pathPage->already_registered ?? '' !!}
                                </div>
                            </div>
                            <div class="fc-choice-foot">
                                <a href="{{ route('fc.login', $intentQuery ?? []) }}"
                                    class="btn btn-primary fc-btn-block">
                                    <i class="bi bi-person-check" aria-hidden="true"></i> Login to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FAQs -->
            <section aria-labelledby="path-faq-heading">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3 mb-3">
                    <div>
                        <h2 id="path-faq-heading" class="fc-section-title mb-1">Frequently Asked Questions</h2>
                        <p class="fc-page-sub">Find your query from this list of frequently asked questions</p>
                    </div>
                    <a href="{{ route('fc.faqs.all') }}"
                        class="btn btn-outline-primary btn-sm align-self-start align-self-md-center">
                        View All FAQs
                    </a>
                </div>

                @if ($pathPage->faqs && $pathPage->faqs->count())
                    <div class="accordion fc-faq" id="faqAccordion">
                        @foreach ($pathPage->faqs as $index => $faq)
                            <div class="accordion-item">
                                <h3 class="accordion-header m-0" id="heading{{ $index }}">
                                    <button class="accordion-button collapsed shadow-none rounded-0"
                                        type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}"
                                        aria-expanded="false"
                                        aria-controls="collapse{{ $index }}">
                                        {!! $faq->header !!}
                                    </button>
                                </h3>
                                <div id="collapse{{ $index }}"
                                    class="accordion-collapse collapse"
                                    aria-labelledby="heading{{ $index }}" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body fc-rte">
                                        {!! $faq->content !!}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="ds-empty-state">
                        <i class="bi bi-question-circle fs-4 d-block mb-2" aria-hidden="true"></i>
                        No FAQs available at the moment.
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // SweetAlert takes a colour string, not a CSS variable — read the brand
        // token off the portal scope so this stays in step with sargam-app.css.
        var FC_BRAND = (getComputedStyle(document.body).getPropertyValue('--fc-primary') || '').trim() || '#004a93';
    </script>

    @if (session('warning'))
        <script>
            Swal.fire({
                title: 'Warning',
                text: @json(session('warning')),
                icon: 'warning',
                confirmButtonColor: FC_BRAND,
                confirmButtonText: 'OK'
            });
        </script>
    @endif

    @if ($errors->any())
        <script>
            Swal.fire({
                title: 'Validation Error',
                text: @json(implode("\n", $errors->all())),
                icon: 'error',
                confirmButtonColor: FC_BRAND,
                confirmButtonText: 'OK'
            });
        </script>
    @endif
@endpush
