@php
    $fmt = function ($d) {
        if (! $d) return '';
        try { return \Carbon\Carbon::parse($d)->format('d-m-Y'); } catch (\Throwable $e) { return $d; }
    };
    $g       = fn ($k) => trim((string) ($data[$k] ?? ''));
    $name    = $g('officer_name');
    $post    = $g('present_post');
    $cadre   = $g('cadre');
    $pay     = $g('present_pay');
    $dated   = fc_document_date();   // hard-frozen date, overrides any saved value
    $asOn    = $fmt($data['as_on_date'] ?? '');
    $rows    = $data['_tables']['properties'] ?? [];
    $sigSrc  = $data['_signature_src'][0] ?? null;
    $blank = function ($v, $w = '150pt') {
        $val = ($v !== '' && $v !== null) ? e($v) : str_repeat('_', max(12, (int) round((strpos($w, 'mm') !== false ? (float) $w * 2.83465 : (float) $w) / 6)));
        return '<span style="display:inline-block; min-width:'.$w.'; border-bottom:1px solid #000; text-align:center; font-weight:bold; padding:0 4pt;">'.$val.'</span>';
    };
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    /* Landscape, as in the official Document-6(a) sample: the 7-column property grid
       cannot be read at portrait width. Content is unchanged — only the sheet. */
    @page { sheet-size: A4-L; margin: 11mm 12mm 11mm 12mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; color: #000; }
    .docno { text-align: right; font-weight: bold; font-size: 11pt; }
    .formline { text-align: center; font-weight: bold; font-size: 9.5pt; margin-top: 3pt; }
    .title-hi { text-align: center; font-weight: bold; font-size: 12pt; margin-top: 5pt; }
    .title { text-align: center; font-weight: bold; font-size: 11pt; margin-top: 2pt; }
    .item { margin-top: 6pt; font-size: 10pt; }
    table.im { width: 100%; border-collapse: collapse; margin-top: 10pt; }
    table.im th, table.im td { border: 0.8px solid #000; padding: 4pt 5pt; font-size: 9.5pt; vertical-align: top; }
    table.im th { text-align: center; font-weight: bold; }
    .cno { font-weight: normal; }
    .foot { width: 100%; margin-top: 18pt; font-size: 10pt; }
    .sig-img { max-height: 34pt; }
    .pto { text-align: right; font-weight: bold; font-size: 9.5pt; margin-top: 8pt; }
    .note-hd { font-weight: bold; }
    .note-p { margin-top: 8pt; line-height: 1.5; font-size: 9.5pt; }
    .rowspace td { height: 46pt; }
</style>
</head>
<body>

    {{-- ═══════════ PAGE 1 · FORM (bilingual) ═══════════ --}}
    <div class="docno">Document-6(a)</div>
    <div class="formline">[Form 1 — See Government of India's Instruction (1) and (2) below Rule 16]</div>
    <div class="title-hi">प्रथम नियुक्ति के समय भरा जाने वाला अचल संपत्ति के विवरण का फार्म</div>
    <div class="title" style="white-space:nowrap; font-size:9pt;">STATEMENT OF IMMOVABLE PROPERTY ON FIRST APPOINTMENT<span style="text-decoration:none; font-weight:bold;">&nbsp; as on date / जिस तिथि तक: {!! $blank($asOn, '90pt') !!}</span></div>

    <div class="item"><b>1.</b> अधिकारी का पूरा नाम, तथा सेवा जिससे वह संबंधित है / Name of the Officer (in full) and service to which the officer belongs: {!! $blank($name, '300pt') !!}</div>
    <div class="item"><b>2.</b> वर्तमान पद / Present Post held: {!! $blank($post, '220pt') !!}</div>
    <div class="item"><b>3.</b> राज्य संवर्ग जिससे संबंधित है/ Cadre of the State on which borne: {!! $blank($cadre, '160pt') !!} &nbsp;&nbsp; <b>4.</b> वर्तमान वेतन / Present Pay (₹): {!! $blank($pay, '140pt') !!}</div>

    <table class="im">
        <thead>
            <tr>
                <th style="width:13%;">जिला, उपखण्ड, तालुका एवं गांव का नाम जहां संपत्ति है / Name of District, Sub-Division, Taluk and Village in which property is situated</th>
                <th colspan="2">संपत्ति का नाम तथा विवरण / Name and details of Property<br>मकान एवं अन्य भवन तथा भूमि / Housing and other building and Land</th>
                <th style="width:10%;">* वर्तमान मूल्य / Present Value</th>
                <th style="width:16%;">** यदि अपने नाम पर नहीं है तो उसका नाम, जिसके नाम पर है तथा उसका कर्मचारी के साथ संबंध / If not in own name, in whose name held and his/her relationship to the member of the Service</th>
                <th>कैसे अर्जित की- खरीदी, पट्टे*** पर ली, बंधक रखी, विरासत में मिली, उपहार-स्वरूप मिली या अन्य तरह प्राप्त हुई। अर्जित करने की तारीख दें तथा उस व्यक्ति का नाम एवं विवरण भी दें जिससे प्राप्त की है / How acquired whether by purchase, lease*** mortgage, inheritance gift or otherwise, with date of acquisition and name with details of persons from whom acquired</th>
                <th style="width:10%;">संपत्ति से प्राप्त वार्षिक आय / Annual Income from the Property</th>
                <th style="width:10%;">अभ्युक्तियां / REMARKS</th>
            </tr>
            <tr>
                <th class="cno">1</th>
                <th class="cno" style="width:9%;">2</th>
                <th class="cno" style="width:7%;">3</th>
                <th class="cno">4</th>
                <th class="cno">5</th>
                <th class="cno">6</th>
                <th class="cno">7</th>
                <th class="cno">8</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="height:28pt; vertical-align:top;"></td>
                <td colspan="2" style="text-align:left; vertical-align:top;">2. Housing and other buildings</td>
                <td></td><td></td><td></td><td></td><td></td>
            </tr>
            <tr>
                <td style="height:28pt; vertical-align:top;"></td>
                <td colspan="2" style="text-align:left; vertical-align:top;">3. Land</td>
                <td></td><td></td><td></td><td></td><td></td>
            </tr>
            @forelse($rows as $r)
                <tr>
                    <td style="vertical-align:top;">{{ $r['location'] ?? '' }}</td>
                    <td colspan="2" style="vertical-align:top;">{{ $r['property_details'] ?? '' }}</td>
                    <td style="vertical-align:top;">{{ $r['present_value'] ?? '' }}</td>
                    <td style="vertical-align:top;">{{ $r['in_whose_name'] ?? '' }}</td>
                    <td style="vertical-align:top;">{{ $r['how_acquired'] ?? '' }}</td>
                    <td style="vertical-align:top;">{{ $r['annual_income'] ?? '' }}</td>
                    <td style="vertical-align:top;">{{ $r['remarks'] ?? '' }}</td>
                </tr>
            @empty
                @for($i = 0; $i < 1; $i++)<tr><td style="height:40pt;"></td><td colspan="2"></td><td></td><td></td><td></td><td></td><td></td></tr>@endfor
            @endforelse
        </tbody>
    </table>

    <table class="foot">
        <tr>
            <td style="width:50%;">दिनांक / Dated: {!! $blank($dated, '160pt') !!}</td>
            <td style="text-align:right;">हस्ताक्षर / Signature:
                @if($sigSrc)<img src="{{ $sigSrc }}" class="sig-img">@else {!! $blank('', '200pt') !!} @endif
            </td>
        </tr>
    </table>
    <div class="pto">कृ.पृ.पलटिए / P.T.O</div>

    <pagebreak />

    {{-- ═══════════ PAGE 2 · NOTES ═══════════ --}}
    <div style="text-align:center; font-weight:bold; margin-bottom:10pt;">-2-</div>
    <div class="note-hd">टिप्पणी / Note:</div>
    <div class="note-p">
        अखिल भारतीय सिविल सेवा (आचरण) नियमावली 1968 के नियम 16(5) के अंतर्गत भा.प्र.सेवा/भा.पु.सेवा के प्रत्येक सदस्य को, और केन्द्रीय सिविल सेवा (आचरण) नियमावली 1965 के नियम 18(3) के अंतर्गत प्रथम एवं द्वितीय श्रेणी के सदस्यों को, इस घोषणा प्रपत्र में निजी, अर्जित की गई, विरासत में मिली, पट्टे पर ली गई या बंधक रखी गई अचल संपत्ति का विवरण — चाहे वह उसके अपने नाम पर हो या परिवार के किसी सदस्य अथवा किसी अन्य व्यक्ति के नाम पर हो — प्रथम नियुक्ति के समय भरकर प्रस्तुत करना होता है, और बाद में इस आशय की सूचना प्रत्येक 12 माह के अंतराल में दी जानी होती है।<br>
        The declaration form is required to be filled in and submitted by every member of IAS/IPS under Rule 16(5) of the All India Services (Conduct) Rules, 1968 on first appointment to the service, and by Class-I and Class-II Service members under Rule 18(3) of the Central Civil Services (Conduct) Rules, 1965, on first appointment to the service and thereafter at an interval of every twelve months — giving particulars of all immovable property owned, acquired or inherited by him/her, or held by him/her on lease or mortgage, either in his/her own name or in the name of any member of his/her family or any other person.
    </div>
    <div class="note-p"><b>*</b> जहाँ सही-सही मूल्य निर्धारण संभव न हो, वहाँ वर्तमान स्थिति के अनुसार अनुमानित मूल्य दर्शाएँ। / In cases where it is not possible to assess the value accurately, the approximate value in relation to present conditions may be indicated.</div>
    <div class="note-p"><b>**</b> जो लागू न हो, काट दें। / Inapplicable clause to be struck out.</div>
    <div class="note-p"><b>***</b> अल्पावधि का पट्टा भी सम्मिलित है। / Includes short-term lease also.</div>

</body>
</html>
