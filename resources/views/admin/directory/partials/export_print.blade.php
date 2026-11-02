{{-- Any directory grid → printable sheet (OT, LBSNAA).

     A server-rendered view, not window.print() on the grid: the same controller
     action serves print, CSV, Excel and PDF off one query, so the four can't
     drift apart (docs/new-design-index-page.md §1).

     Self-contained styles rather than the Centcom partials — those are scoped
     `ic-print-*` and live under issue_management; reaching across modules for
     them is how two unrelated reports end up coupled. --}}
@php
    $logoFor = function (string $relative): ?string {
        $path = public_path($relative);
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }
        $mime = str_ends_with(strtolower($relative), '.png') ? 'image/png' : 'image/jpeg';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    };

    $emblem = $logoFor('images/ashoka.png');
    $logo = $logoFor('images/lbsnaa_logo.jpg');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} — LBSNAA</title>
    <style>
        /* 8 columns don't fit portrait once Email and two Room columns are in. */
        @page { size: A4 landscape; margin: 10mm 8mm; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 16px;
        }

        /* ── Branded header: emblem + LBSNAA logo left, institution centre ── */
        table.dir-print-hdr { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.dir-print-hdr td { vertical-align: middle; padding: 0; }
        table.dir-print-hdr .dir-print-logo { width: 130px; white-space: nowrap; }
        table.dir-print-hdr .dir-print-logo img { height: 52px; width: auto; object-fit: contain; }
        table.dir-print-hdr .dir-print-logo img + img { margin-left: 6px; }
        table.dir-print-hdr .dir-print-centre { text-align: center; padding: 0 8px; }

        .dir-print-inst {
            font-size: 13px;
            font-weight: bold;
            color: #003366;
            line-height: 1.3;
            text-transform: uppercase;
        }
        .dir-print-sub { font-size: 10px; color: #4b5563; margin-top: 2px; }
        .dir-print-rule { border-bottom: 2px solid #003366; margin-bottom: 8px; }

        .dir-print-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            color: #003366;
            margin: 6px 0 2px;
            text-transform: uppercase;
        }
        .dir-print-meta { text-align: center; font-size: 9px; color: #6b7280; margin-bottom: 8px; }

        /* print-color-adjust is the important bit: browsers drop background
           colours when printing, so white-on-navy came out white on white. */
        .dir-print-total {
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

        table.dir-print-table { width: 100%; border-collapse: collapse; font-size: 10px; table-layout: fixed; }

        table.dir-print-table thead th {
            background: #003366;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 7px 6px;
            border: 1px solid #002244;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        table.dir-print-table td {
            padding: 6px;
            border: 1px solid #dee2e6;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        table.dir-print-table tbody tr:nth-child(even) td {
            background: #f4f7fb;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Header row repeats when the table breaks across pages. */
        table.dir-print-table thead { display: table-header-group; }
        table.dir-print-table tr { page-break-inside: avoid; }

        .dir-print-empty { text-align: center; padding: 20px; color: #6b7280; }
        .dir-print-note { text-align: center; font-size: 8px; color: #92400e; margin-bottom: 6px; }
        .dir-print-foot { margin-top: 10px; text-align: center; font-size: 8px; color: #6b7280; }

        @media print {
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print();">

    <table class="dir-print-hdr">
        <tr>
            <td class="dir-print-logo">
                @if($emblem)<img src="{{ $emblem }}" alt="">@endif
                @if($logo)<img src="{{ $logo }}" alt="">@endif
            </td>
            <td class="dir-print-centre">
                <div class="dir-print-inst">Lal Bahadur Shastri National Academy of Administration</div>
                <div class="dir-print-sub">Mussoorie, Uttarakhand &nbsp;|&nbsp; Sargam 2.0</div>
            </td>
            <td class="dir-print-logo"></td>
        </tr>
    </table>

    <div class="dir-print-rule"></div>

    <div class="dir-print-title">{{ $title }}</div>
    <div class="dir-print-meta">
        @if(filled($filterLine)){{ $filterLine }} &nbsp;|&nbsp; @endif Generated: {{ $exportDate }}
    </div>
    <div class="dir-print-total">Total Records: {{ number_format($rows->count()) }}</div>
    @if(filled($note ?? null))
        <div class="dir-print-note">{{ $note }}</div>
    @endif

    {{-- Columns come from the controller's <grid>ExportColumnDefs(), already
         filtered to whatever is still ticked in the grid's Columns modal, so the
         printout matches the screen and can't drift from the CSV. Cells are keyed
         by column key — never by position. Widths go inline: they are the only
         per-report part of the table, and the PDF sibling needs them inline too. --}}
    <table class="dir-print-table">
        <thead>
            <tr>
                @foreach ($columns as $col)
                    <th style="width: {{ $col['width'] }}; text-align: {{ $col['align'] }};">{{ $col['heading'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr>
                    @foreach ($columns as $col)
                        <td style="text-align: {{ $col['align'] }};">{{ $col['value']($row, $index) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(1, count($columns)) }}" class="dir-print-empty">Nothing to print</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="dir-print-foot">Sargam 2.0 · Directory · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
