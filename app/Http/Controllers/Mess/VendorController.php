<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Exports\VendorMasterExport;
use App\Models\Mess\Vendor;
use App\Support\DataTableRedisCache;
use App\Support\DataTableSearchHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Barryvdh\DomPDF\Facade\Pdf;

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

        // Column 0 is the running S. No. — it has no column of its own to sort by,
        // so it sorts on the same key the serial is numbered from (id).
        switch ($orderCol) {
            case 1:
                $paged->orderBy('name', $orderDir);
                break;
            case 2:
                $paged->orderBy('email', $orderDir);
                break;
            case 3:
                $paged->orderBy('contact_person', $orderDir);
                break;
            case 4:
                $paged->orderBy('phone', $orderDir);
                break;
            case 5:
                $paged->orderBy('address', $orderDir);
                break;
            case 0:
            default:
                $paged->orderBy('id', $orderDir);
        }
        $paged->orderByDesc('id');

        if ($length !== -1) {
            $paged->skip($start)->take($length);
        }

        $rows = $paged->get();
        $canDelete = function_exists('hasRole') && (hasRole('Super Admin') || hasRole('Mess-Admin'));

        // Column 0 is a running serial, not the primary key — the same "S. No."
        // the branded export prints. It follows the page, so it always reads 1..n.
        $data = $rows->values()
            ->map(fn (Vendor $vendor, int $i) => $this->buildVendorDatatableRow($vendor, $canDelete, $start + $i + 1))
            ->all();

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
    private function buildVendorDatatableRow(Vendor $vendor, bool $canDelete, int $serial): array
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

        // Icon-over-label row actions, the same vocabulary Store Master uses — the
        // page's .vendor-action-btn CSS was written for this markup.
        $viewBtn = '<button type="button" class="vendor-action-btn text-primary btn-view-vendor"'
            . $dataAttrs . ' title="See" aria-label="See ' . e($vendor->name) . '">'
            . '<i class="material-symbols-rounded" aria-hidden="true">visibility</i><span>See</span></button>';

        $editBtn = '<button type="button" class="vendor-action-btn text-primary btn-edit-vendor"'
            . $dataAttrs . ' title="Edit" aria-label="Edit ' . e($vendor->name) . '">'
            . '<i class="material-symbols-rounded" aria-hidden="true">edit</i><span>Edit</span></button>';

        $deleteForm = '';
        if ($canDelete) {
            $deleteUrl = route('admin.mess.vendors.destroy', $vendor->id);
            // mess-delete-form + no native confirm(): mess.partials.delete-confirm
            // intercepts the submit and shows the branded dialog instead.
            $deleteForm = '<form method="POST" action="' . e($deleteUrl) . '" class="d-inline mess-delete-form"'
                . ' data-confirm-title="Delete Vendor?"'
                . ' data-confirm-message="' . e('“' . $vendor->name . '” will be removed. This action cannot be undone.') . '">'
                . '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">'
                . '<input type="hidden" name="_method" value="DELETE">'
                . '<button type="submit" class="vendor-action-btn text-danger"'
                . ' aria-label="Delete ' . e($vendor->name) . '">'
                . '<i class="material-symbols-rounded" aria-hidden="true">delete</i><span>Delete</span></button>'
                . '</form>';
        }

        $actions = '<div class="d-flex align-items-center vendor-actions">' . $viewBtn . $editBtn . $deleteForm . '</div>';

        return [
            (string) $serial,
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

    /**
     * Branded Vendor Master report — Print (inline PDF) and Download (styled .xlsx).
     * Both carry the official LBSNAA header (emblem, academy line, 75-years logo,
     * blue title band + blue table header). See {@see \App\Exports\VendorMasterExport}.
     */
    public function export(Request $request)
    {
        $format = strtolower((string) $request->get('format', 'excel'));
        if (! in_array($format, ['csv', 'excel', 'xlsx', 'pdf'], true)) {
            $format = 'excel';
        }

        $search = $request->get('search');
        $visibleColumns = $this->parseVisibleColumns($request->get('columns'));

        $export = new VendorMasterExport($search, $visibleColumns);
        $fileName = 'vendor-master-' . now()->format('Y-m-d_H-i-s');

        if ($format === 'pdf') {
            @ini_set('memory_limit', '256M');
            @set_time_limit(120);

            $pdf = Pdf::loadView('mess.vendors.export_pdf', array_merge([
                'headings' => $export->activeHeadings(),
                'rows' => $export->pdfRows(),
                'filterLine' => ($search !== null && trim((string) $search) !== '') ? ('Applied Filters:   Search: ' . trim($search)) : '',
                'printedOn' => now()->format('d-m-Y H:i'),
                'reportTitle' => 'Vendor Master',
            ], $this->buildExportHeaderData()))
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'isPhpEnabled' => true,
                    'dpi' => 96,
                ]);

            // Print opens the PDF inline (?inline=1); a plain request downloads it.
            return $request->boolean('inline')
                ? $pdf->stream($fileName . '.pdf')
                : $pdf->download($fileName . '.pdf');
        }

        // Styled workbook (logos, blue header band, bordered zebra rows) so the
        // download visually matches the Print / PDF layout — a plain CSV can't.
        return Excel::download($export, $fileName . '.xlsx', ExcelFormat::XLSX);
    }

    /**
     * Turn the `columns=0,1,2,…` request param (built from the live DataTable's
     * visible columns) into a clean list of exportable data-column indexes.
     * Only 0..5 are valid — Action (6) is never exported. Returns null when nothing
     * usable is supplied, so the export shows every column.
     *
     * @return array<int,int>|null
     */
    private function parseVisibleColumns($raw): ?array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $cols = array_values(array_unique(array_filter(
            array_map('intval', explode(',', $raw)),
            static fn ($v) => $v >= 0 && $v <= 5
        )));

        return $cols !== [] ? $cols : null;
    }

    /**
     * Branded LBSNAA header assets for the PDF export — the same emblem / Hindi
     * title / 75-years logo used by the official report layout. Vendor Master has no
     * course context, so the course line is left empty.
     *
     * @return array{logoLeft:?string,logoRight:?string,titleHindi:?string,courseName:string,courseDuration:string}
     */
    private function buildExportHeaderData(): array
    {
        $toDataUri = static function (string $path): ?string {
            if (! is_file($path) || ! is_readable($path)) {
                return null;
            }
            $raw = @file_get_contents($path);
            if ($raw === false) {
                return null;
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'svg' => 'image/svg+xml',
                'jpg', 'jpeg' => 'image/jpeg',
                default => 'image/png',
            };

            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        };

        return [
            'logoLeft' => $toDataUri(public_path('admin_assets/images/logos/logo_new.png')),
            'logoRight' => $toDataUri(public_path('admin_assets/images/logos/constitution-75.png'))
                ?: $toDataUri(public_path('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png')),
            'titleHindi' => $toDataUri(public_path('admin_assets/images/logos/lbsnaa-title-hi.png')),
            'courseName' => '',
            'courseDuration' => '',
        ];
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
