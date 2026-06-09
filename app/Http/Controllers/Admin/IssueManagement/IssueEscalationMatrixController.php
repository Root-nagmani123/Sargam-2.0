<?php

namespace App\Http\Controllers\Admin\IssueManagement;

use App\Http\Controllers\Controller;
use App\Models\{
    IssueCategoryMaster,
    IssueCategoryEmployeeMap,
    EmployeeMaster,
};
use App\Support\DataTableRedisCache;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Auth};

class IssueEscalationMatrixController extends Controller
{
    private const LISTING_CACHE_EPOCH_KEY = 'admin_issue_escalation_matrix_index_list_epoch';

    public static function bumpEscalationMatrixListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LISTING_CACHE_EPOCH_KEY, 'IssueEscalationMatrixController@index');
    }

    /**
     * Build matrix + categories (one categories query + one maps query — no per-category N+1).
     *
     * @return array{matrix: array<int, array{category: IssueCategoryMaster, level1: IssueCategoryEmployeeMap|null, level2: IssueCategoryEmployeeMap|null, level3: IssueCategoryEmployeeMap|null}>, categories: Collection<int, IssueCategoryMaster>}
     */
    private function buildMatrixData(): array
    {
        $categories = IssueCategoryMaster::active()->orderBy('issue_category')->get();
        $ids = $categories->pluck('pk')->all();
        if ($ids === []) {
            return ['matrix' => [], 'categories' => $categories];
        }

        $allLevels = IssueCategoryEmployeeMap::query()
            ->whereIn('issue_category_master_pk', $ids)
            ->orderBy('priority')
            ->with('employee')
            ->get()
            ->groupBy('issue_category_master_pk');

        $matrix = [];
        foreach ($categories as $category) {
            $levels = $allLevels->get($category->pk, collect());
            $matrix[] = [
                'category' => $category,
                'level1' => $levels->firstWhere('priority', 1),
                'level2' => $levels->firstWhere('priority', 2),
                'level3' => $levels->firstWhere('priority', 3),
            ];
        }

        return ['matrix' => $matrix, 'categories' => $categories];
    }

    /**
     * @param  array<int, array{category: IssueCategoryMaster, level1: IssueCategoryEmployeeMap|null, level2: IssueCategoryEmployeeMap|null, level3: IssueCategoryEmployeeMap|null}>  $matrix
     */
    private function matrixToCacheArray(array $matrix, Collection $categories): array
    {
        $serializeLevel = function (?IssueCategoryEmployeeMap $level): ?array {
            if ($level === null) {
                return null;
            }
            $emp = $level->relationLoaded('employee') ? $level->getRelation('employee') : $level->employee;
            $empAttrs = $emp instanceof EmployeeMaster ? $emp->getAttributes() : null;

            return [
                'map' => $level->getAttributes(),
                'employee' => $empAttrs,
            ];
        };

        $rows = [];
        foreach ($matrix as $row) {
            $rows[] = [
                'category' => $row['category']->getAttributes(),
                'level1' => $serializeLevel($row['level1'] ?? null),
                'level2' => $serializeLevel($row['level2'] ?? null),
                'level3' => $serializeLevel($row['level3'] ?? null),
            ];
        }

        return [
            'matrix_rows' => $rows,
            'categories' => $categories->map(fn (IssueCategoryMaster $c) => $c->getAttributes())->values()->all(),
        ];
    }

    /**
     * @return array{matrix: array<int, array<string, mixed>>, categories: Collection<int, IssueCategoryMaster>}
     */
    private function matrixFromCacheArray(array $cached): array
    {
        if (! isset($cached['matrix_rows'], $cached['categories']) || ! is_array($cached['matrix_rows']) || ! is_array($cached['categories'])) {
            $built = $this->buildMatrixData();

            return ['matrix' => $built['matrix'], 'categories' => $built['categories']];
        }

        $categories = IssueCategoryMaster::hydrate($cached['categories']);
        $byCategoryPk = $categories->keyBy('pk');

        $hydrateLevel = function (?array $data): ?IssueCategoryEmployeeMap {
            if ($data === null || ! isset($data['map']) || ! is_array($data['map'])) {
                return null;
            }
            $map = new IssueCategoryEmployeeMap;
            $map->setRawAttributes($data['map']);
            $map->syncOriginal();
            if (! empty($data['employee']) && is_array($data['employee'])) {
                $emp = new EmployeeMaster;
                $emp->setRawAttributes($data['employee']);
                $emp->syncOriginal();
                $map->setRelation('employee', $emp);
            }

            return $map;
        };

        $matrix = [];
        foreach ($cached['matrix_rows'] as $row) {
            if (! isset($row['category']) || ! is_array($row['category'])) {
                continue;
            }
            $pk = $row['category']['pk'] ?? null;
            $category = $pk !== null ? $byCategoryPk->get($pk) : null;
            if ($category === null) {
                $category = IssueCategoryMaster::hydrate([$row['category']])->first();
            }
            if ($category === null) {
                continue;
            }
            $matrix[] = [
                'category' => $category,
                'level1' => $hydrateLevel($row['level1'] ?? null),
                'level2' => $hydrateLevel($row['level2'] ?? null),
                'level3' => $hydrateLevel($row['level3'] ?? null),
            ];
        }

        return ['matrix' => $matrix, 'categories' => $categories];
    }

    /**
     * Display escalation matrix - categories with 3-level hierarchy (employees + days).
     */
    public function index()
    {
        $epoch = DataTableRedisCache::readListEpoch(self::LISTING_CACHE_EPOCH_KEY);
        $cacheKey = 'admin_issue_escalation_matrix:v1:' . md5(json_encode(['epoch' => $epoch]));

        $cached = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'ISSUE_ESCALATION_MATRIX_CACHE_ENABLED',
                'seconds' => 'ISSUE_ESCALATION_MATRIX_CACHE_SECONDS',
            ],
            'IssueEscalationMatrixController@index',
            function () {
                $built = $this->buildMatrixData();

                return $this->matrixToCacheArray($built['matrix'], $built['categories']);
            }
        );

        if (! is_array($cached) || ! isset($cached['matrix_rows'], $cached['categories'])) {
            $built = $this->buildMatrixData();
            $cached = $this->matrixToCacheArray($built['matrix'], $built['categories']);
        }

        $hydrated = $this->matrixFromCacheArray($cached);
        $matrix = $hydrated['matrix'];
        $categories = $hydrated['categories'];
        $employees = $this->getEmployeesForDropdown();

        // Ensure any currently mapped inactive employee appears in the dropdown list to avoid empty/incorrect selection
        $mappedEmployeeIds = [];
        foreach ($matrix as $row) {
            foreach (['level1', 'level2', 'level3'] as $lvl) {
                if (isset($row[$lvl]) && $row[$lvl] && $row[$lvl]->employee_master_pk) {
                    $mappedEmployeeIds[] = (int) $row[$lvl]->employee_master_pk;
                }
            }
        }
        $mappedEmployeeIds = array_unique($mappedEmployeeIds);
        if (!empty($mappedEmployeeIds)) {
            $existingEmployeePks = $employees->pluck('employee_pk')->map(fn($v) => (int)$v)->all();
            $missingPks = array_diff($mappedEmployeeIds, $existingEmployeePks);
            if (!empty($missingPks)) {
                $missingEmployees = DB::table('employee_master as e')
                    ->whereIn('e.pk', $missingPks)
                    ->select(
                        'e.pk as employee_pk',
                        DB::raw("TRIM(CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, ''))) as employee_name")
                    )
                    ->get();
                $employees = $employees->concat($missingEmployees);
            }
        }

        return view('admin.issue_management.escalation_matrix.index', compact('matrix', 'categories', 'employees'));
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
        $query = DB::table('employee_master as e')
            ->join('user_credentials as uc', function ($join) {
                $join->on('uc.user_id', '=', 'e.pk');
                if (\Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'pk_old')) {
                    $join->orOn('uc.user_id', '=', 'e.pk_old');
                }
            })
            ->where('uc.user_category', '!=', 'S');

        if (\Illuminate\Support\Facades\Schema::hasColumn('employee_master', 'status')) {
            $query->where('e.status', 1);
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('user_credentials', 'Active_inactive')) {
            $query->where('uc.Active_inactive', 1);
        }

        return $query->select(
                'e.pk as employee_pk',
                DB::raw("TRIM(CONCAT(COALESCE(e.first_name, ''), ' ', COALESCE(e.middle_name, ''), ' ', COALESCE(e.last_name, ''))) as employee_name")
            )
            ->orderBy('e.first_name')
            ->groupBy('e.pk', 'e.first_name', 'e.middle_name', 'e.last_name')
            ->get();
    }
}
