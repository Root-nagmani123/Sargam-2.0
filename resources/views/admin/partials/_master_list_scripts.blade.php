{{-- Shared client-side grid behaviour for the simple master lists
     (Country / State / District / City): status filter, DataTable paging +
     search, branded print, and column show/hide.

     Expects a table#masterTable rendered with `programme-dt-table`, each row
     carrying data-status, plus the toolbar controls (#masterStatusFilter,
     #masterResetFilters, #masterPrintBtn, #masterBtnColumns) and the
     _master_columns_modal partial.

     @include vars:
       reportTitle   string  — heading used in the printed report
       storageKey    string  — localStorage key for hidden columns
       printColumns  array   — [['label' => 'State Name', 'index' => 1], …]
       statusColumn  int     — column index holding the status toggle
       actionColumn  int     — column index holding the row actions
       defaultOrder  int     — column index to sort by initially (default 1)
--}}
<script>
$(function () {
    var $table = $('#masterTable');
    if (!$table.length || $table.find('tbody tr[data-status]').length === 0) { return; }

    var PRINT_COLUMNS = @json($printColumns ?? []);
    var STATUS_COL = @json($statusColumn ?? null);
    var ACTION_COL = @json($actionColumn ?? null);
    var REPORT_TITLE = @json($reportTitle ?? 'List');
    var STORAGE_KEY = @json($storageKey ?? 'masterGrid:hiddenColumns:v1');
    var DEFAULT_ORDER = @json($defaultOrder ?? 1);

    /* ---- Client-side Status filter ---- */
    $.fn.dataTable.ext.search.push(function (settings, searchData, dataIndex) {
        if (settings.nTable.id !== 'masterTable') { return true; }
        var want = $('#masterStatusFilter').val();
        if (!want || want === 'all') { return true; }
        var row = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
        return row ? (row.getAttribute('data-status') === want) : true;
    });

    var noSort = [0];
    if (STATUS_COL !== null) { noSort.push(STATUS_COL); }
    if (ACTION_COL !== null) { noSort.push(ACTION_COL); }

    var table = $table.DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        responsive: false,
        order: [[DEFAULT_ORDER, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        columnDefs: [
            { targets: noSort, orderable: false, searchable: false }
        ],
        language: {
            search: '',
            searchPlaceholder: 'Search',
            paginate: { previous: '‹', next: '›' },
            lengthMenu: 'Showing _MENU_',
            info: 'of _TOTAL_ items',
            infoEmpty: 'of 0 items',
            infoFiltered: 'of _MAX_ items'
        },
        drawCallback: function () {
            var info = this.api().page.info();
            this.api().column(0, { page: 'current' }).nodes().each(function (cell, i) {
                cell.innerHTML = info.start + i + 1;
            });
        }
    });

    $('#masterStatusFilter').on('change', function () { table.draw(); });
    $('#masterResetFilters').on('click', function () {
        $('#masterStatusFilter').val('all');
        table.search('').draw();
    });

    /* ---- Branded print (LBSNAA header) ---- */
    function cellText(html) {
        if (html === null || html === undefined) { return ''; }
        // Orthogonal cell data can arrive as an object — prefer its display value.
        if (typeof html === 'object') { html = html.display !== undefined ? html.display : ''; }
        var d = document.createElement('div');
        d.innerHTML = String(html);
        return (d.textContent || '').replace(/\s+/g, ' ').trim();
    }

    $('#masterPrintBtn').on('click', function () {
        var printWindow = window.open('', '_blank');
        if (!printWindow) { alert('Please allow pop-ups for this site to print the report.'); return; }

        // The status toggle and action buttons don't print meaningfully, so
        // status is rebuilt as text and the actions column is dropped.
        var headHtml = '<tr><th>S. No.</th>' +
            PRINT_COLUMNS.map(function (c) { return '<th>' + c.label + '</th>'; }).join('') +
            '<th>Status</th></tr>';

        var rowIdxs = table.rows({ search: 'applied', order: 'applied' }).indexes().toArray();
        var bodyHtml = rowIdxs.map(function (rowIdx, r) {
            var node = table.row(rowIdx).node();
            var active = node && node.getAttribute('data-status') === '1';
            return '<tr><td>' + (r + 1) + '</td>' +
                PRINT_COLUMNS.map(function (c) {
                    return '<td>' + cellText(table.cell(rowIdx, c.index).render('display')) + '</td>';
                }).join('') +
                '<td>' + (active ? 'Active' : 'Inactive') + '</td></tr>';
        }).join('');

        var dateStr = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
        var logoLeft  = @json(asset('admin_assets/images/logos/logo_new.png'));
        var logoRight = @json(file_exists(public_path('admin_assets/images/logos/constitution-75.png'))
            ? asset('admin_assets/images/logos/constitution-75.png')
            : asset('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png'));
        var titleHindi = @json(asset('admin_assets/images/logos/lbsnaa-title-hi.png'));

        var statusVal = $('#masterStatusFilter').val();
        var statusLine = (statusVal && statusVal !== 'all')
            ? ('Status: ' + $('#masterStatusFilter option:selected').text())
            : '';

        var printContent =
            '<!DOCTYPE html><html><head><title>' + REPORT_TITLE + ' - Print</title><style>' +
            'body{font-family:Arial,sans-serif;margin:16px;color:#1f2937;}' +
            '.pdf-hdr{width:100%;border-collapse:collapse;margin-bottom:4px;}' +
            '.pdf-hdr td{vertical-align:middle;} .pdf-hdr .logo{width:90px;text-align:center;}' +
            '.pdf-hdr .logo img{max-height:64px;max-width:84px;} .pdf-hdr .center{text-align:center;padding:0 8px;}' +
            '.pdf-hdr .inst-hi-img{height:18px;width:auto;margin-bottom:2px;}' +
            '.pdf-hdr .inst-en{font-size:16px;font-weight:bold;color:#102a43;line-height:1.25;}' +
            '.report-title{text-align:center;font-size:20px;font-weight:bold;color:#004a93;margin:8px 0 6px;padding-bottom:8px;border-bottom:2px solid #004a93;}' +
            '.print-info{margin-bottom:12px;font-size:11px;color:#666;text-align:center;}' +
            'table{width:100%;border-collapse:collapse;margin-top:10px;}' +
            'table th,table td{border:1px solid #8fa3bd;padding:6px 8px;text-align:left;font-size:12px;}' +
            'table thead th{font-weight:bold;background-color:#004a93 !important;color:#fff !important;text-align:center;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            'table tbody tr:nth-child(even){background-color:#eef2f8;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            '.print-footer{margin-top:18px;text-align:center;font-size:10px;color:#666;border-top:1px solid #ccc;padding-top:10px;}' +
            '@media print{@page{size:A4 portrait;margin:10mm;} body{margin:0;}}' +
            '</style></head><body onload="window.focus();window.print();">' +
            '<table class="pdf-hdr"><tr>' +
                '<td class="logo"><img src="' + logoLeft + '" alt=""></td>' +
                '<td class="center"><img class="inst-hi-img" src="' + titleHindi + '" alt="">' +
                    '<div class="inst-en">Lal Bahadur Shastri National Academy of Administration, Mussoorie</div>' +
                '</td>' +
                '<td class="logo"><img src="' + logoRight + '" alt=""></td>' +
            '</tr></table>' +
            '<div class="report-title">' + REPORT_TITLE + '</div>' +
            '<div class="print-info"><div>Print Date: ' + dateStr + '</div>' +
                (statusLine ? '<div>' + statusLine + '</div>' : '') +
                '<div>Total Records: ' + rowIdxs.length + '</div></div>' +
            '<table><thead>' + headHtml + '</thead><tbody>' + bodyHtml + '</tbody></table>' +
            '<div class="print-footer"><p>Generated on ' + new Date().toLocaleString() + '</p></div>' +
            '</body></html>';

        printWindow.document.open();
        printWindow.document.write(printContent);
        printWindow.document.close();
    });

    /* ---- Column show / hide ---- */
    function getHidden() { try { var a = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); return Array.isArray(a) ? a : []; } catch (e) { return []; } }
    function setHidden(a) { try { localStorage.setItem(STORAGE_KEY, JSON.stringify(a)); } catch (e) {} }

    (function setupColumns(dt) {
        var hidden = getHidden();
        dt.columns().every(function () { var idx = this.index(); this.visible(hidden.indexOf(idx) === -1, false); });
        dt.columns.adjust();

        var $grid = $('#masterColumnToggleGrid');
        if (!$grid.length) { return; }
        $grid.empty();
        dt.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }
            var inputId = 'mastercolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>').attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">').attr('id', inputId).prop('checked', hidden.indexOf(idx) === -1);
            $cb.on('change', function () {
                var h = getHidden(); var pos = h.indexOf(idx);
                if (this.checked) { if (pos !== -1) h.splice(pos, 1); } else { if (pos === -1) h.push(idx); }
                setHidden(h); dt.column(idx).visible(this.checked, false); dt.columns.adjust();
            });
            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label); $grid.append($cell);
        });
    })(table);
});
</script>
