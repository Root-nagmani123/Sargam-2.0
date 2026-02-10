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
use App\Models\Mess\Vendor;
use DB;

class ReportController extends Controller
{
    /**
     * Stock Purchase Details Report
     * Shows detailed purchase information for all items
     */
    public function stockPurchaseDetails(Request $request)
    {
        $query = PurchaseOrderItem::with([
            'purchaseOrder.vendor',
            'purchaseOrder.store',
            'inventory'
        ]);
        
        // Apply filters
        if ($request->filled('from_date')) {
            $query->whereHas('purchaseOrder', function($q) use ($request) {
                $q->whereDate('po_date', '>=', $request->from_date);
            });
        }
        
        if ($request->filled('to_date')) {
            $query->whereHas('purchaseOrder', function($q) use ($request) {
                $q->whereDate('po_date', '<=', $request->to_date);
            });
        }
        
        if ($request->filled('store_id')) {
            $query->whereHas('purchaseOrder', function($q) use ($request) {
                $q->where('store_id', $request->store_id);
            });
        }
        
        if ($request->filled('vendor_id')) {
            $query->whereHas('purchaseOrder', function($q) use ($request) {
                $q->where('vendor_id', $request->vendor_id);
            });
        }
        
        $purchaseItems = $query->orderBy('created_at', 'desc')->get();
        
        // Get filter options
        $stores = Store::where('is_active', true)->get();
        $vendors = Vendor::where('status', 'active')->get();
        
        // Get selected vendor name for heading
        $selectedVendor = null;
        if ($request->filled('vendor_id')) {
            $selectedVendor = Vendor::find($request->vendor_id);
        }
        
        return view('admin.mess.reports.stock-purchase-details', compact(
            'purchaseItems',
            'stores',
            'vendors',
            'selectedVendor'
        ));
    }

    /**
     * Stock Summary Report
     * Shows summary of all items with current stock levels
     */
    public function stockSummary(Request $request)
    {
        $query = Inventory::with(['category', 'subcategory', 'store']);
        
        // Apply filters
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }
        
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->subcategory_id);
        }
        
        if ($request->filled('stock_status')) {
            if ($request->stock_status == 'low') {
                $query->whereRaw('current_stock < minimum_stock');
            } elseif ($request->stock_status == 'out') {
                $query->where('current_stock', '<=', 0);
            }
        }
        
        $items = $query->where('is_active', true)
                      ->orderBy('item_name')
                      ->get();
        
        // Calculate totals
        $totalValue = $items->sum(function($item) {
            return $item->current_stock * $item->unit_price;
        });
        
        $lowStockCount = $items->filter(function($item) {
            return $item->current_stock < $item->minimum_stock;
        })->count();
        
        $outOfStockCount = $items->filter(function($item) {
            return $item->current_stock <= 0;
        })->count();
        
        // Get filter options
        $stores = Store::where('is_active', true)->get();
        $categories = ItemCategory::where('status', 'active')->get();
        $subcategories = ItemSubcategory::where('status', 'active')->get();
        
        return view('admin.mess.reports.stock-summary', compact(
            'items',
            'totalValue',
            'lowStockCount',
            'outOfStockCount',
            'stores',
            'categories',
            'subcategories'
        ));
    }

    /**
     * Category-wise Print Slip
     * Shows items grouped by category for printing
     */
    public function categoryWisePrintSlip(Request $request)
    {
        $query = ItemCategory::where('status', 'active')
                            ->with(['items' => function($q) use ($request) {
                                $q->where('is_active', true);
                                
                                if ($request->filled('store_id')) {
                                    $q->where('store_id', $request->store_id);
                                }
                                
                                if ($request->filled('show_stock')) {
                                    if ($request->show_stock == 'available') {
                                        $q->where('current_stock', '>', 0);
                                    } elseif ($request->show_stock == 'low') {
                                        $q->whereRaw('current_stock < minimum_stock');
                                    }
                                }
                                
                                $q->orderBy('item_name');
                            }, 'items.subcategory']);
        
        if ($request->filled('category_id')) {
            $query->where('id', $request->category_id);
        }
        
        $categories = $query->orderBy('category_name')->get();
        
        // Get filter options
        $stores = Store::where('is_active', true)->get();
        $allCategories = ItemCategory::where('status', 'active')->get();
        
        return view('admin.mess.reports.category-wise-print-slip', compact(
            'categories',
            'stores',
            'allCategories'
        ));
    }

    /**
     * Stock Balance as of Till Date
     * Shows stock balance up to a specific date
     */
    public function stockBalanceTillDate(Request $request)
    {
        $tillDate = $request->filled('till_date') 
                    ? $request->till_date 
                    : now()->format('Y-m-d');
        
        $query = Inventory::with(['category', 'subcategory', 'store']);
        
        // Apply filters
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }
        
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->subcategory_id);
        }
        
        $items = $query->where('is_active', true)
                      ->whereDate('created_at', '<=', $tillDate)
                      ->orderBy('item_name')
                      ->get();
        
        // Calculate opening balance, purchases, issues, and closing balance
        $itemsWithBalance = $items->map(function($item) use ($tillDate) {
            // Get purchase orders till date
            $totalPurchased = PurchaseOrderItem::where('inventory_id', $item->id)
                ->whereHas('purchaseOrder', function($q) use ($tillDate) {
                    $q->where('status', 'approved')
                      ->whereDate('po_date', '<=', $tillDate);
                })
                ->sum('quantity');
            
            // Get issues till date
            $totalIssued = \DB::table('mess_kitchen_issue_details')
                ->where('inventory_id', $item->id)
                ->whereDate('created_at', '<=', $tillDate)
                ->sum('quantity');
            
            $item->total_purchased = $totalPurchased;
            $item->total_issued = $totalIssued;
            $item->closing_balance = $item->current_stock;
            $item->opening_balance = $item->closing_balance - $totalPurchased + $totalIssued;
            
            return $item;
        });
        
        // Calculate totals
        $totalClosingValue = $itemsWithBalance->sum(function($item) {
            return $item->closing_balance * $item->unit_price;
        });
        
        // Get filter options
        $stores = Store::where('is_active', true)->get();
        $categories = ItemCategory::where('status', 'active')->get();
        $subcategories = ItemSubcategory::where('status', 'active')->get();
        
        return view('admin.mess.reports.stock-balance-till-date', compact(
            'itemsWithBalance',
            'totalClosingValue',
            'tillDate',
            'stores',
            'categories',
            'subcategories'
        ));
    }
}
