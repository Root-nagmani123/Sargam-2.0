@php
    $fmt = function ($d) {
        if (! $d) return '';
        try { return \Carbon\Carbon::parse($d)->format('d-m-Y'); } catch (\Throwable $e) { return $d; }
    };
    $flat = [];
    foreach (($template['sections'] ?? []) as $sec) { foreach ($sec['fields'] as $f) { $flat[$f['name']] = $f; } }
    $opt = fn ($name) => $flat[$name]['options'] ?? [];
    $v   = fn ($name) => trim((string) ($data[$name] ?? ''));
    // Comb box: render a value into fixed single-character cells.
    $comb = function ($value, $cells = 20) {
        $value = strtoupper((string) $value);
        $out = '<table style="border-collapse:collapse;"><tr>';
        for ($i = 0; $i < $cells; $i++) {
            $ch = mb_substr($value, $i, 1);
            $out .= '<td style="border:0.5px solid #555; width:13px; height:15px; text-align:center; font-size:9px; font-weight:bold; color:#0b3d91;">'.($ch !== '' ? e($ch) : '&nbsp;').'</td>';
        }
        $out .= '</tr></table>';
        return $out;
    };
    // Tick row for a select value.
    $ticks = function ($name, $options) use ($data) {
        $sel = (string) ($data[$name] ?? '');
        $out = '';
        foreach ($options as $o) {
            $mark = ((string) $o === $sel) ? '&#9745;' : '&#9744;'; // ☑ / ☐
            $out .= '<span style="margin-right:14px; font-size:9.5px;">'.$mark.' '.e($o).'</span>';
        }
        return $out;
    };
    $sigSrc = $data['_signature_src'][0] ?? null;
    $tbl = $template['tables'][0] ?? null;
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #000; }
    .top { border: 1.4px solid #000; }
    .top td { vertical-align: middle; padding: 5px 7px; }
    .top .t { text-align: center; font-weight: bold; font-size: 12px; }
    .top .c { text-align: center; font-size: 7px; color: #333; margin-top: 1px; }
    .top .h { text-align: center; font-weight: bold; font-size: 10px; margin-top: 1px; }
    .top .photo { border-left: 1.4px solid #000; width: 78px; text-align: center; font-size: 6.5px; color: #667; }
    .catbox { border: 1px solid #000; border-top: 0; padding: 5px 7px; }
    .catbox b { font-size: 9px; }
    .to { font-size: 8.6px; margin: 7px 0; line-height: 1.45; }
    .note { font-size: 7px; color: #444; font-style: italic; }
    .band { background-color: #14315e; color: #fff; font-weight: bold; font-size: 8.6px; padding: 3px 7px; text-transform: uppercase; margin-top: 8px; }
    table.kv { width: 100%; border-collapse: collapse; border: 0.6px solid #b9c7de; border-top: 0; }
    table.kv td { padding: 4px 6px; font-size: 8.6px; vertical-align: middle; border-bottom: 0.5px solid #dbe3ef; }
    table.kv td.l { font-weight: bold; width: 30%; color: #333; }
    table.grid { width: 100%; border-collapse: collapse; margin-top: 3px; }
    table.grid th, table.grid td { border: 0.6px solid #666; padding: 3.5px 5px; font-size: 8px; }
    table.grid th { background-color: #eaf0f8; text-align: center; font-weight: bold; }
    .decl { border: 0.6px solid #999; background-color: #fafafa; padding: 6px 8px; font-size: 8.2px; line-height: 1.45; }
    .sign { margin-top: 24px; text-align: right; }
    .sign-line { display: inline-block; border-top: 1px solid #000; padding-top: 3px; font-size: 8.4px; min-width: 240px; text-align: center; }
    .sig-img { max-height: 34px; }
</style>
</head>
<body>
    <table class="top" width="100%"><tr>
        <td>
            <div class="t">NATIONAL PENSION SYSTEM (NPS) – SUBSCRIBER REGISTRATION FORM</div>
            <div class="c">Central Recordkeeping Agency (CRA) — Protean eGov Technologies Limited <i>(formerly NSDL e-Governance Infrastructure Limited)</i></div>
            <div class="h">राष्ट्रीय पेंशन प्रणाली (एनपीएस) — अंशदाता पंजीकरण प्रपत्र</div>
        </td>
        <td class="photo">Affix recent passport-size photograph (3.5 × 2.5 cm)</td>
    </tr></table>

    <div class="catbox">
        <b>Please select your category / कृपया अपनी श्रेणी चुनें:</b><br>
        {!! $ticks('category', $opt('category')) !!}
    </div>

    <div class="to">
        To, <b>The National Pension System Trust.</b> Dear Sir/Madam, I hereby request that an NPS account be opened in my name as per the particulars given below:
        <div class="note">* indicates mandatory fields. To be filled in English and in BLOCK letters.</div>
    </div>

    {{-- 1. PERSONAL DETAILS --}}
    <div class="band">1. PERSONAL DETAILS / व्यक्तिगत विवरण</div>
    <table class="kv">
        <tr><td class="l">Name of Applicant / आवेदक का नाम</td><td>{!! $ticks('salutation', $opt('salutation')) !!}</td></tr>
        <tr><td class="l">First Name / नाम</td><td>{!! $comb($v('first_name'), 22) !!}</td></tr>
        <tr><td class="l">Middle Name / मध्य नाम</td><td>{!! $comb($v('middle_name'), 22) !!}</td></tr>
        <tr><td class="l">Last Name / उपनाम</td><td>{!! $comb($v('last_name'), 22) !!}</td></tr>
        <tr><td class="l">Father's Name / पिता का नाम</td><td>{!! $comb($v('father_name'), 30) !!}</td></tr>
        <tr><td class="l">Mother's Name / माता का नाम</td><td>{!! $comb($v('mother_name'), 30) !!}</td></tr>
        <tr><td class="l">Date of Birth / जन्म तिथि</td><td><b>{{ $fmt($v('date_of_birth')) }}</b></td></tr>
        <tr><td class="l">Place of Birth / जन्म स्थान</td><td><b>{{ $v('place_of_birth') }}</b></td></tr>
        <tr><td class="l">Nationality / राष्ट्रीयता</td><td><b>{{ $v('nationality') }}</b></td></tr>
        <tr><td class="l">Gender / लिंग</td><td>{!! $ticks('gender', $opt('gender')) !!}</td></tr>
        <tr><td class="l">Marital Status / वैवाहिक स्थिति</td><td>{!! $ticks('marital_status', $opt('marital_status')) !!}</td></tr>
        <tr><td class="l">Spouse's Name / जीवनसाथी का नाम</td><td><b>{{ $v('spouse_name') }}</b></td></tr>
    </table>

    {{-- 2. PROOF OF IDENTITY --}}
    <div class="band">2. PROOF OF IDENTITY (PoI) / पहचान का प्रमाण</div>
    <table class="kv">
        <tr><td class="l">PAN / पैन</td><td>{!! $comb($v('pan'), 10) !!}</td></tr>
        <tr><td class="l">Aadhaar No. / आधार संख्या</td><td>{!! $comb($v('aadhaar'), 12) !!}</td></tr>
    </table>

    {{-- 3. CORRESPONDENCE ADDRESS --}}
    <div class="band">3. CORRESPONDENCE ADDRESS DETAILS / पत्राचार का पता</div>
    <table class="kv">
        <tr><td class="l">Address / पता</td><td><b>{{ $v('corr_address') }}</b></td></tr>
        <tr><td class="l">City / शहर</td><td><b>{{ $v('corr_city') }}</b></td></tr>
        <tr><td class="l">State / राज्य</td><td><b>{{ $v('corr_state') }}</b></td></tr>
        <tr><td class="l">Pincode / पिन कोड</td><td>{!! $comb($v('corr_pincode'), 6) !!}</td></tr>
    </table>

    {{-- 4. PERMANENT ADDRESS --}}
    <div class="band">4. PERMANENT ADDRESS DETAILS / स्थायी पता</div>
    <table class="kv">
        <tr><td class="l">Address / पता</td><td><b>{{ $v('perm_address') }}</b></td></tr>
        <tr><td class="l">City / शहर</td><td><b>{{ $v('perm_city') }}</b></td></tr>
        <tr><td class="l">State / राज्य</td><td><b>{{ $v('perm_state') }}</b></td></tr>
        <tr><td class="l">Pincode / पिन कोड</td><td>{!! $comb($v('perm_pincode'), 6) !!}</td></tr>
    </table>

    {{-- 5. CONTACT DETAILS --}}
    <div class="band">5. CONTACT DETAILS / संपर्क विवरण</div>
    <table class="kv">
        <tr><td class="l">Mobile No. / मोबाइल संख्या</td><td>{!! $comb($v('mobile'), 10) !!}</td></tr>
        <tr><td class="l">Email / ईमेल</td><td><b>{{ $v('email') }}</b></td></tr>
    </table>

    {{-- 7. BANK DETAILS --}}
    <div class="band">7. SUBSCRIBER BANK DETAILS / अंशदाता बैंक विवरण</div>
    <table class="kv">
        <tr><td class="l">Account Type / खाता प्रकार</td><td>{!! $ticks('account_type', $opt('account_type')) !!}</td></tr>
        <tr><td class="l">Bank A/c Number / बैंक खाता संख्या</td><td>{!! $comb($v('account_number'), 20) !!}</td></tr>
        <tr><td class="l">Bank Name / बैंक का नाम</td><td><b>{{ $v('bank_name') }}</b></td></tr>
        <tr><td class="l">Branch Name / शाखा</td><td><b>{{ $v('branch_name') }}</b></td></tr>
        <tr><td class="l">Branch Address / शाखा का पता</td><td><b>{{ $v('branch_address') }}</b></td></tr>
        <tr><td class="l">IFSC Code / आईएफएससी कोड</td><td>{!! $comb($v('ifsc'), 11) !!}</td></tr>
        <tr><td class="l">MICR Code / एमआईसीआर कोड</td><td>{!! $comb($v('micr'), 9) !!}</td></tr>
    </table>

    {{-- 8. NOMINATION --}}
    @if($tbl)
        <div class="band">8. SUBSCRIBERS NOMINATION DETAILS / नामांकन विवरण</div>
        @php $rows = $data['_tables'][$tbl['key']] ?? []; @endphp
        <table class="grid">
            <thead><tr><th style="width:6%;">#</th>@foreach($tbl['columns'] as $c)<th>{!! $c['label'] !!}</th>@endforeach</tr></thead>
            <tbody>
                @forelse($rows as $i => $row)
                    <tr><td style="text-align:center;">{{ $i+1 }}</td>@foreach($tbl['columns'] as $c)<td>{{ $row[$c['name']] ?? '' }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ count($tbl['columns'])+1 }}" style="text-align:center;">—</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    {{-- 10. PF & INVESTMENT --}}
    <div class="band">10. PENSION FUND (PF) SELECTION &amp; INVESTMENT OPTION / पेंशन निधि चयन एवं निवेश विकल्प</div>
    <table class="kv">
        <tr><td class="l">Pension Fund Manager (PFM) / पेंशन निधि प्रबंधक</td><td><b>{{ $v('pension_fund') }}</b></td></tr>
        <tr><td class="l">Investment Option / निवेश विकल्प</td><td>{!! $ticks('investment_option', $opt('investment_option')) !!}</td></tr>
        <tr><td class="l">Tier II Account? / टियर II खाता?</td><td>{!! $ticks('tier_ii', $opt('tier_ii')) !!}</td></tr>
    </table>

    {{-- 11. FATCA --}}
    <div class="band">11. DECLARATION ON FATCA COMPLIANCE / एफएटीसीए अनुपालन घोषणा</div>
    <table class="kv">
        <tr><td class="l">Tax resident of any country other than India? / क्या भारत के अतिरिक्त किसी अन्य देश के कर निवासी हैं?</td><td>{!! $ticks('tax_resident_outside', $opt('tax_resident_outside')) !!}</td></tr>
    </table>

    {{-- 12. DECLARATION --}}
    <div class="band">12. DECLARATION BY SUBSCRIBER / अंशदाता द्वारा घोषणा</div>
    <div class="decl">{!! $template['declaration'] !!}</div>
    @foreach($template['sections_footer'] ?? [] as $section)
        <table class="kv" style="border-top:0.6px solid #b9c7de; margin-top:3px;">
            @foreach($section['fields'] as $f)
                <tr><td class="l" style="width:18%;">{!! $f['label'] !!}</td><td><b>{{ ($f['type']??'')==='date' ? $fmt($data[$f['name']] ?? '') : ($data[$f['name']] ?? '') }}</b></td></tr>
            @endforeach
        </table>
    @endforeach

    @if(!empty($template['signatures']))
        <div class="sign">
            @if($sigSrc)<div><img src="{{ $sigSrc }}" class="sig-img"></div>@endif
            <div class="sign-line">{!! $template['signatures'][0] !!}</div>
        </div>
    @endif
</body>
</html>
