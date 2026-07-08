@php
    $headings = $headings ?? [];
    $rows = $rows ?? collect();
    $filterLine = $filterLine ?? '';
    $printedOn = $printedOn ?? now()->format('d-m-Y H:i');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Student Medical Exemption — LBSNAA</title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 7px;
            color: #212529;
            margin: 0;
            padding: 4px;
        }
        .report-header {
            text-align: center;
            border-bottom: 2px solid #004a93;
            margin-bottom: 8px;
            padding-bottom: 6px;
        }
        .report-header h1 {
            margin: 0;
            font-size: 13px;
            color: #004a93;
            text-transform: uppercase;
        }
        .report-header p {
            margin: 3px 0 0;
            font-size: 8px;
            color: #555;
        }
        .meta {
            font-size: 7px;
            color: #444;
            margin-bottom: 6px;
            text-align: center;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.data-table th,
        table.data-table td {
            border: 1px solid #333;
            padding: 3px 4px;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
        }
        table.data-table th {
            background: #af2910;
            color: #fff;
            font-weight: 700;
            font-size: 6.5px;
        }
        table.data-table tbody tr:nth-child(even) {
            background: #f5f5f5;
        }
        a.doc-link {
            color: #004a93;
            text-decoration: underline;
            word-break: break-all;
        }
        .footer {
            margin-top: 8px;
            text-align: center;
            font-size: 7px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>Student Medical Exemption</h1>
        <p>Lal Bahadur Shastri National Academy of Administration</p>
    </div>

    <div class="meta">
        @if($filterLine)
            <div>{{ $filterLine }}</div>
        @endif
        <div>Generated on: {{ $printedOn }} &nbsp;|&nbsp; Total records: {{ $rows->count() }}</div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>S.No.</th>
                @foreach($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                @php
                    $cells = array_values((array) $row);
                    $documentIndex = array_search('Document', $headings, true);
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @foreach($cells as $cellIndex => $value)
                        <td>
                            @if($documentIndex !== false && $cellIndex === $documentIndex && is_string($value) && preg_match('/^https?:\/\//i', $value))
                                <a href="{{ $value }}" class="doc-link">View Document</a>
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headings) + 1 }}" style="text-align:center;">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">LBSNAA — Student Medical Exemption Report</div>
</body>
</html>
