<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/course-repository-user.css') }}">
<script>
(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        var grid = document.getElementById('courseCardsGrid');
        var toggles = document.querySelectorAll('[data-cru-view]');
        if (!grid || !toggles.length) return;

        var cardsPanel = grid.querySelector('.cru-view-cards');
        var listPanel = grid.querySelector('.cru-view-grid');

        function setView(view) {
            var isGrid = view === 'grid';

            if (cardsPanel && listPanel) {
                cardsPanel.classList.toggle('d-none', isGrid);
                listPanel.classList.toggle('d-none', !isGrid);
                grid.classList.remove('cru-view-list');
            } else {
                grid.classList.toggle('cru-view-list', isGrid);
            }

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
            if (saved === 'grid' || saved === 'card') {
                setView(saved);
            }
        } catch (e) {}
    });
})();
</script>
