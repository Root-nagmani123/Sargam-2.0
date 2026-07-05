@php
    $fmt = function ($d) {
        if (! $d) return '';
        try { return \Carbon\Carbon::parse($d)->format('d-m-Y'); } catch (\Throwable $e) { return $d; }
    };
    $g       = fn ($k) => trim((string) ($data[$k] ?? ''));
    $town    = $g('town_village');
    $district= $g('district');
    $state   = $g('state');
    $reason  = $g('reason');
    $name    = $g('officer_name');
    $desig   = $g('designation');
    $place   = $g('place');
    $dated   = $fmt($data['declaration_date'] ?? '');
    $sigSrc  = $data['_signature_src'][0] ?? null;
    $blank = function ($v, $w = '160px') {
        $val = ($v !== '' && $v !== null) ? e($v) : '&nbsp;';
        return '<span style="display:inline-block; min-width:'.$w.'; border-bottom:1px solid #000; text-align:center; font-weight:bold; padding:0 4px;">'.$val.'</span>';
    };
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #000; line-height: 1.9; }
    .docno { text-align: right; font-weight: bold; font-size: 11px; }
    .title { text-align: center; font-weight: bold; font-size: 13px; text-decoration: underline; margin-top: 4px; }
    .sub { text-align: center; font-size: 9px; color: #333; margin-top: 3px; }
    .lead { margin-top: 20px; }
    .line { margin-top: 12px; }
    .sign { margin-top: 26px; }
    .sig-img { max-height: 34px; }
    .def { margin-top: 24px; font-size: 9.5px; line-height: 1.5; }
    .def-hd { font-weight: bold; }
    .def ol { margin: 5px 0 0; padding-left: 20px; }
    .accepted { text-align: center; font-weight: bold; text-decoration: underline; margin-top: 22px; }
</style>
</head>
<body>
    <div class="docno">Document-5</div>
    <div class="title">गृह नगर घोषणा &nbsp; DECLARATION OF HOME TOWN</div>
    <div class="sub">For the purpose of Leave Travel Concession (MHA Memo No. 43/715/57-Ests.(A), dated 24-06-1958)</div>

    <div class="lead">
        मैं घोषणा करता/करती हूँ कि अवकाश यात्रा रियायत हेतु मेरा गृह नगर/गांव निम्नलिखित है —<br>
        I declare that my &lsquo;Home-Town&rsquo; for the purpose of Leave Travel Concession is:
    </div>

    <div class="line">नगर/गांव का नाम / Name of Town/Village: {!! $blank($town, '320px') !!}</div>
    <div class="line">जिला / District: {!! $blank($district, '180px') !!} &nbsp;&nbsp; राज्य / State: {!! $blank($state, '180px') !!}</div>

    <div class="line">
        उपर्युक्त स्थान को &lsquo;गृह नगर&rsquo; घोषित किये जाने के निम्नलिखित कारण* हैं —<br>
        Reasons* for declaring the above place as my &lsquo;HOME-TOWN&rsquo; are given below:<br>
        {!! $blank($reason, '460px') !!}
    </div>

    <div class="sign">
        <div style="text-align:right;">हस्ताक्षर / Signature: @if($sigSrc)<img src="{{ $sigSrc }}" class="sig-img">@else {!! $blank('', '200px') !!} @endif</div>
        <div>नाम स्पष्ट अक्षरों में / Name in Block Letters: {!! $blank($name, '300px') !!}</div>
        <div>पदनाम / Designation: {!! $blank($desig, '320px') !!}</div>
        <div>स्थान / Place: {!! $blank($place, '180px') !!} &nbsp;&nbsp; तारीख / Dated: {!! $blank($dated, '150px') !!}</div>
    </div>

    <div class="def">
        <div class="def-hd">Definition of the term &ldquo;Home Town&rdquo; for the purpose of Leave Travel Concession (MHA Memo No. 43/715/57-Ests.(A) dated 24-06-1958). The declaration may be made based on the criteria below / &ldquo;गृह नगर&rdquo; की परिभाषा — घोषणा निम्नलिखित मानदंडों के आधार पर की जा सकती है:</div>
        <ol type="a">
            @foreach($template['notes'] ?? [] as $n)<li>{!! preg_replace('/^\([a-d]\)\s*/', '', $n) !!}</li>@endforeach
        </ol>
    </div>

    <div class="accepted">स्वीकृत / ACCEPTED</div>
</body>
</html>
