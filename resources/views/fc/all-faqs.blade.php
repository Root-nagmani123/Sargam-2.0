@extends('fc.layouts.master')

@section('title', 'All FAQs - Foundation Course')

@section('content')
    <div class="container my-5">
        <!-- Back Button -->
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <h3 class="mb-4" style="color: #004a93;">All Frequently Asked Questions</h3>
            <div class="mb-3 ms-3 justify-content-end">
                <a href="{{ route('fc.choose.path') }}" class="btn btn-outline-secondary">
                Back to FAQs
            </a>
            </div>
        </div>

        <div class="accordion" id="faqAccordionAll">
            @foreach ($faqs as $key => $faq)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingAll{{ $key }}">
                        <button class="accordion-button {{ $key !== 0 ? 'collapsed' : '' }}" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapseAll{{ $key }}"
                            aria-expanded="{{ $key === 0 ? 'true' : 'false' }}"
                            aria-controls="collapseAll{{ $key }}">
                            {{ $faq->header }}
                        </button>
                    </h2>
                    <div id="collapseAll{{ $key }}"
                        class="accordion-collapse collapse {{ $key === 0 ? 'show' : '' }}"
                        aria-labelledby="headingAll{{ $key }}" data-bs-parent="#faqAccordionAll">
                        <div class="accordion-body">
                            {!! $faq->content !!}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
