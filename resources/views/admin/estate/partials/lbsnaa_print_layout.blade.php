{{-- LBSNAA print layout: document-style design, header repeats on every page. --}}
<script>
(function() {
    var logoUrl = {!! json_encode(asset('admin_assets/images/logos/logo.svg')) !!};

    var styles = '' +
        '@page{size:A4;margin:12mm;}@media print{body{background:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}}' +
        'body{font-family:"Segoe UI",Helvetica Neue,Arial,sans-serif;font-size:10.5pt;color:#334155;line-height:1.4;margin:0;padding:0;background:#fff;}' +
        '.lbsnaa-layout-table{width:100%;border-collapse:collapse;}' +
        '.lbsnaa-layout-table > thead > tr > td{padding:0;border:none;vertical-align:top;}' +
        '.lbsnaa-layout-table > tbody > tr > td{padding:0 0 12px 0;border:none;}' +
        '.lbsnaa-print-header{display:flex;align-items:center;gap:16px;padding:14px 20px;margin:0;background:#fff;border-bottom:3px solid #1e3a5f;}' +
        '.lbsnaa-print-logo-wrap{flex-shrink:0;}' +
        '.lbsnaa-print-logo{width:48px;height:48px;display:block;object-fit:contain;}' +
        '.lbsnaa-print-brand{flex:1;}' +
        '.lbsnaa-print-org{font-size:12pt;font-weight:700;color:#1e3a5f;margin:0 0 2px 0;letter-spacing:0.02em;}' +
        '.lbsnaa-print-sub{font-size:9pt;color:#64748b;margin:0;text-transform:uppercase;letter-spacing:0.08em;}' +
        '.lbsnaa-print-title-wrap{border-left:4px solid #c9a227;padding:6px 0 6px 12px;}' +
        '.lbsnaa-print-title{font-size:11pt;font-weight:700;color:#1e3a5f;margin:0;letter-spacing:0.04em;}' +
        '.lbsnaa-print-body{padding:16px 20px 0;}' +
        '.lbsnaa-print-footer{font-size:8pt;color:#94a3b8;text-align:center;padding:10px 20px;border-top:1px solid #e2e8f0;margin-top:16px;}' +
        '.lbsnaa-print-body table{width:100%;border-collapse:collapse;font-size:10px;}' +
        '.lbsnaa-print-body th,.lbsnaa-print-body td{border:1px solid #cbd5e1;padding:8px 10px;vertical-align:top;text-align:left;word-break:break-word;}' +
        '.lbsnaa-print-body th{background:#f1f5f9;color:#1e3a5f;font-weight:600;font-size:9.5px;text-transform:uppercase;letter-spacing:0.03em;}' +
        '.lbsnaa-print-body tbody tr:nth-child(even){background:#f8fafc;}' +
        '.lbsnaa-print-body thead{display:table-header-group;}' +
        '.lbsnaa-print-body tr{page-break-inside:avoid;}';

    function getDocumentHtml(reportTitle, bodyContent) {
        var logoImg = logoUrl
            ? '<img src="' + logoUrl + '" alt="LBSNAA" class="lbsnaa-print-logo">'
            : '<div class="lbsnaa-print-logo" style="width:48px;height:48px;border:2px solid #1e3a5f;border-radius:6px;font-size:10pt;font-weight:800;color:#1e3a5f;line-height:44px;text-align:center;">LBS</div>';
        var header = '<div class="lbsnaa-print-header">' +
            '<div class="lbsnaa-print-logo-wrap">' + logoImg + '</div>' +
            '<div class="lbsnaa-print-brand">' +
            '<p class="lbsnaa-print-org">Lal Bahadur Shastri National Academy of Administration</p>' +
            '<p class="lbsnaa-print-sub">Mussoorie · Estate Section</p>' +
            '</div>' +
            '<div class="lbsnaa-print-title-wrap"><p class="lbsnaa-print-title">' + (reportTitle || 'Report') + '</p></div>' +
            '</div>';
        var footer = '<div class="lbsnaa-print-footer">Estate Section, LBSNAA, Mussoorie</div>';
        var bodyHtml = '<table class="lbsnaa-layout-table">' +
            '<thead><tr><td>' + header + '</td></tr></thead>' +
            '<tbody><tr><td><div class="lbsnaa-print-body">' + (bodyContent || '') + '</div>' + footer + '</td></tr></tbody>' +
            '</table>';

        return '<!doctype html><html><head><meta charset="utf-8">' +
            '<title>' + (reportTitle || 'Print') + ' - LBSNAA</title>' +
            '<style>' + styles + '</style></head><body>' +
            bodyHtml +
            '</body></html>';
    }

    window.LBSNAAPrint = { getDocumentHtml: getDocumentHtml, logoUrl: logoUrl };
})();
</script>
