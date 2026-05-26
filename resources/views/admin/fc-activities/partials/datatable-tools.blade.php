@once
    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
        <style>
            .fc-dt-toolbar-wrap {
                display: none;
                align-items: center;
                justify-content: flex-end;
                gap: 8px;
                flex-wrap: wrap;
            }
            .dataTables_wrapper .row.align-items-center.mb-2 {
                align-items: center !important;
            }
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                margin-top: 6px;
                margin-bottom: 6px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .dataTables_wrapper .dataTables_filter label,
            .dataTables_wrapper .dataTables_length label {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                margin-bottom: 0;
            }
            .dataTables_wrapper .dataTables_filter input {
                height: 32px;
                margin-left: 0 !important;
            }
            .dataTables_filter .fc-dt-toolbar-wrap {
                display: inline-flex;
            }
            .fc-dt-toolbar-wrap .dropdown-menu {
                max-height: 260px;
                overflow: auto;
                min-width: 220px;
            }
            .fc-dt-toolbar-wrap .fc-colvis-menu .fc-colvis-item {
                display: flex;
                align-items: center;
                gap: 0.55rem;
                padding: 0.4rem 0.85rem;
                margin: 0;
                cursor: pointer;
                white-space: normal;
            }
            .fc-dt-toolbar-wrap .fc-colvis-menu .fc-colvis-item:hover {
                background-color: var(--bs-dropdown-link-hover-bg, #f8f9fa);
            }
            .fc-dt-toolbar-wrap .fc-colvis-menu .fc-colvis-checkbox {
                float: none;
                position: static;
                margin: 0;
                width: 1rem;
                height: 1rem;
                min-width: 1rem;
                flex-shrink: 0;
                border: 2px solid #495057;
                background-color: #fff;
                cursor: pointer;
                opacity: 1;
                box-shadow: none;
            }
            .fc-dt-toolbar-wrap .fc-colvis-menu .fc-colvis-checkbox:checked {
                background-color: #004a93;
                border-color: #004a93;
            }
            .fc-dt-toolbar-wrap .fc-colvis-menu .fc-colvis-checkbox:focus {
                border-color: #004a93;
                box-shadow: 0 0 0 0.15rem rgba(0, 74, 147, 0.25);
            }
            .fc-dt-toolbar-wrap .fc-colvis-menu .fc-colvis-label {
                flex: 1;
                font-size: 12px;
                line-height: 1.35;
                cursor: pointer;
                user-select: none;
            }
            .fc-dt-toolbar-wrap .btn {
                border-radius: 6px !important;
                font-size: 12px;
                padding: 6px 10px;
                line-height: 1.2;
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }
            .fc-dt-toolbar-wrap .btn .material-icons {
                font-size: 16px;
            }
            .fc-dt-toolbar-wrap .btn-group .btn {
                border-radius: 0 !important;
            }
            .fc-dt-toolbar-wrap .btn-group .btn:first-child {
                border-top-left-radius: 6px !important;
                border-bottom-left-radius: 6px !important;
            }
            .fc-dt-toolbar-wrap .btn-group .btn:last-child {
                border-top-right-radius: 6px !important;
                border-bottom-right-radius: 6px !important;
            }
            .fc-dt-toolbar-wrap .dropdown-item {
                font-size: 12px;
            }
            .fc-dt-hidden-buttons {
                display: none !important;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
        <script>
            (function () {
                function cssEscapeSimple(v) {
                    return String(v).replace(/([ #;?%&,.+*~\':"!^$[\]()=>|\/@])/g, '\\$1');
                }

                function buildFilterSummary(dt) {
                    var parts = [];
                    var globalSearch = dt.search();
                    if (globalSearch) parts.push('Search: ' + globalSearch);
                    return parts.length ? parts.join(' | ') : '';
                }

                function getExportColumnIndexes(dt) {
                    var out = [];
                    dt.columns().every(function (idx) {
                        if (!this.visible()) return;
                        if ($(this.header()).hasClass('fc-dt-no-export')) return;
                        out.push(idx);
                    });
                    return out;
                }

                function extractCellText(cellNode) {
                    if (!cellNode) return '';
                    var $cell = $(cellNode).clone();
                    $cell.find('input,button,select,textarea,a').remove();
                    var $titled = $cell.find('[title]').first();
                    if ($titled.length) {
                        var title = String($titled.attr('title') || '').trim();
                        if (title) return title;
                    }
                    return ($cell.text() || '').replace(/\s+/g, ' ').trim();
                }

                function exportRows(dt) {
                    return dt.rows({ search: 'applied', page: 'all' });
                }

                function buildPrintableTableHtml(dt, visIdx) {
                    var html = '<thead><tr>';
                    visIdx.forEach(function (ci) {
                        html += '<th>' + ($(dt.column(ci).header()).text() || '').trim() + '</th>';
                    });
                    html += '</tr></thead><tbody>';

                    exportRows(dt).nodes().each(function (rowNode) {
                        var $row = $(rowNode);
                        if ($row.hasClass('child')) return;
                        html += '<tr>';
                        visIdx.forEach(function (ci) {
                            var cellNode = dt.cell(rowNode, ci).node();
                            html += '<td>' + extractCellText(cellNode) + '</td>';
                        });
                        html += '</tr>';
                    });
                    html += '</tbody>';
                    return html;
                }

                function openBrandedPrintWindow(title, filterLine, tableHtml, autoPrint) {
                    var emblemUrl = '{{ asset("images/ashoka.png") }}';
                    var logoUrl = '{{ asset("admin_assets/images/logos/logo.png") }}';
                    var printWindow = window.open('', '_blank');
                    if (!printWindow) return;

                    printWindow.document.open();
                    printWindow.document.write('<!doctype html><html lang="en"><head><meta charset="utf-8"><title>' + title + '</title>' +
                        '<style>' +
                        '*,*::before,*::after{box-sizing:border-box} body{font-family:"Segoe UI",system-ui,-apple-system,sans-serif;font-size:11px;color:#212529;margin:0;padding:12mm 10mm;-webkit-print-color-adjust:exact;print-color-adjust:exact}' +
                        '.print-header{display:flex;align-items:center;gap:12px;border-bottom:3px solid #004a93;padding-bottom:10px;margin-bottom:12px}' +
                        '.print-header img{height:48px;width:auto;object-fit:contain}' +
                        '.header-text{flex:1}.header-text .line1{font-size:9px;text-transform:uppercase;letter-spacing:.08em;color:#004a93;font-weight:600;margin:0}' +
                        '.header-text .line2{font-size:14px;font-weight:700;text-transform:uppercase;color:#1a1a1a;margin:2px 0 0}' +
                        '.header-text .line3{font-size:9px;color:#555;margin:1px 0 0}' +
                        '.report-title-block{text-align:center;margin-bottom:10px}.report-title-block h2{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin:0 0 4px;color:#1a1a1a}' +
                        '.date-pill{display:inline-block;background:#004a93;color:#fff;padding:3px 14px;border-radius:10px;font-size:10px;font-weight:500;border:1px solid #004a93}' +
                        '.report-meta{font-size:10px;line-height:1.7;margin:8px 0 10px;color:#333}' +
                        '.data-table{width:max-content;min-width:100%;border-collapse:collapse;font-size:9px}.data-table th,.data-table td{padding:3px 5px;border:1px solid #bbb;vertical-align:middle;word-break:normal;white-space:nowrap}' +
                        '.data-table thead th{background:#004a93;color:#fff;font-weight:600;font-size:10px;text-align:left}' +
                        '.data-table tbody tr:nth-child(even) td{background:#f9fafb}' +
                        '.footer{border-top:1px solid #dee2e6;font-size:8px;color:#666;text-align:center;padding-top:4px;margin-top:8px}' +
                        '@page{size:A4 landscape;margin:8mm} @media print{body{padding:0} thead{display:table-header-group} tr{page-break-inside:avoid}}' +
                        '</style></head><body>' +
                        '<div class="print-header"><img src="' + emblemUrl + '" alt="Emblem"><div class="header-text"><p class="line1">Government of India</p><p class="line2">LBSNAA MUSSOORIE</p><p class="line3">Lal Bahadur Shastri National Academy of Administration</p></div><img src="' + logoUrl + '" alt="LBSNAA Logo"></div>' +
                        '<div class="report-title-block"><h2>' + title + '</h2>' + (filterLine ? ('<span class="date-pill">' + filterLine + '</span>') : '') + '</div>' +
                        '<div class="report-meta"><strong>Printed:</strong> ' + new Date().toLocaleDateString('en-IN') + ' ' + new Date().toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit'}) + '</div>' +
                        '<table class="data-table">' + tableHtml + '</table>' +
                        '<div class="footer"><small>LBSNAA Mussoorie - FC Activity Report</small></div>' +
                        (autoPrint ? '<script>window.addEventListener("load",function(){setTimeout(function(){window.print()},250)});<\/script>' : '') +
                        '</body></html>');
                    printWindow.document.close();
                }

                function toDataUrl(url) {
                    return fetch(url).then(function (r) {
                        if (!r.ok) throw new Error('Image fetch failed: ' + url);
                        return r.blob();
                    }).then(function (blob) {
                        return new Promise(function (resolve, reject) {
                            var fr = new FileReader();
                            fr.onloadend = function () { resolve(fr.result); };
                            fr.onerror = reject;
                            fr.readAsDataURL(blob);
                        });
                    });
                }

                function toPdfMakeTableBody(dt, visIdx) {
                    var body = [];
                    var header = visIdx.map(function (ci) {
                        return { text: ($(dt.column(ci).header()).text() || '').trim(), style: 'tableHeader' };
                    });
                    body.push(header);

                    exportRows(dt).nodes().each(function (rowNode) {
                        var row = [];
                        visIdx.forEach(function (ci) {
                            var cellNode = dt.cell(rowNode, ci).node();
                            row.push(extractCellText(cellNode));
                        });
                        body.push(row);
                    });
                    return body;
                }

                function isWideExportTable($table, visCount) {
                    return visCount > 10
                        || String($table.data('exportWide') ?? $table.data('export-wide') ?? '') === '1'
                        || $table.data('exportWide') === true
                        || $table.data('export-wide') === true;
                }

                function buildPdfColumnWidths($table, visCount) {
                    if (isWideExportTable($table, visCount) && visCount > 2) {
                        var widths = [72, 58];
                        for (var i = 2; i < visCount; i++) {
                            widths.push(34);
                        }
                        return widths;
                    }
                    var widths = [];
                    for (var j = 0; j < visCount; j++) {
                        widths.push('*');
                    }
                    return widths;
                }

                function buildPdfPageSize($table, visCount) {
                    if (!isWideExportTable($table, visCount)) {
                        return { pageSize: 'A4', pageOrientation: 'landscape' };
                    }
                    var tableWidth = 130 + Math.max(0, visCount - 2) * 34;
                    return {
                        pageSize: {
                            width: Math.min(Math.max(841.89, tableWidth), 2400),
                            height: 595.28
                        },
                        pageOrientation: 'landscape'
                    };
                }

                function wireFcDtToolbar($table, $toolbar, dt, title) {
                    $toolbar.show();
                    var $wrapper = $table.closest('.dataTables_wrapper');
                    var $filter = $wrapper.find('.dataTables_filter');
                    if ($filter.length) {
                        $filter.addClass('d-flex align-items-center justify-content-end flex-wrap gap-2');
                        $filter.append($toolbar);
                    }

                    var $colMenu = $toolbar.find('.fc-colvis-menu');
                    dt.columns().every(function (idx) {
                        var col = this;
                        var label = ($(col.header()).text() || '').trim();
                        if (!label) return;
                        var item = $(
                            '<li>' +
                            '<label class="dropdown-item fc-colvis-item mb-0">' +
                            '<input type="checkbox" class="form-check-input fc-colvis-checkbox" data-col="' + idx + '">' +
                            '<span class="fc-colvis-label">' + label + '</span>' +
                            '</label></li>'
                        );
                        item.find('input').prop('checked', col.visible()).on('change', function () {
                            col.visible($(this).prop('checked'));
                        });
                        $colMenu.append(item);
                    });

                    function exportRowCount() {
                        var info = dt.page.info();
                        if (info.serverSide) {
                            return info.recordsDisplay;
                        }
                        return exportRows(dt).count();
                    }

                    $toolbar.find('.fc-btn-print').on('click', function () {
                        var vis = getExportColumnIndexes(dt);
                        if (vis.length === 0) {
                            window.alert('Select at least one column using the Columns menu before exporting.');
                            return;
                        }
                        var n = exportRowCount();
                        if (n > 4000 && !window.confirm('This export has ' + n + ' rows. Printing may take a long time or freeze the tab. Continue?')) {
                            return;
                        }
                        openBrandedPrintWindow(title, buildFilterSummary(dt), buildPrintableTableHtml(dt, vis), true);
                    });

                    $toolbar.find('.fc-btn-excel').on('click', function () {
                        var vis = getExportColumnIndexes(dt);
                        if (vis.length === 0) {
                            window.alert('Select at least one column using the Columns menu before exporting.');
                            return;
                        }
                        dt.button('.fc-dt-excel').trigger();
                    });

                    $toolbar.find('.fc-btn-pdf').on('click', function () {
                        var $pdfBtn = $(this);
                        if ($pdfBtn.prop('disabled')) return;

                        var vis = getExportColumnIndexes(dt);
                        if (vis.length === 0) {
                            window.alert('Select at least one column using the Columns menu before exporting.');
                            return;
                        }

                        var rowCount = exportRowCount();
                        if (rowCount > 4000 && !window.confirm('This export has ' + rowCount + ' rows. PDF generation may freeze the page for a minute or more. Continue?')) {
                            return;
                        }

                        var origHtml = $pdfBtn.html();
                        $pdfBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> PDF…');

                        setTimeout(function () {
                            (async function () {
                                try {
                        var filterLine = buildFilterSummary(dt);
                        var wide = isWideExportTable($table, vis.length);
                        var pageLayout = buildPdfPageSize($table, vis.length);
                        var emblemUrl = '{{ asset("images/ashoka.png") }}';
                        var logoUrl = '{{ asset("admin_assets/images/logos/logo.png") }}';
                        var emblemData = null;
                        var logoData = null;
                        try { emblemData = await toDataUrl(emblemUrl); } catch (e) { emblemData = null; }
                        try { logoData = await toDataUrl(logoUrl); } catch (e) { logoData = null; }

                        var docDefinition = {
                            pageSize: pageLayout.pageSize,
                            pageOrientation: pageLayout.pageOrientation,
                            pageMargins: [10, 10, 10, 10],
                            content: [
                                {
                                    columns: [
                                        emblemData ? { image: emblemData, width: 36 } : { text: '' },
                                        {
                                            width: '*',
                                            stack: [
                                                { text: 'Government of India', fontSize: 8, color: '#004a93', bold: true, alignment: 'center' },
                                                { text: 'LBSNAA MUSSOORIE', fontSize: 13, bold: true, alignment: 'center' },
                                                { text: 'Lal Bahadur Shastri National Academy of Administration', fontSize: 8, color: '#555', alignment: 'center' }
                                            ]
                                        },
                                        logoData ? { image: logoData, width: 42, alignment: 'right' } : { text: '' }
                                    ],
                                    margin: [0, 0, 0, 6]
                                },
                                { text: title, style: 'reportTitle' },
                                ...(filterLine ? [{
                                    text: filterLine,
                                    style: 'filterLine',
                                    margin: [0, 2, 0, 8]
                                }] : []),
                                {
                                    table: {
                                        headerRows: 1,
                                        widths: buildPdfColumnWidths($table, vis.length),
                                        body: toPdfMakeTableBody(dt, vis)
                                    },
                                    layout: {
                                        hLineWidth: function () { return 0.8; },
                                        vLineWidth: function () { return 0.8; },
                                        hLineColor: function () { return '#c7c7c7'; },
                                        vLineColor: function () { return '#c7c7c7'; },
                                        fillColor: function (rowIndex) {
                                            return rowIndex > 0 && rowIndex % 2 === 0 ? '#f8fafc' : null;
                                        },
                                        paddingLeft: function () { return 3; },
                                        paddingRight: function () { return 3; },
                                        paddingTop: function () { return 2; },
                                        paddingBottom: function () { return 2; }
                                    }
                                }
                            ],
                            styles: {
                                reportTitle: { bold: true, fontSize: 12, color: '#1a1a1a', alignment: 'center' },
                                filterLine: { fontSize: 8, color: '#333', alignment: 'center' },
                                tableHeader: {
                                    bold: true,
                                    color: 'white',
                                    fillColor: '#004a93',
                                    fontSize: wide ? 5 : 8,
                                    alignment: 'left',
                                    noWrap: false
                                }
                            },
                            defaultStyle: {
                                fontSize: wide ? 5 : 7,
                                alignment: 'left',
                                noWrap: false
                            }
                        };
                        pdfMake.createPdf(docDefinition).download((title || 'report') + '.pdf');
                                } catch (err) {
                                    console.error('FC PDF export failed', err);
                                    window.alert('PDF export failed. Try fewer rows (filter the table) or use Excel.');
                                } finally {
                                    $pdfBtn.prop('disabled', false).html(origHtml);
                                }
                            })();
                        }, 50);
                    });
                }

                function setupTable($table) {
                    if ($table.hasClass('dataTable')) return;

                    var title = $table.data('export-title') || document.title || 'FC Activities Report';
                    var $thead = $table.find('thead');
                    if (!$thead.length) return;

                    var toolbarId = 'fcdt-toolbar-' + Math.random().toString(36).slice(2, 10);
                    var $toolbar = $(
                        '<div class="fc-dt-toolbar-wrap no-print" id="' + toolbarId + '">' +
                            '<div class="dropdown d-inline-block" data-bs-auto-close="outside">' +
                                '<button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle d-inline-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">' +
                                    '<i class="material-icons material-symbols-rounded">view_column</i><span>Columns</span>' +
                                '</button>' +
                                '<ul class="dropdown-menu dropdown-menu-end py-2 fc-colvis-menu"></ul>' +
                            '</div>' +
                            '<div class="btn-group shadow-sm" role="group" aria-label="Print or download PDF">' +
                                '<button type="button" class="btn btn-outline-primary btn-sm fc-btn-print"><i class="material-icons material-symbols-rounded">print</i><span>Print</span></button>' +
                                '<button type="button" class="btn btn-outline-primary btn-sm fc-btn-pdf"><i class="material-icons material-symbols-rounded">picture_as_pdf</i><span>PDF</span></button>' +
                            '</div>' +
                            '<button type="button" class="btn btn-success btn-sm fc-btn-excel"><i class="material-icons material-symbols-rounded">table_view</i><span>Excel</span></button>' +
                        '</div>'
                    );
                    $table.before($toolbar);

                    var serverAjax = $table.data('serverAjax');
                    var dt;
                    var commonDom = '<"row align-items-center mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row align-items-center mt-2"<"col-md-5"i><"col-md-7"p>>';
                    var excelBtn = {
                        extend: 'excelHtml5',
                        className: 'fc-dt-hidden-buttons fc-dt-excel',
                        title: title,
                        messageTop: function () {
                            var line = buildFilterSummary(dt);
                            return line || null;
                        },
                        exportOptions: {
                            columns: function (idx, data, node) {
                                if ($(node).hasClass('fc-dt-no-export')) return false;
                                return dt.column(idx).visible();
                            },
                            modifier: { search: 'applied', page: 'all' }
                        }
                    };

                    if (serverAjax) {
                        var fcFormSel = $table.data('filterForm');
                        var fcOtcodeInp = $table.data('filterOtcode');
                        var fcActSel = $table.data('filterActivity');
                        dt = $table.DataTable({
                            processing: true,
                            serverSide: true,
                            pageLength: 25,
                            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                            orderCellsTop: true,
                            fixedHeader: false,
                            scrollX: true,
                            autoWidth: false,
                            responsive: false,
                            deferRender: true,
                            order: [[6, 'desc']],
                            ajax: {
                                url: serverAjax,
                                data: function (d) {
                                    d.filter_form_id = fcFormSel ? String($(fcFormSel).val() || '').trim() : '';
                                    d.filter_otcode = fcOtcodeInp ? String($(fcOtcodeInp).val() || '').trim() : '';
                                    d.filter_activity = fcActSel ? String($(fcActSel).val() || '').trim() : '';
                                }
                            },
                            columns: [
                                { data: null, name: 'rownum', searchable: false, orderable: false, className: 'text-muted text-nowrap', render: function (data, type, row, meta) {
                                    return meta.row + 1 + meta.settings._iDisplayStart;
                                }},
                                { data: 'ot_name', name: 'ot_name', searchable: false, orderable: true },
                                { data: 'ot_code', name: 'ot_code', searchable: false, orderable: true },
                                { data: 'course', name: 'course', searchable: true, orderable: true },
                                { data: 'activity_label', name: 'activity_label', searchable: false, orderable: true },
                                { data: 'activityval', name: 'activityval', searchable: true, orderable: true },
                                { data: 'activitydt', name: 'activitydt', searchable: true, orderable: true },
                                { data: 'action', name: 'action', searchable: false, orderable: false, className: 'fc-dt-no-export', render: function (d) { return d; } }
                            ],
                            dom: commonDom,
                            buttons: [excelBtn]
                        });
                    } else {
                        dt = $table.DataTable({
                            pageLength: 25,
                            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                            orderCellsTop: true,
                            fixedHeader: false,
                            scrollX: true,
                            autoWidth: false,
                            responsive: false,
                            deferRender: true,
                            processing: true,
                            dom: commonDom,
                            buttons: [excelBtn]
                        });
                    }

                    wireFcDtToolbar($table, $toolbar, dt, title);
                }

                $(function () {
                    $('table.js-fc-datatable').each(function () {
                        setupTable($(this));
                    });
                });
            })();
        </script>
    @endpush
@endonce
