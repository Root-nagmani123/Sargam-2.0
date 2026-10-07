@extends('admin.layouts.master')

@section('title', 'Member - Sargam | Lal Bahadur')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/member-admin.css') }}?v={{ @filemtime(public_path('css/member-admin.css')) ?: time() }}">
@endpush

@section('setup_content')

    <div class="container-fluid member-admin-page member-form-page">

        <x-breadcrum title="Edit Member" />
        <x-session_message />

        <!-- start Vertical Steps Example -->
        <div>
            <div>
                <input type="hidden" name="emp_id" id="emp_id" value="{{ ($member->pk) ?? '' }}">
                <form id="member-form" enctype="multipart/form-data">
                    @csrf
                    <div id="wizard" class="wizard clearfix vertical"
                        data-finish-label="Update Employee"
                        data-cancel-url="{{ route('member.index') }}">
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
        <script src="{{ asset('js/member-wizard.js') }}?v={{ @filemtime(public_path('js/member-wizard.js')) ?: time() }}"></script>
        <script>
            // Seeded server-side — the only source of the PK for this edit session.
            // Per-step validation no longer returns a pk (nothing is saved until Finish),
            // so onFinished() below relies on this to tell the server which record to update.
            let employeePK = {{ $member->pk ?? 'null' }};

            $(document).ready(function () {
                const form = $("#member-form");
                const loadedSteps = {};
                let formIsDirty = false;

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
                                formIsDirty = false;
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

                // Rail numbering + the Cancel / Update Employee pair. Presentation
                // only — the wizard above still owns validation and submit.
                window.MemberWizardUI.attach('#wizard');

                // --- Unsaved changes protection ---
                // Any edit inside the wizard (including fields loaded later via AJAX,
                // since this listener is delegated) marks the form dirty. Programmatic
                // pre-filling of fields (loadStepContent below) doesn't fire input/change,
                // so loading existing data doesn't falsely trip this.
                form.on('input change', ':input', function () {
                    formIsDirty = true;
                });

                // Reload / close tab / browser back-forward: browsers show their own
                // built-in message and ignore any custom text we set here.
                window.addEventListener('beforeunload', function (e) {
                    if (formIsDirty) {
                        e.preventDefault();
                        e.returnValue = '';
                    }
                });

                // In-app navigation (sidebar/menu links, breadcrumbs, etc.): show a
                // friendlier confirmation instead of the plain browser dialog.
                $(document).on('click', 'a[href]:not([href^="#"]):not([href^="javascript:"])', function (e) {
                    if (!formIsDirty) return;
                    if (e.which === 2 || e.ctrlKey || e.metaKey || e.shiftKey || $(this).attr('target') === '_blank') return;

                    const link = this;
                    e.preventDefault();

                    Swal.fire({
                        title: 'Details not saved yet!',
                        text: "You've changed some information on this page that hasn't been saved. If you leave now, it will be lost. Do you still want to leave?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, leave without saving',
                        cancelButtonText: 'Stay on this page'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            formIsDirty = false;
                            window.location.href = link.href;
                        }
                    });
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
                    // The server validates the UNION of all five steps (MemberController::
                    // combinedMemberRules), so a final-submit 422 can name a field that is not
                    // on the step we were handed.
                    //
                    // jQuery Steps keeps every step body in the DOM but HIDES all but the
                    // current one - jquery.steps.min.js K() calls _showAria(currentIndex === d),
                    // and _showAria(false) is this.hide()._aria("hidden","true"), i.e. inline
                    // display:none PLUS aria-hidden="true". So widening the search is not
                    // enough on its own: a message rendered into another step is invisible on
                    // screen and withheld from assistive technology as well.
                    //
                    // Three things therefore have to happen, and the last two are what make
                    // the message actually reach the user:
                    //   1. render each message next to its own field, wherever that field is;
                    //   2. walk the wizard back to the earliest step carrying an error, so
                    //      those messages become visible;
                    //   3. summarise anything still off-screen in a toast, so a failure can
                    //      never be silent even if navigation is refused mid-transition.
                    var wizard = $("#wizard");
                    var scope = wizard.length ? wizard : stepElement;
                    var unseen = [];
                    var firstErrorIndex = null;

                    clearErrors(scope);

                    $.each(errors, function (field, messages) {
                        var message = messages[0];
                        // The "[]" form catches array inputs such as userrole[] on step 3,
                        // whose error key arrives as "userrole" or "userrole.0".
                        var base = String(field).split(".")[0];
                        var input = stepElement.find('[name="' + field + '"]');
                        if (!input.length) input = scope.find('[name="' + field + '"]');
                        if (!input.length) input = scope.find('[name="' + base + '"]');
                        if (!input.length) input = scope.find('[name="' + base + '[]"]');
                        if (!input.length) {
                            // No field on the page owns this message - a toast is the only
                            // place it can go.
                            unseen.push(message);
                            return;
                        }

                        var target = input.first();
                        target.addClass("is-invalid")
                              .after($('<div class="text-danger mt-1"></div>').text(message));

                        if (!target.is(":visible")) {
                            var idx = stepIndexOf(target);
                            if (idx !== null && (firstErrorIndex === null || idx < firstErrorIndex)) {
                                firstErrorIndex = idx;
                            }
                            var title = stepTitleAt(idx);
                            unseen.push(title ? title + " - " + message : message);
                        }
                    });

                    // Bring the earliest offending step into view. The messages are already
                    // rendered there, so this turns them from present-but-hidden into visible.
                    if (firstErrorIndex !== null && wizard.length) {
                        goToStep(wizard, firstErrorIndex);
                    }

                    if (unseen.length && window.toastr) {
                        // escapeHtml is off in this toastr build (escapeHtml: !1), so the
                        // message would otherwise be inserted as HTML. Force escaping and keep
                        // the separator plain text rather than <br>.
                        toastr.error(unseen.join(" \u00b7 "), "Please correct the highlighted fields",
                            { escapeHtml: true });
                    }
                }

                /**
                 * Index of the wizard step that owns an element, or null.
                 * jQuery Steps re-ids each step body as #wizard-p-{n} while leaving the
                 * original <section> element (and its step-section class) in place.
                 */
                function stepIndexOf($el) {
                    var id = $el.closest("section").attr("id") || "";
                    var m = /wizard-p-(\d+)/.exec(id);
                    return m ? parseInt(m[1], 10) : null;
                }

                /** Human label for a step, read from the rail the plugin builds. */
                function stepTitleAt(index) {
                    if (index === null) return "";
                    var a = $("#wizard").children(".steps").find("> ul > li").eq(index).find("a").first();
                    if (!a.length) return "";
                    var clone = a.clone();
                    clone.find(".number, .current-info").remove();
                    return $.trim(clone.text());
                }

                /**
                 * Step the wizard back to targetIndex.
                 *
                 * steps("setStep") is NOT usable - this build defines it as
                 * throw new Error("Not yet implemented!") - so move one step at a time with
                 * previous(). onStepChanging returns true immediately when newIndex <
                 * currentIndex, so going backwards never re-validates.
                 *
                 * If a call is refused (a slide transition still in flight) the index does not
                 * change; stop rather than spin. The toast has already been prepared, so the
                 * user is told either way.
                 */
                function goToStep($wizard, targetIndex) {
                    for (var guard = 0; guard < 20; guard++) {
                        var current;
                        try {
                            current = $wizard.steps("getCurrentIndex");
                        } catch (e) {
                            // Fall back to the rail's own state if the plugin isn't ready.
                            current = $wizard.children(".steps").find("> ul > li").index($wizard.find("li.current"));
                        }
                        if (current === null || current <= targetIndex) return;
                        $wizard.steps("previous");
                        var moved;
                        try {
                            moved = $wizard.steps("getCurrentIndex");
                        } catch (e) {
                            return;
                        }
                        if (moved === current) return;
                    }
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
