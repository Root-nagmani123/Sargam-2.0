<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sale Voucher Report</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11pt;
            margin: 0;
            padding: 0;
            color: #222;
            background: #fff;
        }

        .print-page-wrap {
            page-break-after: auto;
        }

        .print-page-break {
            page-break-after: always;
        }

        .print-grand-total-block {
            page-break-inside: avoid;
        }

        .report-mess-title {
            color: #000;
            font-size: 14pt;
            font-weight: bold;
            margin: 0 0 4px 0;
        }

        .report-title-bar {
            background-color: #004a93;
            color: #fff;
            padding: 8px 14px;
            font-size: 11pt;
            margin-top: 6px;
        }

        .report-header {
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #2c3e50;
        }

        .lbsnaa-header-row {
            display: table;
            width: 100%;
            border-bottom: 2px solid #004a93;
            margin-bottom: 10px;
            padding: 2px 0 8px;
        }

        .lbsnaa-brand-left {
            display: table-cell;
            width: auto;
            vertical-align: middle;
        }

        .lbsnaa-logo-wrap {
            display: table-cell;
            width: 40px;
            vertical-align: middle;
            text-align: left;
        }

        .lbsnaa-header-logo {
            width: 34px;
            height: 34px;
            object-fit: contain;
        }

        .lbsnaa-brand-lines {
            display: table-cell;
            vertical-align: middle;
            text-align: left;
            line-height: 1.25;
            padding: 0 10px 0 0;
        }

        .lbsnaa-brand-right {
            display: table-cell;
            width: 200px;
            text-align: right;
            vertical-align: middle;
        }

        .lbsnaa-header-logo-right {
            width: 165px;
            height: auto;
        }

        .lbsnaa-brand-line-1 {
            font-size: 8pt;
            color: #004a93;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .lbsnaa-brand-line-2 {
            font-size: 13pt;
            color: #222;
            font-weight: 700;
            margin-top: 2px;
        }

        .lbsnaa-brand-line-3 {
            font-size: 10pt;
            color: #555;
            margin-top: 2px;
        }

        .report-details-row {
            display: table;
            width: 100%;
            padding: 8px 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 3px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        .report-buyer-label {
            display: table-cell;
            font-weight: 600;
            width: 50%;
            vertical-align: middle;
        }

        .report-client-type {
            display: table-cell;
            font-weight: 600;
            text-align: right;
            vertical-align: middle;
        }

        .print-slip-section {
            margin-bottom: 14px;
        }

        .table-responsive {
            width: 100%;
            overflow: visible;
        }

        .print-slip-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
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

        .print-slip-table .th-buyer,
        .print-slip-table .buyer-name-cell {
            width: 22%;
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
    </style>
</head>
<body>
@include('admin.mess.reports.partials.category-wise-print-slip-body', [
    'sectionsToShow' => $sectionsToShow,
    'fromDateFormatted' => $fromDateFormatted ?? 'Start',
    'toDateFormatted' => $toDateFormatted ?? 'End',
    'otCourses' => $otCourses ?? collect(),
    'grandTotal' => $grandTotal ?? 0,
    'filtersApplied' => true,
    'printPageBreakPerBuyer' => true,
    'showBrandingHeader' => true,
    'emblemSrc' => $emblemSrc ?? null,
    'lbsnaaLogoSrc' => $lbsnaaLogoSrc ?? null,
])
</body>
</html>
