@php
    $fmtDate = function ($d) {
        if (! $d) return '';
        try { return \Carbon\Carbon::parse($d)->format('d-m-Y'); } catch (\Throwable $e) { return $d; }
    };
    $name    = trim((string) ($data['officer_name'] ?? ''));
    $service = trim((string) ($data['service'] ?? ''));
    $dated   = $fmtDate($data['declaration_date'] ?? '');
    $sigSrc  = $data['_signature_src'][0] ?? null;

    // A ruled fill-in-the-blank: shows the value (or an empty ruled space).
    $blank = function ($value, $minWidth = '180px') {
        $v = $value !== '' && $value !== null ? e($value) : '&nbsp;';
        return '<span style="display:inline-block; min-width:'.$minWidth.'; border-bottom:1px solid #000; '
             . 'text-align:center; font-weight:bold; padding:0 4px; line-height:1.4;">'.$v.'</span>';
    };
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #000; line-height: 1.9; }
    .doc-title { text-align: center; font-weight: bold; font-size: 15px; text-decoration: underline; }
    .doc-subtitle { text-align: center; font-weight: bold; font-size: 9.5px; margin-top: 4px; }
    .doc-body { text-align: justify; margin-top: 26px; }
    .doc-god { text-align: center; font-weight: bold; margin-top: 20px; }
    .lines { margin-top: 22px; }
    .lines td { padding: 6px 0; vertical-align: bottom; }
    .lbl { font-weight: bold; white-space: nowrap; padding-right: 8px; width: 120px; }
    .doc-place { margin-top: 26px; }
    .doc-note { margin-top: 26px; font-size: 11px; }
    .doc-accepted { text-align: center; font-weight: bold; text-decoration: underline; margin-top: 30px; }
    .sig-img { max-height: 40px; max-width: 200px; }
</style>
</head>
<body>

    {{-- ─────────────────────────── PAGE 1 · ENGLISH ─────────────────────────── --}}
    <div class="doc-title">FORM OF OATH/AFFIRMATION</div>
    <div class="doc-subtitle">[MHA OM No. 31/3/65-Estt.(A) dated 23-3-1964 - as amended from time to time]</div>

    <div class="doc-body">
        &ldquo;I, {!! $blank($name, '250px') !!} (Name of the Probationer) do swear/solemnly affirm
        that I will be faithful and bear true allegiance to India and to the Constitution of India as
        by law established, that I will uphold the sovereignty and integrity of India, and that I will
        carry out the duties of my office loyally, honestly, and with impartiality.
    </div>
    <div class="doc-god">(SO HELP ME GOD)&rdquo;</div>

    <table class="lines" style="width:100%;">
        <tr>
            <td class="lbl">SIGNATURE</td>
            <td style="border-bottom:1px solid #000; text-align:center;">
                @if($sigSrc)<img src="{{ $sigSrc }}" class="sig-img">@else &nbsp; @endif
            </td>
        </tr>
        <tr>
            <td class="lbl">NAME</td>
            <td style="border-bottom:1px solid #000; text-align:center; font-weight:bold;">{{ $name ?: ' ' }}</td>
        </tr>
        <tr>
            <td></td>
            <td style="text-align:right; font-style:italic; font-weight:normal;">(In capital letters)</td>
        </tr>
        <tr>
            <td class="lbl">SERVICE</td>
            <td style="border-bottom:1px solid #000; text-align:center; font-weight:bold;">{{ $service ?: ' ' }}</td>
        </tr>
    </table>

    <div class="doc-place">Lal Bahadur Shastri National Academy of Administration, Mussoorie</div>
    <div style="margin-top:10px;">Dated {!! $blank($dated, '170px') !!}</div>

    <div class="doc-note">(Conscientious objectors of Oath-taking may make a solemn affirmation in the form indicated above).</div>
    <div class="doc-accepted">ACCEPTED</div>

    {{-- ─────────────────────────── PAGE 2 · HINDI ─────────────────────────── --}}
    <pagebreak />

    <div class="doc-title">शपथ / पुष्टि प्रपत्र</div>

    <div class="doc-body">
        &ldquo;मैं {!! $blank($name, '250px') !!} (परिवीक्षाधीन का नाम) शपथ लेता/लेती हूँ/सत्यनिष्ठा से
        प्रतिज्ञा करता/करती हूँ कि मैं भारत, तथा विधि द्वारा यथास्थापित भारत के संविधान के प्रति वफादार
        एवं सत्यनिष्ठ रहूँगा/रहूँगी, मैं भारत की प्रभुसत्ता एवं अखण्डता बनाये रखूँगा/रखूँगी तथा अपने पद के
        कर्तव्यों को निष्ठा, ईमानदारी एवं निष्पक्षता के साथ निभाऊँगा/निभाऊँगी।
    </div>
    <div class="doc-god">(भगवान मेरी सहायता करे)&rdquo;</div>

    <table class="lines" style="width:100%;">
        <tr>
            <td class="lbl">हस्ताक्षर</td>
            <td style="border-bottom:1px solid #000; text-align:center;">
                @if($sigSrc)<img src="{{ $sigSrc }}" class="sig-img">@else &nbsp; @endif
            </td>
        </tr>
        <tr>
            <td class="lbl">नाम</td>
            <td style="border-bottom:1px solid #000; text-align:center; font-weight:bold;">{{ $name ?: ' ' }}</td>
        </tr>
        <tr>
            <td class="lbl">सेवा</td>
            <td style="border-bottom:1px solid #000; text-align:center; font-weight:bold;">{{ $service ?: ' ' }}</td>
        </tr>
    </table>

    <div class="doc-place">लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी, मसूरी</div>
    <div style="margin-top:10px;">दिनांक {!! $blank($dated, '170px') !!}</div>

    <div class="doc-note">(शपथ ग्रहण के लिए नैतिक आपत्तिकर्ता उपर्युक्त प्रपत्र में सत्यनिष्ठ बनने हेतु प्रतिज्ञा करें)</div>
    <div class="doc-accepted">स्वीकृत</div>

</body>
</html>
