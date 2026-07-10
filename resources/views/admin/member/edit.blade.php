@extends('admin.layouts.master')

@section('title', 'Member - Sargam | Lal Bahadur')

@section('setup_content')

    <div class="container-fluid">

        <x-breadcrum title="Member" />
        <x-session_message />

        <!-- start Vertical Steps Example -->
        <div class="card" >
            <div class="card-body">
                <h4 class="card-title mb-0">Edit Member</h4>
                <h6 class="card-subtitle mb-3"></h6>
                <hr>
                <input type="hidden" name="emp_id" id="emp_id" value="{{ ($member->pk) ?? '' }}">
                <form id="member-form" enctype="multipart/form-data">
                    @csrf
                    <div id="wizard" class="wizard clearfix vertical">
                        <h3>Member Information</h3>
                        <section id="step-1" class="step-section">
                            <!-- Content will be loaded via AJAX -->
                            <div class="text-center py-5">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </section>

                        <h3>Employment Details</h3>
                        <section id="step-2" class="step-section">
                            <!-- Content will be loaded via AJAX -->
                        </section>

                        <h3>Role Assignment</h3>
                        <section id="step-3" class="step-section">
                            <!-- Content will be loaded via AJAX -->
                        </section>

                        <h3>Contact Information</h3>
                        <section id="step-4" class="step-section">
                            <!-- Content will be loaded via AJAX -->
                        </section>

                        <h3>Additional Details</h3>
                        <section id="step-5" class="step-section">
                            <!-- Content will be loaded via AJAX -->
                        </section>
                    </div>
                </form>

            </div>
        </div>
        <!-- end Vertical Steps Example -->
    </div>

    @push('scripts')
        <script>
            // Seeded server-side — the only source of the PK for this edit session.
            // Per-step validation no longer returns a pk (nothing is saved until Finish),
            // so onFinished() below relies on this to tell the server which record to update.
            let employeePK = {{ $member->pk ?? 'null' }};

            $(document).ready(function () {
                const form = $("#member-form");
                const loadedSteps = {};

                const wizard = $("#wizard").steps({
                    headerTag: "h3",
                    bodyTag: "section",
                    transitionEffect: "slideLeft",
                    stepsOrientation: "vertical",
                    autoFocus: true,
                    enablePagination: true,

                    onStepChanging: function (event, currentIndex, newIndex) {
                        if (newIndex < currentIndex) return true;
                        event.preventDefault();

                        const currentStep = $(`#wizard-p-${currentIndex}`);
                        let stepData = currentStep.find(':input').serialize();
                        stepData += `&emp_id=${employeePK}`;

                        let canProceed = false;

                        // Validates this step's fields only — nothing is written to the
                        // database yet. The record is updated in one shot from onFinished().
                        $.ajax({
                            url: `/member/update-validate-step/${currentIndex + 1}/${employeePK}`,
                            method: "POST",
                            data: stepData + '&_token={{ csrf_token() }}',
                            async: false,
                            success: function (success) {
                                clearErrors(currentStep);
                                canProceed = true;
                            },
                            error: function (xhr) {
                                const status = xhr.status;
                                const errors = xhr.responseJSON?.errors || {};

                                if (status === 422) {
                                    showErrors(currentStep, errors);
                                } else {
                                    toastr.error(xhr.responseJSON?.message || `Unexpected error (${status}) occurred.`);
                                }

                                canProceed = false;
                            }
                        });

                        return canProceed;
                    },

                    onStepChanged: function (event, currentIndex, priorIndex) {
                        const stepNumber = currentIndex + 1;
                        loadStepContent(stepNumber);
                    },

                    onFinishing: function () {
                        return true;
                    },

                    onFinished: function () {
                        // All 5 steps' inputs are still in the DOM (jQuery Steps never
                        // removes them), so this FormData already carries every field from
                        // every step — this is the single point where the record is saved.
                        const formData = new FormData(form[0]);
                        formData.append('emp_id', employeePK);

                        $.ajax({
                            url: "{{ route('member.update') }}",
                            method: "POST",
                            data: formData,
                            contentType: false,
                            processData: false,
                            success: function () {
                                alert("Member updated successfully!");
                                window.location.href = "/member";
                            },
                            error: function (xhr) {
                                const status = xhr.status;
                                const errors = xhr.responseJSON?.errors || {};

                                if (status === 422) {
                                    const lastStep = $(".wizard .step-section").last();
                                    showErrors(lastStep, errors);
                                } else {
                                    toastr.error(xhr.responseJSON?.message || `Unexpected error (${status}) occurred.`);
                                }
                            }
                        });
                    }
                });

                function loadStepContent(stepNumber) {
                    if (loadedSteps[stepNumber]) return;

                    const stepSection = $(`#wizard-p-${stepNumber - 1}`);

                    $.ajax({
                        url: `/member/edit-step/${stepNumber}/${employeePK}`,
                        method: "GET",
                        success: function (html) {
                            stepSection.html(html);
                            loadedSteps[stepNumber] = true;
                        },
                        error: function (xhr) {
                            toastr.error(`Failed to load step ${stepNumber} (HTTP ${xhr.status})`);
                            stepSection.html(`<div class="alert alert-danger">Failed to load step ${stepNumber}</div>`);
                        }
                    });
                }

                function showErrors(stepElement, errors) {
                    clearErrors(stepElement);
                    $.each(errors, function (field, messages) {
                        const input = stepElement.find(`[name="${field}"]`);
                        const message = messages[0];
                        const errorDiv = $('<div class="text-danger mt-1"></div>').text(message);
                        input.addClass("is-invalid").after(errorDiv);
                    });
                }

                function clearErrors(stepElement) {
                    stepElement.find("div.text-danger").remove();
                    stepElement.find("div.validation-error").remove();
                    stepElement.find(".is-invalid").removeClass("is-invalid");
                }

                // Initial step loads
                loadStepContent(1);
                $("#wizard .steps li").removeClass('disabled').addClass("done");
                $("#wizard .steps li:nth-child(1)").removeClass('done');
            });
        </script>

@endpush

    @endsection
