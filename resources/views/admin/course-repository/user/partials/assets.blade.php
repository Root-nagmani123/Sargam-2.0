<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('css/course-repository-user.css') }}">
<script>
(function () {
    'use strict';

    // Re-runnable so it re-binds after an AJAX results swap (cru:results-updated).
    function initCruView() {
        var grid = document.getElementById('courseCardsGrid');
        var toggles = document.querySelectorAll('[data-cru-view]');
        if (!grid || !toggles.length) return;

        var cardsPanel = grid.querySelector('.cru-view-cards');
        var listPanel = grid.querySelector('.cru-view-grid');
        // Ancestor shared by the filter toolbar and the grid — used to expose the
        // current view as a CSS hook (e.g. the Columns control shows only in grid view).
        var modeHost = grid.parentElement || grid;

        function applyMode(isGrid) {
            modeHost.classList.toggle('cru-view-mode-grid', isGrid);
            modeHost.classList.toggle('cru-view-mode-card', !isGrid);
        }

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

            applyMode(isGrid);

            try { localStorage.setItem('cru-view', view); } catch (e) {}
        }

        toggles.forEach(function (btn) {
            if (btn.dataset.cruViewBound) return; // avoid double-binding persistent nodes
            btn.dataset.cruViewBound = '1';
            btn.addEventListener('click', function () {
                setView(this.getAttribute('data-cru-view'));
            });
        });

        try {
            var saved = localStorage.getItem('cru-view');
            if (saved === 'grid' || saved === 'card') {
                setView(saved);
            } else {
                // No saved preference: markup defaults to card view.
                applyMode(false);
            }
        } catch (e) {
            applyMode(false);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCruView);
    } else {
        initCruView();
    }
    document.addEventListener('cru:results-updated', initCruView);
})();
</script>
