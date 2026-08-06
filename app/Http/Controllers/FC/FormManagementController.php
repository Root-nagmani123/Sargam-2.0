<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\CourseMaster;
use App\Models\FC\FcForm;
use App\Models\FC\FcFormStep;
use App\Models\FC\FcFormFieldGroup;
use App\Services\FC\FcDescriptiveDataFieldResolver;
use App\Services\FC\FcStepApplicabilityService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FormManagementController extends Controller
{
    // ── List all forms ───────────────────────────────────────────────
    public function index()
    {
        $forms = FcForm::withCount('steps')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.forms.index', compact('forms'));
    }

    public function ajaxList(Request $request)
    {
        $forms = $this->formsIndexQuery($request)
            ->orderByDesc('created_at')
            ->get();

        $html = view('admin.forms.partials.forms-grid', compact('forms'))->render();

        return response()->json([
            'success' => true,
            'html'    => $html,
            'count'   => $forms->count(),
        ]);
    }

    /**
     * AJAX: course dropdown options for form filter (by active/archive).
     */
    public function filterCourses(Request $request): JsonResponse
    {
        $status = $request->input('status_filter', 'active');
        $courses = $this->courseOptionsForFormFilter($status);

        return response()->json([
            'success' => true,
            'courses' => $courses,
        ]);
    }

    protected function formsIndexQuery(Request $request): Builder
    {
        $query = FcForm::query()
            ->with('courseMaster')
            ->withCount('steps');

        $statusFilter = $request->input('status_filter', 'active');
        $currentDate  = Carbon::now()->format('Y-m-d');

        if ($statusFilter === 'archive') {
            $query->whereHas('courseMaster', function ($q) use ($currentDate) {
                $q->whereNotNull('end_date')
                    ->where('end_date', '<', $currentDate);
            });
        } else {
            $query->where(function ($q) use ($currentDate) {
                $q->whereNull('course_master_pk')
                    ->orWhereHas('courseMaster', function ($c) use ($currentDate) {
                        $c->where(function ($e) use ($currentDate) {
                            $e->whereNull('end_date')
                                ->orWhere('end_date', '>=', $currentDate);
                        });
                    });
            });
        }

        if ($request->filled('course_filter')) {
            $query->where('course_master_pk', (int) $request->input('course_filter'));
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('form_name', 'like', $like)
                    ->orWhere('form_slug', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('courseMaster', fn ($c) => $c->where('course_name', 'like', $like));
            });
        }

        return $query;
    }

    protected function courseOptionsForFormFilter(string $status): array
    {
        $formQuery = $this->formsIndexQuery(new Request(['status_filter' => $status]));
        $coursePks = (clone $formQuery)
            ->whereNotNull('course_master_pk')
            ->distinct()
            ->pluck('course_master_pk');

        if ($coursePks->isEmpty()) {
            return [];
        }

        return CourseMaster::query()
            ->whereIn('pk', $coursePks)
            ->orderBy('course_name')
            ->pluck('course_name', 'pk')
            ->toArray();
    }

    // ── Create form ──────────────────────────────────────────────────
    public function create()
    {
        $tables = $this->getExistingTables();
        $sourceForms = FcForm::withCount('steps')->orderBy('form_name')->get();
        return view('admin.forms.create', compact('tables', 'sourceForms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'form_name'           => 'required|string|max:150',
            'form_slug'           => 'required|string|max:80|unique:fc_forms,form_slug|regex:/^[a-z0-9\-]+$/',
            'description'         => 'nullable|string',
            'icon'                => 'nullable|string|max:50',
            'consolidation_table' => 'nullable|string|max:100',
            'user_identifier'     => 'nullable|string|max:100',
            'source_form_id'      => 'nullable|integer|exists:fc_forms,id',
        ]);

        $sourceFormId = $validated['source_form_id'] ?? null;
        unset($validated['source_form_id']);

        $validated['icon']            = $validated['icon'] ?: 'bi-file-text';
        $validated['user_identifier'] = $validated['user_identifier'] ?: 'username';
        $validated['is_active']       = 1;

        $form = FcForm::create($validated);

        // Clone steps from source form
        if ($sourceFormId) {
            $sourceSteps = FcFormStep::where('form_id', $sourceFormId)
                ->orderBy('step_number')
                ->get();

            foreach ($sourceSteps as $srcStep) {
                $newStep = $srcStep->replicate();
                $newStep->form_id   = $form->id;
                $newStep->step_slug = $form->form_slug . '-' . $srcStep->step_slug;
                $newStep->save();

                // Clone flat fields for this step
                foreach ($srcStep->fields as $srcField) {
                    $newField = $srcField->replicate();
                    $newField->step_id = $newStep->id;
                    $newField->save();
                }

                // Clone field groups and their fields for this step (step3 type)
                foreach ($srcStep->fieldGroups as $srcGroup) {
                    $newGroup = $srcGroup->replicate();
                    $newGroup->step_id = $newStep->id;
                    $newGroup->save();

                    foreach ($srcGroup->groupFields as $srcGf) {
                        $newGf = $srcGf->replicate();
                        $newGf->group_id = $newGroup->id;
                        $newGf->save();
                    }
                }
            }
        }

        return redirect()->route('fc-reg.admin.forms.edit', $form)
            ->with('success', "Form '{$form->form_name}' created" . ($sourceFormId ? ' with cloned steps.' : '.'));
    }

    // ── Edit form settings & manage steps ────────────────────────────
    public function edit(FcForm $form)
    {
        $form->load(['steps' => fn($q) => $q->orderBy('step_number')]);
        $form->loadCount('steps');
        $tables = $this->getExistingTables();

        // Reference step-number → target_table mapping from FC Registration (form_id=1)
        $referenceSteps = FcFormStep::where('form_id', 1)
            ->orderBy('step_number')
            ->pluck('target_table', 'step_number')
            ->toArray();

        $nextStepNumber = ($form->steps->max('step_number') ?? 0) + 1;

        return view('admin.forms.edit', compact('form', 'tables', 'referenceSteps', 'nextStepNumber'));
    }

    public function update(Request $request, FcForm $form)
    {
        $validated = $request->validate([
            'form_name'           => 'required|string|max:150',
            'description'         => 'nullable|string',
            'icon'                => 'nullable|string|max:50',
            'consolidation_table' => 'nullable|string|max:100',
            'registration_requires_all_steps' => 'nullable|boolean',
            'is_active'           => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        // Unchecked switches are absent from the payload — read them explicitly so turning
        // the rule back off actually persists.
        //
        // Guarded, and the key dropped entirely when the column is missing: validate() leaves
        // it in $validated whenever the checkbox was ticked, so naming it in the UPDATE would
        // throw SQLSTATE[42S22] on a database where 2026_07_27_100000 has not run — breaking
        // the save of EVERY form setting, not just this switch. Mirrors the defensive read in
        // FcForm::registrationRequiresAllSteps().
        if (fc_schema_has_column('fc_forms', 'registration_requires_all_steps')) {
            $validated['registration_requires_all_steps'] = $request->boolean('registration_requires_all_steps');
        } else {
            unset($validated['registration_requires_all_steps']);
        }

        $form->update($validated);

        return back()->with('success', 'Form settings updated.');
    }

    public function destroy(FcForm $form)
    {
        $form->delete();
        return redirect()->route('fc-reg.admin.forms.index')
            ->with('success', "Form '{$form->form_name}' deleted.");
    }

    // ── Step CRUD within a form ──────────────────────────────────────

    public function storeStep(Request $request, FcForm $form)
    {
        $validated = $request->validate([
            'step_name'         => 'required|string|max:100',
            'step_slug'         => 'required|string|max:50|regex:/^[a-z0-9\-_]+$/|unique:fc_form_steps,step_slug',
            'target_table'      => 'required|string|max:100',
            'completion_column' => 'nullable|string|max:100',
            'tracker_column'    => 'nullable|string|max:100',
            'applicability_rule' => 'nullable|string|in:'.FcStepApplicabilityService::RULE_PH_VALUE,
            'description'       => 'nullable|string',
            'icon'              => 'nullable|string|max:50',
            'has_groups'        => 'nullable|boolean',
        ]);

        $validated['applicability_rule'] = filled($request->input('applicability_rule'))
            ? $validated['applicability_rule']
            : null;

        $validated['form_id']     = $form->id;
        $validated['step_number'] = ($form->steps()->max('step_number') ?? 0) + 1;
        $validated['is_active']   = 1;
        $validated['icon']        = $validated['icon'] ?: 'bi-file-text';

        unset($validated['has_groups']);
        FcFormStep::create($validated);

        // Auto-create tracker column in the consolidation table so tracking is fully dynamic
        if (filled($validated['tracker_column'] ?? null)) {
            $this->ensureTrackerColumn($form->trackerStorageTable(), $validated['tracker_column']);
        }

        $this->forgetFormDerivedCaches((int) $form->id);

        return back()->with('success', 'Step added.');
    }

    public function updateStep(Request $request, FcFormStep $step)
    {
        $validated = $request->validate([
            'step_name'         => 'required|string|max:100',
            'step_slug'         => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9\-_]+$/',
                Rule::unique('fc_form_steps', 'step_slug')->ignore($step->id),
            ],
            'target_table'      => 'required|string|max:100',
            'completion_column' => 'nullable|string|max:100',
            'tracker_column'    => 'nullable|string|max:100',
            'applicability_rule' => 'nullable|string|in:'.FcStepApplicabilityService::RULE_PH_VALUE,
            'description'       => 'nullable|string',
            'icon'              => 'nullable|string|max:50',
            'is_active'         => 'nullable|boolean',
        ]);

        // A step marked "applies only when …" is skipped for trainees the rule excludes,
        // and drops out of their progress denominator. Blank = applies to everyone.
        $validated['applicability_rule'] = filled($request->input('applicability_rule'))
            ? $validated['applicability_rule']
            : null;

        $validated['is_active'] = $request->boolean('is_active');
        $validated['icon'] = ($validated['icon'] ?? '') !== '' ? $validated['icon'] : 'bi-file-text';
        $validated['completion_column'] = filled($request->input('completion_column'))
            ? $validated['completion_column']
            : null;
        $validated['tracker_column'] = filled($request->input('tracker_column'))
            ? $validated['tracker_column']
            : null;

        $step->update($validated);

        // Auto-create tracker column if one was just set or changed
        if (filled($validated['tracker_column'] ?? null)) {
            $this->ensureTrackerColumn($step->form->trackerStorageTable(), $validated['tracker_column']);
        }

        $this->forgetFormDerivedCaches((int) $step->form_id);

        return back()->with('success', 'Step updated.');
    }

    /**
     * Drop the caches derived from this form's step/field structure.
     *
     * The Descriptive Data report resolves its columns — and its filter dropdowns — from
     * fc_form_steps joined to fc_form_fields, filtered on `s.is_active = 1`, and caches the
     * result per form. The form BUILDER already invalidates that through its own
     * bumpFormStructure() hook; the step mutators on THIS controller did not, so deactivating
     * or deleting a step here left the report rendering that step's columns, populated with
     * data, until the 60-minute TTL expired.
     *
     * Best-effort by contract: FcDescriptiveDataFieldResolver::forgetForm() swallows cache
     * failures, so a cache outage can never break saving a form step.
     */
    private function forgetFormDerivedCaches(int $formId): void
    {
        if ($formId > 0) {
            FcDescriptiveDataFieldResolver::forgetForm($formId);
        }
    }

    public function deleteStep(FcFormStep $step)
    {
        // Read the owning form BEFORE the row is gone — afterwards $step->form_id is still
        // populated in memory, but relying on that is a trap for the next edit.
        $formId = (int) $step->form_id;

        $step->delete();
        $this->forgetFormDerivedCaches($formId);

        return back()->with('success', 'Step and all its fields deleted.');
    }

    public function reorderSteps(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);

        // One query for the owning form ids, before the reorder — not one per step (G5).
        $formIds = FcFormStep::whereIn('id', $request->order)
            ->distinct()
            ->pluck('form_id')
            ->all();

        foreach ($request->order as $position => $id) {
            FcFormStep::where('id', $id)->update(['step_number' => $position + 1]);
        }

        foreach ($formIds as $formId) {
            $this->forgetFormDerivedCaches((int) $formId);
        }

        return response()->json(['ok' => true]);
    }

    // ── Helper: get all existing DB tables ────────────────────────────
    private function getExistingTables(): array
    {
        $tables = DB::select('SHOW TABLES');
        $result = [];
        foreach ($tables as $t) {
            $arr = get_object_vars($t);
            $result[] = reset($arr);
        }
        sort($result);
        return $result;
    }

    // ── Auto-create tracker column in consolidation table if missing ───
    // Called whenever a step is created or updated with a tracker_column value.
    private function ensureTrackerColumn(string $table, string $column): void
    {
        if (!$table || !$column) {
            return;
        }
        if (Schema::hasTable($table) && !Schema::hasColumn($table, $column)) {
            Schema::table($table, function (Blueprint $t) use ($column) {
                $t->tinyInteger($column)->unsigned()->default(0)->after('updated_at')->nullable();
            });

            // Runtime DDL — invalidate the cached column listing for this table.
            fc_schema_cache_forget($table);
        }
    }

    // ── API: get columns for a table (used by Select2 in field forms) ─
    public function getTableColumns(Request $request)
    {
        $request->validate(['table' => 'required|string|max:100']);
        $table = $request->input('table');

        // Verify table exists
        $tables = $this->getExistingTables();
        if (!in_array($table, $tables, true)) {
            return response()->json([]);
        }

        $columns = DB::select("SHOW COLUMNS FROM `{$table}`");
        $skip = ['id', 'created_at', 'updated_at', 'username'];

        $result = [];
        foreach ($columns as $col) {
            if (in_array($col->Field, $skip, true)) {
                continue;
            }
            $result[] = [
                'id'   => $col->Field,
                'text' => $col->Field,
                'type' => $col->Type,
            ];
        }

        return response()->json($result);
    }
}
