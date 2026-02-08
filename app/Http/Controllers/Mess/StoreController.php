<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\Store;

class StoreController extends Controller
{
    public function index()
    {
        $stores = Store::orderByDesc('id')->get();
        return view('mess.stores.index', compact('stores'));
    }

    public function create()
    {
        return view('mess.stores.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        Store::create(array_merge($data, [
            'store_code' => $this->generateStoreCode(),
        ]));

        return redirect()->route('admin.mess.stores.index')->with('success', 'Store added successfully');
    }

    public function edit($id)
    {
        $store = Store::findOrFail($id);
        return view('mess.stores.edit', compact('store'));
    }

    public function update(Request $request, $id)
    {
        $store = Store::findOrFail($id);
        $data  = $this->validatedData($request, $store);

        $store->update($data);
        return redirect()->route('admin.mess.stores.index')->with('success', 'Store updated successfully');
    }

    public function destroy($id)
    {
        $store = Store::findOrFail($id);
        $store->delete();
        return redirect()->route('admin.mess.stores.index')->with('success', 'Store deleted successfully');
    }

    /**
     * Build an array of validated attributes for create/update.
     */
    protected function validatedData(Request $request, ?Store $store = null): array
    {
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'store_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(Store::storeTypes()))],
            'location'   => ['nullable', 'string', 'max:255'],
            'status'     => ['nullable', 'in:active,inactive'],
        ]);

        $status = $validated['status'] ?? Store::STATUS_ACTIVE;
        $storeType = $validated['store_type'] ?? Store::TYPE_MESS;

        return [
            'store_name' => $validated['store_name'],
            'store_type' => $storeType,
            'location'   => $validated['location'] ?? null,
            'status'     => $status,
        ];
    }

    /**
     * Generate a unique store code.
     */
    protected function generateStoreCode(): string
    {
        $next = ((int) Store::max('id')) + 1;
        $code = 'STR' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);

        while (Store::where('store_code', $code)->exists()) {
            $next++;
            $code = 'STR' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
        }

        return $code;
    }
}
