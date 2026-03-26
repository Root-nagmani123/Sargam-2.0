<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Estate Bill - {{ $bill->bill_month ?? '' }} {{ $bill->bill_year ?? '' }}</title>
    <style>
        @page { margin: 5mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.3pt; color: #1a202c; line-height: 1.26; margin: 0; padding: 0; }
        .bill-doc {
            border: 1.5px solid #1e3a5f;
            margin: 0;
            page-break-inside: avoid;
            overflow: hidden;
        }
        .bill-header {
            background: #fff;
            color: #1e3a5f;
            text-align: center;
            padding: 10px 15px 11px;
            position: relative;
        }
        .bill-header-main { margin: 0; padding: 0 86px 0 6px; }
        .bill-header .org-name { font-size: 10.8pt; font-weight: 700; margin: 0 0 2px 0; }
        .bill-header .org-sub { font-size: 8.2pt; margin: 0; }
        .bill-header .bill-title { font-size: 9.8pt; font-weight: 700; margin: 7px 0 0 0; }
        .bill-badge {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 7pt; font-weight: 700;
            background: #c9a227; color: #1a1a1a; padding: 3px 8px;
            z-index: 2;
        }
        .bill-logo {
            width: 112px;
            height: 30px;
            display: block;
            margin: 0 auto 5px;
            object-fit: contain;
        }
        .bill-meta-bar {
            display: table; width: 100%;
            padding: 8px 15px; background: #f7efd9;
            border-bottom: 1px solid #dbc489;
            font-size: 8.2pt;
        }
        .bill-meta-bar .bill-no { font-weight: 700; color: #1e3a5f; }
        .bill-consumer { margin: 0; padding: 10px 15px; border-bottom: 1px solid #e4dbbf; background: #fffef8; }
        .bill-consumer-title { font-size: 8pt; font-weight: 700; text-transform: uppercase; color: #2c5282; margin: 0 0 5px 0; }
        .bill-consumer-table { width: 100%; border-collapse: collapse; font-size: 8.2pt; }
        .bill-consumer-table { table-layout: fixed; }
        .bill-consumer-table td { padding: 3px 8px 3px 0; }
        .bill-consumer-table .label { color: #4a5568; font-weight: 600; width: 28%; }
        .bill-section-title {
            font-size: 8.2pt; font-weight: 700; text-transform: uppercase; color: #1e3a5f;
            margin: 11px 15px 6px; padding-bottom: 4px; border-bottom: 1px solid #2c5282;
        }
        .bill-table-wrap { padding: 0 15px 6px; }
        .bill-table { width: 100%; border-collapse: collapse; margin-bottom: 9px; font-size: 8pt; }
        .bill-table th, .bill-table td { border: 1px solid #a0aec0; padding: 5px 7px; text-align: left; }
        .bill-table th { background: #1e3a5f; color: #fff; font-weight: 600; }
        .bill-table .text-right { text-align: right; }
        .bill-table .amount { text-align: right; font-weight: 600; }
        .bill-total-wrap { padding: 0 15px 11px; }
        .bill-total-box {
            border: 1.5px solid #1e3a5f; margin-top: 4px; padding: 10px 11px;
            background: #f7efd9;
        }
        .bill-total-label { font-size: 7.8pt; font-weight: 700; color: #4a5568; margin-bottom: 4px; }
        .bill-total-box .grand-total { font-size: 11.6pt; font-weight: 700; text-align: right; color: #1e3a5f; }
        .bill-amount-words { font-size: 7.5pt; color: #4a5568; margin-top: 4px; text-align: right; }
        .bill-pay-by { font-size: 7pt; color: #718096; margin-top: 4px; text-align: right; }
        .bill-footer {
            margin: 0; padding: 10px 15px 11px;
            border-top: 1px solid #dccb96; background: #fffef8;
            font-size: 7.1pt; color: #4a5568;
        }
        .bill-footer p { margin: 0 0 4px; }
        .bill-footer .footer-note { line-height: 1.2; }
        .bill-footer .sign-block { margin-top: 10px; text-align: right; }
        .bill-footer .sign-block > div { display: inline-block; vertical-align: top; margin-left: 16px; }
        .bill-footer .sign-line { border-top: 1px solid #2c5282; width: 118px; padding-top: 3px; font-size: 7pt; font-weight: 600; text-align: center; }
        .bill-footer .sign-sub { font-size: 6.7pt; margin-top: 2px; text-align: center; }
    </style>
</head>
<body>
@include('admin.estate.partials._bill_doc', ['bill' => $bill])
</body>
</html>
