<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\Mess\StoreAllocation;
use App\Models\Mess\StoreAllocationItem;
use App\Models\Mess\SubStore;
use App\Models\Mess\ItemSubcategory;
use App\Support\DataTableSearchHelper;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StoreAllocationController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax() && $request->has('draw')) {
            return $this->storeAllocationsDatatable($request);
        }

        $subStores = SubStore::active()->orderBy('sub_store_name')->get();
        $itemSubcategories = ItemSubcategory::active()->orderBy('name')->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'item_name' => $s->item_name ?? $s->name ?? '—',
                'item_code' => $s->item_code ?? '—',
                'unit_measurement' => $s->unit_measurement ?? '—',
            ]);

        return view('mess.storeallocations.index', compact('subStores', 'itemSubcategories'));
    }

    /**
     * Server-side DataTables JSON (one row per allocation item line).
     */
    private function storeAllocationsDatatable(Request $request)
    {
        $draw = (int) $request->input('draw', 0);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        if ($length < 1 || $length > 100) {
            $length = 10;
        }

        $searchRaw = '';
        $searchPayload = $request->input('search');
        if (is_array($searchPayload) && isset($searchPayload['value'])) {
            $searchRaw = (string) $searchPayload['value'];
        }

        $base = $this->storeAllocationRowsBaseQuery();
        $recordsTotal = (clone $base)->count('sai.id');

        $filtered = clone $base;
        $this->applyStoreAllocationSearch($filtered, $searchRaw);

        $searchTrimmed = DataTableSearchHelper::normalizeRaw($searchRaw);
        $recordsFiltered = $searchTrimmed === ''
            ? $recordsTotal
            : (clone $filtered)->count('sai.id');

        $ordered = clone $filtered;
        $this->applyStoreAllocationDatatableOrder($ordered, $request);

        $rows = $ordered
            ->offset($start)
            ->limit($length)
            ->get();

        $canDelete = function_exists('hasRole') && (hasRole('Admin') || hasRole('Mess-Admin'));
        $data = [];
        foreach ($rows as $idx => $row) {
            $data[] = $this->buildStoreAllocationDatatableRow($row, $start + $idx + 1, $canDelete);
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    private function storeAllocationRowsBaseQuery(): Builder
    {
        $itemLabelSql = $this->itemSubcategoryLabelSql('mis');

        return DB::table('mess_store_allocation_items as sai')
            ->join('mess_store_allocations as sa', 'sai.store_allocation_id', '=', 'sa.id')
            ->leftJoin('mess_sub_stores as mss', 'sa.sub_store_id', '=', 'mss.id')
            ->leftJoin('mess_item_subcategories as mis', 'sai.item_subcategory_id', '=', 'mis.id')
            ->leftJoin('mess_item_categories as mic', 'mis.category_id', '=', 'mic.id')
            ->whereNotNull('sa.sub_store_id')
            ->select([
                'sai.id as item_line_id',
                'sa.id as allocation_id',
                'mss.sub_store_name',
                DB::raw("{$itemLabelSql} as item_name"),
                'mic.category_name',
                'sai.quantity',
                'sa.allocation_date',
            ]);
    }

    private function applyStoreAllocationSearch(Builder $query, string $searchRaw): void
    {
        $tokens = DataTableSearchHelper::tokens($searchRaw);
        if ($tokens === []) {
            return;
        }

        $itemLabelSql = $this->itemSubcategoryLabelSql('mis');

        foreach ($tokens as $token) {
            $like = DataTableSearchHelper::likePattern($token);
            $query->where(function ($q) use ($like, $itemLabelSql) {
                $q->where('mss.sub_store_name', 'like', $like)
                    ->orWhereRaw("{$itemLabelSql} LIKE ?", [$like])
                    ->orWhere('mic.category_name', 'like', $like)
                    ->orWhere('sai.quantity', 'like', $like)
                    ->orWhere('sa.allocation_date', 'like', $like);
            });
        }
    }

    private function applyStoreAllocationDatatableOrder(Builder $query, Request $request): void
    {
        $orderCol = DataTableSearchHelper::orderColumnIndex($request, 5);
        $orderDir = DataTableSearchHelper::orderDirection($request, 'desc');
        $itemLabelSql = $this->itemSubcategoryLabelSql('mis');

        $sortMap = [
            1 => 'mss.sub_store_name',
            2 => DB::raw($itemLabelSql),
            3 => 'mic.category_name',
            4 => 'sai.quantity',
            5 => 'sa.allocation_date',
        ];

        if (isset($sortMap[$orderCol])) {
            $query->orderBy($sortMap[$orderCol], $orderDir);
        } else {
            $query->orderByDesc('sa.allocation_date');
        }

        $query->orderByDesc('sa.id')->orderByDesc('sai.id');
    }

    private function buildStoreAllocationDatatableRow(object $row, int $sno, bool $canDelete): array
    {
        $storeName = e($row->sub_store_name ?? 'N/A');
        $itemName = e($row->item_name ?? 'N/A');
        $categoryName = e($row->category_name ?? 'N/A');
        $quantity = e((string) $row->quantity);
        $dateDisplay = '—';
        if (! empty($row->allocation_date)) {
            try {
                $dateDisplay = e(\Carbon\Carbon::parse($row->allocation_date)->format('d-m-Y'));
            } catch (\Throwable $e) {
                $dateDisplay = e((string) $row->allocation_date);
            }
        }

        $allocationId = (int) $row->allocation_id;
        $editBtn = '<button type="button" class="btn btn-sm btn-info btn-edit-allocation text-primary bg-transparent border-0 p-0 d-inline-flex align-items-center justify-content-center" data-allocation-id="' . $allocationId . '" title="Edit allocation">'
            . '<span class="material-symbols-rounded" style="font-size: 1.1rem;">edit</span>'
            . '</button>';

        $deleteForm = '';
        if ($canDelete) {
            $deleteUrl = route('admin.mess.storeallocations.destroy', $allocationId);
            $deleteForm = '<form action="' . e($deleteUrl) . '" method="POST" class="d-inline-flex align-items-center justify-content-center m-0" onsubmit="return confirm(\'Are you sure you want to delete this store allocation?\');">'
                . '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">'
                . '<input type="hidden" name="_method" value="DELETE">'
                . '<button type="submit" class="btn btn-sm btn-outline-danger bg-transparent border-0 p-0 text-primary d-inline-flex align-items-center justify-content-center" title="Delete allocation">'
                . '<span class="material-symbols-rounded" style="font-size: 1.1rem;">delete</span>'
                . '</button>'
                . '</form>';
        }

        $actions = '<div class="d-inline-flex align-items-center justify-content-center gap-2">' . $editBtn . $deleteForm . '</div>';

        return [
            '<span class="col-sno">' . $sno . '</span>',
            $storeName,
            $itemName,
            $categoryName,
            $quantity,
            $dateDisplay,
            '<div class="text-center align-middle store-alloc-actions-cell">' . $actions . '</div>',
        ];
    }

    private function itemSubcategoryLabelSql(string $alias = 'mis'): string
    {
        $parts = [];
        foreach (['item_name', 'subcategory_name', 'name'] as $col) {
            if (Schema::hasColumn('mess_item_subcategories', $col)) {
                $parts[] = "NULLIF(TRIM({$alias}.{$col}), '')";
            }
        }

        if ($parts === []) {
            return "'—'";
        }

        return 'COALESCE(' . implode(', ', $parts) . ", '—')";
    }

    public function store(Request $request)
    {
        $request->validate([
            'sub_store_id' => 'required|exists:mess_sub_stores,id',
            'allocation_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_subcategory_id' => 'required|exists:mess_item_subcategories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $allocation = StoreAllocation::create([
                'sub_store_id' => $request->sub_store_id,
                'allocation_date' => $request->allocation_date,
            ]);

            foreach ($request->items as $item) {
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $totalPrice = round($qty * $unitPrice, 2);
                $sub = ItemSubcategory::find($item['item_subcategory_id']);
                StoreAllocationItem::create([
                    'store_allocation_id' => $allocation->id,
                    'item_subcategory_id' => $item['item_subcategory_id'],
                    'quantity' => $qty,
                    'unit' => $item['unit'] ?? ($sub ? ($sub->unit_measurement ?? null) : null),
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ]);
            }
        });

        return redirect()->route('admin.mess.storeallocations.index')->with('success', 'Store allocation added successfully.');
    }

    public function edit($id)
    {
        $allocation = StoreAllocation::with(['subStore', 'items.itemSubcategory'])->whereNotNull('sub_store_id')->findOrFail($id);
        $data = [
            'id' => $allocation->id,
            'sub_store_id' => $allocation->sub_store_id,
            'allocation_date' => $allocation->allocation_date ? $allocation->allocation_date->format('Y-m-d') : null,
        ];
        $items = $allocation->items->map(function ($item) {
            return [
                'item_subcategory_id' => $item->item_subcategory_id,
                'item_name' => optional($item->itemSubcategory)->item_name ?? '—',
                'item_code' => optional($item->itemSubcategory)->item_code ?? '—',
                'unit' => $item->unit ?? '—',
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
            ];
        })->values()->toArray();

        return response()->json(['allocation' => $data, 'items' => $items]);
    }

    public function update(Request $request, $id)
    {
        $allocation = StoreAllocation::whereNotNull('sub_store_id')->findOrFail($id);
        $request->validate([
            'sub_store_id' => 'required|exists:mess_sub_stores,id',
            'allocation_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_subcategory_id' => 'required|exists:mess_item_subcategories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $allocation) {
            $allocation->update([
                'sub_store_id' => $request->sub_store_id,
                'allocation_date' => $request->allocation_date,
            ]);
            $allocation->items()->delete();
            foreach ($request->items as $item) {
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $totalPrice = round($qty * $unitPrice, 2);
                $sub = ItemSubcategory::find($item['item_subcategory_id']);
                StoreAllocationItem::create([
                    'store_allocation_id' => $allocation->id,
                    'item_subcategory_id' => $item['item_subcategory_id'],
                    'quantity' => $qty,
                    'unit' => $item['unit'] ?? ($sub ? ($sub->unit_measurement ?? null) : null),
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ]);
            }
        });

        return redirect()->route('admin.mess.storeallocations.index')->with('success', 'Store allocation updated successfully.');
    }

    public function destroy($id)
    {
        $allocation = StoreAllocation::whereNotNull('sub_store_id')->findOrFail($id);
        $allocation->items()->delete();
        $allocation->delete();

        return redirect()->route('admin.mess.storeallocations.index')->with('success', 'Store allocation deleted successfully.');
    }
}
