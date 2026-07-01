<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Student List - LBSNAA MUSSOORIE</title>
    <style>
        @page { margin: 12mm 10mm; }
        * { box-sizing: border-box; font-family: DejaVu Sans, Arial, Helvetica, sans-serif; }
        body { margin: 0; color: #1f2937; background: #fff; }

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
        .brand-1 {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #0b4a7e;
            font-weight: 600;
        }
        .brand-2 {
            font-size: 11pt;
            font-weight: 700;
            text-transform: uppercase;
            color: #111;
            margin-top: 2px;
        }
        .brand-3 {
            font-size: 7.5pt;
            color: #555;
            margin-top: 2px;
        }

        .report-title-block {
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #dee2e6;
        }
        .report-title {
            font-size: 11pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #0f172a;
            margin: 0 0 6px;
        }
        .report-meta {
            font-size: 8pt;
            color: #555;
        }

        table.data-table { width: 100%; border-collapse: collapse; font-size: 8pt; }
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
    </style>
</head>
<body>
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
</body>
</html>
