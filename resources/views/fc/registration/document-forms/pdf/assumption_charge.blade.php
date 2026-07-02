@php
    $fmt = function ($d) {
        if (! $d) return '';
        try { return \Carbon\Carbon::parse($d)->format('d-m-Y'); } catch (\Throwable $e) { return $d; }
    };
    $name    = trim((string) ($data['officer_name'] ?? ''));
    $desig   = trim((string) ($data['designation'] ?? ''));
    $post    = trim((string) ($data['post_assumed'] ?? ''));
    $posting = trim((string) ($data['place_of_posting'] ?? ''));
    $adate   = $fmt($data['date_of_assumption'] ?? '');
    $time    = trim((string) ($data['time_of_assumption'] ?? ''));
    $place   = trim((string) ($data['place'] ?? ''));
    $ddate   = $fmt($data['declaration_date'] ?? '');
    $sigSrc  = $data['_signature_src'][0] ?? null;

    $blank = function ($v, $w = '150px') {
        $val = ($v !== '' && $v !== null) ? e($v) : '&nbsp;';
        return '<span style="display:inline-block; min-width:'.$w.'; border-bottom:1px solid #000; text-align:center; font-weight:bold; padding:0 4px;">'.$val.'</span>';
    };
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #000; line-height: 2.1; }
    .title { text-align: center; font-weight: bold; font-size: 15px; text-decoration: underline; }
    .title-hi { text-align: center; font-weight: bold; font-size: 13px; margin-top: 4px; }
    .body { text-align: justify; margin-top: 28px; }
    .lines { margin-top: 30px; }
    .sign { margin-top: 40px; text-align: right; }
    .sig-img { max-height: 42px; max-width: 220px; }
    .sep { border: 0; border-top: 1px dashed #999; margin: 34px 0; }
</style>
</head>
<body>

    {{-- ─────────────── ENGLISH ─────────────── --}}
    <div class="title">CERTIFICATE OF ASSUMPTION OF CHARGE</div>
    <div class="body">
        Certified that I, {!! $blank($name, '210px') !!} (Name), {!! $blank($desig, '190px') !!} (Designation),
        have assumed charge of the post of {!! $blank($post, '200px') !!} at {!! $blank($posting, '170px') !!}
        on {!! $blank($adate, '140px') !!} in the {!! $blank($time, '150px') !!}.
    </div>
    <div class="lines">
        Place: {!! $blank($place, '200px') !!} &nbsp;&nbsp;&nbsp; Date: {!! $blank($ddate, '150px') !!}
    </div>
    <div class="sign">
        <div style="display:inline-block; text-align:center; min-width:240px;">
            <div style="height:44px;">@if($sigSrc)<img src="{{ $sigSrc }}" class="sig-img">@endif</div>
            <div style="border-top:1px solid #000; padding-top:4px;">Signature of the Officer</div>
        </div>
    </div>

    <hr class="sep">

    {{-- ─────────────── HINDI ─────────────── --}}
    <div class="title-hi">कार्यभार ग्रहण प्रमाण-पत्र</div>
    <div class="body">
        प्रमाणित किया जाता है कि मैंने, {!! $blank($name, '210px') !!} (नाम), {!! $blank($desig, '190px') !!} (पद नाम), ने
        {!! $blank($post, '200px') !!} पद का कार्यभार {!! $blank($posting, '170px') !!} में दिनांक
        {!! $blank($adate, '140px') !!} को {!! $blank($time, '150px') !!} ग्रहण कर लिया है।
    </div>
    <div class="lines">
        स्थान: {!! $blank($place, '200px') !!} &nbsp;&nbsp;&nbsp; दिनांक: {!! $blank($ddate, '150px') !!}
    </div>
    <div class="sign">
        <div style="display:inline-block; text-align:center; min-width:240px;">
            <div style="border-top:1px solid #000; padding-top:4px;">अधिकारी के हस्ताक्षर</div>
        </div>
    </div>

</body>
</html>
