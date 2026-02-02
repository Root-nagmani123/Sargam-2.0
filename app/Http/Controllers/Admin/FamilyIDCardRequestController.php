<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\FamilyIDCardExport;
use App\Models\FamilyIDCardRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class FamilyIDCardRequestController extends Controller
{
    /**
     * Display a listing of family ID card requests.
     */
    public function index()
    {
        $activeRequests = FamilyIDCardRequest::latest()
            ->paginate(200);

        $archivedRequests = FamilyIDCardRequest::onlyTrashed()
            ->latest()
            ->paginate(200, ['*'], 'archive_page');

        return view('admin.family_idcard.index', [
            'activeRequests' => $activeRequests,
            'archivedRequests' => $archivedRequests,
        ]);
    }

    /**
     * Show the form for creating a new family ID card request.
     */
    public function create()
    {
        return view('admin.family_idcard.create');
    }

    /**
     * Store newly created family ID card requests (one per appended member).
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|string|max:100',
            'designation' => 'required|string|max:255',
            'card_type' => 'required|string|max:100',
            'section' => 'required|string|max:255',
            'group_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'members' => 'required|array|min:1',
            'members.*.name' => 'required|string|max:255',
            'members.*.relation' => 'nullable|string|max:100',
            'members.*.family_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'members.*.dob' => 'nullable|date',
            'members.*.valid_from' => 'nullable|date',
            'members.*.valid_to' => 'nullable|date',
        ]);

        $employeeId = $request->input('employee_id');
        $employeeName = $request->input('employee_name', $employeeId);
        $designation = $request->input('designation');
        $cardType = $request->input('card_type');
        $section = $request->input('section');
        $createdBy = Auth::id();

        // Group photo (one for all family members)
        $groupPhotoPath = $request->file('group_photo')->store('family_idcard/photos', 'public');

        $members = $request->input('members', []);
        $count = 0;

        foreach ($members as $index => $member) {
            $name = $member['name'] ?? null;
            if (empty(trim($name ?? ''))) {
                continue;
            }

            // Individual photo per family member
            $familyPhotoPath = null;
            if ($request->hasFile('members.' . $index . '.family_photo')) {
                $familyPhotoPath = $request->file('members.' . $index . '.family_photo')->store('family_idcard/photos', 'public');
            }

            FamilyIDCardRequest::create([
                'employee_id' => $employeeId,
                'employee_name' => $employeeName,
                'designation' => $designation,
                'card_type' => $cardType,
                'section' => $section,
                'name' => $name,
                'relation' => ! empty($member['relation']) ? $member['relation'] : null,
                'group_photo' => $groupPhotoPath,
                'family_photo' => $familyPhotoPath,
                'dob' => ! empty($member['dob']) ? $member['dob'] : null,
                'valid_from' => ! empty($member['valid_from']) ? $member['valid_from'] : null,
                'valid_to' => ! empty($member['valid_to']) ? $member['valid_to'] : null,
                'created_by' => $createdBy,
            ]);
            $count++;
        }

        $message = $count === 1
            ? 'Family ID Card request created successfully!'
            : "{$count} Family ID Card requests created successfully!";

        return redirect()
            ->route('admin.family_idcard.index')
            ->with('success', $message);
    }

    /**
     * Display the specified family ID card request.
     */
    public function show(FamilyIDCardRequest $familyIDCardRequest)
    {
        return view('admin.family_idcard.show', ['request' => $familyIDCardRequest]);
    }

    /**
     * Show the form for editing the specified family ID card request.
     */
    public function edit(FamilyIDCardRequest $familyIDCardRequest)
    {
        $existingFamilyMembers = FamilyIDCardRequest::where('created_by', Auth::id())
            ->where('id', '!=', $familyIDCardRequest->id)
            ->latest()
            ->limit(50)
            ->get();

        return view('admin.family_idcard.edit', [
            'request' => $familyIDCardRequest,
            'existingFamilyMembers' => $existingFamilyMembers,
        ]);
    }

    /**
     * Update the specified family ID card request.
     */
    public function update(Request $request, FamilyIDCardRequest $familyIDCardRequest)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string|max:100',
            'designation' => 'required|string|max:255',
            'card_type' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'relation' => 'nullable|string|max:100',
            'section' => 'required|string|max:255',
            'family_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'dob' => 'nullable|date',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'family_member_id' => 'nullable|string|max:100',
            'status' => 'nullable|in:Pending,Approved,Rejected,Issued',
            'remarks' => 'nullable|string',
        ]);

        $validated['employee_name'] = $request->input('employee_name', $validated['employee_id']);
        $validated['updated_by'] = Auth::id();

        if ($request->hasFile('family_photo')) {
            $validated['family_photo'] = $request->file('family_photo')->store('family_idcard/photos', 'public');
        }

        $familyIDCardRequest->update($validated);

        return redirect()
            ->route('admin.family_idcard.show', $familyIDCardRequest)
            ->with('success', 'Family ID Card request updated successfully!');
    }

    /**
     * Remove the specified family ID card request.
     */
    public function destroy(FamilyIDCardRequest $familyIDCardRequest)
    {
        $familyIDCardRequest->delete();

        return redirect()
            ->route('admin.family_idcard.index')
            ->with('success', 'Family ID Card request archived successfully!');
    }

    /**
     * Restore a soft deleted family ID card request.
     */
    public function restore($id)
    {
        $familyIDCardRequest = FamilyIDCardRequest::onlyTrashed()->findOrFail($id);
        $familyIDCardRequest->restore();

        return redirect()
            ->route('admin.family_idcard.index')
            ->with('success', 'Family ID Card request restored successfully!');
    }

    /**
     * Force delete a soft deleted family ID card request permanently.
     */
    public function forceDelete($id)
    {
        $familyIDCardRequest = FamilyIDCardRequest::onlyTrashed()->findOrFail($id);
        $familyIDCardRequest->forceDelete();

        return redirect()
            ->route('admin.family_idcard.index')
            ->with('success', 'Family ID Card request deleted permanently!');
    }

    /**
     * Export family ID card requests to Excel, CSV or PDF.
     */
    public function export(Request $request)
    {
        $tab = $request->get('tab', 'active');
        $format = $request->get('format', 'xlsx');

        if (! in_array($tab, ['active', 'archive', 'all'])) {
            $tab = 'active';
        }

        $filename = 'family_idcard_requests_' . $tab . '_' . now()->format('Y-m-d_His');

        if ($format === 'pdf') {
            $query = match ($tab) {
                'archive' => FamilyIDCardRequest::onlyTrashed()->latest(),
                'all' => FamilyIDCardRequest::withTrashed()->latest(),
                default => FamilyIDCardRequest::latest(),
            };
            $requests = $query->get();

            $pdf = Pdf::loadView('admin.family_idcard.export_pdf', [
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
                new FamilyIDCardExport($tab),
                $filename . '.csv',
                \Maatwebsite\Excel\Excel::CSV
            );
        }

        return Excel::download(
            new FamilyIDCardExport($tab),
            $filename . '.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }
}
