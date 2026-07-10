@php
    $fmt = function ($d) {
        if (! $d) return '';
        try { return \Carbon\Carbon::parse($d)->format('d-m-Y'); } catch (\Throwable $e) { return $d; }
    };
    $name    = trim((string) ($data['officer_name'] ?? ''));
    $desig   = trim((string) ($data['designation'] ?? ''));
    $place   = trim((string) ($data['place'] ?? ''));
    $dated   = $fmt($data['declaration_date'] ?? '');
    $variant = (string) ($data['form_variant'] ?? '');
    $isForm7 = strpos($variant, 'Form 7') === 0;
    $tag     = $isForm7 ? 'UNMARRIED' : (strpos($variant,'Form 8')===0 ? 'MARRIED' : '');
    $famEn   = $isForm7 ? 'having no family' : (strpos($variant,'Form 8')===0 ? 'having a family' : 'having no family / a family');
    $famHi   = $isForm7 ? 'जिसका कोई परिवार नहीं है' : (strpos($variant,'Form 8')===0 ? 'जिसका परिवार है' : 'जिसका कोई परिवार नहीं है / परिवार है');
    $colDefs = $template['tables'][0]['columns'];
    $rows    = $data['_tables']['nominees'] ?? [];
    $sigs    = $data['_signature_src'] ?? [];
    // Account Section (page 1)
    $service   = trim((string) ($data['service'] ?? ''));
    $doj       = $fmt($data['date_of_joining'] ?? '');
    $jtime     = (string) ($data['joining_time'] ?? '');
    $ttype     = (string) ($data['trainee_type'] ?? '');
    $deptText  = trim((string) ($data['department_details'] ?? ''));
    $earlier   = trim((string) ($data['earlier_member'] ?? ''));
    $tick = fn ($opt, $sel) => (((string) $opt === (string) $sel) ? '&#9745;' : '&#9744;').' '.e($opt);
    $blank = function ($v, $w='150px') {
        $val = ($v !== '' && $v !== null) ? e($v) : '&nbsp;';
        return '<span style="display:inline-block; min-width:'.$w.'; border-bottom:1px solid #000; text-align:center; font-weight:bold;">'.$val.'</span>';
    };
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #000; line-height: 1.7; }
    .tag { text-align: right; font-weight: bold; text-decoration: underline; font-size: 11px; }
    .formno { text-align: center; font-weight: bold; font-size: 11px; }
    .title { text-align: center; font-weight: bold; font-size: 12px; margin-top: 8px; text-transform: uppercase; }
    .title-hi { text-align: center; font-weight: bold; font-size: 11px; margin-top: 3px; }
    .sub { text-align: center; font-style: italic; font-size: 9.5px; margin-top: 5px; }
    .body { text-align: justify; margin-top: 16px; }
    table.gi { width: 100%; border-collapse: collapse; margin: 12px 0; }
    table.gi th, table.gi td { border: 0.6px solid #444; padding: 4px 5px; font-size: 8.8px; vertical-align: top; }
    table.gi th { background-color: #eaf0f8; text-align: center; font-weight: bold; }
    .cno { font-weight: normal; color: #555; }
    .lines { margin-top: 16px; }
    .sign-row { margin-top: 22px; text-align: right; }
    .sign-cell { display: inline-block; text-align: center; min-width: 200px; }
    .sig-img { max-height: 34px; }
    .note { font-size: 8.8px; margin-top: 16px; line-height: 1.5; }
    ol { margin: 3px 0 0; padding-left: 15px; }
</style>
</head>
<body>
    {{-- ═══════════ PAGE 1 · ACCOUNT SECTION ═══════════ --}}
    <div style="text-align:center;">
        <div style="font-weight:bold; font-size:11px;">Government of India / भारत सरकार</div>
        <div style="font-size:9.5px;">Ministry of Personnel, Public Grievances &amp; Pensions (Department of Personnel &amp; Training)</div>
        <div style="font-size:9px;">कार्मिक, लोक शिकायत एवं पेंशन मंत्रालय (कार्मिक एवं प्रशिक्षण विभाग)</div>
        <div style="font-weight:bold; font-size:10.5px; margin-top:3px;">Lal Bahadur Shastri National Academy of Administration, Mussoorie-248179</div>
        <div style="font-weight:bold; font-size:9.5px;">लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी, मसूरी-248179</div>
    </div>
    <div style="text-align:center; margin:12px 0;">
        <span style="background-color:#14315e; color:#fff; font-weight:bold; letter-spacing:1px; padding:3px 18px;">ACCOUNT SECTION / लेखा अनुभाग</span>
    </div>
    <table style="width:100%; border-collapse:collapse; font-size:10px;">
        <tr><td style="padding:6px 2px; vertical-align:top; width:52%;">1. Name in full (Block Letters) / पूरा नाम (बड़े अक्षरों में):</td>
            <td style="padding:6px 2px; border-bottom:1px solid #000; font-weight:bold;">{!! $name !== '' ? e($name) : '&nbsp;' !!}</td></tr>
        <tr><td style="padding:6px 2px; vertical-align:top;">2. Service to which you belong / जिस सेवा से आप संबंधित हैं:</td>
            <td style="padding:6px 2px; border-bottom:1px solid #000; font-weight:bold;">{!! $service !== '' ? e($service) : '&nbsp;' !!}</td></tr>
        <tr><td style="padding:6px 2px; vertical-align:top;">3. Date of Joining / कार्यग्रहण की तिथि:</td>
            <td style="padding:6px 2px; border-bottom:1px solid #000;"><b>{{ $doj }}</b> &nbsp;&nbsp; ({!! $tick('Forenoon', $jtime) !!} / पूर्वाह्न &nbsp; {!! $tick('Afternoon', $jtime) !!} / अपराह्न)</td></tr>
        <tr><td style="padding:6px 2px; vertical-align:top;">4. Whether a fresh Trainee or a Departmental candidate who will receive salary from the parent department / नया प्रशिक्षु अथवा विभागीय अभ्यर्थी:</td>
            <td style="padding:6px 2px; border-bottom:1px solid #000;">{!! $tick('Fresh Trainee', $ttype) !!} / नया प्रशिक्षु &nbsp;&nbsp; {!! $tick('Departmental Candidate', $ttype) !!} / विभागीय अभ्यर्थी</td></tr>
        <tr><td style="padding:6px 2px; vertical-align:top;">5. If a Departmental candidate, name &amp; address of the Department paying salary during the Foundation Course / यदि विभागीय अभ्यर्थी हैं, तो वेतन देने वाले विभाग का नाम एवं पता:</td>
            <td style="padding:6px 2px; border-bottom:1px solid #000; font-weight:bold;">{!! $deptText !== '' ? e($deptText) : '&nbsp;' !!}</td></tr>
        <tr><td style="padding:6px 2px; vertical-align:top;">6. Were you earlier a member of the CGEGIS? If so, the monthly subscription and the name &amp; address of the office maintaining the account / क्या आप पहले सीजीईजीआईएस के सदस्य थे? यदि हाँ, तो मासिक अंशदान तथा खाता रखने वाले कार्यालय का नाम एवं पता:</td>
            <td style="padding:6px 2px; border-bottom:1px solid #000; font-weight:bold;">{!! $earlier !== '' ? e($earlier) : '&nbsp;' !!}</td></tr>
    </table>
    <table style="width:100%; margin-top:26px; font-size:10px;"><tr>
        <td>Date / दिनांक: <b>{{ $dated }}</b></td>
        <td style="text-align:right;">
            <div style="height:30px;">@if(!empty($sigs[0]))<img src="{{ $sigs[0] }}" class="sig-img">@endif</div>
            Signature of Trainee / प्रशिक्षु के हस्ताक्षर
        </td>
    </tr></table>

    <pagebreak />

    {{-- ═══════════ PAGE 2 · FORM No. 7 / 8 NOMINATION ═══════════ --}}
    @if($tag)<div class="tag">{{ $tag }}</div>@endif
    <div class="formno">{{ $isForm7 ? 'Form No. 7' : (strpos($variant,'Form 8')===0 ? 'Form No. 8' : 'Form No. 7 / Form No. 8') }} &nbsp; {See Para 19.7}</div>
    <div class="title">Nomination for Benefits under the Central Government Employees Group Insurance Scheme, 1980</div>
    <div class="title-hi">केंद्रीय सरकार कर्मचारी समूह बीमा योजना, 1980 के अंतर्गत लाभों हेतु नामांकन</div>
    <div class="sub">
        {{ $isForm7 ? '(When the Government servant has no family and wishes to nominate one person or more than one person.)' : (strpos($variant,'Form 8')===0 ? '(When the Government servant has a family and wishes to nominate one member or more than one member thereof.)' : '') }}
    </div>

    <div class="body">
        I, {!! $blank($name, '250px') !!} {{ $famEn }}, hereby nominate the person/persons mentioned below and confer on
        him/them the right to receive, to the extent specified below, any amount that may be sanctioned by the Central
        Government under the Central Government Employees Group Insurance Scheme, 1980, in the event of my death while in
        service or which, having become payable on my attaining the age of superannuation, may remain unpaid at my death.
        <br><span style="font-size:9.2px;">मैं, {!! $blank($data['_hi']['name'] ?? '', '200px') !!} {{ $famHi }}, एतद्द्वारा नीचे उल्लिखित व्यक्ति/व्यक्तियों को नामांकित करता/करती हूँ तथा उन्हें उक्त योजना के अंतर्गत मेरी मृत्यु की स्थिति में स्वीकृत राशि प्राप्त करने का अधिकार प्रदान करता/करती हूँ।</span>
    </div>

    <table class="gi">
        <thead>
            <tr>@foreach($colDefs as $i => $c)<th>{!! $c['label'] !!}<div class="cno">({{ $i + 1 }})</div></th>@endforeach</tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>@foreach($colDefs as $c)<td>{{ $row[$c['name']] ?? '' }}</td>@endforeach</tr>
            @empty
                @for($i = 0; $i < 3; $i++)<tr>@foreach($colDefs as $c)<td>&nbsp;</td>@endforeach</tr>@endfor
            @endforelse
        </tbody>
    </table>

    <div class="lines">
        Dated this {!! $blank($dated, '150px') !!} at {!! $blank($place, '160px') !!} / दिनांक व स्थान<br><br>
        Two witnesses to signature / हस्ताक्षर के दो साक्षी:<br>
        1. {!! $blank(isset($sigs[1]) ? '' : '', '300px') !!}
        @if(!empty($sigs[1]))<img src="{{ $sigs[1] }}" class="sig-img">@endif<br>
        2. {!! $blank(isset($sigs[2]) ? '' : '', '300px') !!}
        @if(!empty($sigs[2]))<img src="{{ $sigs[2] }}" class="sig-img">@endif
    </div>

    <div class="sign-row">
        <div class="sign-cell">
            <div style="height:36px;">@if(!empty($sigs[0]))<img src="{{ $sigs[0] }}" class="sig-img">@endif</div>
            <div style="border-top:1px solid #000; padding-top:3px; font-size:9px;">Signature of the Government Servant / सरकारी कर्मचारी के हस्ताक्षर</div>
        </div>
        <div style="text-align:right; margin-top:6px;">Name / नाम: <strong>{{ $name ?: ' ' }}</strong></div>
        <div style="text-align:right;">Designation / पद नाम: <strong>{{ $desig ?: ' ' }}</strong></div>
    </div>

    <div class="note">
        <strong>N.B. / टिप्पणी:</strong>
        <ol>@foreach($template['notes'] ?? [] as $n)<li>{!! $n !!}</li>@endforeach</ol>
    </div>
</body>
</html>
