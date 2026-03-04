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

    /** Letters, numbers, spaces, hyphens only (no special characters). */
    protected const NAME_PATTERN = '/^[\pL\pN\s\-]+$/u';

    /** Letters, numbers, spaces, hyphens, commas, periods, newlines (no special characters). */
    protected const ADDRESS_PATTERN = '/^[\pL\pN\s\-\.\,\r\n]+$/u';

    /** GST: alphanumeric only (e.g. 15-char GSTIN). */
    protected const GST_PATTERN = '/^[A-Za-z0-9]+$/u';

    /** Bank name: letters, numbers, spaces, hyphens only. */
    protected const BANK_NAME_PATTERN = '/^[\pL\pN\s\-]+$/u';

    /** IFSC: alphanumeric only (11 chars). */
    protected const IFSC_PATTERN = '/^[A-Za-z0-9]+$/u';

    /**
     * Build an array of validated attributes for create/update.
     */
    protected function validatedData(Request $request, ?Vendor $vendor = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:' . self::NAME_PATTERN,
            ],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'contact_person' => [
                'required',
                'string',
                'max:255',
                'regex:' . self::NAME_PATTERN,
            ],
            'phone' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'address' => [
                'required',
                'string',
                'max:2000',
                'regex:' . self::ADDRESS_PATTERN,
            ],
            'gst_number' => ['nullable', 'string', 'max:15', 'regex:' . self::GST_PATTERN],
            'bank_name' => ['nullable', 'string', 'max:255', 'regex:' . self::BANK_NAME_PATTERN],
            'ifsc_code' => ['nullable', 'string', 'max:11', 'regex:' . self::IFSC_PATTERN],
            'account_number' => ['nullable', 'string', 'max:18', 'regex:/^[0-9]+$/'],
            'licence_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ], [
            'name.regex' => 'Vendor name may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.',
            'contact_person.regex' => 'Contact person may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.',
            'address.regex' => 'Address may only contain letters, numbers, spaces, hyphens, commas, periods and new lines. Special characters are not allowed.',
            'address.max' => 'Address cannot exceed 2000 characters.',
            'phone.regex' => 'The phone number must be exactly 10 digits.',
            'gst_number.regex' => 'GST number may only contain letters and numbers. No special characters.',
            'gst_number.max' => 'GST number cannot exceed 15 characters.',
            'bank_name.regex' => 'Bank name may only contain letters, numbers, spaces and hyphens. No special characters.',
            'bank_name.max' => 'Bank name cannot exceed 255 characters.',
            'ifsc_code.regex' => 'IFSC code may only contain letters and numbers. No special characters.',
            'ifsc_code.max' => 'IFSC code cannot exceed 11 characters.',
            'account_number.regex' => 'Account number must contain only digits.',
            'account_number.max' => 'Account number cannot exceed 18 digits.',
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
