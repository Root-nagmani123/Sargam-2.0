<?php

namespace App\Http\Controllers\Admin\IssueManagement;

use App\Http\Controllers\Controller;
use App\Models\{
    IssueLogManagement,
    IssueCategoryMaster,
    IssueSubCategoryMaster,
    IssuePriorityMaster,
    IssueReproducibilityMaster,
    IssueLogSubCategoryMap,
    IssueLogBuildingMap,
    IssueLogHostelMap,
    IssueLogStatus,
    BuildingMaster,
    HostelBuildingMaster
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Auth, Storage, Schema, Log};
use Carbon\Carbon;

class IssueManagementController extends Controller
{
    /**
     * Display a listing of all issues.
     */
    public function index(Request $request)
    {
        $query = IssueLogManagement::with([
            'category',
            'priority',
            'reproducibility',
            'subCategoryMappings.subCategory',
            'buildingMapping.building',
            'hostelMapping.hostelBuilding',
            'statusHistory'
        ])->orderBy('created_date', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('issue_status', $request->status);
        }

        // Filter by category
        if ($request->has('category') && $request->category !== '') {
            $query->where('issue_category_master_pk', $request->category);
        }

        // Filter by priority
        if ($request->has('priority') && $request->priority !== '') {
            $query->where('issue_priority_master_pk', $request->priority);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from !== '') {
            $query->whereDate('created_date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to !== '') {
            $query->whereDate('created_date', '<=', $request->date_to);
        }

        $issues = $query->paginate(20);

        $categories = IssueCategoryMaster::active()->get();
        $priorities = IssuePriorityMaster::active()->ordered()->get();

        return view('admin.issue_management.index', compact('issues', 'categories', 'priorities'));
    }

    /**
     * Display issues reported on behalf (CENTCOM).
     */
    public function centcom(Request $request)
    {
        $query = IssueLogManagement::reportedOnBehalf()
            ->with([
                'category',
                'priority',
                'reproducibility',
                'subCategoryMappings.subCategory',
                'buildingMapping.building',
                'hostelMapping.hostelBuilding',
                'statusHistory'
            ])->orderBy('created_date', 'desc');

        // Apply filters similar to index
        if ($request->has('status') && $request->status !== '') {
            $query->where('issue_status', $request->status);
        }

        if ($request->has('category') && $request->category !== '') {
            $query->where('issue_category_master_pk', $request->category);
        }

        $issues = $query->paginate(20);

        $categories = IssueCategoryMaster::active()->get();
        $priorities = IssuePriorityMaster::active()->ordered()->get();

        return view('admin.issue_management.centcom', compact('issues', 'categories', 'priorities'));
    }

    /**
     * Show the form for creating a new issue.
     */
    public function create()
    {
        $categories = IssueCategoryMaster::active()->get();
        $priorities = IssuePriorityMaster::active()->ordered()->get();
        $reproducibilities = IssueReproducibilityMaster::active()->get();
        
        // Make building/hostel queries conditional based on table existence
        $buildings = collect([]);
        $hostels = collect([]);
        
        try {
            if (Schema::hasTable('building_master')) {
                $buildings = BuildingMaster::where('status', 1)->get();
            }
        } catch (\Exception $e) {
            \Log::warning('Building master table not accessible: ' . $e->getMessage());
        }
        
        try {
            if (Schema::hasTable('hostel_building_master')) {
                $hostels = HostelBuildingMaster::where('status', 1)->get();
            }
        } catch (\Exception $e) {
            \Log::warning('Hostel building master table not accessible: ' . $e->getMessage());
        }

        return view('admin.issue_management.create', compact(
            'categories',
            'priorities',
            'reproducibilities',
            'buildings',
            'hostels'
        ));
    }

    /**
     * Store a newly created issue in storage.
     */
    public function store(Request $request)
    {
        $validationRules = [
            'issue_category_master_pk' => 'required|exists:issue_category_master,pk',
            'issue_priority_master_pk' => 'required|exists:issue_priority_master,pk',
            'issue_reproducibility_master_pk' => 'required|exists:issue_reproducibility_master,pk',
            'description' => 'required|string',
            'location' => 'nullable|string|max:500',
            'behalf' => 'required|in:0,1',
            'sub_categories' => 'nullable|array',
            'sub_categories.*' => 'exists:issue_sub_category_master,pk',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'location_type' => 'required|in:building,hostel,other',
            'floor_name' => 'nullable|string',
            'room_name' => 'nullable|string',
        ];
        
        // Add conditional validation for building/hostel only if tables exist
        if ($request->location_type === 'building' && Schema::hasTable('building_master')) {
            $validationRules['building_master_pk'] = 'required_if:location_type,building|nullable';
        }
        
        if ($request->location_type === 'hostel' && Schema::hasTable('hostel_building_master')) {
            $validationRules['hostel_building_master_pk'] = 'required_if:location_type,hostel|nullable';
        }
        
        $request->validate($validationRules);

        DB::beginTransaction();
        try {
            // Handle document upload
            $documentPath = null;
            if ($request->hasFile('document')) {
                $documentPath = $request->file('document')->store('issue_documents', 'public');
            }

            // Handle image upload
            $imageName = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = $image->getClientOriginalName();
                // Store with pk prefix after creation
            }

            // Determine issue_logger and behalf based on who is logging
            $createdBy = Auth::id();
            $issueLogger = $request->behalf == 0 ? $createdBy : $createdBy; // In centcom, creator logs on behalf
            
            // Create issue log
            $issue = IssueLogManagement::create([
                'issue_category_master_pk' => $request->issue_category_master_pk,
                'issue_priority_master_pk' => $request->issue_priority_master_pk,
                'issue_reproducibility_master_pk' => $request->issue_reproducibility_master_pk,
                'description' => $request->description,
                'location' => $request->location,
                'document' => $documentPath,
                'issue_status' => IssueLogManagement::STATUS_REPORTED,
                'remark' => $request->remark,
                'created_by' => $createdBy,
                'created_date' => now(),
                'created_time' => now()->format('H:i:s'),
                'issue_logger' => $issueLogger,
                'behalf' => $request->behalf,
                'notification_status' => 0,
                'image_name' => $imageName,
            ]);

            // Store image with issue pk prefix if uploaded
            if ($request->hasFile('image') && $imageName) {
                $image = $request->file('image');
                $finalImageName = $issue->pk . '_' . $imageName;
                $image->storeAs('issue_images', $finalImageName, 'public');
            }

            // Map sub-categories
            if ($request->has('sub_categories') && is_array($request->sub_categories)) {
                foreach ($request->sub_categories as $subCategoryPk) {
                    $subCategory = IssueSubCategoryMaster::find($subCategoryPk);
                    if ($subCategory) {
                        IssueLogSubCategoryMap::create([
                            'issue_log_management_pk' => $issue->pk,
                            'issue_category_master_pk' => $request->issue_category_master_pk,
                            'issue_sub_category_master_pk' => $subCategoryPk,
                            'sub_category_name' => $subCategory->issue_sub_category,
                        ]);
                    }
                }
            }

            // Map location (building or hostel)
            if ($request->location_type === 'building' && $request->building_master_pk) {
                try {
                    IssueLogBuildingMap::create([
                        'issue_log_management_pk' => $issue->pk,
                        'building_master_pk' => $request->building_master_pk,
                        'floor_name' => $request->floor_name,
                        'room_name' => $request->room_name,
                    ]);
                } catch (\Exception $e) {
                    // Log error but continue - building may not exist
                    \Log::warning('Building mapping failed: ' . $e->getMessage());
                }
            } elseif ($request->location_type === 'hostel' && $request->hostel_building_master_pk) {
                try {
                    IssueLogHostelMap::create([
                        'issue_log_management_pk' => $issue->pk,
                        'hostel_building_master_pk' => $request->hostel_building_master_pk,
                        'floor_name' => $request->floor_name,
                        'room_name' => $request->room_name,
                    ]);
                } catch (\Exception $e) {
                    // Log error but continue - hostel may not exist
                    \Log::warning('Hostel mapping failed: ' . $e->getMessage());
                }
            }

            // Create initial status entry
            IssueLogStatus::create([
                'issue_log_management_pk' => $issue->pk,
                'issue_date' => now(),
                'created_by' => Auth::id(),
                'issue_status' => IssueLogManagement::STATUS_REPORTED,
                'remarks' => 'Issue reported',
            ]);

            DB::commit();

            return redirect()->route('admin.issue-management.show', $issue->pk)
                ->with('success', 'Issue logged successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()
                ->with('error', 'Failed to log issue: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified issue.
     */
    public function show($id)
    {
        $issue = IssueLogManagement::with([
            'category',
            'priority',
            'reproducibility',
            'subCategoryMappings.subCategory',
            'buildingMapping.building',
            'hostelMapping.hostelBuilding',
            'statusHistory.creator',
            'escalationHistory',
            'creator',
            'logger'
        ])->findOrFail($id);

        return view('admin.issue_management.show', compact('issue'));
    }

    /**
     * Show the form for editing the specified issue.
     */
    public function edit($id)
    {
        $issue = IssueLogManagement::with([
            'subCategoryMappings',
            'buildingMapping',
            'hostelMapping'
        ])->findOrFail($id);

        $categories = IssueCategoryMaster::active()->get();
        $priorities = IssuePriorityMaster::active()->ordered()->get();
        $reproducibilities = IssueReproducibilityMaster::active()->get();
        
        // Make building/hostel queries conditional
        $buildings = collect([]);
        $hostels = collect([]);
        
        try {
            if (Schema::hasTable('building_master')) {
                $buildings = BuildingMaster::where('status', 1)->get();
            }
        } catch (\Exception $e) {
            \Log::warning('Building master table not accessible: ' . $e->getMessage());
        }
        
        try {
            if (Schema::hasTable('hostel_building_master')) {
                $hostels = HostelBuildingMaster::where('status', 1)->get();
            }
        } catch (\Exception $e) {
            \Log::warning('Hostel building master table not accessible: ' . $e->getMessage());
        }

        return view('admin.issue_management.edit', compact(
            'issue',
            'categories',
            'priorities',
            'reproducibilities',
            'buildings',
            'hostels'
        ));
    }

    /**
     * Update the specified issue in storage.
     */
    public function update(Request $request, $id)
    {
        $issue = IssueLogManagement::findOrFail($id);

        $request->validate([
            'issue_status' => 'required|in:0,1,2,3,6',
            'remark' => 'nullable|string',
            'assigned_to' => 'nullable|string',
            'assigned_to_contact' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $issue->issue_status;

            // Update issue
            $issue->update([
                'issue_status' => $request->issue_status,
                'remark' => $request->remark,
                'assigned_to' => $request->assigned_to,
                'assigned_to_contact' => $request->assigned_to_contact,
                'updated_by' => Auth::id(),
                'updated_date' => now(),
            ]);

            // If status changed, create status history
            if ($oldStatus != $request->issue_status) {
                IssueLogStatus::create([
                    'issue_log_management_pk' => $issue->pk,
                    'issue_date' => now(),
                    'created_by' => Auth::id(),
                    'issue_status' => $request->issue_status,
                    'remarks' => $request->remark ?? 'Status updated',
                ]);

                // If completed, set clear date/time
                if ($request->issue_status == IssueLogManagement::STATUS_COMPLETED) {
                    $issue->update([
                        'clear_date' => now(),
                        'clear_time' => now()->format('H:i:s'),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('admin.issue-management.show', $issue->pk)
                ->with('success', 'Issue updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()
                ->with('error', 'Failed to update issue: ' . $e->getMessage());
        }
    }

    /**
     * Get sub-categories based on category (AJAX).
     */
    public function getSubCategories($categoryId)
    {
        $subCategories = IssueSubCategoryMaster::byCategory($categoryId)
            ->active()
            ->orderBy('issue_sub_category')
            ->get();

        return response()->json($subCategories);
    }

    /**
     * Add feedback to an issue.
     */
    public function addFeedback(Request $request, $id)
    {
        $request->validate([
            'feedback' => 'required|string',
        ]);

        $issue = IssueLogManagement::findOrFail($id);

        $issue->update([
            'feedback' => $request->feedback,
            'feedback_status' => 1,
            'updated_by' => Auth::id(),
            'updated_date' => now(),
        ]);

        return back()->with('success', 'Feedback added successfully.');
    }
}
