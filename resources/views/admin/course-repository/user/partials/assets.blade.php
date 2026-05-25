<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/course-repository-user.css') }}">
<script>
(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        var grid = document.getElementById('courseCardsGrid');
        var toggles = document.querySelectorAll('[data-cru-view]');
        if (!grid || !toggles.length) return;

        function setView(view) {
            grid.classList.toggle('cru-view-list', view === 'grid');
            toggles.forEach(function (btn) {
                var isActive = btn.getAttribute('data-cru-view') === view;
                btn.classList.toggle('active', isActive);
                btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
            try { localStorage.setItem('cru-view', view); } catch (e) {}
        }

        toggles.forEach(function (btn) {
            btn.addEventListener('click', function () {
                setView(this.getAttribute('data-cru-view'));
            });
        });

        try {
            var saved = localStorage.getItem('cru-view');
            if (saved === 'grid' || saved === 'card') setView(saved);
        } catch (e) {}
    });
})();
</script>
