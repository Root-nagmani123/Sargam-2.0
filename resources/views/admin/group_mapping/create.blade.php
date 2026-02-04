@extends('admin.layouts.master')

@section('title', 'Group Mapping')

@section('css')
<style>
/* Fix Select2 dropdown cropping on Add Group Mapping page */
.group-mapping-create .select2-container { width: 100% !important; }
.group-mapping-create .select2-selection--single .select2-selection__rendered {
    white-space: normal;
    overflow: visible;
    text-overflow: unset;
}
/* When this page is active, fix dropdowns appended to body */
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
</style>
@endsection

@section('setup_content')

<div class="container-fluid group-mapping-create py-3">
    <x-breadcrum title="Group Mapping" />
    <x-session_message />

    <div class="card shadow-sm rounded-4 overflow-visible mt-2" style="border-left: 4px solid #004a93;">
        <div class="card-body py-3 py-md-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
                <div>
                    <h4 class="card-title mb-1 fw-semibold text-dark">
                        {{ !empty($groupMapping) ? 'Edit Group Mapping' : 'Add Group Mapping' }}
                    </h4>
                    <p class="text-muted mb-0 small">
                        Configure course, group type and optional faculty in a single place.
                    </p>
                </div>
            </div>

            <form action="{{ route('group.mapping.store') }}" method="POST" id="classSessionForm" class="row g-3 g-md-4">
                @csrf
                @if(!empty($groupMapping))
                <input type="hidden" name="pk" value="{{ encrypt($groupMapping->pk) }}">
                @endif

                <div class="col-12 col-md-4">
                    <div class="mb-2 mb-md-3">
                        <x-select name="course_id" label="Course name :" placeholder="Course name"
                            formLabelClass="form-label" formSelectClass="select2 w-100" required="true"
                            :options="$courses" :value="old('course_id', $groupMapping->course_name ?? '')" />
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="mb-2 mb-md-3">
                        <x-select name="type_id" label="Group Type :" placeholder="Group Type"
                            formLabelClass="form-label" formSelectClass="select2 w-100" required="true"
                            :options="$courseGroupTypeMaster"
                            :value="old('type_name', $groupMapping->type_name ?? '')" />
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="mb-2 mb-md-3">
                        <x-input name="group_name" label="Group Name :" placeholder="Group Name"
                            formLabelClass="form-label" required="true"
                            value="{{ old('group_name', $groupMapping->group_name ?? '') }}" />
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="mb-2 mb-md-3">
                        <x-select name="facility_id" label="Faculty(Optional) :" placeholder="Select Faculty"
                            formLabelClass="form-label" formSelectClass="select2 w-100"
                            :options="$facilities ?? []"
                            :value="old('facility_id', $groupMapping->facility_id ?? '')" />
                    </div>
                </div>

                <div class="col-12">
                    <hr class="mt-2 mb-3">
                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 pt-1">
                        <button class="btn btn-primary px-4" type="submit" id="saveClassSessionForm">
                            Save
                        </button>
                        <a href="{{ route('group.mapping.index') }}" class="btn btn-outline-secondary">
                            Back
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
                $el.select2('destroy');
            }
            $el.select2({
                allowClear: true,
                width: '100%',
                dropdownParent: $(document.body)
            });
        });
    }
    $(document).ready(function() {
        setTimeout(initGroupMappingSelect2, 150);
    });
})();
</script>
@endpush

@endsection