<?php

namespace App\Http\Controllers\Admin\IssueManagement;

use App\DataTables\IssueEscalationMatrixDataTable;
use App\Http\Controllers\Controller;
use App\Models\{
    IssueCategoryMaster,
    IssueCategoryEmployeeMap,
    EmployeeMaster,
};
use App\Support\DataTableRedisCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Auth};

class IssueEscalationMatrixController extends Controller
{
    private const LISTING_CACHE_EPOCH_KEY = 'admin_issue_escalation_matrix_index_list_epoch';

    public static function bumpEscalationMatrixListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'IssueEscalationMatrixController@index');
    }

    /**
     * Display escalation matrix - categories with 3-level hierarchy (employees + days).
     */
    public function index(IssueEscalationMatrixDataTable $dataTable)
    {
        $categories = IssueCategoryMaster::active()->orderBy('issue_category')->get();
        $employees = $this->getEmployeesForDropdown();

        return $dataTable->render('admin.issue_management.escalation_matrix.index', compact('categories', 'employees'));
    }

    /**
     * Store escalation matrix for a category (3 levels).
     * Use only for categories that do not have hierarchy yet. For existing hierarchy, use update().
     */
    public function store(Request $request)
    {
        $empRule = ['required', 'integer', function ($attr, $v, $fail) {
            if (! EmployeeMaster::findByIdOrPkOld($v)) {
                $fail('The selected employee is invalid.');
            }
        }];
        $request->validate([
            'issue_category_master_pk' => 'required|exists:issue_category_master,pk',
            'level1_employee_pk' => $empRule,
            'level1_days' => 'required|integer|min:0',
            'level2_employee_pk' => $empRule,
            'level2_days' => 'required|integer|min:0',
            'level3_employee_pk' => $empRule,
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
            static::bumpEscalationMatrixListCacheEpoch();

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
