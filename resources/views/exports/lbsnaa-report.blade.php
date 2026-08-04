{{--
    Reusable LBSNAA branded report — Print + PDF (DomPDF) export chrome.
    Matches the academy document header (logos + Hindi/English name + course/batch line +
    blue report title + blue table header). Generic: pass $headings + $rows + $reportTitle.

    Params:
      $reportTitle  (string)   e.g. "Country List"
      $headings     (string[]) column headers
      $rows         (array[])  each row = array of cell values (same order as $headings)
      $subtitle     (?string)  course line, e.g. "IAS Professional Course, Phase - I (2025 Batch)"
      $subtitle2    (?string)  batch dates, e.g. "(8 December 2025 to 17 April, 2026)"
      $logoLeft/$logoRight/$titleHindi (?string data-URIs)  from LocationController::exportAssets()
      $printedOn    (?string)
      $autoPrint    (bool)     true → window.print() on load (the "Print" route)
--}}
@php
    $headings   = $headings ?? [];
    $rows       = $rows ?? [];
    $reportTitle= $reportTitle ?? 'Report';
    $subtitle   = $subtitle ?? 'IAS Professional Course, Phase - I';
    $subtitle2  = $subtitle2 ?? null;
    $printedOn  = $printedOn ?? now()->format('d-m-Y H:i');
    $autoPrint  = $autoPrint ?? false;
    $statusIdx  = array_search('Status', $headings, true);   // colour the Status pill if present
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }} — LBSNAA</title>
    <style>
        @page { size: A4 landscape; margin: 12mm 10mm; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; padding: 0; color: #1f2937; font-size: 10px; }

        table.pdf-hdr { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        table.pdf-hdr td { vertical-align: middle; }
        table.pdf-hdr .logo { width: 90px; text-align: center; }
        table.pdf-hdr .logo img { max-height: 62px; max-width: 86px; }
        table.pdf-hdr .center { text-align: center; padding: 0 8px; }
        .inst-hi-img { height: 16px; width: auto; margin-bottom: 2px; }
        .inst-en { font-size: 14px; font-weight: bold; color: #102a43; line-height: 1.3; }
        .inst-sub { font-size: 10px; color: #333; line-height: 1.35; margin-top: 1px; }

        .report-title {
            text-align: center; font-size: 18px; font-weight: bold; color: #004384;
            margin: 8px 0 6px; padding-bottom: 6px; border-bottom: 2px solid #004384;
        }
        .meta { font-size: 8px; color: #555; margin: 0 0 8px; text-align: center; }

        table.data-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data-table th, table.data-table td {
            border: 0.8px solid #8fa3bd; padding: 6px 7px; text-align: left;
            vertical-align: middle; word-break: break-word; overflow-wrap: break-word;
        }
        table.data-table thead th {
            background: #004384; color: #fff; font-weight: bold; font-size: 10px;
            text-align: center; border-color: #004384;
        }
        table.data-table td.cell-center, table.data-table th { text-align: center; }
        table.data-table tbody tr:nth-child(even) { background: #eef2f8; }
        .status-pill { padding: 2px 8px; border-radius: 10px; font-weight: bold; }
        .st-active   { color: #146c43; background: #ebfaf0; }
        .st-inactive { color: #b02a37; background: #fdecec; }
    </style>
</head>
<body>
    <table class="pdf-hdr">
        <tr>
            <td class="logo">@if($logoLeft ?? null)<img src="{{ $logoLeft }}" alt="">@endif</td>
            <td class="center">
                @if($titleHindi ?? null)<img class="inst-hi-img" src="{{ $titleHindi }}" alt="">@endif
                <div class="inst-en">Lal Bahadur Shastri National Academy of Administration, Mussoorie</div>
            </td>
            <td class="logo">@if($logoRight ?? null)<img src="{{ $logoRight }}" alt="">@endif</td>
        </tr>
    </table>

    <div class="report-title">{{ $reportTitle }}</div>

    <div class="meta">Generated on: {{ $printedOn }} &nbsp;|&nbsp; Total records: {{ count($rows) }}</div>

    <table class="data-table">
        <thead>
            <tr>@foreach($headings as $h)<th>{{ $h }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                @php $cells = array_values((array) $row); @endphp
                <tr>
                    @foreach($cells as $ci => $value)
                        @if($ci === $statusIdx)
                            <td class="cell-center"><span class="status-pill {{ strtolower($value) === 'active' ? 'st-active' : 'st-inactive' }}">{{ $value }}</span></td>
                        @else
                            <td>{{ $value }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ max(count($headings), 1) }}" style="text-align:center;">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($autoPrint)
    <script>window.onload = function () { window.print(); };</script>
    @endif
</body>
</html>
