/**
 * Global success toast.
 *
 * Intercepts every SweetAlert2 success message (`Swal.fire({ icon: 'success', ... })`)
 * across the app and renders it as a consistent top-right toast card
 * (filled green check + bold "Success" + message) so individual modules
 * never have to style their own success feedback.
 *
 * Only `icon: 'success'` is intercepted — warning / error / confirmation
 * dialogs keep their native modal behaviour untouched.
 *
 * To keep it a drop-in replacement, the returned promise resolves with
 * `{ isConfirmed: true }`, so existing callbacks work whether they are written
 * as `.then(() => ...)` or `.then(result => { if (result.isConfirmed) ... })`.
 */
(function () {
    'use strict';

    var CHECK_SVG =
        '<svg class="sargam-success-toast__glyph" viewBox="0 0 24 24" width="30" height="30" aria-hidden="true" focusable="false">' +
        '<circle cx="12" cy="12" r="12" fill="#17B26A"></circle>' +
        '<path d="M17 8.4l-6.35 6.5L7 11.25" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"></path>' +
        '</svg>';

    function showSargamSuccessToast(nativeFire, opts) {
        opts = opts || {};

        var title = opts.title || 'Success';
        var message = opts.text || opts.html || '';
        var timer = (typeof opts.timer === 'number' && opts.timer > 0) ? opts.timer : 3000;

        return nativeFire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: timer,
            timerProgressBar: false,
            icon: 'success',
            iconHtml: CHECK_SVG,
            title: title,
            text: message,
            customClass: {
                popup: 'sargam-success-toast',
                icon: 'sargam-success-toast__icon',
                title: 'sargam-success-toast__title',
                htmlContainer: 'sargam-success-toast__text'
            }
        }).then(function () {
            // Preserve post-success callbacks that expect a confirmed result.
            return { isConfirmed: true, isDenied: false, isDismissed: false, value: true, dismiss: undefined };
        });
    }

    function install() {
        if (typeof window.Swal === 'undefined' || typeof window.Swal.fire !== 'function') {
            return false;
        }
        if (window.Swal.__sargamSuccessPatched) {
            return true;
        }

        var nativeFire = window.Swal.fire.bind(window.Swal);
        window.Swal.__sargamNativeFire = nativeFire;

        window.Swal.fire = function () {
            var first = arguments[0];

            // Object form: Swal.fire({ icon: 'success', ... })
            if (first && typeof first === 'object' && first.icon === 'success') {
                return showSargamSuccessToast(nativeFire, first);
            }

            // Positional form: Swal.fire(title, text, 'success')
            if (typeof first === 'string' && arguments[2] === 'success') {
                return showSargamSuccessToast(nativeFire, { title: first, text: arguments[1] });
            }

            return nativeFire.apply(window.Swal, arguments);
        };

        // Keep helpers pointing at the real implementation.
        window.Swal.fire.__sargamNativeFire = nativeFire;
        window.Swal.__sargamSuccessPatched = true;
        return true;
    }

    // Try now, and again after the page (and any page-level SweetAlert re-includes)
    // has parsed, so a later `window.Swal` swap gets re-patched too.
    install();
    document.addEventListener('DOMContentLoaded', install);
    window.addEventListener('load', install);

    // Fallback poll in case SweetAlert2 loads asynchronously.
    if (!(window.Swal && window.Swal.__sargamSuccessPatched)) {
        var tries = 0;
        var poll = setInterval(function () {
            if (install() || ++tries > 60) {
                clearInterval(poll);
            }
        }, 100);
    }
})();
