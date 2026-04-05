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
        <div id="purchaseSaleReportCardBody" class="card-body p-0" data-view-types='@json($viewTypes)'>
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
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" rel="stylesheet" />

<style>
    @media print {
        .no-print { display: none !important; }
        .report-header { display: block !important; }
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
    var lbsnaaLogoSvgInline = printImages.lbsnaaLogoSvgInline || '';
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
            '<table class="purchase-sale-data">' +
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
                tablesHtml += '<div class="group-title">' + escapeHtml(secTitle.textContent.trim()) + '</div>';
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

    var lbsnaaLogoCell = '';
    if (lbsnaaLogoSvgInline) {
        lbsnaaLogoCell = '<td class="branding-logo-svg-td"><div class="lbsnaa-inline-svg-wrap">' + lbsnaaLogoSvgInline + '</div></td>';
    } else if (lbsnaaLogoSrc) {
        lbsnaaLogoCell = '<td style="width:48px;"><img src="' + escapeHtml(lbsnaaLogoSrc) + '" alt="LBSNAA" class="header-img-right-seal"></td>';
    } else {
        lbsnaaLogoCell = '<td style="width:48px;"></td>';
    }

    var docTitle = 'Item Report - OFFICER\'S MESS LBSNAA MUSSOORIE';

    var printWindow = window.open('about:blank', '_blank', 'width=1200,height=900');
    if (!printWindow) {
        window.print();
        return;
    }
    try {
        printWindow.opener = null;
    } catch (ignore) {}

    printWindow.document.open();
    printWindow.document.write('<!doctype html>\n' +
'<html lang="en">\n' +
'<head>\n' +
'  <meta charset="utf-8">\n' +
'  <meta name="viewport" content="width=device-width, initial-scale=1">\n' +
'  <title>' + docTitle + '</title>\n' +
'  <style>\n' +
'    * { box-sizing: border-box; }\n' +
'    body {\n' +
'      font-family: "DejaVu Sans", "Noto Sans Devanagari", "Segoe UI", Arial, sans-serif;\n' +
'      font-size: 9pt;\n' +
'      margin: 0;\n' +
'      padding: 10mm 12mm;\n' +
'      color: #222;\n' +
'      background: #fff;\n' +
'      -webkit-print-color-adjust: exact;\n' +
'      print-color-adjust: exact;\n' +
'    }\n' +
'    @page { size: A4 landscape; margin: 10mm 12mm; }\n' +
'    .lbsnaa-header-wrap {\n' +
'      border-bottom: 3px solid #003366;\n' +
'      margin-bottom: 10px;\n' +
'      padding: 4px 0 10px;\n' +
'    }\n' +
'    .branding-table { width: 100%; border-collapse: collapse; margin: 0; }\n' +
'    .branding-table td { border: 0; padding: 0; vertical-align: middle; }\n' +
'    .branding-logo-left { width: 48px; }\n' +
'    .branding-text { text-align: center; padding: 0 12px; line-height: 1.25; }\n' +
'    .branding-logo-right { width: 220px; }\n' +
'    .branding-right-inner { width: 100%; border-collapse: collapse; }\n' +
'    .branding-right-inner td { border: 0; vertical-align: middle; padding: 0; }\n' +
'    .lbsnaa-brand-line-1 {\n' +
'      font-size: 8pt;\n' +
'      color: #0070c0;\n' +
'      text-transform: uppercase;\n' +
'      letter-spacing: 0.06em;\n' +
'      font-weight: 600;\n' +
'    }\n' +
'    .lbsnaa-brand-line-2 {\n' +
'      font-size: 12pt;\n' +
'      color: #111;\n' +
'      font-weight: 700;\n' +
'      text-transform: uppercase;\n' +
'      margin-top: 3px;\n' +
'    }\n' +
'    .lbsnaa-brand-line-3 {\n' +
'      font-size: 9pt;\n' +
'      color: #4a5a6a;\n' +
'      margin-top: 3px;\n' +
'    }\n' +
'    .header-img-left { width: 40px; height: 40px; object-fit: contain; display: block; }\n' +
'    .header-img-right-seal { width: 44px; height: 44px; object-fit: contain; display: block; }\n' +
'    .branding-logo-svg-td { width: 140px; vertical-align: middle; }\n' +
'    .lbsnaa-inline-svg-wrap { line-height: 0; }\n' +
'    .lbsnaa-inline-svg-wrap svg { width: 130px; max-width: 100%; height: auto; max-height: 44px; display: block; }\n' +
'    .branding-right-text { text-align: left; padding-left: 8px; line-height: 1.2; }\n' +
'    .branding-hindi { font-size: 8pt; color: #7b2d26; font-weight: 600; }\n' +
'    .branding-en-side { font-size: 7pt; color: #7b2d26; margin-top: 2px; }\n' +
'    .report-header-block {\n' +
'      text-align: center;\n' +
'      margin-bottom: 10px;\n' +
'      padding-bottom: 8px;\n' +
'      border-bottom: 1px solid #dee2e6;\n' +
'    }\n' +
'    .report-title-center {\n' +
'      font-size: 13pt;\n' +
'      font-weight: 700;\n' +
'      text-transform: uppercase;\n' +
'      margin: 0 0 6px;\n' +
'      color: #212529;\n' +
'    }\n' +
'    .report-date-bar {\n' +
'      background: #003366;\n' +
'      color: #fff;\n' +
'      padding: 6px 12px;\n' +
'      text-align: center;\n' +
'      font-weight: 600;\n' +
'      font-size: 9pt;\n' +
'      display: inline-block;\n' +
'    }\n' +
'    .report-meta-print { font-size: 8pt; margin: 8px 0 10px; line-height: 1.45; }\n' +
'    .report-meta-print .meta-line { margin-bottom: 3px; word-wrap: break-word; }\n' +
'    table.purchase-sale-data {\n' +
'      width: 100%;\n' +
'      border-collapse: collapse;\n' +
'      font-size: 8pt;\n' +
'      margin-bottom: 8px;\n' +
'    }\n' +
'    table.purchase-sale-data thead { display: table-header-group; }\n' +
'    table.purchase-sale-data th,\n' +
'    table.purchase-sale-data td { padding: 4px 6px; border: 1px solid #dee2e6; vertical-align: middle; }\n' +
'    table.purchase-sale-data thead th {\n' +
'      background: #d3d6d9;\n' +
'      font-weight: 600;\n' +
'      text-align: left;\n' +
'    }\n' +
'    table.purchase-sale-data thead th.text-end,\n' +
'    table.purchase-sale-data thead th[style*="text-align: right"] { text-align: right; }\n' +
'    table.purchase-sale-data thead th.text-center,\n' +
'    table.purchase-sale-data thead th[style*="text-align: center"] { text-align: center; }\n' +
'    table.purchase-sale-data .text-end { text-align: right; }\n' +
'    table.purchase-sale-data .text-center { text-align: center; }\n' +
'    table.purchase-sale-data tbody tr:nth-child(even) td { background: #fafbfc; }\n' +
'    .group-title {\n' +
'      margin-top: 8px;\n' +
'      margin-bottom: 4px;\n' +
'      font-weight: 700;\n' +
'      font-size: 9pt;\n' +
'      color: #003366;\n' +
'    }\n' +
'    .no-data { font-size: 9pt; margin: 10px 0; color: #555; }\n' +
'    .footer {\n' +
'      border-top: 1px solid #dee2e6;\n' +
'      font-size: 7pt;\n' +
'      color: #666;\n' +
'      text-align: center;\n' +
'      padding-top: 5px;\n' +
'      margin-top: 6px;\n' +
'    }\n' +
'  </style>\n' +
'</head>\n' +
'<body>\n' +
'<div class="lbsnaa-header-wrap">\n' +
'  <table class="branding-table">\n' +
'    <tr>\n' +
'      <td class="branding-logo-left">\n' +
'        <img src="' + escapeHtml(emblemSrc) + '" alt="Emblem of India" class="header-img-left">\n' +
'      </td>\n' +
'      <td class="branding-text">\n' +
'        <div class="lbsnaa-brand-line-1">Government of India</div>\n' +
'        <div class="lbsnaa-brand-line-2">OFFICER\'S MESS LBSNAA MUSSOORIE</div>\n' +
'        <div class="lbsnaa-brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>\n' +
'      </td>\n' +
'      <td class="branding-logo-right">\n' +
'        <table class="branding-right-inner">\n' +
'          <tr>\n' +
'            ' + lbsnaaLogoCell + '\n' +
'            <td class="branding-right-text">\n' +
'              <div class="branding-hindi">लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी</div>\n' +
'              <div class="branding-en-side">Lal Bahadur Shastri National Academy of Administration</div>\n' +
'            </td>\n' +
'          </tr>\n' +
'        </table>\n' +
'      </td>\n' +
'    </tr>\n' +
'  </table>\n' +
'</div>\n' +
'<div class="report-header-block">\n' +
'  <h1 class="report-title-center">Item Report</h1>\n' +
'  <div class="report-date-bar">' + escapeHtml(periodBar) + '</div>\n' +
'</div>\n' +
'<div class="report-meta-print">\n' +
'  <div class="meta-line"><strong>View:</strong> ' + escapeHtml(viewLabel) + '</div>\n' +
'  <div class="meta-line"><strong>Store:</strong> ' + escapeHtml(storeLabel) + '</div>\n' +
'  <div class="meta-line"><strong>Items:</strong> ' + escapeHtml(itemsLabel) + '</div>\n' +
'  <div class="meta-line"><strong>Printed on:</strong> ' + escapeHtml(printedOn) + '</div>\n' +
'</div>\n' +
tablesHtml +
'<div class="footer">\n' +
'  <small>Officer\'s Mess LBSNAA Mussoorie — Item Report (Purchase / Sale Quantity)</small>\n' +
'</div>\n' +
'<script>(function(){function runPrint(){window.print();}function afterImages(){var imgs=document.querySelectorAll("img");var n=imgs.length;if(!n){setTimeout(runPrint,100);return;}var left=n;function one(){if(--left<=0)setTimeout(runPrint,150);}for(var i=0;i<imgs.length;i++){var img=imgs[i];if(img.complete){one();}else{img.addEventListener("load",one);img.addEventListener("error",one);}}}window.addEventListener("load",afterImages);})();<\\/script>\n' +
'</body>\n' +
'</html>');
    printWindow.document.close()
}
</script>
@endsection
