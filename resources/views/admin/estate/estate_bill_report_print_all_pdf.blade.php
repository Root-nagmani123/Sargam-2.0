<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Estate Bills - {{ $bills->first()->bill_month ?? '' }} {{ $bills->first()->bill_year ?? '' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #1a202c; line-height: 1.45; margin: 0; padding: 0; }
        .bill-doc {
            border: 2px solid #1e3a5f;
            margin-bottom: 24px;
            page-break-inside: avoid;
            overflow: hidden;
        }
        .bill-header {
            background: #1e3a5f;
            color: #fff;
            text-align: center;
            padding: 16px 20px;
        }
        .bill-header .org-name { font-size: 13pt; font-weight: 700; margin: 0 0 4px 0; }
        .bill-header .org-sub { font-size: 10pt; margin: 0; }
        .bill-header .bill-title { font-size: 12pt; font-weight: 700; margin: 10px 0 0 0; }
        .bill-badge {
            float: right;
            font-size: 8pt; font-weight: 700;
            background: #c9a227; color: #1a1a1a; padding: 4px 10px;
        }
        .bill-meta-bar {
            display: table; width: 100%;
            padding: 10px 20px; background: #edf2f7;
            border-bottom: 1px solid #cbd5e0;
            font-size: 10pt;
        }
        .bill-meta-bar .bill-no { font-weight: 700; color: #1e3a5f; }
        .bill-consumer { margin: 0; padding: 14px 20px; border-bottom: 1px solid #e2e8f0; background: #fafbfc; }
        .bill-consumer-title { font-size: 9pt; font-weight: 700; text-transform: uppercase; color: #2c5282; margin: 0 0 8px 0; }
        .bill-consumer-table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        .bill-consumer-table td { padding: 4px 10px 4px 0; }
        .bill-consumer-table .label { color: #4a5568; font-weight: 600; width: 28%; }
        .bill-section-title {
            font-size: 10pt; font-weight: 700; text-transform: uppercase; color: #1e3a5f;
            margin: 14px 20px 8px; padding-bottom: 6px; border-bottom: 2px solid #2c5282;
        }
        .bill-table-wrap { padding: 0 20px 8px; }
        .bill-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 10pt; }
        .bill-table th, .bill-table td { border: 1px solid #a0aec0; padding: 8px 10px; text-align: left; }
        .bill-table th { background: #2c5282; color: #fff; font-weight: 600; }
        .bill-table .text-right { text-align: right; }
        .bill-table .amount { text-align: right; font-weight: 600; }
        .bill-total-wrap { padding: 0 20px 20px; }
        .bill-total-box {
            border: 2px solid #1e3a5f; margin-top: 6px; padding: 14px 16px;
            background: #ebf8ff;
        }
        .bill-total-box .grand-total { font-size: 14pt; font-weight: 700; text-align: right; color: #1e3a5f; }
        .bill-amount-words { font-size: 9pt; color: #4a5568; margin-top: 6px; text-align: right; }
        .bill-pay-by { font-size: 8pt; color: #718096; margin-top: 6px; text-align: right; }
        .bill-footer {
            margin: 0; padding: 14px 20px 20px;
            border-top: 1px solid #e2e8f0; background: #f7fafc;
            font-size: 8pt; color: #4a5568;
        }
        .bill-footer .sign-block { margin-top: 20px; }
        .bill-footer .sign-line { border-top: 1px solid #2c5282; width: 140px; padding-top: 4px; font-size: 8pt; font-weight: 600; text-align: center; }
    </style>
</head>
<body>
@foreach($bills as $bill)
    @include('admin.estate.partials._bill_doc', ['bill' => $bill])
@endforeach
</body>
</html>
