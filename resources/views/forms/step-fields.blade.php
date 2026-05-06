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
        <div class="card-header bg-white py-3">
            <h5 class="mb-1"><i class="bi {{ $step->icon ?? 'bi-file-text' }} me-2"></i>{{ $step->step_name }}</h5>
            @if($step->description)
                <p class="text-muted small mb-0">{{ $step->description }}</p>
            @endif
        </div>
        <div class="card-body">
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
        </div>
    </div>
</div>
@endsection

@if(($step->step_slug ?? '') === 'step2')
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cb = document.getElementById('same_as_permanent');
    if (!cb) return;
    const form = cb.closest('form');
    if (!form) return;

    const presFields = ['pres_address_line1', 'pres_address_line2', 'pres_city', 'pres_state_id', 'pres_pincode', 'pres_country_id'];
    const permFields = ['perm_address_line1', 'perm_address_line2', 'perm_city', 'perm_state_id', 'perm_pincode', 'perm_country_id'];

    function field(name) {
        return form.querySelector('[name="' + name + '"]');
    }

    function syncPresentFromPermanent() {
        if (cb.checked) {
            permFields.forEach(function (perm, i) {
                const src = field(perm);
                const dst = field(presFields[i]);
                if (src && dst) {
                    dst.value = src.value;
                    dst.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }
        presFields.forEach(function (name) {
            const el = field(name);
            if (!el) return;
            el.disabled = cb.checked;
            const wrap = el.closest('[class*="col-md"]');
            if (wrap) wrap.style.opacity = cb.checked ? '0.65' : '1';
        });
    }

    cb.addEventListener('change', syncPresentFromPermanent);
    permFields.forEach(function (name) {
        const el = field(name);
        if (!el) return;
        ['change', 'input'].forEach(function (ev) {
            el.addEventListener(ev, function () {
                if (cb.checked) syncPresentFromPermanent();
            });
        });
    });
    syncPresentFromPermanent();
});
</script>
@endpush
@endif
