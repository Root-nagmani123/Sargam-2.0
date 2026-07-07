{{--
    Single-page (no-tabs) layout for dynamic FC forms — matches the flat-step look.
    All groups are stacked as sections; one "Save & Continue" button posts each
    group to its existing saveGroup endpoint (AJAX, in order) and then advances.
    Backend / saveGroup / replace_all / upsert / row insertion logic is unchanged.
    Used only when $form is set (dynamic forms); the legacy tabbed layout is kept
    in dynamic-step3.blade.php for the hard-coded FC registration step 3.
--}}
@php
    $nextUrl = (isset($nextStep) && $nextStep)
        ? route('fc-reg.forms.step', [$form, $nextStep])
        : route('fc-reg.forms.dashboard', $form);
@endphp
@include('fc.registration.partials.fc-form-theme')

{{-- Choices.js — searchable dropdowns (e.g. Spouse Name from fc_registration_master). --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<style>
    .choices-field .choices { margin-bottom: 0; }
    .choices-field .choices__inner { min-height: calc(1.5em + .5rem + 2px); padding: .2rem .5rem; font-size: .875rem; border-radius: .375rem; background: #fff; }
    .choices-field .choices__list--dropdown .choices__item { font-size: .875rem; }
</style>

<div class="fc-form-page">
<div class="fc-shell fc-step3-page">
    <div class="fc-band">
        <div class="fc-band__row">
            <div class="fc-band__ico"><i class="bi {{ $step->icon ?? 'bi-list-ul' }}"></i></div>
            <div>
                <h4>{{ $form->form_name }}</h4>
                <p>Step {{ (isset($allSteps) ? ($allSteps->search(fn ($s) => $s->id === $step->id)) : 0) + 1 }}@isset($allSteps) of {{ $allSteps->count() }}@endisset — {{ $step->step_name }}</p>
            </div>
            <a href="{{ route('fc-reg.forms.dashboard', $form) }}" class="btn btn-light btn-sm ms-auto rounded-pill px-3">
                <i class="bi bi-grid me-1"></i>All Steps
            </a>
        </div>
    </div>

    @isset($allSteps)
        @include('fc.registration.partials.fc-stepper')
    @endisset

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
        <div class="card-header bg-white py-3">
            <h5 class="mb-1"><i class="bi {{ $step->icon ?? 'bi-journal-text' }} me-2"></i>{{ $step->step_name }}</h5>
            @if($step->description)
                <p class="text-muted small mb-0">{{ $step->description }}</p>
            @endif
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success small py-2">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger small py-2 mb-3">
                    <strong class="d-block mb-1">Please fix the following:</strong>
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <div id="fcGroupSections">
                @foreach($groups as $group)
                    @php
                        $rows           = $existingRows[$group->group_name] ?? collect();
                        $gLookups       = $groupLookups[$group->group_name] ?? [];
                        $isSingleRow    = ($group->max_rows <= 1);
                        $groupFieldDefs = $group->activeGroupFields->isNotEmpty()
                            ? $group->activeGroupFields
                            : $group->groupFields;
                        $needsMultipart = $groupFieldDefs->contains(fn ($f) => $f->field_type === 'file');
                    @endphp
                    <section class="fc-group-section mb-4">
                        <h6 class="text-uppercase small fw-bold text-muted border-bottom pb-2 {{ $loop->first ? '' : 'mt-2' }} mb-3" style="letter-spacing:0.5px;">
                            @if(($group->group_name ?? '') === 'pre_medical_history')<i class="bi bi-heart-pulse me-1"></i>@endif
                            {{ $group->group_label }}
                            @if($completedGroups[$group->group_name] ?? false)
                                <i class="bi bi-check-circle-fill text-success ms-1"></i>
                            @endif
                        </h6>

                        <form class="fc-group-form" data-group="{{ $group->group_name }}"
                              method="POST"
                              action="{{ route('fc-reg.forms.group.save', [$form, $group]) }}"
                              @if($needsMultipart) enctype="multipart/form-data" @endif>
                            @csrf
                            @if($groupFieldDefs->isEmpty())
                                <div class="alert alert-warning small mb-0">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    This section has no fields yet.
                                </div>
                            @else
                                <div id="{{ $group->group_name }}-container">
                                    @if($rows->isNotEmpty())
                                        @foreach($rows as $i => $row)
                                            @include('fc.registration.partials.dynamic-group-row', [
                                                'group' => $group, 'i' => $i, 'row' => $row, 'groupLookups' => $gLookups,
                                                'districtOptions' => $districtOptions ?? collect(),
                                            ])
                                        @endforeach
                                    @else
                                        @php $starterRows = max(1, (int) $group->min_rows); @endphp
                                        @for($i = 0; $i < $starterRows; $i++)
                                            @include('fc.registration.partials.dynamic-group-row', [
                                                'group' => $group, 'i' => $i, 'row' => (object)[], 'groupLookups' => $gLookups,
                                                'districtOptions' => $districtOptions ?? collect(),
                                            ])
                                        @endfor
                                    @endif
                                </div>

                                @if(! $isSingleRow)
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2"
                                            onclick="addGroupRow('{{ $group->group_name }}', {{ $group->id }}, {{ $group->max_rows }})">
                                        <i class="bi bi-plus-circle me-1"></i>Add Row
                                    </button>
                                @endif
                            @endif
                        </form>
                    </section>
                @endforeach
            </div>

            <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                @if(isset($prevStep) && $prevStep)
                    <a href="{{ route('fc-reg.forms.step', [$form, $prevStep]) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Previous Step
                    </a>
                @else
                    <a href="{{ route('fc-reg.forms.dashboard', $form) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Dashboard
                    </a>
                @endif
                <button type="button" id="fcSaveAllBtn" class="btn btn-primary px-4" data-next-url="{{ $nextUrl }}">
                    <span class="fc-save-label">Save &amp; Continue</span>
                    <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                    <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>
</div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<style>
    .select2-container--default .select2-selection--single { height: 31px; padding: 2px 8px; font-size: 0.875rem; border: 1px solid #dee2e6; border-radius: 0.25rem; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 29px; }
    .fc-step3-page .repeatable-row { margin-bottom: 0.5rem; }
    .fc-group-section { scroll-margin-top: 80px; }
    .is-invalid-select2 .select2-selection { border-color: #dc3545 !important; }
</style>
@endpush

@push('scripts')
@include('fc.registration.partials.fc-form-validation')
@include('fc.registration.partials.fc-location-cascade-script')
{{-- FC public layout has no jQuery; load it if not already present (select2 needs it). --}}
<script>window.jQuery || document.write('<script src="https://code.jquery.com/jquery-3.6.4.min.js"><\/script>')</script>
<script src="{{ asset('admin_assets/libs/select2/dist/js/select2.full.min.js') }}"></script>
<script>
// Repeatable "Add Row" — identical behaviour to the tabbed layout.
function addGroupRow(groupName, groupId, maxRows) {
    const container = document.getElementById(groupName + '-container');
    const currentRows = container.querySelectorAll('.repeatable-row');
    if (currentRows.length >= maxRows) { alert('Maximum ' + maxRows + ' rows allowed.'); return; }
    const newIndex = currentRows.length;
    const lastRow = currentRows[currentRows.length - 1];
    if (!lastRow) return;
    const clone = lastRow.cloneNode(true);
    clone.dataset.index = newIndex;
    clone.querySelectorAll('input, select, textarea').forEach(function (el) {
        const name = el.getAttribute('name');
        if (name) { el.setAttribute('name', name.replace(/\[\d+\]/, '[' + newIndex + ']')); }
        if (el.tagName === 'SELECT') { el.selectedIndex = 0; }
        else if (el.type === 'checkbox') { el.checked = false; }
        else { el.value = ''; }
        el.classList.remove('is-invalid');
    });
    clone.querySelectorAll('.dynamic-current-file-hint').forEach(function (el) { el.remove(); });
    clone.querySelectorAll('.invalid-feedback').forEach(function (el) { el.remove(); });
    container.appendChild(clone);
    $(clone).find('.select2-dynamic').each(function () {
        $(this).next('.select2-container').remove();
        $(this).select2({ theme: 'bootstrap-5', width: '100%', placeholder: '-- Select --', allowClear: true });
    });
}

$(document).ready(function () {
    $('.select2-dynamic').select2({ theme: 'bootstrap-5', width: '100%', placeholder: '-- Select --', allowClear: true });
});

// File field size / type guard (e.g. Pre-Medical supporting document).
document.querySelectorAll('.fc-file-upload[data-max-kb]').forEach(function (input) {
    var maxKb = parseInt(input.getAttribute('data-max-kb'), 10) || 0;
    var allowedExts = ['pdf','jpg','jpeg','png'];
    function validateFile(file) {
        if (!file) return;
        var ext = file.name.split('.').pop().toLowerCase();
        var errEl = input.nextElementSibling;
        if (!errEl || !errEl.classList.contains('js-file-error')) {
            errEl = document.createElement('div');
            errEl.className = 'js-file-error text-danger small mt-1';
            input.parentNode.insertBefore(errEl, input.nextSibling);
        }
        var msg = '';
        if (allowedExts.indexOf(ext) === -1) { msg = 'Invalid file type. Allowed: ' + allowedExts.join(', ').toUpperCase() + '.'; }
        else if (maxKb > 0 && file.size > maxKb * 1024) { var limit = maxKb >= 1024 ? (maxKb/1024)+' MB' : maxKb+' KB'; msg = 'File is too large. Maximum allowed size is ' + limit + '.'; }
        if (msg) { errEl.textContent = msg; input.classList.add('is-invalid'); input.value = ''; }
        else { errEl.textContent = ''; input.classList.remove('is-invalid'); }
    }
    input.addEventListener('change', function () { validateFile(this.files[0]); });
});

// One "Save & Continue": client-validate required fields, then POST each group
// form to its existing saveGroup endpoint in order, then advance to next step.
(function () {
    const btn = document.getElementById('fcSaveAllBtn');
    if (!btn) return;

    function flagRequired(form) {
        let first = null;
        form.querySelectorAll('[data-required]').forEach(function (el) {
            const empty = (!el.value || String(el.value).trim() === '');
            const s2 = el.classList.contains('select2-dynamic') ? $(el).next('.select2-container')[0] : null;
            if (empty) {
                el.classList.add('is-invalid');
                if (s2) s2.classList.add('is-invalid-select2');
                if (!first) first = el;
            } else {
                el.classList.remove('is-invalid');
                if (s2) s2.classList.remove('is-invalid-select2');
            }
        });
        return first;
    }

    btn.addEventListener('click', async function () {
        const forms = Array.from(document.querySelectorAll('.fc-group-form'));

        if (document.querySelector('.fc-file-upload.is-invalid')) {
            alert('Please fix the highlighted file before saving.');
            return;
        }

        let firstInvalid = null;
        forms.forEach(function (f) { const fi = flagRequired(f); if (fi && !firstInvalid) firstInvalid = fi; });
        if (firstInvalid) {
            (firstInvalid.closest('.repeatable-row') || firstInvalid.closest('.fc-group-section') || firstInvalid)
                .scrollIntoView({ behavior: 'smooth', block: 'center' });
            try { firstInvalid.classList.contains('select2-dynamic') ? $(firstInvalid).select2('open') : firstInvalid.focus(); } catch (e) {}
            return;
        }

        const label = btn.querySelector('.fc-save-label');
        const spin  = btn.querySelector('.spinner-border');
        const originalText = label.textContent;
        btn.disabled = true; label.textContent = 'Saving…'; spin.classList.remove('d-none');

        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const headers = Object.assign({ 'X-Requested-With': 'XMLHttpRequest' },
            tokenMeta ? { 'X-CSRF-TOKEN': tokenMeta.getAttribute('content') } : {});

        try {
            for (const form of forms) {
                const resp = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: headers,
                    credentials: 'same-origin',
                });
                if (!resp.ok) { throw new Error('save failed (' + resp.status + ')'); }
            }
            window.location.href = btn.dataset.nextUrl;
        } catch (e) {
            btn.disabled = false; label.textContent = originalText; spin.classList.add('d-none');
            alert('Could not save all sections. Please review your entries and try again.');
        }
    });
})();

// Choices.js searchable dropdowns + conditional show/hide.
// A field marked with .choices-field becomes a searchable dropdown; a field carrying
// data-fc-cond-* is shown only when another field in the same row holds a given value
// (e.g. Spouse Name appears only when "Is your spouse also registering?" = Yes).
(function () {
    function initChoices() {
        if (typeof window.Choices === 'undefined') { return; }
        document.querySelectorAll('.choices-field select').forEach(function (sel) {
            if (sel._choices) { return; }
            try {
                sel._choices = new window.Choices(sel, {
                    searchEnabled: true,
                    shouldSort: false,
                    itemSelectText: '',
                    searchPlaceholderValue: 'Type to search…',
                    placeholderValue: '-- Select --',
                });
            } catch (e) {}
        });
    }

    function clearField(f) {
        if (f._choices) {
            try { f._choices.setChoiceByValue(''); } catch (e) {}
            f.value = '';
        } else if (f.tagName === 'SELECT') {
            f.selectedIndex = 0;
        } else if (f.type === 'checkbox' || f.type === 'radio') {
            f.checked = false;
        } else {
            f.value = '';
        }
        f.classList.remove('is-invalid');
    }

    function applyConditionalFields() {
        document.querySelectorAll('[data-fc-cond-name]').forEach(function (el) {
            var name = el.getAttribute('data-fc-cond-name');
            var want = el.getAttribute('data-fc-cond-value');
            var scope = el.closest('.repeatable-row') || document;
            var checked = scope.querySelector('input[name="' + name + '"]:checked');
            var show = !!(checked && checked.value === want);
            el.style.display = show ? '' : 'none';
            if (!show) {
                el.querySelectorAll('select, input, textarea').forEach(clearField);
            }
        });
    }

    document.addEventListener('change', function (e) {
        if (e.target && e.target.matches && e.target.matches('input[type=radio]')) {
            applyConditionalFields();
        }
    });

    function boot() { initChoices(); applyConditionalFields(); }
    if (window.jQuery) { jQuery(boot); }
    else if (document.readyState !== 'loading') { boot(); }
    else { document.addEventListener('DOMContentLoaded', boot); }
})();
</script>
@endpush
