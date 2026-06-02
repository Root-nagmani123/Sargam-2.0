<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Users Export</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; color: #1f2937; }
        .report-head { margin-bottom: 12px; }
        .report-head h1 { font-size: 16px; margin: 0 0 2px; }
        .report-head .meta { font-size: 10px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        thead th {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            text-align: left;
            font-weight: bold;
        }
        tbody td {
            border: 1px solid #e5e7eb;
            padding: 4px 6px;
            vertical-align: top;
        }
        tbody tr:nth-child(even) td { background: #fafafa; }
        .empty { text-align: center; padding: 18px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="report-head">
        <h1>Users</h1>
        <div class="meta">Generated: {{ $generatedAt }} &middot; {{ count($rows) }} record(s)</div>
    </div>

    <table>
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
