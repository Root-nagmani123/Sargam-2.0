@extends('admin.layouts.master')
@section('title', 'Item Report')
@section('content')
@include('admin.mess.reports.partials.report-styles')
@php
    /** @var array<int> $storeIds */
    $storeIds = isset($storeIds) ? $storeIds : [];
    /** @var array<int> $itemIds */
    $itemIds = isset($itemIds) ? $itemIds : [];
    /** @var array<int, string> $viewTypes */
    $viewTypes = isset($viewTypes) ? $viewTypes : ['item_wise'];
    /** @var int $perPage */
    $perPage = isset($perPage) ? (int) $perPage : 25;

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
<div class="container-fluid purchase-sale-quantity-report py-3 py-md-4">
    <script>
        window.__purchaseSalePrintConfig = @json($purchaseSalePrintConfig);
        window.__purchaseSalePrintImages = @json($purchaseSalePrintImages);
    </script>
    <x-breadcrum title="Item Report" :showBack="false"></x-breadcrum>

    @php $psqExportQ = request()->query(); $psqExportQuery = $psqExportQ ? '?' . http_build_query($psqExportQ) : ''; @endphp
    {{-- Download / Print bar --}}
    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <a href="{{ route('admin.mess.reports.purchase-sale-quantity.excel') }}{{ $psqExportQuery }}" class="btn psq-export-btn border-0" title="Download (Excel)">
            <i class="material-symbols-rounded">download</i><span>Download</span>
        </a>
        <button type="button" class="btn psq-export-btn border-0" onclick="printPurchaseSaleQuantity()" title="Print (or Save as PDF)">
            <i class="material-symbols-rounded">print</i><span>Print</span>
        </button>
    </div>
    {{-- Report card --}}
    <div class="card border-0 rounded-4 shadow-sm overflow-hidden">
        <div class="card-header border-0 py-4 text-center report-header psq-report-header">
            <h4 class="fw-bold mb-2 text-white lh-sm">Item Report</h4>
            <span class="badge bg-white bg-opacity-25 rounded-1 px-3 py-2 mb-2 d-inline-flex align-items-center gap-1 text-white">
                <span class="material-symbols-rounded" style="font-size:0.9rem;">date_range</span>
                From {{ $purchaseSalePeriodFromLabel }} to {{ $purchaseSalePeriodToLabel }}
            </span>
            <div class="d-flex flex-wrap justify-content-center gap-2 mt-2">
                <span class="badge bg-white bg-opacity-15 rounded-1 px-2 py-1 small d-inline-flex align-items-center gap-1 text-primary">
                    <span class="material-symbols-rounded" style="font-size:0.8rem;">visibility</span>
                    {{ $messViewLabel }}
                </span>
                <span class="badge bg-white bg-opacity-15 rounded-1 px-2 py-1 small d-inline-flex align-items-center gap-1 text-primary">
                    <span class="material-symbols-rounded" style="font-size:0.8rem;">storefront</span>
                    {{ ($selectedStoreName !== null && $selectedStoreName !== '') ? $selectedStoreName : 'All Stores' }}
                </span>
                <span class="badge bg-white bg-opacity-15 rounded-1 px-2 py-1 small d-inline-flex align-items-center gap-1 text-primary">
                    <span class="material-symbols-rounded" style="font-size:0.8rem;">inventory_2</span>
                    {{ ($selectedItemNamesLabel !== null && $selectedItemNamesLabel !== '') ? $selectedItemNamesLabel : 'All Items' }}
                </span>
            </div>
        </div>
        <div id="purchaseSaleReportCardBody" class="card-body p-0 psq-scroll-wrapper" data-view-types='@json($viewTypes)'>
            <div class="card-body p-3 p-lg-4">
            <div class="d-flex align-items-center gap-2 psq-filter-toolbar">
                <form id="purchaseSaleQuantityFilterForm" method="GET" action="{{ route('admin.mess.reports.purchase-sale-quantity') }}" class="d-flex align-items-center gap-2 flex-wrap psq-filter-form">
                    <input type="hidden" name="refresh" value="1">
                    <span class="programme-dt-filters-label flex-shrink-0">Filter</span>
                    <div class="psq-filter-item">
                        <input type="date" name="from_date" class="form-control psq-filter-date psq-auto-filter" value="{{ $fromDate }}" aria-label="From date">
                    </div>
                    <div class="psq-filter-item">
                        <input type="date" name="to_date" class="form-control psq-filter-date psq-auto-filter" value="{{ $toDate }}" aria-label="To date">
                    </div>
                    <div class="psq-filter-item">
                        <select name="view_type[]" id="viewType" class="form-select purchase-sale-view-tomselect psq-auto-filter" multiple data-placeholder="View">
                            <option value="item_wise" @selected(in_array('item_wise', $viewTypes, true))>Item-wise</option>
                            <option value="subcategory_wise" @selected(in_array('subcategory_wise', $viewTypes, true))>Subcategory-wise</option>
                            <option value="category_wise" @selected(in_array('category_wise', $viewTypes, true))>Category-wise</option>
                        </select>
                    </div>
                    <div class="psq-filter-item{{ in_array('category_wise', $viewTypes, true) ? '' : ' d-none' }}" id="categoryIdWrap">
                        <select name="category_id" id="categoryId" class="form-select purchase-sale-category-tomselect psq-auto-filter" data-placeholder="All categories">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="psq-filter-item">
                        <select name="item_id[]" id="purchase_sale_item_id" class="form-select purchase-sale-item-multiselect psq-auto-filter" multiple data-placeholder="All Items">
                            @foreach($allItems as $it)
                                <option value="{{ $it->id }}" data-category-id="{{ $it->category_id ?? '' }}" @selected(in_array((int) $it->id, $itemIds, true))>{{ $it->item_name ?? $it->subcategory_name ?? $it->name ?? '—' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="psq-filter-item">
                        <select name="store_id[]" id="purchase_sale_store_id" class="form-select purchase-sale-store-multiselect psq-auto-filter" multiple data-placeholder="All Stores">
                            @foreach($stores ?? [] as $store)
                                <option value="{{ $store->id }}" @selected(in_array((int) $store->id, $storeIds, true))>{{ $store->store_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="per_page" id="psqPerPageHidden" value="{{ (int) $perPage }}">
                    <input type="hidden" name="search" id="psqSearchHidden" value="{{ request('search') }}">
                    <a href="{{ route('admin.mess.reports.purchase-sale-quantity') }}" id="psqRemoveFilter" class="programme-dt-btn-reset flex-shrink-0 d-inline-flex align-items-center justify-content-center text-decoration-none" title="Remove all filters">Remove Filter</a>
                </form>
                <div class="d-flex align-items-center gap-2 ms-auto flex-shrink-0">
                    <input type="search" id="psqSearch" class="form-control psq-search-input" placeholder="Search item…" autocomplete="off">
                </div>
            </div>
        </div>
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
                            <table class="table programme-dt-table align-middle mb-0 psq-table">
                                <thead>
                                    <tr>
                                        @include('admin.mess.reports.partials.report-sno-th', ['class' => 'border-0 py-3 text-center'])
                                        @include('admin.mess.reports.partials.report-sort-th', ['sortKey' => 'item_name', 'label' => 'Item Name', 'defaultDir' => 'asc', 'defaultSort' => 'item_name', 'class' => 'border-0 py-3'])
                                        <th class="border-0 py-3" style="width:80px;">Unit</th>
                                        <th class="text-end border-0 py-3">Total Purchase Qty</th>
                                        <th class="text-end border-0 py-3">Avg Purchase Price</th>
                                        <th class="text-end border-0 py-3">Total Sale Qty</th>
                                        <th class="text-end border-0 py-3">Avg Sale Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $psqItemPaginator = $section['paginator'] ?? null; @endphp
                                    @forelse($section['reportData'] as $index => $row)
                                        <tr class="psq-data-row">
                                            <td class="text-center text-body-secondary small fw-medium mess-report-sno-cell">@include('admin.mess.reports.partials.report-serial-number', ['paginator' => $psqItemPaginator ?? ($section['paginator'] ?? null), 'index' => $index])</td>
                                            <td class="fw-medium">{{ $row['item_name'] }}</td>
                                            <td><span class="badge bg-body-secondary text-body-emphasis rounded-1 px-2">{{ $row['unit'] }}</span></td>
                                            <td class="text-end">{{ number_format($row['purchase_qty'], 2) }}</td>
                                            <td class="text-end">{{ $row['avg_purchase_price'] !== null ? '₹' . number_format($row['avg_purchase_price'], 2) : '—' }}</td>
                                            <td class="text-end">{{ number_format($row['sale_qty'], 2) }}</td>
                                            <td class="text-end">{{ $row['avg_sale_price'] !== null ? '₹' . number_format($row['avg_sale_price'], 2) : '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-body-secondary py-5">
                                                <div class="d-flex flex-column align-items-center gap-2">
                                                    <span class="material-symbols-rounded text-body-tertiary" style="font-size: 3rem;">inbox</span>
                                                    <span class="fw-medium">No data found</span>
                                                    <span class="small text-body-tertiary">Try adjusting your date range or filters</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                    @if(! empty($section['reportData']))
                                        @php
                                            $psGrandPurchase = collect($section['reportData'])->sum('purchase_qty');
                                            $psGrandSale = collect($section['reportData'])->sum('sale_qty');
                                        @endphp
                                        <tr class="table-secondary fw-semibold">
                                            <td class="text-center">—</td>
                                            <td>Grand Total</td>
                                            <td>—</td>
                                            <td class="text-end">{{ number_format($psGrandPurchase, 2) }}</td>
                                            <td class="text-end">—</td>
                                            <td class="text-end">{{ number_format($psGrandSale, 2) }}</td>
                                            <td class="text-end">—</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    @else
                        @php
                            $groupedData = $section['groupedData'] ?? [];
                            $psqGroupedSerial = 0;
                            if (($section['viewType'] ?? '') === 'category_wise' && ! empty($section['paginator']) && $section['paginator']->firstItem() !== null) {
                                $psqGroupedSerial = (int) $section['paginator']->firstItem() - 1;
                            }
                        @endphp
                        @forelse($groupedData as $group)
                            <div class="purchase-sale-group-block mb-0 border-bottom border-secondary border-opacity-25">
                                <div class="px-4 pt-3 pb-2">
                                    <h6 class="text-primary fw-semibold mb-0 d-flex align-items-center gap-2">
                                        <span class="material-symbols-rounded" style="font-size: 1.25rem;">category</span>
                                        {{ $group['category_name'] }}
                                    </h6>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 psq-table">
                                        <thead>
                                            <tr>
                                                @include('admin.mess.reports.partials.report-sno-th', ['class' => 'border-0 py-3 text-center'])
                                                <th class="border-0 py-3">Item Name</th>
                                                <th class="border-0 py-3" style="width:80px;">Unit</th>
                                                <th class="text-end border-0 py-3">Total Purchase Qty</th>
                                                <th class="text-end border-0 py-3">Avg Purchase Price</th>
                                                <th class="text-end border-0 py-3">Total Sale Qty</th>
                                                <th class="text-end border-0 py-3">Avg Sale Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($group['items'] as $idx => $row)
                                                @php $psqGroupedSerial++; @endphp
                                                <tr class="psq-data-row">
                                                    <td class="text-center text-body-secondary small fw-medium mess-report-sno-cell">@include('admin.mess.reports.partials.report-serial-number', ['start' => $psqGroupedSerial, 'index' => 0])</td>
                                                    <td class="fw-medium">{{ $row['item_name'] }}</td>
                                                    <td><span class="badge bg-body-secondary text-body-emphasis rounded-1 px-2">{{ $row['unit'] }}</span></td>
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
                            <div class="text-center py-5">
                                <span class="material-symbols-rounded text-body-tertiary d-block mb-2" style="font-size:3rem;">search_off</span>
                                <p class="fw-medium mb-1">No data found</p>
                                <span class="small text-body-tertiary">Try adjusting your filters</span>
                            </div>
                        @endforelse
                        @if(! empty($groupedData ?? []) && ! empty($section['reportData']))
                            @php
                                $psGrandPurchaseGrouped = collect($section['reportData'])->sum('purchase_qty');
                                $psGrandSaleGrouped = collect($section['reportData'])->sum('sale_qty');
                            @endphp
                            <div class="table-responsive border-top border-secondary border-opacity-25">
                                <table class="table align-middle mb-0">
                                    <tbody>
                                        <tr class="table-secondary fw-semibold">
                                            <td class="text-center" style="width: 60px;">—</td>
                                            <td colspan="2">Grand Total</td>
                                            <td class="text-end">{{ number_format($psGrandPurchaseGrouped, 2) }}</td>
                                            <td class="text-end">—</td>
                                            <td class="text-end">{{ number_format($psGrandSaleGrouped, 2) }}</td>
                                            <td class="text-end">—</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif
                    @if(! empty($section['paginator']))
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 py-3 px-3 border-top no-print">
                            <div class="psq-pagination-links">
                                @if($section['paginator']->hasPages())
                                    {{ $section['paginator']->withQueryString()->links('pagination::bootstrap-5') }}
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-2 small text-body-secondary ms-auto">
                                <span>Showing</span>
                                <select id="psqPerPage" class="form-select form-select-sm" style="width:auto;">
                                    @foreach([10, 25, 50, 100] as $n)
                                        <option value="{{ $n }}" @selected((int) $perPage === (int) $n)>{{ $n }}</option>
                                    @endforeach
                                </select>
                                <span>of {{ number_format($section['paginator']->total()) }} items</span>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-5">
                    <span class="material-symbols-rounded text-body-tertiary d-block mb-2" style="font-size:3rem;">search_off</span>
                    <p class="fw-medium mb-1">No data found</p>
                    <span class="small text-body-tertiary">Apply filters to view report data</span>
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Tom Select: view, category, item & store filters --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<link rel="stylesheet" href="{{ asset('admin_assets/css/material-icons-local.css') }}" />

<style>
    /* ── Filter card polish ── */
    .purchase-sale-quantity-report .psq-filter-card {
        border-top: 3px solid var(--bs-primary, #0d6efd) !important;
    }
    .purchase-sale-quantity-report .psq-filter-header {
        border-bottom: 1px solid rgba(0,0,0,0.06);
    }
    .purchase-sale-quantity-report .psq-filter-card .form-control,
    .purchase-sale-quantity-report .psq-filter-card .form-select {
        border-radius: 0.5rem;
        border-color: #dee2e6;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .purchase-sale-quantity-report .psq-filter-card .form-control:focus,
    .purchase-sale-quantity-report .psq-filter-card .form-select:focus {
        border-color: var(--bs-primary, #0d6efd);
        box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.15);
    }

    /* ── Report header gradient ── */
    .purchase-sale-quantity-report .psq-report-header {
        background: linear-gradient(135deg, #0b4a7e 0%, #1565c0 50%, #1976d2 100%) !important;
    }

    /* ── Scrollable table body with sticky header ── */
    @media screen {
        .purchase-sale-quantity-report .psq-scroll-wrapper {
            overflow-x: auto;
            overflow-y: auto;
            max-height: min(72vh, calc(100dvh - 12rem));
            display: block !important;
            position: relative;
        }
        .purchase-sale-quantity-report .psq-scroll-wrapper .table-responsive {
            overflow: visible !important;
            overflow-x: visible !important;
            overflow-y: visible !important;
            -webkit-overflow-scrolling: unset !important;
        }
        .purchase-sale-quantity-report .psq-scroll-wrapper .purchase-sale-view-section {
            overflow: visible !important;
        }
        .purchase-sale-quantity-report .psq-scroll-wrapper .purchase-sale-group-block {
            overflow: visible !important;
        }
        .purchase-sale-quantity-report .psq-table {
            border-collapse: separate !important;
            border-spacing: 0;
        }
        .purchase-sale-quantity-report .psq-table > thead > tr > th {
            position: sticky !important;
            top: 0;
            z-index: 10;
            background: var(--ds-surface-2, #f8fafc) !important;
            color: var(--ds-ink-muted, #667085) !important;
            font-weight: 600;
            border-bottom: 1px solid var(--ds-line, #e5e7eb) !important;
            border-top: none !important;
            border-left: 0 !important;
            border-right: 0 !important;
            padding: 0.75rem;
            font-size: 0.8rem;
            white-space: nowrap;
            text-transform: none;
        }
        .purchase-sale-quantity-report .psq-table > thead > tr > th a,
        .purchase-sale-quantity-report .psq-table > thead > tr > th .mess-report-sort-th-link {
            color: var(--ds-ink-muted, #667085) !important;
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
        .purchase-sale-quantity-report .psq-table > thead > tr > th.text-center {
            text-align: center !important;
        }
        /* Data rows */
        .purchase-sale-quantity-report .psq-table > tbody > tr.psq-data-row > td {
            padding: 0.6rem 0.75rem;
            font-size: 0.875rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: background-color 0.15s ease;
        }
        .purchase-sale-quantity-report .psq-table > tbody > tr.psq-data-row:hover > td {
            background-color: rgba(13,110,253,0.04) !important;
        }
        .purchase-sale-quantity-report .psq-table > tbody > tr.psq-data-row:nth-child(even) > td {
            background-color: rgba(0,0,0,0.015);
        }
        .purchase-sale-quantity-report .psq-table > tbody > tr.psq-data-row:nth-child(even):hover > td {
            background-color: rgba(13,110,253,0.04) !important;
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
        .purchase-sale-quantity-report .psq-scroll-wrapper::-webkit-scrollbar { height: 8px; width: 8px; }
        .purchase-sale-quantity-report .psq-scroll-wrapper::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 8px; }
        .purchase-sale-quantity-report .psq-scroll-wrapper::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
        .purchase-sale-quantity-report .psq-scroll-wrapper::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    }

    @media print {
        .no-print { display: none !important; }
        .report-header { display: block !important; }
        .purchase-sale-quantity-report .psq-report-header { background: #0b4a7e !important; }
        .purchase-sale-quantity-report .psq-scroll-wrapper { max-height: none !important; overflow: visible !important; }
        .purchase-sale-quantity-report .psq-table > thead > tr > th { position: static !important; box-shadow: none !important; text-transform: none !important; }
    }

    .purchase-sale-quantity-report .card-footer .btn {
        transition: all 0.2s ease;
    }
    .purchase-sale-quantity-report .card-footer .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.12);
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

    /* ── New-design chrome: Download/Print bar + single-row filter toolbar (token-based per design.md) ── */
    .purchase-sale-quantity-report .psq-export-btn {
        background: var(--ds-surface, #fff); border: 1px solid var(--ds-line, #e5e7eb);
        color: var(--ds-primary, #004a93); border-radius: var(--ds-radius, 4px);
        min-height: var(--ds-control-h, 40px); padding: 0 1rem; font-weight: 500; font-size: 0.875rem;
        display: inline-flex; align-items: center; gap: 0.4rem;
    }
    .purchase-sale-quantity-report .psq-export-btn:hover { background: var(--ds-surface-2, #f8fafc); border-color: var(--ds-primary, #004a93); }
    .purchase-sale-quantity-report .psq-export-btn .material-symbols-rounded { font-size: 1.15rem; }
    .purchase-sale-quantity-report .psq-filter-toolbar,
    .purchase-sale-quantity-report .psq-filter-form { flex-wrap: wrap; gap: 0.5rem; }
    .purchase-sale-quantity-report .psq-filter-item { flex-shrink: 0; }
    .purchase-sale-quantity-report .psq-filter-date {
        min-height: var(--ds-control-h, 40px); height: var(--ds-control-h, 40px); width: 9.5rem;
        border-radius: var(--ds-radius, 4px); border: 1px solid var(--ds-line, #e5e7eb); font-size: 0.85rem;
    }
    .purchase-sale-quantity-report .psq-filter-toolbar .form-select,
    .purchase-sale-quantity-report .psq-filter-toolbar .ts-wrapper { min-width: 10rem; }
    .purchase-sale-quantity-report .psq-search-input {
        min-height: var(--ds-control-h, 40px); height: var(--ds-control-h, 40px); width: 13rem;
        border-radius: var(--ds-radius, 4px); border: 1px solid var(--ds-line, #e5e7eb); font-size: 0.85rem;
    }
    /* On-screen: hide the blue print-style report header (mock goes straight to the table; the print window builds its own) */
    .purchase-sale-quantity-report .psq-report-header { display: none !important; }
    .purchase-sale-quantity-report .psq-pagination-links p { display: none !important; }
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
<script>
    // New-design toolbar: debounced auto-apply (GET reload) + client-side item search.
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('purchaseSaleQuantityFilterForm');
        if (form) {
            var psqTimer = null;
            form.addEventListener('change', function (e) {
                if (!e.target || !e.target.classList || !e.target.classList.contains('psq-auto-filter')) return;
                if (psqTimer) clearTimeout(psqTimer);
                psqTimer = setTimeout(function () { form.submit(); }, 500);
            });
        }
        var psqSearch = document.getElementById('psqSearch');
        if (psqSearch) {
            psqSearch.addEventListener('input', function () {
                var q = psqSearch.value.trim().toLowerCase();
                document.querySelectorAll('.purchase-sale-quantity-report .psq-table tbody tr.psq-data-row').forEach(function (tr) {
                    tr.style.display = (!q || tr.textContent.toLowerCase().indexOf(q) !== -1) ? '' : 'none';
                });
            });
        }
        // Rows-per-page (footer select, re-rendered) → hidden field → submit
        document.addEventListener('change', function (e) {
            if (!e.target || e.target.id !== 'psqPerPage') return;
            var hidden = document.getElementById('psqPerPageHidden');
            if (hidden) hidden.value = e.target.value;
            if (form) form.submit();
        });
    });
</script>
@endsection
