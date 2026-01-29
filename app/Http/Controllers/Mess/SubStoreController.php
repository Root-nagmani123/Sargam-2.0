<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\SubStore;
use App\Models\Mess\Store;

class SubStoreController extends Controller
{
    public function index()
    {
        $subStores = SubStore::with('parentStore')->orderByDesc('id')->get();
        $stores = Store::orderBy('store_name')->get();
        return view('mess.sub-stores.index', compact('subStores', 'stores'));
    }

    public function create()
    {
        $stores = Store::orderBy('store_name')->get();
        return view('mess.sub-stores.create', compact('stores'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        SubStore::create($data);

        return redirect()->route('admin.mess.sub-stores.index')->with('success', 'Sub Store added successfully');
    }

    public function edit($id)
    {
        $subStore = SubStore::findOrFail($id);
        $stores = Store::orderBy('store_name')->get();
        return view('mess.sub-stores.edit', compact('subStore', 'stores'));
    }

    public function update(Request $request, $id)
    {
        $subStore = SubStore::findOrFail($id);
        $data = $this->validatedData($request, $subStore);

        $subStore->update($data);
        return redirect()->route('admin.mess.sub-stores.index')->with('success', 'Sub Store updated successfully');
    }

    public function destroy($id)
    {
        $subStore = SubStore::findOrFail($id);
        $subStore->delete();
        return redirect()->route('admin.mess.sub-stores.index')->with('success', 'Sub Store deleted successfully');
    }

    /**
     * Build an array of validated attributes for create/update.
     */
    protected function validatedData(Request $request, ?SubStore $subStore = null): array
    {
        $validated = $request->validate([
            'parent_store_id' => ['required', 'exists:mess_stores,id'],
            'sub_store_name'  => ['required', 'string', 'max:255'],
            'status'          => ['nullable', 'in:active,inactive'],
        ]);

        $status = $validated['status'] ?? SubStore::STATUS_ACTIVE;

        return [
            'parent_store_id' => $validated['parent_store_id'],
            'sub_store_name'  => $validated['sub_store_name'],
            'status'          => $status,
        ];
    }
}
