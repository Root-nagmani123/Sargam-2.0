<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\EmployeeIDCardExport;
use App\Models\SecurityParmIdApply;
use App\Models\SecurityParmIdApplyApproval;
use App\Models\EmployeeMaster;
use App\Support\IdCardSecurityMapper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * ID Card List & Generate New ID Card - mapped to security_parm_id_apply.
 */
class EmployeeIDCardRequestController extends Controller
{
    public function index()
    {
        $with = [
            'employee:pk,first_name,last_name,designation_master_pk',
            'employee.designation:pk,designation_name',
            'approvals:pk,security_parm_id_apply_pk,status,approval_emp_pk,created_date,approval_remarks',
            'approvals.approver:pk,first_name,last_name',
        ];
        $columns = ['pk', 'emp_id_apply', 'employee_master_pk', 'id_status', 'created_date', 'card_valid_from', 'card_valid_to', 'id_card_no', 'id_photo_path', 'mobile_no', 'telephone_no', 'blood_group', 'card_type', 'remarks', 'created_by', 'employee_dob'];
        $base = fn () => SecurityParmIdApply::select($columns)->with($with)->orderBy('pk', 'desc');

        // simplePaginate avoids slow COUNT(*) on large tables; pagination shows Next/Previous only
        $perPage = 25;
        $activeTotal = SecurityParmIdApply::where('id_status', SecurityParmIdApply::ID_STATUS_PENDING)->count();
        $archivedTotal = SecurityParmIdApply::whereIn('id_status', [SecurityParmIdApply::ID_STATUS_APPROVED, SecurityParmIdApply::ID_STATUS_REJECTED])->count();

        $activeRequests = (clone $base())->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING)
            ->simplePaginate($perPage);
        $archivedRequests = (clone $base())->whereIn('id_status', [SecurityParmIdApply::ID_STATUS_APPROVED, SecurityParmIdApply::ID_STATUS_REJECTED])
            ->simplePaginate($perPage, $columns, 'archive_page');

        $activeRequests->getCollection()->transform(fn ($r) => IdCardSecurityMapper::toEmployeeRequestDto($r));
        $archivedRequests->getCollection()->transform(fn ($r) => IdCardSecurityMapper::toEmployeeRequestDto($r));

        $duplicationRequests = $activeRequests;
        $extensionRequests = $activeRequests;

        return view('admin.employee_idcard.index', [
            'activeRequests' => $activeRequests,
            'archivedRequests' => $archivedRequests,
            'duplicationRequests' => $duplicationRequests,
            'extensionRequests' => $extensionRequests,
            'activeTotal' => $activeTotal,
            'archivedTotal' => $archivedTotal,
        ]);
    }

    public function create()
    {
        return view('admin.employee_idcard.create');
    }

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
            'employee_master_pk' => 'nullable|integer|exists:employee_master,pk',
        ], [
            'fir_receipt.required_if' => 'FIR Receipt is required when the card is reported as Lost.',
        ]);

        $employeePk = $validated['employee_master_pk'] ?? null;
        if (!$employeePk && !empty($validated['name'])) {
            $emp = EmployeeMaster::where(DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))"), 'like', '%' . trim($validated['name']) . '%')->first();
            $employeePk = $emp?->pk;
        }

        $nextId = SecurityParmIdApply::max('pk') + 1;
        $empIdApply = 'PID' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('idcard/photos', 'public');
        }

        $cardValidFrom = null;
        $cardValidTo = null;
        if (!empty($validated['id_card_valid_from'])) {
            $cardValidFrom = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['id_card_valid_from'])->format('Y-m-d');
        }
        if (!empty($validated['id_card_valid_upto'])) {
            $cardValidTo = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['id_card_valid_upto'])->format('Y-m-d');
        }

        SecurityParmIdApply::create([
            'emp_id_apply' => $empIdApply,
            'employee_master_pk' => $employeePk,
            'card_valid_from' => $cardValidFrom,
            'card_valid_to' => $cardValidTo,
            'id_card_no' => $validated['id_card_number'] ?? null,
            'id_status' => SecurityParmIdApply::ID_STATUS_PENDING,
            'remarks' => $validated['remarks'] ?? null,
            'created_by' => Auth::id(),
            'created_date' => now()->format('Y-m-d H:i:s'),
            'id_photo_path' => $photoPath,
            'employee_dob' => $validated['date_of_birth'] ?? null,
            'mobile_no' => $validated['mobile_number'] ?? null,
            'telephone_no' => $validated['telephone_number'] ?? null,
            'blood_group' => $validated['blood_group'] ?? null,
            'card_type' => $validated['card_type'] ?? null,
        ]);

        return redirect()
            ->route('admin.employee_idcard.index')
            ->with('success', 'Employee ID Card request created successfully!');
    }

    public function show($id)
    {
        $row = SecurityParmIdApply::with(['employee.designation', 'approvals.approver'])
            ->where('pk', $id)->firstOrFail();
        $request = IdCardSecurityMapper::toEmployeeRequestDto($row);
        return view('admin.employee_idcard.show', ['request' => $request]);
    }

    public function edit($id)
    {
        $row = SecurityParmIdApply::with(['employee.designation', 'approvals.approver'])
            ->where('pk', $id)->firstOrFail();
        $request = IdCardSecurityMapper::toEmployeeRequestDto($row);
        return view('admin.employee_idcard.edit', ['request' => $request]);
    }

    public function update(Request $request, $id)
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
            'joining_letter' => 'nullable|mimes:pdf,doc,docx|max:5120',
            'fir_receipt' => 'nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
            'payment_receipt' => 'nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
            'documents' => 'nullable|mimes:pdf,doc,docx|max:5120',
            'status' => 'nullable|in:Pending,Approved,Rejected,Issued',
            'remarks' => 'nullable|string',
        ]);

        $row = SecurityParmIdApply::where('pk', $id)->firstOrFail();
        $cardValidFrom = null;
        $cardValidTo = null;
        if (!empty($validated['id_card_valid_from'])) {
            $cardValidFrom = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['id_card_valid_from'])->format('Y-m-d');
        }
        if (!empty($validated['id_card_valid_upto'])) {
            $cardValidTo = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['id_card_valid_upto'])->format('Y-m-d');
        }

        $row->card_valid_from = $cardValidFrom;
        $row->card_valid_to = $cardValidTo;
        $row->id_card_no = $validated['id_card_number'] ?? null;
        $row->remarks = $validated['remarks'] ?? null;
        $row->employee_dob = $validated['date_of_birth'] ?? null;
        $row->mobile_no = $validated['mobile_number'] ?? null;
        $row->telephone_no = $validated['telephone_number'] ?? null;
        $row->blood_group = $validated['blood_group'] ?? null;
        $row->card_type = $validated['card_type'] ?? null;
        if ($request->hasFile('photo')) {
            $row->id_photo_path = $request->file('photo')->store('idcard/photos', 'public');
        }
        $row->save();

        return redirect()
            ->route('admin.employee_idcard.show', $row->pk)
            ->with('success', 'Employee ID Card request updated successfully!');
    }

    public function amendDuplicationExtension(Request $request, $id)
    {
        $validated = $request->validate([
            'duplication_reason' => 'nullable|string|in:Expired Card,Lost,Damage',
            'id_card_valid_from' => 'nullable|string|max:50',
            'id_card_valid_upto' => 'nullable|string|max:50',
            'id_card_number' => 'nullable|string|max:50',
            'fir_receipt' => 'nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
            'payment_receipt' => 'nullable|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
        ]);

        $row = SecurityParmIdApply::where('pk', $id)->firstOrFail();
        if (array_key_exists('id_card_valid_from', $validated) && $validated['id_card_valid_from']) {
            $row->card_valid_from = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['id_card_valid_from'])->format('Y-m-d');
        }
        if (array_key_exists('id_card_valid_upto', $validated) && $validated['id_card_valid_upto']) {
            $row->card_valid_to = \Carbon\Carbon::createFromFormat('d/m/Y', $validated['id_card_valid_upto'])->format('Y-m-d');
        }
        if (array_key_exists('id_card_number', $validated)) {
            $row->id_card_no = $validated['id_card_number'];
        }
        $row->save();

        $dto = IdCardSecurityMapper::toEmployeeRequestDto($row->load(['employee.designation', 'approvals.approver']));
        return response()->json([
            'success' => true,
            'message' => 'Duplication/Extension details updated successfully.',
            'data' => [
                'duplication_reason' => '',
                'id_card_valid_from' => $dto->id_card_valid_from ?? '',
                'id_card_valid_upto' => $dto->id_card_valid_upto ?? '',
                'id_card_number' => $dto->id_card_number ?? '',
            ],
        ]);
    }

    public function destroy($id)
    {
        $row = SecurityParmIdApply::where('pk', $id)->firstOrFail();
        SecurityParmIdApplyApproval::where('security_parm_id_apply_pk', $row->emp_id_apply)->delete();
        $row->delete();

        return redirect()
            ->route('admin.employee_idcard.index')
            ->with('success', 'Employee ID Card request archived successfully!');
    }

    public function restore($id)
    {
        return redirect()->route('admin.employee_idcard.index')
            ->with('info', 'Security table does not use soft delete. Record remains in archive.');
    }

    public function forceDelete($id)
    {
        return redirect()->route('admin.employee_idcard.index')
            ->with('info', 'Security table does not use soft delete.');
    }

    public function export(Request $request)
    {
        $tab = $request->get('tab', 'active');
        $format = $request->get('format', 'xlsx');
        if (!in_array($tab, ['active', 'archive', 'duplication', 'extension', 'all'])) {
            $tab = 'active';
        }
        $filename = 'employee_idcard_requests_' . $tab . '_' . now()->format('Y-m-d_His');

        $query = match ($tab) {
            'archive' => SecurityParmIdApply::with(['employee.designation', 'approvals.approver'])->whereIn('id_status', [SecurityParmIdApply::ID_STATUS_APPROVED, SecurityParmIdApply::ID_STATUS_REJECTED])->orderBy('pk', 'desc'),
            'duplication', 'extension' => SecurityParmIdApply::with(['employee.designation', 'approvals.approver'])->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING)->orderBy('pk', 'desc'),
            'all' => SecurityParmIdApply::with(['employee.designation', 'approvals.approver'])->orderBy('pk', 'desc'),
            default => SecurityParmIdApply::with(['employee.designation', 'approvals.approver'])->where('id_status', SecurityParmIdApply::ID_STATUS_PENDING)->orderBy('pk', 'desc'),
        };
        $rows = $query->get();
        $requests = $rows->map(fn ($r) => IdCardSecurityMapper::toEmployeeRequestDto($r));

        if ($format === 'pdf') {
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

        return Excel::download(
            new EmployeeIDCardExport($tab, true),
            $filename . ($format === 'csv' ? '.csv' : '.xlsx'),
            $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
        );
    }
}
