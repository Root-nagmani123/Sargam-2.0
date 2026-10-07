/**
 * Global DataTables UI: moves search, pagination, and "Showing X of Y items"
 * into .programme-dt-search / .programme-dt-footer slots (or auto-creates them).
 *
 * Opt out: data-sargam-dt-ui="false" on <table> or a parent element.
 * Custom slots: data-dt-search-for / data-dt-footer-for on container elements,
 *   or data-dt-search / data-dt-footer on the table pointing to selectors.
 */
(function ($, window) {
    'use strict';

    if (!$ || !$.fn || !$.fn.dataTable) {
        return;
    }

    var NS = '.sargamDtUi';
    var HIDDEN_ROW_CLASS = 'sargam-dt-hidden-controls';
    var DEFAULT_DOM = 'rt<"row d-none ' + HIDDEN_ROW_CLASS + '"<"col-sm-12"filp>>';

    var DEFAULT_LANGUAGE = {
        search: '',
        searchPlaceholder: 'Search',
        lengthMenu: 'Showing _MENU_',
        info: 'of _TOTAL_ items',
        infoEmpty: 'of 0 items',
        infoFiltered: 'of _MAX_ items',
        paginate: {
            previous: '‹',
            next: '›'
        }
    };

  function parseBoolAttr(value) {
        if (value === undefined || value === null || value === '') {
            return null;
        }
        var normalized = String(value).trim().toLowerCase();
        if (['false', '0', 'no', 'off'].includes(normalized)) {
            return false;
        }
        if (['true', '1', 'yes', 'on'].includes(normalized)) {
            return true;
        }
        return null;
    }

    function shouldEnhance($table) {
        if (!$table || !$table.length) {
            return false;
        }
        if ($table.hasClass('dt-legacy-layout')) {
            return false;
        }
        if ($table.is('[data-mess-column-manager]')) {
            return false;
        }

        var tableAttr = parseBoolAttr($table.attr('data-sargam-dt-ui'));
        if (tableAttr === false) {
            return false;
        }
        if (tableAttr === true) {
            return true;
        }

        var $optOutParent = $table.closest('[data-sargam-dt-ui="false"]');
        if ($optOutParent.length) {
            return false;
        }

        if ($table.closest('.modal').length) {
            var hasSlot = $table.is('[data-dt-search],[data-dt-footer]') ||
                $('[data-dt-search-for="' + $table.attr('id') + '"],[data-dt-footer-for="' + $table.attr('id') + '"]').length;
            if (!hasSlot) {
                return false;
            }
        }

        return true;
    }

    function getTableId($table) {
        var id = $table.attr('id');
        if (id) {
            return id;
        }
        id = 'sargam-dt-' + Math.random().toString(36).slice(2, 9);
        $table.attr('id', id);
        return id;
    }

    function getWrapper($table, api) {
        var $wrapper = $table.closest('.dataTables_wrapper');
        if (!$wrapper.length && api) {
            try {
                $wrapper = $(api.table().container());
            } catch (e) { /* noop */ }
        }
        if (!$wrapper.length) {
            $wrapper = $('#' + getTableId($table) + '_wrapper');
        }
        return $wrapper;
    }

    function resolveSlot($table, tableId, kind, createIfMissing) {
        var isSearch = kind === 'search';
        var dataAttr = isSearch ? 'dt-search' : 'dt-footer';
        var dataForAttr = isSearch ? 'dt-search-for' : 'dt-footer-for';
        var slotClass = isSearch ? 'programme-dt-search' : 'programme-dt-footer';

        var direct = $table.attr('data-' + dataAttr);
        if (direct) {
            var $direct = $(direct);
            if ($direct.length) {
                return $direct.first();
            }
        }

        var $for = $('[data-' + dataForAttr + '="' + tableId + '"]');
        if ($for.length) {
            return $for.first();
        }

        var $scope = $table.closest('.programme-dt-panel, .card-body, .datatables, .gm-dt-card, .cgt-dt-card, .ems-dt-card, .eccm-dt-card, .mdt-dt-card, .mmt-dt-card, .mcm-dt-card, .cmdm-dt-card');
        if ($scope.length) {
            var $scoped = $scope.find('.' + slotClass).first();
            if ($scoped.length) {
                return $scoped;
            }
        }

        if (!createIfMissing) {
            return $();
        }

        return ensureAutoSlot($table, tableId, kind, slotClass);
    }

    function ensureAutoSlot($table, tableId, kind, slotClass) {
        var isSearch = kind === 'search';
        var marker = isSearch ? 'sargam-dt-search-auto' : 'sargam-dt-footer-auto';
        var existing = $('[data-' + marker + '="' + tableId + '"]');
        if (existing.length) {
            return existing.first();
        }

        var $wrapper = getWrapper($table);
        var $panel = $table.closest('.programme-dt-panel');
        if (!$panel.length) {
            $panel = $wrapper.parent();
        }

        if (isSearch) {
            var $toolbar = $(
                '<div class="sargam-dt-toolbar d-flex flex-wrap align-items-center justify-content-end gap-3 mb-3"></div>'
            );
            var $slot = $('<div class="' + slotClass + ' ms-lg-auto"></div>')
                .attr('data-' + marker, tableId)
                .attr('data-dt-search-for', tableId);
            $toolbar.append($slot);

            if ($panel.length) {
                $panel.before($toolbar);
            } else {
                $wrapper.before($toolbar);
            }
            return $slot;
        }

        var $footer = $('<div class="' + slotClass + ' d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"></div>')
            .attr('data-' + marker, tableId)
            .attr('data-dt-footer-for', tableId);

        if ($panel.length) {
            $panel.after($footer);
        } else {
            $wrapper.after($footer);
        }
        return $footer;
    }

    function ensureDomHasControls(oInit) {
        if (!oInit || oInit.sargamDtUi === false) {
            return;
        }
        if (oInit.searching === false && oInit.paging === false && oInit.info === false) {
            return;
        }

        var dom = oInit.dom;
        if (!dom || typeof dom !== 'string') {
            oInit.dom = DEFAULT_DOM;
            return;
        }

        if (dom.indexOf('f') === -1 && oInit.searching !== false) {
            if (dom.indexOf('ilp') !== -1) {
                oInit.dom = dom.replace('ilp', 'filp');
            } else if (dom.indexOf('lp') !== -1) {
                oInit.dom = dom.replace('lp', 'flp');
            } else if (dom.indexOf('p') !== -1) {
                oInit.dom = dom.replace('p', 'filp');
            } else {
                oInit.dom = dom + DEFAULT_DOM;
            }
        }
    }

    /**
     * Define the `reset` / `reload` button types.
     *
     * 18 of our Yajra DataTable classes declare Button::make('reset') and
     * Button::make('reload'). Those two types are NOT part of DataTables Buttons —
     * they ship in Yajra's own buttons.server-side.js, which this app never loads.
     * The Buttons extension then throws "Cannot extend unknown button type: reset"
     * from its own init.dt handler, and because jQuery aborts the remaining
     * handlers for an event once one throws, every listener registered after it —
     * including this file's enhancer — silently never runs. Symptom: search,
     * pagination and the "Showing N of M items" count all stay in the hidden dom
     * row and the page looks like it has no chrome at all.
     *
     * Defining the two missing types keeps Buttons happy. We do it from preInit
     * (below), which is dispatched before Buttons builds its instances, and only
     * when Buttons is actually present.
     */
    function ensureYajraButtonTypes() {
        var buttons = $.fn.dataTable && $.fn.dataTable.ext && $.fn.dataTable.ext.buttons;
        if (!buttons) {
            return;
        }

        // Behaviour matches Yajra's own definitions. The icons are Bootstrap Icons,
        // not the FontAwesome ones Yajra uses — bootstrap-icons is what this app
        // loads globally (admin/layouts/pre_header.blade.php), and an `fa` class
        // here would render an empty box.
        if (!buttons.reset) {
            buttons.reset = {
                className: 'buttons-reset',
                text: '<i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset',
                action: function (e, dt) {
                    dt.search('').columns().search('').draw();
                }
            };
        }

        if (!buttons.reload) {
            buttons.reload = {
                className: 'buttons-reload',
                text: '<i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Reload',
                action: function (e, dt) {
                    dt.draw(false);
                }
            };
        }
    }

    function styleSearchFilter($filter, searchLabel) {
        $filter.find('input')
            .addClass('form-control shadow-none')
            .attr('placeholder', 'Search')
            .attr('aria-label', searchLabel || 'Search');
        $filter.find('label').contents().filter(function () {
            return this.nodeType === 3;
        }).remove();
    }

    function enhance(api, options) {
        options = options || {};

        if (!api) {
            return;
        }

        var $table = $(api.table().node());
        if (!shouldEnhance($table)) {
            return;
        }

        var tableId = getTableId($table);
        var $wrapper = getWrapper($table, api);
        if (!$wrapper.length) {
            return;
        }

        $wrapper.addClass('sargam-dt-ui');

        var $searchSlot = resolveSlot($table, tableId, 'search', true);
        var $footer = resolveSlot($table, tableId, 'footer', true);
        var searchLabel = options.searchLabel || $table.data('searchLabel') || $table.attr('data-search-label') || 'Search';

        if ($searchSlot.length && !$searchSlot.find('.dataTables_filter').length) {
            var $filter = $wrapper.find('.dataTables_filter').first();
            if ($filter.length) {
                styleSearchFilter($filter, searchLabel);
                // Stamp it as ours so the destroy handler can remove this node
                // without touching a .dataTables_filter the PAGE supplied itself
                // (group_mapping ships its own, driven by #gmCustomSearch).
                $filter.attr('data-sargam-dt-filter', tableId);
                $searchSlot.append($filter);
            }
        }

        var footerKey = 'sargamDtFooterReady_' + tableId;
        if ($footer.data(footerKey)) {
            updateCount(api);
            return;
        }

        if (!$footer.length) {
            return;
        }

        var $paginate = $wrapper.find('.dataTables_paginate').first();
        var $length = $wrapper.find('.dataTables_length').first();
        var $info = $wrapper.find('.dataTables_info').first();

        // Two bail-outs, both BEFORE the $footer.empty() below. That empty() is
        // destructive and irreversible: the nodes leave the document, and
        // DataTables only ever updates its EXISTING feature nodes on redraw, so
        // anything wiped here never comes back on its own.
        //
        // (1) Nothing in the wrapper to relocate — DataTables hasn't rendered its
        //     controls yet. Return WITHOUT setting footerKey so the later pass
        //     retries, instead of locking in a permanently empty footer.
        if (!$paginate.length && !$length.length && !$info.length) {
            return;
        }

        // (2) The footer already has content and we don't hold the pager, so
        //     something else (a page-local enhancer that ran first) populated it
        //     and owns those nodes. Rebuilding from what's left in the wrapper
        //     could only lose controls — leave it alone.
        if ($footer.children().length && !$paginate.length) {
            return;
        }

        var $pagCol = $('<div class="programme-dt-pagination"></div>');
        var $countCol = $('<div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto"></div>');

        if ($paginate.length) {
            $paginate.find('.pagination').addClass('mb-0');
            $pagCol.append($paginate);
        }

        if ($length.length) {
            // Detach (not remove) the select BEFORE emptying the label. jQuery's
            // .empty()/.remove() strip the event handlers + data of every
            // descendant they clear — which would kill the `change.DT` handler
            // DataTables binds directly on this select, leaving the "Showing"
            // dropdown visible but inert. .detach() preserves those handlers so
            // re-appending the same node keeps it working.
            var $pageSize = $length.find('select').addClass('form-select form-select-sm').detach();
            $length.find('label')
                .empty()
                .append(document.createTextNode('Showing '))
                .append($pageSize)
                .append(document.createTextNode(' '));
            $countCol.append($length);
        }

        if ($info.length) {
            $info.addClass('mb-0');
            $countCol.append($info);
        }

        $footer.empty().append($pagCol).append($countCol);
        $footer.data(footerKey, true);
        $footer.data('sargamDtTableId', tableId);

        updateCount(api);
    }

    function updateCount(api) {
        if (!api) {
            return;
        }

        var $table = $(api.table().node());
        var tableId = getTableId($table);
        var $footer = resolveSlot($table, tableId, 'footer', false);
        if (!$footer.length) {
            return;
        }

        var info = api.page.info();
        var $info = $footer.find('.dataTables_info');
        if ($info.length && info && info.recordsDisplay !== undefined) {
            $info.text('of ' + info.recordsDisplay.toLocaleString() + ' items');
        }
    }

    function resetFooterIfNeeded(api) {
        var $table = $(api.table().node());
        var tableId = getTableId($table);
        var $wrapper = getWrapper($table, api);
        var $footer = resolveSlot($table, tableId, 'footer', false);

        if (!$footer.length) {
            return;
        }

        if ($wrapper.find('.dataTables_paginate').length && !$footer.find('.dataTables_paginate').length) {
            // Only reclaim a footer WE built. If it has content that we didn't put
            // there — a page's hand-written Laravel paginator, say — we never owned
            // it and must not empty it. Same reasoning as the guards in enhance();
            // `sargamDtTableId` is stamped there precisely to mark our own work.
            if ($footer.children().length && $footer.data('sargamDtTableId') !== tableId) {
                return;
            }
            $footer.empty().data('sargamDtFooterReady_' + tableId, false);
            enhance(api);
        }
    }

    function bindTableEvents(api) {
        var $table = $(api.table().node());
        var tableId = getTableId($table);
        var bindKey = 'sargamDtUiEventsBound';

        if ($table.data(bindKey)) {
            return;
        }
        $table.data(bindKey, true);

        api.on('draw' + NS, function () {
            resetFooterIfNeeded(api);
            updateCount(api);
        });

        api.on('length' + NS, function () {
            updateCount(api);
        });
    }

    function enhanceFromSettings(settings) {
        var api = new $.fn.dataTable.Api(settings);
        enhance(api);
        bindTableEvents(api);
    }

    function applyGlobalDefaults() {
        $.extend(true, $.fn.dataTable.defaults, {
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
            pagingType: 'full_numbers',
            dom: DEFAULT_DOM,
            language: DEFAULT_LANGUAGE
        });
    }

    applyGlobalDefaults();

    /**
     * Global column-sorting: make every data column client-side sortable so
     * users can click any header to sort the loaded rows. We normalise the init
     * options BEFORE DataTables builds the table (the only reliable point to
     * change column orderability).
     *
     * Rules:
     *  - Server-side tables are left untouched (ordering is delegated to the
     *    backend there; computed/formatted columns can't be ordered in SQL).
     *  - `ordering: false` is flipped on so headers are clickable.
     *  - column-level `orderable: false` is cleared, EXCEPT for Action-like /
     *    checkbox columns (detected by data/name/title/className).
     *  - columnDefs are left as-is: their `orderable:false` usually targets the
     *    Action / checkbox / row-index columns by numeric index, which we want
     *    to keep unsortable and can't safely distinguish here.
     */
    function looksLikeActionColumn(col) {
        if (!col) { return false; }
        var key = String(col.title || col.name || col.data || '').trim().toLowerCase();
        if (key === 'action' || key === 'actions' || key === 'dt_rowindex') {
            return true;
        }
        var cls = String(col.className || col.class || '').toLowerCase();
        return /(^|\s)(action|actions|no-sort|dt-nosort|select-checkbox)(\s|$)/.test(cls);
    }

    function normalizeSortingOptions(options) {
        if (!options || typeof options !== 'object') {
            return options;
        }

        if (options.serverSide === true) {
            // Server-side (Yajra) tables can't be safely converted to full
            // client-side globally — their generated ajax request logic depends
            // on the server-side request structure. So we turn OFF DataTables'
            // server ordering and let enableClientSidePageSort() sort the loaded
            // page in the browser instead.
            //
            // Opt-in escape hatch: a table can set `sargamServerOrder: true` in
            // its parameters to KEEP DataTables' native (server-side) ordering —
            // clicking a header then re-queries and sorts the WHOLE dataset
            // (with pagination), instead of only reordering the visible page.
            // enableClientSidePageSort() detects native ordering is on and skips.
            if (options.sargamServerOrder !== true) {
                options.ordering = false;
            }
            return options;
        }

        if (options.ordering === false) {
            options.ordering = true;
        }

        if (Array.isArray(options.columns)) {
            options.columns.forEach(function (col) {
                if (col && col.orderable === false && !looksLikeActionColumn(col)) {
                    col.orderable = true;
                }
            });
        }

        return options;
    }

    /* ── Client-side page sort for server-side tables ──────────────────────────
       Server-side tables now have DataTables' own ordering disabled (above), so
       we sort the CURRENTLY-LOADED rows in the browser on header click, toggling
       asc/desc. We reuse the .sorting / .sorting_asc / .sorting_desc classes so
       the sort-arrow styling applies. Sorts each loaded page (the accepted
       client-side behaviour). */
    function cellSortValue(cell) {
        if (!cell) { return { text: '', num: null }; }
        var text = (cell.textContent || '').replace(/\s+/g, ' ').trim();
        var cleaned = text.replace(/[,%₹$]/g, '').trim();
        var num = (cleaned !== '' && !isNaN(cleaned)) ? parseFloat(cleaned) : null;
        return { text: text, num: num };
    }

    function sortVisiblePageRows(tableNode, colIdx, dir) {
        var tbody = tableNode.tBodies && tableNode.tBodies[0];
        if (!tbody) { return; }
        var rows = Array.prototype.slice.call(tbody.rows).filter(function (r) {
            return !r.querySelector('td[colspan], th[colspan]'); // skip empty / grouping rows
        });
        if (rows.length < 2) { return; }
        var factor = dir === 'desc' ? -1 : 1;
        rows.sort(function (a, b) {
            var av = cellSortValue(a.cells[colIdx]);
            var bv = cellSortValue(b.cells[colIdx]);
            if (av.num !== null && bv.num !== null) {
                return (av.num - bv.num) * factor;
            }
            return av.text.localeCompare(bv.text, undefined, { numeric: true, sensitivity: 'base' }) * factor;
        });
        var frag = document.createDocumentFragment();
        rows.forEach(function (r) { frag.appendChild(r); });
        tbody.appendChild(frag);
    }

    function enableClientSidePageSort(settings) {
        if (!settings.oFeatures || !settings.oFeatures.bServerSide) {
            return; // client-side tables sort natively via DataTables
        }
        if (settings.oFeatures.bSort) {
            // Native (server-side) ordering is enabled for this table
            // (sargamServerOrder opt-in) — DataTables sends the order to the
            // server and sorts the full dataset, so don't add the page sorter.
            return;
        }
        var api = new $.fn.dataTable.Api(settings);
        var tableNode = api.table().node();
        var $table = $(tableNode);
        if ($table.data('sargamPageSort')) { return; }
        $table.data('sargamPageSort', true);

        var $container = $(api.table().container());
        if (!$container.length) { $container = $table.closest('.dataTables_wrapper'); }
        var sortState = { col: null, dir: 'asc' };

        // Resolve the header DataTables actually manages. For scrollX/scrollY
        // tables the visible, clickable header is a CLONE inside
        // .dataTables_scrollHead; $table's own <thead> is hidden in the scroll
        // body, so binding there would make header clicks do nothing (the
        // "some tables don't sort" symptom). api.table().header() returns the
        // clone when scrolling and the original thead otherwise — and because
        // scrolling tables re-clone that header on every draw, we re-decorate
        // it after each draw rather than caching the cells.
        function headerCells() {
            var $root;
            try { $root = $(api.table().header()); } catch (e) { $root = $(); }
            if (!$root.length) { $root = $table.find('thead'); }
            var $row = $root.find('tr').last();
            if (!$row.length) { $row = $table.find('thead tr').last(); }
            return $row.children('th, td');
        }

        function decorateHeader() {
            headerCells().each(function (idx) {
                var $c = $(this);
                var title = $c.text().replace(/\s+/g, ' ').trim().toLowerCase();
                if (title === '' || title === 'action' || title === 'actions') { return; }
                $c.attr('data-sargam-sort', idx).css('cursor', 'pointer');
                // reflect the active sort direction (survives header re-clone)
                if (sortState.col === idx) {
                    $c.removeClass('sorting').addClass(sortState.dir === 'asc' ? 'sorting_asc' : 'sorting_desc');
                } else {
                    $c.removeClass('sorting_asc sorting_desc').addClass('sorting');
                }
            });
        }

        decorateHeader();

        // Delegate on the stable container so the handler survives the header
        // being re-cloned on redraw (scrollX/scrollY tables).
        $container.off('click.sargamSort').on('click.sargamSort', 'thead [data-sargam-sort]', function () {
            var col = parseInt($(this).attr('data-sargam-sort'), 10);
            if (isNaN(col)) { return; }
            if (sortState.col === col) {
                sortState.dir = (sortState.dir === 'asc') ? 'desc' : 'asc';
            } else {
                sortState.col = col;
                sortState.dir = 'asc';
            }
            decorateHeader();
            sortVisiblePageRows(tableNode, col, sortState.dir);
        });

        // Each server draw (page change / filter) replaces the rows and, for
        // scrolling tables, the header clone — so re-decorate and re-apply the
        // active sort so the visible page stays sorted the way the user chose.
        $table.on('draw.dt.sargamSort', function () {
            decorateHeader();
            if (sortState.col !== null) {
                sortVisiblePageRows(tableNode, sortState.col, sortState.dir);
            }
        });
    }

    // Patch the DataTables initialisers once so the normalisation applies to
    // every table on the page, without touching each call site.
    (function patchDataTableInit() {
        if (!$.fn.DataTable || $.fn.DataTable.__sargamSortPatched) {
            return;
        }

        var origDataTable = $.fn.DataTable;
        var origLegacy = $.fn.dataTable;

        $.fn.DataTable = function (options) {
            return origDataTable.call(this, normalizeSortingOptions(options));
        };
        $.extend($.fn.DataTable, origDataTable);
        $.fn.DataTable.__sargamSortPatched = true;

        if (typeof origLegacy === 'function') {
            $.fn.dataTable = function (options) {
                return origLegacy.call(this, normalizeSortingOptions(options));
            };
            $.extend($.fn.dataTable, origLegacy);
            $.fn.dataTable.__sargamSortPatched = true;
        }
    })();

    $(document).on('preInit.dt' + NS, function (e, settings) {
        // Runs before the Buttons extension builds its instances — see the comment
        // on ensureYajraButtonTypes(). Must happen for EVERY table, including ones
        // that opt out of the UI enhancement, because the throw it prevents would
        // take down unrelated handlers too.
        try { ensureYajraButtonTypes(); } catch (err) { /* noop */ }

        var api = new $.fn.dataTable.Api(settings);
        var $table = $(api.table().node());

        if (!shouldEnhance($table)) {
            settings.oInit.sargamDtUi = false;
            return;
        }

        settings.oInit.sargamDtUi = true;
        ensureDomHasControls(settings.oInit);

        if (!settings.oInit.pagingType) {
            settings.oInit.pagingType = 'full_numbers';
        }

        settings.oInit.language = $.extend(true, {}, DEFAULT_LANGUAGE, settings.oInit.language || {});
    });

    $(document).on('init.dt' + NS, function (e, settings) {
        var api = new $.fn.dataTable.Api(settings);
        var $table = $(api.table().node());

        // Client-side page sorting for server-side tables applies regardless of
        // the UI-enhancement opt-out, so every table gets sortable headers.
        try { enableClientSidePageSort(settings); } catch (err) { /* noop */ }

        if (!shouldEnhance($table)) {
            return;
        }

        window.setTimeout(function () {
            enhanceFromSettings(settings);
        }, 0);

        window.setTimeout(function () {
            enhanceFromSettings(settings);
        }, 300);
    });


    /**
     * Safety net: enhance any initialised table the init.dt path missed.
     *
     * enhance() normally runs from the delegated init.dt handler above. That is a
     * single point of failure: if ANY handler registered earlier for the same
     * event throws, jQuery abandons the rest of the dispatch and we are never
     * called — which is exactly what the unknown-button-type error used to do.
     * The per-page enhancer copies that this file replaced polled on a timer
     * instead, which is why they survived it.
     *
     * So we also enhance from `draw.dt`, delegated on document. Every table draws
     * at least once after initialising, whenever it is created — including tables
     * built long after page load (opened in a modal, or rebuilt by a filter change),
     * which a fixed timer would miss. Separate event, separate dispatch, so a
     * throw in another extension's init handler cannot suppress it.
     *
     * Cheap and idempotent: enhance() returns immediately once it has built a
     * footer (the footerKey flag), and its guards stop it touching a footer owned
     * by someone else.
     */
    function enhanceIfNeeded(settings) {
        try {
            var api = new $.fn.dataTable.Api(settings);
            if (!shouldEnhance($(api.table().node()))) {
                return;
            }
            enhance(api);
            bindTableEvents(api);
        } catch (err) { /* table mid-teardown — skip it */ }
    }

    $(document).on('draw.dt' + NS, function (e, settings) {
        enhanceIfNeeded(settings);
    });

    /**
     * Release the slots when a table is destroyed.
     *
     * .destroy() tears down the wrapper but leaves the nodes we relocated stranded
     * in the page's slots, while footerKey stays set — so the re-initialised table
     * early-returns in enhance() and the user is left with dead chrome. Pages hit
     * by this (attendance) had to clear the slots by hand before recreating.
     *
     * We only clear what we own: the footer is checked against the sargamDtTableId
     * stamp, and the filter against the data-sargam-dt-filter stamp, so a
     * hand-written footer or a page-supplied search box is never touched.
     */
    $(document).on('destroy.dt' + NS, function (e, settings) {
        try {
            var $table = $(new $.fn.dataTable.Api(settings).table().node());
            var tableId = $table.attr('id');
            if (!tableId) {
                return;
            }

            var $footer = resolveSlot($table, tableId, 'footer', false);
            if ($footer.length && $footer.data('sargamDtTableId') === tableId) {
                $footer.empty()
                    .removeData('sargamDtTableId')
                    .removeData('sargamDtFooterReady_' + tableId);
            }

            var $searchSlot = resolveSlot($table, tableId, 'search', false);
            if ($searchSlot.length) {
                $searchSlot.find('[data-sargam-dt-filter="' + tableId + '"]').remove();
            }

            $table.removeData('sargamDtUiEventsBound');
        } catch (err) { /* noop */ }
    });

    /**
     * Move page-owned controls (an "Add New" button, a print/columns toolbar, ...) into a table's
     * DataTables search row, wherever that row currently lives.
     *
     * Pages used to hand-roll this as:
     *
     *     var $filter = $table.closest('.dataTables_wrapper').find('.dataTables_filter');
     *     var $btn    = $('.my-add-btn').detach();          // <- detached before the target is known
     *     if ($filter.length) { $filter.append($btn); }
     *
     * which breaks here, because enhance() moves .dataTables_filter OUT of .dataTables_wrapper into
     * its own toolbar. The wrapper lookup then finds nothing and the already-detached control is
     * gone for good. That silently hid the "Add New" button on four Estate Master screens and the
     * Columns/Print buttons on Timetable Report.
     *
     * This resolves the filter by its data-sargam-dt-filter stamp first (post-enhance) and the
     * wrapper second (pre-enhance, or enhancement skipped), and never detaches — jQuery's append()
     * moves existing nodes anyway. If no search row exists the control is left exactly where the
     * page put it, which is always better than losing it.
     *
     * @param  {string} tableId  table id attribute, without the leading '#'
     * @param  {jQuery|Element|string} el  control(s) to move in
     * @return {boolean} true if the control was placed in a search row
     */
    function appendToSearchRow(tableId, el) {
        if (!tableId || !el) {
            return false;
        }

        var $el = $(el);
        if (!$el.length) {
            return false;
        }

        var $filter = $('[data-sargam-dt-filter="' + tableId + '"]').first();
        if (!$filter.length) {
            $filter = $(document.getElementById(tableId))
                .closest('.dataTables_wrapper')
                .find('.dataTables_filter')
                .first();
        }
        if (!$filter.length) {
            return false;
        }

        $filter.addClass('d-flex align-items-center justify-content-end flex-wrap gap-2').append($el);

        return true;
    }

    window.SargamDataTableUI = {
        enhance: enhance,
        updateCount: updateCount,
        shouldEnhance: shouldEnhance,
        appendToSearchRow: appendToSearchRow,
        DEFAULT_DOM: DEFAULT_DOM,
        DEFAULT_LANGUAGE: DEFAULT_LANGUAGE
    };
})(window.jQuery, window);
