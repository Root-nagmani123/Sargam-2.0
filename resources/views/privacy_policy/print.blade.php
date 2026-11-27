{{-- Printable Privacy Policy: opened by "Print" (auto-prints) and rendered by DomPDF for "Download".
     Plain CSS only - DomPDF ignores custom properties and grid. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Privacy Policy – SARGAM 2.0</title>
    <style>
        @page { margin: 18mm 16mm; }
        body { font-family: "DejaVu Sans", Arial, sans-serif; color: #1f2937; font-size: 11pt; line-height: 1.5; margin: 0; }
        .pp-head { border-bottom: 2px solid #004a93; padding-bottom: 8px; margin-bottom: 14px; }
        .pp-head .org { font-size: 9pt; color: #667085; }
        .pp-head h1 { font-size: 16pt; margin: 2px 0; color: #004a93; }
        .pp-head .updated { font-size: 9pt; color: #667085; }
        .pp-section { margin-bottom: 12px; page-break-inside: auto; }
        .pp-section h3 { font-size: 12pt; margin: 0 0 6px; page-break-after: avoid; }
        .pp-section h4 { font-size: 11pt; margin: 8px 0 4px; page-break-after: avoid; }
        .pp-section p { margin: 0 0 6px; }
        .pp-section ul { margin: 0 0 6px; padding-left: 18px; }
        .pp-contact { display: block; background: #eef3fb; border-left: 3px solid #004a93; padding: 8px 10px; font-style: normal; margin: 0 0 6px; }
        .pp-contact a { color: #004a93; }
        .pp-signoff { border-top: 1px solid #e5e7eb; padding-top: 8px; font-size: 9pt; color: #667085; }
    </style>
</head>
<body @if ($autoPrint) onload="window.print()" @endif>
    <div class="pp-head">
        <div class="org">Lal Bahadur Shastri National Academy of Administration (LBSNAA), Mussoorie, Uttarakhand, Government of India</div>
        <h1>Privacy Policy – SARGAM 2.0</h1>
        <div class="updated">Last Updated: {{ $lastUpdated }}</div>
    </div>
    @include('privacy_policy._content')
</body>
</html>
