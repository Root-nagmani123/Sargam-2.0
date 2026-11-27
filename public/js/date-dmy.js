/**
 * DD-MM-YYYY date fields for the Member (employee) and Faculty forms.
 *
 * A native <input type="date"> renders in whatever format the browser's locale
 * dictates (MM/DD/YYYY on an en-US machine), so it cannot show DD-MM-YYYY on
 * its own. Any input carrying `data-date-dmy` is turned into a flatpickr
 * picker instead:
 *
 *   - the field the user sees and types into reads DD-MM-YYYY;
 *   - the named input — the one that is serialised and posted — keeps
 *     YYYY-MM-DD, so validation rules and the database columns are untouched.
 *
 * Optional: data-date-max="today" (or any YYYY-MM-DD) caps the selectable date.
 *
 * Inputs inserted later (the member wizard loads its steps over AJAX) are picked
 * up by a MutationObserver, so callers never have to initialise anything. If
 * flatpickr failed to load, the input is left as a native date field.
 *
 * Error renderers that look a field up by name get the hidden input; they must
 * call SargamDate.visibleField(el) to mark and annotate what the user sees.
 */
(function (window, document) {
    'use strict';

    var SELECTOR = 'input[data-date-dmy]';
    var DISPLAY_FORMAT = 'd-m-Y';
    var VALUE_FORMAT = 'Y-m-d';

    function cssEscape(value) {
        return window.CSS && window.CSS.escape ? window.CSS.escape(value) : String(value).replace(/["\\]/g, '\\$&');
    }

    function init(input) {
        if (!input || input._flatpickr) {
            return input ? input._flatpickr : null;
        }
        if (typeof window.flatpickr !== 'function') {
            return null;
        }

        // Stored values can arrive as "YYYY-MM-DD hh:mm:ss"; keep the date part
        // so flatpickr's Y-m-d parser reads it rather than rejecting it.
        var match = /^(\d{4}-\d{2}-\d{2})/.exec(input.value || '');
        input.value = match ? match[1] : '';
        input.type = 'text';

        var options = {
            dateFormat: VALUE_FORMAT,
            altInput: true,
            altFormat: DISPLAY_FORMAT,
            // flatpickr builds the visible input with altInputClass only, so pass
            // the original classes through (form-control, and is-invalid when the
            // page was rendered with a server-side error).
            altInputClass: input.className,
            allowInput: true,
            // Otherwise phones get the native picker back, in the device format.
            disableMobile: true,
            onReady: function (selectedDates, dateStr, instance) {
                var alt = instance.altInput;
                if (!alt) {
                    return;
                }
                alt.placeholder = 'DD-MM-YYYY';
                alt.setAttribute('autocomplete', 'off');
                if (input.id) {
                    // Keep the label clickable: point it at the field the user sees.
                    alt.id = input.id + '_display';
                    var label = document.querySelector('label[for="' + cssEscape(input.id) + '"]');
                    if (label) {
                        label.htmlFor = alt.id;
                    }
                }
            }
        };

        var max = input.getAttribute('data-date-max');
        if (max) {
            options.maxDate = max;
        }

        return window.flatpickr(input, options);
    }

    function initWithin(root) {
        if (!root || !root.querySelectorAll) {
            return;
        }
        if (root.matches && root.matches(SELECTOR)) {
            init(root);
        }
        Array.prototype.forEach.call(root.querySelectorAll(SELECTOR), init);
    }

    /**
     * The element the user actually sees for a date field: flatpickr's visible
     * input when one was built, otherwise the element itself.
     */
    function visibleField(el) {
        var node = el && el.jquery ? el[0] : el;
        if (node && node._flatpickr && node._flatpickr.altInput) {
            return node._flatpickr.altInput;
        }
        return node;
    }

    /** Set a date field from a YYYY-MM-DD string or a Date, without firing change. */
    function setValue(el, value) {
        var node = el && el.jquery ? el[0] : el;
        if (!node) {
            return;
        }
        var fp = node._flatpickr || init(node);
        if (fp) {
            fp.setDate(value || null, false);
        } else {
            node.value = value instanceof Date ? toYmd(value) : (value || '');
        }
    }

    function toYmd(date) {
        if (!(date instanceof Date) || isNaN(date.getTime())) {
            return '';
        }
        var pad = function (n) { return (n < 10 ? '0' : '') + n; };
        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
    }

    window.SargamDate = {
        init: init,
        initWithin: initWithin,
        visibleField: visibleField,
        setValue: setValue,
        toYmd: toYmd
    };

    function start() {
        initWithin(document);

        if (typeof window.MutationObserver === 'function' && document.body) {
            new window.MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    Array.prototype.forEach.call(mutation.addedNodes, function (node) {
                        if (node.nodeType === 1) {
                            initWithin(node);
                        }
                    });
                });
            }).observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})(window, document);
