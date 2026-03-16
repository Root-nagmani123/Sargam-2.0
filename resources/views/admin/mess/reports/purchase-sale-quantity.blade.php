@extends('admin.layouts.master')
@section('title', 'Item Report')
@section('setup_content')
<div class="container-fluid purchase-sale-quantity-report py-3">
    <x-breadcrum title="Item Report"></x-breadcrum>

    {{-- Filter card --}}
    <div class="card mb-4 border-0 rounded-3 shadow-sm no-print">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="material-symbols-rounded text-primary fs-4">filter_list</span>
                    <h5 class="mb-0 fw-semibold text-dark">Filter Item Report</h5>
                </div>
                <span class="text-muted small">Refine results by date range, view type &amp; category</span>
            </div>
        </div>
        <div class="card-body pt-0 pb-3">
            <form id="purchaseSaleQuantityFilterForm" method="GET" action="{{ route('admin.mess.reports.purchase-sale-quantity') }}">
                <div class="row g-3 g-md-4">
                    <div class="col-12 col-sm-6 col-md-6 col-lg-2">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" class="form-select" value="{{ $fromDate }}">
                    </div>
                    <div class="col-12 col-sm-6 col-md-6 col-lg-2">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" class="form-select" value="{{ $toDate }}">
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <label class="form-label">View</label>
                        <select name="view_type" id="viewType" class="form-select choices-select" data-placeholder="Select View Type">
                            <option value="item_wise" {{ $viewType === 'item_wise' ? 'selected' : '' }}>Item-wise</option>
                            <option value="subcategory_wise" {{ $viewType === 'subcategory_wise' ? 'selected' : '' }}>Subcategory-wise</option>
                            <option value="category_wise" {{ $viewType === 'category_wise' ? 'selected' : '' }}>Category-wise</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3" id="categoryIdWrap" style="display: {{ $viewType === 'category_wise' ? 'block' : 'none' }};">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="categoryId" class="form-select choices-select" data-placeholder="All Categories">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <label class="form-label">Item</label>
                        <select name="item_id" class="form-select choices-select" data-placeholder="All Items">
                            <option value="">All Items</option>
                            @foreach($allItems as $it)
                                <option
                                    value="{{ $it->id }}"
                                    data-category-id="{{ $it->category_id ?? '' }}"
                                    {{ ($itemId ?? '') == $it->id ? 'selected' : '' }}
                                >
                                    {{ $it->item_name ?? $it->subcategory_name ?? $it->name ?? '—' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-footer bg-body-secondary bg-opacity-10 border-0 py-3 d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" form="purchaseSaleQuantityFilterForm"
                        class="btn btn-primary d-inline-flex align-items-center gap-1">
                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">filter_list</span>
                    <span>Apply Filters</span>
                </button>
                <a href="{{ route('admin.mess.reports.purchase-sale-quantity') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">refresh</span>
                    <span>Reset</span>
                </a>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-1" onclick="printPurchaseSaleQuantity()" title="Print or Save as PDF">
                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">print</span>
                    <span>Print</span>
                </button>
                <a href="{{ route('admin.mess.reports.purchase-sale-quantity.pdf', request()->query()) }}" target="_blank" rel="noopener" class="btn btn-outline-danger d-inline-flex align-items-center gap-1" title="Download PDF">
                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">picture_as_pdf</span>
                    <span>PDF</span>
                </a>
                <a href="{{ route('admin.mess.reports.purchase-sale-quantity.excel', request()->query()) }}" class="btn btn-success d-inline-flex align-items-center gap-1" title="Export to Excel">
                    <span class="material-symbols-rounded" style="font-size: 1.1rem;">table_view</span>
                    <span>Export Excel</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Report card --}}
    <div class="card border-0 rounded-3 shadow-sm overflow-hidden">
        <div class="card-header bg-primary bg-opacity-10 border-0 py-3 text-center report-header">
            <h4 class="fw-bold mb-1 text-primary">Item Report</h4>
            <p class="mb-0 text-body-secondary small">
                From {{ date('d-M-Y', strtotime($fromDate)) }} to {{ date('d-M-Y', strtotime($toDate)) }}
            </p>
        </div>
        <div class="card-body p-0">
            @if($viewType === 'item_wise')
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="border-0 bg-body-secondary py-3">S. No.</th>
                                <th class="border-0 bg-body-secondary py-3">Item Name</th>
                                <th class="border-0 bg-body-secondary py-3">Unit</th>
                                <th class="text-end border-0 bg-body-secondary py-3">Total Purchase Qty</th>
                                <th class="text-end border-0 bg-body-secondary py-3">Avg Purchase Price</th>
                                <th class="text-end border-0 bg-body-secondary py-3">Total Sale Qty</th>
                                <th class="text-end border-0 bg-body-secondary py-3">Avg Sale Price</th>
                            </tr>
                        </thead>
                        <tbody class="table-group-divider">
                            @forelse($reportData as $index => $row)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $row['item_name'] }}</td>
                                    <td>{{ $row['unit'] }}</td>
                                    <td class="text-end">{{ number_format($row['purchase_qty'], 2) }}</td>
                                    <td class="text-end">{{ $row['avg_purchase_price'] !== null ? '₹' . number_format($row['avg_purchase_price'], 2) : '—' }}</td>
                                    <td class="text-end">{{ number_format($row['sale_qty'], 2) }}</td>
                                    <td class="text-end">{{ $row['avg_sale_price'] !== null ? '₹' . number_format($row['avg_sale_price'], 2) : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-body-secondary py-5">
                                        <span class="material-symbols-rounded text-muted d-block mb-2" style="font-size: 2.5rem;">inbox</span>
                                        No data found for the selected date range
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                @php $groupedData = $groupedData ?? []; @endphp
                @forelse($groupedData as $group)
                    <div class="mb-0 border-bottom border-secondary border-opacity-25">
                        <div class="px-4 pt-3 pb-2">
                            <h6 class="text-primary fw-semibold mb-0 d-flex align-items-center gap-2">
                                <span class="material-symbols-rounded" style="font-size: 1.25rem;">category</span>
                                {{ $group['category_name'] }}
                            </h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle mb-0">
                                <thead class="table-primary">
                                    <tr>
                                        <th class="border-0 py-3" style="width: 60px;">S. No.</th>
                                        <th class="border-0 py-3">Item Name</th>
                                        <th class="border-0 py-3">Unit</th>
                                        <th class="text-end border-0 py-3">Total Purchase Qty</th>
                                        <th class="text-end border-0 py-3">Avg Purchase Price</th>
                                        <th class="text-end border-0 py-3">Total Sale Qty</th>
                                        <th class="text-end border-0 py-3">Avg Sale Price</th>
                                    </tr>
                                </thead>
                                <tbody class="table-group-divider">
                                    @foreach($group['items'] as $idx => $row)
                                        <tr>
                                            <td class="text-center">{{ $idx + 1 }}</td>
                                            <td>{{ $row['item_name'] }}</td>
                                            <td>{{ $row['unit'] }}</td>
                                            <td class="text-end">{{ number_format($row['purchase_qty'], 2) }}</td>
                                            <td class="text-end">
                                                {{ isset($row['avg_purchase_price']) && $row['avg_purchase_price'] !== null ? '₹' . number_format($row['avg_purchase_price'], 2) : '—' }}
                                            </td>
                                            <td class="text-end">{{ number_format($row['sale_qty'], 2) }}</td>
                                            <td class="text-end">
                                                {{ isset($row['avg_sale_price']) && $row['avg_sale_price'] !== null ? '₹' . number_format($row['avg_sale_price'], 2) : '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-info fade show rounded-0 border-0 mb-0 d-flex align-items-center gap-2" role="alert">
                        <span class="material-symbols-rounded">info</span>
                        <span>No data found for the selected filters.</span>
                    </div>
                @endforelse
            @endif
        </div>
    </div>
</div>

{{-- Choices.js (enhanced dropdowns) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet" />

<style>
    @media print {
        .no-print { display: none !important; }
        .report-header { display: block !important; }
    }

    .purchase-sale-quantity-report .choices__inner {
        min-height: 31px;
        padding: 0.25rem 0.5rem;
        border-radius: var(--bs-border-radius, 0.375rem);
        font-size: 0.875rem;
    }

    .purchase-sale-quantity-report .choices__list--single .choices__item {
        padding: 2px 0;
    }

    .purchase-sale-quantity-report .card-footer .btn {
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var viewType = document.getElementById('viewType');
    var categoryIdWrap = document.getElementById('categoryIdWrap');
    var categorySelect = document.querySelector('select[name="category_id"]');
    var itemSelectEl = document.querySelector('select[name="item_id"]');

    if (viewType && categoryIdWrap) {
        function toggleCategory() {
            categoryIdWrap.style.display = viewType.value === 'category_wise' ? 'block' : 'none';
        }
        toggleCategory();
        viewType.addEventListener('change', function () {
            toggleCategory();
            if (filterForm) {
                filterForm.submit();
            }
        });
    }

    if (categorySelect && filterForm) {
        categorySelect.addEventListener('change', function () {
            filterForm.submit();
        });
    }

    if (typeof window.Choices !== 'undefined') {
        document
            .querySelectorAll('.purchase-sale-quantity-report select.choices-select')
            .forEach(function (el) {
                if (el.dataset.choices === 'initialized') return;

                var placeholder = el.getAttribute('data-placeholder') || 'Select';

                new TomSelect(el, {
                    create: false,
                    allowEmptyOption: true,
                    placeholder: placeholder,
                    plugins: ['dropdown_input'],
                    sortField: {
                        field: 'text',
                        direction: 'asc'
                    }
                });
            });

        // After TomSelect is initialized, restrict the Item dropdown options
        // when "Category-wise" view is selected, so that only items belonging
        // to the chosen category are shown.
        if (itemSelectEl && itemSelectEl.tomselect) {
            var itemTom = itemSelectEl.tomselect;
            var allItemOptions = Object.values(itemTom.options || {});

            function filterItemsByCategory() {
                if (!itemTom) return;

                var currentView = viewType ? viewType.value : 'item_wise';
                var selectedCategoryId = categorySelect ? String(categorySelect.value || '') : '';
                var currentValue = itemTom.getValue();

                itemTom.clearOptions();

                var allowedValues = [];
                allItemOptions.forEach(function (opt) {
                    // Always keep the "All Items" (empty) option
                    if (!opt.value) {
                        itemTom.addOption(opt);
                        allowedValues.push(opt.value);
                        return;
                    }

                    if (currentView === 'category_wise' && selectedCategoryId) {
                        var catId = '';
                        if (typeof opt.category_id !== 'undefined' && opt.category_id !== null) {
                            catId = String(opt.category_id);
                        } else if (typeof opt.categoryId !== 'undefined' && opt.categoryId !== null) {
                            catId = String(opt.categoryId);
                        } else if (typeof opt['data-category-id'] !== 'undefined' && opt['data-category-id'] !== null) {
                            catId = String(opt['data-category-id']);
                        }

                        if (catId !== selectedCategoryId) {
                            return; // skip items from other categories
                        }
                    }

                    itemTom.addOption(opt);
                    allowedValues.push(opt.value);
                });

                itemTom.refreshOptions(false);

                if (currentValue && allowedValues.indexOf(currentValue) !== -1) {
                    itemTom.setValue(currentValue, true);
                } else if (currentView === 'category_wise' && selectedCategoryId) {
                    // Default to "All Items" when switching categories
                    itemTom.clear(true);
                }
            }

            // Run once on page load
            filterItemsByCategory();

            if (categorySelect) {
                categorySelect.addEventListener('change', filterItemsByCategory);
            }
            if (viewType) {
                viewType.addEventListener('change', filterItemsByCategory);
            }
        }
    }
});

function printPurchaseSaleQuantity() {
    const tables = document.querySelectorAll('.card-body .table-responsive table');
    if (!tables.length) {
        window.print();
        return;
    }

    const title = 'Item Report - Purchase/Sale Quantity';
    const dateRange = '{{ "From " . date("d-F-Y", strtotime($fromDate)) . " To " . date("d-F-Y", strtotime($toDate)) }}';

    const printWindow = window.open('', '_blank');
    if (!printWindow) { window.print(); return; }

    let sectionsHtml = '';
    tables.forEach(function(tbl) {
        const originalThead = tbl.querySelector('thead');
        const originalTbody = tbl.querySelector('tbody');
        const firstHeaderRow = originalThead ? originalThead.querySelector('tr') : null;
        const columnsCount = firstHeaderRow ? firstHeaderRow.children.length : 7;

        const columnHeadHtml = originalThead ? originalThead.innerHTML : '';
        const bodyHtml       = originalTbody ? originalTbody.innerHTML : tbl.innerHTML;

        const printableTable = `
      <table class="table table-sm table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th colspan="${columnsCount}">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                  <img src="https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg" alt="India Emblem" height="40">
                  <div>
                    <div class="brand-line-1">Government of India</div>
                    <div class="brand-line-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
                    <div class="brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
                  </div>
                </div>
                <div class="d-none d-print-block">
                  <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="LBSNAA Logo" height="40">
                </div>
              </div>
              <div class="d-flex flex-wrap justify-content-between align-items-center report-meta">
                <span><strong>${title}</strong></span>
                <span>${dateRange}</span>
                <span><strong>Printed on:</strong> ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</span>
              </div>
            </th>
          </tr>
          ${columnHeadHtml}
        </thead>
        <tbody>
          ${bodyHtml}
        </tbody>
      </table>`;

        sectionsHtml += `
      <div class="print-page mb-3">
        <div class="table-responsive">
          ${printableTable}
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
      font-size: 10px;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .lbsnaa-header { border-bottom: 2px solid #004a93; padding-bottom:.75rem; margin-bottom:1rem; }
    .brand-line-1 { font-size:.85rem; text-transform:uppercase; letter-spacing:.06em; color:#004a93; }
    .brand-line-2 { font-size:1.1rem; font-weight:700; text-transform:uppercase; color:#222; }
    .brand-line-3 { font-size:.8rem; color:#555; }
    .report-meta { font-size:.8rem; margin-bottom:.75rem; }
    .report-meta span { display:inline-block; margin-right:1.5rem; }
    table { width:100%; border-collapse:collapse; font-size: 9px; }
    th, td { padding:4px 6px; border:1px solid #dee2e6; }
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
</script>
@endsection
