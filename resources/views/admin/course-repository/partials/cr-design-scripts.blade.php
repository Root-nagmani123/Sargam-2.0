<script>
(function () {
    'use strict';
    function updateCrDesignFileLabel(input) {
        var labelEl = document.getElementById(input.id + '_label');
        if (!labelEl) return;
        var name = (input.files && input.files.length === 1)
            ? input.files[0].name
            : (input.files && input.files.length > 1)
                ? input.files.length + ' files chosen'
                : 'No file chosen';
        labelEl.textContent = name;
    }
    document.querySelectorAll('.cr-design-file-input').forEach(function (input) {
        input.addEventListener('change', function () {
            updateCrDesignFileLabel(this);
        });
    });
    document.querySelectorAll('.file-input-institutional').forEach(function (input) {
        input.addEventListener('change', function () {
            updateCrDesignFileLabel(this);
            var container = document.querySelector('.selected-files-institutional');
            if (!container || !this.files || !this.files.length) {
                if (container) container.style.display = 'none';
                return;
            }
            container.style.display = 'block';
            container.textContent = Array.from(this.files).map(function (f) { return f.name; }).join(', ');
        });
    });
})();
</script>
