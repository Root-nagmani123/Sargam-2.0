@extends('admin.layouts.master')

@section('title', 'Group Mapping')

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
                            formLabelClass="form-label" formSelectClass=" w-100" required="true"
                            :options="$courses" :value="old('course_id', $groupMapping->course_name ?? '')" />
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <div class="mb-0">
                        <x-select name="type_id" label="Group Type :" placeholder="Group Type"
                            formLabelClass="form-label" formSelectClass=" w-100" required="true"
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
                            formLabelClass="form-label" formSelectClass=" w-100"
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

@section('css')
    @parent
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <style>
        .group-mapping-create .choices__inner {
            min-height: calc(2.25rem + 2px);
            border-radius: 0.375rem;
            border: 1px solid #ced4da;
            padding: 0.375rem 0.75rem;
            background-color: #fff;
            
        }

        .group-mapping-create .choices__list--single .choices__item {
            padding: 0;
            margin: 0;
        }

        .group-mapping-create .choices__list--dropdown {
            border-radius: 0.375rem;
            border-color: #ced4da;
        }

        .group-mapping-create .choices.is-focused .choices__inner,
        .group-mapping-create .choices.is-open .choices__inner {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
    (function () {
        function initGroupMappingChoices() {
            if (typeof Choices === 'undefined') return;

            document.querySelectorAll('.group-mapping-create select').forEach(function (el) {
                if (el.dataset.choicesInitialized === 'true') return;

                new Choices(el, {
                    allowHTML: false,
                    searchPlaceholderValue: 'Search...',
                    removeItemButton: !!el.multiple,
                    shouldSort: false,
                    placeholder: true,
                    placeholderValue: el.getAttribute('placeholder') || 'Select an option',
                });

                el.dataset.choicesInitialized = 'true';
            });
        }

        document.addEventListener('DOMContentLoaded', initGroupMappingChoices);
        window.addEventListener('load', initGroupMappingChoices);
    })();
</script>
@endpush

@endsection
