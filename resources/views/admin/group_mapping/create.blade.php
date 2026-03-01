@extends('admin.layouts.master')

@section('title', 'Group Mapping')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
/* Fix Select2 dropdown cropping on Add Group Mapping page */
.group-mapping-create .select2-container { width: 100% !important; }
.group-mapping-create .select2-selection--single .select2-selection__rendered {
    white-space: normal;
    overflow: visible;
    text-overflow: unset;
}
/* Ensure Select2 dropdown is visible and above all content */
.select2-container--open .select2-dropdown--below,
.select2-container--open .select2-dropdown--above {
    z-index: 99999 !important;
    min-width: 280px !important;
}
body .select2-dropdown {
    z-index: 99999 !important;
}
.group-mapping-create ~ .select2-container,
body .select2-dropdown {
    z-index: 99999 !important;
}
body:has(.group-mapping-create) .select2-dropdown {
    min-width: 280px !important;
    z-index: 99999 !important;
}
body:has(.group-mapping-create) .select2-results__option {
    white-space: normal !important;
    word-wrap: break-word;
    overflow-wrap: break-word;
}
body:has(.group-mapping-create) .select2-results__options {
    max-height: 300px;
}
/* Prevent parent overflow from clipping dropdown */
.group-mapping-create,
.group-mapping-create .card,
.group-mapping-create .card-body {
    overflow: visible !important;
}

/* Group Mapping Create - Bootstrap 5.3 enhanced */
.group-mapping-create .group-mapping-card {
    border-radius: 0.75rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-start: 4px solid #004a93;
    transition: box-shadow 0.2s ease;
}
.group-mapping-create .group-mapping-card:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}
.group-mapping-create .form-label {
    font-weight: 500;
    color: #495057;
}
.group-mapping-create .form-control,
.group-mapping-create .form-select,
.group-mapping-create .select2-container .select2-selection--single {
    border-radius: 0.5rem;
    border: 1px solid #dee2e6;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.group-mapping-create .form-control:focus,
.group-mapping-create .form-select:focus {
    border-color: #004a93;
    box-shadow: 0 0 0 0.2rem rgba(0, 74, 147, 0.15);
}
.group-mapping-create .btn-primary {
    border-radius: 0.5rem;
    font-weight: 600;
    padding: 0.5rem 1.5rem;
    transition: all 0.2s ease;
}
.group-mapping-create .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 74, 147, 0.3);
}
.group-mapping-create .btn-outline-secondary {
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.2s ease;
}
.group-mapping-create .btn-outline-secondary:hover {
    transform: translateY(-1px);
}
@media (prefers-reduced-motion: reduce) {
    .group-mapping-create .group-mapping-card,
    .group-mapping-create .btn-primary,
    .group-mapping-create .btn-outline-secondary { transition: none; }
    .group-mapping-create .btn-primary:hover,
    .group-mapping-create .btn-outline-secondary:hover { transform: none; }
}
</style>
@endsection

@section('setup_content')

<div class="container-fluid group-mapping-create py-3">
    <x-breadcrum title="Group Mapping" />
    <x-session_message />

    <div class="card group-mapping-card shadow-sm overflow-visible mt-2">
        <div class="card-body py-4 py-lg-5 px-4 px-lg-5">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-2 d-flex align-items-center justify-content-center">
                        <i class="bi bi-diagram-3-fill text-primary" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h4 class="card-title mb-1 fw-semibold text-dark">
                            {{ !empty($groupMapping) ? 'Edit Group Mapping' : 'Add Group Mapping' }}
                        </h4>
                        <p class="text-muted mb-0 small">
                            Configure course, group type and optional faculty in a single place.
                        </p>
                    </div>
                </div>
            </div>

            <form action="{{ route('group.mapping.store') }}" method="POST" id="classSessionForm" class="row g-4">
                @csrf
                @if(!empty($groupMapping))
                <input type="hidden" name="pk" value="{{ encrypt($groupMapping->pk) }}">
                @endif

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="mb-0">
                        <x-select name="course_id" label="Course name :" placeholder="Course name"
                            formLabelClass="form-label" formSelectClass="select2 w-100" required="true"
                            :options="$courses" :value="old('course_id', $groupMapping->course_name ?? '')" />
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="mb-0">
                        <x-select name="type_id" label="Group Type :" placeholder="Group Type"
                            formLabelClass="form-label" formSelectClass="select2 w-100" required="true"
                            :options="$courseGroupTypeMaster"
                            :value="old('type_name', $groupMapping->type_name ?? '')" />
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="mb-0">
                        <x-input name="group_name" label="Group Name :" placeholder="Group Name"
                            formLabelClass="form-label" required="true"
                            value="{{ old('group_name', $groupMapping->group_name ?? '') }}" />
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="mb-0">
                        <x-select name="facility_id" label="Faculty (Optional) :" placeholder="Select Faculty"
                            formLabelClass="form-label" formSelectClass="select2 w-100"
                            :options="$facilities ?? []"
                            :value="old('facility_id', $groupMapping->facility_id ?? '')" />
                    </div>
                </div>

                <div class="col-12">
                    <hr class="my-4">
                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 pt-1">
                        <button class="btn btn-primary px-4 d-inline-flex align-items-center gap-2" type="submit" id="saveClassSessionForm">
                            <i class="bi bi-check-lg"></i> Save
                        </button>
                        <a href="{{ route('group.mapping.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
(function() {
    function initGroupMappingSelect2() {
        if (typeof $ === 'undefined' || !$.fn.select2) return;
        $('.group-mapping-create select.select2').each(function() {
            var $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                try { $el.select2('destroy'); } catch (e) {}
            }
            $el.select2({
                allowClear: true,
                width: '100%',
                dropdownParent: $('body'),
                dropdownCssClass: 'select2-dropdown-group-mapping',
                theme: 'default'
            });
        });
    }
    $(document).ready(function() {
        initGroupMappingSelect2();
        $(window).on('load', function() {
            initGroupMappingSelect2();
        });
    });
})();
</script>
@endpush

@endsection