@extends('admin.layouts.master')

@section('title', 'Print All Estate Bills - Sargam')

@section('setup_content')
<style>
.estate-bill-print { max-width: 210mm; margin: 0 auto; background: #f1f5f9; padding: 24px 0; }
@media print {
    .estate-bill-print { padding: 0; background: #fff; }
}
@media print {
    body * { visibility: hidden; }
    .estate-bill-print.bill-pages, .estate-bill-print.bill-pages * { visibility: visible; }
    .estate-bill-print.bill-pages { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 0; background: #fff; }
    .no-print { display: none !important; }
    .bill-doc { break-inside: auto; page-break-inside: auto; page-break-after: auto; }
    @page { size: A4 portrait; margin: 2mm; }
    body { background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .bill-doc { box-shadow: none !important; }
    .bill-pages.compact-print {
        display: grid;
        grid-template-columns: 1fr;
        gap: 4mm;
        max-width: none;
    }
    .bill-pages.compact-print .bill-doc {
        margin: 0;
        break-inside: avoid;
        page-break-inside: avoid;
        page-break-after: always;
        width: 100%;
        max-width: 206mm;
        border-width: 1.5px;
        margin-left: auto;
        margin-right: auto;
    }
    .bill-pages.compact-print .bill-doc:last-child { page-break-after: auto; }
    .bill-pages.compact-print .bill-header { padding: 7px 10px 8px; }
    .bill-pages.compact-print .bill-header::after { margin-top: 4px; height: 1px; }
    .bill-pages.compact-print .bill-header .org-name { font-size: 9.1pt; margin-bottom: 1px; }
    .bill-pages.compact-print .bill-header .org-sub { font-size: 7pt; }
    .bill-pages.compact-print .bill-header .bill-title { font-size: 8.3pt; margin-top: 5px; letter-spacing: 0.06em; }
    .bill-pages.compact-print .bill-logo { width: 22px; height: 22px; margin-bottom: 3px; object-fit: contain; }
    .bill-pages.compact-print .bill-badge { top: 5px; right: 7px; font-size: 6.2pt; padding: 2px 6px; }
    .bill-pages.compact-print .bill-meta-bar { padding: 5px 10px; gap: 4px; font-size: 7.1pt; border-bottom-width: 1px; }
    .bill-pages.compact-print .bill-meta-bar .bill-no { font-size: 7.8pt; padding: 1px 6px; border-width: 1px; }
    .bill-pages.compact-print .bill-consumer { padding: 5px 10px; border-left-width: 2px; }
    .bill-pages.compact-print .bill-consumer-title { margin-bottom: 3px; padding-bottom: 2px; font-size: 7.1pt; border-bottom-width: 1px; }
    .bill-pages.compact-print .bill-consumer-table td { padding: 2px 6px 2px 0; font-size: 7pt; }
    .bill-pages.compact-print .bill-section-title { margin: 6px 10px 3px; padding: 2px 0 2px 5px; font-size: 7.1pt; border-left-width: 2px; }
    .bill-pages.compact-print .bill-table-wrap { padding: 0 10px 2px; }
    .bill-pages.compact-print .bill-table { margin-bottom: 4px; font-size: 6.9pt; }
    .bill-pages.compact-print .bill-table th,
    .bill-pages.compact-print .bill-table td { padding: 2px 4px; }
    .bill-pages.compact-print .bill-total-wrap { padding: 0 10px 5px; }
    .bill-pages.compact-print .bill-total-box { margin-top: 1px; padding: 5px 7px; border-width: 1.5px; }
    .bill-pages.compact-print .bill-total-box::before { height: 1px; }
    .bill-pages.compact-print .bill-total-label { font-size: 6.8pt; margin-bottom: 2px; }
    .bill-pages.compact-print .bill-total-box .grand-total { font-size: 10pt; }
    .bill-pages.compact-print .bill-amount-words,
    .bill-pages.compact-print .bill-pay-by { margin-top: 2px; font-size: 6.3pt; line-height: 1.15; padding-top: 0; }
    .bill-pages.compact-print .bill-footer { padding: 5px 10px 6px; font-size: 6.2pt; border-top-width: 1px; }
    .bill-pages.compact-print .bill-footer .footer-note { margin-bottom: 2px; line-height: 1.15; }
    .bill-pages.compact-print .bill-footer p { margin-bottom: 2px; }
    .bill-pages.compact-print .bill-footer .sign-block { margin-top: 6px; gap: 10px; display: flex; }
    .bill-pages.compact-print .bill-footer .sign-line { width: 95px; padding-top: 2px; font-size: 6.2pt; border-top-width: 1px; }
    .bill-pages.compact-print .bill-footer .sign-sub { font-size: 5.8pt; margin-top: 1px; }
}
.bill-pages { font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif; font-size: 11pt; color: #1a202c; line-height: 1.45; }
.bill-pages.compact-print {
    max-width: 210mm;
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
}
.bill-pages.compact-print .bill-doc { margin: 0; }
.bill-pages.compact-print .bill-header { padding: 14px 16px 15px; }
.bill-pages.compact-print .bill-header::after { margin-top: 4px; height: 1px; }
.bill-pages.compact-print .bill-header .org-name { font-size: 12.5pt; margin-bottom: 1px; }
.bill-pages.compact-print .bill-header .org-sub { font-size: 9.6pt; }
.bill-pages.compact-print .bill-header .bill-title { font-size: 11.2pt; margin-top: 7px; letter-spacing: 0.06em; }
.bill-pages.compact-print .bill-logo { width: 32px; height: 32px; margin-bottom: 5px; object-fit: contain; }
.bill-pages.compact-print .bill-badge { top: 7px; right: 9px; font-size: 8.2pt; padding: 3px 9px; }
.bill-pages.compact-print .bill-meta-bar { padding: 10px 15px; font-size: 9.4pt; gap: 5px; border-bottom-width: 1px; }
.bill-pages.compact-print .bill-meta-bar .bill-no { font-size: 10.4pt; padding: 2px 9px; border-width: 1px; }
.bill-pages.compact-print .bill-consumer { padding: 11px 15px; border-left-width: 2px; }
.bill-pages.compact-print .bill-consumer-title { font-size: 9.6pt; margin-bottom: 4px; padding-bottom: 2px; border-bottom-width: 1px; }
.bill-pages.compact-print .bill-consumer-table td { font-size: 9.3pt; padding: 4px 8px 4px 0; }
.bill-pages.compact-print .bill-section-title { margin: 12px 15px 6px; font-size: 9.6pt; padding: 3px 0 3px 6px; border-left-width: 2px; }
.bill-pages.compact-print .bill-table-wrap { padding: 0 15px 5px; }
.bill-pages.compact-print .bill-table { margin-bottom: 9px; font-size: 9.2pt; }
.bill-pages.compact-print .bill-table th,
.bill-pages.compact-print .bill-table td { padding: 6px 9px; }
.bill-pages.compact-print .bill-total-wrap { padding: 0 15px 11px; }
.bill-pages.compact-print .bill-total-box { margin-top: 2px; padding: 11px 13px; border-width: 1.5px; }
.bill-pages.compact-print .bill-total-box::before { height: 1px; }
.bill-pages.compact-print .bill-total-label { font-size: 9.1pt; margin-bottom: 3px; }
.bill-pages.compact-print .bill-total-box .grand-total { font-size: 14.1pt; }
.bill-pages.compact-print .bill-amount-words { font-size: 8.2pt; margin-top: 3px; padding-top: 3px; line-height: 1.2; }
.bill-pages.compact-print .bill-pay-by { font-size: 8.1pt; margin-top: 2px; line-height: 1.2; }
.bill-pages.compact-print .bill-footer { padding: 10px 15px 11px; font-size: 8pt; border-top-width: 1px; }
.bill-pages.compact-print .bill-footer .footer-note { margin-bottom: 3px; line-height: 1.2; }
.bill-pages.compact-print .bill-footer p { margin-bottom: 2px; }
.bill-pages.compact-print .bill-footer .sign-block { margin-top: 8px; gap: 12px; }
.bill-pages.compact-print .bill-footer .sign-line { width: 124px; font-size: 7.9pt; padding-top: 2px; border-top-width: 1px; }
.bill-pages.compact-print .bill-footer .sign-sub { font-size: 7.2pt; margin-top: 1px; }
.bill-doc {
    position: relative;
    border: 2px solid #1e3a5f;
    padding: 0;
    background: #fff;
    box-shadow: 0 4px 24px rgba(30, 58, 95, 0.12);
    margin-bottom: 28px;
    overflow: hidden;
}
.bill-doc::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, #c9a227 0%, #e0b83d 50%, #c9a227 100%);
    z-index: 1;
}
.bill-header {
    background: #ffffff;
    color: #af2910;
    text-align: center;
    padding: 18px 24px 20px;
    position: relative;
}
.bill-header::after {
    content: ''; display: block; height: 4px;
    background: linear-gradient(90deg, transparent, #c9a227, transparent);
    margin-top: 14px; opacity: 0.9;
}
.bill-header .org-name { font-size: 14pt; font-weight: 700; letter-spacing: 0.04em; margin: 0 0 4px 0; }
.bill-header .org-sub { font-size: 10.5pt; opacity: 0.92; margin: 0; letter-spacing: 0.06em; text-transform: uppercase; }
.bill-header .bill-title { font-size: 13pt; font-weight: 700; margin: 14px 0 0 0; letter-spacing: 0.12em; text-transform: uppercase; color: #e8c15a; }
.bill-badge {
    position: absolute; top: 14px; right: 20px;
    font-size: 9pt; font-weight: 700; letter-spacing: 0.12em;
    background: #c9a227; color: #1a1a1a; padding: 6px 14px; border-radius: 0;
}
.bill-logo {
    width: 40px; height: 40px;
    display: block;
    margin: 0 auto 8px;
    object-fit: contain;
}
.bill-meta-bar {
    display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
    padding: 14px 24px; background: linear-gradient(to bottom, #f8f4e6 0%, #efe6c8 100%);
    border-bottom: 2px solid #dbc489;
    font-size: 11pt;
}
.bill-meta-bar .bill-no { font-weight: 800; color: #1e3a5f; font-size: 12pt; padding: 4px 12px; background: #fff; border: 1px solid #2c5282; }
.bill-meta-bar .bill-period { font-weight: 600; color: #2d3748; }
.bill-consumer {
    margin: 0; padding: 18px 24px;
    border-bottom: 1px solid #e4dbbf;
    background: linear-gradient(to bottom, #fffef8 0%, #fbf7ea 100%);
    border-left: 4px solid #c9a227;
}
.bill-consumer-title {
    font-size: 10pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;
    color: #2c5282; margin: 0 0 12px 0; padding-bottom: 6px;
    border-bottom: 2px solid #cbd5e0;
}
.bill-consumer-table { width: 100%; border-collapse: collapse; }
.bill-consumer-table { table-layout: fixed; }
.bill-consumer-table td { padding: 6px 14px 6px 0; vertical-align: middle; font-size: 10.5pt; }
.bill-consumer-table .label { width: 26%; color: #4a5568; font-weight: 600; }
.bill-consumer-table .value { font-weight: 500; color: #1a202c; }
.bill-section-title {
    font-size: 10.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
    color: #1e3a5f; margin: 20px 24px 10px; padding: 8px 0 8px 12px;
    border-left: 4px solid #c9a227; background: #fffaf0;
}
.bill-table-wrap { padding: 0 24px 8px; }
.bill-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 10.5pt; }
.bill-table th, .bill-table td { border: 1px solid #a0aec0; padding: 10px 12px; text-align: left; }
.bill-table th {
    background: linear-gradient(to bottom, #1e3a5f 0%, #254d7a 100%);
    color: #fff; font-weight: 600; font-size: 10pt;
}
.bill-table tbody tr:nth-child(even) { background: #f7fafc; }
.bill-table .text-right { text-align: right; }
.bill-table .amount { text-align: right; font-weight: 600; font-variant-numeric: tabular-nums; }
.bill-total-wrap { padding: 0 24px 24px; }
.bill-total-box {
    border: 3px solid #1e3a5f; margin-top: 8px; padding: 20px 20px;
    background: linear-gradient(135deg, #fffdf6 0%, #f7efd9 100%);
    position: relative;
}
.bill-total-box::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #c9a227, #e0b83d, #c9a227);
}
.bill-total-label { font-size: 10pt; font-weight: 700; color: #4a5568; letter-spacing: 0.06em; margin-bottom: 6px; }
.bill-total-box .grand-total { font-size: 18pt; font-weight: 800; text-align: right; color: #1e3a5f; font-variant-numeric: tabular-nums; }
.bill-amount-words { font-size: 9.5pt; color: #4a5568; margin-top: 10px; text-align: right; padding-top: 8px; border-top: 1px dashed #a0aec0; }
.bill-pay-by { font-size: 9pt; color: #718096; margin-top: 8px; text-align: right; }
.bill-footer {
    margin: 0; padding: 20px 24px 24px;
    border-top: 2px solid #dccb96; background: linear-gradient(to bottom, #fffef8 0%, #f6f0de 100%);
    font-size: 9pt; color: #4a5568;
}
.bill-footer .footer-note { margin-bottom: 10px; line-height: 1.5; }
.bill-footer .sign-block { margin-top: 28px; display: flex; justify-content: flex-end; gap: 48px; flex-wrap: wrap; }
.bill-footer .sign-line {
    border-top: 2px solid #2c5282; width: 180px; padding-top: 6px;
    font-size: 9pt; font-weight: 700; text-align: center; color: #1e3a5f;
}
.bill-footer .sign-sub { font-size: 8pt; margin-top: 2px; color: #718096; }
</style>
<div class="container-fluid">
    <div class="no-print">
        <x-breadcrum title="Print All Estate Bills"></x-breadcrum>
    </div>

    @if($bills->isNotEmpty())
    <div class="no-print card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body p-4">
            <p class="text-muted mb-3">
                @if(!empty($isSelectedPrint))
                    Selected {{ $bills->count() }} bill(s) are shown below. You can print all selected bills at once.
                @else
                    All {{ $bills->count() }} bill(s) for the selected month and unit type are shown below. You can print them all at once or download as a single PDF.
                @endif
            </p>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                @if(empty($isSelectedPrint))
                <a href="{{ route('admin.estate.reports.bill-report-print-all-pdf', ['bill_month' => $billMonth, 'unit_sub_type_pk' => $unitSubTypePk]) }}" class="btn btn-danger d-inline-flex align-items-center gap-2" target="_blank" rel="noopener">
                    <i class="material-symbols-rounded" style="font-size: 1.2rem;">picture_as_pdf</i>
                    Download PDF
                </a>
                @endif
                <button type="button" class="btn btn-success d-inline-flex align-items-center gap-2" onclick="window.print();" title="Print all bills at once">
                    <i class="material-symbols-rounded" style="font-size: 1.2rem;">print</i>
                    Print All at Once
                </button>
                <a href="{{ $backUrl ?? route('admin.estate.generate-estate-bill', ['bill_month' => $billMonth, 'unit_sub_type_pk' => $unitSubTypePk]) }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                    <i class="material-symbols-rounded" style="font-size: 1rem;">arrow_back</i>
                    Back to Generate Bill
                </a>
            </div>
        </div>
    </div>

    <div class="estate-bill-print bill-pages {{ (!empty($isSelectedPrint) || !empty($isOtherSelected)) ? 'compact-print' : '' }}" id="all-bills-content">
        @foreach($bills as $bill)
            @include('admin.estate.partials._bill_doc', ['bill' => $bill])
        @endforeach
    </div>
    @else
    <div class="no-print card shadow-sm">
        <div class="card-body text-center py-5">
            <p class="text-muted mb-2">No bills found for the selected filters.</p>
            <a href="{{ route('admin.estate.generate-estate-bill') }}" class="btn btn-primary">Back to Generate Estate Bill</a>
        </div>
    </div>
    @endif
</div>
@endsection
