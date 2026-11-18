/* ==========================================================================
   Athletics Meet — shared grid + form behaviour for every am-* screen.
   One copy of the column chooser / export-link / confirm wiring instead of one
   per page (docs/new-design-index-page.md "Second page in a module → file").
   jQuery handlers throughout: Select2 fires jQuery events only (doc §3c).
   ========================================================================== */
(function ($) {
    'use strict';

    function confirmBox(opts) {
        if (typeof Swal === 'undefined' || typeof Swal.fire !== 'function') {
            return Promise.resolve(window.confirm(opts.text));
        }
        return Swal.fire({
            title: opts.title,
            text: opts.text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: opts.confirm,
            cancelButtonText: 'Cancel',
            confirmButtonColor: opts.danger ? '#d92d20' : '#004a93',
            reverseButtons: true
        }).then(function (r) { return r.isConfirmed; });
    }

    /**
     * Client-side DataTable + Columns modal + Download/Print links that carry the
     * search term and visible columns (?q=, ?cols=), as the master exports expect.
     *
     * opts: table (selector), key (localStorage key), exportKeys (header index →
     * export column key, '' = not exported), links (ids of export anchors),
     * colvisGrid (selector), noSort (column indexes), serial (index of S. No.).
     */
    function initGrid(opts) {
        var $table = $(opts.table);
        if (!$table.length) { return null; }

        var dt = $table.DataTable($.extend({
            order: [],
            columnDefs: [{ targets: opts.noSort || [], orderable: false, searchable: false }]
        }, opts.dtOptions || {}));

        if (opts.serial !== undefined) {
            dt.on('draw.dt', function () {
                var start = dt.page.info().start;
                dt.rows({ page: 'current', order: 'applied', search: 'applied' }).nodes().each(function (row, i) {
                    $(row).children('td').eq(opts.serial).text(start + i + 1);
                });
            }).draw(false);
        }

        var keys = opts.exportKeys || [];
        var exportCount = keys.filter(Boolean).length;

        function syncLinks() {
            var visible = [];
            dt.columns().every(function () {
                var k = keys[this.index()];
                if (k && this.visible()) { visible.push(k); }
            });
            (opts.links || []).forEach(function (id) {
                var link = document.getElementById(id);
                if (!link) { return; }
                var url = new URL(link.href, window.location.origin);
                url.searchParams.delete('q');
                url.searchParams.delete('cols');
                if (dt.search()) { url.searchParams.set('q', dt.search()); }
                if (visible.length !== exportCount) { url.searchParams.set('cols', visible.join(',')); }
                link.href = url.toString();
            });
        }

        function hiddenCols() {
            try {
                var v = JSON.parse(localStorage.getItem(opts.key) || '[]');
                return Array.isArray(v) ? v : [];
            } catch (e) { return []; }
        }

        function saveHidden(cols) {
            try { localStorage.setItem(opts.key, JSON.stringify(cols)); } catch (e) { /* private mode */ }
        }

        // Hidden columns are remembered by LABEL so adding a column can't hide the wrong one.
        var $grid = $(opts.colvisGrid).empty();
        var hidden = hiddenCols();
        dt.columns().every(function () {
            var col = this;
            var title = $(col.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; } // e.g. the select-all checkbox column
            col.visible(hidden.indexOf(title) === -1, false);

            var id = $table.attr('id') + '_colvis_' + col.index();
            var $cb = $('<input type="checkbox" class="form-check-input m-0">').attr('id', id).prop('checked', col.visible());
            $cb.on('change', function () {
                var cols = hiddenCols().filter(function (t) { return t !== title; });
                if (!this.checked) { cols.push(title); }
                saveHidden(cols);
                col.visible(this.checked, false);
                dt.columns.adjust();
                syncLinks();
            });
            $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', id).append($cb).append($('<span></span>').text(title))
            ).appendTo($grid);
        });
        dt.columns.adjust();
        dt.on('search.dt', syncLinks);
        syncLinks();

        return dt;
    }

    /**
     * Repeatable rows built from a <template>: names carry __i__, re-indexed on
     * every change; − shows once there is more than one row, + on the last only.
     */
    function bindRows(tbody, template, onAdd) {
        var $body = $(tbody);

        function sync() {
            var $rows = $body.children('tr');
            $rows.each(function (i) {
                $(this).find('[data-name]').each(function () {
                    this.name = this.getAttribute('data-name').replace('__i__', i);
                });
                $(this).find('.am-row-btn--remove').toggle($rows.length > 1);
                $(this).find('.am-row-btn--add').toggle(i === $rows.length - 1);
            });
        }

        function add() {
            // trim(): jQuery only parses a string as HTML when it starts with '<'.
            var $row = $($(template).html().trim());
            $body.append($row);
            sync();
            if (window.DropdownSearch) { window.DropdownSearch.initAll($row.find('select')); }
            if (onAdd) { onAdd($row); }
            return $row;
        }

        $body.on('click', '.am-row-btn--add', function () { add(); });
        $body.on('click', '.am-row-btn--remove', function () {
            if ($body.children('tr').length > 1) { $(this).closest('tr').remove(); sync(); }
        });

        return { add: add, sync: sync, clear: function () { $body.empty(); } };
    }

    /**
     * One modal serves Create and Edit (they must look alike — doc §3c). The modal
     * carries data-store, data-{create,edit}-title and -submit; an Edit button
     * carries data-action (update URL) and data-values (field name → value).
     * Pages listen for 'am:fill' to rebuild anything dependent (rows, cascades).
     */
    $(document).on('click', '[data-am-create], [data-am-edit]', function () {
        var $btn = $(this);
        var $modal = $($btn.data('am-modal'));
        var edit = $btn.is('[data-am-edit]');
        var mode = edit ? 'edit' : 'create';
        var values = edit ? ($btn.data('values') || {}) : {};
        var $form = $modal.find('form');

        $form.attr('action', edit ? $btn.data('action') : $modal.data('store'));
        $form.find('input[name="_method"]').val(edit ? 'PUT' : 'POST');
        $form[0].reset();
        $form.find('select').trigger('change.select2');
        $modal.find('[data-am-title]').text($modal.data(mode + '-title'));
        $modal.find('[data-am-submit]').text($modal.data(mode + '-submit'));

        $.each(values, function (name, val) {
            var $f = $form.find('[name="' + name + '"], [name="' + name + '[]"]');
            if ($f.is(':radio')) {
                $f.filter('[value="' + val + '"]').prop('checked', true);
            } else {
                $f.val(val).trigger('change');
            }
        });

        $modal.trigger('am:fill', [mode, values]);
        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    });

    // Delete (single row): Figma's "Confirm Delete?" dialog.
    $(document).on('submit', '.am-delete-form', function (e) {
        var form = this;
        if ($(form).data('confirmed')) { return; }
        e.preventDefault();
        confirmBox({
            title: 'Confirm Delete?',
            text: 'Are you sure you want to delete ' + ($(form).data('name') || 'this record') + '? This action can\'t be undone.',
            confirm: 'Yes, Delete',
            danger: true
        }).then(function (ok) {
            if (ok) { $(form).data('confirmed', true); form.submit(); }
        });
    });

    // Any other form that must be confirmed first (approve / decline / remove).
    $(document).on('submit', '.am-confirm-form', function (e) {
        var form = this;
        if ($(form).data('confirmed')) { return; }
        e.preventDefault();
        confirmBox({
            title: $(form).data('title') || 'Are you sure?',
            text: $(form).data('text') || '',
            confirm: $(form).data('confirm') || 'Yes',
            danger: !!$(form).data('danger')
        }).then(function (ok) {
            if (ok) { $(form).data('confirmed', true); form.submit(); }
        });
    });

    // custom.js does the toggle AJAX; badge and switch are in different columns, so reload.
    $(document).ajaxSuccess(function (e, xhr, s) {
        var url = (s && s.url) || '';
        if (url.indexOf('toggle-status') === -1 && url.indexOf('toggleStatus') === -1) { return; }
        if (!$('.am-page').length) { return; }
        setTimeout(function () { window.location.reload(); }, 600);
    });

    window.AthleticsGrid = { init: initGrid, rows: bindRows, confirm: confirmBox };
})(jQuery);
