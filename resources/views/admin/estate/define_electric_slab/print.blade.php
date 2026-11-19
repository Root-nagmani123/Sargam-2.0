{{--
    Define Electric Slab — print view.

    Rendered by EstateElectricSlabController@print from the SAME payload as the
    Excel download (exportPayload), and the columns come from
    EstateElectricSlabExport::columnDefs(), so the printout and the .xlsx cannot
    drift apart (new-design-index-page.md §1). It is a standalone page — not the
    admin layout — so nothing but the report reaches the paper.
--}}
@php
    use App\Exports\EstateElectricSlabExport;

    $defs = EstateElectricSlabExport::columnDefs();
    $cols = $cols ?: array_keys($defs);
    $logo = public_path('images/lbsnaa_logo.jpg');
    $logoSrc = file_exists($logo)
        ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logo))
        : null;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Define Electric Slab</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 16px;
            background: #fff;
            color: #212529;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }

        .rpt-header {
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 2px solid #2c3e50;
            text-align: center;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .rpt-header img {
            height: 58px;
            width: auto;
            margin-bottom: 8px;
        }

        .rpt-org {
            margin-bottom: 6px;
            font-size: 16px;
            font-weight: 700;
            color: #1a1a1a;
        }

        .rpt-title {
            display: inline-block;
            margin: 4px 0;
            padding: 7px 16px;
            border-radius: 4px;
            background: #004384;
            color: #fff;
            font-size: 13px;
            letter-spacing: .02em;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .rpt-meta {
            margin-top: 8px;
            font-size: 10.5px;
            color: #6c757d;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            page-break-inside: auto;
        }

        thead {
            display: table-header-group;
        }

        th,
        td {
            padding: 5px 7px;
            border: 1px solid #adb5bd;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
        }

        thead th {
            background: #004384 !important;
            border-color: #003468;
            color: #fff !important;
            font-weight: 600;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        tbody tr {
            page-break-inside: avoid;
        }

        tbody tr:nth-child(even) td {
            background: #f4f6f8 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        td.is-center,
        th.is-center {
            text-align: center;
        }

        td.is-money,
        th.is-money {
            text-align: right;
            white-space: nowrap;
        }

        .rpt-empty {
            padding: 24px;
            color: #6c757d;
            text-align: center;
        }

        .rpt-foot {
            margin-top: 12px;
            font-size: 10px;
            color: #6c757d;
        }

        @media print {
            body {
                padding: 0;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <div class="rpt-header">
        @if($logoSrc)
        <div><img src="{{ $logoSrc }}" alt="LBSNAA Logo"></div>
        @endif
        <div class="rpt-org">LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION</div>
        <div class="rpt-title">Define Electric Slab</div>
        <div class="rpt-meta">{{ $filterLine }} &nbsp;|&nbsp; Generated: {{ $generatedAt }}</div>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($cols as $key)
                @continue(! isset($defs[$key]))
                <th
                    class="{{ $defs[$key]['center'] ? 'is-center' : '' }} {{ $defs[$key]['money'] ? 'is-money' : '' }}">
                    {{ $defs[$key]['heading'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $row)
            <tr>
                @foreach($cols as $key)
                @continue(! isset($defs[$key]))
                @php $value = $defs[$key]['value']($row, $i + 1); @endphp
                <td
                    class="{{ $defs[$key]['center'] ? 'is-center' : '' }} {{ $defs[$key]['money'] ? 'is-money' : '' }}">
                    @if($defs[$key]['money'])
                    {{ number_format((float) $value, 2) }} INR
                    @else
                    {{ $value }}
                    @endif
                </td>
                @endforeach
            </tr>
            @empty
            <tr>
                <td class="rpt-empty" colspan="{{ max(1, count($cols)) }}">No electric slabs match this search.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="rpt-foot">Total Records: {{ $rows->count() }}</div>
</body>

</html>
