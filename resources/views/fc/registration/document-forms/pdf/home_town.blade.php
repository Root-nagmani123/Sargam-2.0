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
    $place   = $g('place') ?: 'Mussoorie';
    $dated   = '24-08-2026';   // hard-frozen date, overrides any saved value
    $sigSrc  = $data['_signature_src'][0] ?? null;
    $blank = function ($v, $w = '160pt') {
        $val = ($v !== '' && $v !== null) ? e($v) : str_repeat('_', max(12, (int) round((strpos($w, 'mm') !== false ? (float) $w * 2.83465 : (float) $w) / 6)));
        return '<span style="display:inline-block; min-width:'.$w.'; border-bottom:1px solid #000; text-align:center; font-weight:bold; padding:0 4pt;">'.$val.'</span>';
    };
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 11mm 18mm 11mm 18mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; color: #000; line-height: 1.9; }
    .docno { text-align: right; font-weight: bold; font-size: 11pt; }
    .title { text-align: center; font-weight: bold; font-size: 13pt; text-decoration: underline; margin-top: 4pt; }
    .sub { text-align: center; font-size: 9.5pt; color: #333; margin-top: 3pt; }
    .lead { margin-top: 20pt; }
    .line { margin-top: 12pt; }
    .sign { margin-top: 26pt; }
    .sig-img { max-height: 34pt; }
    .def { margin-top: 24pt; font-size: 9.5pt; line-height: 1.5; }
    .def-hd { font-weight: bold; }
    .def ol { margin: 5pt 0 0; padding-left: 20pt; }
    .accepted { text-align: center; font-weight: bold; text-decoration: underline; margin-top: 22pt; }
</style>
</head>
<body>
    <div class="docno">Document-5</div>
    <div class="title">गृह नगर घोषणा &nbsp; DECLARATION OF HOME TOWN</div>

    <div class="lead">
        मैं घोषणा करता/करती हूँ कि अवकाश यात्रा रियायत हेतु मेरा गृह नगर/गांव निम्नलिखित है —<br>
        I declare that my &lsquo;Home-Town&rsquo; for the purpose of Leave Travel Concession is:
    </div>

    <div class="line">नगर/गांव का नाम / Name of Town/Village: {!! $blank($town, '320pt') !!}</div>
    <div class="line">जिला / District: {!! $blank($district, '180pt') !!} &nbsp;&nbsp; राज्य / State: {!! $blank($state, '180pt') !!}</div>

    <div class="line">
        उपर्युक्त स्थान को &lsquo;गृह नगर&rsquo; घोषित किये जाने के निम्नलिखित कारण* हैं —<br>
        Reasons* for declaring the above place as my &lsquo;HOME-TOWN&rsquo; are given below:<br>
        {!! $blank($reason, '460pt') !!}
    </div>

    <div class="sign">
        <div style="padding-left:42%;">हस्ताक्षर / Signature: @if($sigSrc)<img src="{{ $sigSrc }}" class="sig-img">@else {!! $blank('', '190pt') !!} @endif</div>
        <div style="padding-left:42%;">नाम स्पष्ट अक्षरों में / Name in Block Letters: {!! $blank($name, '190pt') !!}</div>
        <div style="padding-left:42%;">पदनाम / Designation: {!! $blank($desig, '190pt') !!}</div>
        <table style="width:100%; margin-top:6pt;"><tr>
            <td style="width:42%;">स्थान / Place: {!! $blank($place, '150pt') !!}</td>
            <td>तारीख / Dated: {!! $blank($dated, '150pt') !!}</td>
        </tr></table>
    </div>

    <div class="def">
        <div class="def-hd">Definition of term &ldquo;Home Town&rdquo; for the purpose of LEAVE TRAVEL CONCESSION in view of Ministry of Home Affairs Memo No. 43/715/57/Exts(A) dated 24.06.1958 received under F.No. 30/189/58 (Co-ord) (372) dated 12.07.1958. The declaration may be made based on the criteria given below.</div>
        <div style="margin:5pt 0 0 18pt;">a) Whether the place declared by Government servant is the one which requires his physical presence at intervals for discharging various domestic and social obligations, and if so, whether after his entry into service, the Government servant had been visiting that place frequently.</div>
        <div style="margin:3pt 0 0 18pt;">b) Whether the Government servant owns residential property in that place or whether he is a member of a joint family having such property there.</div>
        <div style="margin:3pt 0 0 18pt;">c) Whether his near relations are resident in that place.</div>
        <div style="margin:3pt 0 0 18pt;">d) Whether, prior to his entry into Government service, the Government servant had been living there for some years.</div>
    </div>

    <div class="accepted">स्वीकृत / ACCEPTED</div>
</body>
</html>
