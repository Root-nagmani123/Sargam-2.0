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
use DB;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    /**
     * Stock Purchase Details Report
     * Shows detailed purchase information grouped by bill (Purchase Order)
     */
    public function stockPurchaseDetails(Request $request)
    {
        // Use request dates when provided, otherwise default to today (so default filter applies on first load)
        $fromDate = $request->filled('from_date') ? $request->from_date : now()->format('Y-m-d');
        $toDate = $request->filled('to_date') ? $request->to_date : now()->format('Y-m-d');

        $baseQuery = PurchaseOrder::with(['vendor', 'store', 'items.itemSubcategory']);

        $baseQuery->whereDate('po_date', '>=', $fromDate);
        $baseQuery->whereDate('po_date', '<=', $toDate);
        if ($request->filled('store_id')) {
            $baseQuery->where('store_id', $request->store_id);
        }
        if ($request->filled('vendor_id')) {
            $baseQuery->where('vendor_id', $request->vendor_id);
        }

        $baseQuery->orderBy('po_date', 'asc')->orderBy('id', 'asc');

        // Grand total: sum of (quantity * unit_price) for all filtered POs
        $poIds = (clone $baseQuery)->pluck('id');
        $grandTotal = PurchaseOrderItem::whereIn('purchase_order_id', $poIds)
            ->selectRaw('SUM(quantity * unit_price) as total')
            ->value('total') ?? 0;

        // Paginate purchase orders (bills per page)
        $purchaseOrders = $baseQuery->paginate(5)->withQueryString();

        $stores = Store::where('status', 'active')->get();
        $vendors = Vendor::all();

        $selectedVendor = null;
        if ($request->filled('vendor_id')) {
            $selectedVendor = Vendor::find($request->vendor_id);
        }

        return view('admin.mess.reports.stock-purchase-details', compact(
            'purchaseOrders',
            'grandTotal',
            'stores',
            'vendors',
            'selectedVendor',
            'fromDate',
            'toDate'
        ));
    }

    /**
     * Stock Summary Report
     * Shows summary of all items with opening, purchase, sale, and closing stock
     */
    public function stockSummary(Request $request)
    {
        $fromDate = $request->filled('from_date') ? $request->from_date : now()->format('Y-m-d');
        $toDate = $request->filled('to_date') ? $request->to_date : now()->format('Y-m-d');
        $storeId = $request->filled('store_id') ? $request->store_id : null;
        $storeType = $request->filled('store_type') ? $request->store_type : 'main';

        [$reportData, $selectedStoreName] = $this->getStockSummaryReportData($fromDate, $toDate, $storeId, $storeType);

        $stores = Store::where('status', 'active')->get();
        $subStores = SubStore::where('status', 'active')->get();

        return view('admin.mess.reports.stock-summary', compact(
            'reportData',
            'stores',
            'subStores',
            'fromDate',
            'toDate',
            'storeId',
            'storeType',
            'selectedStoreName'
        ));
    }

    /**
     * Stock Summary Report - Excel Export
     */
    public function stockSummaryExcel(Request $request)
    {
        $fromDate = $request->filled('from_date') ? $request->from_date : now()->format('Y-m-d');
        $toDate = $request->filled('to_date') ? $request->to_date : now()->format('Y-m-d');
        $storeId = $request->filled('store_id') ? $request->store_id : null;
        $storeType = $request->filled('store_type') ? $request->store_type : 'main';

        [$reportData] = $this->getStockSummaryReportData($fromDate, $toDate, $storeId, $storeType);

        $fileName = 'stock-summary-report-' . $fromDate . '-to-' . $toDate . '-' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(new StockSummaryExport($reportData), $fileName);
    }

    /**
     * Stock Purchase Details Report - Excel Export
     */
    public function stockPurchaseDetailsExcel(Request $request)
    {
        $baseQuery = PurchaseOrder::with(['vendor', 'store', 'items.itemSubcategory']);

        if ($request->filled('from_date')) {
            $baseQuery->whereDate('po_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $baseQuery->whereDate('po_date', '<=', $request->to_date);
        }
        if ($request->filled('store_id')) {
            $baseQuery->where('store_id', $request->store_id);
        }
        if ($request->filled('vendor_id')) {
            $baseQuery->where('vendor_id', $request->vendor_id);
        }
        $baseQuery->orderBy('po_date', 'asc')->orderBy('id', 'asc');
        $purchaseOrders = $baseQuery->get();

        $fromDate = $request->filled('from_date') ? $request->from_date : now()->format('Y-m-d');
        $toDate = $request->filled('to_date') ? $request->to_date : now()->format('Y-m-d');
        $vendorName = 'All Vendors';
        if ($request->filled('vendor_id')) {
            $v = Vendor::find($request->vendor_id);
            if ($v) {
                $vendorName = $v->name;
            }
        }
        $fileName = 'stock-purchase-details-' . $fromDate . '-to-' . $toDate . '-' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(new StockPurchaseDetailsExport($purchaseOrders, $fromDate, $toDate, $vendorName), $fileName);
    }

    /**
     * Stock Balance Till Date Report - Excel Export
     */
    public function stockBalanceTillDateExcel(Request $request)
    {
        $tillDate = $request->filled('till_date') ? $request->till_date : now()->format('Y-m-d');
        $storeId = $request->filled('store_id') ? $request->store_id : null;
        $items = ItemSubcategory::where('status', 'active')->orderBy('name')->get();
        $reportData = [];

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
            if ($remainingQty <= 0) {
                continue;
            }
            $avgRate = PurchaseOrderItem::where('item_subcategory_id', $item->id)
                ->whereHas('purchaseOrder', function ($q) use ($tillDate, $storeId) {
                    $q->where('status', 'approved')->whereDate('po_date', '<=', $tillDate);
                    if ($storeId) {
                        $q->where('store_id', $storeId);
                    }
                })
                ->avg('unit_price');
            $rate = $avgRate ?? $item->standard_cost ?? 0;
            $reportData[] = [
                'item_code' => $item->item_code ?? $item->subcategory_code ?? '—',
                'item_name' => $item->item_name ?? $item->subcategory_name ?? $item->name,
                'unit' => $item->unit_measurement ?? 'Unit',
                'remaining_qty' => $remainingQty,
                'remaining_quantity' => $remainingQty,
                'rate' => $rate,
                'amount' => $remainingQty * $rate,
            ];
        }

        $fileName = 'stock-balance-till-date-' . $tillDate . '-' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(new StockBalanceTillDateExport($reportData), $fileName);
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
        $itemId = $request->filled('item_id') ? $request->item_id : null;

        $itemsQuery = ItemSubcategory::where('status', 'active')->with('category')->orderBy('name');
        if ($viewType === 'category_wise' && $categoryId) {
            $itemsQuery->where('category_id', $categoryId);
        } elseif ($viewType === 'category_wise') {
            $itemsQuery->whereRaw('1 = 0');
        }
        if ($itemId) {
            $itemsQuery->where('id', $itemId);
        }
        $items = $itemsQuery->get();

        $reportData = [];
        foreach ($items as $item) {
            $purchaseAgg = PurchaseOrderItem::where('item_subcategory_id', $item->id)
                ->whereHas('purchaseOrder', function ($q) use ($fromDate, $toDate) {
                    $q->where('status', 'approved')
                        ->whereDate('po_date', '>=', $fromDate)
                        ->whereDate('po_date', '<=', $toDate);
                })
                ->selectRaw('COALESCE(SUM(quantity), 0) as total_qty, COALESCE(SUM(quantity * unit_price), 0) as total_amount')
                ->first();
            $purchaseQty = (float) ($purchaseAgg->total_qty ?? 0);
            $purchaseAmount = (float) ($purchaseAgg->total_amount ?? 0);
            $avgPurchasePrice = $purchaseQty > 0 ? $purchaseAmount / $purchaseQty : null;

            $saleKi = \DB::table('kitchen_issue_items as kii')
                ->join('kitchen_issue_master as kim', 'kii.kitchen_issue_master_pk', '=', 'kim.pk')
                ->where('kii.item_subcategory_id', $item->id)
                ->where('kim.kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
                ->where('kim.store_type', 'store')
                ->whereDate('kim.issue_date', '>=', $fromDate)
                ->whereDate('kim.issue_date', '<=', $toDate)
                ->selectRaw('COALESCE(SUM(kii.quantity - COALESCE(kii.return_quantity, 0)), 0) as net_qty, COALESCE(SUM((kii.quantity - COALESCE(kii.return_quantity, 0)) * COALESCE(kii.rate, 0)), 0) as net_amount')
                ->first();
            $saleQtyKi = (float) ($saleKi->net_qty ?? 0);
            $saleAmountKi = (float) ($saleKi->net_amount ?? 0);
            $saleSv = \DB::table('sv_date_range_report_items as svi')
                ->join('sv_date_range_reports as svr', 'svi.sv_date_range_report_id', '=', 'svr.id')
                ->where('svi.item_subcategory_id', $item->id)
                ->where('svr.store_type', 'store')
                ->whereDate('svr.issue_date', '>=', $fromDate)
                ->whereDate('svr.issue_date', '<=', $toDate)
                ->selectRaw('COALESCE(SUM(svi.quantity - COALESCE(svi.return_quantity, 0)), 0) as net_qty, COALESCE(SUM((svi.quantity - COALESCE(svi.return_quantity, 0)) * COALESCE(svi.rate, 0)), 0) as net_amount')
                ->first();
            $saleQtySv = (float) ($saleSv->net_qty ?? 0);
            $saleAmountSv = (float) ($saleSv->net_amount ?? 0);
            $saleQty = $saleQtyKi + $saleQtySv;
            $saleAmount = $saleAmountKi + $saleAmountSv;
            $avgSalePrice = $saleQty > 0 ? $saleAmount / $saleQty : null;

            $reportData[] = [
                'item_name' => $item->item_name ?? $item->subcategory_name ?? $item->name ?? '—',
                'unit' => $item->unit_measurement ?? 'Unit',
                'purchase_qty' => $purchaseQty,
                'sale_qty' => $saleQty,
                'avg_purchase_price' => $avgPurchasePrice,
                'avg_sale_price' => $avgSalePrice,
                'category_id' => $item->category_id,
                'category_name' => $item->category ? $item->category->category_name : null,
            ];
        }

        $fileName = 'purchase-sale-quantity-' . $fromDate . '-to-' . $toDate . '-' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(new PurchaseSaleQuantityExport($reportData), $fileName);
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
            $query->whereDate('issue_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('issue_date', '<=', $request->to_date);
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
     */
    public function categoryWisePrintSlipExcel(Request $request)
    {
        $clientTypeSlugToInt = array_flip([
            \App\Models\Mess\ClientType::TYPE_EMPLOYEE => KitchenIssueMaster::CLIENT_EMPLOYEE,
            \App\Models\Mess\ClientType::TYPE_OT      => KitchenIssueMaster::CLIENT_OT,
            \App\Models\Mess\ClientType::TYPE_COURSE  => KitchenIssueMaster::CLIENT_COURSE,
            \App\Models\Mess\ClientType::TYPE_OTHER   => KitchenIssueMaster::CLIENT_OTHER,
        ]);

        $svQuery = \App\Models\Mess\SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'items.itemSubcategory']);
        if ($request->filled('from_date')) {
            $svQuery->whereDate('issue_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $svQuery->whereDate('issue_date', '<=', $request->to_date);
        }
        if ($request->filled('client_type_slug')) {
            $svQuery->where('client_type_slug', $request->client_type_slug);
        }
        if ($request->filled('client_type_pk')) {
            $svQuery->where('client_type_pk', $request->client_type_pk);
        }
        if ($request->filled('buyer_name')) {
            $svQuery->where('client_name', 'LIKE', '%' . trim($request->buyer_name) . '%');
        }
        $svQuery->whereIn('status', [
            \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_DRAFT,
            \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_FINAL,
            \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_APPROVED,
        ]);
        $svQuery->whereHas('items');
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
        if ($request->filled('client_type_slug') && isset($clientTypeSlugToInt[$request->client_type_slug])) {
            $kiQuery->where('client_type', $clientTypeSlugToInt[$request->client_type_slug]);
        }
        if ($request->filled('client_type_pk')) {
            $kiQuery->where('client_type_pk', $request->client_type_pk);
        }
        if ($request->filled('buyer_name')) {
            $kiQuery->where('client_name', 'LIKE', '%' . trim($request->buyer_name) . '%');
        }
        $kiVouchers = $kiQuery->orderBy('issue_date', 'desc')->get();
        $slugMap = self::kitchenIssueClientTypeToSlug();
        foreach ($kiVouchers as $v) {
            $v->request_no = 'KI-' . str_pad($v->pk, 6, '0', STR_PAD_LEFT);
            $v->client_type_slug = $slugMap[$v->client_type] ?? 'other';
            $v->id = $v->pk;
        }

        $vouchers = $svVouchers->concat($kiVouchers)
            ->sortByDesc(function ($v) {
                return $v->issue_date ? $v->issue_date->format('Y-m-d') : '';
            })
            ->values();

        $fileName = 'category-wise-print-slip-' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(new CategoryWisePrintSlipExport($vouchers), $fileName);
    }

    /**
     * Build Stock Summary report data for the given filters.
     * Returns [reportData, selectedStoreName].
     */
    private function getStockSummaryReportData(string $fromDate, string $toDate, $storeId, string $storeType): array
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
                    ->whereHas('purchaseOrder', function ($q) use ($previousDate, $storeId) {
                        $q->where('status', 'approved')->whereDate('po_date', '<=', $previousDate);
                        if ($storeId) {
                            $q->where('store_id', $storeId);
                        }
                    })
                    ->sum('quantity');
                $previousSale = \DB::table('kitchen_issue_items as kii')
                    ->join('kitchen_issue_master as kim', 'kii.kitchen_issue_master_pk', '=', 'kim.pk')
                    ->where('kii.item_subcategory_id', $item->id)
                    ->where('kim.kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
                    ->where('kim.store_type', 'store')
                    ->whereDate('kim.issue_date', '<=', $previousDate)
                    ->when($storeId, fn ($q) => $q->where('kim.store_id', $storeId))
                    ->selectRaw('SUM(kii.quantity - COALESCE(kii.return_quantity, 0)) as net')
                    ->value('net') ?? 0;
                $previousSaleSv = \DB::table('sv_date_range_report_items as svi')
                    ->join('sv_date_range_reports as svr', 'svi.sv_date_range_report_id', '=', 'svr.id')
                    ->where('svi.item_subcategory_id', $item->id)
                    ->where('svr.store_type', 'store')
                    ->whereDate('svr.issue_date', '<=', $previousDate)
                    ->when($storeId, fn ($q) => $q->where('svr.store_id', $storeId))
                    ->selectRaw('SUM(svi.quantity - COALESCE(svi.return_quantity, 0)) as net')
                    ->value('net') ?? 0;
                $itemData['opening_qty'] = $previousPurchase - $previousSale - $previousSaleSv;
            } else {
                $previousAllocation = \DB::table('mess_store_allocation_items as sai')
                    ->join('mess_store_allocations as sa', 'sai.store_allocation_id', '=', 'sa.id')
                    ->where('sai.item_subcategory_id', $item->id)
                    ->whereDate('sa.allocation_date', '<=', $previousDate)
                    ->when($storeId, fn ($q) => $q->where('sa.sub_store_id', $storeId))
                    ->sum('sai.quantity');
                $previousSale = \DB::table('kitchen_issue_items as kii')
                    ->join('kitchen_issue_master as kim', 'kii.kitchen_issue_master_pk', '=', 'kim.pk')
                    ->where('kii.item_subcategory_id', $item->id)
                    ->where('kim.kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
                    ->where('kim.store_type', 'sub_store')
                    ->whereDate('kim.issue_date', '<=', $previousDate)
                    ->when($storeId, fn ($q) => $q->where('kim.store_id', $storeId))
                    ->selectRaw('SUM(kii.quantity - COALESCE(kii.return_quantity, 0)) as net')
                    ->value('net') ?? 0;
                $previousSaleSv = \DB::table('sv_date_range_report_items as svi')
                    ->join('sv_date_range_reports as svr', 'svi.sv_date_range_report_id', '=', 'svr.id')
                    ->where('svi.item_subcategory_id', $item->id)
                    ->where('svr.store_type', 'sub_store')
                    ->whereDate('svr.issue_date', '<=', $previousDate)
                    ->when($storeId, fn ($q) => $q->where('svr.store_id', $storeId))
                    ->selectRaw('SUM(svi.quantity - COALESCE(svi.return_quantity, 0)) as net')
                    ->value('net') ?? 0;
                $itemData['opening_qty'] = $previousAllocation - $previousSale - $previousSaleSv;
            }

            $itemData['opening_amount'] = $itemData['opening_qty'] * $itemData['opening_rate'];

            if ($storeType == 'main') {
                $purchases = PurchaseOrderItem::where('item_subcategory_id', $item->id)
                    ->whereHas('purchaseOrder', function ($q) use ($fromDate, $toDate, $storeId) {
                        $q->where('status', 'approved')->whereBetween('po_date', [$fromDate, $toDate]);
                        if ($storeId) {
                            $q->where('store_id', $storeId);
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
                    ->when($storeId, fn ($q) => $q->where('sa.sub_store_id', $storeId))
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
                ->when($storeId, fn ($q) => $q->where('kim.store_id', $storeId))
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
                ->when($storeId, fn ($q) => $q->where('svr.store_id', $storeId))
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
        if ($storeId) {
            if ($storeType == 'main') {
                $selectedStore = Store::find($storeId);
                $selectedStoreName = $selectedStore ? $selectedStore->store_name : null;
            } else {
                $selectedStore = SubStore::find($storeId);
                $selectedStoreName = $selectedStore ? $selectedStore->sub_store_name : null;
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
        ];
    }

    /**
     * Category-wise Print Slip
     * Shows selling voucher details from both: Selling Voucher Date Range and Kitchen Issue (Selling Voucher type)
     */
    public function categoryWisePrintSlip(Request $request)
    {
        $clientTypeSlugToInt = array_flip([
            \App\Models\Mess\ClientType::TYPE_EMPLOYEE => KitchenIssueMaster::CLIENT_EMPLOYEE,
            \App\Models\Mess\ClientType::TYPE_OT      => KitchenIssueMaster::CLIENT_OT,
            \App\Models\Mess\ClientType::TYPE_COURSE  => KitchenIssueMaster::CLIENT_COURSE,
            \App\Models\Mess\ClientType::TYPE_OTHER   => KitchenIssueMaster::CLIENT_OTHER,
        ]);

        // --- 1. Selling Voucher Date Range ---
        $svQuery = \App\Models\Mess\SellingVoucherDateRangeReport::with([
            'store',
            'clientTypeCategory',
            'items.itemSubcategory'
        ]);

        if ($request->filled('from_date')) {
            $svQuery->whereDate('issue_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $svQuery->whereDate('issue_date', '<=', $request->to_date);
        }
        if ($request->filled('client_type_slug')) {
            $svQuery->where('client_type_slug', $request->client_type_slug);
        }
        if ($request->filled('client_type_pk')) {
            $svQuery->where('client_type_pk', $request->client_type_pk);
        }
        if ($request->filled('buyer_name')) {
            $svQuery->where('client_name', 'LIKE', '%' . trim($request->buyer_name) . '%');
        }
        $svQuery->whereIn('status', [
            \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_DRAFT,
            \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_FINAL,
            \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_APPROVED,
        ]);
        $svQuery->whereHas('items');
        $svVouchers = $svQuery->orderBy('issue_date', 'desc')->get();
        foreach ($svVouchers as $v) {
            $v->request_no = 'SV-' . str_pad($v->id, 6, '0', STR_PAD_LEFT);
        }

        // --- 2. Kitchen Issue (Selling Voucher type only, not Date Range) ---
        // Include all statuses (Pending, Processing, Approved, Rejected, Completed)
        $kiQuery = KitchenIssueMaster::with(['store', 'clientTypeCategory', 'items.itemSubcategory'])
            ->where('kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
            ->whereHas('items');

        if ($request->filled('from_date')) {
            $kiQuery->whereDate('issue_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $kiQuery->whereDate('issue_date', '<=', $request->to_date);
        }
        if ($request->filled('client_type_slug') && isset($clientTypeSlugToInt[$request->client_type_slug])) {
            $kiQuery->where('client_type', $clientTypeSlugToInt[$request->client_type_slug]);
        }
        if ($request->filled('client_type_pk')) {
            $kiQuery->where('client_type_pk', $request->client_type_pk);
        }
        if ($request->filled('buyer_name')) {
            $kiQuery->where('client_name', 'LIKE', '%' . trim($request->buyer_name) . '%');
        }
        $kiVouchers = $kiQuery->orderBy('issue_date', 'desc')->get();
        $slugMap = self::kitchenIssueClientTypeToSlug();
        foreach ($kiVouchers as $v) {
            $v->request_no = 'KI-' . str_pad($v->pk, 6, '0', STR_PAD_LEFT);
            $v->client_type_slug = $slugMap[$v->client_type] ?? 'other';
            $v->id = $v->pk; // so view can use $voucher->id if needed
        }

        // --- 3. Merge and sort by issue_date desc ---
        $vouchers = $svVouchers->concat($kiVouchers)
            ->sortByDesc(function ($v) { return $v->issue_date ? $v->issue_date->format('Y-m-d') : ''; })
            ->values();

        // --- 4. Paginate by BUYER: one buyer per page (or all for print_all) ---
        $groupedByBuyer = $vouchers->groupBy('client_type_pk');
        $totalBuyers = $groupedByBuyer->count();
        $printAll = $request->boolean('print_all');

        if ($printAll) {
            $allBuyersSections = $groupedByBuyer->values()->map(function ($buyerVouchers) {
                return $buyerVouchers->groupBy(function ($v) {
                    $pk = $v->client_type_pk ?? '';
                    $slug = $v->client_type_slug ?? '';
                    return $pk . '-' . $slug;
                });
            });
            $currentBuyerVouchers = collect();
            $groupedSections = collect();
            $paginator = null;
        } else {
            $currentPage = (int) $request->get('page', 1);
            $perPage = 1;
            $currentPage = max(1, min($currentPage, (int) max(1, ceil($totalBuyers / $perPage)) ?: 1));
            $currentBuyerVouchers = $groupedByBuyer->values()->forPage($currentPage, $perPage)->first();
            if (! $currentBuyerVouchers) {
                $currentBuyerVouchers = collect();
            }
            $groupedSections = $currentBuyerVouchers->groupBy(function ($v) {
                $pk = $v->client_type_pk ?? '';
                $slug = $v->client_type_slug ?? '';
                return $pk . '-' . $slug;
            });
            $allBuyersSections = collect();
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $groupedByBuyer->forPage($currentPage, $perPage)->values(),
                $totalBuyers,
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->except('print_all')]
            );
        }

        $clientTypes = ClientType::clientTypes();
        $clientTypeCategories = ClientType::active()
            ->orderBy('client_type')
            ->orderBy('client_name')
            ->get()
            ->groupBy('client_type');

        $faculties = FacultyMaster::whereNotNull('full_name')->where('full_name', '!=', '')->orderBy('full_name')->get(['pk', 'full_name']);
        $employees = EmployeeMaster::when(Schema::hasColumn('employee_master', 'status'), fn ($q) => $q->where('status', 1))
            ->orderBy('first_name')->orderBy('last_name')
            ->get(['pk', 'first_name', 'middle_name', 'last_name'])
            ->map(function ($e) {
                $fullName = trim(($e->first_name ?? '') . ' ' . ($e->middle_name ?? '') . ' ' . ($e->last_name ?? ''));
                return (object) ['pk' => $e->pk, 'full_name' => $fullName ?: '—'];
            })
            ->filter(fn ($e) => $e->full_name !== '—')
            ->values();
        $officersMessDept = DepartmentMaster::where('department_name', 'Officers Mess')->first();
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
            'otCourses'
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
        
        $storeId = $request->filled('store_id') ? $request->store_id : null;
        
        // Get all items (subcategories) with their current stock
        $itemsQuery = ItemSubcategory::where('status', 'active')
            ->orderBy('name');
        
        $items = $itemsQuery->get();
        
        // Build report data
        $reportData = [];
        
        foreach ($items as $item) {
            // Calculate current stock based on purchases and sales till date
            // Get total purchases till date
            $totalPurchased = PurchaseOrderItem::where('item_subcategory_id', $item->id)
                ->whereHas('purchaseOrder', function($q) use ($tillDate, $storeId) {
                    $q->where('status', 'approved')
                      ->whereDate('po_date', '<=', $tillDate);
                    if ($storeId) {
                        $q->where('store_id', $storeId);
                    }
                })
                ->sum('quantity');
            
            // Get total sales/issues till date from Selling Voucher (kitchen_issue)
            $totalIssuedKi = \DB::table('kitchen_issue_items as kii')
                ->join('kitchen_issue_master as kim', 'kii.kitchen_issue_master_pk', '=', 'kim.pk')
                ->where('kii.item_subcategory_id', $item->id)
                ->where('kim.kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
                ->where('kim.store_type', 'store')
                ->whereDate('kim.issue_date', '<=', $tillDate)
                ->when($storeId, function($q) use ($storeId) {
                    return $q->where('kim.store_id', $storeId);
                })
                ->selectRaw('SUM(kii.quantity - COALESCE(kii.return_quantity, 0)) as net')
                ->value('net') ?? 0;

            // Get total sales/issues from Selling Voucher with Date Range
            $totalIssuedSv = \DB::table('sv_date_range_report_items as svi')
                ->join('sv_date_range_reports as svr', 'svi.sv_date_range_report_id', '=', 'svr.id')
                ->where('svi.item_subcategory_id', $item->id)
                ->where('svr.store_type', 'store')
                ->whereDate('svr.issue_date', '<=', $tillDate)
                ->when($storeId, function($q) use ($storeId) {
                    return $q->where('svr.store_id', $storeId);
                })
                ->selectRaw('SUM(svi.quantity - COALESCE(svi.return_quantity, 0)) as net')
                ->value('net') ?? 0;

            $totalIssued = $totalIssuedKi + $totalIssuedSv;

            // Calculate remaining quantity
            $remainingQty = $totalPurchased - $totalIssued;
            
            // Get average rate from purchases
            $avgRate = PurchaseOrderItem::where('item_subcategory_id', $item->id)
                ->whereHas('purchaseOrder', function($q) use ($tillDate, $storeId) {
                    $q->where('status', 'approved')
                      ->whereDate('po_date', '<=', $tillDate);
                    if ($storeId) {
                        $q->where('store_id', $storeId);
                    }
                })
                ->avg('unit_price');
            
            // If no purchases, use standard cost
            $rate = $avgRate ?? $item->standard_cost ?? 0;
            
            // Calculate amount
            $amount = $remainingQty * $rate;
            
            // Only include items with remaining stock
            if ($remainingQty > 0) {
                $alertQty = null;
                if (Schema::hasColumn('mess_item_subcategories', 'alert_quantity')) {
                    $alertQty = $item->alert_quantity;
                }
                $reportData[] = [
                    'item_code' => $item->item_code ?? $item->subcategory_code ?? '—',
                    'item_name' => $item->item_name ?? $item->subcategory_name ?? $item->name,
                    'unit' => $item->unit_measurement ?? 'Unit',
                    'remaining_qty' => $remainingQty,
                    'remaining_quantity' => $remainingQty,
                    'alert_quantity' => $alertQty,
                    'rate' => $rate,
                    'amount' => $amount,
                ];
            }
        }
        
        // Get filter options
        $stores = Store::where('status', 'active')->get();
        
        // Get selected store name for heading
        $selectedStoreName = null;
        if ($storeId) {
            $selectedStore = Store::find($storeId);
            $selectedStoreName = $selectedStore ? $selectedStore->store_name : null;
        }
        
        return view('admin.mess.reports.stock-balance-till-date', compact(
            'reportData',
            'stores',
            'tillDate',
            'storeId',
            'selectedStoreName'
        ));
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
            $query->whereDate('issue_date', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $query->whereDate('issue_date', '<=', $request->to_date);
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
        $itemId = $request->filled('item_id') ? $request->item_id : null;

        $itemsQuery = ItemSubcategory::where('status', 'active')->with('category')->orderBy('name');
        if ($viewType === 'category_wise') {
            if ($categoryId) {
                $itemsQuery->where('category_id', $categoryId);
            } else {
                $itemsQuery->whereRaw('1 = 0');
            }
        }
        if ($itemId) {
            $itemsQuery->where('id', $itemId);
        }
        $items = $itemsQuery->get();

        $reportData = [];
        foreach ($items as $item) {
            $purchaseAgg = PurchaseOrderItem::where('item_subcategory_id', $item->id)
                ->whereHas('purchaseOrder', function ($q) use ($fromDate, $toDate) {
                    $q->where('status', 'approved')
                        ->whereDate('po_date', '>=', $fromDate)
                        ->whereDate('po_date', '<=', $toDate);
                })
                ->selectRaw('COALESCE(SUM(quantity), 0) as total_qty, COALESCE(SUM(quantity * unit_price), 0) as total_amount')
                ->first();
            $purchaseQty = (float) ($purchaseAgg->total_qty ?? 0);
            $purchaseAmount = (float) ($purchaseAgg->total_amount ?? 0);
            $avgPurchasePrice = $purchaseQty > 0 ? $purchaseAmount / $purchaseQty : null;

            $saleKi = \DB::table('kitchen_issue_items as kii')
                ->join('kitchen_issue_master as kim', 'kii.kitchen_issue_master_pk', '=', 'kim.pk')
                ->where('kii.item_subcategory_id', $item->id)
                ->where('kim.kitchen_issue_type', KitchenIssueMaster::TYPE_SELLING_VOUCHER)
                ->where('kim.store_type', 'store')
                ->whereDate('kim.issue_date', '>=', $fromDate)
                ->whereDate('kim.issue_date', '<=', $toDate)
                ->selectRaw('COALESCE(SUM(kii.quantity - COALESCE(kii.return_quantity, 0)), 0) as net_qty, COALESCE(SUM((kii.quantity - COALESCE(kii.return_quantity, 0)) * COALESCE(kii.rate, 0)), 0) as net_amount')
                ->first();
            $saleQtyKi = (float) ($saleKi->net_qty ?? 0);
            $saleAmountKi = (float) ($saleKi->net_amount ?? 0);

            $saleSv = \DB::table('sv_date_range_report_items as svi')
                ->join('sv_date_range_reports as svr', 'svi.sv_date_range_report_id', '=', 'svr.id')
                ->where('svi.item_subcategory_id', $item->id)
                ->where('svr.store_type', 'store')
                ->whereDate('svr.issue_date', '>=', $fromDate)
                ->whereDate('svr.issue_date', '<=', $toDate)
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

        return view('admin.mess.reports.purchase-sale-quantity', compact(
            'reportData',
            'groupedData',
            'fromDate',
            'toDate',
            'viewType',
            'categoryId',
            'itemId',
            'categories',
            'allItems'
        ));
    }
}
