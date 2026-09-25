/* ============================================================================
 * Master module — step wizard for full-page forms (`.mst-wizard`)
 *
 * Turns a stack of `.mst-form-card[data-mst-step="N"]` cards into a stepped
 * form with a vertical rail. Deliberately presentational: it only toggles
 * visibility, so every field stays in the DOM and the page's existing single
 * submit still posts the whole form. No markup inside a card is touched, which
 * is what keeps the ~550 lines of field-level JS on these pages working.
 *
 * Markup contract
 *   .mst-wizard
 *     .mst-wizard__rail  > .mst-steps > .mst-step[data-step="N"]
 *     .mst-wizard__body  > .mst-form-card[data-mst-step="N"] (one or more)
 *   .mst-form-footer     — relocated into the last card of the active step
 *     [data-mst-next]    — "Save & Next", shown on every step but the last
 *     [data-mst-final]   — the real submit, shown on the last step only
 *
 * Options, as data-* on `.mst-wizard`
 *   data-mst-readonly    — view mode: no validation, no final submit
 * ========================================================================= */
(function () {
    'use strict';

    function init(wizard) {
        var steps = Array.prototype.slice.call(wizard.querySelectorAll('.mst-step[data-step]'));
        var cards = Array.prototype.slice.call(wizard.querySelectorAll('.mst-form-card[data-mst-step]'));
        if (!steps.length || !cards.length) {
            return;
        }

        var readOnly = wizard.hasAttribute('data-mst-readonly');
        var footer = wizard.querySelector('.mst-form-footer');
        var nextBtn = footer ? footer.querySelector('[data-mst-next]') : null;
        var finalBtn = footer ? footer.querySelector('[data-mst-final]') : null;
        var last = steps.length;
        var current = 0;

        function cardsFor(step) {
            return cards.filter(function (card) {
                return parseInt(card.getAttribute('data-mst-step'), 10) === step;
            });
        }

        function clearErrors(scope) {
            Array.prototype.forEach.call(scope.querySelectorAll('.is-invalid'), function (el) {
                el.classList.remove('is-invalid');
            });
            Array.prototype.forEach.call(scope.querySelectorAll('.mst-step-error'), function (el) {
                el.parentNode.removeChild(el);
            });
        }

        /**
         * Block "Save & Next" on a step whose required fields are empty. Only
         * the fields in this step are checked — the rest of the form is still
         * validated by the page's own submit handler.
         */
        function validate(step) {
            var ok = true;
            var firstBad = null;

            cardsFor(step).forEach(function (card) {
                clearErrors(card);

                Array.prototype.forEach.call(
                    card.querySelectorAll('input[required], select[required], textarea[required]'),
                    function (field) {
                        if (field.disabled || field.type === 'file' || field.closest('.d-none')) {
                            return;
                        }
                        if (String(field.value || '').trim() !== '') {
                            return;
                        }

                        ok = false;
                        field.classList.add('is-invalid');
                        firstBad = firstBad || field;

                        var labelEl = card.querySelector('label[for="' + field.id + '"]');
                        // Labels here read "Faculty Type :" — trim the trailing
                        // colon/asterisk (and the space before it) off the name.
                        var label = labelEl
                            ? labelEl.textContent.replace(/[\s:*]+$/, '').trim()
                            : 'This field';

                        var msg = document.createElement('span');
                        msg.className = 'text-danger mst-step-error mt-1 d-block';
                        msg.textContent = label + ' is required.';
                        field.parentNode.insertBefore(msg, field.nextSibling);
                    }
                );
            });

            if (firstBad) {
                firstBad.focus({ preventScroll: true });
                firstBad.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }

            return ok;
        }

        function show(step) {
            current = step;

            cards.forEach(function (card) {
                var owner = parseInt(card.getAttribute('data-mst-step'), 10);
                card.classList.toggle('is-active', owner === step);
            });

            steps.forEach(function (el) {
                var n = parseInt(el.getAttribute('data-step'), 10);
                el.classList.toggle('is-active', n === step);
                el.classList.toggle('is-done', n < step);
                el.setAttribute('aria-selected', n === step ? 'true' : 'false');
                el.setAttribute('tabindex', n === step ? '0' : '-1');
            });

            // The footer lives at the bottom of whatever card is showing.
            if (footer) {
                var visible = cardsFor(step);
                var host = visible.length ? visible[visible.length - 1].querySelector('.card-body') : null;
                if (host) {
                    host.appendChild(footer);
                }
                if (nextBtn) {
                    nextBtn.classList.toggle('d-none', step >= last);
                }
                // The submit stays reachable on EVERY step. Hiding it until the
                // last step made the form unsubmittable for any record that
                // cannot clear an earlier step's `required` fields — and those
                // attributes do not all match what the server enforces, so a
                // legacy row could be left with no way out. The page's own
                // save handler still validates the whole form, and the click
                // handler below brings the offending step forward.
                if (finalBtn) {
                    finalBtn.classList.remove('d-none');
                }
            }

            wizard.dispatchEvent(new CustomEvent('mst:step', { detail: { step: step }, bubbles: true }));
        }

        function goTo(step, validateForward) {
            if (step < 1 || step > last || step === current) {
                return;
            }
            // Going back is always allowed; going forward has to pass the step.
            if (validateForward && step > current && !readOnly && !validate(current)) {
                return;
            }
            show(step);
            wizard.scrollIntoView({ block: 'start', behavior: 'smooth' });
        }

        steps.forEach(function (el) {
            el.addEventListener('click', function () {
                goTo(parseInt(el.getAttribute('data-step'), 10), true);
            });
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    goTo(parseInt(el.getAttribute('data-step'), 10), true);
                }
            });
        });

        if (nextBtn) {
            nextBtn.addEventListener('click', function (e) {
                e.preventDefault();
                goTo(current + 1, true);
            });
        }

        // The page's own submit handler marks fields .is-invalid and then scrolls
        // to the first one — which would be inside a hidden step. Bring that step
        // forward so the user can actually see what it is complaining about.
        if (finalBtn && !readOnly) {
            finalBtn.addEventListener('click', function () {
                window.setTimeout(function () {
                    var bad = wizard.querySelector('.is-invalid');
                    if (!bad) {
                        return;
                    }
                    var card = bad.closest('.mst-form-card[data-mst-step]');
                    if (!card) {
                        return;
                    }
                    var step = parseInt(card.getAttribute('data-mst-step'), 10);
                    if (step !== current) {
                        show(step);
                    }
                    bad.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }, 0);
            });
        }

        show(1);
    }

    function boot() {
        Array.prototype.forEach.call(document.querySelectorAll('.mst-wizard'), init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
