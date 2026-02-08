<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\MaterialRequest;
use App\Models\Mess\MaterialRequestItem;
use App\Models\Mess\Inventory;
use App\Models\Mess\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaterialRequestController extends Controller
{
    public function index()
    {
        $requests = MaterialRequest::with(['store', 'requester', 'approver', 'items'])->latest()->get();
        return view('mess.materialrequests.index', compact('requests'));
    }

    public function create()
    {
        $stores = Store::where('is_active', true)->get();
        $inventories = Inventory::all();
        $request_number = 'MR' . date('Ymd') . str_pad(MaterialRequest::count() + 1, 4, '0', STR_PAD_LEFT);
        return view('mess.materialrequests.create', compact('stores', 'inventories', 'request_number'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'request_number' => 'required|unique:mess_material_requests,request_number',
            'request_date' => 'required|date',
            'store_id' => 'nullable|exists:mess_stores,id',
            'purpose' => 'nullable',
            'items' => 'required|array',
            'items.*.inventory_id' => 'required|exists:mess_inventories,id',
            'items.*.requested_quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable',
        ]);

        DB::transaction(function () use ($request) {
            $materialRequest = MaterialRequest::create([
                'request_number' => $request->request_number,
                'request_date' => $request->request_date,
                'store_id' => $request->store_id,
                'purpose' => $request->purpose,
                'requested_by' => Auth::id(),
                'status' => 'pending',
            ]);

            foreach ($request->items as $item) {
                MaterialRequestItem::create([
                    'material_request_id' => $materialRequest->id,
                    'inventory_id' => $item['inventory_id'],
                    'requested_quantity' => $item['requested_quantity'],
                    'unit' => $item['unit'] ?? null,
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }
        });

        return redirect()->route('admin.mess.materialrequests.index')->with('success', 'Material request created successfully');
    }

    public function show($id)
    {
        $materialRequest = MaterialRequest::with(['store', 'requester', 'approver', 'items.inventory'])->findOrFail($id);
        return view('mess.materialrequests.show', compact('materialRequest'));
    }

    public function approve($id)
    {
        $materialRequest = MaterialRequest::findOrFail($id);
        return view('mess.materialrequests.approve', compact('materialRequest'));
    }

    public function processApproval(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected',
            'items' => 'required_if:status,approved|array',
        ]);

        DB::transaction(function () use ($request, $id) {
            $materialRequest = MaterialRequest::findOrFail($id);
            $materialRequest->update([
                'status' => $request->status,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_reason' => $request->rejection_reason,
            ]);

            if ($request->status === 'approved' && $request->items) {
                foreach ($request->items as $itemId => $data) {
                    $item = MaterialRequestItem::findOrFail($itemId);
                    $item->update([
                        'approved_quantity' => $data['approved_quantity'] ?? $item->requested_quantity,
                    ]);
                }
            }
        });

        return redirect()->route('admin.mess.materialrequests.index')->with('success', 'Material request ' . $request->status . ' successfully');
    }
}
