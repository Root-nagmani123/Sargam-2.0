@php
    use App\Exports\EstatePossessionDetailsExport;

    $defs = EstatePossessionDetailsExport::columnDefs();
    $columns = collect($cols)->map(fn ($key) => $defs[$key] + ['key' => $key])->all();
    $colCount = count($columns);

    // Plain asset URLs, not base64 — this page is rendered by the browser (unlike
    // the DomPDF exports, which need the bytes inlined), so the logos stay cacheable
    // and the page stays a few KB instead of ~400 KB.
    $pickAsset = function (array $candidates): ?string {
        foreach ($candidates as $relative) {
            if (is_file(public_path($relative))) {
                return asset($relative) . '?v=' . (@filemtime(public_path($relative)) ?: 0);
            }
        }

        return null;
    };

    $emblemSrc = $pickAsset(['admin_assets/images/logos/ashoka.png', 'images/ashoka.png']);
    $lbsnaaLogoSrc = $pickAsset([
        'admin_assets/images/logos/logo-web.png',
        'admin_assets/images/logos/logo.png',
        'images/lbsnaa_logo.jpg',
    ]);
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Possession Details - LBSNAA MUSSOORIE</title>
    <style>
        @page { size: A4 landscape; margin: 12mm 10mm; }

        * { box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 16px;
            color: #212529;
            background: #fff;
            line-height: 1.4;
        }

        .print-header {
            border-bottom: 2.5px solid #004a93;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .print-header table { width: 100%; border-collapse: collapse; }
        .print-header td { border: 0; padding: 0; vertical-align: middle; }
        .print-header .hdr-emblem { width: 52px; }
        .print-header .hdr-emblem img { width: 44px; height: 44px; object-fit: contain; }
        /* The academy mark is a wide wordmark, not a square badge — size it by
           height and let the width follow, or it renders as an unreadable speck. */
        .print-header .hdr-logo { width: 170px; text-align: right; }
        .print-header .hdr-logo img { height: 38px; width: auto; max-width: 100%; object-fit: contain; }
        .print-header .hdr-center { padding: 0 10px; }

        .brand-1 {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #004a93;
            font-weight: 700;
        }
        .brand-2 {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            color: #111;
            margin-top: 2px;
        }
        .brand-3 { font-size: 10px; color: #555; margin-top: 2px; }

        .report-title-block {
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid #dee2e6;
        }
        .report-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #0f172a;
            margin: 0 0 5px;
        }
        .report-filter-pill {
            display: inline-block;
            background: #004a93;
            color: #fff;
            font-weight: 600;
            font-size: 10px;
            padding: 3px 12px;
            border-radius: 10px;
        }

        .report-meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 10px;
            color: #334155;
        }
        .report-meta td { border: 0; padding: 0; }
        .report-meta .meta-right { text-align: right; }
        .meta-label { font-weight: 700; color: #0f172a; }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
            table-layout: fixed;
        }
        .data-table th,
        .data-table td {
            padding: 5px 7px;
            border: 1px solid #d1d5db;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }
        .data-table thead th {
            background: #004a93;
            color: #fff;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            text-align: left;
        }
        .data-table tbody tr:nth-child(even) td { background: #f4f7fb; }
        .text-center { text-align: center; }

        .req-type { font-weight: 700; }
        .req-type--change { color: #b54708; }
        .req-type--new { color: #175cd3; }

        .empty-row td {
            text-align: center;
            color: #777;
            padding: 18px;
            font-style: italic;
        }

        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }

        .print-toolbar { text-align: right; margin-bottom: 12px; }
        .print-toolbar button {
            font: inherit;
            font-weight: 600;
            padding: 8px 18px;
            border: 1px solid #004a93;
            border-radius: 6px;
            background: #004a93;
            color: #fff;
            cursor: pointer;
        }
        @media print {
            body { padding: 0; }
            .print-toolbar { display: none; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <button type="button" onclick="window.print();">Print</button>
    </div>

    <div class="print-header">
        <table>
            <tr>
                <td class="hdr-emblem">
                    @if($emblemSrc)<img src="{{ $emblemSrc }}" alt="Emblem of India">@endif
                </td>
                <td class="hdr-center">
                    <div class="brand-1">Government of India</div>
                    <div class="brand-2">LBSNAA Mussoorie</div>
                    <div class="brand-3">Lal Bahadur Shastri National Academy of Administration</div>
                </td>
                <td class="hdr-logo">
                    @if($lbsnaaLogoSrc)<img src="{{ $lbsnaaLogoSrc }}" alt="LBSNAA Logo">@endif
                </td>
            </tr>
        </table>
    </div>

    <div class="report-title-block">
        <h1 class="report-title">Possession Details</h1>
        <div class="report-filter-pill">{{ $filterLine }}</div>
    </div>

    <table class="report-meta">
        <tr>
            <td><span class="meta-label">Printed on:</span> {{ $generatedAt }}</td>
            <td class="meta-right"><span class="meta-label">Total records:</span> {{ number_format($rows->count()) }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                @foreach($columns as $column)
                    <th style="width: {{ $column['width'] }}%;" class="{{ $column['center'] ? 'text-center' : '' }}">
                        {{ $column['heading'] }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    @foreach($columns as $column)
                        @php $value = ($column['value'])($row, $index + 1); @endphp
                        <td class="{{ $column['center'] ? 'text-center' : '' }}">
                            {{ $value === '' || $value === null ? '-' : $value }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="{{ $colCount }}">No possession records found for the applied filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <script>
        // Auto-open the print dialog once images have settled; the toolbar button
        // is the fallback when the browser blocks the automatic call.
        window.addEventListener('load', function () {
            window.setTimeout(function () { window.print(); }, 250);
        });
    </script>
</body>
</html>
