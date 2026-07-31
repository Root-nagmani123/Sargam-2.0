@php
    $g = fn ($k) => trim((string) ($data[$k] ?? ''));
    $name    = $g('officer_name');
    $sname   = $g('surety_name');
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
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; color: #000; line-height: 1.38; }
    .docno { text-align: right; font-weight: bold; font-size: 11pt; }
    .title { text-align: center; font-weight: bold; font-size: 12pt; text-decoration: underline; line-height: 1.5; margin-top: 4pt; }
    .subtitle { text-align: center; font-size: 11pt; margin-top: 3pt; }
    .to { margin-top: 7pt; }
    .body { text-align: justify; margin-top: 5pt; text-indent: 28pt; }
    .lines { margin-top: 7pt; }
    .sign { margin-top: 6pt; }
    .sig-img { max-height: 34pt; }
    .elig { margin-top: 5pt; }
</style>
</head>
<body>

    {{-- ═══════════ HINDI (Document-7-A — as per official sample) ═══════════ --}}
    <div class="docno">Document-7-A</div>
    <div class="title">This bond to be typed and signed on India Non Judicial Paper (Rs. 100/-)<br>by Probationers of All India Services (IAS/IPS/IFoS as the case may be)</div>
    <div class="subtitle">भारतीय प्रशासनिक सेवा/भारतीय पुलिस सेवा/ भारतीय वन सेवा के अधिकारियों द्वारा भरा जाना है</div>
    <div style="text-align:center; margin-top:2pt;">*******************************</div>

    <div class="to">सेवा में<br>&emsp;&emsp;भारत के राष्ट्रपति,</div>

    <div class="body">
        मैं, {!! $blank($hi['pname'] ?? '', '250pt') !!} (परिवीक्षाधीन का नाम और पता) भारतीय प्रशासनिक सेवा में परिवीक्षाधीन व्यक्ति, (जिसे इसमें आगे
        &lsquo;परिवीक्षाधीन&rsquo; कहा गया है), राष्ट्रपति (जिन्हें इसमें आगे &lsquo;केन्द्र सरकार&rsquo; कहा गया है) अथवा जिस राज्य में मुझे नियुक्त किया जाए, उस
        राज्य सरकार से प्रशिक्षण की अवधि के लिए (भारतीय प्रशासन सेवा (परिवीक्षा) नियमावली, 1954 अथवा भारतीय पुलिस सेवा (परिवीक्षा) नियमावली, 1954
        के अध्यधीन) वेतन और भत्ते पाने का हकदार हूं।
    </div>

    <div class="body">
        अब, परिवीक्षाधीन और मैं, {!! $blank($hi['sname'] ?? '', '230pt') !!} (प्रतिभू का नाम और पता, जिसे इसमें आगे प्रतिभू कहा गया है) उक्त नियमों के
        अनुसरण में संयुक्त रूप से और पृथक-पृथक एतद्वारा वचन देते हैं और करार करते हैं कि यदि परिवीक्षाधीन व्यक्ति केन्द्र सरकार की संतुष्टि के अनुसार
        परिवीक्षा की अवधि पूरी नहीं कर पाता है तो वेतन और नियुक्ति पर जाने के लिए यात्रा व्यय सहित परिवीक्षाधीन व्यक्ति को भुगतान की गई किसी भी राशि
        की मांग किए जाने पर हम उसका भुगतान करेंगे।
    </div>

    <div class="body">
        प्रतिभू एतद्वारा यह करार करता है कि यहां नीचे दी गई उसकी देयता केन्द्र सरकार द्वारा उसकी परिवीक्षा की अवधि बढ़ाए जाने से अथवा परिवीक्षाधीन
        व्यक्ति को भुगतान के लिए अधिक समय देने या यहां नीचे दी गई देय राशि को माफ करने से किसी प्रकार प्रभावित नहीं होगी।
    </div>

    <div class="lines">
        <table style="border-collapse:collapse; margin-top:2pt;"><tr style="vertical-align:bottom;">
            <td style="padding:0;">तारीख&nbsp;</td>
            <td style="padding:0 4pt; width:175pt; border-bottom:1px solid #000; text-align:center; font-weight:bold;">{{ $hi['ddate'] ?? '' }}</td>
            <td style="padding:0;">&nbsp;माह&nbsp;</td>
            <td style="padding:0 4pt; width:155pt; border-bottom:1px solid #000; text-align:center; font-weight:bold;">{{ $hi['dmonth'] ?? '' }}</td>
            <td style="padding:0;">&nbsp;वर्ष&nbsp;</td>
            <td style="padding:0 4pt; width:75pt; border-bottom:1px solid #000; text-align:center; font-weight:bold;">{{ $hi['dyear'] ?? '' }}</td>
        </tr></table>

        <div class="sign" style="text-align:center; margin-top:12pt;">परिवीक्षाधीन के हस्ताक्षर: {!! $blank('', '200pt') !!}</div>
        <div style="margin-top:10pt;">(साक्षी का नाम) {!! $blank($hi['pwname'] ?? '', '180pt') !!} पता {!! $blank($hi['pwaddr'] ?? '', '230pt') !!} व्यवसाय {!! $blank($hi['pwocc'] ?? '', '150pt') !!} की उपस्थिति में <b>परिवीक्षाधीन द्वारा</b> हस्ताक्षर किए।</div>

        <div class="sign" style="text-align:center; margin-top:12pt;">प्रतिभू के हस्ताक्षर: {!! $blank('', '200pt') !!}</div>
        <div style="margin-top:10pt;">(साक्षी का नाम) {!! $blank($hi['swname'] ?? '', '180pt') !!} पता {!! $blank($hi['swaddr'] ?? '', '230pt') !!} व्यवसाय {!! $blank($hi['swocc'] ?? '', '150pt') !!} की उपस्थिति में <b>प्रतिभू द्वारा</b> हस्ताक्षर किए।</div>
    </div>

    <div class="body" style="text-indent:0; margin-top:12pt;">
        मैं, *** (प्रतिभू का नाम डालें) {!! $blank($hi['sname'] ?? '', '220pt') !!} जिसके हस्ताक्षर उपर्युक्त करार में प्रतिभू के रूप में दिए गए हैं एतद्वारा घोषणा करता हूं कि
        <div class="elig" style="margin-left:20pt;">**** [क] मैं {!! $blank('', '170pt') !!} सरकार की स्थायी सेवा में हूं।</div>
        <div class="elig" style="text-align:center;">या</div>
        <div class="elig" style="margin-left:20pt;">**** [ख] मैं साधारणतया भारत का निवासी हूं और मेरे पास ऐसे साधन हैं जिनसे मैं केन्द्र सरकार द्वारा करार के निबंधनों के अनुसार राशि की मांग किए जाने पर केन्द्र सरकार को वह राशि चुका सकता/सकती हूं।</div>
    </div>

    <div class="lines">
        <div class="sign" style="text-align:center; margin-top:12pt;">प्रतिभू के हस्ताक्षर: {!! $blank('', '200pt') !!}</div>
        <div style="margin-top:10pt;">(साक्षी का नाम) {!! $blank($hi['swname2'] ?? '', '180pt') !!} पता {!! $blank($hi['swaddr2'] ?? '', '230pt') !!} व्यवसाय {!! $blank($hi['swocc2'] ?? '', '150pt') !!} की उपस्थिति में <b>प्रतिभू द्वारा</b> हस्ताक्षर किए।</div>
    </div>

    <div style="text-align:center; margin-top:12pt;">************************</div>

    <pagebreak />

    {{-- ═══════════ ENGLISH (Document-7-A — as per official sample) ═══════════ --}}
    <div class="docno">Document-7-A</div>
    <div class="title">This bond to be typed and signed on India Non Judicial Paper (Rs. 100/-)<br>by Probationers of All India Services (IAS/IPS/ IFoS)</div>
    <div class="subtitle">Format to be used by IAS/ IPS Probationers</div>

    <div class="to">To<br><br>&emsp;&emsp;The President of India,</div>

    <div class="body">
        Whereas I, (Insert name of probationer) {!! $blank($name, '230pt') !!} a probationer in the Indian Administrative or
        Police Service (hereinafter referred to as &ldquo;the probationer&rdquo;) being entitled [subject to compliance with the
        Indian Administrative Service (Probation) Rules, 1954 or Indian Police Service (Probation) Rules, 1954 or Indian forest
        Service (Probation) Rules, 1968] to receive from the President of India <i>(hereinafter referred to as the Central
        Government)</i> or from the Government of the State to which I may be posted, pay and allowances during the period in which
        I am under training:
    </div>

    <div class="body">
        Now, we, the probationer, and** {!! $blank($sname, '230pt') !!} (hereinafter referred to as &ldquo;the surety&rdquo;)
        jointly and severally, do hereby, in pursuance of the said rules, promise and agree in the event of the failure of the
        probationer to complete probation to the satisfaction of the Central Government, to refund to the Central Government on
        demand any moneys paid to him, including the pay and travelling expenses to join appointment.
    </div>

    <div class="body">
        The surety hereby agrees that his/her liability hereunder shall not be affected by the Central Government extending the
        period of probation or giving the probationer an extension of time for payment of or compounding the amount payable
        hereunder.
    </div>

    <div class="lines">
        <div style="text-align:center;">Dated {!! $blank($dAt, '130pt') !!} this {!! $blank($dDay, '110pt') !!} day of {!! $blank($dMon, '150pt') !!}</div>

        <div class="sign" style="text-align:center; margin-top:16pt;">Signature of the Probationer:
            @if(!empty($sigs[0]))<img src="{{ $sigs[0] }}" class="sig-img">@else {!! $blank('', '200pt') !!} @endif
        </div>
        <div style="margin-top:10pt;">Signed <b><u>by Probationer</u></b> in the presence of &nbsp;(Name of Witness) {!! $blank($pwn, '220pt') !!}</div>
        <div>Address {!! $blank($pwa, '420pt') !!}</div>
        <div>{!! $blank('', '200pt') !!} Occupation {!! $blank($pwo, '200pt') !!}</div>

        <div class="sign" style="text-align:center; margin-top:16pt;">Signature of the Surety:
            @if(!empty($sigs[1]))<img src="{{ $sigs[1] }}" class="sig-img">@else {!! $blank('', '200pt') !!} @endif
        </div>
        <div style="margin-top:10pt;">Signed <b><u>by Surety</u></b> in the presence of &nbsp;(Name of Witness) {!! $blank($swn, '220pt') !!}</div>
        <div>Address {!! $blank($swa, '420pt') !!}</div>
        <div>{!! $blank('', '200pt') !!} Occupation {!! $blank($swo, '200pt') !!}</div>
    </div>

    <div class="body" style="text-indent:0; margin-top:14pt;">
        I (Insert name, address and occupation of surety) {!! $blank($sdname, '230pt') !!} whose signature is appended to the above agreement as surety, do hereby declare that
        <div class="elig" style="margin-left:36pt;">**** (a) I am in the permanent service of Government of {!! $blank('', '180pt') !!}</div>
        <div class="elig" style="text-align:center;">Or</div>
        <div class="elig" style="margin-left:36pt;">**** (b) I am ordinarily resident in India and that I possess means which will enable me, to repay to the Central Government the sums of money referred to in the event of my being called upon to do so in accordance with the terms of the agreement.</div>
        <div style="margin-top:6pt;"><i>{**** one of those should be stroke out}</i></div>
    </div>

    <div class="lines">
        <div class="sign" style="text-align:center; margin-top:16pt;">Signature of the Surety: {!! $blank('', '260pt') !!}</div>
        <div style="margin-top:10pt;">Signed <b><u>by Surety</u></b> in the presence of &nbsp;(Name of Witness) {!! $blank($swn2, '220pt') !!}</div>
        <div>Address {!! $blank($swa2, '420pt') !!}</div>
        <div>Occupation {!! $blank($swo2, '200pt') !!}</div>
    </div>

    <div style="text-align:center; margin-top:12pt;">********************************</div>

</body>
</html>
