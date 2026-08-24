{{-- Print sheet for the Users listing.

     A server-rendered page, not a window.print() of the on-screen table — the
     grid shows ONE page of 10 rows, so cloning it printed 10 users and called it
     the list. This runs the same query, the same column list and the same row
     builder as the CSV / XLSX / PDF (docs/new-design-index-page.md §1).

     ⚠️ print-color-adjust is the important bit. Browsers drop background colours
     when printing by default, so the white-on-navy header band would otherwise
     come out white-on-white — an invisible header row.

     Expects: $columns (label / width / centre), $rows, $filterHtml (HTML or
     null), $exportDate. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Users &mdash; LBSNAA</title>
    <style>
        /* Landscape: seven columns including an email address do not fit on A4
           portrait. margin:0 also drops the browser's own URL/date furniture. */
        @page { size: A4 landscape; margin: 10mm; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 12px;
        }

        table.up-hdr { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.up-hdr td { vertical-align: middle; padding: 0; }
        table.up-hdr .up-logo { width: 130px; white-space: nowrap; }
        table.up-hdr .up-logo img { height: 52px; width: auto; object-fit: contain; }
        table.up-hdr .up-logo img + img { margin-left: 6px; }
        table.up-hdr .up-centre { text-align: center; padding: 0 8px; }

        .up-inst {
            font-size: 13px;
            font-weight: bold;
            color: #003366;
            line-height: 1.3;
            text-transform: uppercase;
        }
        .up-sub { font-size: 10px; color: #4b5563; margin-top: 2px; }

        .up-rule { border-bottom: 2px solid #003366; margin-bottom: 8px; }

        .up-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            color: #003366;
            margin: 6px 0 2px;
            text-transform: uppercase;
        }
        .up-meta { text-align: center; font-size: 9px; color: #6b7280; margin-bottom: 8px; }

        .up-filters {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 10px;
            font-size: 10px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .up-total {
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

        table.up-table { width: 100%; border-collapse: collapse; font-size: 10px; table-layout: fixed; }

        table.up-table thead th {
            background: #003366;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 7px 6px;
            border: 1px solid #002244;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        table.up-table td {
            padding: 6px;
            border: 1px solid #dee2e6;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        table.up-table tbody tr:nth-child(even) td {
            background: #f4f7fb;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Repeat the column titles at the top of every printed page. */
        table.up-table thead { display: table-header-group; }
        table.up-table tr { page-break-inside: avoid; }

        table.up-table th.up-centre-col,
        table.up-table td.up-centre-col { text-align: center; }

        .up-empty { text-align: center; padding: 20px; color: #6b7280; }
        .up-foot { margin-top: 10px; text-align: center; font-size: 8px; color: #6b7280; }
    </style>
</head>
<body onload="window.print();">

    <table class="up-hdr">
        <tr>
            <td class="up-logo">
                <img src="{{ asset('images/ashoka.png') }}" alt="National Emblem of India">
                <img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="LBSNAA">
            </td>
            <td class="up-centre">
                <div class="up-inst">Lal Bahadur Shastri National Academy of Administration</div>
                <div class="up-sub">Mussoorie, Uttarakhand &nbsp;|&nbsp; Sargam 2.0</div>
            </td>
            {{-- Mirrors the logo cell so the centre block stays optically centred. --}}
            <td class="up-logo"></td>
        </tr>
    </table>

    <div class="up-rule"></div>

    <div class="up-title">Users</div>
    <div class="up-meta">Generated: {{ $exportDate }}</div>

    @if (filled($filterHtml))
        <div class="up-filters">{!! $filterHtml !!}</div>
    @endif

    <div class="up-total">Total Records: {{ number_format(count($rows)) }}</div>

    {{-- $columns is already filtered to whatever is still ticked in the grid's
         Columns modal, and the rows were built from the same list, so a hidden
         column drops cleanly out of both. --}}
    <table class="up-table">
        <thead>
            <tr>
                @foreach ($columns as $col)
                    <th class="{{ $col['centre'] ? 'up-centre-col' : '' }}" style="width: {{ $col['width'] }};">
                        {{ $col['label'] }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($columns as $i => $col)
                        <td class="{{ $col['centre'] ? 'up-centre-col' : '' }}">{{ $row[$i] ?? '' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(count($columns), 1) }}" class="up-empty">No users to print</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="up-foot">Sargam 2.0 · Users · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
