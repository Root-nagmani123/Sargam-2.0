@extends('fc.layouts.master')
@section('title', $step->step_name . ' – ' . $form->form_name)

@section('content')
@include('fc.registration.partials.fc-form-theme')
<div class="fc-form-page">
<div class="fc-shell">
    <div class="fc-band">
        <div class="fc-band__row">
            <div class="fc-band__ico"><i class="bi {{ $step->icon ?? 'bi-file-text' }}"></i></div>
            <div>
                <h4>{{ $form->form_name }}</h4>
                <p>Step {{ ($allSteps->search(fn ($s) => $s->id === $step->id)) + 1 }} of {{ $allSteps->count() }} — {{ $step->step_name }}</p>
            </div>
            <a href="{{ route('fc-reg.forms.dashboard', $form) }}" class="btn btn-light btn-sm ms-auto rounded-pill px-3">
                <i class="bi bi-grid me-1"></i>All Steps
            </a>
        </div>
    </div>

    @include('fc.registration.partials.fc-stepper')

    @if($errors->any())
        <div class="alert alert-danger shadow-sm mb-3" role="alert" id="fc-validation-alert">
            <strong class="d-block mb-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please fix the following errors:</strong>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card fc-card border-0 shadow-sm">
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
            <form method="POST" action="{{ route('fc-reg.forms.step.save', [$form, $step]) }}" enctype="multipart/form-data" class="fc-reg-step-form" novalidate>
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
                            'field'           => $field,
                            'existingData'    => $existingData,
                            'lookups'         => $lookups,
                            'districtOptions' => $districtOptions ?? collect(),
                            'readonly'        => false,
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
</div>
@endsection

@php
    $hasSameAsPermanent = $fields->contains(fn ($f) => $f->field_name === 'same_as_permanent');
@endphp
@push('scripts')
@include('fc.registration.partials.fc-form-validation')
@if($hasSameAsPermanent ?? false)
@include('fc.registration.partials.same-as-permanent-script')
@endif
@include('fc.registration.partials.fc-location-cascade-script')
<script>
document.querySelectorAll('.fc-file-upload[data-max-kb]').forEach(function (input) {
    var maxKb = parseInt(input.getAttribute('data-max-kb'), 10) || 5120;
    var allowedExts = (input.getAttribute('data-accept-ext') || 'pdf,jpg,jpeg,png')
        .split(',').map(function (e) { return e.trim().toLowerCase(); });

    function validateFile(file) {
        if (!file) return true;
        var ext = file.name.split('.').pop().toLowerCase();
        var errEl = input.parentNode.querySelector('.js-file-error');
        if (!errEl) {
            errEl = document.createElement('div');
            errEl.className = 'js-file-error text-danger small mt-1';
            input.parentNode.appendChild(errEl);
        }
        var msg = '';
        if (allowedExts.indexOf(ext) === -1) {
            msg = 'Invalid file type. Allowed: ' + allowedExts.join(', ').toUpperCase() + '.';
        } else if (maxKb > 0 && file.size > maxKb * 1024) {
            var limit = maxKb >= 1024 ? (maxKb / 1024) + ' MB' : maxKb + ' KB';
            msg = 'File is too large. Maximum allowed size is ' + limit + '.';
        }
        if (msg) {
            errEl.textContent = msg;
            input.classList.add('is-invalid');
            input.value = '';
            return false;
        }
        errEl.textContent = '';
        input.classList.remove('is-invalid');
        return true;
    }

    input.addEventListener('change', function () { validateFile(this.files[0]); });

    var form = input.closest('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (input.files && input.files.length && !validateFile(input.files[0])) {
                e.preventDefault();
                input.focus();
            }
        });
    }
});
</script>
@endpush
