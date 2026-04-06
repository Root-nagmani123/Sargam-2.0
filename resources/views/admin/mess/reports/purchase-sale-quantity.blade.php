@extends('admin.layouts.master')
@section('title', 'Item Report')
@section('setup_content')
@php
    /** @var array<int> $storeIds */
    $storeIds = isset($storeIds) ? $storeIds : [];
    /** @var array<int> $itemIds */
    $itemIds = isset($itemIds) ? $itemIds : [];
    /** @var array<int, string> $viewTypes */
    $viewTypes = isset($viewTypes) ? $viewTypes : ['item_wise'];

    $messEmblemSrc = 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';
    // Print opens about:blank; remote URLs often fail. Raster: data URI on <img>. SVG: must be inlined in HTML — <img src="data:image/svg+xml;base64,…"> often stays blank when the SVG embeds raster (xlink:data PNG), which is how admin logo.svg is built.
    $messLbsnaaLogoSrc = 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png';
    $messLbsnaaLogoSvgInline = null;
    foreach ([public_path('images/lbsnaa_logo.jpg'), public_path('images/lbsnaa_logo.png')] as $logoPath) {
        if (is_file($logoPath) && is_readable($logoPath)) {
            $raw = @file_get_contents($logoPath);
            if ($raw !== false) {
                $mime = str_ends_with(strtolower($logoPath), '.png') ? 'image/png' : 'image/jpeg';
                $messLbsnaaLogoSrc = 'data:'.$mime.';base64,'.base64_encode($raw);
                break;
            }
        }
    }
    if (str_starts_with($messLbsnaaLogoSrc, 'http')) {
        foreach ([
            public_path('admin_assets/images/logos/logo.png'),
            public_path('admin_assets/images/logos/logo.svg'),
        ] as $localLogoPath) {
            if (! is_file($localLogoPath) || ! is_readable($localLogoPath)) {
                continue;
            }
            $raw = @file_get_contents($localLogoPath);
            if ($raw === false) {
                continue;
            }
            $ext = strtolower(pathinfo($localLogoPath, PATHINFO_EXTENSION));
            if ($ext === 'svg') {
                $messLbsnaaLogoSvgInline = $raw;
                $messLbsnaaLogoSrc = '';

                break;
            }
            if ($ext === 'png') {
                $messLbsnaaLogoSrc = 'data:image/png;base64,'.base64_encode($raw);

                break;
            }
        }
    }

    $messViewLabelMap = ['item_wise' => 'Item-wise', 'subcategory_wise' => 'Subcategory-wise', 'category_wise' => 'Category-wise'];
    $messViewLabel = collect($viewTypes)->map(fn ($v) => $messViewLabelMap[$v] ?? $v)->implode(', ');
    try {
        $purchaseSalePeriodFromLabel = \Carbon\Carbon::parse($fromDate)->format('d-m-Y');
        $purchaseSalePeriodToLabel = \Carbon\Carbon::parse($toDate)->format('d-m-Y');
    } catch (\Throwable $e) {
        $purchaseSalePeriodFromLabel = (string) $fromDate;
        $purchaseSalePeriodToLabel = (string) $toDate;
    }
    $purchaseSalePrintConfig = [
        'periodBar' => 'From ' . $purchaseSalePeriodFromLabel . ' To ' . $purchaseSalePeriodToLabel,
        'storeLabel' => ($selectedStoreName !== null && $selectedStoreName !== '') ? $selectedStoreName : 'All Stores',
        'itemsLabel' => ($selectedItemNamesLabel !== null && $selectedItemNamesLabel !== '') ? $selectedItemNamesLabel : 'All Items',
        'viewLabel' => $messViewLabel,
    ];

    $purchaseSalePrintImages = [
        'emblemSrc' => $messEmblemSrc,
        'lbsnaaLogoSrc' => $messLbsnaaLogoSrc,
        'lbsnaaLogoSvgInline' => $messLbsnaaLogoSvgInline,
    ];
@endphp
<div class="container-fluid purchase-sale-quantity-report py-3">
    <script>
        window.__purchaseSalePrintConfig = @json($purchaseSalePrintConfig);
        window.__purchaseSalePrintImages = @json($purchaseSalePrintImages);
    </script>
    <x-breadcrum title="Item Report"></x-breadcrum>

    {{-- Filter card --}}
    <div class="card mb-4 border-0 rounded-3 shadow-sm no-print">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="material-symbols-rounded text-primary fs-4">filter_list</span>
                    <h5 class="mb-0 fw-semibold text-dark">Filter Item Report</h5>
                </div>
                <span class="text-muted small">Refine results by date range, view type, category, items &amp; store</span>
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
                        <label class="form-label" for="viewType">View</label>
                        <select name="view_type[]" id="viewType" class="form-select purchase-sale-view-tomselect" multiple data-placeholder="Select view type(s)">
                            <option value="item_wise" @selected(in_array('item_wise', $viewTypes, true))>Item-wise</option>
                            <option value="subcategory_wise" @selected(in_array('subcategory_wise', $viewTypes, true))>Subcategory-wise</option>
                            <option value="category_wise" @selected(in_array('category_wise', $viewTypes, true))>Category-wise</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3{{ in_array('category_wise', $viewTypes, true) ? '' : ' d-none' }}" id="categoryIdWrap">
                        <label class="form-label" for="categoryId">Category</label>
                        <select name="category_id" id="categoryId" class="form-select purchase-sale-category-tomselect" data-placeholder="All categories">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <label for="purchase_sale_item_id" class="form-label">Item</label>
                        <select name="item_id[]"
                                id="purchase_sale_item_id"
                                class="form-select purchase-sale-item-multiselect"
                                multiple
                                data-placeholder="All Items">
                            @foreach($allItems as $it)
                                <option
                                    value="{{ $it->id }}"
                                    data-category-id="{{ $it->category_id ?? '' }}"
                                    @selected(in_array((int) $it->id, $itemIds, true))
                                >
                                    {{ $it->item_name ?? $it->subcategory_name ?? $it->name ?? '—' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row g-3 g-md-4">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="purchase_sale_store_id" class="form-label">Store</label>
                        <div class="input-group">
                            <span class="input-group-text bg-body-secondary" id="store_id_addon">
                                <span class="material-symbols-rounded" style="font-size: 1.1rem;" aria-hidden="true">storefront</span>
                            </span>
                            <select name="store_id[]"
                                    id="purchase_sale_store_id"
                                    class="form-select purchase-sale-store-multiselect"
                                    multiple
                                    data-placeholder="All Stores"
                                    aria-describedby="store_id_addon">
                                @foreach($stores ?? [] as $store)
                                    <option value="{{ $store->id }}" @selected(in_array((int) $store->id, $storeIds, true))>
                                        {{ $store->store_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
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
                From {{ $purchaseSalePeriodFromLabel }} to {{ $purchaseSalePeriodToLabel }}
            </p>
            <p class="mb-0 text-body-secondary small">
                View: {{ $messViewLabel }}
            </p>
            <p class="mb-0 text-body-secondary small">
                Store: {{ ($selectedStoreName !== null && $selectedStoreName !== '') ? $selectedStoreName : 'All Stores' }}
            </p>
            <p class="mb-0 text-body-secondary small">
                Items: {{ ($selectedItemNamesLabel !== null && $selectedItemNamesLabel !== '') ? $selectedItemNamesLabel : 'All Items' }}
            </p>
        </div>
        <div id="purchaseSaleReportCardBody" class="card-body p-0 psq-scroll-wrapper" data-view-types='@json($viewTypes)'>
            @php $viewTypeSections = $viewTypeSections ?? []; $multiView = count($viewTypeSections) > 1; @endphp
            @forelse($viewTypeSections as $section)
                <div class="purchase-sale-view-section border-bottom border-secondary border-opacity-25" data-view-type="{{ $section['viewType'] }}">
                    @if($multiView)
                        <div class="px-4 pt-3 pb-1">
                            <h6 class="purchase-sale-section-heading text-primary fw-semibold mb-0 d-flex align-items-center gap-2">
                                <span class="material-symbols-rounded" style="font-size: 1.25rem;">layers</span>
                                {{ $section['viewLabel'] }}
                            </h6>
                        </div>
                    @endif
                    @if($section['viewType'] === 'item_wise')
                        <div class="table-responsive">
                            <table class="table align-middle mb-0 psq-table">
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
                                    @forelse($section['reportData'] as $index => $row)
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
                        @php $groupedData = $section['groupedData'] ?? []; @endphp
                        @forelse($groupedData as $group)
                            <div class="purchase-sale-group-block mb-0 border-bottom border-secondary border-opacity-25">
                                <div class="px-4 pt-3 pb-2">
                                    <h6 class="text-primary fw-semibold mb-0 d-flex align-items-center gap-2">
                                        <span class="material-symbols-rounded" style="font-size: 1.25rem;">category</span>
                                        {{ $group['category_name'] }}
                                    </h6>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped align-middle mb-0 psq-table">
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
            @empty
                <div class="alert alert-info fade show rounded-0 border-0 mb-0 d-flex align-items-center gap-2" role="alert">
                    <span class="material-symbols-rounded">info</span>
                    <span>No data found for the selected filters.</span>
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Tom Select: view, category, item & store filters --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<link rel="stylesheet" href="{{ asset('admin_assets/css/material-icons-local.css') }}" />

<style>
    /* ── Scrollable table body with sticky header ── */
    @media screen {
        .purchase-sale-quantity-report .psq-scroll-wrapper {
            max-height: min(72vh, 760px);
            overflow: auto;
            position: relative;
        }
        .purchase-sale-quantity-report .psq-table {
            border-collapse: separate !important;
            border-spacing: 0;
        }
        .purchase-sale-quantity-report .psq-table > thead > tr > th {
            position: sticky !important;
            top: 0;
            z-index: 10;
            background: #0b4a7e !important;
            color: #fff !important;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-bottom: 2px solid #004a93 !important;
            border-top: none !important;
            border-left: 1px solid rgba(255,255,255,0.15) !important;
            border-right: 1px solid rgba(255,255,255,0.15) !important;
            padding: 0.6rem 0.75rem;
            font-size: 0.8125rem;
            white-space: nowrap;
        }
        .purchase-sale-quantity-report .psq-table > thead > tr > th:first-child {
            border-left: none !important;
        }
        .purchase-sale-quantity-report .psq-table > thead > tr > th:last-child {
            border-right: none !important;
        }
        .purchase-sale-quantity-report .psq-table > thead > tr > th.text-end {
            text-align: right !important;
        }
        /* Section headings should stick below the table header when scrolling */
        .purchase-sale-quantity-report .purchase-sale-section-heading {
            position: sticky;
            top: 0;
            z-index: 5;
            background: #fff;
            padding-top: 0.75rem !important;
            padding-bottom: 0.5rem !important;
        }
        .purchase-sale-quantity-report .purchase-sale-group-block h6 {
            position: sticky;
            top: 0;
            z-index: 5;
            background: #fff;
        }
        /* Scrollbar styling */
        .purchase-sale-quantity-report .psq-scroll-wrapper::-webkit-scrollbar { height: 10px; width: 10px; }
        .purchase-sale-quantity-report .psq-scroll-wrapper::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 8px; }
        .purchase-sale-quantity-report .psq-scroll-wrapper::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
        .purchase-sale-quantity-report .psq-scroll-wrapper::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    }

    @media print {
        .no-print { display: none !important; }
        .report-header { display: block !important; }
        .purchase-sale-quantity-report .psq-scroll-wrapper { max-height: none !important; overflow: visible !important; }
        .purchase-sale-quantity-report .psq-table > thead > tr > th { position: static !important; box-shadow: none !important; }
    }

    .purchase-sale-quantity-report .card-footer .btn {
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
    }

    .purchase-sale-quantity-report .input-group .ts-wrapper {
        flex: 1 1 auto;
        min-width: 0;
    }

    .purchase-sale-quantity-report .input-group .ts-control {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    .purchase-sale-quantity-report .purchase-sale-view-tomselect + .ts-wrapper,
    .purchase-sale-quantity-report .purchase-sale-category-tomselect + .ts-wrapper {
        min-width: 0;
    }

    .purchase-sale-quantity-report .purchase-sale-item-multiselect + .ts-wrapper {
        min-width: 0;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.TomSelect === 'undefined') return;

    var tsDropdownToBody = { dropdownParent: 'body' };

    document.querySelectorAll('.purchase-sale-quantity-report select.purchase-sale-view-tomselect').forEach(function (el) {
        if (el.dataset.tomselectInitialized === 'true') return;
        new TomSelect(el, Object.assign({
            placeholder: el.getAttribute('data-placeholder') || 'Select view type(s)',
            maxItems: null,
            maxOptions: null,
            plugins: ['remove_button', 'dropdown_input'],
            sortField: { field: 'text', direction: 'asc' }
        }, tsDropdownToBody));
        el.dataset.tomselectInitialized = 'true';
    });

    document.querySelectorAll('.purchase-sale-quantity-report select.purchase-sale-category-tomselect').forEach(function (el) {
        if (el.dataset.tomselectInitialized === 'true') return;
        new TomSelect(el, Object.assign({
            placeholder: el.getAttribute('data-placeholder') || 'All categories',
            maxItems: 1,
            maxOptions: null,
            plugins: ['dropdown_input'],
            sortField: { field: 'text', direction: 'asc' },
            allowEmptyOption: true
        }, tsDropdownToBody));
        el.dataset.tomselectInitialized = 'true';
    });

    document.querySelectorAll('.purchase-sale-quantity-report select.purchase-sale-store-multiselect').forEach(function (el) {
        if (el.dataset.tomselectInitialized === 'true') return;
        var placeholder = el.getAttribute('data-placeholder') || 'Select';
        new TomSelect(el, Object.assign({
            placeholder: placeholder,
            maxItems: null,
            maxOptions: 500,
            plugins: ['remove_button', 'dropdown_input'],
            sortField: { field: 'text', direction: 'asc' }
        }, tsDropdownToBody));
        el.dataset.tomselectInitialized = 'true';
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var viewType = document.getElementById('viewType');
    var categoryIdWrap = document.getElementById('categoryIdWrap');
    var categorySelect = document.querySelector('select[name="category_id"]');
    var itemSelectEl = document.getElementById('purchase_sale_item_id');
    var originalItemOptions = null;

    function parseOriginalItemOptionsFromDom() {
        if (!itemSelectEl) return [];
        return Array.from(itemSelectEl.querySelectorAll('option')).map(function (opt) {
            return {
                value: opt.value,
                label: opt.textContent ? opt.textContent.trim() : '',
                categoryId: opt.getAttribute('data-category-id') || ''
            };
        }).filter(function (o) { return o.value !== ''; });
    }

    function getSelectedCategoryIdForItemFilter() {
        if (!categoryIdWrap || categoryIdWrap.classList.contains('d-none')) return '';
        return categorySelect ? String(categorySelect.value || '') : '';
    }

    function getFilteredItemOptionsList() {
        var selectedCategoryId = getSelectedCategoryIdForItemFilter();
        if (!originalItemOptions) return [];
        return originalItemOptions.filter(function (opt) {
            if (!selectedCategoryId) return true;
            return String(opt.categoryId || '') === selectedCategoryId;
        });
    }

    function syncItemSelectOptions(filteredList, preselectedValues) {
        var set = new Set((preselectedValues || []).map(String));
        itemSelectEl.innerHTML = '';
        filteredList.forEach(function (o) {
            var opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.label;
            opt.setAttribute('data-category-id', o.categoryId);
            if (set.has(String(o.value))) opt.selected = true;
            itemSelectEl.appendChild(opt);
        });
    }

    function initOrRebuildItemTomSelect(opts) {
        opts = opts || {};
        var forceClear = opts.clear === true;
        if (!itemSelectEl || typeof window.TomSelect === 'undefined') return;

        var prevSelected = [];
        if (itemSelectEl.tomselect) {
            if (!forceClear) {
                prevSelected = itemSelectEl.tomselect.getValue();
                if (!Array.isArray(prevSelected)) {
                    prevSelected = prevSelected ? [String(prevSelected)] : [];
                }
            }
            itemSelectEl.tomselect.destroy();
        } else if (!forceClear) {
            prevSelected = Array.from(itemSelectEl.selectedOptions).map(function (o) { return o.value; });
        }

        var filtered = getFilteredItemOptionsList();
        var allowed = new Set(filtered.map(function (f) { return String(f.value); }));
        var newSelected = forceClear ? [] : prevSelected.filter(function (v) { return allowed.has(String(v)); });

        syncItemSelectOptions(filtered, newSelected);

        new TomSelect(itemSelectEl, {
            placeholder: itemSelectEl.getAttribute('data-placeholder') || 'All Items',
            maxItems: null,
            maxOptions: 500,
            plugins: ['remove_button', 'dropdown_input'],
            sortField: { field: 'text', direction: 'asc' },
            dropdownParent: 'body'
        });
    }

    if (itemSelectEl) {
        originalItemOptions = parseOriginalItemOptionsFromDom();
    }

    if (viewType && categoryIdWrap) {
        function getSelectedViewTypes() {
            if (viewType.tomselect) {
                var v = viewType.tomselect.getValue();
                return Array.isArray(v) ? v : (v ? [String(v)] : []);
            }
            return Array.from(viewType.selectedOptions || []).map(function (o) { return o.value; });
        }

        function updateCategoryWrapVisibility() {
            var types = getSelectedViewTypes();
            if (types.indexOf('category_wise') !== -1) {
                categoryIdWrap.classList.remove('d-none');
            } else {
                categoryIdWrap.classList.add('d-none');
            }
        }

        function clearCategoryValue() {
            if (!categorySelect) return;
            if (categorySelect.tomselect) {
                categorySelect.tomselect.clear();
            } else {
                categorySelect.value = '';
            }
        }

        updateCategoryWrapVisibility();
        if (getSelectedViewTypes().indexOf('category_wise') === -1 && categorySelect) {
            var hasCat = categorySelect.tomselect
                ? categorySelect.tomselect.getValue()
                : categorySelect.value;
            if (hasCat) clearCategoryValue();
        }

        viewType.addEventListener('change', function () {
            updateCategoryWrapVisibility();
            if (getSelectedViewTypes().indexOf('category_wise') === -1) {
                clearCategoryValue();
            }
            initOrRebuildItemTomSelect({ clear: true });
        });
    }

    if (categorySelect) {
        categorySelect.addEventListener('change', function () {
            if (!categoryIdWrap || categoryIdWrap.classList.contains('d-none')) return;
            initOrRebuildItemTomSelect({ clear: true });
        });
    }

    if (itemSelectEl && originalItemOptions) {
        initOrRebuildItemTomSelect();
    }
});

function printPurchaseSaleQuantity() {
    var cardBody = document.getElementById('purchaseSaleReportCardBody');
    if (!cardBody) {
        window.print();
        return;
    }

    var viewTypesRaw = cardBody.getAttribute('data-view-types') || '["item_wise"]';
    var viewTypes = [];
    try {
        viewTypes = JSON.parse(viewTypesRaw);
    } catch (e) {
        viewTypes = ['item_wise'];
    }
    if (!Array.isArray(viewTypes)) {
        viewTypes = ['item_wise'];
    }
    var multiView = viewTypes.length > 1;
    var printCfg = (typeof window.__purchaseSalePrintConfig === 'object' && window.__purchaseSalePrintConfig)
        ? window.__purchaseSalePrintConfig
        : {};
    var printImages = (typeof window.__purchaseSalePrintImages === 'object' && window.__purchaseSalePrintImages) ? window.__purchaseSalePrintImages : {};
    var emblemSrc = printImages.emblemSrc || '';
    var lbsnaaLogoSrc = printImages.lbsnaaLogoSrc || '';
    var periodBar = printCfg.periodBar || '';
    var storeLabel = printCfg.storeLabel || 'All Stores';
    var itemsLabel = printCfg.itemsLabel || 'All Items';
    var viewLabel = printCfg.viewLabel || '';
    var printedOn = new Date().toLocaleDateString() + ' ' + new Date().toLocaleTimeString();

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function tableToPrintFragment(tbl) {
        var thead = tbl.querySelector('thead');
        var tbody = tbl.querySelector('tbody');
        var headHtml = thead ? thead.innerHTML : '';
        var bodyHtml = tbody ? tbody.innerHTML : '';
        return (
            '<table class="data-table">' +
            '<thead>' + headHtml + '</thead>' +
            '<tbody>' + bodyHtml + '</tbody>' +
            '</table>'
        );
    }

    var tablesHtml = '';
    cardBody.querySelectorAll('.purchase-sale-view-section').forEach(function (sec) {
        var vt = sec.getAttribute('data-view-type') || 'item_wise';
        if (multiView) {
            var secTitle = sec.querySelector('h6.purchase-sale-section-heading');
            if (secTitle) {
                tablesHtml += '<div class="view-section-heading">' + escapeHtml(secTitle.textContent.trim()) + '</div>';
            }
        }
        if (vt === 'item_wise') {
            var single = sec.querySelector('.table-responsive table');
            if (single) {
                tablesHtml += tableToPrintFragment(single);
            }
        } else {
            sec.querySelectorAll('.purchase-sale-group-block').forEach(function (block) {
                var titleEl = block.querySelector('h6');
                var titleText = titleEl ? titleEl.textContent.trim() : '';
                var tbl = block.querySelector('table');
                if (titleText) {
                    tablesHtml += '<div class="group-title">' + escapeHtml(titleText) + '</div>';
                }
                if (tbl) {
                    tablesHtml += tableToPrintFragment(tbl);
                }
            });
        }
    });

    if (!tablesHtml.trim()) {
        tablesHtml = '<p class="no-data">No data found for the selected filters.</p>';
    }

    var lbsnaaLogoImg = lbsnaaLogoSrc
        ? '<img src="' + escapeHtml(lbsnaaLogoSrc) + '" alt="LBSNAA Logo" style="width:40px;height:40px;" onerror="this.style.display=\'none\'">'
        : '';

    var printWindow = window.open('about:blank', '_blank', 'width=1200,height=900');
    if (!printWindow) {
        window.print();
        return;
    }
    try { printWindow.opener = null; } catch (ignore) {}

    printWindow.document.open();
    printWindow.document.write('<!doctype html>\n' +
'<html lang="en">\n' +
'<head>\n' +
'  <meta charset="utf-8">\n' +
'  <title>Item Report - OFFICER\'S MESS LBSNAA MUSSOORIE</title>\n' +
'  <style>\n' +
'    * { box-sizing: border-box; }\n' +
'    @page { size: A4 landscape; margin: 12mm 10mm; }\n' +
'    body {\n' +
'      font-family: system-ui, -apple-system, "Segoe UI", Arial, sans-serif;\n' +
'      font-size: 9pt; margin: 0; padding: 12mm 10mm;\n' +
'      color: #212529; background: #fff; line-height: 1.4;\n' +
'      -webkit-print-color-adjust: exact; print-color-adjust: exact;\n' +
'    }\n' +
'    .pdf-header { border-bottom: 2.5px solid #0b4a7e; padding-bottom: 8px; margin-bottom: 10px; }\n' +
'    .pdf-header table { width: 100%; border-collapse: collapse; }\n' +
'    .pdf-header td { border: 0; padding: 0; vertical-align: middle; }\n' +
'    .pdf-header .hdr-left { width: 50px; }\n' +
'    .pdf-header .hdr-left img { width: 40px; height: 40px; }\n' +
'    .pdf-header .hdr-center { padding-left: 10px; }\n' +
'    .pdf-header .hdr-right { width: 50px; text-align: right; }\n' +
'    .pdf-header .hdr-right img { width: 40px; height: 40px; }\n' +
'    .brand-1 { font-size: 7pt; text-transform: uppercase; letter-spacing: 0.06em; color: #0b4a7e; font-weight: 600; }\n' +
'    .brand-2 { font-size: 9.5pt; font-weight: 700; text-transform: uppercase; color: #111; margin-top: 2px; }\n' +
'    .brand-3 { font-size: 7.5pt; color: #555; margin-top: 2px; }\n' +
'    .report-title-block { text-align: center; margin-bottom: 8px; padding-bottom: 6px; border-bottom: 1px solid #dee2e6; }\n' +
'    .report-title { font-size: 10pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #0f172a; margin: 0 0 5px; }\n' +
'    .report-date-pill { display: inline-block; background: #0b4a7e; color: #fff; font-weight: 600; font-size: 8pt; padding: 3px 12px; border-radius: 10px; }\n' +
'    .report-meta { font-size: 8pt; margin-bottom: 8px; line-height: 1.5; color: #334155; }\n' +
'    .report-meta .meta-label { font-weight: 700; color: #0f172a; }\n' +
'    .data-table { width: 100%; border-collapse: collapse; font-size: 8pt; margin-bottom: 10px; }\n' +
'    .data-table th, .data-table td { padding: 4px 6px; border: 1px solid #d1d5db; vertical-align: middle; }\n' +
'    .data-table thead { display: table-header-group; }\n' +
'    .data-table thead th { background: #0b4a7e !important; color: #fff !important; font-weight: 600; font-size: 8pt; text-align: left; white-space: nowrap; }\n' +
'    .data-table thead th.text-end { text-align: right !important; }\n' +
'    .data-table thead th.text-center { text-align: center !important; }\n' +
'    .data-table .text-end { text-align: right; }\n' +
'    .data-table .text-center { text-align: center; }\n' +
'    .data-table tbody tr:nth-child(even) td { background: #f9fafb; }\n' +
'    .data-table thead th.border-0 { border: 1px solid rgba(255,255,255,0.15) !important; }\n' +
'    .data-table thead th.bg-body-secondary { background: #0b4a7e !important; }\n' +
'    .data-table thead th.table-primary { background: #0b4a7e !important; }\n' +
'    .view-section-heading { margin-top: 12px; margin-bottom: 6px; font-weight: 700; font-size: 9pt; color: #0b4a7e; border-bottom: 2px solid #0b4a7e; padding-bottom: 3px; text-transform: uppercase; letter-spacing: 0.03em; }\n' +
'    .group-title { margin-top: 10px; margin-bottom: 4px; font-weight: 700; font-size: 9pt; color: #0b4a7e; background: #eef2f6; padding: 5px 8px; border-left: 3px solid #0b4a7e; }\n' +
'    .no-data { font-size: 8pt; margin: 10px 0; color: #64748b; padding: 12px; background: #f8fafc; border: 1px dashed #cbd5e1; text-align: center; }\n' +
'    .footer { border-top: 1px solid #dee2e6; font-size: 7pt; color: #64748b; text-align: center; padding-top: 5px; margin-top: 10px; }\n' +
'    tr { page-break-inside: avoid; }\n' +
'    @media print { body { margin: 0; padding: 0; } }\n' +
'  </style>\n' +
'</head>\n' +
'<body>\n' +
'<div class="pdf-header">\n' +
'  <table>\n' +
'    <tr>\n' +
'      <td class="hdr-left"><img src="' + escapeHtml(emblemSrc) + '" alt="Emblem of India"></td>\n' +
'      <td class="hdr-center">\n' +
'        <div class="brand-1">Government of India</div>\n' +
'        <div class="brand-2">OFFICER\'S MESS LBSNAA MUSSOORIE</div>\n' +
'        <div class="brand-3">Lal Bahadur Shastri National Academy of Administration</div>\n' +
'      </td>\n' +
'      <td class="hdr-right">' + lbsnaaLogoImg + '</td>\n' +
'    </tr>\n' +
'  </table>\n' +
'</div>\n' +
'<div class="report-title-block">\n' +
'  <h1 class="report-title">Item Report</h1>\n' +
'  <div class="report-date-pill">' + escapeHtml(periodBar) + '</div>\n' +
'</div>\n' +
'<div class="report-meta">\n' +
'  <span class="meta-label">View:</span> ' + escapeHtml(viewLabel) + '<br>\n' +
'  <span class="meta-label">Store:</span> ' + escapeHtml(storeLabel) + '<br>\n' +
'  <span class="meta-label">Items:</span> ' + escapeHtml(itemsLabel) + '<br>\n' +
'  <span class="meta-label">Printed on:</span> ' + escapeHtml(printedOn) + '\n' +
'</div>\n' +
tablesHtml +
'<div class="footer">\n' +
'  <small>Officer\'s Mess LBSNAA Mussoorie \u2014 Item Report (Purchase / Sale Quantity)</small>\n' +
'</div>\n' +
'<script>(function(){var imgs=document.querySelectorAll("img");var n=imgs.length;if(!n){setTimeout(function(){window.print();},100);return;}var left=n;function done(){if(--left<=0)setTimeout(function(){window.print();},150);}for(var i=0;i<imgs.length;i++){var img=imgs[i];if(img.complete){done();}else{img.addEventListener("load",done);img.addEventListener("error",done);}}})();<\\/script>\n' +
'</body>\n' +
'</html>');
    printWindow.document.close();
}
</script>
@endsection
