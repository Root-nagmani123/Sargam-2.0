<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>FC Registration - {{ $username }}</title>
    <style>
        {!! $pdfFontFaceCss ?? '' !!}
        /* Tighter print margins (Chrome + Dompdf); default print margins leave a large empty band */
        @page {
            size: A4;
            margin: 8mm 10mm 10mm 10mm;
        }
        html, body {
            margin: 0;
            padding: 0;
        }
        body {
            font-family: {!! $pdfFontFamilyCss !!};
            font-size: 9.5pt;
            color: #111;
            line-height: 1.35;
        }
        table, table.fields, table.grid, table.meta-bar {
            font-family: {!! $pdfFontFamilyCss !!};
        }
        .masthead {
            text-align: center;
            border: 2px solid #0a3d6b;
            padding: 6px 8px 8px;
            margin-bottom: 6px;
            background: #f8fafc;
        }
        .masthead-hi {
            font-size: 11pt;
            font-weight: bold;
            color: #0a3d6b;
            margin: 0 0 4px;
        }
        .masthead-en-org {
            font-size: 9.2pt;
            color: #333;
            margin: 0 0 2px;
        }
        .masthead-en-name {
            font-size: 10pt;
            font-weight: bold;
            color: #0a3d6b;
            margin: 0 0 4px;
        }
        .masthead-place {
            font-size: 8.5pt;
            color: #444;
            margin: 0;
        }
        .doc-title {
            text-align: center;
            margin: 4px 0 6px;
            padding: 4px 6px;
            border-top: 1px solid #0a3d6b;
            border-bottom: 2px solid #0a3d6b;
        }
        .doc-title-en {
            font-size: 12pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            color: #0a3d6b;
            margin: 0;
        }
        .doc-title-hi {
            font-size: 10.5pt;
            font-weight: bold;
            color: #333;
            margin: 4px 0 0;
        }
        .meta-bar {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 8.5pt;
        }
        .meta-bar td {
            border: 1px solid #999;
            padding: 5px 8px;
            vertical-align: top;
        }
        .meta-bar .label {
            width: 22%;
            background: #e8eef5;
            font-weight: bold;
            color: #0a3d6b;
        }
        .section {
            margin-top: 6px;
            /* Do not avoid-break whole sections: long blocks jump to next page and leave a huge gap */
            page-break-inside: auto;
        }
        .section-h {
            background: #0a3d6b;
            color: #fff;
            padding: 4px 8px;
            font-size: 9.5pt;
            font-weight: bold;
            break-after: avoid-page;
        }
        .section-h-sub {
            font-size: 8pt;
            font-weight: normal;
            opacity: 0.95;
        }
        table.fields {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.8pt;
        }
        table.fields td {
            border: 1px solid #bbb;
            padding: 4px 6px;
            vertical-align: top;
        }
        table.fields .lab-en {
            width: 26%;
            background: #f0f4f8;
            /* mPDF + bold + script-mix can emit tofu; keep labels regular weight */
            font-weight: normal;
            color: #0a3d6e;
        }
        table.fields .lab-hi {
            width: 22%;
            font-size: 8.2pt;
            color: #444;
            background: #fafafa;
        }
        table.fields .val {
            width: 52%;
        }
        table.grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.3pt;
        }
        table.grid th {
            background: #0a3d6b;
            color: #fff;
            border: 1px solid #0a3d6b;
            padding: 4px 3px;
            text-align: center;
            font-weight: bold;
        }
        table.grid th small {
            display: block;
            font-weight: normal;
            font-size: 7.5pt;
            opacity: 0.92;
            margin-top: 2px;
        }
        table.grid td {
            border: 1px solid #999;
            padding: 3px 4px;
            vertical-align: top;
        }
        table.grid tr:nth-child(even) td {
            background: #fafafa;
        }
        .footer-note {
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px solid #ccc;
            font-size: 7.8pt;
            color: #555;
            text-align: center;
        }
        .photo-cell {
            width: 64px;
            text-align: center;
            vertical-align: middle;
            padding: 4px !important;
        }
        .photo-cell img {
            display: block;
            margin: 0 auto;
            width: 56px;
            height: auto;
            max-height: 72px;
            object-fit: cover;
            border: 1px solid #999;
        }
    </style>
</head>
<body>

<div class="masthead">
    <p class="masthead-hi">लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी</p>
    <p class="masthead-en-org">Lal Bahadur Shastri National Academy of Administration</p>
    <p class="masthead-en-name">Government of India | भारत सरकार</p>
    <p class="masthead-place">Mussoorie - Uttarakhand &nbsp;|&nbsp; मसूरी - उत्तराखंड</p>
</div>

<div class="doc-title">
    <p class="doc-title-en">DESCRIPTIVE REGISTRATION PROFILE</p>
    <p class="doc-title-hi">वर्णनात्मक पंजीकरण प्रोफ़ाइल</p>
</div>

<table class="meta-bar">
    <tr>
        <td class="photo-cell" rowspan="3">
            @if(!empty($photoDataUri))
                <img src="{{ $photoDataUri }}" alt="Photo" width="56" height="72">
            @else
                <span style="font-size:8pt;color:#888;">Photo<br/>फोटो<br/>-</span>
            @endif
        </td>
        <td class="label">Name / नाम</td>
        <td><strong>{{ ($pdfFullName ?? '') !== '' ? $pdfFullName : '-' }}</strong></td>
    </tr>
    <tr>
        <td class="label">Username / उपयोगकर्ता</td>
        <td>{{ $username }}</td>
    </tr>
    <tr>
        <td class="label">Generated / जारी दिनांक</td>
        <td>{{ $printedAt }}</td>
    </tr>
</table>

@foreach($sections as $sec)
    <div class="section">
        <div class="section-h">
            {{ $sec['title_en'] }}
            <span class="section-h-sub"> | {{ $sec['title_hi'] }}</span>
        </div>

        @if(($sec['type'] ?? '') === 'fields' && !empty($sec['rows']))
            <table class="fields" cellspacing="0">
                @foreach($sec['rows'] as $row)
                    <tr>
                        <td class="lab-en">{{ $row['en'] }}</td>
                        <td class="lab-hi">{{ $row['hi'] }}</td>
                        <td class="val">{{ $row['value'] }}</td>
                    </tr>
                @endforeach
            </table>
        @elseif(($sec['type'] ?? '') === 'table' && !empty($sec['body']))
            <table class="grid" cellspacing="0">
                <thead>
                    <tr>
                        @foreach($sec['columns'] as $ci => $col)
                            <th>
                                {{ $col }}
                                @if(!empty($sec['head_hi'][$ci] ?? null))
                                    <small>{{ $sec['head_hi'][$ci] }}</small>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($sec['body'] as $tr)
                        <tr>
                            @foreach($tr as $cell)
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endforeach

<div class="footer-note">
    Computer-generated document from Sargam FC Registration module. Signatures / stamps where required may be appended separately.<br/>
    सारगम पंजीकरण मॉड्यूल से कंप्यूटर जनित दस्तावेज़ - आवश्यकतानुसार हस्ताक्षर / मुहर अलग से जोड़े जा सकते हैं।
</div>

</body>
</html>
