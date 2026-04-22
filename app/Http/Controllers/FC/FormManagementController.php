<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcForm;
use App\Models\FC\FcFormStep;
use App\Models\FC\FcFormFieldGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'is_active'           => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
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
            'description'       => 'nullable|string',
            'icon'              => 'nullable|string|max:50',
            'has_groups'        => 'nullable|boolean',
        ]);

        $validated['form_id']     = $form->id;
        $validated['step_number'] = ($form->steps()->max('step_number') ?? 0) + 1;
        $validated['is_active']   = 1;
        $validated['icon']        = $validated['icon'] ?: 'bi-file-text';

        unset($validated['has_groups']);
        FcFormStep::create($validated);

        return back()->with('success', 'Step added.');
    }

    public function updateStep(Request $request, FcFormStep $step)
    {
        $validated = $request->validate([
            'step_name'   => 'required|string|max:100',
            'description' => 'nullable|string',
            'icon'        => 'nullable|string|max:50',
            'is_active'   => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $step->update($validated);

        return back()->with('success', 'Step updated.');
    }

    public function deleteStep(FcFormStep $step)
    {
        $step->delete();
        return back()->with('success', 'Step and all its fields deleted.');
    }

    public function reorderSteps(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);
        foreach ($request->order as $position => $id) {
            FcFormStep::where('id', $id)->update(['step_number' => $position + 1]);
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
