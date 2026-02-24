<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\EmployeeIDCardExport;
use App\Models\EmployeeIDCardRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeIDCardRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $with = [
            'employee:pk,first_name,last_name,designation_master_pk',
            'employee.designation:pk,designation_name',
            'approvals:pk,security_parm_id_apply_pk,status,approval_emp_pk,created_date,approval_remarks',
            'approvals.approver:pk,first_name,last_name',
        ];
        $columns = ['pk', 'emp_id_apply', 'employee_master_pk', 'id_status', 'created_date', 'card_valid_from', 'card_valid_to', 'id_card_no', 'id_photo_path', 'joining_letter_path', 'mobile_no', 'telephone_no', 'blood_group', 'card_type', 'permanent_type', 'perm_sub_type', 'remarks', 'created_by', 'employee_dob'];
        $perPage = 25;
        $filter = $request->get('filter', 'active');
        if (! in_array($filter, ['active', 'archive', 'all'], true)) {
            $filter = 'active';
        }

        // Permanent
        $permQuery = SecurityParmIdApply::select($columns)->with($with)->orderBy('created_date', 'desc');
        if ($filter === 'active') {
            $permQuery->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING);
        } elseif ($filter === 'archive') {
            $permQuery->whereIn('id_status', [SecurityParmIdApply::ID_STATUS_APPROVED, SecurityParmIdApply::ID_STATUS_REJECTED]);
        }
        $permRows = $permQuery->get();

        // Contractual
        $contCols = ['pk', 'emp_id_apply', 'employee_name', 'designation_name', 'id_status', 'created_date', 'card_valid_from', 'card_valid_to', 'id_card_no', 'id_photo_path', 'mobile_no', 'telephone_no', 'blood_group', 'permanent_type', 'perm_sub_type', 'remarks', 'created_by', 'employee_dob', 'vender_name', 'father_name', 'doc_path'];
        $contQuery = DB::table('security_con_oth_id_apply')->select($contCols)->orderBy('created_date', 'desc');
        if ($filter === 'active') {
            $contQuery->where('id_status', 1);
        } elseif ($filter === 'archive') {
            $contQuery->whereIn('id_status', [2, 3]);
        }
        $contRows = $contQuery->get();

        $permDto = $permRows->map(fn ($r) => IdCardSecurityMapper::toEmployeeRequestDto($r));
        $contDto = $contRows->map(fn ($r) => IdCardSecurityMapper::toContractualRequestDto($r));
        $merged = $permDto->concat($contDto)->sortByDesc('created_at')->values();

        // Date range filter (created_date)
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        if ($dateFrom) {
            try {
                $from = \Carbon\Carbon::parse($dateFrom)->startOfDay();
                $merged = $merged->filter(fn ($r) => $r->created_at && $r->created_at->gte($from))->values();
            } catch (\Exception $e) {
            }
        }
        if ($dateTo) {
            try {
                $to = \Carbon\Carbon::parse($dateTo)->endOfDay();
                $merged = $merged->filter(fn ($r) => $r->created_at && $r->created_at->lte($to))->values();
            } catch (\Exception $e) {
            }
        }

        // Name search (case-insensitive)
        $search = trim($request->get('search', ''));
        if ($search !== '') {
            $searchLower = mb_strtolower($search);
            $merged = $merged->filter(function ($r) use ($searchLower) {
                $name = mb_strtolower($r->name ?? '');
                return str_contains($name, $searchLower);
            })->values();
        }

        $allRequests = $merged->values();
        $activeCollection = $allRequests
            ->filter(fn ($r) => ($r->status ?? '') === 'Pending')
            ->values();
        $archivedCollection = $allRequests
            ->filter(fn ($r) => in_array(($r->status ?? ''), ['Approved', 'Rejected'], true))
            ->values();
        $duplicationCollection = $allRequests
            ->filter(fn ($r) => in_array(($r->request_for ?? ''), ['Replacement', 'Duplication'], true))
            ->values();
        $extensionCollection = $allRequests
            ->filter(fn ($r) => ($r->request_for ?? '') === 'Extension')
            ->values();

        $paginate = function ($items, int $page, string $pageName) use ($perPage) {
            $paginator = new LengthAwarePaginator(
                $items->forPage($page, $perPage)->values(),
                $items->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'pageName' => $pageName]
            );
            $paginator->withQueryString();
            return $paginator;
        };

        $activeRequests = $paginate($activeCollection, (int) $request->get('active_page', $request->get('page', 1)), 'active_page');
        $archivedRequests = $paginate($archivedCollection, (int) $request->get('archive_page', 1), 'archive_page');
        $duplicationRequests = $paginate($duplicationCollection, (int) $request->get('duplication_page', 1), 'duplication_page');
        $extensionRequests = $paginate($extensionCollection, (int) $request->get('extension_page', 1), 'extension_page');
        $requests = match ($filter) {
            'archive' => $archivedRequests,
            'all' => $paginate($allRequests, (int) $request->get('page', 1), 'page'),
            default => $activeRequests,
        };

        return view('admin.employee_idcard.index', [
            'requests' => $requests,
            'activeRequests' => $activeRequests,
            'archivedRequests' => $archivedRequests,
            'duplicationRequests' => $duplicationRequests,
            'extensionRequests' => $extensionRequests,
            'filter' => $filter,
            'dateFrom' => $dateFrom ?? '',
            'dateTo' => $dateTo ?? '',
            'search' => $search ?? '',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.employee_idcard.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_type' => 'required|in:Permanent Employee,Contractual Employee',
            'card_type' => 'nullable|string|max:100',
            'sub_type' => 'nullable|string|max:100',
            'request_for' => 'nullable|string|max:100|in:Own ID Card,Family ID Card,Replacement,Duplication,Extension',
            'duplication_reason' => 'nullable|string|in:Expired Card,Lost,Damage',
            'name' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'father_name' => 'nullable|string|max:255',
            'academy_joining' => 'nullable|date',
            'id_card_valid_upto' => 'nullable|string|max:50',
            'id_card_valid_from' => 'nullable|string|max:50',
            'id_card_number' => 'nullable|string|max:50',
            'mobile_number' => 'nullable|string|max:20',
            'telephone_number' => 'nullable|string|max:20',
            'blood_group' => 'nullable|string|max:10',
            'section' => 'nullable|string|max:255',
            'approval_authority' => 'nullable|string|max:255',
            'vendor_organization_name' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'joining_letter' => 'required_if:employee_type,Permanent Employee|nullable|mimes:pdf,doc,docx|max:5120',
            'fir_receipt' => 'required_if:duplication_reason,Lost|nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
            'payment_receipt' => 'nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
            'documents' => 'nullable|mimes:pdf,doc,docx|max:5120',
            'remarks' => 'nullable|string',
        ], [
            'fir_receipt.required_if' => 'FIR Receipt is required when the card is reported as Lost.',
        ]);

        $validated['created_by'] = Auth::id();

        // Handle file uploads
        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('idcard/photos', 'public');
        }

        if ($request->hasFile('joining_letter')) {
            $validated['joining_letter'] = $request->file('joining_letter')->store('idcard/joining_letters', 'public');
        }

        if ($request->hasFile('fir_receipt')) {
            $validated['fir_receipt'] = $request->file('fir_receipt')->store('idcard/fir_receipts', 'public');
        }

        if ($request->hasFile('payment_receipt')) {
            $validated['payment_receipt'] = $request->file('payment_receipt')->store('idcard/payment_receipts', 'public');
        }

        if ($request->hasFile('documents')) {
            $validated['documents'] = $request->file('documents')->store('idcard/documents', 'public');
        }

        EmployeeIDCardRequest::create($validated);

        return redirect()
            ->route('admin.employee_idcard.index')
            ->with('success', 'Employee ID Card request created successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\EmployeeIDCardRequest  $employeeIDCardRequest
     * @return \Illuminate\Http\Response
     */
    public function show(EmployeeIDCardRequest $employeeIDCardRequest)
    {
        $employeeIDCardRequest->load(['approver1', 'approver2']);
        return view('admin.employee_idcard.show', ['request' => $employeeIDCardRequest]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EmployeeIDCardRequest  $employeeIDCardRequest
     * @return \Illuminate\Http\Response
     */
    public function edit(EmployeeIDCardRequest $employeeIDCardRequest)
    {
        return view('admin.employee_idcard.edit', ['request' => $employeeIDCardRequest]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EmployeeIDCardRequest  $employeeIDCardRequest
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EmployeeIDCardRequest $employeeIDCardRequest)
    {
        $validated = $request->validate([
            'employee_type' => 'required|in:Permanent Employee,Contractual Employee',
            'card_type' => 'nullable|string|max:100',
            'sub_type' => 'nullable|string|max:100',
            'request_for' => 'nullable|string|max:100|in:Own ID Card,Family ID Card,Replacement,Duplication,Extension',
            'duplication_reason' => 'nullable|string|in:Expired Card,Lost,Damage',
            'name' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'father_name' => 'nullable|string|max:255',
            'academy_joining' => 'nullable|date',
            'id_card_valid_upto' => 'nullable|string|max:50',
            'id_card_valid_from' => 'nullable|string|max:50',
            'id_card_number' => 'nullable|string|max:50',
            'mobile_number' => 'nullable|string|max:20',
            'telephone_number' => 'nullable|string|max:20',
            'blood_group' => 'nullable|string|max:10',
            'section' => 'nullable|string|max:255',
            'approval_authority' => 'nullable|string|max:255',
            'vendor_organization_name' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'joining_letter' => 'required_if:employee_type,Permanent Employee|nullable|mimes:pdf,doc,docx|max:5120',
            'fir_receipt' => 'nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
            'payment_receipt' => 'nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
            'documents' => 'nullable|mimes:pdf,doc,docx|max:5120',
            'status' => 'nullable|in:Pending,Approved,Rejected,Issued',
            'remarks' => 'nullable|string',
        ]);

        $validated['updated_by'] = Auth::id();

        // Handle file uploads
        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('idcard/photos', 'public');
        }

        if ($request->hasFile('joining_letter')) {
            $validated['joining_letter'] = $request->file('joining_letter')->store('idcard/joining_letters', 'public');
        }

        if ($request->hasFile('fir_receipt')) {
            $validated['fir_receipt'] = $request->file('fir_receipt')->store('idcard/fir_receipts', 'public');
        }

        if ($request->hasFile('payment_receipt')) {
            $validated['payment_receipt'] = $request->file('payment_receipt')->store('idcard/payment_receipts', 'public');
        }

        if ($request->hasFile('documents')) {
            $validated['documents'] = $request->file('documents')->store('idcard/documents', 'public');
        }

        $employeeIDCardRequest->update($validated);

        return redirect()
            ->route('admin.employee_idcard.show', $employeeIDCardRequest)
            ->with('success', 'Employee ID Card request updated successfully!');
    }

    /**
     * Amend Duplication/Extension fields only (modal update).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EmployeeIDCardRequest  $employeeIDCardRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function amendDuplicationExtension(Request $request, EmployeeIDCardRequest $employeeIDCardRequest)
    {
        $validated = $request->validate([
            'duplication_reason' => 'nullable|string|in:Expired Card,Lost,Damage',
            'id_card_valid_from' => 'nullable|string|max:50',
            'id_card_valid_upto' => 'nullable|string|max:50',
            'id_card_number' => 'nullable|string|max:50',
            'fir_receipt' => [
                'nullable',
                'mimes:pdf,doc,docx,jpeg,png,jpg',
                'max:5120',
                function ($attribute, $value, $fail) use ($request, $employeeIDCardRequest) {
                    if ($request->duplication_reason === 'Lost' && ! $request->hasFile('fir_receipt') && ! $employeeIDCardRequest->fir_receipt) {
                        $fail('FIR Receipt is required when the card is reported as Lost.');
                    }
                },
            ],
            'payment_receipt' => 'nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
        ]);

        $employeeIDCardRequest->updated_by = Auth::id();

        if (array_key_exists('duplication_reason', $validated)) {
            $employeeIDCardRequest->duplication_reason = $validated['duplication_reason'];
        }
        if (array_key_exists('id_card_valid_from', $validated)) {
            $employeeIDCardRequest->id_card_valid_from = $validated['id_card_valid_from'];
        }
        if (array_key_exists('id_card_valid_upto', $validated)) {
            $employeeIDCardRequest->id_card_valid_upto = $validated['id_card_valid_upto'];
        }
        if (array_key_exists('id_card_number', $validated)) {
            $employeeIDCardRequest->id_card_number = $validated['id_card_number'];
        }

        if ($request->hasFile('fir_receipt')) {
            $employeeIDCardRequest->fir_receipt = $request->file('fir_receipt')->store('idcard/fir_receipts', 'public');
        }
        if ($request->hasFile('payment_receipt')) {
            $employeeIDCardRequest->payment_receipt = $request->file('payment_receipt')->store('idcard/payment_receipts', 'public');
        }

        $employeeIDCardRequest->save();

        return response()->json([
            'success' => true,
            'message' => 'Duplication/Extension details updated successfully.',
            'data' => [
                'duplication_reason' => $employeeIDCardRequest->duplication_reason ?? '',
                'id_card_valid_from' => $employeeIDCardRequest->id_card_valid_from ?? '',
                'id_card_valid_upto' => $employeeIDCardRequest->id_card_valid_upto ?? '',
                'id_card_number' => $employeeIDCardRequest->id_card_number ?? '',
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EmployeeIDCardRequest  $employeeIDCardRequest
     * @return \Illuminate\Http\Response
     */
    public function destroy(EmployeeIDCardRequest $employeeIDCardRequest)
    {
        $employeeIDCardRequest->delete();

        return redirect()
            ->route('admin.employee_idcard.index')
            ->with('success', 'Employee ID Card request archived successfully!');
    }

    /**
     * Restore a soft deleted resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        $employeeIDCardRequest = EmployeeIDCardRequest::onlyTrashed()->findOrFail($id);
        $employeeIDCardRequest->restore();

        return redirect()
            ->route('admin.employee_idcard.index')
            ->with('success', 'Employee ID Card request restored successfully!');
    }

    /**
     * Force delete a soft deleted resource permanently.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function forceDelete($id)
    {
        $employeeIDCardRequest = EmployeeIDCardRequest::onlyTrashed()->findOrFail($id);
        $employeeIDCardRequest->forceDelete();

        return redirect()
            ->route('admin.employee_idcard.index')
            ->with('success', 'Employee ID Card request deleted permanently!');
    }

    /**
     * Export ID card requests to Excel or CSV.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $tab = $request->get('tab', 'active');
        $format = $request->get('format', 'xlsx');

        if (! in_array($tab, ['active', 'archive', 'duplication', 'extension', 'all'])) {
            $tab = 'active';
        }

        $filename = 'employee_idcard_requests_' . $tab . '_' . now()->format('Y-m-d_His');

        if ($format === 'pdf') {
            $query = match ($tab) {
                'archive' => EmployeeIDCardRequest::onlyTrashed()->latest(),
                'duplication' => EmployeeIDCardRequest::whereIn('request_for', ['Replacement', 'Duplication'])->latest(),
                'extension' => EmployeeIDCardRequest::where('request_for', 'Extension')->latest(),
                'all' => EmployeeIDCardRequest::withTrashed()->latest(),
                default => EmployeeIDCardRequest::latest(),
            };
            $requests = $query->get();

            $pdf = Pdf::loadView('admin.employee_idcard.export_pdf', [
                'requests' => $requests,
                'tab' => $tab,
                'export_date' => now()->format('d/m/Y H:i'),
            ])
                ->setPaper('a4', 'landscape')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);

            return $pdf->download($filename . '.pdf');
        }

        if ($format === 'csv') {
            return Excel::download(
                new EmployeeIDCardExport($tab),
                $filename . '.csv',
                \Maatwebsite\Excel\Excel::CSV
            );
        }

        return Excel::download(
            new EmployeeIDCardExport($tab),
            $filename . '.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    /**
     * Parse date string (Y-m-d from HTML date input, or d/m/Y from text) to Y-m-d.
     */
    private static function parseDateToYmd(?string $value): ?string
    {
        $value = trim($value ?? '');
        if ($value === '') {
            return null;
        }
        // HTML5 date input sends Y-m-d (e.g. 2025-12-31)
        try {
            $date = \Carbon\Carbon::createFromFormat('Y-m-d', $value);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        } catch (\Exception $e) {
        }
        // Text field placeholder DD/MM/YYYY (e.g. 31/12/2025)
        try {
            $date = \Carbon\Carbon::createFromFormat('d/m/Y', $value);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        } catch (\Exception $e) {
        }
        // Fallback: Carbon::parse (handles many formats)
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Export ID card requests to Excel or CSV.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $tab = $request->get('tab', 'active');
        $format = $request->get('format', 'xlsx');

        if (! in_array($tab, ['active', 'archive', 'all'])) {
            $tab = 'active';
        }

        $filename = 'employee_idcard_requests_' . $tab . '_' . now()->format('Y-m-d_His');

        if ($format === 'csv') {
            return Excel::download(
                new EmployeeIDCardExport($tab),
                $filename . '.csv',
                \Maatwebsite\Excel\Excel::CSV
            );
        }

        return Excel::download(
            new EmployeeIDCardExport($tab),
            $filename . '.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }
}

