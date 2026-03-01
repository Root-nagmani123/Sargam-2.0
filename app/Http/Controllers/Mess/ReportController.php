<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\Inventory;
use App\Models\Mess\Store;
use App\Models\Mess\ItemCategory;
use App\Models\Mess\PurchaseOrder;
use App\Models\Mess\InboundTransaction;
use App\Models\Mess\Invoice;
use App\Models\Mess\SaleCounter;
use App\Models\Mess\MonthlyBill;
use App\Models\KitchenIssueMaster;
use DB;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    public function itemsList(Request $request)
    {
        $items = Inventory::with(['category', 'subcategory'])
            ->when($request->category_id, function($q) use ($request) {
                return $q->where('category_id', $request->category_id);
            })
            ->when($request->search, function($q) use ($request) {
                return $q->where('item_name', 'like', '%'.$request->search.'%')
                         ->orWhere('item_code', 'like', '%'.$request->search.'%');
            })
            ->paginate(50);
            
        $categories = ItemCategory::where('status', 'active')->get();
        
        return view('admin.mess.reports.items-list', compact('items', 'categories'));
    }

    public function messSummary(Request $request)
    {
        $stores = Store::where('is_active', true)->get();
        
        $summary = [];
        foreach ($stores as $store) {
            $summary[] = [
                'store' => $store,
                'total_items' => Inventory::where('is_active', true)->count(),
                'low_stock_items' => Inventory::whereRaw('current_stock < minimum_stock')->count(),
                'total_stock_value' => Inventory::sum(DB::raw('current_stock * unit_price'))
            ];
        }
        
        return view('admin.mess.reports.mess-summary', compact('summary', 'stores'));
    }

    public function categoryMaterial(Request $request)
    {
        $categories = ItemCategory::where('status', 'active')->with(['items' => function($q) {
            $q->where('is_active', true);
        }])->get();
        
        $categoryData = [];
        foreach ($categories as $category) {
            $categoryData[] = [
                'category' => $category,
                'total_items' => $category->items->count(),
                'total_stock' => $category->items->sum('current_stock'),
                'total_value' => $category->items->sum(function($item) {
                    return $item->current_stock * $item->unit_price;
                })
            ];
        }
        
        return view('admin.mess.reports.category-material', compact('categoryData'));
    }

    public function pendingOrders(Request $request)
    {
        $orders = PurchaseOrder::with(['vendor', 'store'])
            ->where('status', 'pending')
            ->orWhere('status', 'approved')
            ->orderBy('po_date', 'desc')
            ->paginate(50);
            
        return view('admin.mess.reports.pending-orders', compact('orders'));
    }

    public function paymentOverdue(Request $request)
    {
        $overdueInvoices = Invoice::with(['vendor', 'store'])
            ->where('payment_status', '!=', 'paid')
            ->whereDate('due_date', '<', now())
            ->orderBy('due_date', 'asc')
            ->paginate(50);
            
        return view('admin.mess.reports.payment-overdue', compact('overdueInvoices'));
    }

    public function approvedInbound(Request $request)
    {
        $transactions = InboundTransaction::with(['vendor', 'store', 'receivedBy'])
            ->where('status', 'approved')
            ->when($request->from_date, function($q) use ($request) {
                return $q->whereDate('receipt_date', '>=', $request->from_date);
            })
            ->when($request->to_date, function($q) use ($request) {
                return $q->whereDate('receipt_date', '<=', $request->to_date);
            })
            ->orderBy('receipt_date', 'desc')
            ->paginate(50);
            
        return view('admin.mess.reports.approved-inbound', compact('transactions'));
    }

    public function invoiceBill(Request $request)
    {
        $invoices = Invoice::with(['vendor', 'store'])
            ->when($request->status, function($q) use ($request) {
                return $q->where('payment_status', $request->status);
            })
            ->orderBy('invoice_date', 'desc')
            ->paginate(50);
            
        return view('admin.mess.reports.invoice-bill', compact('invoices'));
    }

    public function purchaseOrdersReport(Request $request)
    {
        $orders = PurchaseOrder::with(['vendor', 'store', 'items.inventory'])
            ->when($request->status, function($q) use ($request) {
                return $q->where('status', $request->status);
            })
            ->when($request->from_date, function($q) use ($request) {
                return $q->whereDate('po_date', '>=', $request->from_date);
            })
            ->when($request->to_date, function($q) use ($request) {
                return $q->whereDate('po_date', '<=', $request->to_date);
            })
            ->orderBy('po_date', 'desc')
            ->paginate(50);
            
        return view('admin.mess.reports.purchase-orders', compact('orders'));
    }

    public function otNotTakingFood(Request $request)
    {
        // This would require integration with OT attendance system
        return view('admin.mess.reports.ot-not-taking-food');
    }

    public function saleCounterReport(Request $request)
    {
        $counters = SaleCounter::with(['store', 'transactions'])
            ->when($request->from_date, function($q) use ($request) {
                return $q->whereHas('transactions', function($q2) use ($request) {
                    $q2->whereDate('transaction_date', '>=', $request->from_date);
                });
            })
            ->get();
            
        return view('admin.mess.reports.sale-counter', compact('counters'));
    }

    public function storeDue(Request $request)
    {
        $stores = Store::with(['invoices' => function($q) {
            $q->where('payment_status', '!=', 'paid');
        }])->get();
        
        return view('admin.mess.reports.store-due', compact('stores'));
    }

    public function messBillReport(Request $request)
    {
        $bills = MonthlyBill::with(['user'])
            ->when($request->month, function($q) use ($request) {
                return $q->where('month', $request->month);
            })
            ->when($request->year, function($q) use ($request) {
                return $q->where('year', $request->year);
            })
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate(50);
            
        return view('admin.mess.reports.mess-bill', compact('bills'));
    }

    public function messInvoiceReport(Request $request)
    {
        $invoices = Invoice::with(['vendor', 'store'])
            ->orderBy('invoice_date', 'desc')
            ->paginate(50);
            
        return view('admin.mess.reports.mess-invoice', compact('invoices'));
    }

    public function stockPurchaseDetails(Request $request)
    {
        $items = Inventory::with(['category', 'subcategory'])
            ->when($request->from_date, function($q) use ($request) {
                // Filter by purchase date range
            })
            ->get();
            
        return view('admin.mess.reports.stock-purchase-details', compact('items'));
    }

    public function clientInvoice(Request $request)
    {
        // This would show invoices for students/employees
        return view('admin.mess.reports.client-invoice');
    }

    public function stockIssueDetail(Request $request)
    {
        $issues = KitchenIssueMaster::with(['inventory', 'store', 'user'])
            ->when($request->from_date, function($q) use ($request) {
                return $q->whereDate('issue_date', '>=', $request->from_date);
            })
            ->when($request->to_date, function($q) use ($request) {
                return $q->whereDate('issue_date', '<=', $request->to_date);
            })
            ->orderBy('issue_date', 'desc')
            ->paginate(50);
            
        return view('admin.mess.reports.stock-issue-detail', compact('issues'));
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
