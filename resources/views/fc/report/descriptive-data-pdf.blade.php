<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Descriptive Data - {{ $form->form_name }}</title>
    <style>
        /* No @page rule: mPDF mis-parses it (see the Descriptive Roll template) — the page
           size and margins come from the Mpdf constructor in the controller. */
        body { font-family: sans-serif; font-size: 7pt; color: #000; }
        .head { text-align: center; margin-bottom: 6px; }
        .head h1 { font-size: 11pt; color: #0a3d6b; margin: 0 0 2px; }
        .head .sub { font-size: 8pt; color: #333; margin: 0; }
        .head .meta { font-size: 7pt; color: #555; margin: 2px 0 0; }
        table.grid { width: 100%; border-collapse: collapse; }
        table.grid th {
            background: #e4ebf2; color: #24486e; border: 0.5pt solid #a6b4c3;
            padding: 3px 2px; text-align: left; font-size: 6.6pt;
        }
        table.grid td {
            border: 0.5pt solid #c3cedb; padding: 2px 2px; vertical-align: top;
            word-wrap: break-word;
        }
        table.grid tr:nth-child(even) td { background: #fafbfc; }
        .note { margin-top: 6px; font-size: 6.5pt; color: #555; text-align: center; }
        /* Matches the Excel export's link styling. mPDF turns <a href> into a real PDF link
           annotation, so these are clickable in any viewer, not just coloured text. */
        a.filelink { color: #0563C1; text-decoration: underline; }
        .warn { margin-top: 6px; font-size: 7pt; color: #8a1f11; text-align: center; font-weight: bold; }
    </style>
</head>
<body>

<div class="head">
    <h1>Lal Bahadur Shastri National Academy of Administration</h1>
    <p class="sub">Descriptive Data Report — {{ $form->form_name }}</p>
    <p class="meta">Generated {{ $printedAt }} · {{ count($rows) }} record(s)</p>
</div>

@if($truncated)
    <p class="warn">
        Showing the first {{ number_format($maxRows) }} rows only. Narrow the filters, or use the
        Excel export, which has no row cap.
    </p>
@endif

<table class="grid">
    <thead>
        <tr>
            <th style="width:22px;">#</th>
            @if($showUsername ?? true)<th>Username</th>@endif
            @foreach($fields as $field)
                <th>{{ $field['label'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                @if($showUsername ?? true)<td>{{ $row->login_username ?? '-' }}</td>@endif
                @foreach($fields as $key => $field)
                    @php
                        $value = $row->{$key} ?? null;
                        $fileUrl = '';   // reset per cell — never inherit the previous column's link
                        if ($field['type'] === 'date') {
                            $value = trim((string) $value);
                            $value = ($value === '' || str_starts_with($value, '0000'))
                                ? '-'
                                : \Carbon\Carbon::parse($value)->format('d-m-Y');
                        } elseif ($field['type'] === 'file') {
                            // Clickable link, like the Excel export. The visible text is the
                            // file name, not the URL — a full URL is far wider than the cell
                            // and would wreck the column widths on a 28-column landscape page.
                            $stored = trim((string) $value);
                            $fileUrl = $stored === '' ? '' : \App\Support\FC\FcUploadUrl::for($stored);
                            $value = $stored === '' ? '-' : basename($stored);
                        } else {
                            $value = trim((string) $value);
                            $value = $value === '' ? '-' : $value;
                        }
                    @endphp
                    <td>
                        @if(($field['type'] ?? '') === 'file' && !empty($fileUrl))
                            <a class="filelink" href="{{ $fileUrl }}">{{ $value }}</a>
                        @else
                            {{ $value }}
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

<p class="note">
    Computer-generated document from Sargam FC Registration module.
</p>

</body>
</html>
