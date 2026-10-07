/*
 * Estate reports — shared behaviour for the report grids
 * (Pending Meter Reading, House Status, Estate Bill Report - Grid View,
 * Estate Migration Report).
 *
 * Presentation only: no endpoint, query or calculation lives here. Each page
 * still owns its own AJAX call and DataTables init; this file supplies the
 * pieces they would otherwise copy-paste (docs/new-design-index-page.md:
 * "second page in a module → move it to a file").
 *
 *   EstateReportGrid.esc(value)                     HTML-escape a cell value
 *   EstateReportGrid.stateRow($table, kind, msg)    loading / empty / error / idle row
 *   EstateReportGrid.columnVisibility(dt, opts)     Columns modal, remembered by LABEL
 *   EstateReportGrid.collectRows(dt, opts)          visible columns × filtered rows
 *   EstateReportGrid.printGrid(opts)                branded print via hidden iframe
 */
(function (window, $) {
    'use strict';

    function esc(value) {
        if (value === null || value === undefined) return '';
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    var STATE_ICON = {
        loading: '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>',
        empty: '<i class="bi bi-inbox" aria-hidden="true"></i>',
        error: '<i class="bi bi-exclamation-triangle" aria-hidden="true"></i>',
        idle: '<i class="bi bi-funnel" aria-hidden="true"></i>'
    };

    /**
     * Replace the tbody with one full-width state row. Call it only while no
     * DataTable owns the table (destroy first) — DataTables rejects a colspan row.
     */
    function stateRow($table, kind, message) {
        var cols = $table.find('thead th').length || 1;
        var role = kind === 'error' ? 'alert' : 'status';
        $table.find('tbody').html(
            '<tr class="er-state-row"><td colspan="' + cols + '">' +
            '<div class="er-state er-state--' + esc(kind) + '" role="' + role + '">' +
            (STATE_ICON[kind] || '') + '<span>' + esc(message) + '</span>' +
            '</div></td></tr>'
        );
    }

    function headerLabel(col) {
        return $(col.header()).text().replace(/\s+/g, ' ').trim();
    }

    /**
     * Columns modal (colvis-item card grid, column-visibility.md). Hidden columns
     * are stored by LABEL, so adding or reordering a column never hides the wrong one.
     *
     * opts: { grid: '#selector', storageKey: 'string', skip: ['Action'] }
     */
    function columnVisibility(dt, opts) {
        if (!dt || !opts || !opts.grid) return;
        var skip = opts.skip || [];
        var key = opts.storageKey;

        function readHidden() {
            try {
                var arr = JSON.parse(window.localStorage.getItem(key) || '[]');
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function saveHidden(arr) {
            try { window.localStorage.setItem(key, JSON.stringify(arr)); } catch (e) { /* private mode */ }
        }

        var hidden = readHidden();
        dt.columns().every(function () {
            var label = headerLabel(this);
            if (label && hidden.indexOf(label) !== -1) this.visible(false, false);
        });
        dt.columns.adjust();

        var $grid = $(opts.grid).empty();
        dt.columns().every(function (idx) {
            var col = this;
            var label = headerLabel(col);
            if (!label || skip.indexOf(label) !== -1) return;
            var id = $grid.attr('id') + '_' + idx;
            var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', id)
                .prop('checked', col.visible())
                .on('change', function () {
                    var h = readHidden();
                    var pos = h.indexOf(label);
                    if (this.checked && pos !== -1) h.splice(pos, 1);
                    if (!this.checked && pos === -1) h.push(label);
                    saveHidden(h);
                    col.visible(this.checked, false);
                    dt.columns.adjust();
                });
            $grid.append(
                $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                    $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
                        .attr('for', id).append($cb).append($('<span></span>').text(label))
                )
            );
        });
    }

    /**
     * Visible columns × rows currently in scope (search applied, on-screen order,
     * every page that is loaded). Cell text only — no markup reaches the printout.
     *
     * opts: { skip: ['Action'] }
     */
    function collectRows(dt, opts) {
        var skip = (opts && opts.skip) || [];
        var cols = [];
        dt.columns().every(function (idx) {
            var label = headerLabel(this);
            if (this.visible() && skip.indexOf(label) === -1) cols.push({ idx: idx, label: label });
        });
        var rows = [];
        dt.rows({ search: 'applied', order: 'applied' }).every(function () {
            var rowNode = this.node();
            if (!rowNode) return;
            rows.push(cols.map(function (c) {
                var cell = dt.cell(rowNode, c.idx).node();
                return cell ? $(cell).text().replace(/\s+/g, ' ').trim() : '';
            }));
        });
        return { headers: cols.map(function (c) { return c.label; }), rows: rows };
    }

    /**
     * Branded LBSNAA printout (same header as the module's report PDFs), printed
     * from a hidden iframe so a popup blocker cannot swallow it.
     *
     * opts: { title, meta: ['Bill Month: …'], headers: [], rows: [[]], numericCols: [idx] }
     * Header images come from window.EstateReportPrintAssets
     * (partials/report_print_assets.blade.php).
     */
    function printGrid(opts) {
        var assets = window.EstateReportPrintAssets || {};
        var numeric = opts.numericCols || [];
        var now = new Date();

        var head = '<tr>' + opts.headers.map(function (h, i) {
            return '<th' + (numeric.indexOf(i) !== -1 ? ' class="num"' : '') + '>' + esc(h) + '</th>';
        }).join('') + '</tr>';
        var body = opts.rows.length
            ? opts.rows.map(function (r) {
                return '<tr>' + r.map(function (v, i) {
                    return '<td' + (numeric.indexOf(i) !== -1 ? ' class="num"' : '') + '>' + esc(v) + '</td>';
                }).join('') + '</tr>';
            }).join('')
            : '<tr><td colspan="' + opts.headers.length + '" style="text-align:center">No records.</td></tr>';

        var meta = (opts.meta || []).filter(Boolean).map(function (m) { return '<div>' + esc(m) + '</div>'; }).join('');

        var html =
            '<!doctype html><html><head><meta charset="utf-8"><title>' + esc(opts.title) + '</title><style>' +
            '@page{size:A4 landscape;margin:10mm;}' +
            'body{font-family:Arial,sans-serif;font-size:11px;color:#1f2937;margin:16px;}' +
            '.hdr{width:100%;border-collapse:collapse;margin-bottom:4px;}' +
            '.hdr td{vertical-align:middle;border:0;}' +
            '.hdr .logo{width:90px;text-align:center;}' +
            '.hdr .logo img{max-height:64px;max-width:84px;}' +
            '.hdr .center{text-align:center;padding:0 8px;}' +
            '.hdr .hi{height:18px;width:auto;margin-bottom:2px;}' +
            '.hdr .en{font-size:16px;font-weight:bold;color:#102a43;line-height:1.25;}' +
            '.title{text-align:center;font-size:20px;font-weight:bold;color:#004384;margin:8px 0 6px;padding-bottom:8px;border-bottom:2px solid #004384;}' +
            '.meta{margin-bottom:12px;font-size:11px;color:#555;text-align:center;}' +
            'table.grid{width:100%;border-collapse:collapse;margin-top:10px;}' +
            '.grid th,.grid td{border:1px solid #8fa3bd;padding:5px 7px;text-align:left;vertical-align:top;word-break:break-word;}' +
            '.grid .num{text-align:right;}' +
            '.grid thead{display:table-header-group;}' +
            '.grid thead th{background:#004384 !important;color:#fff !important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            '.grid tbody tr:nth-child(even){background:#eef2f8;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            '.grid tr{page-break-inside:avoid;}' +
            '.foot{margin-top:18px;text-align:center;font-size:10px;color:#666;border-top:1px solid #ccc;padding-top:10px;}' +
            '@media print{body{margin:0;}}' +
            '</style></head><body>' +
            '<table class="hdr"><tr>' +
            '<td class="logo">' + (assets.logoLeft ? '<img src="' + esc(assets.logoLeft) + '" alt="">' : '') + '</td>' +
            '<td class="center">' + (assets.titleHindi ? '<img class="hi" src="' + esc(assets.titleHindi) + '" alt="">' : '') +
            '<div class="en">Lal Bahadur Shastri National Academy of Administration, Mussoorie</div></td>' +
            '<td class="logo">' + (assets.logoRight ? '<img src="' + esc(assets.logoRight) + '" alt="">' : '') + '</td>' +
            '</tr></table>' +
            '<div class="title">' + esc(opts.title) + '</div>' +
            '<div class="meta">' + meta + '<div>Print Date: ' + esc(now.toLocaleDateString('en-GB')) + ' &middot; ' + opts.rows.length + ' record(s)</div></div>' +
            '<table class="grid"><thead>' + head + '</thead><tbody>' + body + '</tbody></table>' +
            '<div class="foot">Generated on ' + esc(now.toLocaleString('en-GB')) + '</div>' +
            '</body></html>';

        var old = document.getElementById('erPrintFrame');
        if (old) old.parentNode.removeChild(old);
        var frame = document.createElement('iframe');
        frame.id = 'erPrintFrame';
        frame.setAttribute('aria-hidden', 'true');
        frame.setAttribute('tabindex', '-1');
        frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
        document.body.appendChild(frame);
        var doc = frame.contentWindow.document;
        doc.open();
        doc.write(html);
        doc.close();
        // Give the logos a moment to load before the dialog opens.
        setTimeout(function () {
            try {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            } catch (e) {
                window.print();
            }
            setTimeout(function () { if (frame.parentNode) frame.parentNode.removeChild(frame); }, 1000);
        }, 500);
    }

    window.EstateReportGrid = {
        esc: esc,
        stateRow: stateRow,
        columnVisibility: columnVisibility,
        collectRows: collectRows,
        printGrid: printGrid
    };
})(window, jQuery);
