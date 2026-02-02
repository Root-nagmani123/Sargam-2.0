<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\Mess\SellingVoucherDateRangeReport;
use App\Models\Mess\SellingVoucherDateRangeReportItem;
use App\Models\Mess\Store;
use App\Models\Mess\ItemSubcategory;
use App\Models\Mess\ClientType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Selling Voucher with Date Range - standalone module (design/pattern like Selling Voucher, data/logic separate).
 */
class SellingVoucherDateRangeController extends Controller
{
    public function index(Request $request)
    {
        $query = SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'items.itemSubcategory']);

        if ($request->filled('store')) {
            $query->where('store_id', $request->store);
        }
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->where('date_from', '<=', $request->end_date)
                  ->where('date_to', '>=', $request->start_date);
        }

        $reports = $query->orderBy('date_from', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        $stores = Store::active()->get();
        $itemSubcategories = ItemSubcategory::active()->orderBy('name')->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'item_name' => $s->item_name ?? $s->name ?? '—',
                'unit_measurement' => $s->unit_measurement ?? '—',
            ];
        });
        $clientTypes = ClientType::clientTypes();
        $clientNamesByType = ClientType::active()->orderBy('client_type')->orderBy('client_name')->get()->groupBy('client_type');

        return view('mess.selling-voucher-date-range.index', compact('reports', 'stores', 'itemSubcategories', 'clientTypes', 'clientNamesByType'));
    }

    public function create()
    {
        return redirect()->route('admin.mess.selling-voucher-date-range.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'inve_store_master_pk' => 'required|exists:mess_stores,id',
            'payment_type' => 'required|integer|in:0,1,2,5',
            'client_type_slug' => 'required|string|in:employee,ot,course,section,other',
            'client_type_pk' => 'nullable|exists:mess_client_types,id',
            'client_name' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_subcategory_id' => 'required|exists:mess_item_subcategories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.available_quantity' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $issueDate = $request->issue_date;
            $report = SellingVoucherDateRangeReport::create([
                'date_from' => $issueDate,
                'date_to' => $issueDate,
                'store_id' => $request->inve_store_master_pk,
                'report_title' => null,
                'status' => SellingVoucherDateRangeReport::STATUS_APPROVED,
                'total_amount' => 0,
                'remarks' => $request->remarks,
                'client_type_slug' => $request->client_type_slug,
                'client_type_pk' => $request->client_type_pk,
                'client_name' => $request->client_name,
                'payment_type' => (int) $request->payment_type,
                'issue_date' => $issueDate,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $subcategories = ItemSubcategory::whereIn('id', collect($request->items)->pluck('item_subcategory_id'))->get()->keyBy('id');
            $grandTotal = 0;

            foreach ($request->items as $row) {
                $sub = $subcategories->get($row['item_subcategory_id']);
                $qty = (float) ($row['quantity'] ?? 0);
                $rate = (float) ($row['rate'] ?? 0);
                $avail = (float) ($row['available_quantity'] ?? 0);
                $amount = $qty * $rate;
                $grandTotal += $amount;
                SellingVoucherDateRangeReportItem::create([
                    'sv_date_range_report_id' => $report->id,
                    'item_subcategory_id' => $row['item_subcategory_id'],
                    'item_name' => $sub ? ($sub->item_name ?? $sub->name ?? '') : '',
                    'quantity' => $qty,
                    'available_quantity' => $avail,
                    'return_quantity' => 0,
                    'rate' => $rate,
                    'amount' => $amount,
                    'unit' => $sub->unit_measurement ?? '',
                ]);
            }

            $report->update(['total_amount' => $grandTotal]);

            DB::commit();

            return redirect()->route('admin.mess.selling-voucher-date-range.index')
                ->with('success', 'Date Range Report created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.mess.selling-voucher-date-range.index')
                ->withInput()
                ->with('error', 'Failed to create report: ' . $e->getMessage())
                ->with('open_add_modal', true);
        }
    }

    public function show(Request $request, $id)
    {
        $report = SellingVoucherDateRangeReport::with(['store', 'clientTypeCategory', 'items.itemSubcategory'])->findOrFail($id);

        if ($request->wantsJson()) {
            $issueDateFormatted = $report->issue_date ? $report->issue_date->format('d/m/Y') : '—';
            $voucher = [
                'id' => $report->id,
                'request_date' => $report->date_from ? $report->date_from->format('d/m/Y') : '—',
                'date_from' => $report->date_from ? $report->date_from->format('d/m/Y') : '—',
                'date_to' => $report->date_to ? $report->date_to->format('d/m/Y') : '—',
                'store_name' => $report->store->store_name ?? '—',
                'report_title' => $report->report_title ?? '—',
                'status' => $report->status,
                'status_label' => SellingVoucherDateRangeReport::statusLabels()[$report->status] ?? '—',
                'client_type' => $report->clientTypeCategory ? ucfirst($report->clientTypeCategory->client_type ?? '') : ($report->client_type_slug ? ucfirst($report->client_type_slug) : '—'),
                'client_name' => $report->clientTypeCategory ? ($report->clientTypeCategory->client_name ?? '—') : '—',
                'client_name_text' => $report->client_name ?? '—',
                'payment_type' => $report->payment_type == 1 ? 'Credit' : ($report->payment_type == 0 ? 'Cash' : ($report->payment_type == 2 ? 'Online' : '—')),
                'issue_date' => $issueDateFormatted,
                'remarks' => $report->remarks ?? '',
                'created_at' => $report->created_at ? $report->created_at->format('d/m/Y H:i') : '—',
                'updated_at' => $report->updated_at ? $report->updated_at->format('d/m/Y H:i') : null,
            ];
            $items = $report->items->map(function ($item) use ($issueDateFormatted) {
                return [
                    'item_name' => $item->item_name ?: ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? '—'),
                    'unit' => $item->unit ?? '—',
                    'quantity' => (float) $item->quantity,
                    'available_quantity' => (float) ($item->available_quantity ?? 0),
                    'return_quantity' => (float) ($item->return_quantity ?? 0),
                    'issue_date' => $issueDateFormatted,
                    'rate' => number_format($item->rate, 2),
                    'amount' => number_format($item->amount, 2),
                ];
            })->values()->toArray();
            $grand_total = $report->items->sum('amount');
            return response()->json([
                'voucher' => $voucher,
                'items' => $items,
                'grand_total' => number_format($grand_total, 2),
                'has_items' => $report->items->isNotEmpty(),
            ]);
        }

        return redirect()->route('admin.mess.selling-voucher-date-range.index');
    }

    public function edit(Request $request, $id)
    {
        $report = SellingVoucherDateRangeReport::with(['items.itemSubcategory'])->findOrFail($id);

        if ($request->wantsJson()) {
            $clientTypeSlug = $report->clientTypeCategory ? $report->clientTypeCategory->client_type : ($report->client_type_slug ?? 'employee');
            $voucher = [
                'id' => $report->id,
                'date_from' => $report->date_from ? $report->date_from->format('Y-m-d') : '',
                'date_to' => $report->date_to ? $report->date_to->format('Y-m-d') : '',
                'store_id' => $report->store_id,
                'report_title' => $report->report_title,
                'status' => (int) $report->status,
                'remarks' => $report->remarks,
                'client_type_slug' => $report->client_type_slug ?? $clientTypeSlug,
                'client_type_pk' => $report->client_type_pk,
                'client_name' => $report->client_name,
                'payment_type' => (int) $report->payment_type,
                'issue_date' => $report->issue_date ? $report->issue_date->format('Y-m-d') : '',
            ];
            $items = $report->items->map(function ($item) {
                return [
                    'item_subcategory_id' => $item->item_subcategory_id,
                    'item_name' => $item->item_name ?? ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? '—'),
                    'unit' => $item->unit ?? '',
                    'quantity' => (float) $item->quantity,
                    'available_quantity' => (float) ($item->available_quantity ?? 0),
                    'return_quantity' => (float) ($item->return_quantity ?? 0),
                    'rate' => (float) $item->rate,
                    'amount' => (float) $item->amount,
                ];
            })->values()->toArray();
            return response()->json(['voucher' => $voucher, 'items' => $items]);
        }

        return redirect()->route('admin.mess.selling-voucher-date-range.index');
    }

    public function update(Request $request, $id)
    {
        $report = SellingVoucherDateRangeReport::findOrFail($id);

        $request->validate([
            'inve_store_master_pk' => 'required|exists:mess_stores,id',
            'payment_type' => 'required|integer|in:0,1,2,5',
            'client_type_slug' => 'required|string|in:employee,ot,course,section,other',
            'client_type_pk' => 'nullable|exists:mess_client_types,id',
            'client_name' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_subcategory_id' => 'required|exists:mess_item_subcategories,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.available_quantity' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $issueDate = $request->issue_date;
            $report->update([
                'date_from' => $issueDate,
                'date_to' => $issueDate,
                'store_id' => $request->inve_store_master_pk,
                'report_title' => null,
                'status' => SellingVoucherDateRangeReport::STATUS_APPROVED,
                'remarks' => $request->remarks,
                'client_type_slug' => $request->client_type_slug,
                'client_type_pk' => $request->client_type_pk,
                'client_name' => $request->client_name,
                'payment_type' => (int) $request->payment_type,
                'issue_date' => $issueDate,
                'updated_by' => Auth::id(),
            ]);

            $report->items()->delete();

            $subcategories = ItemSubcategory::whereIn('id', collect($request->items)->pluck('item_subcategory_id'))->get()->keyBy('id');
            $grandTotal = 0;

            foreach ($request->items as $row) {
                $sub = $subcategories->get($row['item_subcategory_id']);
                $qty = (float) ($row['quantity'] ?? 0);
                $rate = (float) ($row['rate'] ?? 0);
                $avail = (float) ($row['available_quantity'] ?? 0);
                $amount = $qty * $rate;
                $grandTotal += $amount;
                SellingVoucherDateRangeReportItem::create([
                    'sv_date_range_report_id' => $report->id,
                    'item_subcategory_id' => $row['item_subcategory_id'],
                    'item_name' => $sub ? ($sub->item_name ?? $sub->name ?? '') : '',
                    'quantity' => $qty,
                    'available_quantity' => $avail,
                    'return_quantity' => (float) ($row['return_quantity'] ?? 0),
                    'rate' => $rate,
                    'amount' => $amount,
                    'unit' => $sub->unit_measurement ?? '',
                ]);
            }

            $report->update(['total_amount' => $grandTotal]);

            DB::commit();

            return redirect()->route('admin.mess.selling-voucher-date-range.index')
                ->with('success', 'Date Range Report updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.mess.selling-voucher-date-range.index')
                ->with('error', 'Failed to update report: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $report = SellingVoucherDateRangeReport::findOrFail($id);
        $report->items()->delete();
        $report->delete();
        return redirect()->route('admin.mess.selling-voucher-date-range.index')
            ->with('success', 'Date Range Report deleted successfully.');
    }
}
