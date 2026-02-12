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
            'itemSubcategory'
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
        $stores = Store::where('status', 'active')->get();
        $vendors = Vendor::all();
        
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
     * Shows summary of all items with opening, purchase, sale, and closing stock
     */
    public function stockSummary(Request $request)
    {
        $fromDate = $request->filled('from_date') ? $request->from_date : now()->format('Y-m-d');
        $toDate = $request->filled('to_date') ? $request->to_date : now()->format('Y-m-d');
        $storeId = $request->filled('store_id') ? $request->store_id : null;
        $storeType = $request->filled('store_type') ? $request->store_type : 'main'; // main or sub
        
        // Get all items (subcategories)
        $itemsQuery = ItemSubcategory::where('status', 'active')
            ->orderBy('name');
        
        $items = $itemsQuery->get();
        
        // Get previous day for opening stock calculation
        $previousDate = date('Y-m-d', strtotime($fromDate . ' -1 day'));
        
        // Build report data
        $reportData = [];
        
        foreach ($items as $item) {
            // Initialize data structure
            $itemData = [
                'item_name' => $item->item_name ?? $item->subcategory_name ?? $item->name,
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
            
            // Calculate Opening Stock (previous day's closing)
            if ($storeType == 'main') {
                // For main store: Get from purchase orders till previous day
                $previousPurchase = PurchaseOrderItem::where('item_subcategory_id', $item->id)
                    ->whereHas('purchaseOrder', function($q) use ($previousDate, $storeId) {
                        $q->where('status', 'approved')
                          ->whereDate('po_date', '<=', $previousDate);
                        if ($storeId) {
                            $q->where('store_id', $storeId);
                        }
                    })
                    ->sum('quantity');
                    
                // Get sales till previous day
                $previousSale = \DB::table('kitchen_issue_items as kii')
                    ->join('kitchen_issue_master as kim', 'kii.kitchen_issue_master_pk', '=', 'kim.pk')
                    ->where('kii.item_subcategory_id', $item->id)
                    ->whereDate('kim.issue_date', '<=', $previousDate)
                    ->when($storeId, function($q) use ($storeId) {
                        return $q->where('kim.store_id', $storeId);
                    })
                    ->sum('kii.quantity');
                    
                $itemData['opening_qty'] = $previousPurchase - $previousSale;
            } else {
                // For sub store: Get from store allocations till previous day
                $previousAllocation = \DB::table('mess_store_allocation_items as sai')
                    ->join('mess_store_allocations as sa', 'sai.store_allocation_id', '=', 'sa.id')
                    ->where('sai.item_subcategory_id', $item->id)
                    ->whereDate('sa.allocation_date', '<=', $previousDate)
                    ->when($storeId, function($q) use ($storeId) {
                        return $q->where('sa.sub_store_id', $storeId);
                    })
                    ->sum('sai.quantity');
                    
                // Get sales till previous day from sub store
                $previousSale = \DB::table('kitchen_issue_items as kii')
                    ->join('kitchen_issue_master as kim', 'kii.kitchen_issue_master_pk', '=', 'kim.pk')
                    ->where('kii.item_subcategory_id', $item->id)
                    ->whereDate('kim.issue_date', '<=', $previousDate)
                    ->when($storeId, function($q) use ($storeId) {
                        return $q->where('kim.store_id', $storeId);
                    })
                    ->sum('kii.quantity');
                    
                $itemData['opening_qty'] = $previousAllocation - $previousSale;
            }
            
            $itemData['opening_amount'] = $itemData['opening_qty'] * $itemData['opening_rate'];
            
            // Calculate Purchase for the date range
            if ($storeType == 'main') {
                // For main store: Get from purchase orders
                $purchases = PurchaseOrderItem::where('item_subcategory_id', $item->id)
                    ->whereHas('purchaseOrder', function($q) use ($fromDate, $toDate, $storeId) {
                        $q->where('status', 'approved')
                          ->whereBetween('po_date', [$fromDate, $toDate]);
                        if ($storeId) {
                            $q->where('store_id', $storeId);
                        }
                    })
                    ->selectRaw('SUM(quantity) as total_qty, AVG(unit_price) as avg_rate')
                    ->first();
                    
                $itemData['purchase_qty'] = $purchases->total_qty ?? 0;
                $itemData['purchase_rate'] = $purchases->avg_rate ?? $itemData['purchase_rate'];
            } else {
                // For sub store: Get from store allocations
                $allocations = \DB::table('mess_store_allocation_items as sai')
                    ->join('mess_store_allocations as sa', 'sai.store_allocation_id', '=', 'sa.id')
                    ->where('sai.item_subcategory_id', $item->id)
                    ->whereBetween('sa.allocation_date', [$fromDate, $toDate])
                    ->when($storeId, function($q) use ($storeId) {
                        return $q->where('sa.sub_store_id', $storeId);
                    })
                    ->selectRaw('SUM(sai.quantity) as total_qty, AVG(sai.unit_price) as avg_rate')
                    ->first();
                    
                $itemData['purchase_qty'] = $allocations->total_qty ?? 0;
                $itemData['purchase_rate'] = $allocations->avg_rate ?? $itemData['purchase_rate'];
            }
            
            $itemData['purchase_amount'] = $itemData['purchase_qty'] * $itemData['purchase_rate'];
            
            // Calculate Sale for the date range
            $sales = \DB::table('kitchen_issue_items as kii')
                ->join('kitchen_issue_master as kim', 'kii.kitchen_issue_master_pk', '=', 'kim.pk')
                ->where('kii.item_subcategory_id', $item->id)
                ->whereBetween('kim.issue_date', [$fromDate, $toDate])
                ->when($storeId, function($q) use ($storeId) {
                    return $q->where('kim.store_id', $storeId);
                })
                ->selectRaw('SUM(kii.quantity) as total_qty, AVG(kii.rate) as avg_rate')
                ->first();
                
            $itemData['sale_qty'] = $sales->total_qty ?? 0;
            $itemData['sale_rate'] = $sales->avg_rate ?? $itemData['sale_rate'];
            $itemData['sale_amount'] = $itemData['sale_qty'] * $itemData['sale_rate'];
            
            // Calculate Closing Stock
            $itemData['closing_qty'] = $itemData['opening_qty'] + $itemData['purchase_qty'] - $itemData['sale_qty'];
            $itemData['closing_amount'] = $itemData['closing_qty'] * $itemData['closing_rate'];
            
            // Only include items with activity
            if ($itemData['opening_qty'] != 0 || $itemData['purchase_qty'] != 0 || $itemData['sale_qty'] != 0) {
                $reportData[] = $itemData;
            }
        }
        
        // Get filter options
        $stores = Store::where('status', 'active')->get();
        $subStores = SubStore::where('status', 'active')->get();
        
        // Get selected store name for heading
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
     * Category-wise Print Slip
     * Shows selling voucher details grouped by category
     */
    public function categoryWisePrintSlip(Request $request)
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
        
        // Only show approved vouchers
        $query->where('status', \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_APPROVED);
        
        $vouchers = $query->orderBy('issue_date', 'desc')->get();
        
        // Get filter options
        $clientTypes = \App\Models\Mess\ClientType::clientTypes();
        $clientTypeCategories = \App\Models\Mess\ClientType::active()
            ->orderBy('client_type')
            ->orderBy('client_name')
            ->get()
            ->groupBy('client_type');
        
        return view('admin.mess.reports.category-wise-print-slip', compact(
            'vouchers',
            'clientTypes',
            'clientTypeCategories'
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
            
            // Get total sales/issues till date
            $totalIssued = \DB::table('kitchen_issue_items as kii')
                ->join('kitchen_issue_master as kim', 'kii.kitchen_issue_master_pk', '=', 'kim.pk')
                ->where('kii.item_subcategory_id', $item->id)
                ->whereDate('kim.issue_date', '<=', $tillDate)
                ->when($storeId, function($q) use ($storeId) {
                    return $q->where('kim.store_id', $storeId);
                })
                ->sum('kii.quantity');
            
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
                $reportData[] = [
                    'item_name' => $item->item_name ?? $item->subcategory_name ?? $item->name,
                    'unit' => $item->unit_measurement ?? 'Unit',
                    'remaining_qty' => $remainingQty,
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
        
        // Only show approved vouchers
        $query->where('status', \App\Models\Mess\SellingVoucherDateRangeReport::STATUS_APPROVED);
        
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
}
