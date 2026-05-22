@extends('admin.layouts.master')
@section('title', $step->step_name)

@section('setup_content')
<div class="container py-4">
    @include('partials.step-indicator', ['current' => $step->step_number])

    <div class="card border-0 shadow-sm" style="border-radius:10px;">
        <div class="card-header bg-white py-3">
            <h5 class="mb-1"><i class="bi {{ $step->icon ?? 'bi-file-text' }} me-2"></i>{{ $step->step_name }}</h5>
            @if($step->description)
                <p class="text-muted small mb-0">{{ $step->description }}</p>
            @endif
        </div>
        <div class="card-body">
            <form method="POST" action="{{ $saveUrl }}" enctype="multipart/form-data">
                @csrf

                @php $lastSection = null; @endphp
                @foreach($fields as $field)
                    @if($field->section_heading && $field->section_heading !== $lastSection)
                        @if($lastSection !== null)
                            </div>
                        @endif
                        @php $lastSection = $field->section_heading; @endphp
                        <h6 class="text-uppercase small fw-bold text-muted border-bottom pb-2 {{ $loop->first ? '' : 'mt-4' }} mb-3" style="letter-spacing:0.5px;">
                            {{ $field->section_heading }}
                        </h6>
                        <div class="row g-3">
                    @elseif($loop->first)
                        @php $lastSection = $field->section_heading; @endphp
                        @if($field->section_heading)
                            <h6 class="text-uppercase small fw-bold text-muted border-bottom pb-2 mb-3" style="letter-spacing:0.5px;">
                                {{ $field->section_heading }}
                            </h6>
                        @endif
                        <div class="row g-3">
                    @endif

                    <div class="{{ $field->css_class }}">
                        @include('fc.registration.partials.dynamic-field', [
                            'field'        => $field,
                            'existingData' => $existingData,
                            'lookups'      => $lookups,
                            'readonly'     => false,
                        ])
                    </div>
                @endforeach

                @if($fields->isNotEmpty())
                    </div>
                @endif

                <div class="d-flex justify-content-between mt-4">
                    @if($prevUrl)
                        <a href="{{ $prevUrl }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Previous
                        </a>
                    @else
                        <span></span>
                    @endif
                    <button type="submit" class="btn btn-primary px-4">
                        Save & Continue <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@php
    $hasSameAsPermanent = isset($fields) && collect($fields)->contains(fn ($f) => $f->field_name === 'same_as_permanent');
@endphp
@if($hasSameAsPermanent)
@push('scripts')
@include('fc.registration.partials.same-as-permanent-script')
@endpush
@endif
