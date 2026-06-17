@extends('admin.layouts.master')

@section('title', 'Edit Member - Sargam | Lal Bahadur')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/member-wizard-admin.css') }}?v={{ @filemtime(public_path('css/member-wizard-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid member-wizard-page pb-4">
    <x-breadcrum title="Edit Employee Master" />

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
                <section id="step-2" class="step-section"></section>

                <h3>Role Assignment</h3>
                <section id="step-3" class="step-section"></section>

                <h3>Contact Information</h3>
                <section id="step-4" class="step-section"></section>

                <h3>Additional Details</h3>
                <section id="step-5" class="step-section"></section>
            </div>
        </form>
    </div>
</div>
@endsection

    @push('scripts')
        <script>
            let employeePK = null;

$(document).ready(function () {
    const form = $("#member-form");
    const loadedSteps = {};

    $("#wizard").steps({
        headerTag: "h3",
        bodyTag: "section",
        transitionEffect: "slideLeft",
        stepsOrientation: "vertical",
        autoFocus: true,
        enablePagination: true,
        labels: {
            finish: "Update Employee",
            next: "Next",
            previous: "Previous",
            loading: "Loading..."
        },

        onStepChanging: function (event, currentIndex, newIndex) {
            if (newIndex < currentIndex) {
                return true;
            }
            event.preventDefault();

            const currentStep = $(`#wizard-p-${currentIndex}`);
            let stepData = currentStep.find(':input').serialize();

            if (employeePK) {
                stepData += `&emp_id=${employeePK}`;
            }

            let canProceed = false;

            $.ajax({
                url: `/member/update-validate-step/${currentIndex + 1}/${employeePK || $('#emp_id').val()}`,
                method: "POST",
                data: stepData + '&_token={{ csrf_token() }}',
                async: false,
                success: function (response) {
                    if (response.pk) {
                        employeePK = response.pk;

                        $(".wizard section").each(function () {
                            const section = $(this);
                            const existingInput = section.find('#employeePK');
                            if (!existingInput.length) {
                                section.append(
                                    `<input type="hidden" id="employeePK" name="emp_id" value="${employeePK}">`
                                );
                            } else {
                                existingInput.val(employeePK);
                            }
                        });
                    }

                    clearErrors(currentStep);
                    canProceed = true;
                },
                error: function (xhr) {
                    const status = xhr.status;
                    const errors = xhr.responseJSON?.errors || {};

                    if (status === 422) {
                        showErrors(currentStep, errors);
                    } else {
                        toastr.error(xhr.responseJSON?.message ||
                            `Error (${status}) occurred while validating step.`);
                    }

                    canProceed = false;
                }
            });

            return canProceed;
        },

        onStepChanged: function (event, currentIndex, priorIndex) {
            loadStepContent(currentIndex + 1);
            styleWizardActions();
        },

        onFinishing: function () {
            return true;
        },

        onFinished: function () {
            const formData = new FormData(form[0]);
            if (employeePK) {
                formData.append('emp_id', employeePK);
            }

            $.ajax({
                url: "{{ route('member.update') }}",
                method: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function () {
                    toastr.success("Member updated successfully!");
                    window.location.href = "/member";
                },
                error: function (xhr) {
                    const status = xhr.status;
                    const errors = xhr.responseJSON?.errors || {};
                    const lastStep = $(".wizard .step-section").last();

                    if (status === 422) {
                        showErrors(lastStep, errors);
                    } else {
                        toastr.error(xhr.responseJSON?.message ||
                            `Error (${status}) occurred while submitting.`);
                    }
                }
            });
        }
    });

    $('#wizard').on('click', '.steps ul li.done a', function (e) {
        e.preventDefault();
        const targetIndex = $(this).parent().index();
        $('#wizard').steps('setCurrentStep', targetIndex);
    });

    function styleWizardActions() {
        const $actionsList = $('#wizard .actions ul');
        if (!$actionsList.find('.mw-wizard-cancel-li').length) {
            $actionsList.prepend(
                '<li class="mw-wizard-cancel-li"><a href="#" class="mw-wizard-cancel" role="button">Cancel</a></li>'
            );
            $actionsList.find('.mw-wizard-cancel').on('click', function (e) {
                e.preventDefault();
                window.location.href = "{{ route('member.index') }}";
            });
        }

        const $cancel = $actionsList.find('.mw-wizard-cancel-li');
        const $nextLi = $actionsList.find('li').filter(function () {
            return $(this).find('a[href="#next"], a[href="#finish"]').length;
        }).first();
        if ($cancel.length && $nextLi.length) {
            $cancel.insertBefore($nextLi);
        }
    }

    function loadStepContent(stepNumber) {
        if (loadedSteps[stepNumber]) {
            return;
        }

        const stepSection = $(`#wizard-p-${stepNumber - 1}`);

        $.ajax({
            url: `/member/edit-step/${stepNumber}/${employeePK || $('#emp_id').val()}`,
            method: "GET",
            success: function (html) {
                stepSection.html(html);

                if (employeePK && !stepSection.find('#employeePK').length) {
                    stepSection.append(
                        `<input type="hidden" id="employeePK" name="emp_id" value="${employeePK}">`
                    );
                }

                loadedSteps[stepNumber] = true;
            },
            error: function (xhr) {
                toastr.error(`Failed to load step ${stepNumber} (HTTP ${xhr.status})`);
                stepSection.html(
                    `<div class="alert alert-danger mb-0">Failed to load step ${stepNumber}</div>`
                );
            }
        });
    }

    function showErrors(stepElement, errors) {
        clearErrors(stepElement);
        $.each(errors, function (field, messages) {
            const $input = stepElement.find(`[name="${field}"], [name="${field}[]"]`).first();
            if (!$input.length) {
                return;
            }
            const message = messages[0];
            $('<div class="text-danger mt-1"></div>').text(message).insertAfter($input);
            $input.addClass("is-invalid");
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
