/**
 * Master: Status Toggle -> Delete Icon auto-refresh (no full page reload)
 *
 * Problem this solves:
 *   The "Delete" action is rendered server-side based on a record's status
 *   (active = disabled delete button, inactive = enabled delete form). After
 *   toggling the status, the delete control stayed disabled/enabled until a
 *   manual page refresh.
 *
 * How it works (universal, no per-module config needed):
 *   On ANY successful status-toggle AJAX, we re-fetch the CURRENT page in the
 *   background and swap ONLY the affected row's delete control with the freshly
 *   server-rendered one. Because the server already decides the correct markup
 *   (disabled button vs. delete form, with the right route/CSRF), this works
 *   for every module without knowing its delete route.
 *
 *   - Plain Blade tables: the matching row exists in the fetched HTML, so its
 *     delete control is swapped in instantly.
 *   - Server-side DataTables: their static <tbody> is empty in the fetched
 *     HTML, so this safely no-ops (those tables reload their own data).
 *
 * Row matching is done via the status-toggle checkbox (data-table + data-id),
 * which every status switch in the project already exposes.
 */

(function ($) {
    'use strict';

    /**
     * Locate the "delete" control inside a scope (a <tr> or a parsed row).
     * Finds the material "delete" icon and climbs to its <form> (inactive
     * state) or <button>/<a> (active/disabled state). The "edit" icon is
     * ignored because we match on the icon's exact text.
     */
    function getDeleteControl($scope) {
        if (!$scope || !$scope.length) return $();

        var $icon = $scope
            .find('i.material-icons, i.material-symbols-rounded, i.menu-icon')
            .filter(function () {
                return $.trim($(this).text()).toLowerCase() === 'delete';
            })
            .first();

        if (!$icon.length) return $();

        var $form = $icon.closest('form');
        if ($form.length) return $form;

        var $btn = $icon.closest('button, a, span');
        return $btn.length ? $btn : $icon.parent();
    }

    /**
     * Find a record's row inside a context using the status-toggle checkbox.
     */
    function getRow($context, table, id) {
        if (id === null || typeof id === 'undefined' || id === '') return $();

        var selector = table
            ? '.status-toggle[data-table="' + table + '"][data-id="' + id + '"]'
            : '.status-toggle[data-id="' + id + '"]';

        return $context.find(selector).first().closest('tr');
    }

    /**
     * Re-init Bootstrap tooltips for freshly swapped-in markup.
     */
    function reinitTooltips($row) {
        if (window.bootstrap && bootstrap.Tooltip) {
            $row.find('[data-bs-toggle="tooltip"]').each(function () {
                try { new bootstrap.Tooltip(this); } catch (e) { /* noop */ }
            });
        }
    }

    /**
     * Swap the delete control of one row with the server-rendered version.
     */
    function syncDeleteControl(table, id) {
        var $currentRow = getRow($(document), table, id);
        if (!$currentRow.length) return;

        var $currentDelete = getDeleteControl($currentRow);
        if (!$currentDelete.length) return;

        // Re-fetch the current page (query string keeps pagination/search/filters).
        $.get(window.location.href)
            .done(function (html) {
                var doc;
                try {
                    doc = new DOMParser().parseFromString(html, 'text/html');
                } catch (e) {
                    return;
                }

                var $newRow = getRow($(doc), table, id);
                if (!$newRow.length) return; // DataTable / row absent -> leave as-is

                var $newDelete = getDeleteControl($newRow);
                if (!$newDelete.length) return;

                $currentDelete.replaceWith($newDelete.clone(true));
                reinitTooltips($currentRow);
            });
    }

    // Remember which switch initiated the request (most reliable source).
    var pendingTable = null;
    var pendingId = null;

    $(document).on('change', '.status-toggle', function () {
        pendingTable = $(this).data('table');
        pendingId = $(this).data('id');
    });

    /**
     * Hook every successful status-toggle AJAX call.
     */
    $(document).ajaxSuccess(function (event, xhr, settings) {
        var url = (settings && settings.url) ? settings.url : '';
        if (url.indexOf('toggle-status') === -1 && url.indexOf('toggleStatus') === -1) {
            return;
        }

        var table = pendingTable;
        var id = pendingId;

        // Fallback: parse from the request payload if the change handler missed it.
        if ((!table || id === null || typeof id === 'undefined') && typeof settings.data === 'string') {
            var tableMatch = settings.data.match(/(?:^|&)table=([^&]+)/);
            var idMatch = settings.data.match(/(?:^|&)id=([^&]+)/);
            if (tableMatch) table = decodeURIComponent(tableMatch[1]);
            if (idMatch) id = decodeURIComponent(idMatch[1]);
        }

        pendingTable = null;
        pendingId = null;

        // Let custom.js finish its own success handling, then sync the row.
        setTimeout(function () {
            syncDeleteControl(table, id);
        }, 50);
    });

})(jQuery);
