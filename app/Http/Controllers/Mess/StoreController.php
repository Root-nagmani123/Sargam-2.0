<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\Store;

class StoreController extends Controller
{
    public function index()
    {
        $stores = Store::all();
        return view('mess.stores.index', compact('stores'));
    }

    public function create()
    {
        return view('mess.stores.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_name' => 'required',
            'store_code' => 'required|unique:mess_stores,store_code',
            'location' => 'nullable',
            'incharge_name' => 'nullable',
            'incharge_contact' => 'nullable',
            'status' => 'required|in:active,inactive',
        ]);
        Store::create($request->all());
        return redirect()->route('mess.stores.index')->with('success', 'Store added successfully');
    }

    public function edit($id)
    {
        $store = Store::findOrFail($id);
        return view('mess.stores.edit', compact('store'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'store_name' => 'required',
            'store_code' => 'required|unique:mess_stores,store_code,'.$id,
            'status' => 'required|in:active,inactive',
        ]);
        $store = Store::findOrFail($id);
        $store->update($request->all());
        return redirect()->route('mess.stores.index')->with('success', 'Store updated successfully');
    }
}
