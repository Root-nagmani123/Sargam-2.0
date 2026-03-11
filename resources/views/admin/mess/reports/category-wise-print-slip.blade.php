@extends('admin.layouts.master')
@section('title', 'Sale Voucher Report')
@section('setup_content')
<div class="container-fluid py-3 py-md-4 {{ request('print_all') ? 'print-all-mode' : '' }}">
    <x-breadcrum title="Sale Voucher Report"></x-breadcrum>
    @if(!request('print_all'))
    <!-- Header Section -->
    <div class="card mb-4 border-0 shadow-sm rounded-3 no-print">
        <div class="card-header bg-white border-0 pb-0">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-semibold text-dark">Filter Sale Voucher Report</h5>
                    <p class="mb-0 text-muted small">Refine results by date, client type &amp; buyer name</p>
                </div>
                <span class="badge bg-light text-secondary fw-normal d-flex align-items-center">
                    <span class="material-icons me-1" style="font-size: 16px;">info</span>
                    Smart filters
                </span>
            </div>
        </div>
        <div class="card-body pt-3">
            <form method="GET" action="{{ route('admin.mess.reports.category-wise-print-slip') }}" id="filterForm">
                <div class="row g-3 g-md-4">
                    <div class="col-12 col-md-3 col-lg-2">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-1">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                    </div>
                    <div class="col-12 col-md-3 col-lg-2">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-1">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>
                    <div class="col-12 col-md-3 col-lg-3">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-1">Employee / OT / Course</label>
                        <select name="client_type_slug" id="clientTypeSlug" class="form-select form-select-sm">
                            <option value="">All Client Types</option>
                            @foreach($clientTypes as $key => $label)
                                <option value="{{ $key }}" {{ request('client_type_slug') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3 col-lg-3">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-1">Client Type</label>
                        <select id="clientTypePk" class="form-select form-select-sm" name="{{ request('client_type_slug') === 'ot' ? 'course_master_pk' : 'client_type_pk' }}">
                            <option value="">All</option>
                            @if(request('client_type_slug') === 'employee' && isset($clientTypeCategories['employee']))
                                @foreach($clientTypeCategories['employee'] as $category)
                                    <option value="{{ $category->id }}" data-client-name="{{ strtolower($category->client_name ?? '') }}" {{ request('client_type_pk') == $category->id ? 'selected' : '' }}>{{ $category->client_name }}</option>
                                @endforeach
                            @elseif(request('client_type_slug') === 'ot' && isset($otCourses))
                                @foreach($otCourses as $course)
                                    <option value="{{ $course->pk }}" {{ (string)request('course_master_pk') === (string)$course->pk ? 'selected' : '' }}>{{ $course->course_name }}</option>
                                @endforeach
                            @elseif(request('client_type_slug') === 'course' && isset($clientTypeCategories['course']) && $clientTypeCategories['course']->isNotEmpty())
                                @foreach($clientTypeCategories['course'] as $category)
                                    <option value="{{ $category->id }}" {{ request('client_type_pk') == $category->id ? 'selected' : '' }}>{{ $category->client_name }}</option>
                                @endforeach
                            @elseif(request('client_type_slug') && isset($clientTypeCategories[request('client_type_slug')]))
                                @foreach($clientTypeCategories[request('client_type_slug')] as $category)
                                    <option value="{{ $category->id }}" {{ request('client_type_pk') == $category->id ? 'selected' : '' }}>{{ $category->client_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-12 col-md-3 col-lg-2">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-1">Buyer Name (Selling Voucher)</label>
                        <select name="buyer_name" id="clientTypePkBuyer" class="form-select form-select-sm">
                            <option value="">All Buyers</option>
                            @if(request('client_type_slug') === 'employee' && request('client_type_pk'))
                                @php
                                    $cat = isset($clientTypeCategories['employee']) ? $clientTypeCategories['employee']->firstWhere('id', request('client_type_pk')) : null;
                                    $catName = $cat ? strtolower($cat->client_name ?? '') : '';
                                @endphp
                                @if($catName === 'academy staff' && isset($employees))
                                    @foreach($employees as $e)
                                        <option value="{{ $e->full_name }}" {{ request('buyer_name') == $e->full_name ? 'selected' : '' }}>{{ $e->full_name }}</option>
                                    @endforeach
                                @elseif($catName === 'faculty' && isset($faculties))
                                    @foreach($faculties as $f)
                                        <option value="{{ $f->full_name }}" {{ request('buyer_name') == $f->full_name ? 'selected' : '' }}>{{ $f->full_name }}</option>
                                    @endforeach
                                @elseif($catName === 'mess staff' && isset($messStaff))
                                    @foreach($messStaff as $m)
                                        <option value="{{ $m->full_name }}" {{ request('buyer_name') == $m->full_name ? 'selected' : '' }}>{{ $m->full_name }}</option>
                                    @endforeach
                                @endif
                            @elseif(request('client_type_slug') === 'ot')
                                {{-- OT: student names load via AJAX when course selected; no static options to avoid wrong list on reload --}}
                            @elseif(request('client_type_slug') === 'course' && isset($clientTypeCategories['course']) && $clientTypeCategories['course']->isNotEmpty())
                                @foreach($clientTypeCategories['course'] as $category)
                                    <option value="{{ $category->client_name }}" {{ request('buyer_name') == $category->client_name ? 'selected' : '' }}>{{ $category->client_name }}</option>
                                @endforeach
                            @elseif(request('client_type_slug') === 'course' && isset($otCourses) && $otCourses->isNotEmpty())
                                @foreach($otCourses as $course)
                                    <option value="{{ $course->course_name }}" {{ request('buyer_name') == $course->course_name ? 'selected' : '' }}>{{ $course->course_name }}</option>
                                @endforeach
                            @elseif(request('client_type_slug') && isset($clientTypeCategories[request('client_type_slug')]))
                                @foreach($clientTypeCategories[request('client_type_slug')] as $category)
                                    <option value="{{ $category->client_name }}" {{ request('buyer_name') == $category->client_name ? 'selected' : '' }}>{{ $category->client_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top d-flex flex-wrap gap-2 align-items-center">
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center">
                        <span class="material-icons me-1" style="font-size: 18px;">filter_list</span>
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.mess.reports.category-wise-print-slip') }}" class="btn btn-outline-secondary d-inline-flex align-items-center">
                        <span class="material-icons me-1" style="font-size: 18px;">refresh</span>
                        Reset
                    </a>
                    <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center" id="btnPrintAll" title="Print or Save as PDF">
                        <span class="material-symbols-rounded me-1" style="font-size: 18px;">print</span>
                        Print
                    </button>
                    <a href="{{ route('admin.mess.reports.category-wise-print-slip.excel', request()->query()) }}" class="btn btn-success d-inline-flex align-items-center" title="Export to Excel">
                        <span class="material-symbols-rounded me-1" style="font-size: 18px;">table_view</span>
                        Export Excel
                    </a>
                    <a href="{{ route('admin.mess.reports.category-wise-print-slip.pdf', request()->query()) }}" class="btn btn-danger d-inline-flex align-items-center" title="Download PDF">
                        <span class="material-symbols-rounded me-1" style="font-size: 18px;">picture_as_pdf</span>
                        Download PDF
                    </a>
                </div>
            </form>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-body">
        @php
        $fromDateFormatted = request('from_date') ? \Carbon\Carbon::parse(request('from_date'))->format('d-F-Y') : 'Start';
        $toDateFormatted = request('to_date') ? \Carbon\Carbon::parse(request('to_date'))->format('d-F-Y') : 'End';
        $sectionsToShow = request('print_all') && isset($allBuyersSections) ? $allBuyersSections : collect([$groupedSections]);
    @endphp

    @if($sectionsToShow->isEmpty())
        <div class="alert alert-info">No selling vouchers found for the selected filters.</div>
    @else
    @foreach($sectionsToShow as $groupedSections)
    @php $isPrintPage = request('print_all'); @endphp
    <div class="print-page-wrap {{ $isPrintPage ? 'print-page-break' : '' }}">
    <!-- Report Heading (each printed page has header) -->
    <div class="report-header text-center mb-2 print-slip-page">
        <h3 class="report-mess-title mb-1">OFFICER'S MESS LBSNAA MUSSOORIE</h3>
        <div class="report-title-bar">
            Sale Voucher Report
            @if(request('from_date') || request('to_date'))
                Between {{ $fromDateFormatted }} To {{ $toDateFormatted }}
            @endif
        </div>
    </div>

    @forelse($groupedSections as $groupKey => $sectionVouchers)
        @php
            $first = $sectionVouchers->first();
            $buyerName = $first->client_name ?? ($first->clientTypeCategory->client_name ?? 'N/A');
            $clientTypeLabel = $first->clientTypeCategory
                ? ucfirst($first->clientTypeCategory->client_type)
                : ucfirst($first->client_type_slug ?? 'N/A');
            $slug = $first->client_type_slug ?? '';
            $typeSuffix = ($slug === 'employee') ? 'Employee' : (($slug === 'ot') ? 'OT' : ucfirst($slug));
            if (!$typeSuffix) $typeSuffix = 'N/A';
        @endphp
        <div class="print-slip-section print-slip-page mb-4">
            <div class="report-details-row mb-2">
                <span class="report-buyer-label">BUYER NAME : {{ $buyerName }}- {{ $typeSuffix }}</span>
                <span class="report-client-type">CLIENT TYPE : <strong>{{ $clientTypeLabel }}</strong></span>
            </div>
            <div class="table-responsive">
                <table class="table text-nowrap table-sm mb-0 print-slip-table align-middle">
                    <thead>
                        <tr>
                            <th class="th-slip-no">Slip No.</th>
                            <th class="th-buyer">Buyer Name</th>
                            <th class="th-remark">Remark</th>
                            <th class="th-item">Item Name</th>
                            <th class="th-date">Request Date</th>
                            <th class="th-qty">Quantity</th>
                            <th class="th-price">Price</th>
                            <th class="th-amount">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sectionTotal = 0; @endphp
                        @foreach($sectionVouchers as $voucher)
                            @php
                                $requestNo = $voucher->request_no ?? ('SV-' . str_pad($voucher->id ?? $voucher->pk ?? 0, 6, '0', STR_PAD_LEFT));
                                $requestDate = $voucher->issue_date ? $voucher->issue_date->format('d-m-Y') : 'N/A';
                                $rowCount = $voucher->items->count();
                            @endphp
                            @foreach($voucher->items as $itemIndex => $item)
                                @php
                                    $itemAmount = ($item->quantity ?? 0) * ($item->rate ?? 0);
                                    $sectionTotal += $itemAmount;
                                    $itemName = $item->item_name ?? ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? 'N/A');
                                @endphp
                                <tr>
                                    @if($itemIndex === 0)
                                        <td class="text-center align-middle" rowspan="{{ $rowCount }}">{{ $requestNo }}</td>
                                        <td class="align-middle" rowspan="{{ $rowCount }}">{{ $buyerName }}</td>
                                        <td class="align-middle" rowspan="{{ $rowCount }}">{{ $voucher->remarks ?? '—' }}</td>
                                    @endif
                                    <td>{{ $itemName }}</td>
                                    <td class="text-center">{{ $requestDate }}</td>
                                    <td class="text-end">{{ number_format($item->quantity ?? 0, 2) }}</td>
                                    <td class="text-end">{{ number_format($item->rate ?? 0, 2) }}</td>
                                    <td class="text-end">{{ number_format($itemAmount, 2) }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                        <tr class="total-row">
                            <td colspan="6"></td>
                            <td class="text-end"><strong>TOTAL</strong></td>
                            <td class="text-end"><strong>{{ number_format($sectionTotal, 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="alert alert-info">No selling vouchers found for the selected filters.</div>
    @endforelse
    </div>
    @endforeach
    @endif

    <!-- Pagination: one buyer per page (hide when print) -->
    @if(!request('print_all') && isset($paginator) && $paginator->hasPages())
        <div class="d-flex align-items-center gap-2 mt-3 no-print pagination-custom">
            <span class="text-secondary">Page</span>
            <input type="number" class="form-control  pagination-page-input" id="paginationPageInput"
                value="{{ $paginator->currentPage() }}" min="1" max="{{ $paginator->lastPage() }}"
                style="width: 60px; display: inline-block;">
            <span class="text-secondary">of {{ $paginator->lastPage() }}</span>
            @if($paginator->currentPage() > 1)
                <a href="{{ $paginator->withQueryString()->url($paginator->currentPage() - 1) }}" class="btn btn-sm btn-outline-secondary pagination-arrow" aria-label="Previous">&lsaquo;</a>
            @endif
            @if($paginator->hasMorePages())
                <a href="{{ $paginator->withQueryString()->nextPageUrl() }}" class="btn btn-sm btn-outline-secondary pagination-arrow" aria-label="Next">&rsaquo;</a>
            @endif
        </div>
        <script>
        (function() {
            var input = document.getElementById('paginationPageInput');
            if (!input) return;
            var lastPage = {{ $paginator->lastPage() }};
            var baseUrl = "{{ $paginator->withQueryString()->url(1) }}";
            function goToPage() {
                var p = parseInt(input.value, 10);
                if (isNaN(p) || p < 1) p = 1;
                if (p > lastPage) p = lastPage;
                input.value = p;
                var url = baseUrl.match(/page=/) ? baseUrl.replace(/page=\d+/, 'page=' + p) : baseUrl + (baseUrl.indexOf('?') >= 0 ? '&' : '?') + 'page=' + p;
                window.location.href = url;
            }
            input.addEventListener('change', goToPage);
            input.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); goToPage(); } });
        })();
        </script>
    @endif
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">

<style>
    /* Report header – same on screen and print */
    .report-mess-title {
        color: #000;
        font-size: 1.25rem;
        font-weight: bold;
    }
    .report-title-bar {
        background-color: #004a93;
        color: #fff;
        padding: 8px 12px;
        font-size: 0.95rem;
        margin-top: 6px;
    }
    .report-details-row {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
    }
    .report-buyer-label { font-weight: 500; }
    .report-client-type { font-weight: 500; }

    /* Table – light blue header like reference image */

    .print-slip-table thead th {
        border-color: #8eb8d0 !important;
        color: #1a1a1a;
        font-weight: 600;
        padding: 8px 6px;
    }
    .print-slip-table .th-slip-no, .print-slip-table .th-date { text-align: center; }
    .print-slip-table .th-qty, .print-slip-table .th-price, .print-slip-table .th-amount { text-align: right; }
    .print-slip-table tbody td { padding: 6px 8px; vertical-align: middle; }
    .print-slip-table .total-row { background-color: #f0f0f0; font-weight: bold; }

    .pagination-custom {
        background-color: #f5f5f5;
        padding: 8px 12px;
        border-radius: 4px;
    }
    .pagination-custom .pagination-page-input { text-align: center; }
    .pagination-custom .pagination-arrow { padding: 4px 10px; }

    .print-page-break { page-break-after: always; }
    .print-page-break:last-child { page-break-after: auto; }
    .print-all-mode .print-page-wrap { margin-bottom: 0; }

    /* Impressive print layout */
    @media print {
        .no-print { display: none !important; }
        @page { size: A4; margin: 12mm; }
        body { font-size: 11px; background: #fff !important; }
        .container-fluid { max-width: 100% !important; padding: 0 !important; }
        .print-page-wrap {
            page-break-after: always;
            padding: 0;
            margin: 0 0 8px 0;
        }
        .print-page-wrap:last-child { page-break-after: auto; }
        .report-header {
            margin-top: 0;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #2c3e50;
        }
        .report-mess-title {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a1a;
            letter-spacing: 0.5px;
        }
        .report-title-bar {
            font-size: 11px;
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
        .print-slip-table {
            font-size: 10px;
            border-collapse: collapse;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
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
    }
</style>

@if(request('print_all'))
<script>
window.addEventListener('load', function() {
    setTimeout(function() { window.print(); }, 300);
});
</script>
@endif

<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
function printCategoryWiseSlip() {
    const tables = document.querySelectorAll('.print-slip-section .table-responsive table');
    if (!tables.length) {
        window.print();
        return;
    }

    const title = 'Sale Voucher Report';
    const dateRange = '{{ (request('from_date') || request('to_date')) ? "Between $fromDateFormatted To $toDateFormatted" : "All Dates" }}';

    const printWindow = window.open('', '_blank');
    if (!printWindow) { window.print(); return; }

    let sectionsHtml = '';
    tables.forEach(function(tbl, index) {
        sectionsHtml += `
      <div class="print-page mb-3">
        <div class="row align-items-center lbsnaa-header">
          <div class="col-auto d-none d-print-block">
            <img src="https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg" alt="India Emblem" height="48">
          </div>
          <div class="col">
            <div class="brand-line-1">Government of India</div>
            <div class="brand-line-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
            <div class="brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
          </div>
          <div class="col-auto d-none d-print-block">
            <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="LBSNAA Logo" height="48">
          </div>
        </div>

        <div class="mb-2">
          <h5 class="mb-1">${title}</h5>
          <div class="report-meta">
            <span><strong>Period:</strong> ${dateRange}</span>
            <span><strong>Printed on:</strong> ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</span>
          </div>
        </div>

        <div class="table-responsive">
          ${tbl.outerHTML}
        </div>

        <div class="print-footer text-center mt-2 pt-1">
          <small>OFFICER'S MESS LBSNAA MUSSOORIE</small>
        </div>
      </div>`;
    });

    printWindow.document.open();
    printWindow.document.write(`<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${title} - OFFICER'S MESS LBSNAA MUSSOORIE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      font-size: 9px;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .lbsnaa-header { border-bottom: 2px solid #004a93; padding-bottom:.75rem; margin-bottom:1rem; }
    .brand-line-1 { font-size:.85rem; text-transform:uppercase; letter-spacing:.06em; color:#004a93; }
    .brand-line-2 { font-size:1.1rem; font-weight:700; text-transform:uppercase; color:#222; }
    .brand-line-3 { font-size:.8rem; color:#555; }
    .report-meta { font-size:.8rem; margin-bottom:.75rem; }
    .report-meta span { display:inline-block; margin-right:1.5rem; }
    table { width:100%; border-collapse:collapse; font-size: 8px; }
    th, td { padding:2px 4px; border:1px solid #dee2e6; }
    thead th { background:#f8f9fa; font-weight:600; }
    .table,
    .table * {
      white-space: normal !important;
    }
    .table-responsive {
      overflow: visible !important;
    }
    thead { display:table-header-group; }
    .print-page {
      page-break-after: always;
    }
    .print-page:last-child {
      page-break-after: auto;
    }
    .print-footer {
      border-top: 1px solid #dee2e6;
      font-size: .7rem;
      color: #666;
    }
    @page {
      size: A4;
      margin: 0.5in;
    }
    @media print {
      body { margin:0; }
    }
  </style>
</head>
<body>
  <div class="container-fluid">
    ${sectionsHtml}
  </div>

  <script>
    window.addEventListener('load', function() { window.print(); });
  <\/script>
</body>
</html>`);
    printWindow.document.close();
}

document.addEventListener('DOMContentLoaded', function() {
    var btnPrintAll = document.getElementById('btnPrintAll');
    if (btnPrintAll) {
        btnPrintAll.addEventListener('click', function(e) {
            e.preventDefault();
            printCategoryWiseSlip();
        });
    }

    const clientTypeSlug = document.getElementById('clientTypeSlug');
    const clientTypePk = document.getElementById('clientTypePk');
    const clientTypePkBuyer = document.getElementById('clientTypePkBuyer');
    const studentsByCourseUrl = "{{ url('/admin/mess/selling-voucher-date-range/students-by-course') }}";
    const preservedBuyerName = {!! json_encode(request('buyer_name', '')) !!};

    const clientTypeOptions = {
        @foreach($clientTypes as $key => $label)
            '{{ $key }}': [
                @if(isset($clientTypeCategories[$key]))
                    @foreach($clientTypeCategories[$key] as $category)
                        { value: '{{ $category->id }}', text: '{{ addslashes($category->client_name) }}', dataClientName: '{{ strtolower($category->client_name ?? '') }}' },
                    @endforeach
                @endif
            ],
        @endforeach
    };
    const otCourseOptions = [
        @if(isset($otCourses))
            @foreach($otCourses as $course)
                { value: '{{ $course->pk }}', text: '{{ addslashes($course->course_name) }}' },
            @endforeach
        @endif
    ];
    const employeeNames = { 'academy staff': [ @foreach($employees ?? [] as $e){ value: '{{ addslashes($e->full_name) }}', text: '{{ addslashes($e->full_name) }}' },@endforeach ], 'faculty': [ @foreach($faculties ?? [] as $f){ value: '{{ addslashes($f->full_name) }}', text: '{{ addslashes($f->full_name) }}' },@endforeach ], 'mess staff': [ @foreach($messStaff ?? [] as $m){ value: '{{ addslashes($m->full_name) }}', text: '{{ addslashes($m->full_name) }}' },@endforeach ] };

    if (clientTypeSlug && clientTypePk && clientTypePkBuyer) {
        // Enhance dropdowns with Choices.js if available
        let clientTypeSlugChoices = null;
        let clientTypePkChoices = null;
        let clientTypePkBuyerChoices = null;

        if (window.Choices) {
            const baseConfig = {
                searchEnabled: true,
                shouldSort: false,
                itemSelectText: '',
                removeItemButton: false
            };
            clientTypeSlugChoices = new Choices(clientTypeSlug, baseConfig);
            clientTypePkChoices = new Choices(clientTypePk, baseConfig);
            clientTypePkBuyerChoices = new Choices(clientTypePkBuyer, baseConfig);
        }

        function fillClientTypeSelect() {
            const slug = clientTypeSlug.value;
            const useChoices = !!clientTypePkChoices;

            clientTypePk.name = (slug === 'ot') ? 'course_master_pk' : 'client_type_pk';

            if (useChoices) {
                let choicesData = [{ value: '', label: 'All', selected: true }];
                if (slug === 'ot' && otCourseOptions.length) {
                    otCourseOptions.forEach(function(o) {
                        choicesData.push({ value: o.value, label: o.text });
                    });
                } else if (slug && clientTypeOptions[slug]) {
                    clientTypeOptions[slug].forEach(function(o) {
                        choicesData.push({ value: o.value, label: o.text, customProperties: { clientName: o.dataClientName || '' } });
                    });
                }
                clientTypePkChoices.clearChoices();
                clientTypePkChoices.setChoices(choicesData, 'value', 'label', true);
            } else {
                clientTypePk.innerHTML = '<option value="">All</option>';
                if (slug === 'ot' && otCourseOptions.length) {
                    otCourseOptions.forEach(function(o) {
                        const opt = document.createElement('option');
                        opt.value = o.value;
                        opt.textContent = o.text;
                        clientTypePk.appendChild(opt);
                    });
                } else if (slug && clientTypeOptions[slug]) {
                    clientTypeOptions[slug].forEach(function(o) {
                        const opt = document.createElement('option');
                        opt.value = o.value;
                        opt.textContent = o.text;
                        opt.dataset.clientName = o.dataClientName || '';
                        clientTypePk.appendChild(opt);
                    });
                }
            }
            fillBuyerNameSelect();
        }

        function resolveEmployeeCategoryName(slug, selectedValue) {
            if (slug !== 'employee' || !selectedValue) return '';
            const list = clientTypeOptions['employee'] || [];
            const match = list.find(function(o) { return String(o.value) === String(selectedValue); });
            return match ? (match.dataClientName || '') : '';
        }

        function fillBuyerNameSelect() {
            const slug = clientTypeSlug.value;
            const useChoices = !!clientTypePkBuyerChoices;
            const selectedValue = clientTypePk.value;

            let dataClientName = '';
            if (slug === 'employee') {
                const selectedOpt = clientTypePk.options[clientTypePk.selectedIndex];
                if (selectedOpt && selectedOpt.dataset && selectedOpt.dataset.clientName) {
                    dataClientName = selectedOpt.dataset.clientName || '';
                } else {
                    dataClientName = resolveEmployeeCategoryName(slug, selectedValue);
                }
            }

            function setBuyerChoices(list, preserveValue) {
                if (useChoices) {
                    let choicesData = [{ value: '', label: 'All Buyers', selected: !preserveValue }];
                    (list || []).forEach(function(o) {
                        choicesData.push({ value: o.value, label: o.text });
                    });
                    clientTypePkBuyerChoices.clearChoices();
                    clientTypePkBuyerChoices.setChoices(choicesData, 'value', 'label', true);
                    if (preserveValue) {
                        clientTypePkBuyerChoices.setChoiceByValue(preserveValue);
                    }
                } else {
                    clientTypePkBuyer.innerHTML = '<option value="">All Buyers</option>';
                    (list || []).forEach(function(o) {
                        const opt = document.createElement('option');
                        opt.value = o.value;
                        opt.textContent = o.text;
                        clientTypePkBuyer.appendChild(opt);
                    });
                    if (preserveValue) {
                        clientTypePkBuyer.value = preserveValue;
                    }
                }
            }

            if (slug === 'employee' && dataClientName && employeeNames[dataClientName]) {
                setBuyerChoices(employeeNames[dataClientName], preservedBuyerName);
            } else if (slug === 'ot' && selectedValue) {
                if (!useChoices) {
                    clientTypePkBuyer.innerHTML = '<option value="">Loading...</option>';
                }
                fetch(studentsByCourseUrl + '/' + selectedValue, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        const students = (data.students || []).map(function(s) {
                            return { value: s.display_name || '', text: s.display_name || '—' };
                        });
                        setBuyerChoices(students, preservedBuyerName);
                    })
                    .catch(function() {
                        setBuyerChoices([], preservedBuyerName);
                    });
            } else if (slug === 'course') {
                if (clientTypeOptions['course'] && clientTypeOptions['course'].length) {
                    const list = clientTypeOptions['course'].map(function(o) {
                        return { value: o.text, text: o.text };
                    });
                    setBuyerChoices(list, preservedBuyerName);
                } else if (otCourseOptions.length) {
                    const list = otCourseOptions.map(function(o) {
                        return { value: o.text, text: o.text };
                    });
                    setBuyerChoices(list, preservedBuyerName);
                } else {
                    setBuyerChoices([], preservedBuyerName);
                }
            } else if (slug && clientTypeOptions[slug]) {
                const list = clientTypeOptions[slug].map(function(o) {
                    return { value: o.text, text: o.text };
                });
                setBuyerChoices(list, preservedBuyerName);
            } else {
                setBuyerChoices([], preservedBuyerName);
            }
        }

        clientTypeSlug.addEventListener('change', function() { fillClientTypeSelect(); });
        clientTypePk.addEventListener('change', function() { fillBuyerNameSelect(); });

        if (clientTypeSlug.value === 'ot') {
            clientTypePk.name = 'course_master_pk';
            if (clientTypePk.value) fillBuyerNameSelect();
        }
        if (clientTypeSlug.value === 'course') {
            fillBuyerNameSelect();
        }
    }
});
</script>
@endsection
