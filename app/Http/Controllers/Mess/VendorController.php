<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\Mess\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::orderByDesc('id')->get();
        return view('mess.vendors.index', compact('vendors'));
    }

    public function create()
    {
        return view('mess.vendors.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        Vendor::create($data);

        return redirect()->route('admin.mess.vendors.index')->with('success', 'Vendor added successfully');
    }

    public function edit($id)
    {
        $vendor = Vendor::findOrFail($id);
        return view('mess.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        $data = $this->validatedData($request, $vendor);

        $vendor->update($data);
        return redirect()->route('admin.mess.vendors.index')->with('success', 'Vendor updated successfully');
    }

    public function destroy($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->delete();
        return redirect()->route('admin.mess.vendors.index')->with('success', 'Vendor deleted successfully');
    }

    /**
     * Build an array of validated attributes for create/update.
     */
    protected function validatedData(Request $request, ?Vendor $vendor = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
        ]);

        return [
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'contact_person' => $validated['contact_person'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
        ];
    }

}
