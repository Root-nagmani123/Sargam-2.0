{{-- After a failed submit, bring the first validation error into view. Group/flat
     fields can be far below the fold or inside an inactive tab, so the top error
     summary alone is easy to miss. Only acts when a server-side error is present. --}}
<script>
(function () {
    function run() {
        var field = document.querySelector('form .is-invalid');
        var alertBox = document.getElementById('fc-validation-alert')
            || document.querySelector('.alert-danger');
        if (!field && !alertBox) return;

        // If the invalid field is inside an inactive tab, activate that tab first.
        if (field) {
            var pane = field.closest('.tab-pane');
            if (pane && !pane.classList.contains('active')) {
                var btn = document.querySelector('[data-bs-target="#' + pane.id + '"]');
                if (btn) { btn.click(); }
            }
        }

        var target = field || alertBox;
        setTimeout(function () {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (field && typeof field.focus === 'function') {
                try { field.focus({ preventScroll: true }); } catch (e) {}
            }
        }, 250);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
</script>
