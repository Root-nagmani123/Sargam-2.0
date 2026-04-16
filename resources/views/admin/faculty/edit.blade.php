@extends('admin.layouts.master')

@section('title', 'Edit Faculty')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="Faculty" />

    <!-- start Vertical Steps Example -->
    {{-- id="facultyForm" data-store-url="{{ route('faculty.update') }}"
        data-index-url="{{ route('faculty.index') }} --}}

        <form class="facultyForm">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Personal Information</h4>
                    <hr>
                    <input type="hidden" name="faculty_id" value="{{ $faculty->pk }}">
                    @include('admin.faculty.components.basicInfo')
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Qualification Details</h4>
                    <hr>
                    @include('admin.faculty.components.degree')
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Experience Details</h4>
                    <hr>
                    @include('admin.faculty.components.experienceDetails')
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Bank Details</h4>
                    <hr>
                    @include('admin.faculty.components.bankDetails')
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Other information</h4>
                    <hr>
                    @include('admin.faculty.components.researchPublication')
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="row">
                            <div class="col-12">
                                <label for="sector" class="form-label">Current Sector :</label>
                                <div class="mb-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input success" type="radio" name="current_sector"
                                            id="success-radio" value="1" {{ $faculty->faculty_sector == 1 ? 'checked' : '' }}>
                                        <label class="form-check-label" for="success-radio">Government Sector</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input success" type="radio" name="current_sector"
                                            id="success2-radio" value="2" {{ $faculty->faculty_sector == 2 ? 'checked' : '' }}>
                                        <label class="form-check-label" for="success2-radio">Private Sector</label>
                                    </div>
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
                        <div class="mb-3">
                            <button class="btn btn-primary hstack gap-6 float-end" type="button" id="saveFacultyForm">
                                <i class="material-icons menu-icon">save</i>
                                Update
                            </button>
                            <a href="{{ route('faculty.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
                                <i class="material-icons menu-icon">arrow_back</i>
                                Back
                            </a>
                        </div>
                </div>
            </div>
        </form>
    <!-- end Vertical Steps Example -->
</div>


@endsection

@section('scripts')
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
});
</script>
@endsection
