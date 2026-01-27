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
            
        $categories = ItemCategory::where('is_active', true)->get();
        
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
        $categories = ItemCategory::with(['items' => function($q) {
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
}
