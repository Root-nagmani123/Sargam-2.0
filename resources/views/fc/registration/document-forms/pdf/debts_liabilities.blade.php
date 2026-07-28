@php
    $fmt = function ($d) {
        if (! $d) return '';
        try { return \Carbon\Carbon::parse($d)->format('d-m-Y'); } catch (\Throwable $e) { return $d; }
    };
    $g       = fn ($k) => trim((string) ($data[$k] ?? ''));
    $name    = $g('officer_name');
    $service = $g('service');
    $ason    = $fmt($data['as_on_date'] ?? '');
    $dated   = '24-08-2026';   // hard-frozen date, overrides any saved value
    $rows    = $data['_tables']['liabilities'] ?? [];
    $sigSrc  = $data['_signature_src'][0] ?? null;
    $blank = function ($v, $w = '150pt') {
        $val = ($v !== '' && $v !== null) ? e($v) : str_repeat('_', max(12, (int) round((strpos($w, 'mm') !== false ? (float) $w * 2.83465 : (float) $w) / 6)));
        return '<span style="display:inline-block; min-width:'.$w.'; border-bottom:1px solid #000; text-align:center; font-weight:bold; padding:0 4pt;">'.$val.'</span>';
    };
    // Official 6-C notes (bilingual pairs) — same content as the on-screen form.
    $notes = [
        ['Individual items of loans not exceeding three months\' emoluments or Rs. 1,000, whichever is less, need not be included.',
         'यदि एक ऋणराशि तीन महीनों की परिलब्धियों अथवा 1000 रुपए से अधिक न हो, तो उसे शामिल करने की आवश्यकता नहीं है।'],
        ['In Column 6, information regarding permission, if any, obtained from or report made to the competent authority may also be given.',
         'कॉलम 6 में, यदि सक्षम अधिकारी से अनुमति ली गई हो या उसकी सूचना दी गई हो, तो उसका विवरण भी दें।'],
        ['The term "emoluments" means pay and allowances received by the Government servant.',
         'परिलब्धियों का तात्पर्य सरकारी कर्मचारी द्वारा प्राप्त वेतन और भत्तों से है।'],
        ['The statement should also include various loans and advances available to Government servants, such as advance for purchase of conveyance, house building advance, etc. (other than advances of pay and travelling allowance, advances from the GP Fund, and loans on Life Insurance Policies and fixed deposits).',
         'विवरण में सरकारी कर्मचारियों को उपलब्ध विभिन्न ऋण, यथा आवागमन हेतु उपलब्ध अग्रिम, गृह निर्माण अग्रिम आदि (अन्य अग्रिमों, यथा यात्रा अग्रिम, भविष्य निधि अग्रिम तथा जीवन बीमा पालिसी और आवधिक जमा से लिए गए ऋणों के अतिरिक्त) को भी शामिल किया जाना चाहिए।'],
    ];
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 11mm 12mm 11mm 12mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11pt; color: #000; }
    .docno { text-align: right; font-weight: bold; font-size: 11pt; }
    .title { text-align: center; font-weight: bold; font-size: 13pt; text-decoration: underline; margin-top: 4pt; }
    .formno { text-align: center; font-weight: bold; font-size: 10pt; margin-top: 2pt; }
    .title-hi { text-align: center; font-weight: bold; font-size: 12pt; margin-top: 3pt; }
    .sub { text-align: center; font-style: italic; font-size: 10pt; margin-top: 3pt; }
    .ason { text-align: center; font-size: 11pt; margin-top: 10pt; }
    table.dc { width: 100%; border-collapse: collapse; margin-top: 12pt; }
    table.dc th, table.dc td { border: 0.8px solid #000; padding: 5pt 6pt; font-size: 9.5pt; vertical-align: top; }
    table.dc th { text-align: center; font-weight: bold; }
    .cno { font-weight: normal; }
    .foot { width: 100%; margin-top: 22pt; font-size: 11pt; }
    .foot td { vertical-align: top; padding: 4pt 0; }
    .sig-img { max-height: 34pt; }
    .notes { margin-top: 18pt; font-size: 9.5pt; line-height: 1.5; border-top: 1px solid #000; padding-top: 8pt; }
    .notes td { vertical-align: top; padding: 2pt 4pt 2pt 0; }
    .rowspace td { height: 26pt; }
</style>
</head>
<body>

    <div class="docno">Document-6-C / दस्तावेज़-6-सी</div>
    <div class="title" style="font-size:10.5pt; white-space:nowrap;">Statement of Debts and Other Liabilities on First Appointment<span style="text-decoration:none; font-weight:bold;">&nbsp; as on date / जिस तिथि तक: {!! $blank($ason, '120pt') !!}</span></div>
    <div class="formno">Form No. 6-C</div>
    <div class="title-hi">प्रथम नियुक्ति पर ऋणों तथा अन्य देयताओं का विवरण</div>
    <div class="sub">[debts and other liabilities incurred by him/her directly or indirectly]</div>

    <table class="dc">
        <thead>
            <tr>
                <th style="width:6%;">Sl.No. / क्र.सं.<div class="cno">(1)</div></th>
                <th style="width:14%;">Amount / धनराशि<div class="cno">(2)</div></th>
                <th>Name and address of Creditor / ऋणदाता का नाम और पता<div class="cno">(3)</div></th>
                <th style="width:16%;">Date of incurring Liability / दायित्व की तिथि<div class="cno">(4)</div></th>
                <th>Details of Transaction / लेन-देन का विवरण<div class="cno">(5)</div></th>
                <th style="width:16%;">Remarks / टिप्पणी<div class="cno">(6)</div></th>
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
            <td style="width:45%;">Dated / दिनांक: {!! $blank($dated, '160pt') !!}</td>
            <td>
                <div>Signature / हस्ताक्षर: @if($sigSrc)<img src="{{ $sigSrc }}" class="sig-img">@else {!! $blank('', '210pt') !!} @endif</div>
                <div style="margin-top:6pt;">Name (in Block Letters) / नाम (स्पष्ट अक्षरों में): {!! $blank($name, '200pt') !!}</div>
                <div style="margin-top:6pt;">Service / सेवा: {!! $blank($service, '210pt') !!}</div>
            </td>
        </tr>
    </table>

    <table class="notes">
        @foreach($notes as $i => $n)
            <tr><td style="width:52pt;"><b>NOTE {{ $i + 1 }}.</b></td><td>{{ $n[0] }}<br>{{ $n[1] }}</td></tr>
        @endforeach
    </table>

</body>
</html>
