@extends('admin.layouts.master')

@section('title', 'Edit Faculty')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/faculty-wizard-admin.css') }}?v={{ @filemtime(public_path('css/faculty-wizard-admin.css')) ?: time() }}">
@endpush

@section('setup_content')

<div class="container-fluid faculty-wizard-page pb-4">
    <x-breadcrum title="Faculty" />
    <x-session_message />

    <div class="fw-wizard-shell">
        <form class="facultyForm">
            <input type="hidden" name="faculty_id" id="faculty_id" value="{{ $faculty->pk }}">
            <button type="button" id="saveFacultyForm" class="visually-hidden" tabindex="-1" aria-hidden="true">Update</button>

            <div class="fw-wizard-native" id="facultyWizard">
                <aside class="fw-step-nav" aria-label="Faculty form progress">
                    <ul class="fw-step-nav-list list-unstyled mb-0">
                        <li class="fw-step-nav-item is-active" data-goto-step="0">
                            <span class="fw-step-nav-marker" aria-hidden="true">1</span>
                            <span class="fw-step-nav-label">Personal Information</span>
                        </li>
                        <li class="fw-step-nav-item is-pending" data-goto-step="1">
                            <span class="fw-step-nav-marker" aria-hidden="true">2</span>
                            <span class="fw-step-nav-label">Qualifications Details</span>
                        </li>
                        <li class="fw-step-nav-item is-pending" data-goto-step="2">
                            <span class="fw-step-nav-marker" aria-hidden="true">3</span>
                            <span class="fw-step-nav-label">Experience Details</span>
                        </li>
                        <li class="fw-step-nav-item is-pending" data-goto-step="3">
                            <span class="fw-step-nav-marker" aria-hidden="true">4</span>
                            <span class="fw-step-nav-label">Bank Details</span>
                        </li>
                        <li class="fw-step-nav-item is-pending" data-goto-step="4">
                            <span class="fw-step-nav-marker" aria-hidden="true">5</span>
                            <span class="fw-step-nav-label">Other information</span>
                        </li>
                    </ul>
                </aside>

                <div class="fw-wizard-panel">
                <div class="fw-step-pane is-active" id="fw-step-1" data-step="0">
                    @include('admin.faculty.components.basicInfo')
                </div>

                <div class="fw-step-pane d-none" id="fw-step-2" data-step="1">
                    <div class="fw-step-panel rounded-3 p-3 p-md-4">
                        @include('admin.faculty.components.degree')
                    </div>
                </div>

                <div class="fw-step-pane d-none" id="fw-step-3" data-step="2">
                    <div class="fw-step-panel rounded-3 p-3 p-md-4">
                        @include('admin.faculty.components.experienceDetails')
                    </div>
                </div>

                <div class="fw-step-pane d-none" id="fw-step-4" data-step="3">
                    @include('admin.faculty.components.bankDetails')
                </div>

                <div class="fw-step-pane d-none" id="fw-step-5" data-step="4">
                    @include('admin.faculty.components.researchPublication')

                    <div class="row fw-step-grid g-3">
                        <div class="col-12">
                            <label for="sector" class="form-label">Current Sector : <span class="text-danger">*</span></label>
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
                            <div class="mb-3 expertise-row fw-expertise-grid">
                                @if(!empty($faculties))
                                <fieldset>
                                    <div class="row g-3">
                                        @foreach ($faculties as $key => $option)
                                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                            <div class="form-check py-2">
                                                <input type="checkbox" name="faculties[]" value="{{ $key }}"
                                                    class="form-check-input" id="faculty_expertise_{{ $loop->index }}"
                                                    {{ in_array($key, $facultExpertise) ? 'checked' : '' }}>
                                                <label class="form-check-label"
                                                    for="faculty_expertise_{{ $loop->index }}">{{ $option }}</label>
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
                </div>

                <div class="fw-wizard-actions d-flex flex-wrap justify-content-end align-items-center gap-2">
                    <a href="{{ route('faculty.index') }}" class="btn btn-outline-primary px-4">Cancel</a>
                    <button type="button" class="btn btn-primary px-4" id="fwWizardPrimaryBtn">Save &amp; Next</button>
                </div>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var totalSteps = 5;
    var currentStep = 0;

    function updateStepNav() {
        $('.fw-step-nav-item').each(function () {
            var index = parseInt($(this).attr('data-goto-step'), 10);
            $(this).removeClass('is-active is-done is-pending');
            if (index < currentStep) {
                $(this).addClass('is-done');
            } else if (index === currentStep) {
                $(this).addClass('is-active');
            } else {
                $(this).addClass('is-pending');
            }
        });
    }

    function showStep(stepIndex) {
        if (stepIndex < 0 || stepIndex >= totalSteps) {
            return;
        }
        currentStep = stepIndex;
        $('.fw-step-pane').addClass('d-none').removeClass('is-active');
        $('.fw-step-pane[data-step="' + stepIndex + '"]').removeClass('d-none').addClass('is-active');
        updateStepNav();
        $('#fwWizardPrimaryBtn').text(stepIndex === totalSteps - 1 ? 'Update Faculty' : 'Save & Next');
    }

    $('#fwWizardPrimaryBtn').on('click', function () {
        if (currentStep < totalSteps - 1) {
            showStep(currentStep + 1);
            return;
        }
        $('#saveFacultyForm').trigger('click');
    });

    $('.fw-step-nav-list').on('click', '.fw-step-nav-item.is-done', function () {
        var target = parseInt($(this).attr('data-goto-step'), 10);
        if (!isNaN(target)) {
            showStep(target);
        }
    });

    showStep(0);

    $('#saveFacultyForm').on('click', function () {
        setTimeout(function () {
            var $paneWithError = $('.fw-step-pane').filter(function () {
                return $(this).find('.is-invalid').length > 0;
            }).first();
            if ($paneWithError.length) {
                var step = parseInt($paneWithError.attr('data-step'), 10);
                if (!isNaN(step)) {
                    showStep(step);
                }
            }
        }, 100);
    });
});
</script>
@endpush

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
            $('input[name="faculty_pa"]').val(''); // Clear the field when hidden
        }
    }

    // Initial check on page load
    toggleFacultyPaField();

    // Listen for changes on faculty type dropdown
    $('select[name="facultytype"]').on('change', function() {
        toggleFacultyPaField();
    });

});
</script>
@endsection
