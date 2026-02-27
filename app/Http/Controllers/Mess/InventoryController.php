<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\Mess\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index() {
        $inventories = Inventory::all();
        return view('mess.inventories.index', compact('inventories'));
    }
    public function create() {
        return view('mess.inventories.create');
    }
    public function store(Request $request) {
        $data = $request->validate([
            'item_name' => 'required',
            'category' => 'required',
            'quantity' => 'required|integer',
            'unit' => 'required',
            'expiry_date' => 'nullable|date',
        ]);
        Inventory::create($data);
        return redirect()->route('admin.mess.inventories.index')->with('success', 'Inventory item created successfully.');
    }
}
