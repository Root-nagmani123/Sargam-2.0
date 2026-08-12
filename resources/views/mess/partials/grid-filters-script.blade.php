{{--
    Driver for the shared mess grid filter controls:

      • mess.partials.status-pills          → [data-mess-status-tabs="<tableId>"]
      • the red "Remove Filter" button      → [data-mess-remove-filter="<tableId>"]

    Push into @push('scripts') once per page. It binds every group on the page, so a
    screen with more than one grid — or with a Remove Filter but no pills — costs no
    extra code.

    How the filter travels: the mess DataTable component builds its ajax URL from
    window.location.search (components/mess-master-datatables.blade.php), so writing
    the pill into the query string and reloading the table is the whole mechanism —
    no page navigation, and the filter survives a refresh or a shared link.

    Remove Filter is hidden until something is actually filtered (a pill, or a live
    search term), then clears both.
--}}
<script>
(function () {
    var $ = window.jQuery;

    function api(tableId) {
        return ($ && $.fn.DataTable && $.fn.DataTable.isDataTable('#' + tableId))
            ? $('#' + tableId).DataTable() : null;
    }

    function currentStatus() {
        return (new URLSearchParams(window.location.search).get('status') || '').toLowerCase();
    }

    function syncControls(tableId) {
        var status = currentStatus();

        document.querySelectorAll('[data-mess-status-tabs="' + tableId + '"] [data-mess-status]')
            .forEach(function (btn) {
                var on = status !== '' && btn.getAttribute('data-mess-status') === status;
                btn.classList.toggle('active', on);
                btn.setAttribute('aria-pressed', on ? 'true' : 'false');
                if (on) { btn.setAttribute('aria-current', 'true'); }
                else { btn.removeAttribute('aria-current'); }
            });

        var dt = api(tableId);
        var searching = dt ? (dt.search() || '') !== '' : false;

        document.querySelectorAll('[data-mess-remove-filter="' + tableId + '"]')
            .forEach(function (btn) {
                // A page whose other filters are server-side (a GET <form> select,
                // say) stamps data-mess-filter-server="1" when one of them is on —
                // this script can't see those from the DataTable alone.
                var server = btn.getAttribute('data-mess-filter-server') === '1';
                btn.classList.toggle('d-none', !server && status === '' && !searching);
            });
    }

    function applyStatus(tableId, status) {
        var url = new URL(window.location.href);
        if (status) { url.searchParams.set('status', status); }
        else { url.searchParams.delete('status'); }
        window.history.replaceState({}, '', url.toString());

        syncControls(tableId);

        var dt = api(tableId);
        if (dt) { dt.page(0).ajax.reload(null, false); }
    }

    // Every table id that carries either control on this page.
    var tableIds = [];
    function track(id) { if (id && tableIds.indexOf(id) === -1) tableIds.push(id); }

    document.querySelectorAll('[data-mess-status-tabs]').forEach(function (group) {
        var tableId = group.getAttribute('data-mess-status-tabs');
        if (!tableId) return;
        track(tableId);

        group.querySelectorAll('[data-mess-status]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var value = btn.getAttribute('data-mess-status') || '';
                // Clicking the lit pill clears the filter — with only Active and
                // Inactive on screen this is the only way back to "everything".
                applyStatus(tableId, currentStatus() === value ? '' : value);
            });
        });
    });

    document.querySelectorAll('[data-mess-remove-filter]').forEach(function (btn) {
        var tableId = btn.getAttribute('data-mess-remove-filter');
        if (!tableId) return;
        track(tableId);

        // An <a> Remove Filter navigates to the unfiltered index itself — that full
        // reload clears the server-side filters this script can't reach, so we only
        // manage its visibility and leave the click alone.
        if (btn.tagName === 'A') return;

        btn.addEventListener('click', function () {
            var dt = api(tableId);
            if (dt) { dt.search(''); }
            applyStatus(tableId, '');
        });
    });

    // The tables finish initialising after this script runs, so re-sync once they
    // exist — otherwise Remove Filter can't see an active search term.
    tableIds.forEach(function (tableId) {
        syncControls(tableId);
        var tries = 0;
        var timer = setInterval(function () {
            var dt = api(tableId);
            if (!dt && ++tries <= 20) return;
            clearInterval(timer);
            syncControls(tableId);
            if (dt) { dt.on('search.dt', function () { syncControls(tableId); }); }
        }, 100);
    });
})();
</script>
