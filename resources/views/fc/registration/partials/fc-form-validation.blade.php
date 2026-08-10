@php
    $fcServerValidationErrors = $errors->any() ? $errors->all() : [];
@endphp
{{--
    Validation feedback for the FC dynamic step forms.

    Presentation only — no validation rule is evaluated here beyond the "required" emptiness
    check that already existed. Server-side rules and the controller are untouched.

    Errors are shown as an inline summary at the top of the form plus a highlight on each
    offending field, instead of a modal the trainee had to dismiss before they could see
    which fields were actually at fault.
--}}
{{-- Styles live in public/css/fc-form-validation.css. @once so the three blades that
     include this partial cannot emit the tag twice on one page. --}}
@once
    <link rel="stylesheet" href="{{ asset('css/fc-form-validation.css') }}">
@endonce
<script>
(function () {
    var ALERT_ID = 'fc-validation-alert';

    function fieldLabel(el) {
        var wrap = el.closest('[class*="col-"]') || el.parentElement;
        var label = wrap ? wrap.querySelector('label.form-label, label.fw-semibold') : null;
        if (label) {
            return label.textContent.replace(/\*/g, '').trim();
        }
        return el.getAttribute('name') || 'This field';
    }

    function fieldIsEmpty(el, form) {
        if (el.disabled || el.type === 'hidden') {
            return false;
        }
        if (el.type === 'file') {
            if (el.files && el.files.length > 0) {
                return false;
            }
            var existing = form.querySelector('input[type="hidden"][name="' + el.name + '_existing"]');
            return !existing;
        }
        if (el.type === 'checkbox') {
            return !el.checked;
        }
        return !el.value || String(el.value).trim() === '';
    }

    /** The block that wraps a control together with its label, so both can be tinted. */
    function fieldBlock(el) {
        return el.closest('[class*="col-"]') || el.parentElement;
    }

    function clearHighlights(root) {
        (root || document).querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
        (root || document).querySelectorAll('.fc-field-invalid').forEach(function (el) {
            el.classList.remove('fc-field-invalid');
        });
    }

    function markInvalid(el) {
        el.classList.add('is-invalid');
        var block = fieldBlock(el);
        if (block) {
            block.classList.add('fc-field-invalid');
        }
    }

    /** Find the server-rendered summary, or build one just above the form. */
    function ensureAlertBox(form) {
        var box = document.getElementById(ALERT_ID);
        if (box) {
            return box;
        }

        box = document.createElement('div');
        box.id = ALERT_ID;
        box.className = 'fc-form-errors';
        box.setAttribute('role', 'alert');
        box.setAttribute('tabindex', '-1');

        var anchor = (form && form.closest('.card')) || form;
        if (anchor && anchor.parentNode) {
            anchor.parentNode.insertBefore(box, anchor);
        } else {
            document.body.insertBefore(box, document.body.firstChild);
        }

        return box;
    }

    function renderErrors(messages, form) {
        if (!messages || !messages.length) {
            return;
        }

        var box = ensureAlertBox(form);
        var items = messages.map(function (msg) {
            var li = document.createElement('li');
            li.textContent = String(msg);
            return li.outerHTML;
        }).join('');

        box.className = 'fc-form-errors';
        box.innerHTML = '<span class="fc-form-errors__title">Please fix the following</span>'
            + '<ul>' + items + '</ul>';
        box.hidden = false;

        focusFirstProblem(box);
    }

    function focusFirstProblem(box) {
        if (box && typeof box.scrollIntoView === 'function') {
            box.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        var first = document.querySelector('.is-invalid');
        if (first) {
            if (typeof first.focus === 'function') {
                try { first.focus({ preventScroll: true }); } catch (e) { /* older browsers */ }
            }
        }
    }

    function collectClientRequiredErrors(form) {
        var messages = [];
        form.querySelectorAll('[data-fc-required="1"]').forEach(function (el) {
            if (!fieldIsEmpty(el, form)) {
                return;
            }
            markInvalid(el);
            messages.push(fieldLabel(el) + ' is required.');
        });
        return messages;
    }

    /**
     * The field blades already stamp `is-invalid` on a control via Blade's error directive,
     * so on a
     * server-rejected render the control is marked but its wrapper is not — the box turned
     * pink while the label stayed black, and Choices.js dropdowns (whose real <select> is
     * hidden) showed no highlight at all.
     *
     * Syncing the wrapper from whatever already carries `is-invalid` covers every field the
     * server rejected, including repeatable group rows whose names are arrays
     * (rows[0][degree]) and therefore cannot be matched from the error key alone.
     */
    function syncExistingInvalid() {
        // Two-way: drop stale wrappers first, so a field the page's own validator has just
        // cleared does not keep its red label, then re-add for whatever is still invalid.
        document.querySelectorAll('.fc-field-invalid').forEach(function (block) {
            if (!block.querySelector('.is-invalid')) {
                block.classList.remove('fc-field-invalid');
            }
        });

        document.querySelectorAll('.is-invalid').forEach(function (el) {
            var block = fieldBlock(el);
            if (block) {
                block.classList.add('fc-field-invalid');
            }
        });
    }

    /**
     * Grouped-step "Save & Continue".
     *
     * That page runs its own validator (flagRequired in dynamic-step-groups-single) which
     * scans [data-required] and adds `is-invalid` to the control only — so labels stayed
     * black and Choices.js dropdowns, whose real <select> is hidden, showed nothing.
     *
     * Its handler is registered at parse time and this one on DOMContentLoaded, so theirs
     * runs first and the marks are already in place: sync the wrappers from them, then show
     * the same summary the flat steps use.
     *
     * Deliberately NOT a MutationObserver over the document — Choices.js mutates classes on
     * every option node it renders, and reacting to each one froze the page.
     */
    function bindGroupSaveSummary() {
        var btn = document.getElementById('fcSaveAllBtn');
        if (!btn) {
            return;
        }

        btn.addEventListener('click', function () {
            syncExistingInvalid();

            var seen = {};
            var messages = [];

            document.querySelectorAll('.is-invalid').forEach(function (el) {
                if (el.type === 'hidden' || el.disabled) {
                    return;
                }
                var msg = fieldLabel(el) + ' is required.';
                if (!seen[msg]) {
                    seen[msg] = true;
                    messages.push(msg);
                }
            });

            if (messages.length) {
                renderErrors(messages, btn.closest('form') || document.querySelector('.fc-group-form'));
            }
        });
    }

    /**
     * Fallback for a rejected field whose control the blade did not mark (error key with no
     * matching error directive). Keys may be dotted, e.g. "step2.address".
     */
    function highlightServerFields(keys) {
        keys.forEach(function (key) {
            var selector = '[name="' + key.replace(/"/g, '\\"') + '"]'
                + ',[name="' + key.replace(/\./g, '][').replace(/"/g, '\\"') + ']"]';
            var el = document.querySelector(selector);
            if (el) {
                markInvalid(el);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var serverErrors = @json($fcServerValidationErrors);
        var serverKeys = @json($errors->keys());

        // Wrappers first, from the controls the field blades already marked; then the
        // key-based fallback for anything they missed.
        syncExistingInvalid();
        bindGroupSaveSummary();

        if (serverKeys.length) {
            highlightServerFields(serverKeys);
        }

        if (serverErrors.length) {
            // The server-rendered block is already on the page; restyle it to match and
            // move focus to the first offending field.
            var box = document.getElementById(ALERT_ID);
            if (box) {
                box.classList.remove('alert', 'alert-danger', 'shadow-sm', 'mb-3');
                box.classList.add('fc-form-errors');
                var title = box.querySelector('strong');
                if (title) {
                    title.className = 'fc-form-errors__title';
                    title.textContent = 'Please fix the following';
                }
                var list = box.querySelector('ul');
                if (list) {
                    list.className = '';
                }
            }
            focusFirstProblem(document.getElementById(ALERT_ID));
        }

        document.querySelectorAll('form.fc-reg-step-form').forEach(function (form) {
            form.setAttribute('novalidate', 'novalidate');

            form.addEventListener('submit', function (e) {
                if (form.classList.contains('fc-skip-client-validation')) {
                    return;
                }

                clearHighlights(form);
                var clientErrors = collectClientRequiredErrors(form);

                if (clientErrors.length) {
                    e.preventDefault();
                    e.stopPropagation();
                    renderErrors(clientErrors, form);
                }
            }, true);

            // Clear a field's highlight as soon as the trainee fixes it.
            form.addEventListener('input', function (e) {
                var el = e.target;
                if (el && el.classList && el.classList.contains('is-invalid') && !fieldIsEmpty(el, form)) {
                    el.classList.remove('is-invalid');
                    var block = fieldBlock(el);
                    if (block) {
                        block.classList.remove('fc-field-invalid');
                    }
                }
            });

            form.addEventListener('change', function (e) {
                var el = e.target;
                if (el && el.classList && el.classList.contains('is-invalid') && !fieldIsEmpty(el, form)) {
                    el.classList.remove('is-invalid');
                    var block = fieldBlock(el);
                    if (block) {
                        block.classList.remove('fc-field-invalid');
                    }
                }
            });
        });
    });
})();
</script>
