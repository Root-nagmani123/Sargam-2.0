@php
    $fmt = function ($d) {
        if (! $d) return '';
        try { return \Carbon\Carbon::parse($d)->format('d-m-Y'); } catch (\Throwable $e) { return $d; }
    };
    $g       = fn ($k) => trim((string) ($data[$k] ?? ''));
    $name    = $g('officer_name');
    $desig   = $g('designation');
    $dob     = $fmt($data['date_of_birth'] ?? '');
    $ason    = $fmt($data['details_as_on'] ?? '');
    $place   = 'Mussoorie';   // hard-frozen, overrides any saved value
    $dated   = '24-08-2026';  // hard-frozen date, overrides any saved value
    $rows    = $data['_tables']['members'] ?? [];
    $sigSrc  = $data['_signature_src'][0] ?? null;
    $minRows = max(10, count($rows));   // fixed 10-row grid like the sample
    $blank = function ($v, $w = '160pt') {
        $val = ($v !== '' && $v !== null) ? e($v) : str_repeat('_', max(12, (int) round((strpos($w, 'mm') !== false ? (float) $w * 2.83465 : (float) $w) / 6)));
        return '<span style="display:inline-block; min-width:'.$w.'; border-bottom:1px solid #000; font-weight:bold; padding:0 4pt;">'.$val.'</span>';
    };
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 11mm 12mm 11mm 12mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; color: #000; line-height: 1.45; }
    .docno { text-align: right; font-weight: bold; font-size: 11pt; }
    .title { text-align: center; font-weight: bold; font-size: 13pt; text-decoration: underline; margin-top: 4pt; }
    .sub { text-align: center; font-weight: bold; font-size: 9.5pt; margin-top: 2pt; }
    .title-hi { text-align: center; font-weight: bold; font-size: 12pt; margin-top: 3pt; }
    .hdr { margin-top: 9pt; }
    .hdr .lbl { font-weight: bold; }
    table.fm { width: 100%; border-collapse: collapse; margin-top: 10pt; }
    table.fm th, table.fm td { border: 0.8px solid #000; padding: 4pt 5pt; font-size: 9.5pt; vertical-align: middle; }
    table.fm th { text-align: center; font-weight: bold; }
    .cno { font-weight: normal; }
    .rn { width: 24pt; text-align: center; }
    .rowspace td { height: 30pt; }
    .office { text-align: center; color: #777; font-size: 9.5pt; }
    .decl { margin-top: 14pt; text-align: justify; }
    .sign-tbl { width: 100%; margin-top: 18pt; }
    .sign-tbl td { vertical-align: bottom; font-size: 10pt; }
    .sig-img { max-height: 32pt; }
    .notes { margin-top: 16pt; border-top: 1px solid #000; padding-top: 8pt; font-size: 9.5pt; line-height: 1.5; }
    .note-p { margin-top: 6pt; text-align: justify; }
    .note-hi { display: block; color: #000; }
    .def-hd { font-weight: bold; margin-top: 10pt; text-align: justify; }
    .clause { margin: 4pt 0 0 20pt; text-align: justify; }
</style>
</head>
<body>

    <div class="docno">Document-1 / दस्तावेज़-1</div>
    <div class="title">Form No. 3: Details of Family</div>
    <div class="sub">[See Rule 54(12) of CCS (Pension) Rules, 1972]</div>
    <div class="title-hi">फॉर्म सं. 3 : परिवार का विवरण</div>

    <div class="hdr"><span class="lbl">Name of the Government Servant / सरकारी कर्मचारी का नाम:</span> {!! $blank($name, '280pt') !!}</div>
    <div class="hdr"><span class="lbl">Designation / पद नाम:</span> {!! $blank($desig, '300pt') !!}</div>
    <div class="hdr"><span class="lbl">Date of Birth / जन्म तिथि:</span> {!! $blank($dob, '200pt') !!}</div>
    <div class="hdr"><span class="lbl">Details of the members of my family* as on / परिवार के सदस्यों का विवरण, तदनांक:</span> {!! $blank($ason, '150pt') !!}</div>

    <table class="fm">
        <thead>
            <tr>
                <th class="rn">S.No.<br>क्र.सं.<br><span class="cno">(1)</span></th>
                <th>Name of the members of family* / परिवार के सदस्य का नाम<br><span class="cno">(2)</span></th>
                <th style="width:12%;">Date of Birth** / जन्म तिथि<br><span class="cno">(3)</span></th>
                <th style="width:15%;">Relationship with the officer / अधिकारी के साथ संबंध<br><span class="cno">(4)</span></th>
                <th style="width:12%;">Marital Status / वैवाहिक स्थिति<br><span class="cno">(5)</span></th>
                <th style="width:14%;">Remarks / टिप्पणी<br><span class="cno">(6)</span></th>
                <th style="width:14%;">Dated signature of Head of Office / कार्यालय प्रमुख के दिनांकित हस्ताक्षर<br><span class="cno">(7)</span></th>
            </tr>
        </thead>
        <tbody>
            @for($i = 0; $i < $minRows; $i++)
                @php $r = $rows[$i] ?? null; @endphp
                <tr class="{{ $r ? '' : 'rowspace' }}">
                    <td class="rn">{{ $i + 1 }}.</td>
                    <td>{{ $r['name'] ?? '' }}</td>
                    <td>{{ $r ? $fmt($r['dob'] ?? '') : '' }}</td>
                    <td>{{ $r['relationship'] ?? '' }}</td>
                    <td>{{ $r['marital_status'] ?? '' }}</td>
                    <td>{{ $r['remarks'] ?? '' }}</td>
                    <td class="office">{{ $r ? '' : '(for office use / कार्यालय हेतु)' }}</td>
                </tr>
            @endfor
        </tbody>
    </table>

    <div class="decl">I hereby undertake to keep the above particulars up-to-date by notifying to the Head of the Office any addition or alteration. / मैं एतद्द्वारा किसी भी परिवर्धन या परिवर्तन के बारे में कार्यालय प्रमुख को सूचित करके उपर्युक्त विवरणों को अद्यतन रखने का वचन देता/देती हूँ।</div>

    <table class="sign-tbl">
        <tr>
            <td style="width:50%;">
                Place / स्थान: {!! $blank($place, '160pt') !!}<br>
                <span style="display:inline-block; margin-top:6pt;">Date / दिनांक: {!! $blank($dated, '150pt') !!}</span>
            </td>
            <td style="width:50%; text-align:right;">
                <div style="height:34pt;">@if($sigSrc)<img src="{{ $sigSrc }}" class="sig-img">@endif</div>
                Signature of the Government Servant<br>सरकारी कर्मचारी के हस्ताक्षर
            </td>
        </tr>
    </table>

    <div class="notes">
        <div class="def-hd">*Family for this purpose means family as defined in clause (b) of sub-rule of Rule 54 of the CCS (Pension) Rule, 1972 (Mention overleaf for ready reference)</div>
        <div class="def-hd">** Please attach an ID proof of Date of birth (in each case, except your own).</div>
        <div class="note-p"><b>Note 1.</b> The original Form submitted by the Government servant is to be retained. All additions/alterations are to be recorded in this Form under the signature of Head of Office in Col. 7. No new Form will substitute the original Form. However, the retiring Government servant should submit the details of family afresh along with Form 5.
            <span class="note-hi"><b>नोट 1.</b> सरकारी कर्मचारी द्वारा जमा किया गया मूल फार्म अभिलेख में रखा जाना है। इस फार्म में कॉलम 7 में कार्यालय प्रमुख के हस्ताक्षर के तहत सभी परिवर्धन/परिवर्तन दर्ज किए जाने हैं। कोई भी नया फॉर्म मूल फॉर्म को प्रतिस्थापित नहीं करेगा। सेवानिवृत्त होने वाले सरकारी कर्मचारी को फॉर्म 5 के साथ परिवार का विवरण फिर से जमा करना चाहिए।</span></div>
        <div class="note-p"><b>Note 2.</b> The details of spouse, all children and parents (whether eligible for family pension or not) and disabled siblings (brothers and sisters) may be given.
            <span class="note-hi"><b>नोट 2.</b> पति या पत्नी, सभी बच्चों और माता-पिता (चाहे परिवार पेंशन के लिए पात्र हों या नहीं) और विकलांग सहोदर (भाइयों और बहनों) का विवरण दिया जाए।</span></div>
        <div class="note-p"><b>Note 3.</b> The Head of Office shall indicate the date of receipt of communication regarding addition or alteration in the family in the &lsquo;Remarks&rsquo; column. The fact regarding disability or change of marital status of a family member should also be indicated in the &lsquo;Remarks&rsquo; column.
            <span class="note-hi"><b>नोट 3.</b> कार्यालय प्रमुख &lsquo;टिप्पणी&rsquo; कॉलम में परिवार में परिवर्धन या परिवर्तन की सूचना की प्राप्ति की तारीख का उल्लेख करेगा। परिवार के किसी सदस्य की विकलांगता या वैवाहिक स्थिति में बदलाव के तथ्य को भी &lsquo;टिप्पणी&rsquo; कॉलम में दर्शाया जाना चाहिए।</span></div>
        <div class="note-p"><b>Note 4.</b> Wife and husband shall include judicially separated wife and husband.</div>

        <div class="def-hd" style="margin-top:10pt;">*Family for this purpose means family as defined in clause (b) of sub-rule (14) of Rule 54 of the CCS (Pension) Rules, 1972 (as amended from time to time).</div>
        <div class="note-p">As soon as a Government servant enters Government service, he/she shall give details of his/her family in Form 3 to the Head of Office. If the Government servant has no family, he/she shall furnish the details in Form 3 as soon as he acquires a family.</div>
        <div class="note-p">&lsquo;Family&rsquo; in relation to a Government servant means —</div>
        <div class="clause">14(b) Wife in the case of a male Government servant, or husband in the case of a female Government servant.</div>
        <div class="clause">(ia) a judicially separated wife or husband, such separation not being granted on the ground of adultery and the person surviving was not held guilty of committing adultery.</div>
        <div class="clause">(ii) unmarried son who has not attained the age of twenty-five years and unmarried or widowed or divorced daughter, including such son and daughter adopted legally;</div>
        <div class="clause">(iii) dependent parents;</div>
        <div class="clause">(iv) dependent disabled siblings (i.e. brother or sister) of a Government servant.</div>
    </div>

    <pagebreak />

    {{-- ═══════════ HINDI VERSION / हिन्दी प्रति ═══════════ --}}
    <div class="docno">दस्तावेज़-1</div>
    <div class="title">फार्म सं. 3 : परिवार का विवरण</div>
    <div class="sub">[केंद्रीय सिविल सेवा (पेंशन) नियमावली, 1972 का नियम 54 (12) देखें]</div>

    <div class="hdr"><span class="lbl">सरकारी कर्मचारी का नाम:</span> {!! $blank($name, '280pt') !!}</div>
    <div class="hdr"><span class="lbl">पद नाम:</span> {!! $blank($desig, '300pt') !!}</div>
    <div class="hdr"><span class="lbl">जन्म तिथि:</span> {!! $blank($dob, '200pt') !!}</div>
    <div class="hdr"><span class="lbl">दिनांक …………… को मेरे परिवार* के सदस्यों का विवरण:</span> {!! $blank($ason, '150pt') !!}</div>

    <table class="fm">
        <thead>
            <tr>
                <th class="rn">क्र.सं.<br><span class="cno">(1)</span></th>
                <th>परिवार के सदस्य का नाम*<br><span class="cno">(2)</span></th>
                <th style="width:12%;">जन्म तिथि**<br><span class="cno">(3)</span></th>
                <th style="width:15%;">अधिकारी के साथ संबंध<br><span class="cno">(4)</span></th>
                <th style="width:12%;">वैवाहिक स्थिति<br><span class="cno">(5)</span></th>
                <th style="width:14%;">टिप्पणी<br><span class="cno">(6)</span></th>
                <th style="width:14%;">कार्यालय प्रमुख के दिनांकित हस्ताक्षर<br><span class="cno">(7)</span></th>
            </tr>
        </thead>
        <tbody>
            @for($i = 0; $i < $minRows; $i++)
                @php $r = $rows[$i] ?? null; @endphp
                <tr class="{{ $r ? '' : 'rowspace' }}">
                    <td class="rn">{{ $i + 1 }}.</td>
                    <td>{{ $r['name'] ?? '' }}</td>
                    <td>{{ $r ? $fmt($r['dob'] ?? '') : '' }}</td>
                    <td>{{ $r['relationship'] ?? '' }}</td>
                    <td>{{ $r['marital_status'] ?? '' }}</td>
                    <td>{{ $r['remarks'] ?? '' }}</td>
                    <td class="office">{{ $r ? '' : '(कार्यालय हेतु)' }}</td>
                </tr>
            @endfor
        </tbody>
    </table>

    <div class="decl">मैं एतद्द्वारा किसी भी परिवर्धन या परिवर्तन के बारे में कार्यालय प्रमुख को सूचित करके उपर्युक्त विवरणों को अद्यतन रखने का वचन देता/देती हूँ।</div>

    <table class="sign-tbl">
        <tr>
            <td style="width:50%;">
                स्थान: {!! $blank($place, '160pt') !!}<br>
                <span style="display:inline-block; margin-top:6pt;">दिनांक: {!! $blank($dated, '150pt') !!}</span>
            </td>
            <td style="width:50%; text-align:right;">
                <div style="height:34pt;">@if($sigSrc)<img src="{{ $sigSrc }}" class="sig-img">@endif</div>
                सरकारी कर्मचारी के हस्ताक्षर
            </td>
        </tr>
    </table>

    <div class="notes">
        <div class="def-hd">* इस प्रयोजनार्थ परिवार का अर्थ केंद्रीय सिविल सेवा (पेंशन) नियमावली, 1972 के नियम 54 के उप-नियम (ख) में परिभाषित परिवार से है। (तैयार संदर्भ हेतु पीछे देखें)</div>
        <div class="def-hd">** जन्म तिथि का पहचान प्रमाण संलग्न करें (अपने को छोड़कर प्रत्येक मामले में)।</div>
        <div class="note-p"><b>नोट 1.</b> सरकारी कर्मचारी द्वारा जमा किया गया मूल फार्म अभिलेख में रखा जाना है। इस फार्म में कॉलम 7 में कार्यालय प्रमुख के हस्ताक्षर के तहत सभी परिवर्धन/परिवर्तन दर्ज किए जाने हैं। कोई भी नया फॉर्म मूल फॉर्म को प्रतिस्थापित नहीं करेगा। सेवानिवृत्त होने वाले सरकारी कर्मचारी को फॉर्म 5 के साथ परिवार का विवरण फिर से जमा करना चाहिए।</div>
        <div class="note-p"><b>नोट 2.</b> पति या पत्नी, सभी बच्चों और माता-पिता (चाहे परिवार पेंशन के लिए पात्र हों या नहीं) और विकलांग सहोदर (भाइयों और बहनों) का विवरण दिया जाए।</div>
        <div class="note-p"><b>नोट 3.</b> कार्यालय प्रमुख &lsquo;टिप्पणी&rsquo; कॉलम में परिवार में परिवर्धन या परिवर्तन की सूचना की प्राप्ति की तारीख का उल्लेख करेगा। परिवार के किसी सदस्य की विकलांगता या वैवाहिक स्थिति में बदलाव के तथ्य को भी &lsquo;टिप्पणी&rsquo; कॉलम में दर्शाया जाना चाहिए।</div>
        <div class="note-p"><b>नोट 4.</b> पति और पत्नी में न्यायिक रूप से अलग हो चुके पति और पत्नी शामिल होंगे।</div>

        <div class="def-hd" style="margin-top:10pt;">*इसके प्रयोजनार्थ परिवार का अर्थ केंद्रीय सिविल सेवा (पेंशन) नियमावली, 1972 (समय-समय पर संशोधित) के नियम 54 के उप-नियम (14) के खंड (ख) में परिभाषित परिवार है।</div>
        <div class="note-p">जैसे ही कोई सरकारी कर्मचारी सरकारी सेवा में भर्ती होता है, वह अपने परिवार का विवरण फॉर्म 3 में कार्यालय प्रमुख को देगा/देगी। यदि सरकारी कर्मचारी का कोई परिवार नहीं है, तो उसे परिवार के अस्तित्व में आते ही फॉर्म 3 में विवरण प्रस्तुत करना होगा।</div>
        <div class="note-p">सरकारी सेवक के संबंध में &lsquo;परिवार&rsquo; का अर्थ है—</div>
        <div class="clause">14 (ख) पुरुष सरकारी कर्मचारी के मामले में पत्नी, या महिला सरकारी कर्मचारी के मामले में पति।</div>
        <div class="clause">(iक) न्यायिक रूप से अलग हो चुके पति या पत्नी, ऐसा अलगाव व्यभिचार के आधार पर स्वीकृत न किया गया हो और उत्तरजीवी व्यक्ति को व्यभिचार का दोषी न ठहराया गया हो।</div>
        <div class="clause">(ii) अविवाहित पुत्र जिसने पच्चीस वर्ष की आयु प्राप्त नहीं की है और अविवाहित या विधवा या तलाकशुदा पुत्री, जिसमें कानूनी रूप से गोद लिया गया पुत्र एवं पुत्री भी शामिल है;</div>
        <div class="clause">(iii) आश्रित माता-पिता;</div>
        <div class="clause">(iv) सरकारी कर्मचारी के आश्रित विकलांग सहोदर (अर्थात भाई या बहन)।</div>
    </div>

</body>
</html>
