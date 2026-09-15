{{-- Shared print chrome for every Master-module export_print view.

     Branded like the module's export_pdf so the printout, the PDF, the .xlsx
     and the CSV can't drift apart — see docs/new-design-index-page.md §1. The
     class names are `mst-print-*` (not `ic-print-*`) so the Master module owns
     its own copy rather than depending on Centcom's; the palette is deliberately
     identical to the approved Centcom exports.

     ⚠️ print-color-adjust is the important bit. Browsers drop background colours
     when printing by default, so a white-on-navy header band came out as white
     text on white paper — an invisible header row. --}}
<style>
    @page { size: A4 {{ $orientation ?? 'portrait' }}; margin: 12mm 10mm; }

    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11px;
        color: #1f2937;
        margin: 0;
        padding: 16px;
    }

    /* ── Branded header: emblem + LBSNAA logo left, institution centre ── */
    table.mst-print-hdr { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    table.mst-print-hdr td { vertical-align: middle; padding: 0; }
    table.mst-print-hdr .mst-print-logo { width: 130px; white-space: nowrap; }
    table.mst-print-hdr .mst-print-logo img { height: 52px; width: auto; object-fit: contain; }
    table.mst-print-hdr .mst-print-logo img + img { margin-left: 6px; }
    table.mst-print-hdr .mst-print-centre { text-align: center; padding: 0 8px; }

    .mst-print-inst {
        font-size: 13px;
        font-weight: bold;
        color: #003366;
        line-height: 1.3;
        text-transform: uppercase;
    }
    .mst-print-sub { font-size: 10px; color: #4b5563; margin-top: 2px; }

    .mst-print-rule { border-bottom: 2px solid #003366; margin-bottom: 8px; }

    .mst-print-title {
        text-align: center;
        font-size: 14px;
        font-weight: bold;
        color: #003366;
        margin: 6px 0 2px;
        text-transform: uppercase;
    }
    .mst-print-meta { text-align: center; font-size: 9px; color: #6b7280; margin-bottom: 8px; }

    .mst-print-filters {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 6px 10px;
        margin-bottom: 10px;
        font-size: 10px;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .mst-print-total {
        text-align: center;
        font-size: 10px;
        font-weight: bold;
        color: #003366;
        background: #eef2f8;
        padding: 4px 0;
        margin-bottom: 8px;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* ── Data table ── */
    table.mst-print-table { width: 100%; border-collapse: collapse; font-size: 10px; table-layout: fixed; }

    table.mst-print-table thead th {
        background: #003366;
        color: #ffffff;
        font-weight: bold;
        text-align: left;
        padding: 7px 6px;
        border: 1px solid #002244;
        /* Without these the fill is dropped on paper and the white text vanishes. */
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    table.mst-print-table td {
        padding: 6px;
        border: 1px solid #dee2e6;
        vertical-align: top;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    table.mst-print-table tbody tr:nth-child(even) td {
        background: #f4f7fb;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Header row repeats when the table breaks across pages. */
    table.mst-print-table thead { display: table-header-group; }
    table.mst-print-table tr { page-break-inside: avoid; }

    .mst-print-empty { text-align: center; padding: 20px; color: #6b7280; }

    .mst-print-foot { margin-top: 10px; text-align: center; font-size: 8px; color: #6b7280; }

    @media print {
        body { padding: 0; }
    }
</style>
