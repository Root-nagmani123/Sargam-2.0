<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Support\DataTableRedisCache;
use App\Support\DataTableSearchHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Mess\PurchaseOrder;
use App\Models\Mess\PurchaseOrderItem;
use App\Models\Mess\Vendor;
use App\Models\Mess\Store;
use App\Models\Mess\ItemSubcategory;
use App\Services\Mess\AvailableQuantityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    private const PURCHASE_ORDER_DT_LIST_EPOCH = 'purchase_order_dt_list_epoch';

    /**
     * Invalidate Redis-backed Purchase Order DataTables JSON and the available-quantity
     * cache after mutations that change mess_purchase_order_items (create/update/delete/approve/reject).
     */
    public static function bumpPurchaseOrderListingCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::PURCHASE_ORDER_DT_LIST_EPOCH, 'PurchaseOrderController@index');
        AvailableQuantityService::bumpCacheEpoch();
    }

    public function index(Request $request)
    {
        if ($request->ajax() && $request->has('draw')) {
            return DataTableRedisCache::serveCachedAjax(
                $request,
                'purchase_order_dt:v1:',
                self::PURCHASE_ORDER_DT_LIST_EPOCH,
                [
                    'enabled' => 'PURCHASE_ORDER_DATATABLE_CACHE_ENABLED',
                    'seconds' => 'PURCHASE_ORDER_DATATABLE_CACHE_SECONDS',
                ],
                'PurchaseOrderController@index',
                fn () => $this->buildPurchaseOrderDatatableResponse($request),
                $this->purchaseOrderDatatableFilterFingerprint($request)
            );
        }

        $vendorIds = $this->normalizeFilterIdList($request->input('vendor_id'));
        $storeIds = $this->normalizeFilterIdList($request->input('store_id'));

        $vendors = Vendor::orderBy('name')->get(['id', 'name']);
        $stores = Store::where('status', 1)->orderBy('store_name')->get(['id', 'store_name']);
        $itemSubcategories = ItemSubcategory::active()->orderBy('name')->get(ItemSubcategory::listSelectColumns())
            ->map(fn ($s) => [
                'id' => $s->id,
                'item_name' => $s->item_name ?? $s->name ?? '—',
                'item_code' => $s->item_code ?? '—',
                'unit_measurement' => $s->unit_measurement ?? '—',
            ]);
        $po_number = $this->generatePoNumber();
        $paymentModes = ['Cash' => 'Cash', 'Card' => 'Card', 'UPI' => 'UPI', 'Bank Transfer' => 'Bank Transfer', 'Credit' => 'Credit'];

        $filterDateFrom = $request->get('date_from', '');
        $filterDateTo = $request->get('date_to', '');
        $filterVendorIds = $vendorIds;
        $filterStoreIds = $storeIds;
        $filterStatus = $this->purchaseOrderStatusFilter($request);
        $canApprovePurchaseOrders = $this->canApprovePurchaseOrders();
        $pendingApprovalCount = $canApprovePurchaseOrders
            ? PurchaseOrder::where('status', 'pending')->count()
            : 0;

        return view('mess.purchaseorders.index', compact(
            'vendors', 'stores', 'itemSubcategories', 'po_number', 'paymentModes',
            'filterDateFrom', 'filterDateTo', 'filterVendorIds', 'filterStoreIds',
            'filterStatus', 'pendingApprovalCount', 'canApprovePurchaseOrders'
        ));
    }

    /**
     * A new PO is pending: it is not in stock, so nothing on it can be sold yet. Mess Admin or
     * Super Admin verifies it and approves it (stock, sale allowed, purchase details frozen) or
     * rejects it.
     */
    private function canApprovePurchaseOrders(): bool
    {
        return function_exists('hasRole') && (hasRole('Mess Admin') || hasRole('Super Admin'));
    }

    private function purchaseOrderStatusFilter(Request $request): string
    {
        $status = (string) $request->input('status', '');

        return in_array($status, ['pending', 'approved', 'rejected'], true) ? $status : '';
    }

    /**
     * @return array<string, mixed>
     */
    private function purchaseOrderDatatableFilterFingerprint(Request $request): array
    {
        return [
            'vendor_id' => $this->normalizeFilterIdList($request->input('vendor_id')),
            'store_id' => $this->normalizeFilterIdList($request->input('store_id')),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'status' => $this->purchaseOrderStatusFilter($request),
            'for_print' => $request->boolean('for_print'),
            'can_delete' => function_exists('hasRole') && (hasRole('Admin') || hasRole('Mess-Admin')),
            'can_approve' => $this->canApprovePurchaseOrders(),
        ];
    }

    private function purchaseOrderFilteredQuery(Request $request, array $vendorIds, array $storeIds): Builder
    {
        $query = PurchaseOrder::query();

        if ($request->filled('date_from')) {
            $query->where('po_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('po_date', '<=', $request->date_to);
        }
        if ($vendorIds !== []) {
            $query->whereIn('vendor_id', $vendorIds);
        }
        if ($storeIds !== []) {
            $query->whereIn('store_id', $storeIds);
        }
        $status = $this->purchaseOrderStatusFilter($request);
        if ($status !== '') {
            $query->where('status', $status);
        }

        return $query;
    }

    private function buildPurchaseOrderDatatableResponse(Request $request): JsonResponse
    {
        $vendorIds = $this->normalizeFilterIdList($request->input('vendor_id'));
        $storeIds = $this->normalizeFilterIdList($request->input('store_id'));
        $query = $this->purchaseOrderFilteredQuery($request, $vendorIds, $storeIds);

        $draw = (int) $request->input('draw', 0);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        $searchTokens = DataTableSearchHelper::tokens((string) $request->input('search.value', ''));

        $recordsTotal = (clone $query)->count();

        if ($searchTokens !== []) {
            $query->where(function ($q) use ($searchTokens) {
                foreach ($searchTokens as $token) {
                    $like = DataTableSearchHelper::likePattern($token);
                    $q->where(function ($inner) use ($like) {
                        $inner->where('po_number', 'like', $like)
                            ->orWhere('status', 'like', $like)
                            ->orWhereHas('vendor', function ($v) use ($like) {
                                $v->where('name', 'like', $like);
                            })
                            ->orWhereHas('store', function ($s) use ($like) {
                                $s->where('store_name', 'like', $like);
                            });
                    });
                }
            });
        }

        $recordsFiltered = (clone $query)->count();

        $paged = (clone $query)->with(['vendor', 'store']);
        $table = (new PurchaseOrder())->getTable();
        $orderCol = DataTableSearchHelper::orderColumnIndex($request, 1);
        $orderDir = DataTableSearchHelper::orderDirection($request, 'desc');

        switch ($orderCol) {
            case 0:
                $paged->orderBy($table . '.po_date', $orderDir);
                break;
            case 1:
                $paged->orderBy($table . '.po_number', $orderDir);
                break;
            case 2:
                $paged->leftJoin('mess_vendors as po_sort_v', $table . '.vendor_id', '=', 'po_sort_v.id')
                    ->orderBy('po_sort_v.name', $orderDir)
                    ->select($table . '.*');
                break;
            case 3:
                $paged->leftJoin('mess_stores as po_sort_s', $table . '.store_id', '=', 'po_sort_s.id')
                    ->orderBy('po_sort_s.store_name', $orderDir)
                    ->select($table . '.*');
                break;
            case 4:
                $paged->orderBy($table . '.status', $orderDir);
                break;
            default:
                $paged->orderByDesc($table . '.po_date');
        }
        $paged->orderByDesc($table . '.id');

        if ($length !== -1) {
            $paged->skip($start)->take(max($length, 0));
        }

        $purchaseOrders = $paged->get();
        $forPrint = $request->boolean('for_print');
        $canDeletePurchaseOrder = function_exists('hasRole') && (hasRole('Admin') || hasRole('Mess-Admin'));
        $canApprove = $this->canApprovePurchaseOrders();
        $rowStart = $start + 1;

        $data = $purchaseOrders->map(function ($po, $index) use ($canDeletePurchaseOrder, $canApprove, $rowStart, $forPrint) {
            $statusBadgeClass = $po->status === 'approved'
                ? 'text-bg-success'
                : ($po->status === 'rejected' ? 'text-bg-danger' : ($po->status === 'completed' ? 'text-bg-primary' : 'text-bg-warning'));

            $row = [
                '<span class="ps-4 d-inline-block text-body-secondary fw-medium">' . ($rowStart + $index) . '</span>',
                '<span class="fw-semibold text-body">' . e($po->po_number) . '</span>',
                '<span class="text-body-secondary">' . e(optional($po->vendor)->name ?? 'N/A') . '</span>',
                '<span class="text-body-secondary">' . e(optional($po->store)->store_name ?? 'N/A') . '</span>',
                '<span class="badge rounded-1 ' . $statusBadgeClass . ' px-3 py-1 fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.02em;">' . e(ucfirst($po->status)) . '</span>',
            ];

            if (! $forPrint) {
                $viewBtn = '<button type="button" class="btn btn-sm btn-outline-primary btn-view-po rounded-2 po-action-btn" data-po-id="' . $po->id . '" title="View">'
                    . '<i class="material-icons material-symbol-rounded align-middle" style="font-size: 1rem;">visibility</i>'
                    . '</button>';
                $editBtn = $po->status === 'rejected' ? '' : '<button type="button" class="btn btn-sm btn-outline-info btn-edit-po rounded-2 po-action-btn" data-po-id="' . $po->id . '" title="' . ($po->status === 'approved' ? 'Edit bill details' : 'Edit') . '">'
                    . '<i class="material-icons material-symbol-rounded align-middle" style="font-size: 1rem;">edit</i>'
                    . '</button>';
                $deleteForm = '';
                $approvalForms = '';

                if ($canApprove && $po->status === 'pending') {
                    $csrf = csrf_token();
                    $poNumber = e(addslashes($po->po_number));
                    $approvalForms = '<form action="' . e(route('admin.mess.purchaseorders.approve', $po->id)) . '" method="POST" class="d-inline" onsubmit="return confirm(\'Approve purchase order ' . $poNumber . '? Its items go into stock and can be sold, and its quantities and rates are locked.\');">'
                        . '<input type="hidden" name="_token" value="' . e($csrf) . '">'
                        . '<button type="submit" class="btn btn-sm btn-outline-success rounded-2 po-action-btn" title="Approve">'
                        . '<i class="material-icons material-symbol-rounded align-middle" style="font-size: 1rem;">check_circle</i>'
                        . '</button>'
                        . '</form>'
                        . '<form action="' . e(route('admin.mess.purchaseorders.reject', $po->id)) . '" method="POST" class="d-inline" onsubmit="return confirm(\'Reject purchase order ' . $poNumber . '? Its items will not go into stock.\');">'
                        . '<input type="hidden" name="_token" value="' . e($csrf) . '">'
                        . '<button type="submit" class="btn btn-sm btn-outline-danger rounded-2 po-action-btn" title="Reject">'
                        . '<i class="material-icons material-symbol-rounded align-middle" style="font-size: 1rem;">cancel</i>'
                        . '</button>'
                        . '</form>';
                }

                // An approved PO is in stock and may already be sold, so it cannot be deleted.
                if ($canDeletePurchaseOrder && $po->status !== 'approved') {
                    $deleteUrl = route('admin.mess.purchaseorders.destroy', $po->id);
                    $csrf = csrf_token();
                    $deleteForm = '<form action="' . e($deleteUrl) . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this purchase order?\');">'
                        . '<input type="hidden" name="_token" value="' . e($csrf) . '">'
                        . '<input type="hidden" name="_method" value="DELETE">'
                        . '<button type="submit" class="btn btn-sm btn-outline-danger rounded-2 po-action-btn" title="Delete">'
                        . '<i class="material-icons material-symbol-rounded align-middle" style="font-size: 1rem;">delete</i>'
                        . '</button>'
                        . '</form>';
                }

                $row[] = '<div class="po-actions-cell d-inline-flex align-items-center justify-content-end gap-1">' . $viewBtn . $editBtn . $approvalForms . $deleteForm . '</div>';
            }

            return $row;
        })->values()->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function create(Request $request)
    {
        $vendors = Vendor::orderBy('name')->get(['id', 'name']);
        $stores = Store::where('status', 1)->orderBy('store_name')->get(['id', 'store_name']);
        $itemSubcategories = ItemSubcategory::active()
            ->orderBy('name')
            ->get(ItemSubcategory::listSelectColumns())
            ->map(fn ($s) => (object) [
                'id' => $s->id,
                'item_name' => $s->item_name ?? $s->name ?? '—',
            ]);
        $po_number = $this->generatePoNumber();

        return view('mess.purchaseorders.create', compact('vendors', 'stores', 'itemSubcategories', 'po_number'));
    }

    public function store(Request $request)
    {
        try {
            $this->normalizePurchaseOrderItemsInRequest($request);
            $request->validate([
                'po_number' => 'required|unique:mess_purchase_orders,po_number',
                'vendor_id' => 'required|exists:mess_vendors,id',
                // Stock is counted per store; a PO without one is in nobody's stock.
                'store_id' => 'required|exists:mess_stores,id',
                'po_date' => 'required|date|before_or_equal:today',
                'delivery_date' => 'nullable|date',
                'payment_code' => 'nullable|string|max:50',
                'delivery_address' => 'nullable|string|max:500',
                'contact_number' => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
                'bill_no' => 'nullable|string|max:100',
                'challan_no' => 'nullable|string|max:100',
                'bill_date' => 'nullable|date|before_or_equal:today',
                'challan_date' => 'nullable|date|before_or_equal:today',
                'items' => 'required|array|min:1',
                'items.*.item_subcategory_id' => 'required|exists:mess_item_subcategories,id',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.tax_percent' => 'nullable|numeric|min:0|max:100',
                'bill_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
            ], [
                'store_id.required' => 'Please select a store.',
                'contact_number.regex' => 'The contact number must be exactly 10 digits and contain only numbers (no letters or special characters).',
                'bill_file.mimes' => 'Bill must be PDF or image (jpg, jpeg, png, webp).',
                'bill_file.max' => 'Bill size must not exceed 5 MB.',
            ]);

            $purchaseOrderId = null;
            DB::transaction(function () use ($request, &$purchaseOrderId) {
                $grandTotal = 0;
                foreach ($request->items as $item) {
                    $qty = (float) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];
                    $taxPercent = isset($item['tax_percent']) ? (float) $item['tax_percent'] : 0;
                    $lineTotal = $qty * $unitPrice * (1 + $taxPercent / 100);
                    $grandTotal += $lineTotal;
                }

                $purchaseOrder = PurchaseOrder::create([
                    'po_number' => $request->po_number,
                    'vendor_id' => $request->vendor_id,
                    'store_id' => $request->store_id ?: null,
                    'po_date' => $request->po_date,
                    'delivery_date' => $request->delivery_date ?? null,
                    'total_amount' => round($grandTotal, 2),
                    'payment_code' => $request->payment_code,
                    'delivery_address' => $request->delivery_address,
                    'contact_number' => $request->contact_number,
                    'bill_no' => $request->bill_no,
                    'challan_no' => $request->challan_no,
                    'bill_date' => $request->bill_date,
                    'challan_date' => $request->challan_date,
                    'remarks' => $request->remarks,
                    'created_by' => Auth::id(),
                    // Not in stock until Mess Admin / Super Admin approves it.
                    'status' => 'pending',
                ]);
                $purchaseOrderId = $purchaseOrder->id;

                if ($request->hasFile('bill_file')) {
                    $file = $request->file('bill_file');
                    $path = $file->store('mess/purchase-orders/bills', 'public');
                    $purchaseOrder->update(['bill_path' => $path]);
                }

                $subcategories = ItemSubcategory::whereIn('id', collect($request->items)->map(
                    fn ($item) => $this->coerceItemSubcategoryId($item['item_subcategory_id'] ?? null)
                )->filter())->get()->keyBy('id');

                foreach ($request->items as $item) {
                    $qty = (float) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];
                    $taxPercent = isset($item['tax_percent']) ? (float) $item['tax_percent'] : 0;
                    $lineTotal = round($qty * $unitPrice * (1 + $taxPercent / 100), 2);
                    $itemSubcategoryId = $this->coerceItemSubcategoryId($item['item_subcategory_id'] ?? null);
                    $sub = $itemSubcategoryId ? $subcategories->get($itemSubcategoryId) : null;
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'inventory_id' => null,
                        'item_subcategory_id' => $itemSubcategoryId,
                        'quantity' => $qty,
                        'unit' => $item['unit'] ?? ($sub ? ($sub->unit_measurement ?? null) : null),
                        'unit_price' => $unitPrice,
                        'tax_percent' => $taxPercent,
                        'total_price' => $lineTotal,
                        'description' => $item['description'] ?? null,
                    ]);
                }
            });
            self::bumpPurchaseOrderListingCacheEpoch();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Purchase order created. Its items can be sold once Mess Admin approves it.',
                    'purchase_order_id' => $purchaseOrderId,
                ]);
            }

            return redirect()->route('admin.mess.purchaseorders.index')
                ->with('success', 'Purchase order created. Its items can be sold once Mess Admin approves it.')
                ->with('open_create_po_modal', true);
        } catch (ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }
            return redirect()->route('admin.mess.purchaseorders.index')
                ->withErrors($e->errors())
                ->withInput()
                ->with('open_create_po_modal', true);
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create purchase order: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->route('admin.mess.purchaseorders.index')
                ->withInput()
                ->with('error', 'Failed to create purchase order: ' . $e->getMessage())
                ->with('open_create_po_modal', true);
        }
    }

    public function show($id)
    {
        $purchaseOrder = PurchaseOrder::with(['vendor', 'store', 'creator', 'approver', 'items.itemSubcategory'])->findOrFail($id);
        return view('mess.purchaseorders.show', compact('purchaseOrder'));
    }

    public function edit($id)
    {
        $purchaseOrder = PurchaseOrder::with(['vendor', 'store', 'items.itemSubcategory'])->findOrFail($id);
        $po = [
            'id' => $purchaseOrder->id,
            'po_number' => $purchaseOrder->po_number,
            'po_date' => $purchaseOrder->po_date->format('Y-m-d'),
            'store_id' => $purchaseOrder->store_id,
            'vendor_id' => $purchaseOrder->vendor_id,
            'store_name' => $purchaseOrder->store->store_name ?? '—',
            'vendor_name' => $purchaseOrder->vendor->name ?? '—',
            'payment_code' => $purchaseOrder->payment_code,
            'contact_number' => $purchaseOrder->contact_number,
            'delivery_address' => $purchaseOrder->delivery_address,
            'bill_no' => $purchaseOrder->bill_no,
            'challan_no' => $purchaseOrder->challan_no,
            'bill_date' => $purchaseOrder->bill_date ? $purchaseOrder->bill_date->format('Y-m-d') : null,
            'challan_date' => $purchaseOrder->challan_date ? $purchaseOrder->challan_date->format('Y-m-d') : null,
            'remarks' => $purchaseOrder->remarks,
            'status' => $purchaseOrder->status,
            'bill_path' => $purchaseOrder->bill_path,
            'bill_url' => $purchaseOrder->bill_path ? asset('storage/' . $purchaseOrder->bill_path) : null,
        ];
        $items = $purchaseOrder->items->map(function ($item) {
            return [
                'item_subcategory_id' => $item->item_subcategory_id,
                'item_name' => optional($item->itemSubcategory)->item_name ?? '—',
                'item_code' => optional($item->itemSubcategory)->item_code ?? '—',
                'unit' => $item->unit ?? '—',
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'tax_percent' => (float) ($item->tax_percent ?? 0),
                'total_price' => (float) $item->total_price,
            ];
        })->values()->toArray();
        return response()->json(['po' => $po, 'items' => $items]);
    }

    public function update(Request $request, $id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        if ($purchaseOrder->status === 'approved') {
            return $this->updateApprovedPurchaseOrder($request, $purchaseOrder);
        }
        if ($purchaseOrder->status === 'rejected') {
            return redirect()->route('admin.mess.purchaseorders.index')
                ->with('po_edit_error', 'Purchase order ' . $purchaseOrder->po_number . ' is rejected and cannot be edited.');
        }

        // The edit modal posts its lines as one JSON field (items_json) so a large PO is not cut
        // short by PHP's max_input_vars; turn it back into items[] before anything reads the lines.
        if ($request->filled('items_json')) {
            $jsonItems = json_decode((string) $request->input('items_json'), true);
            if (is_array($jsonItems)) {
                $request->merge(['items' => array_values($jsonItems)]);
            }
        }

        // The edit modal loads the lines of a large PO in parts and sets all_lines_loaded to 1
        // only once every line is in the form. Below, every existing line is deleted and only the
        // posted lines are re-created, so a save that may be missing lines is refused untouched.
        // Requests that do not send the field are handled exactly as before.
        if ($request->has('all_lines_loaded') && $request->input('all_lines_loaded') !== '1') {
            return redirect()->route('admin.mess.purchaseorders.index')
                ->with('po_edit_error', 'Not all lines of this purchase order were loaded, so nothing was saved. Please reload the page and save again.');
        }

        // PHP drops request fields beyond max_input_vars without an error, so a very large PO can
        // arrive with only part of its lines. items_count is sent ahead of the line fields; if fewer
        // lines arrived than the form held, refuse the save so no existing line is deleted.
        if ($request->filled('items_count')) {
            $expectedLines = (int) $request->input('items_count');
            // Count only complete lines: PHP can cut the last line in half, leaving a line with no
            // quantity or price, which would otherwise pass this check and fail validation instead.
            $receivedLines = collect(is_array($request->input('items')) ? $request->input('items') : [])
                ->filter(fn ($line) => is_array($line) && array_key_exists('quantity', $line) && array_key_exists('unit_price', $line))
                ->count();
            if ($receivedLines < $expectedLines) {
                return redirect()->route('admin.mess.purchaseorders.index')
                    ->with('po_edit_error', "Only {$receivedLines} of {$expectedLines} lines of this purchase order reached the server, so nothing was saved. Please contact the administrator (PHP max_input_vars is too low for this order).");
            }

            // items_end is the form's last field. PHP drops fields from the end, so if it is missing the
            // request was cut somewhere - even inside the last line (for example only its tax_percent).
            if (! $request->has('items_end')) {
                return redirect()->route('admin.mess.purchaseorders.index')
                    ->with('po_edit_error', 'This purchase order did not reach the server completely, so nothing was saved. Please contact the administrator (PHP max_input_vars is too low for this order).');
            }
        }

        // A failed validation here reopens the create modal, so report a missing store on the list instead.
        if (! $request->filled('store_id')) {
            return redirect()->route('admin.mess.purchaseorders.index')
                ->with('po_edit_error', 'Please select a store for purchase order ' . $purchaseOrder->po_number . '. Nothing was saved.');
        }

        $this->normalizePurchaseOrderItemsInRequest($request);
        $request->validate([
            'vendor_id' => 'required|exists:mess_vendors,id',
            'store_id' => 'required|exists:mess_stores,id',
            'po_date' => 'required|date|before_or_equal:today',
            'delivery_date' => 'nullable|date',
            'payment_code' => 'nullable|string|max:50',
            'delivery_address' => 'nullable|string|max:500',
            'contact_number' => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
            'bill_no' => 'nullable|string|max:100',
            'challan_no' => 'nullable|string|max:100',
            'bill_date' => 'nullable|date|before_or_equal:today',
            'challan_date' => 'nullable|date|before_or_equal:today',
            'items' => 'required|array|min:1',
            'items.*.item_subcategory_id' => 'required|exists:mess_item_subcategories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'nullable|numeric|min:0|max:100',
            'bill_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ], [
            'contact_number.regex' => 'The contact number must be exactly 10 digits and contain only numbers (no letters or special characters).',
            'bill_file.mimes' => 'Bill must be PDF or image (jpg, jpeg, png, webp).',
            'bill_file.max' => 'Bill size must not exceed 5 MB.',
        ]);

        $savedStatus = DB::transaction(function () use ($request, $purchaseOrder) {
            // The status was read before validation; an approval or rejection may have landed since.
            // Lock the row and re-check so an approved PO's lines are never replaced.
            $current = PurchaseOrder::whereKey($purchaseOrder->id)->lockForUpdate()->value('status');
            if (in_array($current, ['approved', 'rejected'], true)) {
                return $current;
            }

            $grandTotal = 0;
            foreach ($request->items as $item) {
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $taxPercent = isset($item['tax_percent']) ? (float) $item['tax_percent'] : 0;
                $lineTotal = $qty * $unitPrice * (1 + $taxPercent / 100);
                $grandTotal += $lineTotal;
            }

            $purchaseOrder->update([
                'vendor_id' => $request->vendor_id,
                'store_id' => $request->store_id ?: null,
                'po_date' => $request->po_date,
                'delivery_date' => $request->delivery_date ?? null,
                'total_amount' => round($grandTotal, 2),
                'payment_code' => $request->payment_code,
                'delivery_address' => $request->delivery_address,
                'contact_number' => $request->contact_number,
                'bill_no' => $request->bill_no,
                'challan_no' => $request->challan_no,
                'bill_date' => $request->bill_date,
                'challan_date' => $request->challan_date,
                'remarks' => $request->remarks,
            ]);

            $this->replaceBillFile($request, $purchaseOrder);

            $purchaseOrder->items()->delete();
            $subcategories = ItemSubcategory::whereIn('id', collect($request->items)->map(
                fn ($item) => $this->coerceItemSubcategoryId($item['item_subcategory_id'] ?? null)
            )->filter())->get()->keyBy('id');

            foreach ($request->items as $item) {
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $taxPercent = isset($item['tax_percent']) ? (float) $item['tax_percent'] : 0;
                $lineTotal = round($qty * $unitPrice * (1 + $taxPercent / 100), 2);
                $itemSubcategoryId = $this->coerceItemSubcategoryId($item['item_subcategory_id'] ?? null);
                $sub = $itemSubcategoryId ? $subcategories->get($itemSubcategoryId) : null;
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'inventory_id' => null,
                    'item_subcategory_id' => $itemSubcategoryId,
                    'quantity' => $qty,
                    'unit' => $item['unit'] ?? ($sub ? ($sub->unit_measurement ?? null) : null),
                    'unit_price' => $unitPrice,
                    'tax_percent' => $taxPercent,
                    'total_price' => $lineTotal,
                    'description' => $item['description'] ?? null,
                ]);
            }

            return null;
        });
        if ($savedStatus !== null) {
            return redirect()->route('admin.mess.purchaseorders.index')
                ->with('po_edit_error', 'Purchase order ' . $purchaseOrder->po_number . ' was ' . $savedStatus . ' while you were editing it, so nothing was saved.');
        }
        self::bumpPurchaseOrderListingCacheEpoch();

        return redirect()->route('admin.mess.purchaseorders.index')->with('success', 'Purchase order updated successfully');
    }

    /**
     * An approved PO is in stock and may already be sold, so its purchase details (vendor, store,
     * date, lines) are frozen. Only the bill and delivery details can still be filled in: a bill
     * often arrives after the goods. An old approved PO saved without a store may be given one,
     * which puts its stock into that store, so only Mess Admin / Super Admin may do it.
     */
    private function updateApprovedPurchaseOrder(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'store_id' => 'nullable|exists:mess_stores,id',
            'delivery_date' => 'nullable|date',
            'payment_code' => 'nullable|string|max:50',
            'delivery_address' => 'nullable|string|max:500',
            'contact_number' => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
            'bill_no' => 'nullable|string|max:100',
            'challan_no' => 'nullable|string|max:100',
            'bill_date' => 'nullable|date|before_or_equal:today',
            'challan_date' => 'nullable|date|before_or_equal:today',
            'bill_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ], [
            'contact_number.regex' => 'The contact number must be exactly 10 digits and contain only numbers (no letters or special characters).',
            'bill_file.mimes' => 'Bill must be PDF or image (jpg, jpeg, png, webp).',
            'bill_file.max' => 'Bill size must not exceed 5 MB.',
        ]);

        $details = [
            'delivery_date' => $request->delivery_date ?? null,
            'payment_code' => $request->payment_code,
            'delivery_address' => $request->delivery_address,
            'contact_number' => $request->contact_number,
            'bill_no' => $request->bill_no,
            'challan_no' => $request->challan_no,
            'bill_date' => $request->bill_date,
            'challan_date' => $request->challan_date,
            'remarks' => $request->remarks,
        ];
        if (! $purchaseOrder->store_id && $request->filled('store_id') && $this->canApprovePurchaseOrders()) {
            $details['store_id'] = (int) $request->store_id;
        }

        DB::transaction(function () use ($request, $purchaseOrder, $details) {
            $purchaseOrder->update($details);
            $this->replaceBillFile($request, $purchaseOrder);
        });
        self::bumpPurchaseOrderListingCacheEpoch();

        return redirect()->route('admin.mess.purchaseorders.index')
            ->with('success', 'Purchase order ' . $purchaseOrder->po_number . ' updated. It is approved, so only bill and delivery details were saved; vendor, store, date and items are locked.');
    }

    private function replaceBillFile(Request $request, PurchaseOrder $purchaseOrder): void
    {
        if (! $request->hasFile('bill_file')) {
            return;
        }
        if ($purchaseOrder->bill_path && Storage::disk('public')->exists($purchaseOrder->bill_path)) {
            Storage::disk('public')->delete($purchaseOrder->bill_path);
        }
        $path = $request->file('bill_file')->store('mess/purchase-orders/bills', 'public');
        $purchaseOrder->update(['bill_path' => $path]);
    }

    public function destroy($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        if ($purchaseOrder->status === 'approved') {
            return redirect()->route('admin.mess.purchaseorders.index')
                ->with('po_edit_error', 'Purchase order ' . $purchaseOrder->po_number . ' is approved and in stock, so it cannot be deleted.');
        }
        // Re-check under a row lock: the PO may have been approved after it was read above.
        $deleted = DB::transaction(function () use ($purchaseOrder) {
            if (PurchaseOrder::whereKey($purchaseOrder->id)->lockForUpdate()->value('status') === 'approved') {
                return false;
            }
            $purchaseOrder->items()->delete();
            $purchaseOrder->delete();

            return true;
        });
        if (! $deleted) {
            return redirect()->route('admin.mess.purchaseorders.index')
                ->with('po_edit_error', 'Purchase order ' . $purchaseOrder->po_number . ' is approved and in stock, so it cannot be deleted.');
        }
        self::bumpPurchaseOrderListingCacheEpoch();

        return redirect()->route('admin.mess.purchaseorders.index')->with('success', 'Purchase order deleted successfully');
    }

    public function approve($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        if ($refusal = $this->approvalRefusal($purchaseOrder)) {
            return redirect()->route('admin.mess.purchaseorders.index')->with('po_edit_error', $refusal);
        }
        // Only a still-pending PO changes: if another approve/reject landed first, nothing is written.
        $changed = PurchaseOrder::whereKey($purchaseOrder->id)->where('status', 'pending')->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
        if ($changed === 0) {
            return redirect()->route('admin.mess.purchaseorders.index')
                ->with('po_edit_error', $this->approvalRefusal($purchaseOrder->refresh()) ?? 'Purchase order ' . $purchaseOrder->po_number . ' was not changed.');
        }
        self::bumpPurchaseOrderListingCacheEpoch();

        return redirect()->route('admin.mess.purchaseorders.index')
            ->with('success', 'Purchase order ' . $purchaseOrder->po_number . ' approved. Its items are now in stock and can be sold.');
    }

    public function reject($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        if ($refusal = $this->approvalRefusal($purchaseOrder)) {
            return redirect()->route('admin.mess.purchaseorders.index')->with('po_edit_error', $refusal);
        }
        $changed = PurchaseOrder::whereKey($purchaseOrder->id)->where('status', 'pending')->update(['status' => 'rejected']);
        if ($changed === 0) {
            return redirect()->route('admin.mess.purchaseorders.index')
                ->with('po_edit_error', $this->approvalRefusal($purchaseOrder->refresh()) ?? 'Purchase order ' . $purchaseOrder->po_number . ' was not changed.');
        }
        self::bumpPurchaseOrderListingCacheEpoch();

        return redirect()->route('admin.mess.purchaseorders.index')
            ->with('success', 'Purchase order ' . $purchaseOrder->po_number . ' rejected');
    }

    /**
     * Only Mess Admin / Super Admin decide, and only on a pending PO: an approved one is in stock
     * and may be sold, so it cannot be rejected or approved again.
     */
    private function approvalRefusal(PurchaseOrder $purchaseOrder): ?string
    {
        if (! $this->canApprovePurchaseOrders()) {
            return 'Only Mess Admin or Super Admin can approve or reject a purchase order.';
        }
        if ($purchaseOrder->status !== 'pending') {
            return 'Purchase order ' . $purchaseOrder->po_number . ' is already ' . $purchaseOrder->status . '.';
        }

        return null;
    }

    public function getVendorItems($vendorId)
    {
        // Vendor-wise filtering is currently disabled.
        // Always return the full active item list so that
        // the item dropdown shows all items regardless of vendor.

        $items = ItemSubcategory::active()
            ->orderBy('name')
            ->get(ItemSubcategory::listSelectColumns())
            ->map(fn ($s) => [
                'id' => $s->id,
                'item_name' => $s->item_name ?? $s->name ?? '—',
                'item_code' => $s->item_code ?? '—',
                'unit_measurement' => $s->unit_measurement ?? '—',
            ]);

        return response()->json($items);
    }

    /**
     * Multi-select item fields submit item_subcategory_id as an array; expand to one row per id
     * so validation and persistence always see a scalar (avoids "Array to string conversion").
     */
    protected function normalizePurchaseOrderItemsInRequest(Request $request): void
    {
        $items = $request->input('items');
        if (! is_array($items)) {
            return;
        }

        $normalized = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $rawId = $item['item_subcategory_id'] ?? null;
            if (is_array($rawId)) {
                $ids = array_values(array_filter($rawId, static function ($v) {
                    return $v !== '' && $v !== null;
                }));
                foreach ($ids as $subId) {
                    $row = $item;
                    $row['item_subcategory_id'] = $subId;
                    $normalized[] = $row;
                }
            } else {
                if (is_string($rawId) && str_contains($rawId, ',')) {
                    $ids = array_values(array_filter(array_map('trim', explode(',', $rawId)), static function ($v) {
                        return $v !== '';
                    }));
                    foreach ($ids as $subId) {
                        $row = $item;
                        $row['item_subcategory_id'] = $subId;
                        $normalized[] = $row;
                    }
                    continue;
                }
                $normalized[] = $item;
            }
        }

        $request->merge(['items' => $normalized]);
    }

    /**
     * GET filter fields may be scalar or array (multi-select). Returns unique positive int IDs.
     *
     * @param  mixed  $value
     * @return list<int>
     */
    protected function normalizeFilterIdList($value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        if (! is_array($value)) {
            $value = [$value];
        }

        $seen = [];
        foreach ($value as $v) {
            if ($v === '' || $v === null || is_array($v)) {
                continue;
            }
            $id = (int) $v;
            if ($id > 0) {
                $seen[$id] = true;
            }
        }

        return array_map('intval', array_keys($seen));
    }

    protected function coerceItemSubcategoryId($rawId): ?int
    {
        if (is_array($rawId)) {
            $rawId = $rawId[0] ?? null;
        }

        if (is_string($rawId) && str_contains($rawId, ',')) {
            $rawId = trim(explode(',', $rawId)[0] ?? '');
        }

        if ($rawId === '' || $rawId === null) {
            return null;
        }

        return (int) $rawId;
    }

    /**
     * Generate a unique Purchase Order number in format PO/{number}/NM.
     */
    protected function generatePoNumber(): string
    {
        $next = ((int) PurchaseOrder::max('id')) + 1;
        $code = 'PO/' . $next . '/NM';

        while (PurchaseOrder::where('po_number', $code)->exists()) {
            $next++;
            $code = 'PO/' . $next . '/NM';
        }

        return $code;
    }
}
