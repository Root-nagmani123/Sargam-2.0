<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student List - LBSNAA MUSSOORIE</title>
    <style>
        @page { size: A4 landscape; margin: 12mm 10mm; }
        * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
        html, body { margin: 0; padding: 0; color: #1f2937; background: #e9edf2; }

        /* On screen the report sits on a centred "paper" sheet; print resets it. */
        .print-sheet {
            width: 277mm;
            max-width: 100%;
            margin: 18px auto;
            padding: 16mm 12mm;
            background: #fff;
            box-shadow: 0 6px 24px rgba(16, 24, 40, 0.18);
        }

        .pdf-header {
            border-bottom: 2.5px solid #0b4a7e;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .pdf-header table { width: 100%; border-collapse: collapse; }
        .pdf-header td { border: 0; padding: 0; vertical-align: middle; }
        .pdf-header .hdr-left { width: 50px; }
        .pdf-header .hdr-left img { width: 42px; height: 42px; }
        .pdf-header .hdr-center { padding-left: 10px; }
        .pdf-header .hdr-right { width: 50px; text-align: right; }
        .pdf-header .hdr-right img { width: 42px; height: 42px; }
        .brand-1 { font-size: 8px; text-transform: uppercase; letter-spacing: 0.06em; color: #0b4a7e; font-weight: 600; }
        .brand-2 { font-size: 15px; font-weight: 700; text-transform: uppercase; color: #111; margin-top: 2px; }
        .brand-3 { font-size: 10px; color: #555; margin-top: 2px; }

        .report-title-block {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #dee2e6;
        }
        .report-title {
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #0f172a;
            margin: 0 0 6px;
        }
        .report-meta { font-size: 11px; color: #555; }

        table.data-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        table.data-table thead th {
            background: #004a93;
            color: #fff;
            border: 1px solid #003a75;
            padding: 5px 4px;
            text-align: left;
            font-weight: bold;
        }
        table.data-table tbody td {
            border: 1px solid #e5e7eb;
            padding: 4px;
            vertical-align: top;
        }
        table.data-table tbody tr:nth-child(even) td { background: #fafafa; }
        .empty { text-align: center; padding: 18px; color: #6b7280; }

        /* Floating toolbar — never printed. */
        .print-toolbar {
            position: fixed;
            top: 14px;
            right: 18px;
            display: flex;
            gap: 8px;
            z-index: 10;
        }
        .print-toolbar button {
            font: 600 13px/1 Arial, sans-serif;
            padding: 9px 16px;
            border-radius: 8px;
            border: 1px solid #004a93;
            cursor: pointer;
        }
        .print-toolbar .btn-print { background: #004a93; color: #fff; }
        .print-toolbar .btn-close { background: #fff; color: #004a93; }

        @media print {
            html, body { background: #fff; }
            .print-sheet { width: auto; margin: 0; padding: 0; box-shadow: none; }
            .print-toolbar { display: none !important; }
            table.data-table thead { display: table-header-group; }
            table.data-table tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <button type="button" class="btn-print" onclick="window.print()">Print</button>
        <button type="button" class="btn-close" onclick="window.close()">Close</button>
    </div>

    <div class="print-sheet">
        @include('admin.partials.pdf_lbsnaa_official_header')

        <div class="report-title-block">
            <h1 class="report-title">Student List</h1>
            <div class="report-meta">
                Generated: {{ $generatedAt }}
                &nbsp;|&nbsp; {{ count($rows) }} record(s)
                &nbsp;|&nbsp; Filters: {{ $filterSummary }}
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    @foreach($headings as $heading)
                    <th>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)
                    <td>{{ $cell }}</td>
                    @endforeach
                </tr>
                @empty
                <tr>
                    <td class="empty" colspan="{{ max(count($headings), 1) }}">No records found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        // Open the print dialog automatically once the report has rendered.
        window.addEventListener('load', function () {
            window.focus();
            window.setTimeout(function () { window.print(); }, 250);
        });
    </script>
</body>
</html>
