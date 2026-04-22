@extends('admin.layouts.master')
@section('title', 'Preview: ' . $step->step_name)

@section('setup_content')
<div class="container py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('fc-reg.admin.form-builder.step', $step) }}" class="btn btn-sm btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i> Back to Editor
        </a>
        <h4 class="mb-0"><i class="bi bi-eye me-2"></i>Preview: {{ $step->step_name }}</h4>
        <span class="badge bg-info ms-3">Read-Only Preview</span>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:10px;">
        <div class="card-header bg-white py-3">
            <h5 class="mb-1">{{ $step->step_name }}</h5>
            @if($step->description)
                <p class="text-muted small mb-0">{{ $step->description }}</p>
            @endif
        </div>
        <div class="card-body">
            {{-- Flat fields (Step 1, 2, Bank) --}}
            @if($fields->isNotEmpty())
                @php $lastSection = null; @endphp
                <div class="row g-3">
                    @foreach($fields as $field)
                        @if($field->section_heading && $field->section_heading !== $lastSection)
                            @if($lastSection !== null)
                                </div><div class="row g-3 mt-2">
                            @endif
                            @php $lastSection = $field->section_heading; @endphp
                            <div class="col-12">
                                <h6 class="text-uppercase small fw-bold text-muted border-bottom pb-2 mb-0 mt-2" style="letter-spacing:0.5px;">{{ $field->section_heading }}</h6>
                            </div>
                        @endif
                        <div class="{{ $field->css_class }}">
                            @include('fc.registration.partials.dynamic-field', ['field' => $field, 'existingData' => null, 'lookups' => $lookups, 'readonly' => true])
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Groups (Step 3) --}}
            @if($groups->isNotEmpty())
                <ul class="nav nav-tabs mb-3" role="tablist">
                    @foreach($groups as $gi => $group)
                        <li class="nav-item">
                            <a class="nav-link {{ $gi === 0 ? 'active' : '' }}" data-bs-toggle="tab" href="#preview-grp-{{ $group->id }}">{{ $group->group_label }}</a>
                        </li>
                    @endforeach
                </ul>
                <div class="tab-content">
                    @foreach($groups as $gi => $group)
                        <div class="tab-pane {{ $gi === 0 ? 'show active' : '' }}" id="preview-grp-{{ $group->id }}">
                            <div class="border rounded p-3 mb-2 bg-light">
                                <small class="text-muted">Table: <code>{{ $group->target_table }}</code> | Mode: <code>{{ $group->save_mode }}</code> | Rows: {{ $group->min_rows }}-{{ $group->max_rows }}</small>
                            </div>
                            <div class="row g-3">
                                @foreach($group->activeGroupFields as $gf)
                                    <div class="{{ $gf->css_class }}">
                                        @include('fc.registration.partials.dynamic-field', ['field' => $gf, 'existingData' => null, 'lookups' => $groupLookups[$group->group_name] ?? [], 'readonly' => true])
                                    </div>
                                @endforeach
                            </div>
                            @if($group->max_rows > 1)
                                <button type="button" class="btn btn-sm btn-outline-primary mt-3" disabled>
                                    <i class="bi bi-plus-circle me-1"></i>Add Row
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Document Checklist (Documents step) --}}
            @if($docMasters->isNotEmpty())
                <div class="alert alert-warning small py-2 mb-3">
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
@endsection
