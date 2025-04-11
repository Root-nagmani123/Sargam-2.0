@extends('admin.layouts.master')

@section('title', 'Member - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    
    <x-breadcrum title="Member" />
    <x-session_message />

    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-0">Add Member</h4>
            <h6 class="card-subtitle mb-3"></h6>
            <hr>

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

@section('scripts')
<script>
    $(document).ready(function () {
    const form = $("#member-form");
    const loadedSteps = {}; // to track which steps are loaded

    const wizard = $("#wizard").steps({
        headerTag: "h3",
        bodyTag: "section",
        transitionEffect: "slideLeft",
        stepsOrientation: "vertical",
        autoFocus: true,
        enablePagination: true,
        onStepChanging: function (event, currentIndex, newIndex) {
            // Always allow going back
            if (newIndex < currentIndex) return true;

            // Prevent automatic transition
            event.preventDefault();
            
            const currentStep = $(`#wizard-p-${currentIndex}`);
            const stepData = currentStep.find(':input').serialize();
            
            // Use a synchronous AJAX call to validate before proceeding
            let canProceed = false;
            $.ajax({
                url: `/member/validate-step/${currentIndex + 1}`,
                method: "POST",
                data: stepData + '&_token={{ csrf_token() }}',
                async: false, // Make it synchronous
                success: function (success) {
                    clearErrors(currentStep);
                    canProceed = true;
                },
                error: function (xhr) {
                    const errors = xhr.responseJSON.errors || {};
                    showErrors(currentStep, errors);
                    canProceed = false;
                }
            });
            
            return canProceed; // Return true to allow navigation, false to prevent it
        },
        onStepChanged: function (event, currentIndex, priorIndex) {
            const stepNumber = currentIndex + 1;
            loadStepContent(stepNumber);
        },

        onFinishing: function () {
            return true;
        },

        onFinished: function () {
            const formData = new FormData(form[0]);
            $.ajax({
                url: "{{ route('member.store') }}",
                method: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function () {
                    alert("Member created successfully!");
                    window.location.href = "/admin/member";
                },
                error: function (xhr) {
                    const errors = xhr.responseJSON.errors || {};
                    const lastStep = $(".wizard .step-section").last();
                    showErrors(lastStep, errors);
                }
            });
        }
    });

    function loadStepContent(stepNumber) {
        if (loadedSteps[stepNumber]) return;

        // const stepSection = $("#step-" + stepNumber);

        const stepSection = $(`#wizard-p-${stepNumber-1}`);
        // stepSection.html(`
        //     <div class="text-center py-5">
        //         <div class="spinner-border" role="status">
        //             <span class="visually-hidden">Loading...</span>
        //         </div>
        //     </div>
        // `);

        $.ajax({
            url: `/member/step/${stepNumber}`, // Make sure this route exists
            method: "GET",
            success: function (html) {
                stepSection.html(html);
                loadedSteps[stepNumber] = true;
            },
            error: function () {
                stepSection.html(`<div class="alert alert-danger">Failed to load step ${stepNumber}</div>`);
            }
        });
    }

    function showErrors(stepElement, errors) {
        // console.log(errors);
        clearErrors(stepElement);
        $.each(errors, function (field, messageArray) {
            const input = stepElement.find(`[name="${field}"]`);
            const message = messageArray[0];
            const errorDiv = $('<div class="text-danger mt-1"></div>').text(message);
            input.addClass("is-invalid").after(errorDiv);
        });
    }

    function clearErrors(stepElement) {
        stepElement.find(".text-danger").remove();
        stepElement.find(".is-invalid").removeClass("is-invalid");
    }

    // Initial load of the first step
    loadStepContent(1);
});

    </script>
    
@endsection

@endsection