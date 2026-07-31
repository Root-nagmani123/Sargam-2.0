@php
    $fmt = function ($d) {
        if (! $d) return '';
        try { return \Carbon\Carbon::parse($d)->format('d-m-Y'); } catch (\Throwable $e) { return $d; }
    };
    $g       = fn ($k) => trim((string) ($data[$k] ?? ''));
    $name    = $g('officer_name');
    $post    = $g('present_post');
    $pay     = $g('present_pay');
    $year    = $g('year');
    $dated   = fc_document_date();   // hard-frozen date, overrides any saved value
    $rows    = $data['_tables']['movables'] ?? [];
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
    @page { margin: 11mm 12mm 11mm 12mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; color: #000; }
    .docno { text-align: right; font-weight: bold; font-size: 11pt; }
    .title-hi { text-align: center; font-weight: bold; font-size: 12pt; margin-top: 4pt; }
    .title { text-align: center; font-weight: bold; font-size: 11.5pt; margin-top: 2pt; }
    .brackets { text-align: center; font-size: 9.5pt; margin-top: 3pt; line-height: 1.4; }
    .year { text-align: right; font-weight: bold; margin-top: 8pt; }
    .item { margin-top: 6pt; font-size: 10.5pt; }
    table.mp { width: 100%; border-collapse: collapse; margin-top: 10pt; }
    table.mp th, table.mp td { border: 0.8px solid #000; padding: 4pt 5pt; font-size: 9.5pt; vertical-align: top; }
    table.mp th { text-align: center; font-weight: bold; }
    .cno { font-weight: normal; }
    .foot { width: 100%; margin-top: 20pt; font-size: 10.5pt; }
    .sig-img { max-height: 34pt; }
    .pto { text-align: right; font-weight: bold; font-size: 9.5pt; margin-top: 8pt; }
    .note-hd { font-weight: bold; }
    .note-p { margin-top: 8pt; line-height: 1.5; }
    .rowspace td { height: 30pt; }
</style>
</head>
<body>

    {{-- ═══════════ PAGE 1 · FORM (bilingual) ═══════════ --}}
    <div class="docno">Document-6-B</div>
    <div class="title-hi">प्रथम नियुक्ति के समय भरा जाने वाला चल संपत्ति के विवरण का फार्म</div>
    <div class="title">STATEMENT OF MOVABLE PROPERTY ON FIRST APPOINTMENT</div>
    <div class="brackets">
        [b. Shares, debentures, postal Cumulative Time Deposits and cash including bank deposits inherited by him or similarly owned, acquired or held by him]<br>
        [c. Other movable property owned, acquired or held by him]
    </div>

    <div class="year">वर्ष / YEAR: {!! $blank($year, '120pt') !!}</div>

    <div class="item"><b>1.</b> अधिकारी का पूरा नाम, तथा सेवा जिससे वह संबंधित है / Name of the Officer (in full) and service to which the officer belongs: {!! $blank($name, '300pt') !!}</div>
    <div class="item"><b>2.</b> वर्तमान पद / Present Post: {!! $blank($post, '200pt') !!} &nbsp;&nbsp; <b>3.</b> वर्तमान वेतन / Present Pay (₹): {!! $blank($pay, '150pt') !!}</div>

    <table class="mp">
        <thead>
            <tr>
                <th style="width:20%;">चल संपत्ति का नाम तथा विवरण / Name and details of Movable Property<div class="cno">(1)</div></th>
                <th style="width:12%;">*वर्तमान मूल्य / Present Value<div class="cno">(2)</div></th>
                <th style="width:22%;">यदि अपने नाम पर नहीं है तो किसके नाम पर, तथा कर्मचारी के साथ संबंध / If not in own name, in whose name held and his/her relationship to the Govt. Servant<div class="cno">(3)</div></th>
                <th>कैसे अर्जित की — खरीद/विरासत/उपहार आदि; अर्जन की तारीख तथा जिनसे प्राप्त की उनके नाम एवं विवरण / How acquired — purchase/inheritance/gift, etc., with date of acquisition and details of persons from whom acquired<div class="cno">(4)</div></th>
                <th style="width:14%;">अभ्युक्तियां / Remarks<div class="cno">(5)</div></th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r['description'] ?? '' }}</td>
                    <td>{{ $r['present_value'] ?? '' }}</td>
                    <td>{{ $r['in_whose_name'] ?? '' }}</td>
                    <td>{{ $r['how_acquired'] ?? '' }}</td>
                    <td>{{ $r['remarks'] ?? '' }}</td>
                </tr>
            @empty
                @for($i = 0; $i < 5; $i++)<tr class="rowspace"><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>@endfor
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
        भा.प्र.सेवा/भा.पु.सेवा तथा प्रथम एवं द्वितीय श्रेणी सेवा के प्रत्येक सदस्य को इस घोषणा फार्म में अपनी उस समस्त चल संपत्ति का विवरण देना होता है जो चाहे उसके अपने नाम पर हो या परिवार के किसी सदस्य अथवा किसी अन्य व्यक्ति के नाम पर हो।<br>
        The declaration form is required to be filled in and submitted by every member of IAS/IPS and Class-I and Class-II Service, giving particulars of all movable property held by him either in his own name or in the name of any member of his family or in the name of any other person.
    </div>
    <div class="note-p">
        <b>*</b> जहाँ सही-सही मूल्य निर्धारण संभव न हो, वहाँ वर्तमान स्थिति के अनुसार अनुमानित मूल्य दर्शाएँ।<br>
        In cases where it is not possible to assess the value accurately, the approximate value in relation to present conditions may be indicated.
    </div>

</body>
</html>
