@extends('admin.layouts.master')
@section('title', $step->step_name)

@section('setup_content')
<div class="container py-2 fc-step3-page">
    @isset($form)
        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
            <a href="{{ route('fc-reg.forms.dashboard', $form) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>{{ $form->form_name }}
            </a>
            @isset($allSteps)
                @foreach($allSteps as $si => $s)
                    <span class="badge {{ $s->id === $step->id ? 'bg-primary' : 'bg-light text-dark' }} rounded-pill px-3 py-2">
                        {{ $si + 1 }}. {{ $s->step_name }}
                    </span>
                @endforeach
            @endisset
        </div>
    @endisset
    @if (! isset($allSteps))
        @include('partials.step-indicator', ['current' => 3])
    @endif

    <div class="card border-0 shadow-sm" style="border-radius:10px;">
        <div class="card-header bg-white py-2">
            <h5 class="mb-1"><i class="bi {{ $step->icon ?? 'bi-journal-text' }} me-2"></i>{{ $step->step_name }}</h5>
            @if($step->description)
                <p class="text-muted small mb-0">{{ $step->description }}</p>
            @endif
        </div>
        <div class="card-body pt-2 pb-3">
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
            @php $activeGi = fc_form_group_active_index($groups, request()->query('group')); @endphp
            {{-- Group tabs from form builder (includes Pre-medical when configured as first group) --}}
            <ul class="nav nav-tabs mb-2 flex-wrap" id="step3Tabs" role="tablist">
                @foreach($groups as $group)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-nowrap {{ $loop->index === $activeGi ? 'active' : '' }} {{ ($completedGroups[$group->group_name] ?? false) ? 'text-success' : '' }}"
                                id="tab-{{ $group->group_name }}-btn"
                                data-bs-toggle="tab"
                                data-bs-target="#tab-{{ $group->group_name }}"
                                type="button" role="tab">
                            @if($completedGroups[$group->group_name] ?? false)
                                <i class="bi bi-check-circle-fill me-1"></i>
                            @elseif(($group->group_name ?? '') === 'pre_medical_history')
                                <i class="bi bi-heart-pulse me-1"></i>
                            @endif
                            {{ $group->group_label }}
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content" id="step3TabContent">
                @foreach($groups as $group)
                    @php
                        $rows         = $existingRows[$group->group_name] ?? collect();
                        $gLookups     = $groupLookups[$group->group_name] ?? [];
                        $isSingleRow  = ($group->max_rows <= 1);
                        $isLastGroup  = $loop->last;
                        $groupFieldDefs = $group->activeGroupFields->isNotEmpty()
                            ? $group->activeGroupFields
                            : $group->groupFields;
                        $needsMultipart = $groupFieldDefs->contains(fn ($f) => $f->field_type === 'file');
                    @endphp
                    <div class="tab-pane fade {{ $loop->index === $activeGi ? 'show active' : '' }}"
                         id="tab-{{ $group->group_name }}" role="tabpanel"
                         @if($loop->index === $activeGi) style="display:block !important;" @endif>
                        <form method="POST"
                              action="{{ isset($form) ? route('fc-reg.forms.group.save', [$form, $group]) : route('fc-reg.registration.step3.save-group', $group->id) }}"
                              @if($needsMultipart) enctype="multipart/form-data" @endif>
                            @csrf

                            @if($groupFieldDefs->isEmpty())
                                <div class="alert alert-warning small mb-0">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    This tab has no form fields yet. Add fields under <strong>FC Registration → Form Management / Form Builder</strong> for this group, or run the FC form seeder.
                                </div>
                            @else
                            <div id="{{ $group->group_name }}-container">
                                @if($rows->isNotEmpty())
                                    @foreach($rows as $i => $row)
                                        @include('fc.registration.partials.dynamic-group-row', [
                                            'group' => $group,
                                            'i' => $i,
                                            'row' => $row,
                                            'groupLookups' => $gLookups,
                                        ])
                                    @endforeach
                                @else
                                    @php $starterRows = max(1, (int) $group->min_rows); @endphp
                                    @for($i = 0; $i < $starterRows; $i++)
                                        @include('fc.registration.partials.dynamic-group-row', [
                                            'group' => $group,
                                            'i' => $i,
                                            'row' => (object)[],
                                            'groupLookups' => $gLookups,
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

                            <div class="d-flex justify-content-between mt-4">
                                @if($loop->index > 0)
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('tab-{{ $groups[$loop->index - 1]->group_name }}-btn').click()">
                                        <i class="bi bi-arrow-left me-1"></i>Previous Tab
                                    </button>
                                @elseif(isset($prevStep, $form))
                                    <a href="{{ route('fc-reg.forms.step', [$form, $prevStep]) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-1"></i>Previous Step
                                    </a>
                                @else
                                    <a href="{{ route('fc-reg.registration.step2') }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-1"></i>Back to Step 2
                                    </a>
                                @endif

                                <button type="submit" class="btn btn-primary">
                                    @if($isLastGroup && isset($nextStep, $form))
                                        Save & Next Step <i class="bi bi-arrow-right ms-1"></i>
                                    @elseif($isLastGroup)
                                        Save & Continue to Bank Details <i class="bi bi-arrow-right ms-1"></i>
                                    @else
                                        Save <i class="bi bi-check me-1"></i>
                                    @endif
                                </button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}">
<style>
    .select2-container--default .select2-selection--single { height: 31px; padding: 2px 8px; font-size: 0.875rem; border: 1px solid #dee2e6; border-radius: 0.25rem; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 29px; }
    /* Stack group panes in one grid cell so hidden tabs do not add vertical gap */
    #step3TabContent {
        display: grid;
        grid-template-columns: 1fr;
        margin-top: 0;
    }
    #step3TabContent > .tab-pane {
        grid-row: 1;
        grid-column: 1;
        margin: 0;
        padding: 0;
    }
    #step3TabContent > .tab-pane:not(.show.active) {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        overflow: hidden !important;
        pointer-events: none;
    }
    #step3TabContent > .tab-pane.show.active {
        display: block !important;
        visibility: visible !important;
        height: auto !important;
        opacity: 1;
    }
    .fc-step3-page .repeatable-row { margin-bottom: 0.5rem; }
</style>
@endpush

@push('scripts')
<script src="{{ asset('admin_assets/libs/select2/dist/js/select2.full.min.js') }}"></script>
<script>
function addGroupRow(groupName, groupId, maxRows) {
    const container = document.getElementById(groupName + '-container');
    const currentRows = container.querySelectorAll('.repeatable-row');
    if (currentRows.length >= maxRows) {
        alert('Maximum ' + maxRows + ' rows allowed.');
        return;
    }

    const newIndex = currentRows.length;
    // Clone the last row and clear its values
    const lastRow = currentRows[currentRows.length - 1];
    if (!lastRow) return;

    const clone = lastRow.cloneNode(true);
    clone.dataset.index = newIndex;

    // Update all input/select names and clear values
    clone.querySelectorAll('input, select, textarea').forEach(function(el) {
        const name = el.getAttribute('name');
        if (name) {
            el.setAttribute('name', name.replace(/\[\d+\]/, '[' + newIndex + ']'));
        }
        if (el.tagName === 'SELECT') {
            el.selectedIndex = 0;
        } else if (el.type === 'checkbox') {
            el.checked = false;
        } else if (el.type === 'file') {
            el.value = '';
        } else {
            el.value = '';
        }
        el.classList.remove('is-invalid');
    });

    clone.querySelectorAll('.dynamic-current-file-hint').forEach(function(el) { el.remove(); });

    // Clear validation errors
    clone.querySelectorAll('.invalid-feedback').forEach(function(el) { el.remove(); });

    container.appendChild(clone);

    // Initialize select2 on cloned row
    $(clone).find('.select2-dynamic').each(function() {
        // Remove any old select2 container from the clone
        $(this).next('.select2-container').remove();
        $(this).select2({ theme: 'bootstrap-5', width: '100%', placeholder: '-- Select --', allowClear: true });
    });
}

// Initialize select2 on page load
$(document).ready(function() {
    $('.select2-dynamic').select2({ theme: 'bootstrap-5', width: '100%', placeholder: '-- Select --', allowClear: true });
});

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
@include('fc.registration.partials.group-tabs-activate-script')
@endpush
