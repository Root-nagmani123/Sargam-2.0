@php
    $fmt = function ($d) {
        if (! $d) return '';
        try { return \Carbon\Carbon::parse($d)->format('d-m-Y'); } catch (\Throwable $e) { return $d; }
    };
    $name    = trim((string) ($data['officer_name'] ?? ''));
    $desig   = trim((string) ($data['designation'] ?? ''));
    $service = trim((string) ($data['service'] ?? ''));
    $adate   = $fmt($data['date_of_assumption'] ?? '');
    $time    = trim((string) ($data['time_of_assumption'] ?? ''));
    $ddate   = $fmt($data['declaration_date'] ?? '');
    $sigSrc  = $data['_signature_src'][0] ?? null;

    // Hindi: पूर्वाह्न (Forenoon) / अपराह्न (Afternoon) — selected option bolded
    $tmHi = $time === 'Forenoon' ? '<b>पूर्वाह्न</b> / अपराह्न'
          : ($time === 'Afternoon' ? 'पूर्वाह्न / <b>अपराह्न</b>' : '<b>पूर्वाह्न / अपराह्न</b>');
    $tmEn = $time === 'Forenoon' ? '<b>forenoon</b> / afternoon'
          : ($time === 'Afternoon' ? 'forenoon / <b>afternoon</b>' : '<b>forenoon / afternoon</b>');

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
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #000; line-height: 2.0; }
    .title-hi { text-align: center; font-weight: bold; font-size: 14px; }
    .title { text-align: center; font-weight: bold; font-size: 13px; text-decoration: underline; margin-top: 3px; }
    .body { text-align: justify; margin-top: 22px; text-indent: 30px; }
    .body-en { text-align: justify; margin-top: 14px; text-indent: 30px; }
    .foot { margin-top: 34px; width: 100%; }
    .foot td { vertical-align: bottom; font-size: 12px; line-height: 1.9; }
    .foot .lft { text-align: left; }
    .foot .rgt { text-align: right; }
    .sig-img { max-height: 40px; max-width: 210px; }
    .copy { padding: 6px 0; }
    .sep { text-align: center; color: #444; letter-spacing: 3px; font-size: 12px; margin: 26px 0; font-weight: bold; }
    .copytag { text-align: right; font-size: 9px; color: #666; }
</style>
</head>
<body>

@for($copy = 0; $copy < 2; $copy++)
    <div class="copy">
        <div class="copytag">{{ $copy === 0 ? '(Office copy / कार्यालय प्रति)' : '(Officer copy / अधिकारी प्रति)' }}</div>
        <div class="title-hi">कार्यभार-ग्रहण प्रमाणपत्र</div>
        <div class="title">CERTIFICATE OF ASSUMPTION OF CHARGE</div>

        {{-- ─────────────── HINDI ─────────────── --}}
        <div class="body">
            प्रमाणित किया जाता है कि मैंने आज दिनांक {!! $blank($adate, '120px') !!} {!! $tmHi !!} में
            लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी, मसूरी में (सेवा) {!! $blank($service, '190px') !!}
            के पद का कार्यभार ग्रहण कर लिया है।
        </div>

        {{-- ─────────────── ENGLISH ─────────────── --}}
        <div class="body-en">
            Certified that I have on the {!! $tmEn !!} of this day {!! $blank($adate, '120px') !!}
            assumed the charge of the office of {!! $blank($service, '190px') !!} (Service) in
            Lal Bahadur Shastri National Academy of Administration, Mussoorie.
        </div>

        <table class="foot">
            <tr>
                <td class="lft" style="width:45%;">
                    स्थान / Place: <b>मसूरी / Mussoorie</b><br>
                    दिनांक / Dated: <b>{{ $ddate ?: ' ' }}</b>
                </td>
                <td class="rgt" style="width:55%;">
                    <div style="height:42px;">@if($sigSrc)<img src="{{ $sigSrc }}" class="sig-img">@endif</div>
                    हस्ताक्षर / Signature<br>
                    नाम / Name: <b>{{ $name ?: ' ' }}</b><br>
                    पदनाम / Designation: <b>{{ $desig ?: ' ' }}</b>
                </td>
            </tr>
        </table>
    </div>

    @if($copy === 0)
        <div class="sep">* * * * * * * * * * * * * * *</div>
    @endif
@endfor

</body>
</html>
