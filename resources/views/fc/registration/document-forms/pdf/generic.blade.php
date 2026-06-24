@php
    $fmt = function ($d) {
        if (! $d) return '';
        try { return \Carbon\Carbon::parse($d)->format('d-m-Y'); } catch (\Throwable $e) { return $d; }
    };
    $show = function ($f, $value) use ($fmt) {
        if (($f['type'] ?? 'text') === 'date') return $fmt($value);
        return $value;
    };
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #000; }
    .form-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px; }
    .form-title { font-size: 15px; font-weight: bold; }
    .form-subtitle { font-size: 10px; color: #444; margin-top: 3px; }
    .hindi-title { font-size: 13px; font-weight: bold; margin-top: 6px; }
    .section-header { background-color: #f0f0f0; border: 1px solid #999; padding: 5px 8px; margin: 14px 0 8px; font-weight: bold; font-size: 12px; }
    table.kv { width: 100%; border-collapse: collapse; }
    table.kv td { padding: 4px 4px; font-size: 11px; vertical-align: top; }
    table.kv td.lbl { font-weight: bold; width: 32%; }
    table.kv td.val { border-bottom: 1px solid #ccc; }
    table.grid { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.grid th, table.grid td { border: 1px solid #999; padding: 5px; font-size: 10px; vertical-align: top; }
    table.grid th { background-color: #e8e8e8; text-align: center; }
    .declaration { border: 1px solid #999; padding: 8px; margin: 16px 0; background-color: #fafafa; font-size: 10px; line-height: 1.5; }
    .sign-table { width: 100%; margin-top: 35px; }
    .sign-table td { text-align: center; font-size: 10px; padding-top: 6px; border-top: 1px solid #333; }
</style>
</head>
<body>
    <div class="form-header">
        <div class="form-title">{{ $template['title'] }}</div>
        @if(! empty($template['subtitle']))<div class="form-subtitle">{{ $template['subtitle'] }}</div>@endif
        @if(! empty($template['title_hi']))<div class="hindi-title">{{ $template['title_hi'] }}</div>@endif
    </div>

    {{-- Header sections as label/value rows --}}
    @foreach($template['sections'] ?? [] as $section)
        <div class="section-header">{!! $section['heading'] !!}</div>
        <table class="kv">
            @foreach($section['fields'] as $f)
                <tr>
                    <td class="lbl">{!! $f['label'] !!}</td>
                    <td class="val">{{ $show($f, $data[$f['name']] ?? '') }}</td>
                </tr>
            @endforeach
        </table>
    @endforeach

    {{-- Repeatable tables --}}
    @foreach($template['tables'] ?? [] as $tbl)
        <div class="section-header">{!! $tbl['heading'] !!}</div>
        @php $rows = $data['_tables'][$tbl['key']] ?? []; @endphp
        <table class="grid">
            <thead>
                <tr>
                    <th style="width:6%;">S.No.</th>
                    @foreach($tbl['columns'] as $c)<th>{!! $c['label'] !!}</th>@endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $i => $row)
                    <tr>
                        <td style="text-align:center;">{{ $i + 1 }}</td>
                        @foreach($tbl['columns'] as $c)
                            <td>{{ $show($c, $row[$c['name']] ?? '') }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($tbl['columns']) + 1 }}" style="text-align:center;">—</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    {{-- Declaration --}}
    @if(! empty($template['declaration']))
        <div class="declaration"><strong>Declaration / घोषणा:</strong><br>{!! $template['declaration'] !!}</div>
    @endif

    {{-- Footer fields (place / date) inline --}}
    @foreach($template['sections_footer'] ?? [] as $section)
        <table class="kv">
            @foreach($section['fields'] as $f)
                <tr>
                    <td class="lbl" style="width:18%;">{!! $f['label'] !!}</td>
                    <td class="val">{{ $show($f, $data[$f['name']] ?? '') }}</td>
                </tr>
            @endforeach
        </table>
    @endforeach

    {{-- Signatures (with uploaded signature image above the line, if provided) --}}
    @if(! empty($template['signatures']))
        <table class="sign-table">
            <tr>
                @foreach($template['signatures'] as $i => $sig)
                    <td style="width:{{ floor(100 / count($template['signatures'])) }}%; vertical-align:bottom; border-top:none; padding:0 6px;">
                        <div style="height:48px; text-align:center;">
                            @if(! empty($data['_signature_src'][$i]))
                                <img src="{{ $data['_signature_src'][$i] }}" style="max-height:46px; max-width:90%;">
                            @endif
                        </div>
                        <div style="border-top:1px solid #333; padding-top:4px; text-align:center; font-size:10px;">{!! $sig !!}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif
</body>
</html>
