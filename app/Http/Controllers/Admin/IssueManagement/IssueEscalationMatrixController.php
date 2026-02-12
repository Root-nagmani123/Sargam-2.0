<?php

namespace App\Http\Controllers\Admin\IssueManagement;

use App\Http\Controllers\Controller;
use App\Models\{
    IssueCategoryMaster,
    IssueCategoryEmployeeMap,
    EmployeeMaster
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Auth};

class IssueEscalationMatrixController extends Controller
{
    /**
     * Display escalation matrix - categories with 3-level hierarchy (employees + days).
     */
    public function index()
    {
        $categories = IssueCategoryMaster::active()->orderBy('issue_category')->get();

        $matrix = [];
        foreach ($categories as $category) {
            $levels = IssueCategoryEmployeeMap::where('issue_category_master_pk', $category->pk)
                ->orderBy('priority')
                ->with('employee')
                ->get();

            $level1 = $levels->where('priority', 1)->first();
            $level2 = $levels->where('priority', 2)->first();
            $level3 = $levels->where('priority', 3)->first();

            $matrix[] = [
                'category' => $category,
                'level1' => $level1,
                'level2' => $level2,
                'level3' => $level3,
            ];
        }

        $employees = $this->getEmployeesForDropdown();

        return view('admin.issue_management.escalation_matrix.index', compact('matrix', 'categories', 'employees'));
    }

    /**
     * Store escalation matrix for a category (3 levels).
     * Use only for categories that do not have hierarchy yet. For existing hierarchy, use update().
     */
    public function store(Request $request)
    {
        $request->validate([
            'issue_category_master_pk' => 'required|exists:issue_category_master,pk',
            'level1_employee_pk' => 'required|exists:employee_master,pk',
            'level1_days' => 'required|integer|min:0',
            'level2_employee_pk' => 'required|exists:employee_master,pk',
            'level2_days' => 'required|integer|min:0',
            'level3_employee_pk' => 'required|exists:employee_master,pk',
            'level3_days' => 'required|integer|min:0',
        ]);

        $categoryId = $request->issue_category_master_pk;

        // POST (Add): do not allow insert if category already has hierarchy — ask to use Edit
        // PUT (Update): allow; we will delete existing and re-insert below
        if ($request->isMethod('post') && IssueCategoryEmployeeMap::where('issue_category_master_pk', $categoryId)->exists()) {
            $category = IssueCategoryMaster::find($categoryId);
            $categoryName = $category ? $category->issue_category : $categoryId;
            return redirect()->route('admin.issue-escalation-matrix.index')
                ->with('error', 'This category ("' . $categoryName . '") already has escalation hierarchy configured. Please use Edit to update.');
        }

        DB::beginTransaction();
        try {
            // Update path or fresh insert: remove existing mappings so we can insert 3 levels
            IssueCategoryEmployeeMap::where('issue_category_master_pk', $categoryId)->delete();

            // Insert 3 levels
            $levels = [
                ['employee_pk' => $request->level1_employee_pk, 'days' => $request->level1_days, 'priority' => 1],
                ['employee_pk' => $request->level2_employee_pk, 'days' => $request->level2_days, 'priority' => 2],
                ['employee_pk' => $request->level3_employee_pk, 'days' => $request->level3_days, 'priority' => 3],
            ];

            foreach ($levels as $level) {
                IssueCategoryEmployeeMap::create([
                    'issue_category_master_pk' => $categoryId,
                    'employee_master_pk' => $level['employee_pk'],
                    'days_notify' => $level['days'],
                    'priority' => $level['priority'],
                    'created_by' => Auth::id(),
                    'created_date' => now(),
                ]);
            }

            DB::commit();
            return redirect()->route('admin.issue-escalation-matrix.index')
                ->with('success', 'Escalation matrix saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to save: ' . $e->getMessage());
        }
    }

    /**
     * Update escalation matrix for a category.
     */
    public function update(Request $request, $categoryId)
    {
        $request->merge(['issue_category_master_pk' => $categoryId]);
        return $this->store($request);
    }

    private function getEmployeesForDropdown()
    {
        return DB::table('employee_master as e')
            ->select(
                'e.pk as employee_pk',
                DB::raw("TRIM(CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, ''))) as employee_name")
            )
            ->orderBy('e.first_name')
            ->get();
    }
}
