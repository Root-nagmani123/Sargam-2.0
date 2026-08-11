<?php

namespace App\Http\Controllers\FC;

use App\Http\Controllers\Controller;
use App\Models\FC\FcFormStep;
use App\Models\FC\FcFormField;
use App\Models\FC\FcFormFieldGroup;
use App\Models\FC\FcFormGroupField;
use App\Models\FC\FcJoiningRelatedDocumentsMaster;
use App\Services\FC\DynamicFormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Validation\Rule;

class FormBuilderController extends Controller
{
    public function __construct(private DynamicFormService $formService) {}

    /**
     * Invalidate the cached form STRUCTURE for the form that owns the given
     * step / field / group / group-field. Safe no-op if the form can't be resolved.
     */
    private function bumpFormStructure($model): void
    {
        $formId = 0;
        if ($model instanceof FcFormStep) {
            $formId = (int) $model->form_id;
        } elseif ($model instanceof FcFormField) {
            $formId = (int) optional($model->step)->form_id;
        } elseif ($model instanceof FcFormFieldGroup) {
            $formId = (int) optional($model->step)->form_id;
        } elseif ($model instanceof FcFormGroupField) {
            $formId = (int) optional(optional($model->group)->step)->form_id;
        }

        if ($formId > 0) {
            GenericFormController::bumpFormStructureEpoch($formId);

            // The Descriptive Data report resolves its columns (and its filter dropdowns)
            // from this same form definition and caches both. Without this, a field added
            // here stays invisible on that report until the cache TTL expires.
            \App\Services\FC\FcDescriptiveDataFieldResolver::forgetForm($formId);
        }
    }

    // ── Step List ────────────────────────────────────────────────────
    public function index()
    {
        $steps = FcFormStep::orderBy('step_number')
            ->withCount(['fields', 'fieldGroups'])
            ->get();

        $docMasterCount = FcJoiningRelatedDocumentsMaster::where('is_active', 1)->count();

        return view('admin.form-builder.index', compact('steps', 'docMasterCount'));
    }

    // ── Edit Step ────────────────────────────────────────────────────
    public function editStep(FcFormStep $step)
    {
        $step->load([
            'form',
            'fields' => fn ($q) => $q->orderBy('display_order'),
            'fieldGroups' => fn ($q) => $q->orderBy('display_order'),
            'fieldGroups.groupFields' => fn ($q) => $q->orderBy('display_order'),
        ]);

        $docMasters = collect();
        if ($step->isDocumentsStep()) {
            $docMasters = FcJoiningRelatedDocumentsMaster::orderBy('display_order')->get();
        }

        return view('admin.form-builder.edit-step', compact('step', 'docMasters'));
    }

    // ── Update Step Settings ─────────────────────────────────────────
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
        $this->bumpFormStructure($step);

        return back()->with('success', 'Step settings updated.');
    }

    // ── FIELD CRUD ──────────────────────────────────────────────────

    public function storeField(Request $request, FcFormStep $step)
    {
        $data = $this->validateFieldData($request);
        $data['step_id']       = $step->id;
        $data['target_table']  = $data['target_table'] ?: $step->target_table;
        $data['display_order'] = ($step->fields()->max('display_order') ?? 0) + 1;

        $this->ensureColumnExists($data['target_table'], $data['target_column'], $data['field_type']);

        $field = FcFormField::create($data);
        $this->bumpFormStructure($step);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Field "'.$field->label.'" added. You can add another below.',
                'fields_count' => $step->fields()->count(),
                'row_html' => view('admin.form-builder.partials.field-row', compact('field'))->render(),
            ]);
        }

        return back()->with('success', 'Field added successfully.');
    }

    public function updateField(Request $request, FcFormField $field)
    {
        $data = $this->validateFieldData($request);
        $targetTable = $data['target_table'] ?: $field->target_table;
        $this->ensureColumnExists($targetTable, $data['target_column'], $data['field_type']);

        $field->update($data);
        $this->bumpFormStructure($field);

        return back()->with('success', 'Field updated.');
    }

    public function deleteField(FcFormField $field)
    {
        if ($field->is_active) {
            return back()->with('error', 'This field is currently in use on the form and cannot be deleted. Set it to inactive first, then try again.');
        }

        $this->bumpFormStructure($field);
        $field->delete();

        return back()->with('success', 'Field removed.');
    }

    public function reorderFields(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);
        foreach ($request->order as $position => $id) {
            FcFormField::where('id', $id)->update(['display_order' => $position + 1]);
        }
        if (($firstId = collect($request->order)->first()) && ($first = FcFormField::find($firstId))) {
            $this->bumpFormStructure($first);
        }
        return response()->json(['ok' => true]);
    }

    /**
     * Rename a section heading across every field of one step.
     *
     * A "section" is not a record — it is the plain string in fc_form_fields.section_heading,
     * repeated on each field that belongs to it. Renaming therefore meant editing every field
     * by hand and typing the identical text each time; one different character silently splits
     * the section in two (this step already carries both "Mother's Detail" and
     * "Mother's Details" for that reason).
     *
     * Nothing about how sections are stored or read changes here — this only performs the same
     * edit an admin can already make field by field, in one statement and without typos.
     */
    public function renameSection(Request $request, FcFormStep $step)
    {
        $data = $request->validate([
            'old_section_heading' => 'required|string|max:200',
            'new_section_heading' => 'required|string|max:200',
        ]);

        $old = trim($data['old_section_heading']);
        $new = trim($data['new_section_heading']);

        if ($old === '' || $new === '') {
            return back()->with('error', 'Both the current and the new section name are required.');
        }

        if ($old === $new) {
            return back()->with('error', 'The new section name is the same as the current one.');
        }

        // Scoped to THIS step and THIS heading: a section name repeated on another step is a
        // different section and must not be touched.
        //
        // Compared on TRIM(section_heading), because the picker lists trimmed headings while the
        // column may hold " Personal Details" — matching the raw column would offer that section
        // and then refuse to rename it. (Trailing spaces are already forgiven by MySQL's PAD
        // SPACE collations; leading ones are not.)
        $matches = FcFormField::where('step_id', $step->id)
            ->whereRaw('TRIM(section_heading) = ?', [$old])
            ->count();

        if ($matches === 0) {
            return back()->with('error', 'No fields on this step use the section "'.$old.'".');
        }

        // Renaming onto a name already in use merges the two — legitimate, and the only way to
        // repair a section split by a typo — but say so plainly rather than silently merging.
        $mergesInto = FcFormField::where('step_id', $step->id)
            ->whereRaw('TRIM(section_heading) = ?', [$new])
            ->count();

        try {
            DB::transaction(function () use ($step, $old, $new) {
                // Same TRIM comparison as the count above, so the rows counted are the rows
                // updated — and a heading stored with stray whitespace is normalised on the way.
                FcFormField::where('step_id', $step->id)
                    ->whereRaw('TRIM(section_heading) = ?', [$old])
                    ->update(['section_heading' => $new]);
            });
        } catch (\Throwable $e) {
            Log::error('FC form builder: section rename failed', [
                'step_id' => $step->id,
                'old' => $old,
                'new' => $new,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'The section could not be renamed. Please try again.');
        }

        // Publishes the change to trainees immediately instead of waiting out the
        // form-structure cache TTL.
        $this->bumpFormStructure($step);

        $message = $matches.' field'.($matches === 1 ? '' : 's').' moved from "'.$old.'" to "'.$new.'".';
        if ($mergesInto > 0) {
            $message .= ' Merged with the '.$mergesInto.' field'.($mergesInto === 1 ? '' : 's').' already in "'.$new.'".';
        }

        return back()->with('success', $message);
    }

    // ── GROUP CRUD ──────────────────────────────────────────────────

    public function storeGroup(Request $request, FcFormStep $step)
    {
        $data = $request->validate([
            'group_name'   => 'required|string|max:100',
            'group_label'  => 'required|string|max:200',
            'target_table' => 'required|string|max:100',
            'save_mode'    => 'required|in:replace_all,upsert',
            'min_rows'     => 'required|integer|min:0',
            'max_rows'     => 'required|integer|min:1',
        ]);

        $data['step_id']       = $step->id;
        $data['display_order'] = ($step->fieldGroups()->max('display_order') ?? 0) + 1;

        FcFormFieldGroup::create($data);
        $this->bumpFormStructure($step);

        return back()->with('success', 'Group added.');
    }

    public function updateGroup(Request $request, FcFormFieldGroup $group)
    {
        $data = $request->validate([
            'group_label'  => 'required|string|max:200',
            'target_table' => 'required|string|max:100',
            'save_mode'    => 'required|in:replace_all,upsert',
            'min_rows'     => 'required|integer|min:0',
            'max_rows'     => 'required|integer|min:1',
            'is_active'    => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $group->update($data);
        $this->bumpFormStructure($group);

        return back()->with('success', 'Group updated.');
    }

    public function deleteGroup(FcFormFieldGroup $group)
    {
        $this->bumpFormStructure($group);
        $group->delete();
        return back()->with('success', 'Group and its fields removed.');
    }

    // ── GROUP FIELD CRUD ────────────────────────────────────────────

    public function storeGroupField(Request $request, FcFormFieldGroup $group)
    {
        $data = $this->validateGroupFieldData($request);
        $data['group_id']      = $group->id;
        $data['display_order'] = ($group->groupFields()->max('display_order') ?? 0) + 1;
        $data['is_active']    = $request->boolean('is_active', true);

        $this->ensureColumnExists($group->target_table, $data['target_column'], $data['field_type']);

        FcFormGroupField::create($data);
        $this->bumpFormStructure($group);

        return back()->with('success', 'Group field added.');
    }

    public function updateGroupField(Request $request, FcFormGroupField $field)
    {
        $data = $this->validateGroupFieldData($request);
        $this->ensureColumnExists($field->group->target_table, $data['target_column'], $data['field_type']);

        $field->update($data);
        $this->bumpFormStructure($field);

        return back()->with('success', 'Group field updated.');
    }

    public function deleteGroupField(FcFormGroupField $field)
    {
        if ($field->is_active) {
            return back()->with('error', 'This field is currently in use on the form and cannot be deleted. Set it to inactive first, then try again.');
        }

        $this->bumpFormStructure($field);
        $field->delete();

        return back()->with('success', 'Group field removed.');
    }

    public function reorderGroupFields(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);
        foreach ($request->order as $position => $id) {
            FcFormGroupField::where('id', $id)->update(['display_order' => $position + 1]);
        }
        if (($firstId = collect($request->order)->first()) && ($first = FcFormGroupField::find($firstId))) {
            $this->bumpFormStructure($first);
        }
        return response()->json(['ok' => true]);
    }

    // ── Preview ─────────────────────────────────────────────────────

    public function preview(FcFormStep $step)
    {
        $fields  = $step->usesFieldGroups() ? collect() : $step->activeFields;
        $lookups = $this->formService->getLookupData($fields);
        $groups  = $step->usesFieldGroups()
            ? $step->activeFieldGroups()->with('activeGroupFields')->get()
            : collect();

        $groupLookups = [];
        foreach ($groups as $group) {
            $fieldsForLookups = $group->activeGroupFields->isNotEmpty()
                ? $group->activeGroupFields
                : $group->groupFields;
            $groupLookups[$group->group_name] = $this->formService->getGroupLookupData($fieldsForLookups);
        }

        $districtOptions = $this->formService->getDistrictMasterOptions();

        $docMasters = collect();
        if ($step->isDocumentsStep()) {
            $docMasters = FcJoiningRelatedDocumentsMaster::where('is_active', 1)->orderBy('display_order')->get();
        }

        return view('admin.form-builder.preview', compact(
            'step',
            'fields',
            'lookups',
            'groups',
            'groupLookups',
            'districtOptions',
            'docMasters'
        ));
    }

    // ── Private helpers ─────────────────────────────────────────────

    private function validateFieldData(Request $request): array
    {
        // Target column always matches field name (DB column created with same name if missing)
        if ($request->filled('field_name')) {
            $request->merge(['target_column' => $request->input('field_name')]);
        }

        $data = $request->validate([
            'field_name'           => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/'],
            'label'                => 'required|string|max:200',
            'field_type'           => 'required|in:text,number,email,date,select,radio,checkbox,textarea,file,hidden',
            'target_table'         => 'nullable|string|max:100',
            'target_column'        => 'required|string|max:100',
            'validation_rules'     => 'nullable|string|max:500',
            'is_required'          => 'nullable|boolean',
            'placeholder'          => 'nullable|string|max:200',
            'help_text'            => 'nullable|string|max:500',
            'default_value'        => 'nullable|string|max:200',
            'options_json'         => 'nullable|string',
            'lookup_table'         => 'nullable|string|max:100',
            'lookup_value_column'  => 'nullable|string|max:100',
            'lookup_label_column'  => 'nullable|string|max:100',
            'lookup_order_column'  => 'nullable|string|max:100',
            'section_heading'      => 'nullable|string|max:200',
            'css_class'            => ['nullable', 'string', Rule::in(array_keys(FcFormField::columnLayoutOptions()))],
            'file_max_kb'          => 'nullable|integer',
            'file_extensions'      => 'nullable|string|max:200',
            'form_template'        => 'nullable|string|max:100',
            'is_active'            => 'nullable|boolean',
        ]);

        $data['css_class'] = FcFormField::normalizeColumnLayout($data['css_class'] ?? null);
        if (! empty($data['options_json'])) {
            $comma = FcFormField::optionsJsonToCommaList($data['options_json']);
            $data['options_json'] = FcFormField::commaListToOptionsJson($comma);
        }

        // form_template only applies to file fields; clear it otherwise.
        if (($data['field_type'] ?? null) !== 'file') {
            $data['form_template'] = null;
        }

        $data['is_required'] = $request->boolean('is_required');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function validateGroupFieldData(Request $request): array
    {
        if ($request->filled('field_name')) {
            $request->merge(['target_column' => $request->input('field_name')]);
        }

        $data = $request->validate([
            'field_name'           => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/'],
            'label'                => 'required|string|max:200',
            'field_type'           => 'required|in:text,number,email,date,select,radio,checkbox,textarea,file,hidden',
            'target_column'        => 'required|string|max:100',
            'validation_rules'     => 'nullable|string|max:500',
            'is_required'          => 'nullable|boolean',
            'placeholder'          => 'nullable|string|max:200',
            'options_json'         => 'nullable|string',
            'lookup_table'         => 'nullable|string|max:100',
            'lookup_value_column'  => 'nullable|string|max:100',
            'lookup_label_column'  => 'nullable|string|max:100',
            'css_class'            => ['nullable', 'string', Rule::in(array_keys(FcFormField::columnLayoutOptions()))],
            'is_active'            => 'nullable|boolean',
        ]);

        $data['css_class'] = FcFormField::normalizeColumnLayout($data['css_class'] ?? null);
        if (! empty($data['options_json'])) {
            $comma = FcFormField::optionsJsonToCommaList($data['options_json']);
            $data['options_json'] = FcFormField::commaListToOptionsJson($comma);
        }

        $data['is_required'] = $request->boolean('is_required');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    // ── Document Master CRUD (documents step) ────────────────────────

    public function storeDocMaster(Request $request)
    {
        $data = $request->validate([
            'document_name'  => 'required|string|max:200',
            'document_code'  => 'required|string|max:50|unique:fc_joining_related_documents_masters,document_code',
            'is_mandatory'   => 'nullable|boolean',
            'is_active'      => 'nullable|boolean',
        ]);

        $data['is_mandatory'] = $request->boolean('is_mandatory');
        $data['is_active']    = $request->boolean('is_active', true);
        $data['display_order'] = (FcJoiningRelatedDocumentsMaster::max('display_order') ?? 0) + 1;

        FcJoiningRelatedDocumentsMaster::create($data);

        return back()->with('success', 'Document added to checklist.');
    }

    public function updateDocMaster(Request $request, FcJoiningRelatedDocumentsMaster $doc)
    {
        $data = $request->validate([
            'document_name'  => 'required|string|max:200',
            'document_code'  => 'required|string|max:50|unique:fc_joining_related_documents_masters,document_code,' . $doc->id,
            'is_mandatory'   => 'nullable|boolean',
            'is_active'      => 'nullable|boolean',
        ]);

        $data['is_mandatory'] = $request->boolean('is_mandatory');
        $data['is_active']    = $request->boolean('is_active');

        $doc->update($data);

        return back()->with('success', 'Document updated.');
    }

    public function deleteDocMaster(FcJoiningRelatedDocumentsMaster $doc)
    {
        $doc->delete();
        return back()->with('success', 'Document removed from checklist.');
    }

    public function reorderDocMasters(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);
        foreach ($request->order as $position => $id) {
            FcJoiningRelatedDocumentsMaster::where('id', $id)->update(['display_order' => $position + 1]);
        }
        return response()->json(['ok' => true]);
    }

    // ── Auto-create DB column if missing ─────────────────────────────

    private function ensureColumnExists(string $table, string $column, string $fieldType): void
    {
        if ($column === '_skip' || !$table || !$column) {
            return;
        }

        if (Schema::hasTable($table) && !Schema::hasColumn($table, $column)) {
            Schema::table($table, function (Blueprint $t) use ($column, $fieldType) {
                match ($fieldType) {
                    // Store as string so phones and decimals appear as entered (avoids DECIMAL …0000 padding).
                    'number'   => $t->string($column, 100)->nullable(),
                    'date'     => $t->date($column)->nullable(),
                    // Text holds JSON array for multi-option checkboxes, or 0/1 for a single checkbox.
                    'checkbox' => $t->text($column)->nullable(),
                    'textarea' => $t->text($column)->nullable(),
                    'file'     => $t->string($column, 500)->nullable(),
                    default    => $t->string($column, 500)->nullable(),
                };
            });

            // Runtime DDL: drop the cached column listing for this table so
            // fc_schema_has_column() sees the new column immediately instead of
            // serving a stale listing until the cache TTL expires.
            fc_schema_cache_forget($table);
        }
    }
}
