@php
    $fcServerValidationErrors = $errors->any() ? $errors->all() : [];
@endphp
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function () {
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

    function scrollToFirstInvalid() {
        var alertBox = document.getElementById('fc-validation-alert');
        if (alertBox) {
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        var first = document.querySelector('.is-invalid');
        if (first) {
            first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (typeof first.focus === 'function') {
                try { first.focus({ preventScroll: true }); } catch (e) { first.focus(); }
            }
        }
    }

    function showValidationPopup(messages, onClose) {
        if (!messages || !messages.length) {
            return;
        }
        var listHtml = '<ul style="text-align:left;margin:0;padding-left:1.25rem;">'
            + messages.map(function (msg) {
                return '<li style="margin-bottom:0.35rem;">' + String(msg).replace(/</g, '&lt;') + '</li>';
            }).join('')
            + '</ul>';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Please fix the following',
                html: listHtml,
                icon: 'error',
                confirmButtonColor: '#004a93',
                confirmButtonText: 'OK'
            }).then(function () {
                if (typeof onClose === 'function') {
                    onClose();
                }
            });
        } else {
            alert(messages.join('\n'));
            if (typeof onClose === 'function') {
                onClose();
            }
        }
    }

    function collectClientRequiredErrors(form) {
        var messages = [];
        form.querySelectorAll('[data-fc-required="1"]').forEach(function (el) {
            el.classList.remove('is-invalid');
            if (!fieldIsEmpty(el, form)) {
                return;
            }
            el.classList.add('is-invalid');
            messages.push(fieldLabel(el) + ' is required.');
        });
        return messages;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var serverErrors = @json($fcServerValidationErrors);
        if (serverErrors.length) {
            showValidationPopup(serverErrors, scrollToFirstInvalid);
        }

        document.querySelectorAll('form.fc-reg-step-form').forEach(function (form) {
            form.setAttribute('novalidate', 'novalidate');
            form.addEventListener('submit', function (e) {
                if (form.classList.contains('fc-skip-client-validation')) {
                    return;
                }
                var clientErrors = collectClientRequiredErrors(form);
                if (clientErrors.length) {
                    e.preventDefault();
                    e.stopPropagation();
                    showValidationPopup(clientErrors, scrollToFirstInvalid);
                }
            }, true);
        });
    });
})();
</script>
