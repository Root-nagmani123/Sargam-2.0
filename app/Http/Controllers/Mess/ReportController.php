<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\Inventory;
use App\Models\Mess\Store;
use App\Models\Mess\SubStore;
use App\Models\Mess\ItemCategory;
use App\Models\Mess\ItemSubcategory;
use App\Models\Mess\PurchaseOrder;
use App\Models\Mess\PurchaseOrderItem;
use App\Models\Mess\StoreAllocation;
use App\Models\Mess\StoreAllocationItem;
use App\Models\Mess\Vendor;
use App\Models\Mess\ClientType;
use App\Models\KitchenIssueMaster;
use App\Models\FacultyMaster;
use App\Models\EmployeeMaster;
use App\Models\DepartmentMaster;
use App\Models\CourseMaster;
use Illuminate\Support\Collection;
use App\Exports\StockSummaryExport;
use App\Exports\Mess\StockPurchaseDetailsExport;
use App\Exports\Mess\StockBalanceTillDateExport;
use App\Exports\Mess\PurchaseSaleQuantityExport;
use App\Exports\Mess\SellingVoucherPrintSlipExport;
use App\Exports\Mess\CategoryWisePrintSlipExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use DB;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    /**
     * Academy Staff list for report filters.
     * Excludes Officers Mess department staff and employees that are mapped as Faculty.
     *
     * @return \Illuminate\Support\Collection<int, object{pk:mixed, full_name:string}>
     */
    private function academyStaffEmployees(?DepartmentMaster $officersMessDept)
    {
        $facultyEmployeePks = [];

        // Some deployments may not have employee_master_pk column; guard it.
        try {
            $facultyTable = (new FacultyMaster())->getTable();
            if (Schema::hasColumn($facultyTable, 'employee_master_pk')) {
                $facultyEmployeePks = FacultyMaster::query()
                    ->whereNotNull('employee_master_pk')
                    ->pluck('employee_master_pk')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }
        } catch (\Throwable $e) {
            $facultyEmployeePks = [];
        }

        return EmployeeMaster::query()
            ->when(Schema::hasColumn('employee_master', 'status'), fn ($q) => $q->where('status', 1))
            ->when($officersMessDept, function ($q) use ($officersMessDept) {
                $q->where(function ($sub) use ($officersMessDept) {
                    $sub->whereNull('department_master_pk')
                        ->orWhere('department_master_pk', '!=', $officersMessDept->pk);
                });
            })
            ->when(!empty($facultyEmployeePks), function ($q) use ($facultyEmployeePks) {
                $q->whereNotIn('pk', $facultyEmployeePks);
            })
            ->orderBy('first_name')->orderBy('last_name')
            ->get(['pk', 'first_name', 'middle_name', 'last_name'])
            ->map(function ($e) {
                $fullName = trim(($e->first_name ?? '') . ' ' . ($e->middle_name ?? '') . ' ' . ($e->last_name ?? ''));
                return (object) ['pk' => $e->pk, 'full_name' => $fullName ?: '—'];
            })
            ->filter(fn ($e) => $e->full_name !== '—')
            ->values();
    }

    /**
     * Stock Purchase Details Report
     * Shows detailed purchase information grouped by bill (Purchase Order)
     */
    public function stockPurchaseDetails(Request $request)
    {
        $queryData = $this->buildStockPurchaseDetailsQuery($request, forExport: false);

        $stores = Store::where('status', 'active')->get();
        $vendors = Vendor::all();

        return view('admin.mess.reports.stock-purchase-details', [
            'purchaseOrders'  => $queryData['purchaseOrders'],
            'grandTotal'      => $queryData['grandTotal'],
            'stores'          => $stores,
            'vendors'         => $vendors,
            'selectedVendors' => $queryData['selectedVendors'],
            'selectedStores'  => $queryData['selectedStores'],
            'fromDate'        => $queryData['fromDate'],
            'toDate'          => $queryData['toDate'],
        ]);
    }

    /**
     * Normalize request input to a list of positive integer IDs (supports legacy single values and array fields).
     *
     * @return array<int>
     */
    private function normalizedIdList(Request $request, string $key): array
    {
        $raw = $request->input($key);
        if ($raw === null || $raw === '' || $raw === []) {
            return [];
        }

        $list = is_array($raw) ? $raw : [$raw];

        return array_values(array_unique(array_filter(
            array_map(static fn ($v) => (int) $v, $list),
            static fn (int $id) => $id > 0
        )));
    }

    /**
     * @return array<int, string>
     */
    private function categoryWiseNormalizedSlugList(Request $request): array
    {
        $raw = $request->input('client_type_slug');
        if ($raw === null || $raw === '' || $raw === []) {
            return [];
        }

        $list = is_array($raw) ? $raw : [$raw];

        return array_values(array_unique(array_filter(
            array_map(static fn ($v) => strtolower(trim((string) $v)), $list),
            static fn (string $s) => $s !== ''
        )));
    }

    /**
     * @return array<int, string>
     */
    private function normalizedBuyerNameList(Request $request): array
    {
        $raw = $request->input('buyer_name');
        if ($raw === null || $raw === '' || $raw === []) {
            return [];
        }

        $list = is_array($raw) ? $raw : [$raw];

        return array_values(array_unique(array_filter(
            array_map(static fn ($v) => trim((string) $v), $list),
            static fn (string $s) => $s !== ''
        )));
    }

    /**
     * Union of category PKs and course PKs (both stored on voucher client_type_pk).
     *
     * @return array<int>
     */
    private function categoryWiseClientTypePkUnion(Request $request): array
    {
        return array_values(array_unique(array_merge(
            $this->normalizedIdList($request, 'client_type_pk'),
            $this->normalizedIdList($request, 'course_master_pk')
        )));
    }

    /**
     * Build base query and shared data for Stock Purchase Details (view, Excel, PDF).
     *
     * @return array{
     *     purchaseOrders: \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection,
     *     grandTotal: float|int,
     *     fromDate: string,
     *     toDate: string,
     *     selectedVendors: \Illuminate\Support\Collection<int, \App\Models\Mess\Vendor>,
     *     selectedStores: \Illuminate\Support\Collection<int, \App\Models\Mess\Store>
     * }
     */
    private function buildStockPurchaseDetailsQuery(Request $request, bool $forExport = false): array
    {
        // Use request dates when provided, otherwise default to today (so default filter applies on first load)
        $fromDate = $request->filled('from_date') ? $request->from_date : now()->format('Y-m-d');
        $toDate   = $request->filled('to_date') ? $request->to_date : now()->format('Y-m-d');

        $vendorIds = $this->normalizedIdList($request, 'vendor_id');
        $storeIds  = $this->normalizedIdList($request, 'store_id');

        $baseQuery = PurchaseOrder::with(['vendor', 'store', 'items.itemSubcategory'])
            ->whereDate('po_date', '>=', $fromDate)
            ->whereDate('po_date', '<=', $toDate);

        if ($storeIds !== []) {
            $baseQuery->whereIn('store_id', $storeIds);
        }
        if ($vendorIds !== []) {
            $baseQuery->whereIn('vendor_id', $vendorIds);
        }

        $baseQuery->orderBy('po_date', 'asc')->orderBy('id', 'asc');

        // Grand total: sum of (quantity * unit_price) for all filtered POs
        $poIds = (clone $baseQuery)->pluck('id');
        $grandTotal = PurchaseOrderItem::whereIn('purchase_order_id', $poIds)
            ->selectRaw('SUM(quantity * unit_price) as total')
            ->value('total') ?? 0;

        // For screen we paginate, for export (Excel/PDF) we take full collection
        $purchaseOrders = $forExport
            ? $baseQuery->get()
            : $baseQuery->paginate(5)->withQueryString();

        $selectedVendors = $vendorIds === []
            ? collect()
            : Vendor::whereIn('id', $vendorIds)->orderBy('name')->get();

        $selectedStores = $storeIds === []
            ? collect()
            : Store::whereIn('id', $storeIds)->orderBy('store_name')->get();

        return [
            'purchaseOrders'  => $purchaseOrders,
            'grandTotal'      => $grandTotal,
            'fromDate'        => $fromDate,
            'toDate'          => $toDate,
            'selectedVendors' => $selectedVendors,
            'selectedStores'  => $selectedStores,
        ];
    }

    /**
     * Stock Summary Report
     * Shows summary of all items with opening, purchase, sale, and closing stock
     */
    public function stockSummary(Request $request)
    {
        $fromDate = $request->filled('from_date') ? $request->from_date : now()->format('Y-m-d');
        $toDate = $request->filled('to_date') ? $request->to_date : now()->format('Y-m-d');
        $storeType = $request->filled('store_type') ? $request->store_type : 'main';

        $storeIds = $this->stockSummaryStoreIdsFromRequest($request, $storeType);
        sort($storeIds);

        // This report is expensive to compute; cache the computed dataset for repeated pagination
        $cacheTtlSeconds = 600; // 10 minutes
        $cacheKey = 'stock-summary:v3:' . md5(json_encode([$fromDate, $toDate, $storeType, $storeIds]));

        [$reportData, $selectedStoreName, $cachedTotals] = Cache::remember($cacheKey, $cacheTtlSeconds, function () use ($fromDate, $toDate, $storeIds, $storeType) {
            [$data, $storeName] = $this->getStockSummaryReportData($fromDate, $toDate, $storeIds, $storeType);
            $totals = [
                'opening_amount' => (float) collect($data)->sum('opening_amount'),
                'purchase_amount' => (float) collect($data)->sum('purchase_amount'),
                'sale_amount' => (float) collect($data)->sum('sale_amount'),
                'closing_amount' => (float) collect($data)->sum('closing_amount'),
            ];
            return [$data, $storeName, $totals];
        });

        // Convert report data to collection for convenient pagination & totals
        $reportCollection = collect($reportData);

        // Simple server-side pagination (per-page can be tuned)
        $perPage = 25;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $pageItems = $reportCollection
            ->slice(($currentPage - 1) * $perPage, $perPage)
            ->values();

        $reportPage = new LengthAwarePaginator(
            $pageItems,
            $reportCollection->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        // Grand totals across all items (not just current page) - cached to speed up pagination
        $reportTotals = $cachedTotals ?? [
            'opening_amount' => (float) $reportCollection->sum('opening_amount'),
            'purchase_amount' => (float) $reportCollection->sum('purchase_amount'),
            'sale_amount' => (float) $reportCollection->sum('sale_amount'),
            'closing_amount' => (float) $reportCollection->sum('closing_amount'),
        ];

        $stores = Store::where('status', 'active')->get();
        $subStores = SubStore::where('status', 'active')->get();

        // If AJAX request (or ajax=1 flag), return only the table partial
        if ($request->ajax() || $request->boolean('ajax')) {
            return view('admin.mess.reports.partials.stock-summary-table', compact(
                'reportData',
                'reportPage',
                'reportTotals'
            ));
        }

        return view('admin.mess.reports.stock-summary', compact(
            'reportData',
            'reportPage',
            'reportTotals',
            'stores',
            'subStores',
            'fromDate',
            'toDate',
            'storeIds',
            'storeType',
            'selectedStoreName'
        ));
    }

    /**
     * Resolve main / sub store ID list for Stock Summary (multiselect + legacy single params).
     *
     * @return array<int>
     */
    private function stockSummaryStoreIdsFromRequest(Request $request, string $storeType): array
    {
        if ($storeType === 'main') {
            $ids = $this->normalizedIdList($request, 'main_store_id');
            if ($ids === [] && ($request->filled('store_id'))) {
                $ids = $this->normalizedIdList($request, 'store_id');
            }

            return $ids;
        }

        $ids = $this->normalizedIdList($request, 'sub_store_id');
        if ($ids === [] && ($request->filled('store_id'))) {
            $ids = $this->normalizedIdList($request, 'store_id');
        }

        return $ids;
    }

    /**
     * Read a local image file into a data URI for Dompdf (avoids broken remote HTTPS loads in the PDF engine).
     */
    private function pdfTryFileToDataUri(string $path): ?string
    {
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            default => 'image/jpeg',
        };

        return 'data:'.$mime.';base64,'.base64_encode($raw);
    }

    /**
     * Fetch an image over HTTP and return a data URI for Dompdf embedding.
     */
    private function pdfTryHttpToDataUri(string $url, string $mime): ?string
    {
        try {
            $response = Http::timeout(20)->connectTimeout(8)->get($url);
            if ($response->successful()) {
                $body = $response->body();
                if ($body !== '' && strlen($body) > 100) {
                    return 'data:'.$mime.';base64,'.base64_encode($body);
                }
            }
        } catch (\Throwable $e) {
            // Fall back to returning the raw URL for the view / Dompdf remote loader
        }

        return null;
    }

    /**
     * LBSNAA header logo for Stock Summary PDF: local academy assets first, then official site, then URL fallback.
     */
    private function messPdfLbsnaaLogoForDompdf(): string
    {
        foreach ([public_path('images/lbsnaa_logo.jpg'), public_path('images/lbsnaa_logo.png')] as $path) {
            $uri = $this->pdfTryFileToDataUri($path);
            if ($uri !== null) {
                return $uri;
            }
        }

        $official = 'https://www.lbsnaa.gov.in/admin_assets/images/logo.png';
        $embedded = $this->pdfTryHttpToDataUri($official, 'image/png');
        if ($embedded !== null) {
            return $embedded;
        }

        foreach ([
            public_path('admin_assets/images/logos/logo.svg'),
            public_path('admin_assets/images/logos/logo-icon.svg'),
        ] as $path) {
            $uri = $this->pdfTryFileToDataUri($path);
            if ($uri !== null) {
                return $uri;
            }
        }

        return $official;
    }

    /**
     * India emblem (PNG) for PDF header — embedded when fetch succeeds.
     */
    private function messPdfIndiaEmblemForDompdf(): string
    {
        $url = 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Emblem_of_India.svg/120px-Emblem_of_India.svg.png';
        $embedded = $this->pdfTryHttpToDataUri($url, 'image/png');
        if ($embedded !== null) {
            return $embedded;
        }

        return $url;
    }

    /**
     * Stock Summary Report - Excel Export
     */
    public function stockSummaryExcel(Request $request)
    {
        $fromDate = $request->filled('from_date') ? $request->from_date : now()->format('Y-m-d');
        $toDate   = $request->filled('to_date') ? $request->to_date : now()->format('Y-m-d');
        $storeType = $request->filled('store_type') ? $request->store_type : 'main';

        $storeIds = $this->stockSummaryStoreIdsFromRequest($request, $storeType);

        [$reportData, $selectedStoreName] = $this->getStockSummaryReportData($fromDate, $toDate, $storeIds, $storeType);

        $fileName = 'stock-summary-report-' . $fromDate . '-to-' . $toDate . '-' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(
            new \App\Exports\Mess\StockSummaryViewExport($reportData, $fromDate, $toDate, $storeType, $selectedStoreName),
            $fileName
        );
    }

    /**
     * Stock Summary Report - PDF Export
     */
    public function stockSummaryPdf(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);

        $fromDate = $request->filled('from_date') ? $request->from_date : now()->format('Y-m-d');
        $toDate   = $request->filled('to_date') ? $request->to_date : now()->format('Y-m-d');
        $storeType = $request->filled('store_type') ? $request->store_type : 'main';

        $storeIds = $this->stockSummaryStoreIdsFromRequest($request, $storeType);

        [$reportData, $selectedStoreName] = $this->getStockSummaryReportData($fromDate, $toDate, $storeIds, $storeType);

        $data = [
            'reportData'        => $reportData,
            'fromDate'          => $fromDate,
            'toDate'            => $toDate,
            'storeType'         => $storeType,
            'selectedStoreName' => $selectedStoreName,
            'lbsnaaLogoSrc'     => $this->messPdfLbsnaaLogoForDompdf(),
            'emblemSrc'         => $this->messPdfIndiaEmblemForDompdf(),
        ];

        $pdf = Pdf::loadView('admin.mess.reports.pdf.stock-summary-pdf', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont'           => 'DejaVu Sans',
                'isHtml5ParserEnabled'  => true,
                'isRemoteEnabled'       => true,
                'dpi'                   => 96,
            ]);

        $fileName = 'stock-summary-report-' . $fromDate . '-to-' . $toDate . '-' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Stock Purchase Details Report - Excel Export
     */
    public function stockPurchaseDetailsExcel(Request $request)
    {
        $queryData = $this->buildStockPurchaseDetailsQuery($request, forExport: true);

        $fileName = 'stock-purchase-details-' . $queryData['fromDate'] . '-to-' . $queryData['toDate'] . '-' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new StockPurchaseDetailsExport(
                $queryData['purchaseOrders'],
                $queryData['fromDate'],
                $queryData['toDate'],
                $queryData['selectedVendors'],
                $queryData['selectedStores']
            ),
            $fileName
        );
    }

    /**
     * Stock Purchase Details Report - PDF Export
     */
    public function stockPurchaseDetailsPdf(Request $request)
    {
        $queryData = $this->buildStockPurchaseDetailsQuery($request, forExport: true);

        $data = [
            'purchaseOrders'  => $queryData['purchaseOrders'],
            'fromDate'        => $queryData['fromDate'],
            'toDate'          => $queryData['toDate'],
            'selectedVendors' => $queryData['selectedVendors'],
            'selectedStores'  => $queryData['selectedStores'],
        ];

        $pdf = Pdf::loadView('admin.mess.reports.pdf.stock-purchase-details-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'           => 'DejaVu Sans',
                'isHtml5ParserEnabled'  => true,
                'isRemoteEnabled'       => true,
                'dpi'                   => 96,
            ]);

        $fileName = 'stock-purchase-details-' . $queryData['fromDate'] . '-to-' . $queryData['toDate'] . '-' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($fileName);
    }
    /**
     * Stock Balance Till Date Report - Excel Export
     */
    public function stockBalanceTillDateExcel(Request $request)
    {
        $tillDate = $request->filled('till_date') ? $request->till_date : now()->format('Y-m-d');
        $storeIds = $this->normalizedIdList($request, 'store_id');
        $reportData = $this->buildStockBalanceTillDateData($tillDate, $storeIds);
        $selectedStoreName = $this->resolveStoreNamesLabel($storeIds);

        $fileName = 'stock-balance-till-date-' . $tillDate . '-' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new StockBalanceTillDateExport($reportData, $tillDate, $selectedStoreName),
            $fileName
        );
    }

    /**
     * Stock Balance Till Date Report - PDF Export
     */
    public function stockBalanceTillDatePdf(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(120);

        $tillDate = $request->filled('till_date') ? $request->till_date : now()->format('Y-m-d');
        $storeIds = $this->normalizedIdList($request, 'store_id');

        $data = [
            'reportData' => $this->buildStockBalanceTillDateData($tillDate, $storeIds),
            'tillDate' => $tillDate,
            'selectedStoreName' => $this->resolveStoreNamesLabel($storeIds),
        ];

        $pdf = Pdf::loadView('admin.mess.reports.pdf.stock-balance-till-date-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'           => 'DejaVu Sans',
                'isHtml5ParserEnabled'  => true,
                'isRemoteEnabled'       => true,
                'dpi'                   => 96,
                'isPhpEnabled'          => false,
            ]);

        $fileName = 'stock-balance-till-date-' . $tillDate . '-' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Purchase Sale Quantity Report - Excel Export
     */
    public function purchaseSaleQuantityExcel(Request $request)
    {
        $fromDate = $request->filled('from_date') ? $request->from_date : now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->filled('to_date') ? $request->to_date : now()->format('Y-m-d');
        $viewType = $request->filled('view_type') ? $request->view_type : 'item_wise';
        if (!in_array($viewType, ['item_wise', 'subcategory_wise', 'category_wise'], true)) {
            $viewType = 'item_wise';
        }
        $categoryId = $request->filled('category_id') ? $request->category_id : null;
        $itemIds = $this->normalizedIdList($request, 'item_id');
        $storeIds = $this->normalizedIdList($request, 'store_id');

        [$items, $reportData] = $this->buildPurchaseSaleQuantityData($fromDate, $toDate, $viewType, $categoryId, $itemIds, $storeIds);
        $selectedStoreName = $this->resolveStoreNamesLabel($storeIds);

        $fileName = 'purchase-sale-quantity-' . $fromDate . '-to-' . $toDate . '-' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(
            new PurchaseSaleQuantityExport(
                $reportData,
                $fromDate,
                $toDate,
                $viewType,
                $selectedStoreName,
                $this->resolveItemSubcategoryNamesLabel($itemIds)
            ),
            $fileName
        );
    }

    /**
     * Purchase Sale Quantity Report - PDF Export
     */
    public function purchaseSaleQuantityPdf(Request $request)
    {
        $fromDate = $request->filled('from_date') ? $request->from_date : now()->startOfMonth()->format('Y-m-d');
        $toDate   = $request->filled('to_date') ? $request->to_date : now()->format('Y-m-d');
        $viewType = $request->filled('view_type') ? $request->view_type : 'item_wise';
        if (!in_array($viewType, ['item_wise', 'subcategory_wise', 'category_wise'], true)) {
            $viewType = 'item_wise';
        }
        $categoryId = $request->filled('category_id') ? $request->category_id : null;
        $itemIds    = $this->normalizedIdList($request, 'item_id');
        $storeIds   = $this->normalizedIdList($request, 'store_id');

        [$items, $reportData] = $this->buildPurchaseSaleQuantityData($fromDate, $toDate, $viewType, $categoryId, $itemIds, $storeIds);

        if ($viewType === 'item_wise') {
            $groupedData = null;
        } elseif ($viewType === 'subcategory_wise') {
            $groupedData = collect($reportData)
                ->groupBy(function ($r) {
                    return $r['category_name'] ?? 'Uncategorized';
                })
                ->map(function ($rows, $catName) {
                    return ['category_name' => $catName, 'items' => $rows->values()->all()];
                })
                ->values()
                ->all();
        } else {
            if ($items->isEmpty()) {
                $groupedData = [];
            } else {
                $catName = $items->first()->category ? $items->first()->category->category_name : 'Category';
                $groupedData = [['category_name' => $catName, 'items' => $reportData]];
            }
        }

        $data = [
            'reportData' => $reportData,
            'groupedData' => $groupedData,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'viewType' => $viewType,
            'selectedStoreName' => $this->resolveStoreNamesLabel($storeIds),
            'selectedItemNamesLabel' => $this->resolveItemSubcategoryNamesLabel($itemIds),
        ];

        $pdf = Pdf::loadView('admin.mess.reports.pdf.purchase-sale-quantity-pdf', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont'           => 'DejaVu Sans',
                'isHtml5ParserEnabled'  => true,
                'isRemoteEnabled'       => true,
                'dpi'                   => 96,
            ]);

        $fileName = 'purchase-sale-quantity-' . $fromDate . '-to-' . $toDate . '-' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Selling Voucher Print Slip - Excel Export
     */
    public function sellingVoucherPrintSlipExcel(Request $request)
    {
        $query = \App\Models\Mess\SellingVoucherDateRangeReport::with([
            'store',
            'clientTypeCategory',
            'items.itemSubcategory',
        ]);

        if ($request->filled('from_date')) {
            $from = $request->from_date;
            $query->where(function ($q) use ($from) {
                $q->whereDate('issue_date', '>=', $from)
                  ->orWhereDate('date_from', '>=', $from);
            });
        }
        if ($request->filled('to_date')) {
            $to = $request->to_date;
            $query->where(function ($q) use ($to) {
                $q->whereDate('issue_date', '<=', $to)
                  ->orWhereDate('date_to', '<=', $to);
            });
        }
        if ($request->filled('employee_ot_filter')) {
            if ($request->employee_ot_filter === 'employee_ot') {
                $query->whereIn('client_type_slug', [\App\Models\Mess\ClientType::TYPE_EMPLOYEE, \App\Models\Mess\ClientType::TYPE_OT]);
            } elseif ($request->employee_ot_filter === 'employee') {
                $query->where('client_type_slug', \App\Models\Mess\ClientType::TYPE_EMPLOYEE);
            } elseif ($request->employee_ot_filter === 'ot') {
                $query->where('client_type_slug', \App\Models\Mess\ClientType::TYPE_OT);
            }
        }
        if ($request->filled('client_type_slug')) {
            $query->where('client_type_slug', $request->client_type_slug);
        }
        if ($request->filled('client_type_pk')) {
            $query->where('client_type_pk', $request->client_type_pk);
        }
        if ($request->filled('buyer_name') && $request->buyer_name !== '') {
            $query->where('client_name', 'LIKE', '%' . $request->buyer_name . '%');
        }
        $query->whereIn('status', [
            \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_DRAFT,
            \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_FINAL,
            \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_APPROVED,
        ]);
        $vouchers = $query->orderBy('issue_date', 'desc')->get();

        $fileName = 'selling-voucher-print-slip-' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(new SellingVoucherPrintSlipExport($vouchers), $fileName);
    }

    /**
     * Category-wise Print Slip - Excel Export
     * Builds merged SV + KI vouchers with same filters and exports flattened rows.
     * Export is allowed only when at least one filter has been applied.
     */
    public function categoryWisePrintSlipExcel(Request $request)
    {
        $cwSlugs = $this->categoryWiseNormalizedSlugList($request);
        $cwPks = $this->categoryWiseClientTypePkUnion($request);
        $cwBuyers = $this->normalizedBuyerNameList($request);

        $filtersApplied = $request->filled('from_date')
            || $request->filled('to_date')
            || ($cwSlugs !== [])
            || ($cwPks !== [])
            || ($cwBuyers !== []);
        if (! $filtersApplied) {
            return redirect()->route('admin.mess.reports.category-wise-print-slip')
                ->with('error', 'Please apply filters before exporting.');
        }

        $report = $this->buildCategoryWisePrintSlipReportData($request);

        $fileName = 'category-wise-print-slip-' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(
            new CategoryWisePrintSlipExport(
                $report['allBuyersSections'],
                $request->from_date ?? null,
                $request->to_date ?? null,
                $report['otCourses'],
                (float) $report['grandTotal']
            ),
            $fileName
        );
    }

    /**
     * Category-wise Print Slip - PDF Export
     * Generates a downloadable PDF using the same filters as the main report.
     * Export is allowed only when at least one filter has been applied.
     */
    public function categoryWisePrintSlipPdf(Request $request)
    {
        $cwSlugs = $this->categoryWiseNormalizedSlugList($request);
        $cwPks = $this->categoryWiseClientTypePkUnion($request);
        $cwBuyers = $this->normalizedBuyerNameList($request);

        $filtersApplied = $request->filled('from_date')
            || $request->filled('to_date')
            || ($cwSlugs !== [])
            || ($cwPks !== [])
            || ($cwBuyers !== []);
        if (! $filtersApplied) {
            return redirect()->route('admin.mess.reports.category-wise-print-slip')
                ->with('error', 'Please apply filters before exporting.');
        }

        $report = $this->buildCategoryWisePrintSlipReportData($request);

        $fromDateFormatted = $request->from_date
            ? \Carbon\Carbon::parse($request->from_date)->format('d-F-Y')
            : null;
        $toDateFormatted = $request->to_date
            ? \Carbon\Carbon::parse($request->to_date)->format('d-F-Y')
            : null;

        $data = [
            'sectionsToShow' => $report['allBuyersSections'],
            'fromDateFormatted' => $fromDateFormatted,
            'toDateFormatted' => $toDateFormatted,
            'otCourses' => $report['otCourses'],
            'grandTotal' => (float) $report['grandTotal'],
        ];

        $pdf = Pdf::loadView('admin.mess.reports.pdf.category-wise-print-slip-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'Arial',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'dpi' => 96,
            ]);

        $fileName = 'category-wise-print-slip-' . now()->format('Y-m-d_His') . '.pdf';
        return $pdf->download($fileName);
    }

    /**
     * Standalone print view: same report body as PDF (no admin layout), opens in a new window for printing.
     */
    public function categoryWisePrintSlipPrint(Request $request)
    {
        $cwSlugs = $this->categoryWiseNormalizedSlugList($request);
        $cwPks = $this->categoryWiseClientTypePkUnion($request);
        $cwBuyers = $this->normalizedBuyerNameList($request);

        $filtersApplied = $request->filled('from_date')
            || $request->filled('to_date')
            || ($cwSlugs !== [])
            || ($cwPks !== [])
            || ($cwBuyers !== []);
        if (! $filtersApplied) {
            return redirect()->route('admin.mess.reports.category-wise-print-slip')
                ->with('error', 'Please apply filters before printing.');
        }

        $report = $this->buildCategoryWisePrintSlipReportData($request);

        $fromDateFormatted = $request->from_date
            ? \Carbon\Carbon::parse($request->from_date)->format('d-F-Y')
            : 'Start';
        $toDateFormatted = $request->to_date
            ? \Carbon\Carbon::parse($request->to_date)->format('d-F-Y')
            : 'End';

        return view('admin.mess.reports.category-wise-print-slip-print', [
            'sectionsToShow' => $report['allBuyersSections'],
            'fromDateFormatted' => $fromDateFormatted,
            'toDateFormatted' => $toDateFormatted,
            'otCourses' => $report['otCourses'],
            'grandTotal' => (float) $report['grandTotal'],
        ]);
    }

    /**
     * Build Stock Summary report data for the given filters.
     * Returns [reportData, selectedStoreName].
     *
     * @param  array<int>  $storeIds  Empty: all stores for the selected store type; non-empty: filter with whereIn.
     */
    private function getStockSummaryReportData(string $fromDate, string $toDate, array $storeIds, string $storeType): array
    {
        $items = ItemSubcategory::where('status', 'active')->orderBy('name')->get();
        $previousDate = date('Y-m-d', strtotime($fromDate . ' -1 day'));
        $reportData = [];

        foreach ($items as $item) {
            $itemData = [
                'item_name' => $item->item_name ?? $item->subcategory_name ?? $item->name,
                'item_code' => $item->item_code ?? $item->subcategory_code ?? '—',
                'unit' => $item->unit_measurement ?? 'Unit',
                'opening_qty' => 0,
                'opening_rate' => $item->standard_cost ?? 0,
                'opening_amount' => 0,
                'purchase_qty' => 0,
                'purchase_rate' => $item->standard_cost ?? 0,
                'purchase_amount' => 0,
                'sale_qty' => 0,
                'sale_rate' => $item->standard_cost ?? 0,
                'sale_amount' => 0,
                'closing_qty' => 0,
                'closing_rate' => $item->standard_cost ?? 0,
                'closing_amount' => 0,
            ];

            if ($storeType == 'main') {
                $previousPurchase = PurchaseOrderItem::where('item_subcategory_id', $item->id)
                    ->whereHas('purchaseOrder', function ($q) use ($previousDate, $storeIds) {
                        $q->where('status', 'approved')->whereDate('po_date', '<=', $previousDate);
                        if ($storeIds !== []) {
                            $q->whereIn('store_id', $storeIds);
                        }
                    })
                    ->sum('quantity');
                $previousSale = \DB::table('kitchen_issue_items as kii')
                    ->join('kitchen_issue_master as kim', 'kii.kitchen_issue_master_pk', '=', 'kim.pk')
                    ->where('kii.item_subcategory_id', $item->id)
                    ->where('kim.kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
                    ->where('kim.store_type', 'store')
                    ->whereDate('kim.issue_date', '<=', $previousDate)
                    ->when($storeIds !== [], fn ($q) => $q->whereIn('kim.store_id', $storeIds))
                    ->selectRaw('SUM(kii.quantity - COALESCE(kii.return_quantity, 0)) as net')
                    ->value('net') ?? 0;
                $previousSaleSv = \DB::table('sv_date_range_report_items as svi')
                    ->join('sv_date_range_reports as svr', 'svi.sv_date_range_report_id', '=', 'svr.id')
                    ->where('svi.item_subcategory_id', $item->id)
                    ->where('svr.store_type', 'store')
                    ->whereDate('svr.issue_date', '<=', $previousDate)
                    ->when($storeIds !== [], fn ($q) => $q->whereIn('svr.store_id', $storeIds))
                    ->selectRaw('SUM(svi.quantity - COALESCE(svi.return_quantity, 0)) as net')
                    ->value('net') ?? 0;
                $itemData['opening_qty'] = $previousPurchase - $previousSale - $previousSaleSv;
            } else {
                $previousAllocation = \DB::table('mess_store_allocation_items as sai')
                    ->join('mess_store_allocations as sa', 'sai.store_allocation_id', '=', 'sa.id')
                    ->where('sai.item_subcategory_id', $item->id)
                    ->whereDate('sa.allocation_date', '<=', $previousDate)
                    ->when($storeIds !== [], fn ($q) => $q->whereIn('sa.sub_store_id', $storeIds))
                    ->sum('sai.quantity');
                $previousSale = \DB::table('kitchen_issue_items as kii')
                    ->join('kitchen_issue_master as kim', 'kii.kitchen_issue_master_pk', '=', 'kim.pk')
                    ->where('kii.item_subcategory_id', $item->id)
                    ->where('kim.kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
                    ->where('kim.store_type', 'sub_store')
                    ->whereDate('kim.issue_date', '<=', $previousDate)
                    ->when($storeIds !== [], fn ($q) => $q->whereIn('kim.store_id', $storeIds))
                    ->selectRaw('SUM(kii.quantity - COALESCE(kii.return_quantity, 0)) as net')
                    ->value('net') ?? 0;
                $previousSaleSv = \DB::table('sv_date_range_report_items as svi')
                    ->join('sv_date_range_reports as svr', 'svi.sv_date_range_report_id', '=', 'svr.id')
                    ->where('svi.item_subcategory_id', $item->id)
                    ->where('svr.store_type', 'sub_store')
                    ->whereDate('svr.issue_date', '<=', $previousDate)
                    ->when($storeIds !== [], fn ($q) => $q->whereIn('svr.store_id', $storeIds))
                    ->selectRaw('SUM(svi.quantity - COALESCE(svi.return_quantity, 0)) as net')
                    ->value('net') ?? 0;
                $itemData['opening_qty'] = $previousAllocation - $previousSale - $previousSaleSv;
            }

            $itemData['opening_amount'] = $itemData['opening_qty'] * $itemData['opening_rate'];

            if ($storeType == 'main') {
                $purchases = PurchaseOrderItem::where('item_subcategory_id', $item->id)
                    ->whereHas('purchaseOrder', function ($q) use ($fromDate, $toDate, $storeIds) {
                        $q->where('status', 'approved')->whereBetween('po_date', [$fromDate, $toDate]);
                        if ($storeIds !== []) {
                            $q->whereIn('store_id', $storeIds);
                        }
                    })
                    ->selectRaw('SUM(quantity) as total_qty, AVG(unit_price) as avg_rate')
                    ->first();
                $itemData['purchase_qty'] = $purchases->total_qty ?? 0;
                $itemData['purchase_rate'] = $purchases->avg_rate ?? $itemData['purchase_rate'];
            } else {
                $allocations = \DB::table('mess_store_allocation_items as sai')
                    ->join('mess_store_allocations as sa', 'sai.store_allocation_id', '=', 'sa.id')
                    ->where('sai.item_subcategory_id', $item->id)
                    ->whereBetween('sa.allocation_date', [$fromDate, $toDate])
                    ->when($storeIds !== [], fn ($q) => $q->whereIn('sa.sub_store_id', $storeIds))
                    ->selectRaw('SUM(sai.quantity) as total_qty, AVG(sai.unit_price) as avg_rate')
                    ->first();
                $itemData['purchase_qty'] = $allocations->total_qty ?? 0;
                $itemData['purchase_rate'] = $allocations->avg_rate ?? $itemData['purchase_rate'];
            }

            $itemData['purchase_amount'] = $itemData['purchase_qty'] * $itemData['purchase_rate'];

            // Sales from Selling Voucher (kitchen_issue)
            $kimStoreType = $storeType == 'main' ? 'store' : 'sub_store';
            $salesKi = \DB::table('kitchen_issue_items as kii')
                ->join('kitchen_issue_master as kim', 'kii.kitchen_issue_master_pk', '=', 'kim.pk')
                ->where('kii.item_subcategory_id', $item->id)
                ->where('kim.kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
                ->where('kim.store_type', $kimStoreType)
                ->whereBetween('kim.issue_date', [$fromDate, $toDate])
                ->when($storeIds !== [], fn ($q) => $q->whereIn('kim.store_id', $storeIds))
                ->selectRaw('SUM(kii.quantity - COALESCE(kii.return_quantity, 0)) as total_qty, SUM((kii.quantity - COALESCE(kii.return_quantity, 0)) * kii.rate) as total_amount')
                ->first();
            $saleQtyKi = (float) ($salesKi->total_qty ?? 0);
            $saleAmountKi = (float) ($salesKi->total_amount ?? 0);

            // Sales from Selling Voucher with Date Range
            $salesSv = \DB::table('sv_date_range_report_items as svi')
                ->join('sv_date_range_reports as svr', 'svi.sv_date_range_report_id', '=', 'svr.id')
                ->where('svi.item_subcategory_id', $item->id)
                ->where('svr.store_type', $kimStoreType)
                ->whereBetween('svr.issue_date', [$fromDate, $toDate])
                ->when($storeIds !== [], fn ($q) => $q->whereIn('svr.store_id', $storeIds))
                ->selectRaw('SUM(svi.quantity - COALESCE(svi.return_quantity, 0)) as total_qty, SUM((svi.quantity - COALESCE(svi.return_quantity, 0)) * svi.rate) as total_amount')
                ->first();
            $saleQtySv = (float) ($salesSv->total_qty ?? 0);
            $saleAmountSv = (float) ($salesSv->total_amount ?? 0);

            $itemData['sale_qty'] = $saleQtyKi + $saleQtySv;
            $itemData['sale_amount'] = $saleAmountKi + $saleAmountSv;
            $itemData['sale_rate'] = $itemData['sale_qty'] > 0 ? $itemData['sale_amount'] / $itemData['sale_qty'] : $itemData['sale_rate'];

            $itemData['closing_qty'] = $itemData['opening_qty'] + $itemData['purchase_qty'] - $itemData['sale_qty'];
            $itemData['closing_amount'] = $itemData['closing_qty'] * $itemData['closing_rate'];

            if ($itemData['opening_qty'] != 0 || $itemData['purchase_qty'] != 0 || $itemData['sale_qty'] != 0) {
                $reportData[] = $itemData;
            }
        }

        $selectedStoreName = null;
        if ($storeIds !== []) {
            if ($storeType == 'main') {
                $selectedStoreName = Store::whereIn('id', $storeIds)->orderBy('store_name')->pluck('store_name')->implode(', ');
            } else {
                $selectedStoreName = SubStore::whereIn('id', $storeIds)->orderBy('sub_store_name')->pluck('sub_store_name')->implode(', ');
            }
        }

        return [$reportData, $selectedStoreName];
    }

    /**
     * Map Kitchen Issue client_type (int) to client_type_slug (string)
     */
    private static function kitchenIssueClientTypeToSlug(): array
    {
        return [
            KitchenIssueMaster::CLIENT_EMPLOYEE => \App\Models\Mess\ClientType::TYPE_EMPLOYEE,
            KitchenIssueMaster::CLIENT_OT      => \App\Models\Mess\ClientType::TYPE_OT,
            KitchenIssueMaster::CLIENT_COURSE  => \App\Models\Mess\ClientType::TYPE_COURSE,
            KitchenIssueMaster::CLIENT_OTHER   => \App\Models\Mess\ClientType::TYPE_OTHER,
            KitchenIssueMaster::CLIENT_SECTION => 'section',
        ];
    }

    /**
     * AJAX: Buyer Names for Course (Selling Voucher report filter).
     * Returns distinct client_name values for a given course_pk, respecting optional date filters.
     */
    public function getCourseBuyerNamesByCourse(Request $request, $course_pk)
    {
        $coursePk = (int) $course_pk;
        if ($coursePk <= 0) {
            return response()->json(['buyers' => []]);
        }

        $fromDate = $request->filled('from_date') ? $request->from_date : null;
        $toDate   = $request->filled('to_date') ? $request->to_date : null;

        // Selling Voucher Date Range (SV)
        $svQuery = \App\Models\Mess\SellingVoucherDateRangeReport::query()
            ->where('client_type_slug', \App\Models\Mess\ClientType::TYPE_COURSE)
            ->where('client_type_pk', $coursePk)
            ->whereHas('items')
            ->whereIn('status', [
                \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_DRAFT,
                \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_FINAL,
                \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_APPROVED,
            ]);

        // Strict filter by item `issue_date` (Request Date).
        if ($fromDate && $toDate) {
            $svQuery->whereHas('items', function ($itemQ) use ($fromDate, $toDate) {
                $itemQ->whereBetween('issue_date', [$fromDate, $toDate]);
            });
        } elseif ($fromDate) {
            $svQuery->whereHas('items', function ($itemQ) use ($fromDate) {
                $itemQ->whereDate('issue_date', '>=', $fromDate);
            });
        } elseif ($toDate) {
            $svQuery->whereHas('items', function ($itemQ) use ($toDate) {
                $itemQ->whereDate('issue_date', '<=', $toDate);
            });
        }

        $svBuyers = (clone $svQuery)
            ->whereNotNull('client_name')
            ->where('client_name', '!=', '')
            ->distinct()
            ->pluck('client_name');

        // Kitchen Issue (Selling Voucher type)
        $kiQuery = KitchenIssueMaster::query()
            ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
            ->whereHas('items')
            ->where('client_type', KitchenIssueMaster::CLIENT_COURSE)
            ->where('client_type_pk', $coursePk);

        if ($fromDate) {
            $kiQuery->whereDate('issue_date', '>=', $fromDate);
        }
        if ($toDate) {
            $kiQuery->whereDate('issue_date', '<=', $toDate);
        }

        $kiBuyers = (clone $kiQuery)
            ->whereNotNull('client_name')
            ->where('client_name', '!=', '')
            ->distinct()
            ->pluck('client_name');

        $buyers = $svBuyers->concat($kiBuyers)
            ->filter()
            ->map(fn ($n) => trim((string) $n))
            ->filter(fn ($n) => $n !== '')
            ->unique()
            ->sort()
            ->values();

        return response()->json(['buyers' => $buyers]);
    }

    /**
     * AJAX: Buyer Names for Sale Voucher Report filters.
     * Supports: other, section (and course/ot if needed) with optional date + client_type_pk/course_master_pk.
     */
    public function getBuyerNamesForReportFilters(Request $request)
    {
        $slug = strtolower(trim((string) $request->query('client_type_slug', '')));
        if (!in_array($slug, [\App\Models\Mess\ClientType::TYPE_COURSE, \App\Models\Mess\ClientType::TYPE_OTHER, 'section', \App\Models\Mess\ClientType::TYPE_OT, \App\Models\Mess\ClientType::TYPE_EMPLOYEE], true)) {
            return response()->json(['buyers' => []]);
        }

        $fromDate = $request->filled('from_date') ? $request->from_date : null;
        $toDate   = $request->filled('to_date') ? $request->to_date : null;

        $pksUnion = $this->categoryWiseClientTypePkUnion($request);

        $svQuery = \App\Models\Mess\SellingVoucherDateRangeReport::query()
            ->where('client_type_slug', $slug)
            ->whereHas('items')
            ->whereIn('status', [
                \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_DRAFT,
                \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_FINAL,
                \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_APPROVED,
            ]);

        if ($pksUnion !== []) {
            $svQuery->whereIn('client_type_pk', $pksUnion);
        }

        // Strict filter by item `issue_date` (Request Date).
        if ($fromDate && $toDate) {
            $svQuery->whereHas('items', function ($itemQ) use ($fromDate, $toDate) {
                $itemQ->whereBetween('issue_date', [$fromDate, $toDate]);
            });
        } elseif ($fromDate) {
            $svQuery->whereHas('items', function ($itemQ) use ($fromDate) {
                $itemQ->whereDate('issue_date', '>=', $fromDate);
            });
        } elseif ($toDate) {
            $svQuery->whereHas('items', function ($itemQ) use ($toDate) {
                $itemQ->whereDate('issue_date', '<=', $toDate);
            });
        }

        $svBuyers = (clone $svQuery)
            ->whereNotNull('client_name')
            ->where('client_name', '!=', '')
            ->distinct()
            ->pluck('client_name');

        // Kitchen Issue side
        $kiQuery = KitchenIssueMaster::query()
            ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
            ->whereHas('items');

        $slugToKiType = [
            \App\Models\Mess\ClientType::TYPE_EMPLOYEE => KitchenIssueMaster::CLIENT_EMPLOYEE,
            \App\Models\Mess\ClientType::TYPE_OT       => KitchenIssueMaster::CLIENT_OT,
            \App\Models\Mess\ClientType::TYPE_COURSE   => KitchenIssueMaster::CLIENT_COURSE,
            \App\Models\Mess\ClientType::TYPE_OTHER    => KitchenIssueMaster::CLIENT_OTHER,
            'section'                                  => KitchenIssueMaster::CLIENT_SECTION,
        ];
        if (isset($slugToKiType[$slug])) {
            $kiQuery->where('client_type', $slugToKiType[$slug]);
        } else {
            // Unknown mapping => no buyers
            return response()->json(['buyers' => []]);
        }

        if ($pksUnion !== []) {
            $kiQuery->whereIn('client_type_pk', $pksUnion);
        }
        if ($fromDate) {
            $kiQuery->whereDate('issue_date', '>=', $fromDate);
        }
        if ($toDate) {
            $kiQuery->whereDate('issue_date', '<=', $toDate);
        }

        $kiBuyers = (clone $kiQuery)
            ->whereNotNull('client_name')
            ->where('client_name', '!=', '')
            ->distinct()
            ->pluck('client_name');

        $buyers = $svBuyers->concat($kiBuyers)
            ->filter()
            ->map(fn ($n) => trim((string) $n))
            ->filter(fn ($n) => $n !== '')
            ->unique()
            ->sort()
            ->values();

        return response()->json(['buyers' => $buyers]);
    }

    /**
     * Shared SV + Kitchen Issue queries, merge, sort, and buyer grouping for category-wise print slip.
     * Used by the screen, Excel, and PDF so outputs match exactly.
     *
     * @return array{
     *   vouchers:\Illuminate\Support\Collection,
     *   allBuyersSections:\Illuminate\Support\Collection,
     *   courseBuyerNames:\Illuminate\Support\Collection,
     *   otherBuyerNames:\Illuminate\Support\Collection,
     *   sectionBuyerNames:\Illuminate\Support\Collection,
     *   otCourses:\Illuminate\Support\Collection,
     *   grandTotal:float
     * }
     */
    private function buildCategoryWisePrintSlipReportData(Request $request): array
    {
        $slugs = $this->categoryWiseNormalizedSlugList($request);
        $pksUnion = $this->categoryWiseClientTypePkUnion($request);
        $buyerNames = $this->normalizedBuyerNameList($request);

        // Slug => int for kitchen_issue_master.client_type (do NOT array_flip)
        $clientTypeSlugToInt = [
            \App\Models\Mess\ClientType::TYPE_EMPLOYEE => KitchenIssueMaster::CLIENT_EMPLOYEE,
            \App\Models\Mess\ClientType::TYPE_OT      => KitchenIssueMaster::CLIENT_OT,
            \App\Models\Mess\ClientType::TYPE_COURSE  => KitchenIssueMaster::CLIENT_COURSE,
            \App\Models\Mess\ClientType::TYPE_OTHER   => KitchenIssueMaster::CLIENT_OTHER,
            'section'                                 => KitchenIssueMaster::CLIENT_SECTION,
        ];

        $fromDate = $request->filled('from_date') ? $request->from_date : null;
        $toDate   = $request->filled('to_date') ? $request->to_date : null;

        $svQuery = \App\Models\Mess\SellingVoucherDateRangeReport::with([
            'store',
            'clientTypeCategory',
            'items' => function ($itemQ) use ($fromDate, $toDate) {
                if ($fromDate && $toDate) {
                    $itemQ->whereBetween('issue_date', [$fromDate, $toDate]);
                } elseif ($fromDate) {
                    $itemQ->whereDate('issue_date', '>=', $fromDate);
                } elseif ($toDate) {
                    $itemQ->whereDate('issue_date', '<=', $toDate);
                }
            },
            'items.itemSubcategory',
        ]);
        if ($slugs !== []) {
            $svQuery->whereIn('client_type_slug', $slugs);
        }
        if ($pksUnion !== []) {
            $svQuery->whereIn('client_type_pk', $pksUnion);
        }
        if ($buyerNames !== []) {
            $svQuery->where(function ($q) use ($buyerNames) {
                foreach ($buyerNames as $bn) {
                    $q->orWhere('client_name', 'LIKE', '%' . $bn . '%');
                }
            });
        }
        $svQuery->whereIn('status', [
            \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_DRAFT,
            \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_FINAL,
            \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_APPROVED,
        ]);
        if ($fromDate && $toDate) {
            $svQuery->whereHas('items', function ($itemQ) use ($fromDate, $toDate) {
                $itemQ->whereBetween('issue_date', [$fromDate, $toDate]);
            });
        } elseif ($fromDate) {
            $svQuery->whereHas('items', function ($itemQ) use ($fromDate) {
                $itemQ->whereDate('issue_date', '>=', $fromDate);
            });
        } elseif ($toDate) {
            $svQuery->whereHas('items', function ($itemQ) use ($toDate) {
                $itemQ->whereDate('issue_date', '<=', $toDate);
            });
        } else {
            $svQuery->whereHas('items');
        }
        $svVouchers = $svQuery->orderBy('issue_date', 'desc')->get();
        foreach ($svVouchers as $v) {
            $v->request_no = 'SV-' . str_pad($v->id, 6, '0', STR_PAD_LEFT);
        }

        $kiQuery = KitchenIssueMaster::with(['store', 'clientTypeCategory', 'items.itemSubcategory'])
            ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
            ->whereHas('items');

        if ($request->filled('from_date')) {
            $kiQuery->whereDate('issue_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $kiQuery->whereDate('issue_date', '<=', $request->to_date);
        }
        if ($slugs !== []) {
            $kiTypes = [];
            foreach ($slugs as $s) {
                if (isset($clientTypeSlugToInt[$s])) {
                    $kiTypes[] = $clientTypeSlugToInt[$s];
                }
            }
            $kiTypes = array_values(array_unique($kiTypes));
            if ($kiTypes !== []) {
                $kiQuery->whereIn('client_type', $kiTypes);
            }
        }
        if ($pksUnion !== []) {
            $kiQuery->whereIn('client_type_pk', $pksUnion);
        }
        if ($buyerNames !== []) {
            $kiQuery->where(function ($q) use ($buyerNames) {
                foreach ($buyerNames as $bn) {
                    $q->orWhere('client_name', 'LIKE', '%' . $bn . '%');
                }
            });
        }
        $kiVouchers = $kiQuery->orderBy('issue_date', 'desc')->get();
        $slugMap = self::kitchenIssueClientTypeToSlug();
        foreach ($kiVouchers as $v) {
            $v->request_no = 'KI-' . str_pad($v->pk, 6, '0', STR_PAD_LEFT);
            $v->client_type_slug = $slugMap[$v->client_type] ?? 'other';
            $v->id = $v->pk;
        }

        $vouchers = $svVouchers->concat($kiVouchers)
            ->when($slugs !== [], function ($collection) use ($slugs) {
                return $collection->filter(function ($v) use ($slugs) {
                    return in_array($v->client_type_slug ?? null, $slugs, true);
                });
            })
            ->sortByDesc(function ($v) {
                return $v->issue_date ? $v->issue_date->format('Y-m-d') : '';
            })
            ->values();

        $groupedByBuyer = $vouchers->groupBy(function ($v) {
            $name = trim((string) ($v->client_name ?? ($v->clientTypeCategory->client_name ?? '')));
            $slug = (string) ($v->client_type_slug ?? '');
            $pk = (string) ($v->client_type_pk ?? '');

            return $name . '|' . $slug . '|' . $pk;
        });

        $courseBuyerNames = collect();
        $otherBuyerNames = collect();
        $sectionBuyerNames = collect();

        $bySlug = $vouchers->groupBy('client_type_slug');
        if (isset($bySlug[ClientType::TYPE_COURSE])) {
            $courseBuyerNames = $bySlug[ClientType::TYPE_COURSE]->pluck('client_name')
                ->filter()
                ->unique()
                ->sort()
                ->values();
        }
        if (isset($bySlug['other'])) {
            $otherBuyerNames = $bySlug['other']->pluck('client_name')
                ->filter()
                ->unique()
                ->sort()
                ->values();
        }
        if (isset($bySlug['section'])) {
            $sectionBuyerNames = $bySlug['section']->pluck('client_name')
                ->filter()
                ->unique()
                ->sort()
                ->values();
        }

        $allBuyersSections = $groupedByBuyer->values()->map(function ($buyerVouchers) {
            return $buyerVouchers->groupBy(function ($v) {
                $pk = $v->client_type_pk ?? '';
                $slug = $v->client_type_slug ?? '';

                return $pk . '-' . $slug;
            });
        });

        $otCourses = CourseMaster::where('active_inactive', 1)
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            })
            ->orderBy('course_name')
            ->get(['pk', 'course_name']);

        $grandTotal = $vouchers->sum(function ($voucher) {
            return $voucher->items->sum(function ($item) {
                $issueQty = (float) ($item->quantity ?? 0);
                $returnQty = (float) ($item->return_quantity ?? 0);
                $netQty = max(0, $issueQty - $returnQty);
                $rate = (float) ($item->rate ?? 0);

                return $netQty * $rate;
            });
        });

        return [
            'vouchers' => $vouchers,
            'allBuyersSections' => $allBuyersSections,
            'courseBuyerNames' => $courseBuyerNames,
            'otherBuyerNames' => $otherBuyerNames,
            'sectionBuyerNames' => $sectionBuyerNames,
            'otCourses' => $otCourses,
            'grandTotal' => $grandTotal,
        ];
    }

    /**
     * Category-wise Print Slip
     * Shows selling voucher details from both: Selling Voucher Date Range and Kitchen Issue (Selling Voucher type).
     * Data is displayed only after the user applies at least one filter (from_date, to_date, client type, or buyer).
     */
    public function categoryWisePrintSlip(Request $request)
    {
        $cwSlugs = $this->categoryWiseNormalizedSlugList($request);
        $cwPks = $this->categoryWiseClientTypePkUnion($request);
        $cwBuyers = $this->normalizedBuyerNameList($request);

        $filtersApplied = $request->filled('from_date')
            || $request->filled('to_date')
            || ($cwSlugs !== [])
            || ($cwPks !== [])
            || ($cwBuyers !== []);

        if (! $filtersApplied) {
            $groupedSections = collect();
            $paginator = null;
            $allBuyersSections = collect();
            $printAll = false;
            $grandTotal = 0.0;
            $courseBuyerNames = collect();
            $otherBuyerNames = collect();
            $sectionBuyerNames = collect();
            $clientTypes = ClientType::clientTypes();
            $clientTypeCategories = ClientType::active()
                ->orderBy('client_type')
                ->orderBy('client_name')
                ->get()
                ->groupBy('client_type');
            $officersMessDept = DepartmentMaster::where('department_name', 'Officers Mess')->first();
            $faculties = FacultyMaster::whereNotNull('full_name')->where('full_name', '!=', '')->orderBy('full_name')->get(['pk', 'full_name']);
            $employees = $this->academyStaffEmployees($officersMessDept);
            $messStaff = $officersMessDept
                ? EmployeeMaster::when(Schema::hasColumn('employee_master', 'status'), fn ($q) => $q->where('status', 1))
                    ->where('department_master_pk', $officersMessDept->pk)
                    ->orderBy('first_name')->orderBy('last_name')
                    ->get(['pk', 'first_name', 'middle_name', 'last_name'])
                    ->map(function ($e) {
                        $fullName = trim(($e->first_name ?? '') . ' ' . ($e->middle_name ?? '') . ' ' . ($e->last_name ?? ''));
                        return (object) ['pk' => $e->pk, 'full_name' => $fullName ?: '—'];
                    })
                    ->filter(fn ($e) => $e->full_name !== '—')
                    ->values()
                : collect();
            $otCourses = CourseMaster::where('active_inactive', 1)
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
                })
                ->orderBy('course_name')
                ->get(['pk', 'course_name']);

            return view('admin.mess.reports.category-wise-print-slip', compact(
                'groupedSections',
                'paginator',
                'allBuyersSections',
                'printAll',
                'clientTypes',
                'clientTypeCategories',
                'faculties',
                'employees',
                'messStaff',
                'otCourses',
                'filtersApplied',
                'courseBuyerNames',
                'grandTotal'
            ));
        }

        $report = $this->buildCategoryWisePrintSlipReportData($request);
        $allBuyersSections = $report['allBuyersSections'];
        $courseBuyerNames = $report['courseBuyerNames'];
        $otherBuyerNames = $report['otherBuyerNames'];
        $sectionBuyerNames = $report['sectionBuyerNames'];
        $otCourses = $report['otCourses'];
        $printAll = $request->boolean('print_all');

        // Backwards compatibility: keep variables expected by the view.
        $groupedSections = collect();
        $paginator = null;

        $clientTypes = ClientType::clientTypes();
        $clientTypeCategories = ClientType::active()
            ->orderBy('client_type')
            ->orderBy('client_name')
            ->get()
            ->groupBy('client_type');

        $officersMessDept = DepartmentMaster::where('department_name', 'Officers Mess')->first();
        $faculties = FacultyMaster::whereNotNull('full_name')->where('full_name', '!=', '')->orderBy('full_name')->get(['pk', 'full_name']);
        $employees = $this->academyStaffEmployees($officersMessDept);
        $messStaff = $officersMessDept
            ? EmployeeMaster::when(Schema::hasColumn('employee_master', 'status'), fn ($q) => $q->where('status', 1))
                ->where('department_master_pk', $officersMessDept->pk)
                ->orderBy('first_name')->orderBy('last_name')
                ->get(['pk', 'first_name', 'middle_name', 'last_name'])
                ->map(function ($e) {
                    $fullName = trim(($e->first_name ?? '') . ' ' . ($e->middle_name ?? '') . ' ' . ($e->last_name ?? ''));
                    return (object) ['pk' => $e->pk, 'full_name' => $fullName ?: '—'];
                })
                ->filter(fn ($e) => $e->full_name !== '—')
                ->values()
            : collect();

        $filtersApplied = true;
        $grandTotal = (float) $report['grandTotal'];

        return view('admin.mess.reports.category-wise-print-slip', compact(
            'groupedSections',
            'paginator',
            'allBuyersSections',
            'printAll',
            'clientTypes',
            'clientTypeCategories',
            'faculties',
            'employees',
            'messStaff',
            'otCourses',
            'filtersApplied',
            'courseBuyerNames',
            'otherBuyerNames',
            'sectionBuyerNames',
            'grandTotal'
        ));
    }
    /**
     * Stock Balance as of Till Date
     * Shows current stock balance for items
     */
    public function stockBalanceTillDate(Request $request)
    {
        $tillDate = $request->filled('till_date')
            ? $request->till_date
            : now()->format('Y-m-d');

        $storeIds = $this->normalizedIdList($request, 'store_id');
        $reportData = $this->buildStockBalanceTillDateData($tillDate, $storeIds);
        $stores = Store::where('status', 'active')->get();
        $selectedStoreName = $this->resolveStoreNamesLabel($storeIds);

        return view('admin.mess.reports.stock-balance-till-date', compact(
            'reportData',
            'stores',
            'tillDate',
            'storeIds',
            'selectedStoreName'
        ));
    }

    /**
     * Shared stock balance data builder used by screen, Excel and PDF exports.
     *
     * @param  array<int>  $storeIds  Empty: all stores; non-empty: aggregate purchases/issues for those stores only.
     * @return array<int, array<string, mixed>>
     */
    private function buildStockBalanceTillDateData(string $tillDate, array $storeIds = []): array
    {
        $items = ItemSubcategory::where('status', 'active')
            ->orderBy('name')
            ->get();

        $reportData = [];
        $hasAlertQtyColumn = Schema::hasColumn('mess_item_subcategories', 'alert_quantity');

        foreach ($items as $item) {
            $totalPurchased = PurchaseOrderItem::where('item_subcategory_id', $item->id)
                ->whereHas('purchaseOrder', function ($q) use ($tillDate, $storeIds) {
                    $q->where('status', 'approved')
                        ->whereDate('po_date', '<=', $tillDate);
                    if ($storeIds !== []) {
                        $q->whereIn('store_id', $storeIds);
                    }
                })
                ->sum('quantity');

            $totalIssuedKi = DB::table('kitchen_issue_items as kii')
                ->join('kitchen_issue_master as kim', 'kii.kitchen_issue_master_pk', '=', 'kim.pk')
                ->where('kii.item_subcategory_id', $item->id)
                ->where('kim.kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
                ->where('kim.store_type', 'store')
                ->whereDate('kim.issue_date', '<=', $tillDate)
                ->when($storeIds !== [], fn ($q) => $q->whereIn('kim.store_id', $storeIds))
                ->selectRaw('SUM(kii.quantity - COALESCE(kii.return_quantity, 0)) as net')
                ->value('net') ?? 0;

            $totalIssuedSv = DB::table('sv_date_range_report_items as svi')
                ->join('sv_date_range_reports as svr', 'svi.sv_date_range_report_id', '=', 'svr.id')
                ->where('svi.item_subcategory_id', $item->id)
                ->where('svr.store_type', 'store')
                ->whereDate('svr.issue_date', '<=', $tillDate)
                ->when($storeIds !== [], fn ($q) => $q->whereIn('svr.store_id', $storeIds))
                ->selectRaw('SUM(svi.quantity - COALESCE(svi.return_quantity, 0)) as net')
                ->value('net') ?? 0;

            $remainingQty = $totalPurchased - ($totalIssuedKi + $totalIssuedSv);
            if ($remainingQty <= 0) {
                continue;
            }

            $avgRate = PurchaseOrderItem::where('item_subcategory_id', $item->id)
                ->whereHas('purchaseOrder', function ($q) use ($tillDate, $storeIds) {
                    $q->where('status', 'approved')
                        ->whereDate('po_date', '<=', $tillDate);
                    if ($storeIds !== []) {
                        $q->whereIn('store_id', $storeIds);
                    }
                })
                ->avg('unit_price');

            $rate = $avgRate ?? $item->standard_cost ?? 0;
            $reportData[] = [
                'item_code' => $item->item_code ?? $item->subcategory_code ?? '-',
                'item_name' => $item->item_name ?? $item->subcategory_name ?? $item->name,
                'unit' => $item->unit_measurement ?? 'Unit',
                'remaining_qty' => $remainingQty,
                'remaining_quantity' => $remainingQty,
                'alert_quantity' => $hasAlertQtyColumn ? $item->alert_quantity : null,
                'rate' => $rate,
                'amount' => $remainingQty * $rate,
            ];
        }

        return $reportData;
    }

    private function resolveStoreName($storeId): ?string
    {
        if (!$storeId) {
            return null;
        }

        return Store::find($storeId)?->store_name;
    }

    /**
     * Comma-separated store names for multi-store filters (e.g. Stock Balance till date).
     *
     * @param  array<int>  $storeIds
     */
    private function resolveStoreNamesLabel(array $storeIds): ?string
    {
        if ($storeIds === []) {
            return null;
        }

        return Store::whereIn('id', $storeIds)->orderBy('store_name')->pluck('store_name')->implode(', ');
    }

    /**
     * Comma-separated item/subcategory names for multi-item filters (Purchase/Sale quantity report).
     *
     * @param  array<int>  $itemIds
     */
    private function resolveItemSubcategoryNamesLabel(array $itemIds): ?string
    {
        if ($itemIds === []) {
            return null;
        }

        return ItemSubcategory::whereIn('id', $itemIds)
            ->orderBy('name')
            ->get()
            ->map(function (ItemSubcategory $i) {
                return $i->item_name ?? $i->subcategory_name ?? $i->name ?? '—';
            })
            ->implode(', ');
    }

    /**
     * Get items where remaining_quantity <= alert_quantity (for login alert).
     * Uses same calculation as Stock Balance till date. Default: till_date = today, store_id = null.
     *
     * @return array<int, array{item_name: string, unit: string, remaining_quantity: float, alert_quantity: float}>
     */
    public static function getLowStockAlertItems(?string $tillDate = null, $storeId = null): array
    {
        if (!Schema::hasColumn('mess_item_subcategories', 'alert_quantity')) {
            return [];
        }
        $tillDate = $tillDate ?? now()->format('Y-m-d');
        $items = ItemSubcategory::where('status', 'active')
            ->whereNotNull('alert_quantity')
            ->orderBy('name')
            ->get();
        $out = [];
        foreach ($items as $item) {
            $totalPurchased = PurchaseOrderItem::where('item_subcategory_id', $item->id)
                ->whereHas('purchaseOrder', function ($q) use ($tillDate, $storeId) {
                    $q->where('status', 'approved')->whereDate('po_date', '<=', $tillDate);
                    if ($storeId) {
                        $q->where('store_id', $storeId);
                    }
                })
                ->sum('quantity');
            $totalIssuedKi = \DB::table('kitchen_issue_items as kii')
                ->join('kitchen_issue_master as kim', 'kii.kitchen_issue_master_pk', '=', 'kim.pk')
                ->where('kii.item_subcategory_id', $item->id)
                ->where('kim.kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
                ->where('kim.store_type', 'store')
                ->whereDate('kim.issue_date', '<=', $tillDate)
                ->when($storeId, fn ($q) => $q->where('kim.store_id', $storeId))
                ->selectRaw('SUM(kii.quantity - COALESCE(kii.return_quantity, 0)) as net')
                ->value('net') ?? 0;
            $totalIssuedSv = \DB::table('sv_date_range_report_items as svi')
                ->join('sv_date_range_reports as svr', 'svi.sv_date_range_report_id', '=', 'svr.id')
                ->where('svi.item_subcategory_id', $item->id)
                ->where('svr.store_type', 'store')
                ->whereDate('svr.issue_date', '<=', $tillDate)
                ->when($storeId, fn ($q) => $q->where('svr.store_id', $storeId))
                ->selectRaw('SUM(svi.quantity - COALESCE(svi.return_quantity, 0)) as net')
                ->value('net') ?? 0;
            $remainingQty = $totalPurchased - ($totalIssuedKi + $totalIssuedSv);
            $alertQty = (float) $item->alert_quantity;
            if ($alertQty <= 0) {
                continue;
            }
            if ($remainingQty <= $alertQty) {
                $out[] = [
                    'item_id' => $item->id,
                    'item_name' => $item->item_name ?? $item->subcategory_name ?? $item->name,
                    'unit' => $item->unit_measurement ?? 'Unit',
                    'remaining_quantity' => $remainingQty,
                    'alert_quantity' => $alertQty,
                ];
            }
        }
        return $out;
    }

    /**
     * Low Stock Report
     * Lists items where remaining_quantity <= alert_quantity using the same logic as the login alert.
     */
    public function lowStockReport(Request $request)
    {
        $tillDate = $request->filled('till_date')
            ? $request->till_date
            : now()->format('Y-m-d');

        $storeId = $request->filled('store_id') ? $request->store_id : null;

        $items = self::getLowStockAlertItems($tillDate, $storeId);
        $stores = Store::where('status', 'active')->get();

        $selectedStoreName = null;
        if ($storeId) {
            $selectedStore = Store::find($storeId);
            $selectedStoreName = $selectedStore ? $selectedStore->store_name : null;
        }

        return view('admin.mess.reports.low-stock', compact(
            'items',
            'stores',
            'tillDate',
            'storeId',
            'selectedStoreName'
        ));
    }

    /**
     * Low Stock Report - PDF Export
     */
    public function lowStockPdf(Request $request)
    {
        $tillDate = $request->filled('till_date')
            ? $request->till_date
            : now()->format('Y-m-d');

        $storeId = $request->filled('store_id') ? $request->store_id : null;
        $items = self::getLowStockAlertItems($tillDate, $storeId);

        $selectedStoreName = null;
        if ($storeId) {
            $selectedStore = Store::find($storeId);
            $selectedStoreName = $selectedStore ? $selectedStore->store_name : null;
        }

        $data = [
            'items' => $items,
            'tillDate' => $tillDate,
            'selectedStoreName' => $selectedStoreName,
        ];

        $pdf = Pdf::loadView('admin.mess.reports.pdf.low-stock-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'           => 'DejaVu Sans',
                'isHtml5ParserEnabled'  => true,
                'isRemoteEnabled'       => true,
                'dpi'                   => 96,
            ]);

        $fileName = 'low-stock-report-' . $tillDate . '-' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Selling Voucher Print Slip
     * Shows selling voucher details with category-wise filters
     */
    public function sellingVoucherPrintSlip(Request $request)
    {
        $query = \App\Models\Mess\SellingVoucherDateRangeReport::with([
            'store',
            'clientTypeCategory',
            'items.itemSubcategory'
        ]);
        
        // Apply filters
        if ($request->filled('from_date')) {
            $from = $request->from_date;
            $query->where(function ($q) use ($from) {
                $q->whereDate('issue_date', '>=', $from)
                  ->orWhereDate('date_from', '>=', $from);
            });
        }
        
        if ($request->filled('to_date')) {
            $to = $request->to_date;
            $query->where(function ($q) use ($to) {
                $q->whereDate('issue_date', '<=', $to)
                  ->orWhereDate('date_to', '<=', $to);
            });
        }
        
        // Employee / OT filter
        if ($request->filled('employee_ot_filter')) {
            if ($request->employee_ot_filter === 'employee_ot') {
                // Show both employee and OT
                $query->whereIn('client_type_slug', [\App\Models\Mess\ClientType::TYPE_EMPLOYEE, \App\Models\Mess\ClientType::TYPE_OT]);
            } elseif ($request->employee_ot_filter === 'employee') {
                // Show only employee
                $query->where('client_type_slug', \App\Models\Mess\ClientType::TYPE_EMPLOYEE);
            } elseif ($request->employee_ot_filter === 'ot') {
                // Show only OT
                $query->where('client_type_slug', \App\Models\Mess\ClientType::TYPE_OT);
            }
        }
        
        if ($request->filled('client_type_slug')) {
            $query->where('client_type_slug', $request->client_type_slug);
        }
        
        if ($request->filled('client_type_pk')) {
            $query->where('client_type_pk', $request->client_type_pk);
        }
        
        if ($request->filled('buyer_name') && $request->buyer_name !== '') {
            $query->where('client_name', 'LIKE', '%' . $request->buyer_name . '%');
        }
        
        // Include all statuses (Draft, Final, Approved)
        $query->whereIn('status', [
            \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_DRAFT,
            \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_FINAL,
            \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_APPROVED,
        ]);
        
        $vouchers = $query->orderBy('issue_date', 'desc')->get();
        
        // Get filter options
        $clientTypes = \App\Models\Mess\ClientType::clientTypes();
        $clientTypeCategories = \App\Models\Mess\ClientType::active()
            ->orderBy('client_type')
            ->orderBy('client_name')
            ->get()
            ->groupBy('client_type');
        
        return view('admin.mess.reports.selling-voucher-print-slip', compact(
            'vouchers',
            'clientTypes',
            'clientTypeCategories'
        ));
    }

    /**
     * Item Report
     * Item-wise total Purchase quantity and total Sale quantity with date range.
     * Views: item-wise, subcategory-wise (grouped by category), category-wise (one category selected).
     */
    public function purchaseSaleQuantityReport(Request $request)
    {
        $fromDate = $request->filled('from_date') ? $request->from_date : now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->filled('to_date') ? $request->to_date : now()->format('Y-m-d');
        $viewType = $request->filled('view_type') ? $request->view_type : 'item_wise';
        if (!in_array($viewType, ['item_wise', 'subcategory_wise', 'category_wise'], true)) {
            $viewType = 'item_wise';
        }
        $categoryId = $request->filled('category_id') ? $request->category_id : null;
        $itemIds = $this->normalizedIdList($request, 'item_id');
        $storeIds = $this->normalizedIdList($request, 'store_id');

        [$items, $reportData] = $this->buildPurchaseSaleQuantityData($fromDate, $toDate, $viewType, $categoryId, $itemIds, $storeIds);

        if ($viewType === 'item_wise') {
            $groupedData = null;
        } elseif ($viewType === 'subcategory_wise') {
            $groupedData = collect($reportData)
                ->groupBy(function ($r) {
                    return $r['category_name'] ?? 'Uncategorized';
                })
                ->map(function ($rows, $catName) {
                    return ['category_name' => $catName, 'items' => $rows->values()->all()];
                })
                ->values()
                ->all();
        } else {
            if ($items->isEmpty()) {
                $groupedData = [];
            } else {
                $catName = $items->first()->category ? $items->first()->category->category_name : 'Category';
                $groupedData = [['category_name' => $catName, 'items' => $reportData]];
            }
        }

        $categories = ItemCategory::active()->orderBy('category_name')->get();

        $allItems = ItemSubcategory::where('status', 'active')->orderBy('name')->get();

        $stores = Store::where('status', 'active')->orderBy('store_name')->get();
        $selectedStoreName = $this->resolveStoreNamesLabel($storeIds);
        $selectedItemNamesLabel = $this->resolveItemSubcategoryNamesLabel($itemIds);

        return view('admin.mess.reports.purchase-sale-quantity', compact(
            'reportData',
            'groupedData',
            'fromDate',
            'toDate',
            'viewType',
            'categoryId',
            'itemIds',
            'categories',
            'allItems',
            'stores',
            'storeIds',
            'selectedStoreName',
            'selectedItemNamesLabel'
        ));
    }

    /**
     * Shared data builder for Purchase/Sale Quantity (view, Excel, PDF).
     *
     * @param  array<int>  $itemIds   Empty: all items (within view/category scope); non-empty: restrict to these subcategories.
     * @param  array<int>  $storeIds  Empty: all stores; non-empty: filter purchases and sales to those stores.
     * @return array{\Illuminate\Support\Collection, array<int, array>}
     */
    private function buildPurchaseSaleQuantityData(string $fromDate, string $toDate, string $viewType, $categoryId, array $itemIds = [], array $storeIds = []): array
    {
        $itemsQuery = ItemSubcategory::where('status', 'active')->with('category')->orderBy('name');
        if ($viewType === 'category_wise') {
            if ($categoryId) {
                $itemsQuery->where('category_id', $categoryId);
            } else {
                $itemsQuery->whereRaw('1 = 0');
            }
        }
        if ($itemIds !== []) {
            $itemsQuery->whereIn('id', $itemIds);
        }
        $items = $itemsQuery->get();

        $reportData = [];
        foreach ($items as $item) {
            $purchaseAgg = PurchaseOrderItem::where('item_subcategory_id', $item->id)
                ->whereHas('purchaseOrder', function ($q) use ($fromDate, $toDate, $storeIds) {
                    $q->where('status', 'approved')
                        ->whereDate('po_date', '>=', $fromDate)
                        ->whereDate('po_date', '<=', $toDate);
                    if ($storeIds !== []) {
                        $q->whereIn('store_id', $storeIds);
                    }
                })
                ->selectRaw('COALESCE(SUM(quantity), 0) as total_qty, COALESCE(SUM(quantity * unit_price), 0) as total_amount')
                ->first();
            $purchaseQty = (float) ($purchaseAgg->total_qty ?? 0);
            $purchaseAmount = (float) ($purchaseAgg->total_amount ?? 0);
            $avgPurchasePrice = $purchaseQty > 0 ? $purchaseAmount / $purchaseQty : null;

            $saleKiQuery = \DB::table('kitchen_issue_items as kii')
                ->join('kitchen_issue_master as kim', 'kii.kitchen_issue_master_pk', '=', 'kim.pk')
                ->where('kii.item_subcategory_id', $item->id)
                ->where('kim.kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
                ->where('kim.store_type', 'store')
                ->whereDate('kim.issue_date', '>=', $fromDate)
                ->whereDate('kim.issue_date', '<=', $toDate);
            if ($storeIds !== []) {
                $saleKiQuery->whereIn('kim.store_id', $storeIds);
            }
            $saleKi = $saleKiQuery
                ->selectRaw('COALESCE(SUM(kii.quantity - COALESCE(kii.return_quantity, 0)), 0) as net_qty, COALESCE(SUM((kii.quantity - COALESCE(kii.return_quantity, 0)) * COALESCE(kii.rate, 0)), 0) as net_amount')
                ->first();
            $saleQtyKi = (float) ($saleKi->net_qty ?? 0);
            $saleAmountKi = (float) ($saleKi->net_amount ?? 0);

            $saleSvQuery = \DB::table('sv_date_range_report_items as svi')
                ->join('sv_date_range_reports as svr', 'svi.sv_date_range_report_id', '=', 'svr.id')
                ->where('svi.item_subcategory_id', $item->id)
                ->where('svr.store_type', 'store')
                ->whereDate('svr.issue_date', '>=', $fromDate)
                ->whereDate('svr.issue_date', '<=', $toDate);
            if ($storeIds !== []) {
                $saleSvQuery->whereIn('svr.store_id', $storeIds);
            }
            $saleSv = $saleSvQuery
                ->selectRaw('COALESCE(SUM(svi.quantity - COALESCE(svi.return_quantity, 0)), 0) as net_qty, COALESCE(SUM((svi.quantity - COALESCE(svi.return_quantity, 0)) * COALESCE(svi.rate, 0)), 0) as net_amount')
                ->first();
            $saleQtySv = (float) ($saleSv->net_qty ?? 0);
            $saleAmountSv = (float) ($saleSv->net_amount ?? 0);

            $saleQty = $saleQtyKi + $saleQtySv;
            $saleAmount = $saleAmountKi + $saleAmountSv;
            $avgSalePrice = $saleQty > 0 ? $saleAmount / $saleQty : null;

            $row = [
                'item_name' => $item->item_name ?? $item->subcategory_name ?? $item->name ?? '—',
                'unit' => $item->unit_measurement ?? 'Unit',
                'purchase_qty' => $purchaseQty,
                'sale_qty' => $saleQty,
                'avg_purchase_price' => $avgPurchasePrice,
                'avg_sale_price' => $avgSalePrice,
                'category_id' => $item->category_id,
                'category_name' => $item->category ? $item->category->category_name : null,
            ];
            $reportData[] = $row;
        }

        return [$items, $reportData];
    }
}

