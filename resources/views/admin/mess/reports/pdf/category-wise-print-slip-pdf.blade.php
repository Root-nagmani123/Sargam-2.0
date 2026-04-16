<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sale Voucher Report - OFFICER'S MESS LBSNAA MUSSOORIE</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        /* Match standalone print view (category-wise-print-slip-print) for PDF output */
        html, body {
            height: auto !important;
            min-height: 0 !important;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 12px;
            color: #222;
            background: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .cw-slip-pdf-inner {
            max-width: 100%;
        }

        /* Partial uses Bootstrap class names; PDF has no Bootstrap CSS */
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 14px; }
        .mt-2 { margin-top: 8px; }
        .text-center { text-align: center; }
        .text-muted { color: #6c757d; }
        .align-middle { vertical-align: middle; }

        .print-page-wrap {
            page-break-after: auto;
            break-inside: auto;
            page-break-inside: auto;
        }

        .print-page-break {
            page-break-after: always;
            break-after: page;
        }

        .print-grand-total-block {
            display: block;
            margin-top: 12px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .report-buyer-client-banner {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .report-header {
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #2c3e50;
            text-align: center;
        }

        .report-mess-title {
            color: #1a1a1a;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin: 0 0 4px 0;
        }

        .report-title-bar {
            background-color: #2c3e50;
            color: #fff;
            padding: 8px 14px;
            font-size: 13px;
            margin-top: 6px;
            border-radius: 2px;
            letter-spacing: 0.3px;
        }

        .lbsnaa-brand-line-1 {
            font-size: 11.5px;
            color: #004a93;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .lbsnaa-brand-line-2 {
            font-size: 17px;
            color: #222;
            font-weight: 700;
            margin-top: 2px;
        }

        .lbsnaa-brand-line-3 {
            font-size: 13.5px;
            color: #555;
            margin-top: 2px;
        }

        /* Default (e.g. non-table layout); PDF dompdf path uses .lbsnaa-branding-table overrides below */
        .lbsnaa-header-logo {
            width: 34px;
            height: 34px;
            object-fit: contain;
            display: block;
        }

        .lbsnaa-header-logo-right {
            width: 96px;
            max-width: 100%;
            max-height: 44px;
            height: auto;
            object-fit: contain;
            display: block;
            margin-left: auto;
        }

        .print-slip-section {
            margin-bottom: 14px;
            page-break-inside: auto;
            break-inside: auto;
        }

        .print-slip-table thead {
            display: table-header-group;
        }
        .print-slip-table tfoot {
            display: table-footer-group;
        }

        .table-responsive {
            width: 100%;
            overflow: visible;
        }

        .print-slip-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 8px;
            table-layout: fixed;
        }

        .print-slip-table th,
        .print-slip-table td {
            padding: 6px 8px;
            border: 1px solid #dee2e6;
            vertical-align: middle;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .print-slip-table thead th {
            background: #2c3e50;
            color: #fff;
            font-weight: 600;
            border: 1px solid #1a252f;
            padding: 8px 6px;
        }

        .print-slip-table .th-slip-no,
        .print-slip-table .th-date {
            text-align: center;
        }

        .print-slip-table .th-qty,
        .print-slip-table .th-price,
        .print-slip-table .th-amount {
            text-align: right;
        }

        .print-slip-table .th-item {
            width: 22%;
        }
        .print-slip-table .th-remark {
            width: 14%;
        }

        .print-slip-table .text-center {
            text-align: center;
        }

        .print-slip-table .text-end {
            text-align: right;
        }

        .print-slip-table .total-row {
            background: #e9ecef;
            font-weight: bold;
            border-top: 2px solid #2c3e50;
        }

        .print-slip-table .grand-total-row {
            background: #d8e4ef;
            font-weight: bold;
            border-top: 3px solid #004a93;
        }

        .cw-slip-empty {
            padding: 12px;
            border: 1px solid #ccc;
            background: #f8f9fa;
        }

        .alert {
            padding: 12px;
            margin-bottom: 0;
            border-radius: 4px;
        }

        .alert-info {
            background: #cff4fc;
            border: 1px solid #9eeaf9;
            color: #055160;
        }

        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #664d03;
        }

        /* Dompdf-safe header: same visual as print flex row (emblem | left text | right logo) */
        .lbsnaa-branding-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #004a93;
            margin-bottom: 10px;
            padding: 2px 0 8px;
        }
        .lbsnaa-branding-table td {
            border: 0;
            vertical-align: middle;
        }
        .lbsnaa-branding-table td.lbsnaa-branding-emblem {
            width: 40px;
            padding-right: 12px;
        }
        .lbsnaa-branding-table td.lbsnaa-branding-lines {
            text-align: left;
            line-height: 1.25;
            padding: 0 12px 0 0;
        }
        .lbsnaa-branding-table td.lbsnaa-branding-logo {
            width: 150px;
            text-align: right;
            white-space: nowrap;
        }

        /*
         * Dompdf often stretches images to both width+height when both are set; the partial
         * stacks .lbsnaa-header-logo (34×34) + .lbsnaa-header-logo-right on the LBSNAA img.
         * Scope explicit sizing so the emblem stays square and the academy logo keeps aspect ratio.
         */
        .lbsnaa-branding-table .lbsnaa-branding-emblem img {
            width: 34px !important;
            height: 34px !important;
            max-width: 40px !important;
            max-height: 40px !important;
            object-fit: contain;
        }

        .lbsnaa-branding-table .lbsnaa-branding-logo img {
            width: auto !important;
            height: auto !important;
            max-width: 100% !important;
            max-height: 48px !important;
            object-fit: contain;
            display: block;
            margin-left: auto;
        }
    </style>
</head>
<body class="cw-slip-pdf-body">
<div class="cw-slip-pdf-inner container-fluid">
@include('admin.mess.reports.partials.category-wise-print-slip-body', [
    'sectionsToShow' => $sectionsToShow,
    'fromDateFormatted' => $fromDateFormatted ?? 'Start',
    'toDateFormatted' => $toDateFormatted ?? 'End',
    'otCourses' => $otCourses ?? collect(),
    'grandTotal' => $grandTotal ?? 0,
    'filtersApplied' => true,
    'printPageBreakPerBuyer' => true,
    'showBrandingHeader' => true,
    'emblemSrc' => $emblemSrc ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png',
    'lbsnaaLogoSrc' => $lbsnaaLogoSrc ?? 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png',
    'dompdfSafeTables' => true,
])
</div>
</body>
</html>
