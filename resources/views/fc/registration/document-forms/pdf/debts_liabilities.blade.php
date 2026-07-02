@php
    $fmt = function ($d) {
        if (! $d) return '';
        try { return \Carbon\Carbon::parse($d)->format('d-m-Y'); } catch (\Throwable $e) { return $d; }
    };
    $g       = fn ($k) => trim((string) ($data[$k] ?? ''));
    $name    = $g('officer_name');
    $service = $g('service');
    $ason    = $fmt($data['as_on_date'] ?? '');
    $dated   = $fmt($data['declaration_date'] ?? '');
    $rows    = $data['_tables']['liabilities'] ?? [];
    $sigSrc  = $data['_signature_src'][0] ?? null;
    $blank = function ($v, $w = '150px') {
        $val = ($v !== '' && $v !== null) ? e($v) : '&nbsp;';
        return '<span style="display:inline-block; min-width:'.$w.'; border-bottom:1px solid #000; text-align:center; font-weight:bold; padding:0 4px;">'.$val.'</span>';
    };
    $notesEn = [
        'Individual items of loans not exceeding three months\' emoluments or Rs. 1,000, whichever is less, need not be included.',
        'In Column 6, information regarding permission, if any, obtained from or report made to the competent authority may also be given.',
        'The term "emoluments" means pay and allowances received by the Government servant.',
        'The statement should also include various loans and advances available to Government servants, such as advance for purchase of conveyance, house building advance, etc. (other than advances of pay and travelling allowance, advances from the GP Fund, and loans on Life Insurance Policies and fixed deposits).',
    ];
    $notesHi = [
        'यदि एक ऋणराशि तीन महीनों की परिलब्धियों अथवा 1000 रुपए से अधिक न हो, तो उसे शामिल करने की आवश्यकता नहीं है।',
        'कॉलम 6 में, यदि सक्षम अधिकारी से अनुमति ली गई हो या उसकी सूचना दी गई हो, तो उसका विवरण भी दें।',
        'परिलब्धियों का तात्पर्य सरकारी कर्मचारी द्वारा प्राप्त वेतन और भत्तों से है।',
        'विवरण में सरकारी कर्मचारियों को उपलब्ध विभिन्न ऋण, यथा आवागमन हेतु उपलब्ध अग्रिम, गृह निर्माण अग्रिम आदि (अन्य अग्रिमों, यथा यात्रा अग्रिम, भविष्य निधि अग्रिम तथा जीवन बीमा पालिसी और आवधिक जमा से लिए गए ऋणों के अतिरिक्त) को भी शामिल किया जाना चाहिए।',
    ];
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #000; }
    .docno { text-align: right; font-weight: bold; font-size: 11px; }
    .title { text-align: center; font-weight: bold; font-size: 13px; margin-top: 4px; }
    .sub { text-align: center; font-style: italic; font-size: 10px; margin-top: 3px; }
    table.dc { width: 100%; border-collapse: collapse; margin-top: 12px; }
    table.dc th, table.dc td { border: 0.8px solid #000; padding: 5px 6px; font-size: 9.5px; vertical-align: top; }
    table.dc th { text-align: center; font-weight: bold; }
    .cno { font-weight: normal; }
    .foot { width: 100%; margin-top: 22px; font-size: 11px; }
    .foot td { vertical-align: top; padding: 4px 0; }
    .sig-img { max-height: 34px; }
    .notes { margin-top: 16px; font-size: 9px; line-height: 1.5; }
    .notes td { vertical-align: top; padding: 1px 4px 1px 0; }
    .rowspace td { height: 26px; }
</style>
</head>
<body>

    {{-- ═══════════ ENGLISH (page 1) ═══════════ --}}
    <div class="docno">Document-6-C</div>
    <div class="title">Statement of Debts and Other Liabilities on First Appointment as on date {!! $blank($ason, '150px') !!}</div>

    <table class="dc">
        <thead>
            <tr>
                <th style="width:6%;">Sl.No.<div class="cno">(1)</div></th>
                <th style="width:14%;">Amount<div class="cno">(2)</div></th>
                <th>Name and address of Creditor<div class="cno">(3)</div></th>
                <th style="width:16%;">Date of incurring Liability<div class="cno">(4)</div></th>
                <th>Details of Transaction<div class="cno">(5)</div></th>
                <th style="width:16%;">Remarks<div class="cno">(6)</div></th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $r)
                <tr>
                    <td style="text-align:center;">{{ $i + 1 }}</td>
                    <td>{{ $r['amount'] ?? '' }}</td>
                    <td>{{ $r['creditor'] ?? '' }}</td>
                    <td>{{ $fmt($r['date_incurred'] ?? '') }}</td>
                    <td>{{ $r['details'] ?? '' }}</td>
                    <td>{{ $r['remarks'] ?? '' }}</td>
                </tr>
            @empty
                @for($i = 0; $i < 4; $i++)<tr class="rowspace"><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>@endfor
            @endforelse
        </tbody>
    </table>

    <table class="foot">
        <tr>
            <td style="width:45%;">Dated: {!! $blank($dated, '160px') !!}</td>
            <td>
                <div>Signature: @if($sigSrc)<img src="{{ $sigSrc }}" class="sig-img">@else {!! $blank('', '220px') !!} @endif</div>
                <div style="margin-top:6px;">Name: {!! $blank($name, '220px') !!}</div>
                <div style="text-align:center; font-size:9px;">(In Block Letters)</div>
                <div>Service: {!! $blank($service, '220px') !!}</div>
            </td>
        </tr>
    </table>

    <table class="notes">
        @foreach($notesEn as $i => $n)
            <tr><td style="width:52px;"><b>NOTE {{ $i + 1 }}.</b></td><td>{{ $n }}</td></tr>
        @endforeach
    </table>

    <pagebreak />

    {{-- ═══════════ HINDI (page 2) ═══════════ --}}
    <div class="docno">दस्तावेज़-6-सी</div>
    <div class="title">प्रथम नियुक्ति पर ऋणों तथा अन्य देयताओं का विवरण, आज {!! $blank($ason, '150px') !!} की तारीख में</div>
    <div class="sub">[debts and other liabilities incurred by him/her directly or indirectly]</div>

    <table class="dc">
        <thead>
            <tr>
                <th style="width:6%;">क्र.सं.<div class="cno">(1)</div></th>
                <th style="width:14%;">धनराशि<div class="cno">(2)</div></th>
                <th>ऋणदाता का नाम और पता<div class="cno">(3)</div></th>
                <th style="width:16%;">दायित्व की तिथि<div class="cno">(4)</div></th>
                <th>लेन-देन का विवरण<div class="cno">(5)</div></th>
                <th style="width:16%;">टिप्पणी<div class="cno">(6)</div></th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $r)
                <tr>
                    <td style="text-align:center;">{{ $i + 1 }}</td>
                    <td>{{ $r['amount'] ?? '' }}</td>
                    <td>{{ $r['creditor'] ?? '' }}</td>
                    <td>{{ $fmt($r['date_incurred'] ?? '') }}</td>
                    <td>{{ $r['details'] ?? '' }}</td>
                    <td>{{ $r['remarks'] ?? '' }}</td>
                </tr>
            @empty
                @for($i = 0; $i < 4; $i++)<tr class="rowspace"><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>@endfor
            @endforelse
        </tbody>
    </table>

    <table class="foot">
        <tr>
            <td style="width:45%;">दिनांक: {!! $blank($dated, '160px') !!}</td>
            <td>
                <div>हस्ताक्षर: {!! $blank('', '220px') !!}</div>
                <div style="margin-top:6px;">नाम (स्पष्ट अक्षरों में): {!! $blank($name, '200px') !!}</div>
                <div>सेवा: {!! $blank($service, '220px') !!}</div>
            </td>
        </tr>
    </table>

    <table class="notes">
        @foreach($notesHi as $i => $n)
            <tr><td style="width:52px;"><b>नोट {{ $i + 1 }}.</b></td><td>{{ $n }}</td></tr>
        @endforeach
    </table>

</body>
</html>
