@php
    $fmt = function ($d) {
        if (! $d) return '';
        try { return \Carbon\Carbon::parse($d)->format('d-m-Y'); } catch (\Throwable $e) { return $d; }
    };
    $splitHeading = function ($h) {
        $parts = array_map('trim', explode('/', $h, 2));
        return [$parts[0] ?? $h, $parts[1] ?? ''];
    };
    // Comb box: value into single-character cells (sizes to the value, min cells).
    $comb = function ($value, $min = 16) {
        $value = strtoupper((string) $value);
        $len = max($min, mb_strlen($value));
        $out = '<table style="border-collapse:collapse;"><tr>';
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($value, $i, 1);
            $out .= '<td style="border:0.5px solid #666; width:12px; height:14px; text-align:center; font-size:8.5px; font-weight:bold; color:#0b3d91;">'.($ch !== '' ? e($ch) : '&nbsp;').'</td>';
        }
        $out .= '</tr></table>';
        return $out;
    };
    // Tick options (☑ / ☐) for a select value.
    $ticks = function ($options, $sel) {
        $out = '';
        foreach ($options as $o) {
            $mark = ((string) $o === (string) $sel) ? '&#9745;' : '&#9744;';
            $out .= '<span style="margin-right:12px; font-size:8.6px; white-space:nowrap;">'.$mark.' '.e($o).'</span>';
        }
        return $out;
    };
    $sigSrc = $data['_signature_src'][0] ?? null;
    $sno = 0;
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #000; }
    .formtag { text-align: right; font-weight: bold; font-size: 9px; }
    .head { text-align: center; margin-bottom: 6px; }
    .head .t { font-size: 14px; font-weight: bold; text-decoration: underline; }
    .head .s { font-size: 9px; color: #333; margin-top: 2px; }
    .head .h { font-size: 12px; font-weight: bold; margin-top: 3px; }
    table.ddo { width: 100%; border-collapse: collapse; margin: 6px 0 8px; }
    table.ddo td { border-bottom: 1px solid #000; font-size: 9px; font-weight: bold; padding: 3px 4px; }
    table.eis { width: 100%; border-collapse: collapse; }
    table.eis th, table.eis td { border: 0.6px solid #555; padding: 3px 5px; font-size: 9.5px; vertical-align: middle; }
    table.eis thead th { background-color: #e8eef8; text-align: center; font-weight: bold; }
    .sec { background-color: #f2f6fc; text-align: center; font-weight: bold; font-size: 8.5px; color: #14315e; width: 20px; }
    .sno { text-align: center; width: 30px; }
    .part { width: 44%; }
    .sign-wrap { margin-top: 26px; text-align: right; }
    .sign-line { display: inline-block; border-top: 1px solid #000; padding-top: 3px; font-size: 9.5px; min-width: 220px; text-align: center; }
    .sig-img { max-height: 38px; max-width: 200px; }
</style>
</head>
<body>
    <div class="formtag">Form: EIS/B/1</div>
    <div class="head">
        <div class="t">{{ $template['title'] }}</div>
        @if(! empty($template['subtitle']))<div class="s">{{ $template['subtitle'] }}</div>@endif
        @if(! empty($template['title_hi']))<div class="h">{{ $template['title_hi'] }}</div>@endif
    </div>
    <table class="ddo">
        <tr>
            <td style="width:60%;">DDO Code &amp; Name / डीडीओ कोड एवं नाम:</td>
            <td style="width:40%;">Date / दिनांक:</td>
        </tr>
    </table>

    <table class="eis">
        <thead>
            <tr>
                <th style="width:20px;">A</th>
                <th class="sno">SNo</th>
                <th class="part">Particulars / विवरण</th>
                <th>Details / ब्योरा</th>
            </tr>
        </thead>
        <tbody>
            @foreach($template['sections'] as $section)
                @php [$secEn, $secHi] = $splitHeading($section['heading']); $rows = $section['fields']; @endphp
                @foreach($rows as $idx => $f)
                    @php $sno++; $type = $f['type'] ?? 'text'; $v = $data[$f['name']] ?? ''; @endphp
                    <tr>
                        @if($idx === 0)
                            <td class="sec" rowspan="{{ count($rows) }}" style="text-rotate: 90;">{!! $secEn !!}</td>
                        @endif
                        <td class="sno">{{ $sno }}</td>
                        <td class="part">{!! $f['label'] !!}</td>
                        <td>
                            @if($type === 'select')
                                {!! $ticks($f['options'] ?? [], $v) !!}
                            @elseif($type === 'date')
                                <span style="font-weight:bold; color:#0b3d91;">{{ $fmt($v) }}</span>
                            @elseif($type === 'email')
                                <span style="font-weight:bold; color:#0b3d91;">{{ $v }}</span>
                            @else
                                {!! $comb($v) !!}
                            @endif
                        </td>
                    </tr>
                @endforeach
            @endforeach

            @foreach($template['sections_footer'] ?? [] as $section)
                @foreach($section['fields'] as $idx => $f)
                    @php $sno++; $type = $f['type'] ?? 'text'; $v = $data[$f['name']] ?? ''; @endphp
                    <tr>
                        @if($idx === 0)
                            <td class="sec" rowspan="{{ count($section['fields']) }}" style="text-rotate: 90;">Place &amp; Date</td>
                        @endif
                        <td class="sno">{{ $sno }}</td>
                        <td class="part">{!! $f['label'] !!}</td>
                        <td>
                            @if($type === 'date')
                                <span style="font-weight:bold; color:#0b3d91;">{{ $fmt($v) }}</span>
                            @else
                                {!! $comb($v) !!}
                            @endif
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    @if(! empty($template['signatures']))
        <div class="sign-wrap">
            @if($sigSrc)<div><img src="{{ $sigSrc }}" class="sig-img"></div>@endif
            <div class="sign-line">{!! $template['signatures'][0] !!}</div>
        </div>
    @endif
</body>
</html>
