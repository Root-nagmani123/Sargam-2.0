{{-- Print export for Useful Links.

     A server-rendered page, not window.print() — the same controller action
     serves this and the CSV off one query and one column list, so the printout
     and the download can't drift apart (docs/new-design-index-page.md §1).

     ⚠️ print-color-adjust is the important bit. Browsers drop background
     colours when printing by default, so the white-on-navy header band would
     otherwise come out white-on-white — an invisible header row. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Useful Links — LBSNAA</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 10mm; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 16px;
        }

        table.ul-print-hdr { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.ul-print-hdr td { vertical-align: middle; padding: 0; }
        table.ul-print-hdr .ul-print-logo { width: 130px; white-space: nowrap; }
        table.ul-print-hdr .ul-print-logo img { height: 52px; width: auto; object-fit: contain; }
        table.ul-print-hdr .ul-print-logo img + img { margin-left: 6px; }
        table.ul-print-hdr .ul-print-centre { text-align: center; padding: 0 8px; }

        .ul-print-inst {
            font-size: 13px;
            font-weight: bold;
            color: #003366;
            line-height: 1.3;
            text-transform: uppercase;
        }
        .ul-print-sub { font-size: 10px; color: #4b5563; margin-top: 2px; }
        .ul-print-rule { border-bottom: 2px solid #003366; margin-bottom: 8px; }

        .ul-print-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            color: #003366;
            margin: 6px 0 2px;
            text-transform: uppercase;
        }
        .ul-print-meta { text-align: center; font-size: 9px; color: #6b7280; margin-bottom: 8px; }

        .ul-print-filters {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 10px;
            font-size: 10px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .ul-print-total {
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

        table.ul-print-table { width: 100%; border-collapse: collapse; font-size: 10px; table-layout: fixed; }

        table.ul-print-table thead th {
            background: #003366;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 7px 6px;
            border: 1px solid #002244;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        table.ul-print-table td {
            padding: 6px;
            border: 1px solid #dee2e6;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        table.ul-print-table tbody tr:nth-child(even) td {
            background: #f4f7fb;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        table.ul-print-table thead { display: table-header-group; }
        table.ul-print-table tr { page-break-inside: avoid; }

        .ul-print-empty { text-align: center; padding: 20px; color: #6b7280; }
        .ul-print-foot { margin-top: 10px; text-align: center; font-size: 8px; color: #6b7280; }

        /* Column widths are the only per-report part of the table. */
        .ul-print-sno    { width: 7%;  text-align: center; }
        .ul-print-label  { width: 26%; }
        .ul-print-url    { width: 34%; }
        .ul-print-file   { width: 15%; }
        .ul-print-order  { width: 8%;  text-align: center; }
        .ul-print-target { width: 10%; text-align: center; }
    </style>
</head>
<body onload="window.print();">

    <table class="ul-print-hdr">
        <tr>
            <td class="ul-print-logo">
                <img src="{{ asset('images/ashoka.png') }}" alt="National Emblem of India">
                <img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="LBSNAA">
            </td>
            <td class="ul-print-centre">
                <div class="ul-print-inst">Lal Bahadur Shastri National Academy of Administration</div>
                <div class="ul-print-sub">Mussoorie, Uttarakhand &nbsp;|&nbsp; Sargam 2.0</div>
            </td>
            {{-- Mirrors the logo cell so the centre block stays optically centred. --}}
            <td class="ul-print-logo"></td>
        </tr>
    </table>

    <div class="ul-print-rule"></div>

    <div class="ul-print-title">Useful Links</div>
    <div class="ul-print-meta">Generated: {{ $exportDate }}</div>

    @if (filled($search))
        <div class="ul-print-filters"><strong>Search:</strong> {{ $search }}</div>
    @endif

    <div class="ul-print-total">Total Records: {{ number_format($rows->count()) }}</div>

    {{-- Columns come from UsefulLinksSetupController::exportColumnDefs(), already
         filtered to whatever is still ticked in the grid's Columns modal, so the
         printout matches the screen. Cells are keyed by column, never by
         position. --}}
    <table class="ul-print-table">
        <thead>
            <tr>
                @foreach ($columns as $col)
                    <th class="{{ $col['class'] }}">{{ $col['heading'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    @foreach ($columns as $col)
                        <td class="{{ $col['class'] }}">{{ $col['value']($row, $index) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(count($columns), 1) }}" class="ul-print-empty">No useful links to print</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="ul-print-foot">Sargam 2.0 · Setup · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
