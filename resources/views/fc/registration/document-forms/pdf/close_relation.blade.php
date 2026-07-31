@php
    $fmt = function ($d) {
        if (! $d) return '';
        try { return \Carbon\Carbon::parse($d)->format('d-m-Y'); } catch (\Throwable $e) { return $d; }
    };
    $g       = fn ($k) => trim((string) ($data[$k] ?? ''));
    $desig   = $g('designation');
    $dated   = fc_document_date();   // hard-frozen date, overrides any saved value
    $sigSrc  = $data['_signature_src'][0] ?? null;
    $relations = [
        ['Father', 'पिता / Father'],
        ['Mother', 'माता / Mother'],
        ['Wife / Husband', 'पत्नी / पति / Wife / Husband'],
        ['Son(s)', 'पुत्र / Son{s}'],
        ['Daughter(s)', 'पुत्री / पुत्रियाँ / Daughter(s)'],
        ['Brother(s)', 'भाई / Brother(s)'],
        ['Sister(s)', 'बहिन / बहिनें / Sister(s)'],
    ];
    $rn = ['i','ii','iii','iv','v','vi','vii'];
    $fields = ['name','nationality','present_address','place_of_birth','occupation'];
    $lookup = function ($key) use ($data) {
        $m = [];
        foreach ($data['_tables'][$key] ?? [] as $r) { $m[$r['relation'] ?? ''] = $r; }
        return $m;
    };
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 9mm 12mm 9mm 12mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; color: #000; }
    .docno { text-align: right; font-weight: bold; font-size: 11pt; }
    .title-hi { text-align: center; font-weight: bold; font-size: 12pt; margin-top: 4pt; }
    .title { text-align: center; font-weight: bold; font-size: 11pt; margin-top: 2pt; }
    .sub { text-align: center; font-size: 9.5pt; margin-top: 2pt; }
    .secttl { font-weight: bold; margin-top: 8pt; margin-bottom: 3pt; font-size: 9.5pt; }
    table.cr { width: 100%; border-collapse: collapse; }
    table.cr th, table.cr td { border: 0.8px solid #000; padding: 2.5pt 5pt; font-size: 9.5pt; vertical-align: middle; }
    table.cr th { background-color: #eef2f8; text-align: center; font-weight: bold; }
    .rn { width: 22pt; text-align: center; }
    .rel { width: 17%; font-weight: bold; }
    .cert { margin-top: 9pt; text-align: justify; }
    .sign { margin-top: 11pt; }
    .sig-img { max-height: 32pt; }
    .notes { margin-top: 9pt; font-size: 11pt; line-height: 1.45; }
    .notes td { vertical-align: top; padding: 2pt 4pt 2pt 0; }
</style>
</head>
<body>
    <div class="docno">Document - 2</div>
    <div class="title-hi">सरकारी कर्मचारी द्वारा प्रथम नियुक्ति पर भरा जाने वाला फार्म</div>
    <div class="title">FORM TO BE FILLED BY GOVERNMENT EMPLOYEES ON FIRST APPOINTMENT</div>
    <div class="sub">[MHA OM No. F.3/12(S)/64-Ests.(B), dated 12-10-1965]</div>

    @foreach($template['tables'] as $ti => $tbl)
        <div class="secttl">
            {{ $ti + 1 }}.
            @if($ti === 0)
                विदेशों में निवास कर रहे या विदेशी राष्ट्रीयता-प्राप्त निकट संबंधी / Close relations who are Nationals of or are domiciled in other countries
            @else
                भारत में निवास कर रहे निकट संबंधी जो भारतीय मूल के नहीं हैं / Close relations residing in India who are non-Indian origin
            @endif
        </div>
        @php $map = $lookup($tbl['key']); @endphp
        <table class="cr">
            <thead>
                <tr>
                    <th class="rn"></th>
                    <th class="rel">संबंध / Relation</th>
                    <th>नाम / Name</th>
                    <th>राष्ट्रीयता / Nationality</th>
                    <th>वर्तमान पता / Present Address</th>
                    <th>जन्म स्थान / Place of Birth</th>
                    <th>व्यवसाय / Occupation</th>
                </tr>
            </thead>
            <tbody>
                @foreach($relations as $i => $r)
                    @php $row = $map[$r[0]] ?? []; @endphp
                    <tr>
                        <td class="rn">{{ $rn[$i] }}</td>
                        <td class="rel">{{ $r[1] }}</td>
                        @foreach($fields as $col)<td>{{ $row[$col] ?? '' }}</td>@endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="cert">मैं प्रमाणित करता/करती हूँ कि जहाँ तक मेरी जानकारी और विश्वास है, पूर्वोक्त सूचना सही और पूर्ण है।<br>I certify that the foregoing information is correct and complete to the best of my knowledge and belief.</div>

    <div class="sign">
        <div style="text-align:right;">हस्ताक्षर / Signature: @if($sigSrc)<img src="{{ $sigSrc }}" class="sig-img">@else <span style="display:inline-block;min-width:180pt;border-bottom:1px solid #000;font-weight:bold;text-align:center;">{!! str_repeat('_', 30) !!}</span> @endif</div>
        <div style="text-align:right; margin-top:6pt;">पदनाम / Designation: <span style="display:inline-block; min-width:180pt; border-bottom:1px solid #000; font-weight:bold; text-align:center;">{!! $desig !== '' ? e($desig) : str_repeat('_', 30) !!}</span></div>
        <div style="text-align:right; margin-top:6pt;">तारीख / Date: <b>{{ $dated }}</b></div>
    </div>

    <table class="notes">
        <tr><td style="width:70pt;"><b>टिप्पणी / Note 1.</b></td>
            <td>इस प्रपत्र में दी जाने वाली सूचना का छिपाया जाना विभागीय अपराध समझा जाएगा जिसके लिए सेवा से बरखास्त किये जाने तक का दण्ड दिया जा सकता है।<br>Suppression of information in this form will be considered a major departmental offence for which the punishment may extend to dismissal from service.</td></tr>
        <tr><td><b>Note 2.</b></td>
            <td>उपर्युक्त तारीख के बाद यदि कोई परिवर्तन होता है तो इसकी सूचना विभागाध्यक्ष / कार्यालयाध्यक्ष को प्रत्येक वर्ष के अंत में दें।<br>Subsequent changes if any in the above date should be reported to the Head of Office / Department at the end of each year.</td></tr>
    </table>
</body>
</html>
