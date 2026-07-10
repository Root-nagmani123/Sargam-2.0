@php
    $fmt = function ($d) {
        if (! $d) return '';
        try { return \Carbon\Carbon::parse($d)->format('d-m-Y'); } catch (\Throwable $e) { return $d; }
    };
    $g = fn ($k) => trim((string) ($data[$k] ?? ''));
    $name    = $g('officer_name');
    $svc     = $g('service');
    $srule   = $g('service_rule');
    $sname   = $g('surety_name');
    $saddr   = $g('surety_address');
    $socc    = $g('surety_occupation');
    $elig    = $g('surety_eligibility');
    $pwn = $g('prob_witness_name');   $pwa = $g('prob_witness_address');   $pwo = $g('prob_witness_occupation');
    $swn = $g('surety_witness_name'); $swa = $g('surety_witness_address'); $swo = $g('surety_witness_occupation');
    $dated = $fmt($data['declaration_date'] ?? '');
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
    .subtitle { text-align: center; font-weight: bold; font-size: 11px; margin-top: 3px; }
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
    <div class="docno">Document-7-A</div>
    <div class="title">This bond to be typed and signed on India Non-Judicial Paper (Rs. 100/-) by Probationers of<br>ALL INDIA SERVICES (IAS / IPS / IFoS)</div>
    <div class="subtitle">Format to be used by IAS / IPS / IFoS Probationers</div>

    <div class="to">To,<br>&emsp;&emsp;The President of India,</div>

    <div class="body">
        Whereas I, {!! $blank($name, '320px') !!} (full name &amp; address of the Probationer), a probationer in the
        {!! $blank($svc, '90px') !!} (Indian Administrative Service / Indian Police Service / Indian Forest Service) (hereinafter
        referred to as &lsquo;the Probationer&rsquo;), being entitled — subject to compliance with the {!! $blank($srule, '190px') !!}
        [Indian Administrative Service (Probation) Rules, 1954 / Indian Police Service (Probation) Rules, 1954 / Indian Forest
        Service (Probation) Rules, 1968] — to receive from the President of India (hereinafter referred to as the Central
        Government) or from the Government of the State to which I may be posted, pay and allowances during the period in which I
        am under training:
    </div>

    <div class="body">
        Now, we, the Probationer, and {!! $blank($sname, '200px') !!} residing at {!! $blank($saddr, '260px') !!}, working as
        {!! $blank($socc, '160px') !!} (hereinafter referred to as &lsquo;the Surety&rsquo;), jointly and severally, do hereby, in
        pursuance of the said rules, promise and agree that in the event of the failure of the Probationer to complete probation
        to the satisfaction of the Central Government, to refund to the Central Government on demand any moneys paid to him/her,
        including the pay and travelling expenses to join appointment.
    </div>

    <div class="body">
        The Surety hereby agrees that his/her liability hereunder shall not be affected by the Central Government extending the
        period of probation, or giving the Probationer an extension of time for payment of, or compounding, the amount payable
        hereunder.
    </div>

    <div class="lines">
        Dated {!! $blank($dated, '150px') !!}.
        <div class="sign">Signature of the Probationer:
            @if(!empty($sigs[0]))<img src="{{ $sigs[0] }}" class="sig-img">@else {!! $blank('', '200px') !!} @endif
        </div>
        <div>Signed by the Probationer in the presence of — Name of Witness: {!! $blank($pwn, '200px') !!}</div>
        <div>Address: {!! $blank($pwa, '300px') !!} &nbsp; Occupation: {!! $blank($pwo, '160px') !!}</div>

        <div class="sign">Signature of the Surety:
            @if(!empty($sigs[1]))<img src="{{ $sigs[1] }}" class="sig-img">@else {!! $blank('', '200px') !!} @endif
        </div>
        <div>Signed by the Surety in the presence of — Name of Witness: {!! $blank($swn, '200px') !!}</div>
        <div>Address: {!! $blank($swa, '300px') !!} &nbsp; Occupation: {!! $blank($swo, '160px') !!}</div>
    </div>

    <div class="body" style="text-indent:0; margin-top:14px;">
        I, the Surety whose signature is appended to the above agreement, do hereby declare that —
        <div class="elig">{!! $box($isPerm) !!} (a) I am in the permanent service of the Government of {!! $blank('', '180px') !!}; <i>or</i></div>
        <div class="elig">{!! $box($isRes) !!} (b) I am ordinarily resident in India and possess means which will enable me to repay to the Central Government the sums of money referred to, in the event of my being called upon to do so in accordance with the terms of the agreement.</div>
        <div style="margin-top:6px;"><i>(Strike out whichever is not applicable.)</i></div>
    </div>

    <pagebreak />

    {{-- ═══════════ HINDI (page 3–4) ═══════════ --}}
    <div class="docno">दस्तावेज़-7-ए</div>
    <div class="title">अखिल भारतीय सेवाओं (आई.ए.एस. / आई.पी.एस. / आई.एफ़.ओ.एस.) के परिवीक्षाधीन अधिकारियों द्वारा भरा जाना है</div>

    <div class="to">सेवा में,<br>&emsp;&emsp;भारत के राष्ट्रपति,</div>

    <div class="body">
        मैं, {!! $blank($hi['pname'] ?? '', '300px') !!} (परिवीक्षाधीन का नाम और पता), {!! $blank($hi['svc'] ?? '', '90px') !!} सेवा में परिवीक्षाधीन व्यक्ति
        (जिसे इसमें आगे &lsquo;परिवीक्षाधीन&rsquo; कहा गया है), राष्ट्रपति (जिन्हें इसमें आगे &lsquo;केन्द्र सरकार&rsquo; कहा गया है) अथवा जिस
        राज्य में मुझे नियुक्त किया जाए, उस राज्य सरकार से प्रशिक्षण की अवधि के लिए (भारतीय प्रशासनिक सेवा (परिवीक्षा) नियमावली, 1954
        अथवा भारतीय पुलिस सेवा (परिवीक्षा) नियमावली, 1954 अथवा भारतीय वन सेवा (परिवीक्षा) नियमावली, 1968 के अध्यधीन) वेतन और भत्ते
        पाने का हकदार हूँ।
    </div>

    <div class="body">
        अब, परिवीक्षाधीन और मैं, {!! $blank($hi['sname'] ?? '', '200px') !!} निवासी {!! $blank($hi['saddr'] ?? '', '240px') !!},
        {!! $blank($hi['socc'] ?? '', '150px') !!} (प्रतिभू का नाम और पता, जिसे इसमें आगे &lsquo;प्रतिभू&rsquo; कहा गया है), उक्त नियमों के अनुसरण में
        संयुक्त रूप से और पृथक-पृथक एतद्द्वारा वचन देते हैं और करार करते हैं कि यदि परिवीक्षाधीन व्यक्ति केन्द्र सरकार की संतुष्टि के अनुसार
        परिवीक्षा की अवधि पूरी नहीं कर पाता है, तो वेतन और नियुक्ति पर जाने के लिए यात्रा व्यय सहित परिवीक्षाधीन व्यक्ति को भुगतान की गई
        किसी भी राशि की मांग किए जाने पर हम उसका भुगतान करेंगे।
    </div>

    <div class="body">
        प्रतिभू एतद्द्वारा यह करार करता है कि यहां नीचे दी गई उसकी देयता केन्द्र सरकार द्वारा उसकी परिवीक्षा की अवधि बढ़ाए जाने से अथवा
        परिवीक्षाधीन व्यक्ति को भुगतान के लिए अधिक समय देने या यहां नीचे दी गई देय राशि को माफ करने से किसी प्रकार प्रभावित नहीं होगी।
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
