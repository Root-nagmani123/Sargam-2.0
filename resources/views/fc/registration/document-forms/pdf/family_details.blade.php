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
    $place   = $g('place');
    $dated   = $fmt($data['declaration_date'] ?? '');
    $rows    = $data['_tables']['members'] ?? [];
    $sigSrc  = $data['_signature_src'][0] ?? null;
    $minRows = max(10, count($rows));   // fixed 10-row grid like the sample
    $blank = function ($v, $w = '160px') {
        $val = ($v !== '' && $v !== null) ? e($v) : '&nbsp;';
        return '<span style="display:inline-block; min-width:'.$w.'; border-bottom:1px solid #000; font-weight:bold; padding:0 4px;">'.$val.'</span>';
    };
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #000; line-height: 1.6; }
    .docno { text-align: right; font-weight: bold; font-size: 11px; }
    .title { text-align: center; font-weight: bold; font-size: 13px; text-decoration: underline; margin-top: 4px; }
    .sub { text-align: center; font-weight: bold; font-size: 9.5px; margin-top: 2px; }
    .title-hi { text-align: center; font-weight: bold; font-size: 12px; margin-top: 3px; }
    .hdr { margin-top: 9px; }
    .hdr .lbl { font-weight: bold; }
    table.fm { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table.fm th, table.fm td { border: 0.8px solid #000; padding: 4px 5px; font-size: 8.2px; vertical-align: middle; }
    table.fm th { text-align: center; font-weight: bold; }
    .cno { font-weight: normal; }
    .rn { width: 24px; text-align: center; }
    .rowspace td { height: 24px; }
    .office { text-align: center; color: #777; font-size: 7.6px; }
    .decl { margin-top: 14px; text-align: justify; }
    .sign-tbl { width: 100%; margin-top: 18px; }
    .sign-tbl td { vertical-align: bottom; font-size: 10px; }
    .sig-img { max-height: 32px; }
    .notes { margin-top: 16px; border-top: 1px solid #000; padding-top: 8px; font-size: 8.6px; line-height: 1.5; }
    .note-p { margin-top: 6px; text-align: justify; }
    .note-hi { display: block; color: #000; }
    .def-hd { font-weight: bold; margin-top: 10px; text-align: justify; }
    .clause { margin: 4px 0 0 20px; text-align: justify; }
</style>
</head>
<body>

    <div class="docno">Document-1 / दस्तावेज़-1</div>
    <div class="title">Form No. 3: Details of Family</div>
    <div class="sub">[See Rule 54(12) of CCS (Pension) Rules, 1972]</div>
    <div class="title-hi">फॉर्म सं. 3 : परिवार का विवरण</div>

    <div class="hdr"><span class="lbl">Name of the Government Servant / सरकारी कर्मचारी का नाम:</span> {!! $blank($name, '280px') !!}</div>
    <div class="hdr"><span class="lbl">Designation / पद नाम:</span> {!! $blank($desig, '300px') !!}</div>
    <div class="hdr"><span class="lbl">Date of Birth / जन्म तिथि:</span> {!! $blank($dob, '200px') !!}</div>
    <div class="hdr"><span class="lbl">Details of the members of my family* as on / परिवार के सदस्यों का विवरण, तदनांक:</span> {!! $blank($ason, '150px') !!}</div>

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
                Place / स्थान: {!! $blank($place, '160px') !!}<br>
                <span style="display:inline-block; margin-top:6px;">Date / दिनांक: {!! $blank($dated, '150px') !!}</span>
            </td>
            <td style="width:50%; text-align:right;">
                <div style="height:34px;">@if($sigSrc)<img src="{{ $sigSrc }}" class="sig-img">@endif</div>
                Signature of the Government Servant<br>सरकारी कर्मचारी के हस्ताक्षर
            </td>
        </tr>
    </table>

    <div class="notes">
        <div class="note-p"><b>Note 1.</b> The original Form submitted by the Government servant is to be retained. All additions/alterations are to be recorded in this Form under the signature of Head of Office in Col. 7. No new Form will substitute the original Form. However, the retiring Government servant should submit the details of family afresh along with Form 5.
            <span class="note-hi"><b>नोट 1.</b> सरकारी कर्मचारी द्वारा जमा किया गया मूल फार्म अभिलेख में रखा जाना है। इस फार्म में कॉलम 7 में कार्यालय प्रमुख के हस्ताक्षर के तहत सभी परिवर्धन/परिवर्तन दर्ज किए जाने हैं। कोई भी नया फॉर्म मूल फॉर्म को प्रतिस्थापित नहीं करेगा। सेवानिवृत्त होने वाले सरकारी कर्मचारी को फॉर्म 5 के साथ परिवार का विवरण फिर से जमा करना चाहिए।</span></div>
        <div class="note-p"><b>Note 2.</b> The details of spouse, all children and parents (whether eligible for family pension or not) and disabled siblings (brothers and sisters) may be given.
            <span class="note-hi"><b>नोट 2.</b> पति या पत्नी, सभी बच्चों और माता-पिता (चाहे परिवार पेंशन के लिए पात्र हों या नहीं) और विकलांग सहोदर (भाइयों और बहनों) का विवरण दिया जाए।</span></div>
        <div class="note-p"><b>Note 3.</b> The Head of Office shall indicate the date of receipt of communication regarding addition or alteration in the family in the &lsquo;Remarks&rsquo; column. The fact regarding disability or change of marital status of a family member should also be indicated in the &lsquo;Remarks&rsquo; column.
            <span class="note-hi"><b>नोट 3.</b> कार्यालय प्रमुख &lsquo;टिप्पणी&rsquo; कॉलम में परिवार में परिवर्धन या परिवर्तन की सूचना की प्राप्ति की तारीख का उल्लेख करेगा। परिवार के किसी सदस्य की विकलांगता या वैवाहिक स्थिति में बदलाव के तथ्य को भी &lsquo;टिप्पणी&rsquo; कॉलम में दर्शाया जाना चाहिए।</span></div>
        <div class="note-p"><b>Note 4.</b> Wife and husband shall include judicially separated wife and husband.
            <span class="note-hi"><b>नोट 4.</b> पति और पत्नी में न्यायिक रूप से अलग हो चुके पति और पत्नी शामिल होंगे।</span></div>

        <div class="def-hd">*Family for this purpose means family as defined in clause (b) of sub-rule (14) of Rule 54 of the CCS (Pension) Rules, 1972 (as amended from time to time). &lsquo;Family&rsquo; in relation to a Government servant means —
            <span class="note-hi">* इस प्रयोजनार्थ परिवार का अर्थ केन्द्रीय सिविल सेवा (पेंशन) नियमावली, 1972 (समय-समय पर संशोधित) के नियम 54 के उप-नियम (14) के खंड (ख) में परिभाषित परिवार से है। सरकारी सेवक के संबंध में &lsquo;परिवार&rsquo; का अर्थ है —</span></div>
        <div class="clause">14(b) wife in the case of a male Government servant, or husband in the case of a female Government servant; / 14(ख) पुरुष सरकारी कर्मचारी के मामले में पत्नी, या महिला सरकारी कर्मचारी के मामले में पति;</div>
        <div class="clause">(ia) a judicially separated wife or husband, such separation not being granted on the ground of adultery and the person surviving not held guilty of committing adultery; / (iक) न्यायिक रूप से अलग हो चुके पति या पत्नी (जहाँ अलगाव व्यभिचार के आधार पर स्वीकृत न हुआ हो और उत्तरजीवी व्यभिचार का दोषी न ठहराया गया हो);</div>
        <div class="clause">(ii) unmarried son who has not attained the age of twenty-five years, and unmarried or widowed or divorced daughter, including such son and daughter adopted legally; / (ii) अविवाहित पुत्र जिसने पच्चीस वर्ष की आयु प्राप्त नहीं की है, तथा अविवाहित या विधवा या तलाकशुदा पुत्री (कानूनी रूप से गोद लिए गए पुत्र-पुत्री सहित);</div>
        <div class="clause">(iii) dependent parents; / (iii) आश्रित माता-पिता;</div>
        <div class="clause">(iv) dependent disabled siblings (i.e. brother or sister) of a Government servant. / (iv) सरकारी कर्मचारी के आश्रित विकलांग सहोदर (अर्थात भाई या बहन)।</div>
    </div>

</body>
</html>
