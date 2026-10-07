{{-- Branded print view for any admin listing.

     A server-rendered page, not window.print() over the grid, so the printout
     carries the letterhead and every filtered row rather than the ten the screen
     happens to be paginated to (docs/new-design-index-page.md §1).

     Props:
       $title         report name, e.g. "Members"
       $columns       resolved column defs: ['key' => ['heading','class','value']]
       $rows          the collection to print
       $exportDate    already-formatted generation timestamp
       $filterLine    optional plain-text applied filters, null when unfiltered
       $columnStyles  optional raw CSS for the per-report column widths
       $emptyText     optional empty-state wording
       $note          optional caveat (e.g. a row cap) shown under the total --}}
@php
    $filterLine   = $filterLine ?? null;
    $columnStyles = $columnStyles ?? '';
    $emptyText    = $emptyText ?? 'Nothing to print';
    $note         = $note ?? null;
    $total        = $total ?? $rows->count();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — LBSNAA</title>
    <style>
        /* margin:0, NOT 12mm 10mm. A non-zero @page margin is where Chrome and
           Edge draw their own header and footer — the print date/time, the page
           title, the source URL and "1/1". There is no CSS switch for those; the
           only way to drop them is to leave the browser no margin to draw in, so
           the sheet's real margin moves to the body padding below. The branded
           "Generated:" line stays — it is ours, and the PDF/CSV/Excel carry it too. */
        @page { size: A4 portrait; margin: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 16px;
        }

        /* ── Branded header: emblem + LBSNAA logo left, institution centre ── */
        table.bx-hdr { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.bx-hdr td { vertical-align: middle; padding: 0; }
        table.bx-hdr .bx-logo { width: 130px; white-space: nowrap; }
        table.bx-hdr .bx-logo img { height: 52px; width: auto; object-fit: contain; }
        table.bx-hdr .bx-logo img + img { margin-left: 6px; }
        table.bx-hdr .bx-centre { text-align: center; padding: 0 8px; }

        .bx-inst {
            font-size: 13px;
            font-weight: bold;
            color: #003366;
            line-height: 1.3;
            text-transform: uppercase;
        }
        .bx-sub { font-size: 10px; color: #4b5563; margin-top: 2px; }

        .bx-rule { border-bottom: 2px solid #003366; margin-bottom: 8px; }

        .bx-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            color: #003366;
            margin: 6px 0 2px;
            text-transform: uppercase;
        }
        .bx-meta { text-align: center; font-size: 9px; color: #6b7280; margin-bottom: 8px; }

        .bx-filters {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 10px;
            font-size: 10px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .bx-total {
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

        .bx-note {
            text-align: center;
            font-size: 9px;
            color: #92400e;
            background: #fef6e7;
            border: 1px solid #f4d9a6;
            padding: 4px 8px;
            margin-bottom: 8px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── Data table ── */
        table.bx-table { width: 100%; border-collapse: collapse; font-size: 10px; table-layout: fixed; }

        table.bx-table thead th {
            background: #003366;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 7px 6px;
            border: 1px solid #002244;
            /* Browsers drop background colours when printing, which would leave
               white text on white paper — an invisible header row. */
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        table.bx-table td {
            padding: 6px;
            border: 1px solid #dee2e6;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        table.bx-table tbody tr:nth-child(even) td {
            background: #f4f7fb;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Header row repeats when the table breaks across pages. */
        table.bx-table thead { display: table-header-group; }
        table.bx-table tr { page-break-inside: avoid; }

        .bx-empty { text-align: center; padding: 20px; color: #6b7280; }
        .bx-foot { margin-top: 10px; text-align: center; font-size: 8px; color: #6b7280; }

        @media print {
            /* Carries the page margin that @page gave up, so the content still
               sits inside the printable area instead of running to the paper edge. */
            body { padding: 12mm 10mm; }
        }

        {!! $columnStyles !!}
    </style>
</head>
<body onload="window.print();">

    <table class="bx-hdr">
        <tr>
            <td class="bx-logo">
                <img src="{{ asset('images/ashoka.png') }}" alt="National Emblem of India">
                <img src="{{ asset('images/lbsnaa_logo.jpg') }}" alt="LBSNAA">
            </td>
            <td class="bx-centre">
                <div class="bx-inst">Lal Bahadur Shastri National Academy of Administration</div>
                <div class="bx-sub">Mussoorie, Uttarakhand &nbsp;|&nbsp; Sargam 2.0</div>
            </td>
            {{-- Mirrors the logo cell so the centre block stays optically centred. --}}
            <td class="bx-logo"></td>
        </tr>
    </table>

    <div class="bx-rule"></div>

    <div class="bx-title">{{ $title }}</div>
    <div class="bx-meta">Generated: {{ $exportDate }}</div>

    @if (filled($filterLine))
        <div class="bx-filters">{{ $filterLine }}</div>
    @endif

    <div class="bx-total">Total Records: {{ number_format($total) }}</div>

    @if (filled($note))
        <div class="bx-note">{{ $note }}</div>
    @endif

    {{-- Columns are already filtered to whatever is still ticked in the grid's
         Columns modal, so the printout matches the screen and can't drift from
         the CSV. Cells are keyed by column key — never by position. --}}
    <table class="bx-table">
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
                    <td colspan="{{ count($columns) }}" class="bx-empty">{{ $emptyText }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="bx-foot">Sargam 2.0 · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
