<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Models\Mess\Vendor;
use App\Support\DataTableRedisCache;
use App\Support\DataTableSearchHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VendorController extends Controller
{
    private const LIST_CACHE_EPOCH_KEY = 'mess_vendor_master_list_epoch';
    private const DT_LIST_EPOCH_KEY = 'mess_vendor_master_dt_list_epoch';

    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'VendorController');
        DataTableRedisCache::bumpListEpoch(self::DT_LIST_EPOCH_KEY, 'VendorController');
    }

    public function index(Request $request)
    {
        if ($request->ajax() && $request->has('draw')) {
            return DataTableRedisCache::serveCachedAjax(
                $request,
                'mess_vendor_master_dt:v1:',
                self::DT_LIST_EPOCH_KEY,
                [
                    'enabled' => 'MESS_VENDOR_MASTER_DATATABLE_CACHE_ENABLED',
                    'seconds' => 'MESS_VENDOR_MASTER_DATATABLE_CACHE_SECONDS',
                ],
                'VendorController@index',
                fn () => $this->buildVendorDatatableResponse($request)
            );
        }

        return view('mess.vendors.index');
    }

    private function buildVendorDatatableResponse(Request $request): JsonResponse
    {
        $query = Vendor::query();

        $draw = (int) $request->input('draw', 0);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        if ($length < 1 || $length > 100) {
            $length = 10;
        }

        $searchTokens = DataTableSearchHelper::tokens((string) $request->input('search.value', ''));

        $recordsTotal = (clone $query)->count();

        if ($searchTokens !== []) {
            $query->where(function ($q) use ($searchTokens) {
                foreach ($searchTokens as $token) {
                    $like = DataTableSearchHelper::likePattern($token);
                    $q->where(function ($inner) use ($like) {
                        $inner->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('contact_person', 'like', $like)
                            ->orWhere('phone', 'like', $like)
                            ->orWhere('address', 'like', $like);
                    });
                }
            });
        }

        $recordsFiltered = (clone $query)->count();

        $paged = clone $query;
        $orderCol = DataTableSearchHelper::orderColumnIndex($request, 0);
        $orderDir = DataTableSearchHelper::orderDirection($request, 'asc');

        switch ($orderCol) {
            case 0:
                $paged->orderBy('name', $orderDir);
                break;
            case 1:
                $paged->orderBy('email', $orderDir);
                break;
            case 2:
                $paged->orderBy('contact_person', $orderDir);
                break;
            case 3:
                $paged->orderBy('phone', $orderDir);
                break;
            case 4:
                $paged->orderBy('address', $orderDir);
                break;
            default:
                $paged->orderByDesc('id');
        }
        $paged->orderByDesc('id');

        if ($length !== -1) {
            $paged->skip($start)->take($length);
        }

        $rows = $paged->get();
        $canDelete = function_exists('hasRole') && (hasRole('Super Admin') || hasRole('Mess-Admin'));

        $data = $rows->map(fn (Vendor $vendor) => $this->buildVendorDatatableRow($vendor, $canDelete))->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * @return string[]
     */
    private function buildVendorDatatableRow(Vendor $vendor, bool $canDelete): array
    {
        $nameCell = '<div class="fw-semibold">' . e($vendor->name) . '</div>';
        $emailCell = e($vendor->email ?? '-');
        $contactPersonCell = e($vendor->contact_person ?? '-');
        $phoneCell = e($vendor->phone ?? '-');
        $addressCell = e($vendor->address ?? '-');

        $dataAttrs = ' data-id="' . (int) $vendor->id . '"'
            . ' data-name="' . e($vendor->name) . '"'
            . ' data-email="' . e($vendor->email ?? '') . '"'
            . ' data-contact-person="' . e($vendor->contact_person ?? '') . '"'
            . ' data-phone="' . e($vendor->phone ?? '') . '"'
            . ' data-address="' . e($vendor->address ?? '') . '"'
            . ' data-gst-number="' . e($vendor->gst_number ?? '') . '"'
            . ' data-bank-name="' . e($vendor->bank_name ?? '') . '"'
            . ' data-ifsc-code="' . e($vendor->ifsc_code ?? '') . '"'
            . ' data-account-number="' . e($vendor->account_number ?? '') . '"';

        $viewBtn = '<button type="button" class="text-primary btn-view-vendor bg-transparent border-0"'
            . $dataAttrs . ' title="View"><i class="material-icons material-symbol-rounded">visibility</i></button>';

        $editBtn = '<button type="button" class="text-primary btn-edit-vendor bg-transparent border-0"'
            . $dataAttrs . ' title="Edit"><i class="material-icons material-symbol-rounded">edit</i></button>';

        $deleteForm = '';
        if ($canDelete) {
            $deleteUrl = route('admin.mess.vendors.destroy', $vendor->id);
            $deleteForm = '<form method="POST" action="' . e($deleteUrl) . '" class="d-inline"'
                . ' onsubmit="return confirm(\'Are you sure you want to delete this vendor?\');">'
                . '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">'
                . '<input type="hidden" name="_method" value="DELETE">'
                . '<button type="submit" class="text-primary bg-transparent border-0 p-0" title="Delete">'
                . '<i class="material-icons material-symbol-rounded">delete</i></button>'
                . '</form>';
        }

        $actions = '<div class="d-flex gap-2 flex-wrap">' . $viewBtn . $editBtn . $deleteForm . '</div>';

        return [
            $nameCell,
            $emailCell,
            $contactPersonCell,
            $phoneCell,
            $addressCell,
            $actions,
        ];
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

        self::bumpListCacheEpoch();

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

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.vendors.index')->with('success', 'Vendor updated successfully');
    }

    public function destroy($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->delete();

        self::bumpListCacheEpoch();

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
            'phone' => ['required', 'string', 'regex:/^[6-9][0-9]{9}$/'],
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
            'phone.regex' => 'The phone number must be 10 digits and start with 6, 7, 8, or 9.',
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
