@extends('admin.layouts.master')

@section('title', 'Member - Sargam | Lal Bahadur')

@section('setup_content')

<div class="container-fluid">

    <x-breadcrum title="Member" />
    <x-session_message />

    <!-- Add Member Wizard -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white border-0 py-4 px-4">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="text-primary" viewBox="0 0 16 16">
                        <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="card-title mb-1 fw-semibold text-dark">Add Member</h4>
                    <p class="text-body-secondary small mb-0">Complete the steps below to register a new member.</p>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <form id="member-form" enctype="multipart/form-data">
                @csrf
                <div id="wizard" class="wizard clearfix vertical mt-2">
                    <h3 class="fw-semibold text-body">Member Information</h3>
                    <section id="step-1" class="step-section">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="text-body-secondary mt-3 small">Loading step...</p>
                        </div>
                    </section>

                    <h3 class="fw-semibold text-body">Employment Details</h3>
                    <section id="step-2" class="step-section"></section>

                    <h3 class="fw-semibold text-body">Role Assignment</h3>
                    <section id="step-3" class="step-section"></section>

                    <h3 class="fw-semibold text-body">Contact Information</h3>
                    <section id="step-4" class="step-section"></section>

                    <h3 class="fw-semibold text-body">Additional Details</h3>
                    <section id="step-5" class="step-section"></section>
                </div>
            </form>
        </div>
    </div>

    <style>
        /* Modern wizard step list (jQuery Steps vertical) */
        #wizard.wizard.vertical > .steps { padding-right: 1.5rem; }
        #wizard.wizard.vertical .steps ul { list-style: none; padding: 0; margin: 0; }
        #wizard.wizard.vertical .steps li { position: relative; padding: 0.6rem 0 0.6rem 2.25rem; }
        #wizard.wizard.vertical .steps li .number { position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 1.75rem; height: 1.75rem; border-radius: 50%; background: var(--bs-secondary-bg); color: var(--bs-secondary-color); font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; justify-content: center; transition: background .2s, color .2s; }
        #wizard.wizard.vertical .steps li.current .number { background: var(--bs-primary); color: #fff; }
        #wizard.wizard.vertical .steps li.done .number { background: var(--bs-success); color: #fff; }
        #wizard.wizard.vertical .steps li a { color: inherit; text-decoration: none; font-size: 0.9375rem; }
        #wizard.wizard.vertical .steps li.current a { color: #ffffff; font-weight: 600; }
        #wizard.wizard.vertical .content { min-height: 200px; border: 1px solid var(--bs-border-color); border-radius: 0.5rem; padding: 1.25rem 1.5rem; background: var(--bs-body-bg); }
        #wizard.wizard.vertical .actions { padding-top: 1.25rem; }
        #wizard.wizard.vertical .actions a, #wizard.wizard.vertical .actions input { border-radius: 0.375rem; font-weight: 500; padding: 0.5rem 1rem; }
        #wizard.wizard.vertical .actions .disabled a { opacity: 0.6; cursor: not-allowed; }
    </style>
</div>

@section('scripts')
<script>
let employeePK = null;

$(document).ready(function() {
    const form = $("#member-form");
    const loadedSteps = {};

    const wizard = $("#wizard").steps({
        headerTag: "h3",
        bodyTag: "section",
        transitionEffect: "slideLeft",
        stepsOrientation: "vertical",
        autoFocus: true,
        enablePagination: true,

        onStepChanging: function(event, currentIndex, newIndex) {
            if (newIndex < currentIndex) return true;
            event.preventDefault();

            const currentStep = $(`#wizard-p-${currentIndex}`);
            let stepData = currentStep.find(':input').serialize();

            if (employeePK) {
                stepData += `&emp_id=${employeePK}`;
            }

            let canProceed = false;

            $.ajax({
                url: "{{ url('member/validate-step') }}/" + (currentIndex + 1),
                method: "POST",
                data: stepData + '&_token={{ csrf_token() }}',
                async: false,
                success: function(response) {
                    if (response.pk) {
                        employeePK = response.pk;

                        // Add hidden employeePK to all sections
                        $(".wizard section").each(function() {
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
                error: function(xhr) {
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

        onStepChanged: function(event, currentIndex, priorIndex) {
            const stepNumber = currentIndex + 1;
            loadStepContent(stepNumber);
        },

        onFinishing: function() {
            return true;
        },

        onFinished: function() {
            const formData = new FormData(form[0]);
            if (employeePK) {
                formData.append('emp_id', employeePK);
            }

            $.ajax({
                url: "{{ route('member.store') }}",
                method: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function() {
                    toastr.success("Member created successfully!");
                    window.location.href = "/member";
                },
                error: function(xhr) {
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

    function loadStepContent(stepNumber) {
        if (loadedSteps[stepNumber]) return;

        const stepSection = $(`#wizard-p-${stepNumber - 1}`);

        $.ajax({
            url: "{{ route('member.load-step', ['step' => 'STEP_NUM']) }}".replace('STEP_NUM', stepNumber),
            method: "GET",
            success: function(html) {
                stepSection.html(html);

                // Append employeePK if needed
                if (employeePK && !stepSection.find('#employeePK').length) {
                    stepSection.append(
                        `<input type="hidden" id="employeePK" name="emp_id" value="${employeePK}">`
                        );
                }

                loadedSteps[stepNumber] = true;
            },
            error: function(xhr) {
                toastr.error(`Failed to load step ${stepNumber} (HTTP ${xhr.status})`);
                stepSection.html(
                    `<div class="alert alert-danger">Failed to load step ${stepNumber}</div>`);
            }
        });
    }

    function showErrors(stepElement, errors) {
        clearErrors(stepElement);
        $.each(errors, function(field, messages) {
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

    // Initial load
    loadStepContent(1);
});
</script>


@endsection
@endsection