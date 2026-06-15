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
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Validation\Rule;

class FormBuilderController extends Controller
{
    public function __construct(private DynamicFormService $formService) {}

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

        return back()->with('success', 'Field updated.');
    }

    public function deleteField(FcFormField $field)
    {
        if ($field->is_active) {
            return back()->with('error', 'This field is currently in use on the form and cannot be deleted. Set it to inactive first, then try again.');
        }

        $field->delete();

        return back()->with('success', 'Field removed.');
    }

    public function reorderFields(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);
        foreach ($request->order as $position => $id) {
            FcFormField::where('id', $id)->update(['display_order' => $position + 1]);
        }
        return response()->json(['ok' => true]);
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

        return back()->with('success', 'Group updated.');
    }

    public function deleteGroup(FcFormFieldGroup $group)
    {
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

        return back()->with('success', 'Group field added.');
    }

    public function updateGroupField(Request $request, FcFormGroupField $field)
    {
        $data = $this->validateGroupFieldData($request);
        $this->ensureColumnExists($field->group->target_table, $data['target_column'], $data['field_type']);

        $field->update($data);

        return back()->with('success', 'Group field updated.');
    }

    public function deleteGroupField(FcFormGroupField $field)
    {
        if ($field->is_active) {
            return back()->with('error', 'This field is currently in use on the form and cannot be deleted. Set it to inactive first, then try again.');
        }

        $field->delete();

        return back()->with('success', 'Group field removed.');
    }

    public function reorderGroupFields(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);
        foreach ($request->order as $position => $id) {
            FcFormGroupField::where('id', $id)->update(['display_order' => $position + 1]);
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
        }
    }
}
