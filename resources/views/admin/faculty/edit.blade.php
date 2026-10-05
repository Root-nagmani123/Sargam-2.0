@extends('admin.layouts.master')

@section('title', 'Edit Faculty')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')

<div class="container-fluid mst-page">
    <x-breadcrum title="Faculty" />

    <!-- start Vertical Steps Example -->
    {{-- id="facultyForm" data-store-url="{{ route('faculty.update') }}"
        data-index-url="{{ route('faculty.index') }} --}}

        <form class="facultyForm">
            <div class="mst-wizard">
                <aside class="mst-wizard__rail">
                    <ol class="mst-steps" role="tablist" aria-label="Faculty form steps">
                        @foreach ([
                            1 => 'Personal Information',
                            2 => 'Qualifications Details',
                            3 => 'Experience Details',
                            4 => 'Bank Details',
                            5 => 'Other information',
                        ] as $stepNo => $stepLabel)
                            <li class="mst-step" data-step="{{ $stepNo }}" role="tab"
                                tabindex="{{ $stepNo === 1 ? 0 : -1 }}" aria-selected="{{ $stepNo === 1 ? 'true' : 'false' }}">
                                <span class="mst-step__index"><span class="mst-step__num">{{ $stepNo }}</span></span>
                                <span class="mst-step__label">{{ $stepLabel }}</span>
                            </li>
                        @endforeach
                    </ol>
                </aside>

                <div class="mst-wizard__body">
            <div class="card mst-form-card" data-mst-step="1">
                <div class="card-body">
                    <h3 class="mst-form-section-title">Personal Information</h3>
                    <input type="hidden" name="faculty_id" value="{{ $faculty->pk }}">
                    @include('admin.faculty.components.basicInfo')
                </div>
            </div>
            <div class="card mst-form-card" data-mst-step="2">
                <div class="card-body">
                    <h3 class="mst-form-section-title">Qualification Details</h3>
                    @include('admin.faculty.components.degree')
                </div>
            </div>
            <div class="card mst-form-card" data-mst-step="3">
                <div class="card-body">
                    <h3 class="mst-form-section-title">Experience Details</h3>
                    @include('admin.faculty.components.experienceDetails')
                </div>
            </div>
            <div class="card mst-form-card" data-mst-step="4">
                <div class="card-body">
                    <h3 class="mst-form-section-title">Bank Details</h3>
                    @include('admin.faculty.components.bankDetails')
                </div>
            </div>
            <div class="card mst-form-card" data-mst-step="5">
                <div class="card-body">
                    <h3 class="mst-form-section-title">Other information</h3>
                    @include('admin.faculty.components.researchPublication')
                </div>
            </div>

            <div class="card mst-form-card" data-mst-step="5">
                <div class="card-body">
                    <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <x-select
                                        name="current_sector"
                                        label="Current Sector :"
                                        placeholder="Select Sector"
                                        formLabelClass="form-label"
                                        :options="$facultySectorList"
                                        required="true"
                                        labelRequired="true"
                                        value="{{ $faculty->faculty_sector }}"
                                    />
                                    <div id="current-sector-error-placeholder"></div>
                                </div>
                            </div>
                            <div class="col-12">

                                <label for="expertise" class="form-label">Area of Expertise :</label>
                                <div class="mb-3">
                                    @if(!empty($faculties))
                                    <fieldset>
                                        <div class="row">
                                            @foreach ($faculties as $key => $option)
                                            <div class="col-12 col-sm-6 col-md-3">
                                                <div class="form-check py-2">
                                                    <input type="checkbox" name="faculties[]" value="{{ $key }}"
                                                        class="form-check-input" id="{{ $loop->index }}"
                                                        {{ in_array($key, $facultExpertise) ? 'checked' : '' }}>
                                                    <label class="form-check-label"
                                                        for="{{ $loop->index }}">{{ $option }}</label>
                                                </div>
                                            </div>
                                            @endforeach
                                            @error('faculties[]')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </fieldset>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="mst-form-footer mb-3">
                            <a href="{{ route('faculty.index') }}" class="btn mst-btn-cancel d-inline-flex align-items-center gap-2 px-4">
                                <span>Cancel</span>
                            </a>
                            <button type="button" class="btn mst-btn-submit d-inline-flex align-items-center gap-2 px-4"
                                    data-mst-next>
                                <span>Save &amp; Next</span>
                            </button>
                            {{-- No `d-none`: the submit stays reachable on every step. See
                                 master-form-wizard.js — hiding it until the last step left a
                                 record that cannot clear an earlier step's `required` fields
                                 with no way to save at all. --}}
                            <button class="btn mst-btn-submit d-inline-flex align-items-center gap-2 px-4"
                                    type="button" id="saveFacultyForm" data-mst-final>
                                <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">save</i>
                                <span>Update Faculty</span>
                            </button>
                        </div>
                </div>
            </div>
                </div>
            </div>
        </form>
    <!-- end Vertical Steps Example -->
</div>


@endsection

@section('scripts')
<script src="{{ asset('js/master-form-wizard.js') }}?v={{ @filemtime(public_path('js/master-form-wizard.js')) ?: time() }}"></script>
<script>
// Show/Hide Faculty (PA) field based on Faculty Type
$(document).ready(function() {

    // Show/Hide Faculty (PA) field based on Faculty Type
    function toggleFacultyPaField() {
        var facultyType = $('select[name="facultytype"]').val();
        if (facultyType == '1') { // Internal
            $('#facultyPaContainer').removeClass('d-none');
        } else {
            $('#facultyPaContainer').addClass('d-none');
            $('input[name="faculty_pa"]').val('');
        }
    }

    // Fetch next faculty code preview for the selected type
    function fetchFacultyCodePreview(facultyType) {
        if (!facultyType) return;
        fetch("{{ route('faculty.generate.code') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({ faculty_type: facultyType })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status) {
                $('input[name="faculty_code"]').val(data.code);
            }
        })
        .catch(err => console.error(err));
    }

    // On change of faculty type (user interaction only)
    $('select[name="facultytype"]').on('change', function() {
        toggleFacultyPaField();
        // Only fetch new code if it's a user-triggered change (not programmatic)
        if (!$(this).data('programmatic')) {
            fetchFacultyCodePreview($(this).val());
        }
    });

    // Autofill logic for edit form
    window.fillFacultyForm = function(faculty) {
        // Mark as programmatic so the change event does NOT fetch a new code
        $("select[name='facultytype']").data('programmatic', true)
            .val(faculty.faculty_type ? String(faculty.faculty_type) : '')
            .trigger('change')
            .data('programmatic', false);

        $("select[name='appellation']").val(faculty.appellation ?? '').trigger('change');
        $("input[name='faculty_pa']").val(faculty.faculty_pa ?? '');
        // Keep the existing faculty code — do NOT overwrite with preview
        $("input[name='faculty_code']").val(faculty.faculty_code);
        $("input[name='landline']").val(faculty.landline_no);
        $("input[name='mobile']").val(faculty.mobile_no);
    }

    // Run on page load for initial state
    toggleFacultyPaField();

    // ======= PHOTO FILE SIZE VALIDATION =======
    // Custom close button handler for photo error
    $(document).on('click', '.photo-close-btn-basic', function() {
        $('#photoErrorMessageBasic').addClass('d-none').removeClass('show');
    });

    $('input[name="photo"]').on('change', function () {
        try {
            const file = this.files[0];
            const maxSizeInBytes = 2 * 1024 * 1024; // 2 MB
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            const errorMessageDiv = $('#photoErrorMessageBasic');
            const errorText = $('#photoErrorTextBasic');
            const photoPreview = $('#photoPreview');

            // Reset error state completely - remove all Bootstrap alert classes and add d-none
            errorMessageDiv.removeClass('show').addClass('d-none');
            if (errorText.length) {
                errorText.text('');
            }
            photoPreview.addClass('d-none').attr('src', '#');
            $('#previousPhotoWrapper').removeClass('d-none');

            // If no file selected, just return
            if (!file) {
                return;
            }

            // Check file type
            if (!allowedTypes.includes(file.type)) {
                errorText.text('Unsupported format. Only JPG and PNG files are allowed.');
                errorMessageDiv.removeClass('d-none').addClass('show');
                this.value = '';
                console.warn('Photo validation failed: unsupported format', file.type);
                return;
            }

            // Check file size
            if (file.size > maxSizeInBytes) {
                errorText.text('File size exceeds limit. Maximum file size allowed is 2 MB.');
                errorMessageDiv.removeClass('d-none').addClass('show');
                this.value = '';
                console.warn('Photo validation failed: file size exceeded', file.size);
                return;
            }

            // Valid file — hide previous photo and show new preview
            const reader = new FileReader();
            reader.onload = function (event) {
                try {
                    $('#previousPhotoWrapper').addClass('d-none');
                    photoPreview
                        .attr('src', event.target.result)
                        .removeClass('d-none');
                } catch (err) {
                    console.warn('Photo preview error:', err);
                }
            };
            reader.readAsDataURL(file);
        } catch (err) {
            console.error('Photo validation error:', err);
        }
    });
    // ======= END PHOTO VALIDATION =======
});
</script>
@endsection
