@php
    $g = fn ($k) => trim((string) ($data[$k] ?? ''));
    $name    = $g('officer_name');
    $exyear  = $g('exam_year');
    $svc     = $g('service');
    $srule   = $g('service_rule');
    $sname   = $g('surety_name');
    $saddr   = $g('surety_address');
    $socc    = $g('surety_occupation');
    $sdname  = $g('surety_decl_name');
    $pwn = $g('prob_witness_name');   $pwa = $g('prob_witness_address');   $pwo = $g('prob_witness_occupation');
    $swn = $g('surety_witness_name'); $swa = $g('surety_witness_address'); $swo = $g('surety_witness_occupation');
    $swn2 = $g('surety_witness_name2'); $swa2 = $g('surety_witness_address2'); $swo2 = $g('surety_witness_occupation2');
    $dAt   = $g('bond_dated_at');   $dDay = $g('bond_dated_day');   $dMon = $g('bond_dated_month');
    $sigs  = $data['_signature_src'] ?? [];
    $hi    = $data['_hi'] ?? [];   // candidate-typed Hindi values (blank if none)

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
    @page { margin: 9mm 13mm 9mm 13mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; color: #000; line-height: 1.36; }
    .docno { text-align: right; font-weight: bold; font-size: 11pt; }
    .title { text-align: center; font-weight: bold; font-size: 12pt; text-decoration: underline; line-height: 1.5; margin-top: 4pt; }
    .to { margin-top: 7pt; }
    .body { text-align: justify; margin-top: 5pt; text-indent: 28pt; }
    .lines { margin-top: 7pt; }
    .sign { margin-top: 6pt; }
    .sig-img { max-height: 34pt; }
    .opt { margin-top: 5pt; }
</style>
</head>
<body>

    {{-- ═══════════ HINDI (page 1–2) ═══════════ --}}
    <div class="docno">Document-7-B</div>
    <div class="title">केन्द्रीय सिविल सेवा (अखिल भारतीय सेवाओं के अलावा)</div>

    <div class="to">सेवा में<br>&emsp;&emsp;भारत के राष्ट्रपति,</div>

    <div class="body">
        जबकि मैं, (परिवीक्षाधीन अधिकारी का पूरा नाम और पता डाला जाना चाहिए) {!! $blank($hi['pname'] ?? '', '260pt') !!} संघ लोक सेवा श्रेणी-I
        सेवा द्वारा सिविल सेवा परीक्षा, 20{!! $blank($hi['exyear'] ?? '', '55pt') !!} (परीक्षा का नाम) के परिणाम के आधार पर की गई सिफारिश के
        अनुसार नियुक्त उम्मीदवार, जिसे इसमें आगे &lsquo;परिवीक्षाधीन&rsquo; कहा गया है, भर्ती नियमों के अनुपालन के अधीन {!! $blank($hi['svc'] ?? '', '160pt') !!}
        (सेवा का नाम डालें) प्रशिक्षण के दौरान, भारत के राष्ट्रपति (जिन्हें इसमें आगे &lsquo;केन्द्र सरकार&rsquo; कहा गया है) से वेतन और भत्ते पाने का हकदार हूं।
    </div>

    <div class="body">
        और जबकि परिवीक्षाधीन को इसमें उल्लिखित जमानत देनी होती है। और जबकि परिवीक्षाधीन श्री/सुश्री {!! $blank($hi['pname'] ?? '', '160pt') !!}
        (परिवीक्षाधीन का नाम डालें) के अनुरोध पर, मैं, (प्रतिभू का पूरा नाम डाला जाना चाहिए) {!! $blank($hi['sname'] ?? '', '180pt') !!} निवासी
        {!! $blank($hi['saddr'] ?? '', '220pt') !!} (निवास स्थान का पता) के रूप में कार्यरत {!! $blank($hi['socc'] ?? '', '150pt') !!} (संगठन का नाम डालें)
        (जिसे इसमें आगे &lsquo;प्रतिभू&rsquo; कहा गया है) इसमें उल्लिखित परिवीक्षाधीन का प्रतिभू बनने के लिए सहमत हूं।
    </div>

    <div class="body">
        अब हम, परिवीक्षाधीन श्री/सुश्री/डॉ. {!! $blank($hi['pname'] ?? '', '150pt') !!} (परिवीक्षाधीन का नाम डालें) और मैं {!! $blank($hi['sname'] ?? '', '150pt') !!}
        (प्रतिभू का नाम डालें) संयुक्त रूप से और पृथक-पृथक एतद्वारा वचन देता हूं और करार करता हूं कि यदि परिवीक्षाधीन केन्द्र सरकार की संतुष्टि के
        अनुसार अकादमी में अपना प्रशिक्षण पूरा नहीं कर पाता तो वेतन और यात्रा व्यय सहित प्रशिक्षणार्थी को भुगतान की गई किसी भी राशि की मांग किए जाने पर
        हम उसे अविलम्ब लौटा देंगे। हम यह भी करार करते हैं कि उपर्युक्त परिवीक्षाधीन के असफल होने के संबंध में और परिवीक्षाधीन तथा प्रतिभू द्वारा देय राशि
        के संबंध में केन्द्र सरकार का निर्णय परिवीक्षाधीन और प्रतिभू के लिए अंतिम और बाध्यकारी होगा। प्रतिभू एतद्वारा यह भी करार करता है/करती है कि यहां
        नीचे दी गई उसकी देयता केन्द्र सरकार द्वारा परिवीक्षाधीन को भुगतान के लिए अधिक समय देने या यहां नीचे दी गई देय राशि को माफ करने अथवा
        परिवीक्षाधीन के प्रति अन्य उदारता बरते जाने के कारण किसी प्रकार प्रभावित नहीं होगी।
    </div>

    <div class="lines">
        <div>तारीख {!! $blank($hi['ddate'] ?? '', '150pt') !!} माह {!! $blank('', '150pt') !!} वर्ष {!! $blank('', '90pt') !!}</div>
        <div class="sign" style="text-align:center; margin-top:12pt;">परिवीक्षाधीन के हस्ताक्षर: {!! $blank('', '200pt') !!}</div>
    </div>

    <div class="lines">
        <div>(साक्षी का नाम) {!! $blank($hi['pwname'] ?? '', '180pt') !!} पता {!! $blank($hi['pwaddr'] ?? '', '230pt') !!} व्यवसाय {!! $blank($hi['pwocc'] ?? '', '150pt') !!} की उपस्थिति में <b>परिवीक्षाधीन द्वारा</b> हस्ताक्षर किए।</div>

        <div class="sign" style="text-align:center; margin-top:12pt;">प्रतिभू के हस्ताक्षर: {!! $blank('', '200pt') !!}</div>
        <div style="margin-top:10pt;">(साक्षी का नाम) {!! $blank($hi['swname'] ?? '', '180pt') !!} पता {!! $blank($hi['swaddr'] ?? '', '230pt') !!} व्यवसाय {!! $blank($hi['swocc'] ?? '', '150pt') !!} की उपस्थिति में <b>प्रतिभू द्वारा</b> हस्ताक्षर किए।</div>
    </div>

    <div class="body" style="text-indent:0; margin-top:10pt;">
        मैं, (प्रतिभू का नाम डालें) {!! $blank($hi['sname'] ?? '', '220pt') !!} जिसके हस्ताक्षर उपर्युक्त करार में प्रतिभू के रूप में दिए गए हैं एतद्वारा घोषणा करता हूं कि —
        <div class="opt">[क] मैं {!! $blank('', '170pt') !!} सरकार की स्थायी सेवा में हूं।</div>
        <div class="opt" style="text-align:center;">या</div>
        <div class="opt">[ख] मैं साधारणतया भारत का निवासी हूं और मेरे पास ऐसे साधन हैं जिनसे मैं केन्द्र सरकार द्वारा करार के निबंधनों के अनुसार राशि की मांग किए जाने पर केन्द्र सरकार को वह राशि चुका सकता/सकती हूं।</div>
    </div>

    <div class="lines">
        <div class="sign" style="text-align:center; margin-top:12pt;">प्रतिभू के हस्ताक्षर: {!! $blank('', '200pt') !!}</div>
        <div style="margin-top:10pt;">(साक्षी का नाम) {!! $blank($hi['swname2'] ?? '', '180pt') !!} पता {!! $blank($hi['swaddr2'] ?? '', '230pt') !!} व्यवसाय {!! $blank($hi['swocc2'] ?? '', '150pt') !!} की उपस्थिति में <b>प्रतिभू द्वारा</b> हस्ताक्षर किए।</div>
    </div>

    <div style="text-align:center; margin-top:12pt;">************************</div>

    <pagebreak />

    {{-- ═══════════ ENGLISH (page 3–4) ═══════════ --}}
    <div class="docno">Document-7-B</div>
    <div class="title">This bond to be typed and signed on India Non Judicial Paper (Rs. 100/-)<br>by Probationers of<br>CENTRAL CIVIL SERVICES- Group- A (Other then All India Services)</div>

    <div class="to">To<br>&emsp;&emsp;The President of India,</div>

    <div class="body">
        Whereas I, {The full name and address of the probationer should be inserted} {!! $blank($name, '230pt') !!} a candidate recommended by the
        Union Public Service Class I Service, on the results of Civil Service Examination, 20{!! $blank($exyear, '42pt') !!} in the {!! $blank($svc, '150pt') !!}
        (Name of service) (hereinafter referred to as &lsquo;the Probationer&rsquo;) being entitled subject to compliance with the recruitment rules of the
        {!! $blank($srule, '150pt') !!} (Insert name of your service) to receive from the President of India (hereinafter referred to as the Central Government)
        pay and allowances, during the period in which I am under training.
    </div>

    <div class="body">
        And whereas the Probationer is required to furnish Surety as herein contained. And whereas at the request of the Probationer
        Mr./ Ms. {!! $blank($name, '180pt') !!} (Insert name of Probationer), I, {The full name of the surety should be inserted} {!! $blank($sname, '190pt') !!}
        residing at {!! $blank($saddr, '220pt') !!} (Insert residence address) working as {!! $blank($socc, '160pt') !!} (Insert Occupation) (hereinafter
        referred to as Surety) have agreed to stand as Surety for the Probationer as herein contained.
    </div>

    <div class="body">
        Now, we, the Probationer Mr./ Ms./ Dr. {!! $blank($name, '175pt') !!} and I {!! $blank($sname, '175pt') !!} (Insert name of surety) jointly and
        severally, do hereby, in pursuance of the said rules promise and agree that in event of the failure of the Probationer to complete his/her
        training to the satisfaction of the Central Government, to refund to the Central Government on demand without demur any moneys paid to him,
        including pay and travelling expenses. And it is agreed that the decision of the Central Government as to the failure of the Probationer as
        aforesaid and the amount payable by the Probationer and Surety shall be final and binding on the Probationer and Surety. The surety hereby
        agrees that his/her liability hereunder shall not be affected on account of Central Government giving the Probationer extension of time for
        payment of or compounding the amount payable hereunder or on account of any indulgence shown to the Probationer.
    </div>

    <div class="lines">
        <div style="text-align:center;">Dated {!! $blank($dAt, '130pt') !!} this {!! $blank($dDay, '110pt') !!} day of {!! $blank($dMon, '150pt') !!}</div>

        <div class="sign" style="text-align:center; margin-top:14pt;">Signature of the Probationer:
            @if(!empty($sigs[0]))<img src="{{ $sigs[0] }}" class="sig-img">@else {!! $blank('', '200pt') !!} @endif
        </div>
        <div style="margin-top:10pt;">Signed <b>by Probationer</b> in the presence of &nbsp;(Name of Witness) {!! $blank($pwn, '220pt') !!}</div>
        <div>Address {!! $blank($pwa, '300pt') !!} Occupation {!! $blank($pwo, '160pt') !!}</div>

        <div class="sign" style="text-align:center; margin-top:14pt;">Signature of the Surety:
            @if(!empty($sigs[1]))<img src="{{ $sigs[1] }}" class="sig-img">@else {!! $blank('', '200pt') !!} @endif
        </div>
        <div style="margin-top:10pt;">Signed <b>by Surety</b> in the presence of &nbsp;(Name of Witness) {!! $blank($swn, '220pt') !!}</div>
        <div>Address {!! $blank($swa, '300pt') !!} Occupation {!! $blank($swo, '160pt') !!}</div>
    </div>

    <div class="body" style="text-indent:0; margin-top:10pt;">
        I (Insert name of surety) {!! $blank($sdname, '220pt') !!} whose signature is appended to the above agreement as surety, do hereby declare that
        <div class="opt">(a) I am in the permanent service of Government of {!! $blank('', '180pt') !!}</div>
        <div class="opt" style="text-align:center;">Or</div>
        <div class="opt">(b) I am ordinarily resident in India and that I possess means which will enable me, to repay to the Central Government the sums of money referred to in the event of my being called upon to do so in accordance with the terms of the agreement.</div>
        <div style="margin-top:6pt;"><i>{one of those should be stroke out}</i></div>
    </div>

    <div class="lines">
        <div class="sign" style="text-align:center; margin-top:14pt;">Signature of the Surety: {!! $blank('', '260pt') !!}</div>
        <div style="margin-top:10pt;">Signed <b>by Surety</b> in the presence of &nbsp;(Name of Witness) {!! $blank($swn2, '220pt') !!}</div>
        <div>Address {!! $blank($swa2, '300pt') !!} Occupation {!! $blank($swo2, '160pt') !!}</div>
    </div>

    <div style="text-align:center; margin-top:12pt;">********************************</div>

</body>
</html>
