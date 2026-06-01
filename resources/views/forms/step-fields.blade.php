@extends('admin.layouts.master')
@section('title', $step->step_name . ' – ' . $form->form_name)

@section('setup_content')
<div class="container py-4">
    {{-- Step indicator --}}
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
        <a href="{{ route('fc-reg.forms.dashboard', $form) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>{{ $form->form_name }}
        </a>
        @foreach($allSteps as $si => $s)
            <span class="badge {{ $s->id === $step->id ? 'bg-primary' : 'bg-light text-dark' }} rounded-pill px-3 py-2">
                {{ $si + 1 }}. {{ $s->step_name }}
            </span>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:10px;">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h5 class="mb-1"><i class="bi {{ $step->icon ?? 'bi-file-text' }} me-2"></i>{{ $step->step_name }}</h5>
                @if($step->description)
                    <p class="text-muted small mb-0">{{ $step->description }}</p>
                @endif
            </div>
            @php
                $fileFieldCount = $fields->where('field_type', 'file')->count();
                $uploadedDocCount = 0;
                if ($fileFieldCount > 0 && $existingData) {
                    foreach ($fields as $f) {
                        if ($f->field_type !== 'file') {
                            continue;
                        }
                        $c = $f->target_column ?: $f->field_name;
                        if (filled($existingData->{$c} ?? null)) {
                            $uploadedDocCount++;
                        }
                    }
                }
            @endphp
            @if($fileFieldCount > 0)
                <span class="badge bg-primary rounded-pill">{{ $uploadedDocCount }} / {{ $fileFieldCount }} Uploaded</span>
            @endif
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success small py-2">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger small py-2">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger small py-2 mb-3">
                    <strong class="d-block mb-1">Please fix the following:</strong>
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @php $allFileFields = $fields->isNotEmpty() && $fields->every(fn ($f) => $f->field_type === 'file'); @endphp

            @if($allFileFields)
                @include('fc.registration.partials.document-checklist', [
                    'fields' => $fields,
                    'existingData' => $existingData,
                    'readonly' => false,
                    'form' => $form,
                    'step' => $step,
                ])
                <form method="POST" action="{{ route('fc-reg.forms.step.save', [$form, $step]) }}" class="mt-4 pt-3 border-top">
                    @csrf
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        @if($prevStep)
                            <a href="{{ route('fc-reg.forms.step', [$form, $prevStep]) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Previous
                            </a>
                        @else
                            <a href="{{ route('fc-reg.forms.dashboard', $form) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Dashboard
                            </a>
                        @endif
                        <button type="submit" class="btn btn-primary px-4">
                            Save & Continue <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </form>
            @else
            <form method="POST" action="{{ route('fc-reg.forms.step.save', [$form, $step]) }}" enctype="multipart/form-data">
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
                    @if($prevStep)
                        <a href="{{ route('fc-reg.forms.step', [$form, $prevStep]) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Previous
                        </a>
                    @else
                        <a href="{{ route('fc-reg.forms.dashboard', $form) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Dashboard
                        </a>
                    @endif
                    <button type="submit" class="btn btn-primary px-4">
                        Save & Continue <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection

@php
    $hasSameAsPermanent = $fields->contains(fn ($f) => $f->field_name === 'same_as_permanent');
@endphp
@push('scripts')
@if($hasSameAsPermanent ?? false)
@include('fc.registration.partials.same-as-permanent-script')
@endif
<script>
document.querySelectorAll('.fc-file-upload[data-max-kb]').forEach(function (input) {
    var maxKb = parseInt(input.getAttribute('data-max-kb'), 10);
    if (!maxKb) return;
    var form = input.closest('form');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        if (!input.files || !input.files.length) return;
        if (input.files[0].size > maxKb * 1024) {
            e.preventDefault();
            var mb = maxKb >= 1024 ? (maxKb / 1024) + ' MB' : maxKb + ' KB';
            alert('File is too large. Maximum allowed size is ' + mb + '.');
            input.focus();
        }
    });
});
</script>
@endpush
