@php
    $fmt = function ($d) {
        if (! $d) return '';
        try { return \Carbon\Carbon::parse($d)->format('d-m-Y'); } catch (\Throwable $e) { return $d; }
    };
    $g = fn ($k) => trim((string) ($data[$k] ?? ''));
    $name    = $g('officer_name');
    $exyear  = $g('exam_year');
    $svc     = $g('service');
    $srule   = $g('service_rule');
    $sname   = $g('surety_name');
    $saddr   = $g('surety_address');
    $socc    = $g('surety_occupation');
    $elig    = $g('surety_eligibility');
    $pwn = $g('prob_witness_name');   $pwa = $g('prob_witness_address');   $pwo = $g('prob_witness_occupation');
    $swn = $g('surety_witness_name'); $swa = $g('surety_witness_address'); $swo = $g('surety_witness_occupation');
    $dated = $fmt($data['declaration_date'] ?? '');
    $dAt   = $g('bond_dated_at');   $dDay = $g('bond_dated_day');   $dMon = $g('bond_dated_month');
    $sigs  = $data['_signature_src'] ?? [];
    $hi    = $data['_hi'] ?? [];   // candidate-typed Hindi values (blank if none)

    $blank = function ($v, $w = '150px') {
        $val = ($v !== '' && $v !== null) ? e($v) : '&nbsp;';
        return '<span style="display:inline-block; min-width:'.$w.'; border-bottom:1px solid #000; text-align:center; font-weight:bold; padding:0 4px;">'.$val.'</span>';
    };
    $isPerm = $elig === 'In the permanent service of Government';
    $isRes  = $elig === 'Ordinarily resident in India';
    $box = fn ($on) => $on ? '&#9745;' : '&#9744;';
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #000; line-height: 1.9; }
    .docno { text-align: right; font-weight: bold; font-size: 11px; }
    .title { text-align: center; font-weight: bold; font-size: 12px; text-decoration: underline; line-height: 1.5; margin-top: 4px; }
    .to { margin-top: 14px; }
    .body { text-align: justify; margin-top: 12px; text-indent: 28px; }
    .lines { margin-top: 18px; }
    .sign { margin-top: 12px; font-weight: bold; }
    .sig-img { max-height: 34px; }
    .elig { margin-top: 10px; }
</style>
</head>
<body>

    {{-- ═══════════ ENGLISH (page 1–2) ═══════════ --}}
    <div class="docno">Document-7-B</div>
    <div class="title">This bond to be typed and signed on India Non-Judicial Paper (Rs. 100/-) by Probationers of<br>CENTRAL CIVIL SERVICES – Group-A (Other than All India Services)</div>

    <div class="to">To,<br>&emsp;&emsp;The President of India,</div>

    <div class="body">
        Whereas I, {!! $blank($name, '320px') !!} (full name &amp; address of the Probationer), a candidate recommended by the
        Union Public Service Commission, Class-I Service, on the results of the Civil Service Examination, 20{!! $blank($exyear, '70px') !!}
        in the {!! $blank($svc, '200px') !!} (Name of Service) (hereinafter referred to as &lsquo;the Probationer&rsquo;), being entitled,
        subject to compliance with the recruitment rules of the {!! $blank($srule, '200px') !!}, to receive from the President of India
        (hereinafter referred to as the Central Government) pay and allowances during the period in which I am under training.
    </div>

    <div class="body">
        And whereas the Probationer is required to furnish Surety as herein contained. And whereas, at the request of the
        Probationer, I, {!! $blank($sname, '220px') !!} (full name of the Surety), residing at {!! $blank($saddr, '280px') !!}
        (residence address), working as {!! $blank($socc, '180px') !!} (occupation) (hereinafter referred to as the Surety), have
        agreed to stand as Surety for the Probationer as herein contained.
    </div>

    <div class="body">
        Now, we, the Probationer and I, the Surety, jointly and severally, do hereby, in pursuance of the said rules, promise and
        agree that in the event of the failure of the Probationer to complete his/her training to the satisfaction of the Central
        Government, to refund to the Central Government on demand without demur any moneys paid to him/her, including pay and
        travelling expenses. And it is agreed that the decision of the Central Government as to the failure of the Probationer as
        aforesaid and the amount payable by the Probationer and the Surety shall be final and binding on the Probationer and the
        Surety. The Surety hereby agrees that his/her liability hereunder shall not be affected on account of the Central Government
        giving the Probationer extension of time for payment of, or compounding, the amount payable hereunder, or on account of any
        indulgence shown to the Probationer.
    </div>

    <div class="lines">
        Dated {!! $blank($dAt, '130px') !!} this {!! $blank($dDay, '110px') !!} day of {!! $blank($dMon, '150px') !!}
        <div class="sign">Signature of the Probationer:
            @if(!empty($sigs[0]))<img src="{{ $sigs[0] }}" class="sig-img">@else {!! $blank('', '200px') !!} @endif
        </div>
        <div>Signed by the Probationer in the presence of — Name of Witness: {!! $blank($pwn, '200px') !!}</div>
        <div>Address: {!! $blank($pwa, '300px') !!} &nbsp; Occupation: {!! $blank($pwo, '160px') !!}</div>
    </div>

    <div class="body" style="text-indent:0; margin-top:14px;">
        I, the Surety whose signature is appended to the above agreement, do hereby declare that —
        <div class="elig">{!! $box($isPerm) !!} (a) I am in the permanent service of the Government of {!! $blank('', '180px') !!}; <i>or</i></div>
        <div class="elig">{!! $box($isRes) !!} (b) I am ordinarily resident in India and possess means which will enable me to repay to the Central Government the sums of money referred to, in the event of my being called upon to do so in accordance with the terms of the agreement.</div>
        <div style="margin-top:6px;"><i>(Strike out whichever is not applicable.)</i></div>
    </div>

    <div class="lines">
        <div class="sign">Signature of the Surety:
            @if(!empty($sigs[1]))<img src="{{ $sigs[1] }}" class="sig-img">@else {!! $blank('', '200px') !!} @endif
        </div>
        <div>Signed by the Surety in the presence of — Name of Witness: {!! $blank($swn, '200px') !!}</div>
        <div>Address: {!! $blank($swa, '300px') !!} &nbsp; Occupation: {!! $blank($swo, '160px') !!}</div>
    </div>

    <pagebreak />

    {{-- ═══════════ HINDI (page 3–4) ═══════════ --}}
    <div class="docno">दस्तावेज़-7-बी</div>
    <div class="title">केन्द्रीय सिविल सेवा (अखिल भारतीय सेवाओं के अलावा)</div>

    <div class="to">सेवा में,<br>&emsp;&emsp;भारत के राष्ट्रपति,</div>

    <div class="body">
        जबकि मैं, {!! $blank($hi['pname'] ?? '', '300px') !!} (परिवीक्षाधीन का पूरा नाम और पता), संघ लोक सेवा आयोग द्वारा सिविल सेवा परीक्षा,
        20{!! $blank($hi['exyear'] ?? '', '60px') !!} के परिणाम के आधार पर की गई सिफारिश के अनुसार {!! $blank($hi['svc'] ?? '', '180px') !!} (सेवा का नाम) में
        नियुक्त उम्मीदवार, जिसे इसमें आगे &lsquo;परिवीक्षाधीन&rsquo; कहा गया है, भर्ती नियमों के अनुपालन के अधीन {!! $blank($hi['srule'] ?? '', '170px') !!}
        के प्रशिक्षण के दौरान, भारत के राष्ट्रपति (जिन्हें इसमें आगे &lsquo;केन्द्र सरकार&rsquo; कहा गया है) से वेतन और भत्ते पाने का हकदार हूँ।
    </div>

    <div class="body">
        और जबकि परिवीक्षाधीन को इसमें उल्लिखित प्रतिभूति देनी होती है। और जबकि परिवीक्षाधीन के अनुरोध पर, मैं, {!! $blank($hi['sname'] ?? '', '200px') !!}
        (प्रतिभू का पूरा नाम), निवासी {!! $blank($hi['saddr'] ?? '', '250px') !!} (निवास स्थान का पता), {!! $blank($hi['socc'] ?? '', '160px') !!} (व्यवसाय) के रूप में
        कार्यरत (जिसे इसमें आगे &lsquo;प्रतिभू&rsquo; कहा गया है), इसमें उल्लिखित परिवीक्षाधीन का प्रतिभू बनने के लिए सहमत हूँ।
    </div>

    <div class="body">
        अब हम, परिवीक्षाधीन तथा मैं प्रतिभू, संयुक्त रूप से और पृथक-पृथक रूप से एतद्द्वारा वचन देते हैं और करार करते हैं कि यदि परिवीक्षाधीन
        केन्द्र सरकार की संतुष्टि के अनुसार अपना प्रशिक्षण पूरा नहीं कर पाता है, तो वेतन और यात्रा व्यय सहित परिवीक्षाधीन को भुगतान की गई किसी
        भी राशि की मांग किए जाने पर हम उसे अविलम्ब लौटा देंगे। यह भी करार किया जाता है कि उपर्युक्त अनुसार परिवीक्षाधीन के असफल होने के संबंध
        में तथा परिवीक्षाधीन और प्रतिभू द्वारा देय राशि के संबंध में केन्द्र सरकार का निर्णय परिवीक्षाधीन और प्रतिभू के लिए अंतिम और बाध्यकारी होगा।
        प्रतिभू एतद्द्वारा यह भी सहमत होता है कि उसकी देयता, केन्द्र सरकार द्वारा परिवीक्षाधीन को भुगतान हेतु अधिक समय देने या देय राशि को माफ
        करने अथवा परिवीक्षाधीन के प्रति किसी उदारता के कारण किसी प्रकार प्रभावित नहीं होगी।
    </div>

    <div class="lines">
        दिनांक {!! $blank($hi['ddate'] ?? '', '150px') !!}
        <div class="sign">परिवीक्षाधीन के हस्ताक्षर: {!! $blank('', '200px') !!}</div>
        <div>साक्षी की उपस्थिति में परिवीक्षाधीन द्वारा हस्ताक्षरित — साक्षी का नाम: {!! $blank($hi['pwname'] ?? '', '200px') !!}</div>
        <div>पता: {!! $blank($hi['pwaddr'] ?? '', '280px') !!} &nbsp; व्यवसाय: {!! $blank($hi['pwocc'] ?? '', '150px') !!}</div>

        <div class="sign">प्रतिभू के हस्ताक्षर: {!! $blank('', '200px') !!}</div>
        <div>साक्षी की उपस्थिति में प्रतिभू द्वारा हस्ताक्षरित — साक्षी का नाम: {!! $blank($hi['swname'] ?? '', '200px') !!}</div>
        <div>पता: {!! $blank($hi['swaddr'] ?? '', '280px') !!} &nbsp; व्यवसाय: {!! $blank($hi['swocc'] ?? '', '150px') !!}</div>
    </div>

    <div class="body" style="text-indent:0; margin-top:14px;">
        मैं, प्रतिभू, जिसके हस्ताक्षर उपर्युक्त करार में दिए गए हैं, एतद्द्वारा घोषणा करता/करती हूँ कि —
        <div class="elig">{!! $box($isPerm) !!} (क) मैं {!! $blank('', '170px') !!} सरकार की स्थायी सेवा में हूँ; <b>अथवा</b></div>
        <div class="elig">{!! $box($isRes) !!} (ख) मैं साधारणतया भारत का निवासी हूँ और मेरे पास ऐसे साधन हैं जिनसे करार के निबंधनों के अनुसार राशि की मांग किए जाने पर मैं केन्द्र सरकार को वह राशि चुका सकता/सकती हूँ।</div>
        <div style="margin-top:6px;">(जो लागू न हो उसे काट दें।)</div>
    </div>

</body>
</html>
