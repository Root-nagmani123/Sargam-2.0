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
    $dated   = $fmt($data['declaration_date'] ?? '');
    $asOn    = $fmt($data['as_on_date'] ?? '');
    $rows    = $data['_tables']['properties'] ?? [];
    $sigSrc  = $data['_signature_src'][0] ?? null;
    $blank = function ($v, $w = '150px') {
        $val = ($v !== '' && $v !== null) ? e($v) : '&nbsp;';
        return '<span style="display:inline-block; min-width:'.$w.'; border-bottom:1px solid #000; text-align:center; font-weight:bold; padding:0 4px;">'.$val.'</span>';
    };
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #000; }
    .docno { text-align: right; font-weight: bold; font-size: 11px; }
    .formline { text-align: center; font-weight: bold; font-size: 9px; margin-top: 3px; }
    .title-hi { text-align: center; font-weight: bold; font-size: 12px; margin-top: 5px; }
    .title { text-align: center; font-weight: bold; font-size: 11px; margin-top: 2px; }
    .item { margin-top: 6px; font-size: 10px; }
    table.im { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table.im th, table.im td { border: 0.8px solid #000; padding: 3.5px 4px; font-size: 7.8px; vertical-align: top; }
    table.im th { text-align: center; font-weight: bold; }
    .cno { font-weight: normal; }
    .foot { width: 100%; margin-top: 18px; font-size: 10px; }
    .sig-img { max-height: 34px; }
    .pto { text-align: right; font-weight: bold; font-size: 9px; margin-top: 8px; }
    .note-hd { font-weight: bold; }
    .note-p { margin-top: 8px; line-height: 1.5; font-size: 9px; }
    .rowspace td { height: 34px; }
</style>
</head>
<body>

    {{-- ═══════════ PAGE 1 · FORM (bilingual) ═══════════ --}}
    <div class="docno">Document-6(a)</div>
    <div class="formline">[Form 1 — See Government of India's Instruction (1) and (2) below Rule 16]</div>
    <div class="title-hi">प्रथम नियुक्ति के समय भरा जाने वाला अचल संपत्ति के विवरण का फार्म</div>
    <div class="title">STATEMENT OF IMMOVABLE PROPERTY ON FIRST APPOINTMENT</div>
    <div style="text-align:center; font-weight:bold; font-size:10px; margin-top:4px;">as on date / जिस तिथि तक: {!! $blank($asOn, '130px') !!}</div>

    <div class="item"><b>1.</b> अधिकारी का पूरा नाम, तथा सेवा जिससे वह संबंधित है / Name of the Officer (in full) and service to which the officer belongs: {!! $blank($name, '300px') !!}</div>
    <div class="item"><b>2.</b> वर्तमान पद / Present Post held: {!! $blank($post, '220px') !!}</div>
    <div class="item"><b>3.</b> राज्य संवर्ग / Cadre of the State on which borne: {!! $blank($cadre, '160px') !!} &nbsp;&nbsp; <b>4.</b> वर्तमान वेतन / Present Pay (₹): {!! $blank($pay, '140px') !!}</div>

    <table class="im">
        <thead>
            <tr>
                <th style="width:14%;">जिला, उपखण्ड, तालुका एवं गांव का नाम / Name of District, Sub-Division, Taluk and Village in which property is situated<div class="cno">(1)</div></th>
                <th style="width:14%;">संपत्ति का नाम तथा विवरण (मकान/भवन एवं भूमि) / Name and details of Property (Housing / other building and Land)<div class="cno">(2)</div></th>
                <th style="width:10%;">*वर्तमान मूल्य / Present Value<div class="cno">(3)</div></th>
                <th style="width:16%;">**यदि अपने नाम पर नहीं है तो किसके नाम पर, तथा संबंध / If not in own name, in whose name held and his/her relationship to the member of the Service<div class="cno">(4)</div></th>
                <th>कैसे अर्जित की — खरीद/पट्टा***/बंधक/विरासत/उपहार आदि; अर्जन की तारीख तथा जिनसे प्राप्त की उनके नाम एवं विवरण / How acquired — purchase/lease***/mortgage/inheritance/gift, etc., with date of acquisition and details of persons from whom acquired<div class="cno">(5)</div></th>
                <th style="width:10%;">संपत्ति से वार्षिक आय / Annual Income from the Property<div class="cno">(6)</div></th>
                <th style="width:10%;">अभ्युक्तियां / Remarks<div class="cno">(7)</div></th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r['location'] ?? '' }}</td>
                    <td>{{ $r['property_details'] ?? '' }}</td>
                    <td>{{ $r['present_value'] ?? '' }}</td>
                    <td>{{ $r['in_whose_name'] ?? '' }}</td>
                    <td>{{ $r['how_acquired'] ?? '' }}</td>
                    <td>{{ $r['annual_income'] ?? '' }}</td>
                    <td>{{ $r['remarks'] ?? '' }}</td>
                </tr>
            @empty
                @for($i = 0; $i < 4; $i++)<tr class="rowspace"><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>@endfor
            @endforelse
        </tbody>
    </table>

    <table class="foot">
        <tr>
            <td style="width:50%;">दिनांक / Dated: {!! $blank($dated, '160px') !!}</td>
            <td style="text-align:right;">हस्ताक्षर / Signature:
                @if($sigSrc)<img src="{{ $sigSrc }}" class="sig-img">@else {!! $blank('', '200px') !!} @endif
            </td>
        </tr>
    </table>
    <div class="pto">कृ.पृ.पलटिए / P.T.O</div>

    <pagebreak />

    {{-- ═══════════ PAGE 2 · NOTES ═══════════ --}}
    <div style="text-align:center; font-weight:bold; margin-bottom:10px;">-2-</div>
    <div class="note-hd">टिप्पणी / Note:</div>
    <div class="note-p">
        अखिल भारतीय सिविल सेवा (आचरण) नियमावली 1968 के नियम 16(5) के अंतर्गत भा.प्र.सेवा/भा.पु.सेवा के प्रत्येक सदस्य को, तथा केन्द्रीय सिविल सेवा (आचरण) नियमावली 1965 के नियम 18(3) के अंतर्गत प्रथम एवं द्वितीय श्रेणी के सदस्यों को, इस घोषणा प्रपत्र में निजी, अर्जित की गई, विरासत में मिली, पट्टे पर ली गई या बंधक रखी गई अचल संपत्ति का विवरण — चाहे वह उसके अपने नाम पर हो या परिवार के किसी सदस्य अथवा किसी अन्य व्यक्ति के नाम पर — प्रथम नियुक्ति के समय भरकर प्रस्तुत करना होता है, और तत्पश्चात प्रत्येक 12 माह के अंतराल पर सूचना दी जानी होती है।<br>
        The declaration form is required to be filled in and submitted by every member of IAS/IPS under Rule 16(5) of the All India Services (Conduct) Rules, 1968 on first appointment, and by Class-I and Class-II Service members under Rule 18(3) of the Central Civil Services (Conduct) Rules, 1965, and thereafter at an interval of every twelve months — giving particulars of all immovable property owned, acquired or inherited by him/her, or held by him/her on lease or mortgage, either in his/her own name or in the name of any member of his/her family or any other person.
    </div>
    <div class="note-p"><b>*</b> जहाँ सही-सही मूल्य निर्धारण संभव न हो, वहाँ वर्तमान स्थिति के अनुसार अनुमानित मूल्य दर्शाएँ। / In cases where it is not possible to assess the value accurately, the approximate value in relation to present conditions may be indicated.</div>
    <div class="note-p"><b>**</b> जो लागू न हो, काट दें। / Inapplicable clause to be struck out.</div>
    <div class="note-p"><b>***</b> अल्पावधि का पट्टा भी सम्मिलित है। / Includes short-term lease also.</div>

</body>
</html>
