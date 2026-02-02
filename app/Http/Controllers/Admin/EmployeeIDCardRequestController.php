<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\EmployeeIDCardExport;
use App\Models\EmployeeIDCardRequest;
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
        // Get active requests (not deleted)
        $activeRequests = EmployeeIDCardRequest::latest()
            ->paginate(15);
        
        // Get archived requests (soft deleted)
        $archivedRequests = EmployeeIDCardRequest::onlyTrashed()
            ->latest()
            ->paginate(15, ['*'], 'archive_page');
        
        return view('admin.employee_idcard.index', [
            'activeRequests' => $activeRequests,
            'archivedRequests' => $archivedRequests
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
            'request_for' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'father_name' => 'nullable|string|max:255',
            'academy_joining' => 'nullable|date',
            'id_card_valid_upto' => 'nullable|string|max:50',
            'mobile_number' => 'nullable|string|max:20',
            'telephone_number' => 'nullable|string|max:20',
            'blood_group' => 'nullable|string|max:10',
            'section' => 'nullable|string|max:255',
            'approval_authority' => 'nullable|string|max:255',
            'vendor_organization_name' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'documents' => 'nullable|mimes:pdf,doc,docx|max:5120',
            'remarks' => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();

        // Handle file uploads
        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('idcard/photos', 'public');
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
            'request_for' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'father_name' => 'nullable|string|max:255',
            'academy_joining' => 'nullable|date',
            'id_card_valid_upto' => 'nullable|string|max:50',
            'mobile_number' => 'nullable|string|max:20',
            'telephone_number' => 'nullable|string|max:20',
            'blood_group' => 'nullable|string|max:10',
            'section' => 'nullable|string|max:255',
            'approval_authority' => 'nullable|string|max:255',
            'vendor_organization_name' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'documents' => 'nullable|mimes:pdf,doc,docx|max:5120',
            'status' => 'nullable|in:Pending,Approved,Rejected,Issued',
            'remarks' => 'nullable|string',
        ]);

        $validated['updated_by'] = Auth::id();

        // Handle file uploads
        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('idcard/photos', 'public');
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

