<script>
/* ──────────────────────────────────────────────────────────────
   Faculty add/edit wizard — client-side stepping only.
   Nothing is persisted until the final step's button, which is
   still #saveFacultyForm, the id custom.js binds its submit to.
   Shared by create + edit.
   ────────────────────────────────────────────────────────────── */
$(function () {
    if (!$('.ds-wizard-pane').length) { return; }

    var TOTAL_STEPS = $('.ds-wizard-pane').length;
    var currentStep = 1;
    var leaveModalEl = document.getElementById('facultyLeaveModal');
    var leaveModal = leaveModalEl ? bootstrap.Modal.getOrCreateInstance(leaveModalEl) : null;

    // Required fields per step. Step 1 mirrors the list custom.js enforces on
    // submit, so those errors surface here instead of inside a hidden pane.
    var STEP_RULES = {
        1: [
            { sel: 'select[name="facultytype"]', label: 'Faculty Type' },
            { sel: 'select[name="appellation"]', label: 'Appellation' },
            { sel: 'input[name="firstName"]',    label: 'First Name' },
            { sel: 'input[name="lastname"]',     label: 'Last Name' },
            { sel: 'input[name="fullname"]',     label: 'Full Name' },
            { sel: 'select[name="gender"]',      label: 'Gender' },
            { sel: 'input[name="mobile"]',       label: 'Mobile Number' },
            { sel: 'input[name="email"]',        label: 'Email' },
            { sel: 'select[name="country"]',     label: 'Country' },
            { sel: 'select[name="state"]',       label: 'State' },
            { sel: 'select[name="district"]',    label: 'District' },
            { sel: 'select[name="city"]',        label: 'City' }
        ],
        5: [
            { sel: 'select[name="current_sector"]', label: 'Current Sector' }
        ]
    };

    function $pane(step) {
        return $('.ds-wizard-pane[data-step="' + step + '"]');
    }

    function clearStepErrors($scope) {
        $scope.find('.is-invalid').removeClass('is-invalid');
        $scope.find('span.faculty-error-msg').remove();
    }

    function validateStep(step) {
        var rules = STEP_RULES[step];
        if (!rules) { return true; }

        var $scope = $pane(step);
        clearStepErrors($scope);

        var ok = true;
        rules.forEach(function (rule) {
            var $el = $scope.find(rule.sel);
            // Skip fields that aren't on this pane or are hidden by other logic
            // (e.g. Faculty (PA) only shows for Internal).
            if (!$el.length || $el.closest('.d-none').length) { return; }

            var val = $el.val();
            if (!val || !String(val).trim()) {
                $el.addClass('is-invalid');
                $el.after('<span class="text-danger faculty-error-msg mt-1 d-block">' + rule.label + ' is required.</span>');
                ok = false;
            }
        });

        if (!ok) {
            var $first = $scope.find('.is-invalid').first();
            if ($first.length) {
                $('html, body').animate({ scrollTop: $first.offset().top - 150 }, 300);
                $first.trigger('focus');
            }
        }
        return ok;
    }

    function showStep(step, opts) {
        opts = opts || {};
        step = Math.min(Math.max(step, 1), TOTAL_STEPS);
        currentStep = step;

        $('.ds-wizard-pane').removeClass('is-active');
        $pane(step).addClass('is-active');

        $('#facultyStepper .ds-step').each(function () {
            var idx = parseInt($(this).data('step-item'), 10);
            $(this).toggleClass('is-active', idx === step);
            // A step is "done" only once you've moved past it.
            $(this).toggleClass('is-done', idx < step);
        });

        if (opts.scroll !== false) {
            $('html, body').animate({ scrollTop: 0 }, 200);
        }
    }

    /* ── Save & Next ── */
    $(document).on('click', '.faculty-wizard-next', function () {
        if (!validateStep(currentStep)) { return; }
        showStep(currentStep + 1);
    });

    /* ── Stepper: allow jumping BACK to a completed step, never skipping ahead ── */
    $(document).on('click', '#facultyStepper .ds-step', function () {
        var target = parseInt($(this).data('step-item'), 10);
        if (target < currentStep) { showStep(target); }
    });

    /* ── Cancel → Leave the Form? ── */
    $(document).on('click', '.faculty-wizard-cancel', function () {
        if (leaveModal) {
            leaveModal.show();
        } else {
            window.location.href = "{{ route('faculty.index') }}";
        }
    });

    /* ── Safety net ──────────────────────────────────────────────
       custom.js flags invalid fields (its own required checks, and
       422 errors from the server) by adding .is-invalid. Any field
       in a non-active pane would be invisible, so jump to the first
       pane that has an error and scroll to it.
       ──────────────────────────────────────────────────────────── */
    function revealFirstError() {
        var $bad = $('.facultyForm .is-invalid').first();
        if (!$bad.length) { return false; }

        var $ownerPane = $bad.closest('.ds-wizard-pane');
        if (!$ownerPane.length) { return false; }

        var step = parseInt($ownerPane.data('step'), 10);
        if (step && step !== currentStep) {
            showStep(step, { scroll: false });
        }
        // Offsets are only meaningful once the pane is visible.
        $('html, body').animate({ scrollTop: $bad.offset().top - 150 }, 300);
        return true;
    }

    // Client-side errors: custom.js validates synchronously on click, so a
    // 0ms defer runs after it regardless of handler binding order.
    $(document).on('click', '#saveFacultyForm', function () {
        setTimeout(revealFirstError, 0);
    });

    // Server-side (422) errors: custom.js paints them after the POST resolves.
    $(document).ajaxComplete(function (evt, xhr, settings) {
        if (!settings || !settings.url) { return; }
        if (String(settings.url).indexOf('faculty') === -1) { return; }
        setTimeout(revealFirstError, 0);
    });

    showStep(1, { scroll: false });
});
</script>
