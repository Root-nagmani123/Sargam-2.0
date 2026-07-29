@php
    $fmt = function ($d) {
        if (! $d) return '';
        try { return \Carbon\Carbon::parse($d)->format('d-m-Y'); } catch (\Throwable $e) { return $d; }
    };
    $g      = fn ($k) => trim((string) ($data[$k] ?? ''));
    $name   = $g('officer_name');
    $desig  = $g('designation');
    $place  = config('fc.document_place');   // hard-frozen: always Mussoorie, overrides any saved value
    $reasons= $g('exemption_reasons');
    $dated  = fc_document_date();   // hard-frozen date, overrides any saved value
    $sc     = $g('status_clause');
    $sigSrc = $data['_signature_src'][0] ?? null;
    $blank = function ($v, $w = '160pt') {
        $val = ($v !== '' && $v !== null) ? e($v) : str_repeat('_', max(12, (int) round((strpos($w, 'mm') !== false ? (float) $w * 2.83465 : (float) $w) / 6)));
        return '<span style="display:inline-block; min-width:'.$w.'; border-bottom:1px solid #000; text-align:center; font-weight:bold; padding:0 4pt;">'.$val.'</span>';
    };
    $box = fn ($on) => $on ? '&#9745;' : '&#9744;';
    $clauses = [
        ['I am unmarried / a widower / a widow',
         'कि मैं अविवाहित / विधुर / विधवा हूँ।', 'That I am unmarried / a widower / a widow;'],
        ['I am married and have only one spouse living',
         'कि मैं विवाहित हूँ और मेरी केवल एक जीवित पत्नी / पति है।', 'That I am married and have only one spouse living;'],
        ['I am married and have more than one spouse living (exemption applied for)',
         'कि मैं विवाहित हूँ और मेरी एक से अधिक जीवित पत्नियाँ हैं। छूट प्रदान संबंधी प्रार्थना-पत्र संलग्न है।', 'That I have entered into or contracted a marriage with a person having a spouse living. Application for grant of exemption is enclosed.'],
        ['I am about to marry a person who has a spouse living (exemption applied for)',
         'कि मैं ऐसे व्यक्ति के साथ विवाह करने जा रहा/रही हूँ जिसकी पहले ही एक या एक से अधिक जीवित पत्नियाँ हैं। छूट प्रदान संबंधी प्रार्थना-पत्र संलग्न है।', 'That I have entered into and contracted a marriage with another person during the life-time of my spouse. Application for grant of exemption is enclosed.'],
    ];
    $rn = ['i', 'ii', 'iii', 'iv'];
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 11mm 18mm 11mm 18mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; color: #000; line-height: 1.8; }
    .docno { text-align: right; font-weight: bold; font-size: 11pt; }
    .title { text-align: center; font-weight: bold; font-size: 13pt; text-decoration: underline; }
    .title-en { text-align: center; font-weight: bold; font-size: 12pt; text-decoration: underline; margin-top: 2pt; }
    .sub { text-align: center; font-size: 9.5pt; margin-top: 2pt; }
    .lead { margin-top: 18pt; }
    .clause { margin-top: 8pt; padding-left: 6pt; }
    .clause .en { display: block; font-size: 10pt; color: #333; }
    .affirm { margin-top: 14pt; text-align: justify; }
    .sign { margin-top: 22pt; }
    .sig-img { max-height: 32pt; }
    .note { margin-top: 14pt; font-size: 10pt; }
    .ex-title { text-align: center; font-weight: bold; text-decoration: underline; font-size: 12pt; }
    .ex-body { margin-top: 10pt; text-align: justify; }
</style>
</head>
<body>

    {{-- ═══════════ PAGE 1 · DECLARATION ═══════════ --}}
    <div class="docno">Document-4</div>
    <div class="title">विवाह संबंधी घोषणापत्र</div>
    <div class="title-en">DECLARATION REGARDING MARITAL STATUS</div>
    <div class="sub">[Rule 21 and GIDs of CCS (Conduct) Rules, 1964]</div>

    <div class="lead"><b>1.</b> मैं / I, Shri/Smt./Kumari {!! $blank($name, '300pt') !!} घोषणा करता/करती हूँ / declare as under —</div>

    @foreach($clauses as $i => $c)
        <div class="clause">{!! $box($sc === $c[0]) !!} <b>({{ $rn[$i] }})</b> {{ $c[1] }} <span class="en">{{ $c[2] }}</span></div>
    @endforeach

    <div class="affirm"><b>2.</b> मैं सत्यनिष्ठा से प्रतिज्ञा करता/करती हूँ कि उपर्युक्त घोषणा सत्य है और मैं यह भी समझता/समझती हूँ कि मेरी नियुक्ति के बाद इस घोषणा के गलत सिद्ध होने पर मुझे सेवा से बरखास्त किया जा सकता है।<br>
        I solemnly affirm that the above declaration is true and I understand that in the event of the declaration being found to be incorrect after my appointment, I shall be liable to be dismissed from service.
    </div>

    <div class="sign">
        <table style="width:100%;"><tr>
            <td>दिनांक / Date: {!! $blank($dated, '150pt') !!}</td>
            <td style="text-align:right;">हस्ताक्षर / Full Signature: @if($sigSrc)<img src="{{ $sigSrc }}" class="sig-img">@else {!! $blank('', '180pt') !!} @endif</td>
        </tr></table>
        <table style="width:100%; margin-top:6pt;"><tr>
            <td>स्थान / Place: {!! $blank($place, '150pt') !!}</td>
            <td style="text-align:right;">नाम स्पष्ट अक्षरों में / Name (in Block Letters): {!! $blank($name, '200pt') !!}</td>
        </tr></table>
        <div style="text-align:right; margin-top:6pt;">पदनाम / Designation: {!! $blank($desig, '200pt') !!}</div>
    </div>

    <div class="note">टिप्पणी: कृपया उपर्युक्त कथनों में से जो लागू न हों उन्हें काट दें।<br>(*Note: Please delete clause/clauses not applicable.)</div>

    <pagebreak />

    {{-- ═══════════ PAGE 2 · EXEMPTION APPLICATION ═══════════ --}}
    <div class="ex-title">रियायत प्रदान करने के लिए आवेदन पत्र<br>APPLICATION FOR GRANT OF EXEMPTION</div>
    <div style="text-align:center; font-size:9.5pt;">(घोषणा का पैरा 1(iii) / 1(iv) देखें) &middot; (Vide para 1(iii) / 1(iv) of the Declaration)</div>

    <div style="margin-top:14pt;">सेवा में / To,<br>
        &emsp;The Secretary,<br>
        &emsp;Department of Personnel &amp; Training (DoPT),<br>
        &emsp;New Delhi</div>
    <div class="ex-body">
        महोदय / Sir,<br>
        मेरा अनुरोध है कि नीचे बताए गए कारणों को ध्यान में रखते हुए, मुझे एक से अधिक जीवित पत्नी रखने / ऐसी महिला जिसका ऐसे व्यक्ति से विवाह हुआ हो जिसकी पहले से एक या अधिक जीवित पत्नियाँ हों — की सेवा में भर्ती पर प्रतिबंध से छूट प्रदान की जाए।<br>
        I request that, in view of the reasons stated below, I may be granted exemption from the operation of the restriction on recruitment to service of one having more than one wife living / a woman who is married to a person already having one or more wives living.
    </div>
    <div style="margin-top:12pt;">कारण / Reasons:</div>
    <div style="border-bottom:1px solid #000; min-height:16pt; padding:2pt 4pt; font-weight:bold;">{{ $reasons ?: '' }}</div>
    @if(trim((string)$reasons) === '')<div style="border-bottom:1px solid #000; min-height:16pt; margin-top:10pt;">&nbsp;</div><div style="border-bottom:1px solid #000; min-height:16pt; margin-top:10pt;">&nbsp;</div>@endif

    <div style="text-align:right; margin-top:26pt;">भवदीय / Yours faithfully,<br><br>हस्ताक्षर / Signature {!! $blank('', '180pt') !!}</div>
    <div style="margin-top:10pt;">दिनांक / Dated: {!! $blank($dated, '150pt') !!}</div>

</body>
</html>
