<script>
(function () {
    'use strict';

    var tableId = @json($cruTableId);
    var storageKey = @json($cruColumnStorageKey);
    var columns = @json($cruColumns);

    function defaults() {
        var state = {};
        columns.forEach(function (col) {
            if (col.locked) return;
            state[col.key] = col.default !== false;
        });
        return state;
    }

    function loadState() {
        try {
            var raw = localStorage.getItem(storageKey);
            if (!raw) return defaults();
            var parsed = JSON.parse(raw);
            if (!parsed || typeof parsed !== 'object') return defaults();
            var base = defaults();
            Object.keys(base).forEach(function (key) {
                if (typeof parsed[key] === 'boolean') base[key] = parsed[key];
            });
            return base;
        } catch (e) {
            return defaults();
        }
    }

    function saveState(state) {
        try {
            localStorage.setItem(storageKey, JSON.stringify(state));
        } catch (e) { /* ignore */ }
    }

    function setColumnVisible(colKey, visible) {
        var table = document.getElementById(tableId);
        if (!table) return;
        table.querySelectorAll('.cru-col-' + colKey).forEach(function (el) {
            el.classList.toggle('cru-col-hidden', !visible);
        });
    }

    function applyState(state) {
        Object.keys(state).forEach(function (key) {
            setColumnVisible(key, state[key]);
        });
        document.querySelectorAll('.cru-col-toggle-checkbox[data-table="' + tableId + '"]').forEach(function (cb) {
            var col = cb.getAttribute('data-col');
            if (state[col] !== undefined) cb.checked = state[col];
        });
    }

    function init() {
        var table = document.getElementById(tableId);
        if (!table) return;

        var state = loadState();
        applyState(state);

        document.querySelectorAll('.cru-col-toggle-checkbox[data-table="' + tableId + '"]').forEach(function (cb) {
            cb.addEventListener('change', function () {
                var col = this.getAttribute('data-col');
                var next = loadState();
                next[col] = this.checked;
                if (!this.checked) {
                    var visibleCount = Object.keys(next).filter(function (k) { return next[k]; }).length;
                    if (visibleCount < 1) {
                        this.checked = true;
                        return;
                    }
                }
                setColumnVisible(col, this.checked);
                saveState(next);
            });
        });

        document.querySelectorAll('.cru-col-toggle-reset[data-table="' + tableId + '"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                try { localStorage.removeItem(storageKey); } catch (e) { /* ignore */ }
                applyState(defaults());
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
