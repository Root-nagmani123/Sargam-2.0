@php
    $fmt = function ($d) {
        if (! $d) return '';
        try { return \Carbon\Carbon::parse($d)->format('d-m-Y'); } catch (\Throwable $e) { return $d; }
    };
    $g       = fn ($k) => trim((string) ($data[$k] ?? ''));
    $name    = $g('officer_name');
    $service = $g('service');
    $mc      = $g('marital_choice');
    $pgname  = $g('parent_guardian_name');
    $pgaddr  = $g('parent_guardian_address');
    $place   = $g('place') ?: 'Mussoorie';
    $dated   = $fmt($data['declaration_date'] ?? '') ?: '24-08-2026';
    $sigSrc  = $data['_signature_src'][0] ?? null;
    $hi      = $data['_hi'] ?? [];   // candidate-typed Hindi values (blank if none)
    $blank = function ($v, $w = '45mm') {
        $val = ($v !== '' && $v !== null) ? e($v) : '&nbsp;';
        return '<span style="display:inline-block; min-width:'.$w.'; border-bottom:1px solid #000; text-align:center; font-weight:bold; padding:0 4px;">'.$val.'</span>';
    };
    $maritalHi = $mc === 'Unmarried' ? 'अविवाहित' : ($mc === 'Married' ? 'विवाहित' : 'अविवाहित / विवाहित');
    $maritalEn = $mc === 'Unmarried' ? 'unmarried' : ($mc === 'Married' ? 'married' : 'unmarried / married');
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    /* Sizes are in pt, not px: mpdf converts px at 96dpi (1px = 0.75pt), so the
       previous 11px body rendered at 8.25pt — far smaller than the official sample. */
    @page { margin: 11mm 18mm 11mm 18mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; color: #000; line-height: 1.85; }
    .docno { text-align: right; font-weight: bold; font-size: 11pt; margin-bottom: 6pt; }
    .title { text-align: center; font-weight: bold; font-size: 16pt; letter-spacing: 2pt; margin-bottom: 10pt; }
    /* Devanagari conjuncts break up under letter-spacing, so the Hindi title has none. */
    .title-hi { text-align: center; font-weight: bold; font-size: 16pt; text-decoration: underline; margin-bottom: 10pt; }
    .sub { text-align: center; font-size: 9.5pt; color: #333; margin-top: 3pt; }
    .body { margin-top: 9pt; text-align: justify; }
    .clause { margin: 6pt 0 0 26pt; text-align: justify; }
    .nb { font-weight: bold; margin-top: 10pt; }
    .sign { margin-top: 26pt; }
    .sig-img { max-height: 34pt; }
    .copy { margin-top: 20pt; }
    .cl-hd { font-weight: bold; text-decoration: underline; font-size: 12pt; margin-top: 6pt; margin-bottom: 8pt; }
    .cl-rule { font-weight: bold; font-size: 11.5pt; margin-top: 12pt; }
    .cl-body { margin-top: 6pt; text-align: justify; }
    .sep { text-align: center; font-weight: bold; letter-spacing: 3pt; margin: 14pt 0; }
    /* Rule/extract pages are dense legal text; tighter leading keeps each on one page. */
    .clar { line-height: 1.6; }
</style>
</head>
<body>

    {{-- ═══════════ PAGE 1 · HINDI DECLARATION ═══════════ --}}
    <div class="docno">Document-3</div>
    <div class="title-hi">घोषणा</div>
    <div class="body">मुझे अखिल भारतीय सेवा (आचरण) नियमावली, 1968 के नियम 11-ए तथा केन्द्रीय सिविल सेवा (आचरण) नियमावली, 1964 के नियम 13-ए (इस पृष्ठ में नीचे पुनः उद्धृत) के उपबंधों की विशेष तौर पर जानकारी दी गई है;</div>
    <div class="body">और मैं आज की तारीख में <b>{{ $maritalHi }}</b> हूँ,</div>
    <div class="body">मैं, {!! $blank($hi['name'] ?? '', '78mm') !!} (परिवीक्षाधीन का नाम) {!! $blank($hi['svc'] ?? '', '56mm') !!} (सेवा का नाम), परिवीक्षाधीन एतद्द्वारा यह वचन देता/देती हूँ कि मैं —</div>
    <div class="clause">(क) न दहेज दूँगा/दूँगी, न दहेज लूँगा/लूँगी और न ही दहेज देने अथवा लेने के लिए दुष्प्रेरित करूँगा/करूँगी, अथवा</div>
    <div class="clause">(ख) वधू अथवा वर (जो भी लागू हो) के माता-पिता या अभिभावक से प्रत्यक्ष अथवा अप्रत्यक्ष रूप से दहेज की मांग नहीं करूँगा/करूँगी।</div>
    <div class="nb">टिप्पणी: यहाँ &ldquo;दहेज&rdquo; से वही अर्थ अभिप्रेत है जो दहेज प्रतिषेध अधिनियम, 1961 (1961 का 28) में दिया गया है।</div>
    <div class="body">मैंने यह बात भली-भांति जानते हुए इस घोषणा पर हस्ताक्षर किए हैं कि दहेज से संबंधित नियमों अथवा विधि का उल्लंघन करने पर मेरे विरुद्ध उपयुक्त कार्रवाई की जा सकती है।</div>
    <div class="sign">
        <table style="width:100%;"><tr>
            <td>स्थान: {!! $blank(($hi['place'] ?? '') ?: 'Mussoorie', '45mm') !!}</td>
            <td style="text-align:right;">हस्ताक्षर: @if($sigSrc)<img src="{{ $sigSrc }}" class="sig-img">@else {!! $blank('', '50mm') !!} @endif</td>
        </tr></table>
        <table style="width:100%; margin-top:6pt;"><tr>
            <td>तारीख: {!! $blank(($hi['ddate'] ?? '') ?: '24-08-2026', '42mm') !!}</td>
            <td style="text-align:right;">(नाम साफ अक्षरों में): {!! $blank($hi['name'] ?? '', '64mm') !!}</td>
        </tr></table>
    </div>
    <div class="copy">प्रतिलिपि:
        <div>(माता-पिता / अभिभावक का नाम): {!! $blank($hi['pgname'] ?? '', '78mm') !!}</div>
        <div>पता: {!! $blank($hi['pgaddr'] ?? '', '100mm') !!}</div>
    </div>

    <pagebreak />

    {{-- ═══════════ PAGE 2 · HINDI CLARIFICATION ═══════════ --}}
    <div class="clar">
    <div class="cl-hd" style="text-align:center;">उल्लिखित नियम के बारे में स्पष्टीकरण</div>
    <div class="cl-rule">अखिल भारतीय सेवा (आचरण) नियमावली, 1968 का नियम 11-ए</div>
    <div class="cl-body">(क) दहेज देना अथवा लेना — इस सेवा का कोई भी सदस्य</div>
    <div class="clause">(i) न दहेज देगा, न दहेज लेगा और न ही दहेज देने या लेने के लिए दुष्प्रेरित करेगा; अथवा</div>
    <div class="clause">(ii) वधू अथवा वर (जो भी लागू हो) के माता-पिता या अभिभावक से प्रत्यक्ष अथवा अप्रत्यक्ष रूप से किसी दहेज की मांग नहीं करेगा/करेगी।</div>
    <div class="cl-body"><b>स्पष्टीकरण:</b> इस नियम के अंतर्गत &lsquo;दहेज&rsquo; से वही अर्थ अभिप्रेत है जो दहेज प्रतिषेध अधिनियम, 1961 (1961 का 28) में दिया गया है।</div>
    <div class="cl-rule">केन्द्रीय सिविल सेवा (आचरण) नियमावली, 1964 का नियम 13-ए — दहेज</div>
    <div class="cl-body">कोई भी सरकारी कर्मचारी —</div>
    <div class="clause">(i) न दहेज देगा, न दहेज लेगा और न ही दहेज देने या लेने के लिए दुष्प्रेरित करेगा; अथवा</div>
    <div class="clause">(ii) वधू अथवा वर (जो भी लागू हो) के माता-पिता अथवा अभिभावक से प्रत्यक्ष अथवा अप्रत्यक्ष रूप से किसी दहेज की मांग नहीं करेगा/करेगी।</div>
    <div class="cl-body"><b>स्पष्टीकरण:</b> इस नियम के अंतर्गत &lsquo;दहेज&rsquo; से वही अर्थ अभिप्रेत है जो दहेज प्रतिषेध अधिनियम, 1961 (1961 का 28) में दिया गया है।</div>
    <div class="sep">***</div>
    </div>

    <pagebreak />

    {{-- ═══════════ PAGE 3 · ENGLISH DECLARATION ═══════════ --}}
    <div class="docno">Document-3</div>
    <div class="title" style="text-decoration:underline;">DECLARATION</div>
    <div class="body">WHEREAS the provisions of Rule 11-A of the All India Services (Conduct) Rules, 1968 / Rule 13-A of the Central Civil Services (Conduct) Rules, 1964 have been specifically brought to my notice;</div>
    <div class="body">AND WHEREAS on date I am <b>{{ $maritalEn }}</b>:</div>
    <div class="body">Now therefore, I, {!! $blank($name, '72mm') !!} ({!! $blank($service, '50mm') !!}), Probationer, do hereby undertake that I shall not —</div>
    <div class="clause">(a) give or take, or abet the giving or taking of, dowry; or</div>
    <div class="clause">(b) demand, directly or indirectly, from the parents or guardians of the bride or bridegroom, as the case may be, any dowry.</div>
    <div class="nb">N.B. &mdash; &ldquo;Dowry&rdquo; shall have the same meaning as in the Dowry Prohibition Act, 1961.</div>
    <div class="body">I affix my signature to this declaration in the full understanding that any breach of the rules or law relating to dowry shall render me liable to appropriate action.</div>
    <div class="sign">
        <table style="width:100%;"><tr>
            <td>Place: {!! $blank($place, '45mm') !!}</td>
            <td style="text-align:right;">Signature: @if($sigSrc)<img src="{{ $sigSrc }}" class="sig-img">@else {!! $blank('', '50mm') !!} @endif</td>
        </tr></table>
        <table style="width:100%; margin-top:6pt;"><tr>
            <td>Dated: {!! $blank($dated, '42mm') !!}</td>
            <td style="text-align:right;">(Name of Officer in Block Letters): {!! $blank($name, '64mm') !!}</td>
        </tr></table>
    </div>
    <div class="copy"><i>Copy of the declaration to:</i>
        <div>(Name of Parent / Guardian): {!! $blank($pgname, '78mm') !!}</div>
        <div>Address: {!! $blank($pgaddr, '100mm') !!}</div>
    </div>

    <pagebreak />

    {{-- ═══════════ PAGE 4 · ENGLISH CLARIFICATION + EXTRACTS ═══════════ --}}
    <div class="clar">
    <div class="cl-hd" style="text-align:center;">CLARIFICATION ON MENTIONED RULE</div>
    <div class="cl-rule">Rule 11-A of the All India Services (Conduct) Rules, 1968</div>
    <div class="cl-body">11-A. Giving or taking of dowry — No member of the Service shall —</div>
    <div class="clause">(i) give or take or abet the giving or taking of dowry; or</div>
    <div class="clause">(ii) demand, directly or indirectly, from the parents or guardian of a bride or bridegroom, as the case may be, any dowry.</div>
    <div class="cl-body"><b>Explanation:</b> For the purpose of this rule, &ldquo;dowry&rdquo; has the same meaning as in the Dowry Prohibition Act, 1961 (28 of 1961).</div>
    <div class="cl-rule">Rule 13-A of the Central Civil Services (Conduct) Rules, 1964</div>
    <div class="cl-body">13-A. Dowry — No Government servant shall —</div>
    <div class="clause">(i) give or take or abet the giving or taking of dowry; or</div>
    <div class="clause">(ii) demand, directly or indirectly, from the parent or guardian of a bride or bridegroom, as the case may be, any dowry.</div>
    <div class="cl-body"><b>Explanation:</b> For the purposes of this rule, &ldquo;dowry&rdquo; has the same meaning as in the Dowry Prohibition Act, 1961 (28 of 1961).</div>

    <div class="cl-hd" style="text-align:center; margin-top:11pt;">EXTRACT FROM &ldquo;THE DOWRY PROHIBITION ACT, 1961&rdquo; (28 OF 1961)</div>
    <div class="cl-body"><b>Section 2. Definition of &ldquo;dowry&rdquo;</b> — In this Act, &ldquo;dowry&rdquo; means any property or valuable security given or agreed to be given either directly or indirectly —</div>
    <div class="clause">(a) by one party to a marriage to the other party to the marriage, or</div>
    <div class="clause">(b) by the parents of either party to a marriage or by any other person to either party to the marriage or to any other person; at or before or any time after the marriage in connection with the marriage of the said parties, but does not include dower or mahar in the case of persons to whom the Muslim Personal Law (Shariat) applies.</div>
    <div class="cl-body"><b>Explanation II</b> — The expression &ldquo;valuable security&rdquo; has the same meaning as in Section 30 of the Indian Penal Code (45 of 1860).</div>

    <div class="cl-hd" style="text-align:center; margin-top:11pt;">EXTRACT FROM THE INDIAN PENAL CODE (45 OF 1860)</div>
    <div class="cl-body"><b>Section 30. &ldquo;Valuable Security&rdquo;</b> — The words &ldquo;valuable security&rdquo; denote a document which is, or purports to be, a document whereby any legal right is created, extended, transferred, restricted, extinguished or released, or whereby any person acknowledges that he lies under legal liability, or has not a certain legal right.</div>
    </div>

</body>
</html>
