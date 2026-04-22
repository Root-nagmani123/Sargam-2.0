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
            <h5 class="mb-1"><i class="bi {{ $step->icon ?? 'bi-journal-text' }} me-2"></i>{{ $step->step_name }}</h5>
            @if($step->description)
                <p class="text-muted small mb-0">{{ $step->description }}</p>
            @endif
        </div>
        <div class="card-body">
            {{-- Group Tabs --}}
            <ul class="nav nav-tabs mb-3" id="groupTabs" role="tablist">
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
            <div class="tab-content" id="groupTabContent">
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
                        <form method="POST" action="{{ route('fc-reg.forms.group.save', [$form, $group]) }}">
                            @csrf

                            @if($groupFieldDefs->isEmpty())
                                <div class="alert alert-warning small mb-0">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    This tab has no form fields configured for this group.
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
                                @if($gi > 0)
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('tab-{{ $groups[$gi-1]->group_name }}-btn').click()">
                                        <i class="bi bi-arrow-left me-1"></i>Previous Tab
                                    </button>
                                @elseif($prevStep)
                                    <a href="{{ route('fc-reg.forms.step', [$form, $prevStep]) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-1"></i>Previous Step
                                    </a>
                                @else
                                    <a href="{{ route('fc-reg.forms.dashboard', $form) }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-1"></i>Dashboard
                                    </a>
                                @endif

                                <button type="submit" class="btn btn-primary">
                                    @if($isLastGroup && $nextStep)
                                        Save & Next Step <i class="bi bi-arrow-right ms-1"></i>
                                    @elseif($isLastGroup)
                                        Save & Finish <i class="bi bi-check-circle ms-1"></i>
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

@push('scripts')
<script>
function addGroupRow(groupName, groupId, maxRows) {
    const container = document.getElementById(groupName + '-container');
    const currentRows = container.querySelectorAll('.repeatable-row');
    if (currentRows.length >= maxRows) {
        alert('Maximum ' + maxRows + ' rows allowed.');
        return;
    }

    const newIndex = currentRows.length;
    const lastRow = currentRows[currentRows.length - 1];
    if (!lastRow) return;

    const clone = lastRow.cloneNode(true);
    clone.dataset.index = newIndex;

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

    clone.querySelectorAll('.invalid-feedback').forEach(function(el) { el.remove(); });
    container.appendChild(clone);
}
</script>
@endpush
