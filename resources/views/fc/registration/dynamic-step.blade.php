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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cb = document.getElementById('same_as_permanent');
    if (!cb) return;

    const presFields = ['pres_address_line1','pres_address_line2','pres_city','pres_state_id','pres_pincode','pres_country_id'];
    const permFields = ['perm_address_line1','perm_address_line2','perm_city','perm_state_id','perm_pincode','perm_country_id'];

    function toggle() {
        if (cb.checked) {
            permFields.forEach(function (perm, i) {
                const src = document.querySelector('[name="' + perm + '"]');
                const dst = document.querySelector('[name="' + presFields[i] + '"]');
                if (src && dst) dst.value = src.value;
            });
        }
        presFields.forEach(function (name) {
            const el = document.querySelector('[name="' + name + '"]');
            if (el) {
                el.disabled = cb.checked;
                el.closest('.col-md-6, .col-md-3')?.style && (el.closest('.col-md-6, .col-md-3').style.opacity = cb.checked ? '0.4' : '1');
            }
        });
    }

    cb.addEventListener('change', toggle);
    if (cb.checked) toggle();
});
</script>
@endpush
