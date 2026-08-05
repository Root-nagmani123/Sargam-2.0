<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>FC Registration - {{ $userId }}</title>
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
        /* Government-form double rule around every page. position:fixed is repeated on each
           printed page by both engines, which a bordered wrapper <div> is not — that would
           only draw its top edge on page 1 and its bottom edge on the last page. */
        /* Inset 0 exactly: Chrome clips a fixed box to the page content area, so a negative
           inset (to push the rule into the page margin) loses the left/right edges entirely.
           The gap between rule and content therefore comes from .page-body padding below. */
        .page-frame,
        .page-frame-inner {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }
        .page-frame {
            border: 1.6pt solid #0a3d6b;
        }
        .page-frame-inner {
            border: 0.5pt solid #0a3d6b;
            margin: 2.4pt;
        }
        .page-body {
            padding: 3mm 3.5mm 2mm;
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
            padding: 7px 8px 8px;
            margin-bottom: 6px;
            background: #f8fafc;
        }
        /* 3-column masthead: the crest sits on the LEFT and an equal-width empty cell on
           the right keeps the academy name optically centred on the page. */
        table.masthead-grid {
            width: 100%;
            border-collapse: collapse;
        }
        table.masthead-grid td {
            border: 0;
            padding: 0;
            vertical-align: middle;
        }
        .masthead-side {
            width: 62px;
        }
        /* Explicit, not inherited from .masthead — Dompdf does not pass text-align down
           into table cells, which left the academy name flush left in the fallback render. */
        .masthead-main {
            text-align: center;
        }
        .masthead-logo {
            text-align: left;
        }
        .masthead-logo img {
            width: 58px;
            height: auto;
        }
        .masthead-hi {
            font-size: 12.5pt;
            font-weight: bold;
            color: #0a3d6b;
            margin: 0 0 3px;
            line-height: 1.45;
        }
        .masthead-en-org {
            font-size: 10.5pt;
            font-weight: bold;
            color: #0a3d6b;
            margin: 0 0 5px;
        }
        .masthead-place-hi {
            font-size: 9.5pt;
            color: #333;
            margin: 0 0 1px;
            line-height: 1.45;
        }
        .masthead-place {
            font-size: 9pt;
            color: #333;
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
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .meta-bar .label {
            width: 22%;
            background: #e8eef5;
            font-weight: bold;
            color: #0a3d6b;
        }
        /* Ordinal suffix in the course title ("100th"); Dompdf does not style <sup> itself. */
        .meta-bar sup {
            font-size: 70%;
            vertical-align: super;
            line-height: 0;
        }
        .section {
            margin-top: 6px;
            /* Do not avoid-break whole sections: long blocks jump to next page and leave a huge gap */
            page-break-inside: auto;
        }
        /* Muted slate rather than the old solid navy: at this size a full-width saturated
           bar on every section dominated the page. Desaturated fill + a darker left rule
           keeps the hierarchy readable without shouting. */
        .section-h {
            background: #d8e2ec;
            color: #24486e;
            padding: 3px 8px;
            border-left: 3px solid #6d8bab;
            font-size: 9.5pt;
            font-weight: bold;
            break-after: avoid-page;
        }
        .section-h-sub {
            font-size: 8pt;
            font-weight: normal;
            color: #5b7188;
        }
        table.fields {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.8pt;
            /* Fixed layout: without it a single long unbroken value (e.g. a Vision
               Statement with no spaces) squeezes the label column to one word per line. */
            table-layout: fixed;
            /* Outer box only. Row-to-row rules live on the group headings below, so a run of
               fields inside one group reads as a single block instead of a ruled ledger. */
            border: 1px solid #bbb;
        }
        table.fields td {
            /* Vertical rule between label and value only — no horizontal rule per row. */
            border: 0;
            border-right: 1px solid #bbb;
            padding: 4px 6px;
            vertical-align: top;
            /* Long filenames / unbroken statements must wrap instead of running off the
               page. `anywhere` also shrinks min-content width, so an unbreakable value can
               never widen the table and trigger Chrome's shrink-to-fit. Only the two
               columns here are affected, and both have explicit widths, so ordinary labels
               still break at spaces. */
            overflow-wrap: anywhere;
        }
        table.fields .lab {
            width: 32%;
            background: #f7f9fc;
            /* mPDF + bold + script-mix can emit tofu; keep labels regular weight */
            font-weight: normal;
            color: #0a3d6e;
        }
        /* Hindi sits inline after the English in the SAME cell ("Gender / लिंग"), matching
           the meta-bar convention above, and only when it differs. hindiLabel() falls back
           to the English string when unmapped, so a second label column rendered the
           identical text twice on almost every row — half the page width for nothing, and
           double-height rows wherever the label was long. Inline (not stacked) keeps the
           row on one line, which is what kept the page count down. */
        table.fields .lab-hi {
            font-size: 8pt;
            color: #555;
        }
        table.fields .val {
            width: 68%;
        }
        /* Group heading ("Mailing Address", "Physical Details") — this is the only
           horizontal rule left inside a section, so groups stay clearly separated. */
        table.fields .grp {
            background: #edf2f7;
            font-weight: bold;
            color: #24486e;
            border-top: 1px solid #bbb;
            border-bottom: 1px solid #bbb;
            border-right: 0;
        }
        table.fields tr:first-child .grp {
            border-top: 0;
        }
        /* Auto layout, so 5-10 columns of very uneven width size to their content — fixed
           layout gives every column an equal share and hyphenates "Matriculation State"
           mid-word. Overflow is instead prevented by overflow-wrap:anywhere on the cells
           (see below), which is what stops a long filename widening the table past the
           page and triggering Chrome's shrink-to-fit on the WHOLE document. */
        table.grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.3pt;
        }
        /* Column headings sit one level below a section header, so they take the same muted
           palette a shade stronger — not the old solid navy. */
        table.grid th {
            background: #e4ebf2;
            color: #24486e;
            border: 1px solid #b9c6d6;
            padding: 4px 3px;
            text-align: center;
            font-weight: bold;
        }
        table.grid th small {
            display: block;
            font-weight: normal;
            font-size: 7.5pt;
            color: #5b7188;
            margin-top: 2px;
        }
        table.grid td {
            border: 1px solid #999;
            padding: 3px 4px;
            vertical-align: top;
            /* `anywhere`, not `break-word`: only `anywhere` also shrinks the cell's
               min-content width, which is what actually stops an unbreakable filename from
               widening the table past the page. `break-word` wraps visually but still
               reports the full word as the minimum, so the table overflows anyway. */
            overflow-wrap: anywhere;
        }
        table.grid th {
            /* Headings wrap at spaces only — never hyphenate "Matriculation" mid-word. */
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        table.grid tr:nth-child(even) td {
            background: #fafafa;
        }
        .footer-note {
            margin-top: 6px;
            padding-top: 4px;
            border-top: 1px solid #ccc;
            font-size: 7.2pt;
            line-height: 1.25;
            color: #555;
            text-align: center;
            /* Both lines travel together: the Hindi line alone was spilling onto a page of
               its own whenever the content ended near the bottom of a page. */
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .photo-cell {
            width: 112px;
            text-align: center;
            vertical-align: middle;
            padding: 4px !important;
            background: #f8fafc;
        }
        /* Fixed passport-style frame: with height:auto a landscape upload rendered wide and
           short, so the header block changed height from student to student. Both axes must
           be pinned for object-fit:cover to actually crop. */
        .photo-cell img {
            display: block;
            margin: 0 auto;
            width: 96px;
            height: 120px;
            object-fit: cover;
            object-position: top center;
            border: 1px solid #999;
        }
        /* Specimen signature, boxed directly under the photograph. */
        .sign-box {
            width: 96px;
            margin: 4px auto 0;
            border: 1px solid #999;
            background: #fff;
            padding: 2px;
        }
        /* height:auto, not a fixed box — Dompdf ignores object-fit and would stretch the
           specimen out of shape. The source is already capped at 220x110 px. */
        .photo-cell .sign-img {
            width: 90px;
            height: auto;
            max-height: 40px;
            border: 0;
            margin: 0 auto;
        }
        .sign-empty {
            display: block;
            height: 30px;
            line-height: 30px;
            font-size: 8pt;
            color: #888;
        }
        .sign-cap {
            font-size: 6.5pt;
            color: #555;
            margin-top: 1px;
            line-height: 1.2;
        }
    </style>
</head>
<body>

<div class="page-frame"></div>
<div class="page-frame-inner"></div>

<div class="page-body">

<div class="masthead">
    <table class="masthead-grid" cellspacing="0">
        <tr>
            @if(!empty($lbsnaaLogoDataUri))
                <td class="masthead-side masthead-logo">
                    <img src="{{ $lbsnaaLogoDataUri }}" alt="LBSNAA">
                </td>
            @endif
            <td class="masthead-main">
                <p class="masthead-hi">लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी.</p>
                <p class="masthead-en-org">Lal Bahadur Shastri National Academy of Administration,</p>
                <p class="masthead-place-hi">मसूरी-248179(उत्तराखंड)</p>
                <p class="masthead-place">Mussoorie-248 179 (Uttarakhand)</p>
            </td>
            @if(!empty($lbsnaaLogoDataUri))
                <td class="masthead-side">&nbsp;</td>
            @endif
        </tr>
    </table>
</div>

<div class="doc-title">
    <p class="doc-title-en">DESCRIPTIVE REGISTRATION PROFILE</p>
    <p class="doc-title-hi">वर्णनात्मक पंजीकरण प्रोफ़ाइल</p>
</div>

@php
    // Rows are assembled first so the photo/signature cell can span exactly as many of
    // them as actually render — the course rows below are only present when an admin has
    // filled the Path Page / Front Page.
    $ch = $courseHeader ?? [];
    $metaRows = [
        ['Name / नाम', ($pdfFullName ?? '') !== '' ? $pdfFullName : '-', true],
    ];
    // The internal user id is meaningful to the academy, not to the trainee, so it stays on
    // the admin profile PDF and is left off the Descriptive Roll the trainee downloads.
    if (empty($isDescriptiveRoll)) {
        $metaRows[] = ['User ID / उपयोगकर्ता', $userId, false];
    }
    if (!empty($pdfFormName)) {
        $metaRows[] = ['Form / प्रपत्र', $pdfFormName, false];
    }
    if (!empty($ch['course_title'])) {
        $metaRows[] = ['Course / पाठ्यक्रम', $ch['course_title'], false];
    }
    if (!empty($ch['course_duration'])) {
        $metaRows[] = ['Course Duration / पाठ्यक्रम अवधि', $ch['course_duration'], false];
    }
    if (!empty($ch['coordinator_name'])) {
        $coordinator = $ch['coordinator_name'];
        if (!empty($ch['coordinator_designation']) && $ch['coordinator_designation'] !== $ch['coordinator_name']) {
            $coordinator .= ' ('.$ch['coordinator_designation'].')';
        }
        $metaRows[] = ['Course Coordinator / पाठ्यक्रम समन्वयक', $coordinator, false];
    }
    $metaRows[] = ['Generated / जारी दिनांक', $printedAt, false];
@endphp

<table class="meta-bar">
    @foreach($metaRows as $i => $row)
    <tr>
        <td class="label">{{ $row[0] }}</td>
        <td>@if($row[2])<strong>{{ $row[1] }}</strong>@else{{ $row[1] }}@endif</td>
        @if($i === 0)
        <td class="photo-cell" rowspan="{{ count($metaRows) }}">
            @if(!empty($photoDataUri))
                <img src="{{ $photoDataUri }}" alt="Photo" width="96" height="124">
            @elseif(!empty($photoUrl))
                <img src="{{ $photoUrl }}" alt="Photo" width="96" height="124">
            @else
                <span style="font-size:8pt;color:#888;">Photo<br/>फोटो<br/>-</span>
            @endif

            <div class="sign-box">
                @if(!empty($signatureDataUri))
                    <img class="sign-img" src="{{ $signatureDataUri }}" alt="Signature">
                @else
                    <span class="sign-empty">-</span>
                @endif
                <div class="sign-cap">Signature / हस्ताक्षर</div>
            </div>
        </td>
        @endif
    </tr>
    @endforeach
</table>

@php
    // hindiSectionTitle() / hindiLabel() return the ENGLISH string when a term has no
    // Hindi mapping, so a naive bilingual render prints "Bank Details | Bank Details".
    // Show the Hindi side only when it is genuinely a different string.
    $fcHasHindi = function ($en, $hi) {
        $hi = trim(preg_replace('/\s+/u', ' ', (string) $hi));
        $en = trim(preg_replace('/\s+/u', ' ', (string) $en));

        return $hi !== '' && mb_strtolower($hi) !== mb_strtolower($en);
    };

    // A one-column group table restates its own name in the column heading
    // ("Academic Distinction" / "Academic Distinction", "Hobbies" / "Hobby"), which reads
    // as a stutter now that section headers show the bare group label. Drop the heading row
    // in that case only — every multi-column table has genuinely distinct headings and
    // keeps them, as does a single-column table whose heading says something different.
    $fcHeadIsRedundant = function ($title, $columns) {
        $columns = array_values((array) $columns);
        if (count($columns) !== 1) {
            return false;
        }

        $stem = function ($s) {
            $s = mb_strtolower(trim((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', (string) $s)));
            $s = trim((string) preg_replace('/\s+/', ' ', $s));
            // Crude singularisation so a "Hobbies" section matches its "Hobby" column.
            if (str_ends_with($s, 'ies')) {
                return mb_substr($s, 0, -3).'y';
            }
            if (str_ends_with($s, 's') && ! str_ends_with($s, 'ss')) {
                return mb_substr($s, 0, -1);
            }

            return $s;
        };

        return $stem($title) !== '' && $stem($title) === $stem($columns[0]);
    };
@endphp

@foreach($sections as $sec)
    @php
        // Grouped sections carry a fully-qualified title ("Descriptive Roll Continue... -
        // Hobbies"); print just the group label so headers stay short and scannable.
        $secTitle   = filled($sec['group_label'] ?? null) ? $sec['group_label'] : ($sec['title_en'] ?? '');
        $secTitleHi = filled($sec['group_label'] ?? null) ? ($sec['group_label_hi'] ?? '') : ($sec['title_hi'] ?? '');
    @endphp
    <div class="section">
        <div class="section-h">
            {{ $secTitle }}
            @if($fcHasHindi($secTitle, $secTitleHi))
                <span class="section-h-sub"> | {{ $secTitleHi }}</span>
            @endif
        </div>

        @if(($sec['type'] ?? '') === 'fields' && !empty($sec['rows']))
            <table class="fields" cellspacing="0">
                @php $lastGroup = null; @endphp
                @foreach($sec['rows'] as $row)
                    @if(!empty($row['group']) && $row['group'] !== $lastGroup)
                        <tr>
                            <td colspan="2" class="grp">{{ $row['group'] }}</td>
                        </tr>
                        @php $lastGroup = $row['group']; @endphp
                    @endif
                    <tr>
                        <td class="lab">
                            {{ $row['en'] }}@if($fcHasHindi($row['en'] ?? '', $row['hi'] ?? ''))<span class="lab-hi"> / {{ $row['hi'] }}</span>@endif
                        </td>
                        <td class="val">{{ $row['value'] }}</td>
                    </tr>
                @endforeach
            </table>
        @elseif(($sec['type'] ?? '') === 'table' && !empty($sec['body']))
            <table class="grid" cellspacing="0">
                @unless($fcHeadIsRedundant($secTitle, $sec['columns'] ?? []))
                <thead>
                    <tr>
                        @foreach($sec['columns'] as $ci => $col)
                            <th>
                                {{ $col }}
                                @if($fcHasHindi($col, $sec['head_hi'][$ci] ?? ''))
                                    <small>{{ $sec['head_hi'][$ci] }}</small>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                @endunless
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

</div>{{-- .page-body --}}

@if(!empty($autoPrint))
    {{-- Served as the browser Print view: open the dialog once the fonts and the embedded
         photo/signature have decoded, so the preview is never missing them. --}}
    <script>
        window.addEventListener('load', function () {
            var go = function () { window.print(); };
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(go).catch(go);
            } else {
                go();
            }
        });
    </script>
@endif

</body>
</html>
