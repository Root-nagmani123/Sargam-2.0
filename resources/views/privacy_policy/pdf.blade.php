{{-- "Download" PDF (DomPDF). Same header as every other module's PDF (admin.master.partials.export_pdf):
     DomPDF needs the logos as base64 data rather than asset() URLs. Page numbers are stamped by
     PrivacyPolicyController::download() through the canvas API. --}}
@php
    $logoFor = function (string $relative): ?string {
        $path = public_path($relative);
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }
        $mime = str_ends_with(strtolower($relative), '.png') ? 'image/png' : 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
    };

    $emblem = $logoFor('images/ashoka.png');
    $logo = $logoFor('images/lbsnaa_logo.jpg');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Privacy Policy – SARGAM 2.0 — LBSNAA</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 10mm 16mm; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; padding: 0; color: #1f2937; font-size: 9px; }

        table.pdf-hdr { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        table.pdf-hdr td { vertical-align: middle; padding: 0; }
        table.pdf-hdr .logo { width: 120px; }
        table.pdf-hdr .logo img { height: 44px; }
        table.pdf-hdr .centre { text-align: center; }

        .inst { font-size: 12px; font-weight: bold; color: #003366; line-height: 1.3; }
        .sub  { font-size: 8px; color: #4b5563; margin-top: 2px; }
        .rule { border-bottom: 2px solid #003366; margin-bottom: 6px; }

        .report-title { text-align: center; font-size: 12px; font-weight: bold; color: #003366; margin: 5px 0 2px; }
        .meta  { text-align: center; font-size: 8px; color: #6b7280; margin-bottom: 8px; }
        .foot  { margin-top: 8px; text-align: center; font-size: 7px; color: #6b7280; }
    </style>
    @include('privacy_policy._export_styles')
</head>
<body>

    <table class="pdf-hdr">
        <tr>
            <td class="logo">
                @if($emblem)<img src="{{ $emblem }}" alt="">@endif
                @if($logo)<img src="{{ $logo }}" alt="">@endif
            </td>
            <td class="centre">
                <div class="inst">LAL BAHADUR SHASTRI NATIONAL ACADEMY OF ADMINISTRATION</div>
                <div class="sub">Mussoorie, Uttarakhand &nbsp;|&nbsp; Sargam 2.0</div>
            </td>
            <td class="logo"></td>
        </tr>
    </table>

    <div class="rule"></div>

    <div class="report-title">PRIVACY POLICY – SARGAM 2.0</div>
    <div class="meta">Last Updated: {{ $lastUpdated }} &nbsp;|&nbsp; Generated: {{ $exportDate }}</div>

    @include('privacy_policy._content')

    <div class="foot">Sargam 2.0 · Privacy Policy · Lal Bahadur Shastri National Academy of Administration</div>
</body>
</html>
