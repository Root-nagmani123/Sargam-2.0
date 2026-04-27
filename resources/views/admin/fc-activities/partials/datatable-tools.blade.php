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

                function getVisibleColumnIndexes(dt) {
                    var vis = [];
                    dt.columns().every(function (idx) {
                        if (this.visible()) vis.push(idx);
                    });
                    return vis;
                }

                function buildPrintableTableHtml(dt, visIdx) {
                    var html = '<thead><tr>';
                    visIdx.forEach(function (ci) {
                        html += '<th>' + ($(dt.column(ci).header()).text() || '').trim() + '</th>';
                    });
                    html += '</tr></thead><tbody>';

                    dt.rows({ search: 'applied' }).nodes().each(function (rowNode) {
                        var $row = $(rowNode);
                        if ($row.hasClass('child')) return;
                        html += '<tr>';
                        visIdx.forEach(function (ci) {
                            var cellNode = dt.cell(rowNode, ci).node();
                            var cellHtml = '';
                            if (cellNode) {
                                var $cell = $(cellNode).clone();
                                $cell.find('input,button,select,textarea,a').remove();
                                cellHtml = ($cell.text() || '').trim();
                            }
                            html += '<td>' + cellHtml + '</td>';
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
                        '.data-table{width:100%;border-collapse:collapse;font-size:10px}.data-table th,.data-table td{padding:4px 6px;border:1px solid #bbb;vertical-align:middle;word-break:break-word;white-space:normal}' +
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

                    dt.rows({ search: 'applied' }).nodes().each(function (rowNode) {
                        var row = [];
                        visIdx.forEach(function (ci) {
                            var cellNode = dt.cell(rowNode, ci).node();
                            var txt = '';
                            if (cellNode) {
                                txt = ($(cellNode).text() || '').replace(/\s+/g, ' ').trim();
                            }
                            row.push(txt);
                        });
                        body.push(row);
                    });
                    return body;
                }

                function buildPdfColumnWidths(visCount) {
                    var widths = [];
                    for (var i = 0; i < visCount; i++) {
                        widths.push('*');
                    }
                    return widths;
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

                    var dt = $table.DataTable({
                        pageLength: 25,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                        orderCellsTop: true,
                        fixedHeader: false,
                        scrollX: true,
                        autoWidth: false,
                        dom: '<"row align-items-center mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row align-items-center mt-2"<"col-md-5"i><"col-md-7"p>>',
                        buttons: [
                            {
                                extend: 'excelHtml5',
                                className: 'fc-dt-hidden-buttons fc-dt-excel',
                                title: title,
                                messageTop: function () {
                                    var line = buildFilterSummary(dt);
                                    return line || null;
                                },
                                exportOptions: { columns: ':visible' }
                            }
                        ]
                    });

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
                            '<li><div class="dropdown-item px-3 py-1">' +
                            '<div class="form-check d-flex align-items-center mb-0">' +
                            '<input type="checkbox" class="form-check-input me-2" data-col="' + idx + '">' +
                            '<label class="form-check-label cursor-pointer">' + label + '</label>' +
                            '</div></div></li>'
                        );
                        item.find('input').prop('checked', col.visible()).on('change', function () {
                            col.visible($(this).prop('checked'));
                        });
                        item.find('label').on('click', function (e) {
                            e.preventDefault();
                            var $cb = item.find('input');
                            $cb.prop('checked', !$cb.prop('checked')).trigger('change');
                        });
                        $colMenu.append(item);
                    });

                    $toolbar.find('.fc-btn-print').on('click', function () {
                        var vis = getVisibleColumnIndexes(dt);
                        openBrandedPrintWindow(title, buildFilterSummary(dt), buildPrintableTableHtml(dt, vis), true);
                    });

                    $toolbar.find('.fc-btn-excel').on('click', function () {
                        dt.button('.fc-dt-excel').trigger();
                    });

                    $toolbar.find('.fc-btn-pdf').on('click', async function () {
                        var vis = getVisibleColumnIndexes(dt);
                        var filterLine = buildFilterSummary(dt);
                        var emblemUrl = '{{ asset("images/ashoka.png") }}';
                        var logoUrl = '{{ asset("admin_assets/images/logos/logo.png") }}';
                        var emblemData = null;
                        var logoData = null;
                        try { emblemData = await toDataUrl(emblemUrl); } catch (e) { emblemData = null; }
                        try { logoData = await toDataUrl(logoUrl); } catch (e) { logoData = null; }

                        var docDefinition = {
                            pageSize: 'A4',
                            pageOrientation: 'landscape',
                            pageMargins: [14, 14, 14, 14],
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
                                        widths: buildPdfColumnWidths(vis.length),
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
                                    fontSize: 8,
                                    alignment: 'center',
                                    noWrap: false
                                }
                            },
                            defaultStyle: {
                                fontSize: 7,
                                alignment: 'center',
                                noWrap: false
                            }
                        };
                        pdfMake.createPdf(docDefinition).download((title || 'report') + '.pdf');
                    });
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
