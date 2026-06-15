@extends('admin.layouts.master')
@section('title', 'Preview: ' . $step->step_name)

@push('styles')
@include('fc.registration.partials.fc-form-theme')
<style>
    .fc-preview-page .fc-group-section { scroll-margin-top: 80px; }
    .fc-preview-page .repeatable-row { margin-bottom: 0.5rem; }
</style>
@endpush

@section('setup_content')
<div class="fc-form-page fc-preview-page">
<div class="fc-shell">
    <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
        <a href="{{ route('fc-reg.admin.form-builder.step', $step) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Editor
        </a>
        <h4 class="mb-0"><i class="bi bi-eye me-2"></i>Preview: {{ $step->step_name }}</h4>
        <span class="badge bg-info">Read-Only Preview</span>
    </div>

    <div class="card fc-card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-1"><i class="bi {{ $step->icon ?? 'bi-file-text' }} me-2"></i>{{ $step->step_name }}</h5>
            @if($step->description)
                <p class="text-muted small mb-0">{{ $step->description }}</p>
            @endif
        </div>
        <div class="card-body">
            {{-- Flat fields (Step 1, Bank, Health, etc.) --}}
            @if($fields->isNotEmpty())
                @php $allFileFields = $fields->every(fn ($f) => $f->field_type === 'file'); @endphp

                @if($allFileFields)
                    @include('fc.registration.partials.document-checklist', [
                        'fields' => $fields,
                        'existingData' => null,
                        'readonly' => true,
                    ])
                @else
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
                                'existingData'    => null,
                                'lookups'         => $lookups,
                                'districtOptions' => $districtOptions ?? collect(),
                                'readonly'        => true,
                            ])
                        </div>
                    @endforeach
                    @if($fields->isNotEmpty())
                        </div>
                    @endif
                @endif
            @endif

            {{-- Grouped step (same stacked layout as user-facing step 2) --}}
            @if($groups->isNotEmpty())
                <div id="fcGroupSections" class="{{ $fields->isNotEmpty() ? 'mt-4 pt-3 border-top' : '' }}">
                    @foreach($groups as $group)
                        @php
                            $gLookups       = $groupLookups[$group->group_name] ?? [];
                            $isSingleRow    = ($group->max_rows <= 1);
                            $groupFieldDefs = $group->activeGroupFields->isNotEmpty()
                                ? $group->activeGroupFields
                                : $group->groupFields;
                            $starterRows    = max(1, (int) $group->min_rows);
                        @endphp
                        <section class="fc-group-section mb-4">
                            <h6 class="text-uppercase small fw-bold text-muted border-bottom pb-2 {{ $loop->first ? '' : 'mt-2' }} mb-3" style="letter-spacing:0.5px;">
                                @if(($group->group_name ?? '') === 'pre_medical_history')
                                    <i class="bi bi-heart-pulse me-1"></i>
                                @endif
                                {{ $group->group_label }}
                            </h6>

                            @if($groupFieldDefs->isEmpty())
                                <div class="alert alert-warning small mb-0">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    This section has no fields yet.
                                </div>
                            @else
                                <div id="{{ $group->group_name }}-container">
                                    @for($i = 0; $i < $starterRows; $i++)
                                        @include('fc.registration.partials.dynamic-group-row', [
                                            'group'           => $group,
                                            'i'               => $i,
                                            'row'             => (object) [],
                                            'groupLookups'    => $gLookups,
                                            'districtOptions' => $districtOptions ?? collect(),
                                            'readonly'        => true,
                                        ])
                                    @endfor
                                </div>

                                @if(! $isSingleRow)
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" disabled>
                                        <i class="bi bi-plus-circle me-1"></i>Add Row
                                    </button>
                                @endif
                            @endif
                        </section>
                    @endforeach
                </div>
            @endif

            {{-- Legacy document checklist masters --}}
            @if($docMasters->isNotEmpty())
                <div class="alert alert-warning small py-2 mb-3 mt-4">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Documents marked <strong>Mandatory</strong> must be uploaded before final submission.
                    Accepted formats: <strong>PDF, JPG, PNG</strong> (max 5MB each).
                </div>
                <div class="row g-3">
                    @foreach($docMasters as $doc)
                        <div class="col-12">
                            <div class="border rounded p-3 bg-white" style="border-radius:8px!important;">
                                <div class="row align-items-center g-2">
                                    <div class="col-auto">
                                        <i class="bi bi-circle text-muted fs-4"></i>
                                    </div>
                                    <div class="col">
                                        <div class="fw-semibold small">
                                            {{ $doc->document_name }}
                                            @if($doc->is_mandatory)
                                                <span class="badge bg-danger-subtle text-danger ms-1 small">Mandatory</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary ms-1 small">Optional</span>
                                            @endif
                                        </div>
                                        <small class="text-muted">Code: {{ $doc->document_code }}</small>
                                    </div>
                                    <div class="col-auto">
                                        <div class="d-flex gap-1">
                                            <input type="file" class="form-control form-control-sm py-0" style="max-width:220px;" disabled>
                                            <button type="button" class="btn btn-sm btn-primary py-0 px-2" disabled>
                                                <i class="bi bi-upload me-1"></i>Upload
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
@include('fc.registration.partials.fc-location-cascade-script')
@endpush
