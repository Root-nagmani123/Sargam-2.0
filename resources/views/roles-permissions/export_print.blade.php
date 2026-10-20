{{-- Print export for Roles & Permissions.

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
    <title>Roles &amp; Permissions — LBSNAA</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 10mm; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 16px;
        }

        table.rp-print-hdr { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.rp-print-hdr td { vertical-align: middle; padding: 0; }
        table.rp-print-hdr .rp-print-logo { width: 130px; white-space: nowrap; }
        table.rp-print-hdr .rp-print-logo img { height: 52px; width: auto; object-fit: contain; }
        table.rp-print-hdr .rp-print-logo img + img { margin-left: 6px; }
        table.rp-print-hdr .rp-print-centre { text-align: center; padding: 0 8px; }

        .rp-print-inst {
            font-size: 13px;
            font-weight: bold;
            color: #003366;
            line-height: 1.3;
            text-transform: uppercase;
        }
        .rp-print-sub { font-size: 10px; color: #4b5563; margin-top: 2px; }

        .rp-print-rule { border-bottom: 2px solid #003366; margin-bottom: 8px; }

        .rp-print-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            color: #003366;
            margin: 6px 0 2px;
            text-transform: uppercase;
        }
        .rp-print-meta { text-align: center; font-size: 9px; color: #6b7280; margin-bottom: 8px; }

        .rp-print-filters {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 10px;
            font-size: 10px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .rp-print-total {
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

        table.rp-print-table { width: 100%; border-collapse: collapse; font-size: 10px; table-layout: fixed; }

        table.rp-print-table thead th {
            background: #003366;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 7px 6px;
            border: 1px solid #002244;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        table.rp-print-table td {
            padding: 6px;
            border: 1px solid #dee2e6;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        table.rp-print-table tbody tr:nth-child(even) td {
            background: #f4f7fb;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        table.rp-print-table thead { display: table-header-group; }
        table.rp-print-table tr { page-break-inside: avoid; }

        .rp-print-empty { text-align: center; padding: 20px; color: #6b7280; }
        .rp-print-foot { margin-top: 10px; text-align: center; font-size: 8px; color: #6b7280; }

        /* Column widths are the only per-report part of the table. */
        .rp-print-sno     { width: 10%; text-align: center; }
        .rp-print-name    { width: 48%; }
        .rp-print-count   { width: 20%; text-align: center; }
        .rp-print-created { width: 22%; text-align: center; }
    </style>
</head>
<body onload="window.print();">

    <table class="rp-print-hdr">
        <tr>
            <td class="rp-print-logo">
                <img src="{{ asset('images/ashoka.png') }}" alt="National Emblem of India">
                <img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="LBSNAA">
            </td>
            <td class="rp-print-centre">
                <div class="rp-print-inst">Lal Bahadur Shastri National Academy of Administration</div>
                <div class="rp-print-sub">Mussoorie, Uttarakhand &nbsp;|&nbsp; Sargam 2.0</div>
            </td>
            {{-- Mirrors the logo cell so the centre block stays optically centred. --}}
            <td class="rp-print-logo"></td>
        </tr>
    </table>

    <div class="rp-print-rule"></div>

    <div class="rp-print-title">Roles &amp; Permissions</div>
    <div class="rp-print-meta">Generated: {{ $exportDate }}</div>

    @if (filled($search))
        <div class="rp-print-filters"><strong>Search:</strong> {{ $search }}</div>
    @endif

    <div class="rp-print-total">Total Records: {{ number_format($rows->count()) }}</div>

    {{-- Columns come from RoleService::exportColumnDefs(), already filtered to
         whatever is still ticked in the grid's Columns modal, so the printout
         matches the screen. Cells are keyed by column, never by position. --}}
    <table class="rp-print-table">
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
                    <td colspan="{{ max(count($columns), 1) }}" class="rp-print-empty">No roles to print</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="rp-print-foot">Sargam 2.0 · Roles &amp; Permissions · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
