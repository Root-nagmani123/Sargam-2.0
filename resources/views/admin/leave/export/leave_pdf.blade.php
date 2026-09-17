@php
    $headings = $headings ?? [];
    $rows = $rows ?? collect();
    $reportTitle = $reportTitle ?? 'Leave Report';
    $filterLine = $filterLine ?? '';
    $printedOn = $printedOn ?? now()->format('d-m-Y H:i');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }} — LBSNAA</title>
    <style>
        @page { size: A4 landscape; margin: 10mm 8mm; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; padding: 0; color: #1f2937; font-size: 8px; }

        .inst { text-align: center; font-size: 12px; font-weight: bold; color: #102a43; line-height: 1.3; }
        .inst-sub { text-align: center; font-size: 8px; color: #486581; margin-top: 1px; }

        .report-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            color: #004a93;
            margin: 8px 0 4px;
            padding-bottom: 5px;
            border-bottom: 2px solid #004a93;
        }

        .meta { font-size: 7.5px; color: #444; margin: 0 0 7px; text-align: center; }

        table.data-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data-table th,
        table.data-table td {
            border: 0.8px solid #8fa3bd;
            padding: 3px 4px;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
            overflow-wrap: break-word;
        }
        table.data-table thead th {
            background: #004a93;
            color: #fff;
            font-size: 7.5px;
            font-weight: bold;
        }
        table.data-table tbody tr:nth-child(even) td { background: #f3f6fa; }
        table.data-table tbody td { font-size: 7.5px; }

        .empty { text-align: center; padding: 14px; color: #667085; font-style: italic; }
    </style>
</head>
<body>
    <div class="inst">Lal Bahadur Shastri National Academy of Administration</div>
    <div class="inst-sub">Mussoorie, Uttarakhand</div>

    <div class="report-title">{{ $reportTitle }}</div>

    <div class="meta">
        @if($filterLine){{ $filterLine }} &nbsp;|&nbsp; @endif
        Total Records: {{ $rows->count() }} &nbsp;|&nbsp; Printed On: {{ $printedOn }}
    </div>

    @if($rows->isEmpty())
        <div class="empty">No leave applications found for the selected filters.</div>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    @foreach($headings as $heading)
                        <th>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
