{{-- Standalone print window: same markup as PDF / main report body (no admin chrome). --}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sale Voucher Report - OFFICER'S MESS LBSNAA MUSSOORIE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 13px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .print-page-wrap {
            page-break-after: auto;
        }

        .print-page-break {
            page-break-after: always;
        }

        .print-grand-total-block {
            display: block;
            margin-top: 12px;
            page-break-inside: avoid;
        }

        .report-mess-title {
            color: #000;
            font-size: 1.35rem;
            font-weight: bold;
        }

        .report-title-bar {
            background-color: #004a93;
            color: #fff;
            padding: 8px 12px;
            font-size: 1rem;
            margin-top: 6px;
        }

        .lbsnaa-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #004a93;
            margin-bottom: 10px;
            padding: 2px 0 8px;
            gap: 12px;
        }

        .lbsnaa-brand-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 0;
        }

        .lbsnaa-logo-wrap {
            width: 40px;
            flex: 0 0 40px;
            display: flex;
            justify-content: flex-start;
        }

        .lbsnaa-header-logo {
            width: 34px;
            height: 34px;
            object-fit: contain;
        }

        .lbsnaa-brand-lines {
            text-align: left;
            line-height: 1.25;
            min-width: 0;
        }

        .lbsnaa-brand-right {
            width: 200px;
            display: flex;
            justify-content: flex-end;
        }

        .lbsnaa-header-logo-right {
            width: 168px;
            height: auto;
        }

        .lbsnaa-brand-line-1 {
            font-size: 0.72rem;
            color: #004a93;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .lbsnaa-brand-line-2 {
            font-size: 1.08rem;
            color: #222;
            font-weight: 700;
            margin-top: 2px;
        }

        .lbsnaa-brand-line-3 {
            font-size: 0.84rem;
            color: #555;
            margin-top: 2px;
        }

        .report-details-row {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }

        .report-buyer-label,
        .report-client-type {
            font-weight: 500;
        }

        .print-slip-table thead th {
            border-color: #8eb8d0 !important;
            color: #1a1a1a;
            font-weight: 600;
            padding: 8px 6px;
        }

        .table-responsive {
            overflow-x: visible !important;
        }

        .print-slip-table {
            table-layout: fixed;
            width: 100%;
        }

        .print-slip-table th,
        .print-slip-table td {
            white-space: normal !important;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .print-slip-table .th-buyer,
        .print-slip-table .buyer-name-cell {
            width: 22%;
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

        .print-slip-table tbody td {
            padding: 6px 8px;
            vertical-align: middle;
        }

        .print-slip-table .total-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .print-slip-table .grand-total-row {
            background-color: #e2e8f0;
            font-weight: bold;
            border-top: 2px solid #004a93;
        }

        @page {
            size: A4;
            margin: 12mm;
        }

        @media print {
            body {
                font-size: 13px;
                background: #fff !important;
            }

            .report-header {
                margin-top: 0;
                margin-bottom: 12px;
                padding-bottom: 8px;
                border-bottom: 2px solid #2c3e50;
            }

            .lbsnaa-header-logo {
                width: 34px;
                height: 34px;
            }

            .lbsnaa-header-logo-right {
                width: 170px;
            }

            .report-mess-title {
                font-size: 18px;
                font-weight: 700;
                color: #1a1a1a;
                letter-spacing: 0.5px;
            }

            .report-title-bar {
                font-size: 13px;
                padding: 8px 14px;
                margin-top: 6px;
                background: #2c3e50 !important;
                color: #fff !important;
                border-radius: 2px;
                letter-spacing: 0.3px;
            }

            .report-details-row {
                padding: 8px 10px;
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 3px;
                margin-bottom: 10px;
            }

            .print-slip-section {
                page-break-inside: avoid;
                margin-bottom: 14px;
            }

            .print-grand-total-block {
                display: block !important;
                margin-top: 12px;
                page-break-inside: avoid;
            }

            .print-slip-table {
                font-size: 12px;
                border-collapse: collapse;
            }

            .print-slip-table thead tr {
                background: #2c3e50 !important;
                color: #fff !important;
            }

            .print-slip-table thead th {
                border: 1px solid #1a252f !important;
                padding: 8px 6px !important;
                font-weight: 600;
            }

            .print-slip-table tbody td {
                padding: 6px 8px !important;
                border: 1px solid #dee2e6;
            }

            .print-slip-table .total-row {
                background: #e9ecef !important;
                font-weight: bold;
                border-top: 2px solid #2c3e50;
            }

            .print-slip-table .grand-total-row {
                background: #d8e4ef !important;
                font-weight: bold;
                border-top: 3px solid #004a93;
            }
        }
    </style>
</head>
<body class="p-3">
    <div class="container-fluid">
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
        ])
    </div>
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 300);
        });
    </script>
</body>
</html>
