<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\Mess\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        if ($request->hasFile('licence_document')) {
            $data['licence_document'] = $request->file('licence_document')
                ->store('mess_vendors/licences', 'public');
        }

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

        if ($request->hasFile('licence_document')) {
            if ($vendor->licence_document && Storage::disk('public')->exists($vendor->licence_document)) {
                Storage::disk('public')->delete($vendor->licence_document);
            }

            $data['licence_document'] = $request->file('licence_document')
                ->store('mess_vendors/licences', 'public');
        }

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
            'contact_person' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'address' => ['required', 'string'],
            'gst_number' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'ifsc_code' => ['nullable', 'string', 'max:20'],
            'account_number' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]+$/'],
            'licence_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ], [
            'phone.regex' => 'The phone number must contain only digits.',
            'account_number.regex' => 'The account number must contain only digits.',
        ]);

        return [
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'contact_person' => $validated['contact_person'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'gst_number' => $validated['gst_number'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'ifsc_code' => $validated['ifsc_code'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
        ];
    }

}
