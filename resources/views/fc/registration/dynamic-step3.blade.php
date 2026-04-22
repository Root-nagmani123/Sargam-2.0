@extends('admin.layouts.master')
@section('title', $step->step_name)

@section('setup_content')
<div class="container py-4">
    @include('partials.step-indicator', ['current' => 3])

    <div class="card border-0 shadow-sm" style="border-radius:10px;">
        <div class="card-header bg-white py-3">
            <h5 class="mb-1"><i class="bi {{ $step->icon ?? 'bi-journal-text' }} me-2"></i>{{ $step->step_name }}</h5>
            @if($step->description)
                <p class="text-muted small mb-0">{{ $step->description }}</p>
            @endif
        </div>
        <div class="card-body">
            {{-- Group Tabs --}}
            <ul class="nav nav-tabs mb-3" id="step3Tabs" role="tablist">
                @foreach($groups as $gi => $group)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $gi === 0 ? 'active' : '' }} {{ ($completedGroups[$group->group_name] ?? false) ? 'text-success' : '' }}"
                                id="tab-{{ $group->group_name }}-btn"
                                data-bs-toggle="tab"
                                data-bs-target="#tab-{{ $group->group_name }}"
                                type="button" role="tab">
                            @if($completedGroups[$group->group_name] ?? false)
                                <i class="bi bi-check-circle-fill me-1"></i>
                            @endif
                            {{ $group->group_label }}
                        </button>
                    </li>
                @endforeach
            </ul>

            {{-- Tab Content --}}
            <div class="tab-content" id="step3TabContent">
                @foreach($groups as $gi => $group)
                    @php
                        $rows         = $existingRows[$group->group_name] ?? collect();
                        $gLookups     = $groupLookups[$group->group_name] ?? [];
                        $isSingleRow  = ($group->max_rows <= 1);
                        $isLastGroup  = $loop->last;
                        $groupFieldDefs = $group->activeGroupFields->isNotEmpty()
                            ? $group->activeGroupFields
                            : $group->groupFields;
                    @endphp
                    <div class="tab-pane fade {{ $gi === 0 ? 'show active' : '' }}" id="tab-{{ $group->group_name }}" role="tabpanel">
                        <form method="POST" action="{{ route('fc-reg.registration.step3.save-group', $group->id) }}">
                            @csrf

                            @if($groupFieldDefs->isEmpty())
                                <div class="alert alert-warning small mb-0">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    This tab has no form fields yet. Add fields under <strong>FC Registration → FC Admin Dynamic Form</strong> for this group, or run the FC form seeder.
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
                                    {{-- Always show at least one starter row (min_rows may be 0 for optional repeatables) --}}
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
                                @if($gi > 0)
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('tab-{{ $groups[$gi-1]->group_name }}-btn').click()">
                                        <i class="bi bi-arrow-left me-1"></i>Previous Tab
                                    </button>
                                @else
                                    <a href="{{ route('fc-reg.registration.step2') }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-1"></i>Back to Step 2
                                    </a>
                                @endif

                                <button type="submit" class="btn btn-primary">
                                    @if($isLastGroup)
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
        } else {
            el.value = '';
        }
        el.classList.remove('is-invalid');
    });

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
</script>
@endpush
