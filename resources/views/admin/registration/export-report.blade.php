@php
    $printedOn = now()->format('d-m-Y H:i');
    $emblemSrc = $emblemSrc ?? '';
    $logoSrc = $logoSrc ?? '';
    $rows = $rows ?? [];
    $columns = $columns ?? [];
    $rowCount = count($rows);
    $filterLine = $filterLine ?? '';
    $title = $title ?? 'FC Registration export';
    $footerNote = $footerNote ?? 'LBSNAA Mussoorie — FC Registration';
    $truncated = (bool) ($truncated ?? false);
    $totalMatching = (int) ($totalMatching ?? $rowCount);
    $autoprint = (bool) ($autoprint ?? false);
    $colCount = max(1, count($columns));
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — LBSNAA</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, "Segoe UI", system-ui, sans-serif;
            font-size: 10px;
            color: #212529;
            margin: 0;
            padding: 8px;
        }
        .print-actions { text-align: right; margin-bottom: 8px; }
        .print-actions button {
            background: #004a93;
            color: #fff;
            border: 0;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            margin-left: 6px;
        }
        .print-actions .btn-secondary { background: #6c757d; }
        .lbsnaa-header-wrap {
            border-bottom: 2px solid #004a93;
            margin-bottom: 10px;
            padding-bottom: 8px;
        }
        .branding-table { width: 100%; border-collapse: collapse; }
        .branding-table td { border: 0; vertical-align: middle; padding: 0 4px; }
        .branding-logo-left { width: 52px; }
        .branding-logo-left img, .branding-logo-right img {
            max-height: 48px;
            width: auto;
            object-fit: contain;
        }
        .branding-logo-right { width: 56px; text-align: right; }
        .branding-text {
            text-align: center;
            line-height: 1.25;
            padding: 0 8px;
        }
        .branding-text .l1 { font-size: 8px; text-transform: uppercase; letter-spacing: .06em; color: #004a93; font-weight: 600; margin: 0; }
        .branding-text .l2 { font-size: 13px; font-weight: 700; text-transform: uppercase; color: #1a1a1a; margin: 2px 0 0; }
        .branding-text .l3 { font-size: 8px; color: #555; margin: 2px 0 0; }
        .report-title {
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin: 8px 0 6px;
            color: #1a1a1a;
        }
        .meta {
            font-size: 9px;
            color: #333;
            margin-bottom: 8px;
            text-align: center;
            line-height: 1.5;
        }
        .warn {
            font-size: 9px;
            color: #856404;
            background: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 6px 8px;
            margin-bottom: 8px;
            text-align: center;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        .data-table th, .data-table td {
            border: 1px solid #bbb;
            padding: 3px 5px;
            vertical-align: top;
            word-break: break-word;
        }
        .data-table thead th {
            background: #004a93;
            color: #fff;
            font-weight: 600;
            text-align: left;
        }
        .data-table tbody tr:nth-child(even) td { background: #f9fafb; }
        .footer {
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px solid #dee2e6;
            font-size: 8px;
            color: #666;
            text-align: center;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

@if(!($forPdf ?? false))
<div class="print-actions no-print">
    <button type="button" onclick="window.print()">Print</button>
    <button type="button" class="btn-secondary" onclick="window.close()">Close</button>
</div>
@endif

<div class="lbsnaa-header-wrap">
    <table class="branding-table">
        <tr>
            <td class="branding-logo-left">
                @if($emblemSrc)
                    <img src="{{ $emblemSrc }}" alt="Emblem">
                @endif
            </td>
            <td class="branding-text">
                <p class="l1">Government of India</p>
                <p class="l2">LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION</p>
                <p class="l3">Mussoorie, Uttarakhand</p>
            </td>
            <td class="branding-logo-right">
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="LBSNAA">
                @endif
            </td>
        </tr>
    </table>
</div>

<h1 class="report-title">{{ $title }}</h1>
<div class="meta">
    <strong>Filters:</strong> {{ $filterLine }}<br>
    <strong>Printed:</strong> {{ $printedOn }} &nbsp;|&nbsp;
    <strong>Exported:</strong> {{ $rowCount }}@if($truncated) of {{ $totalMatching }} (limit applied)@endif
</div>

@if($truncated)
    <div class="warn">Export limited to {{ number_format($rowCount) }} rows. Narrow filters or use Excel for very large lists.</div>
@endif

<table class="data-table">
    <thead>
        <tr>
            @foreach($columns as $col)
                <th>{{ $col['label'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $r)
            <tr>
                @foreach($columns as $col)
                    <td>{{ $r[$col['key']] ?? '—' }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ $colCount }}" style="text-align:center;">No records for the selected filters.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">{{ $footerNote }}</div>

@if($autoprint && !($forPdf ?? false))
<script>window.addEventListener('load',function(){setTimeout(function(){window.print()},300);});</script>
@endif
</body>
</html>
