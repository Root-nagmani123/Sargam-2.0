<?php

namespace App\Http\Controllers\Admin\Registration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\FormSection;
use App\Models\FormData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use App\Exports\FcformListExport;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;



class FormController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('local_form')->where('visible', 1);

        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $forms = $query->get();

        // Group forms by parent_id (null or 0 for parents)
        $groupedForms = $forms->groupBy(function ($item) {
            return $item->parent_id ?: null;
        });

        return view('admin.registration.index', [
            'forms' => $forms,  // Needed for arrow logic in view
            'groupedForms' => $groupedForms,
        ]);
    }

    // New method for inactive forms
    public function inactive(Request $request)
    {
        $query = DB::table('local_form')
            ->where('visible', 0) // Only inactive
            ->orderBy('sortorder');

        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Get inactive forms
        $forms = $query->get();

        return view('admin.registration.inactive', compact('forms'));
    }




    public function create()
    {
        $forms = DB::table('local_form')->select('id', 'name', 'shortname')->orderBy('name')->get();
        return view('admin.registration.create', compact('forms'));
        // return view('admin.registration.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'shortname' => 'required|string|max:100',
            'description' => 'required|string',
            'course_sdate' => 'required|date',
            'course_edate' => 'required|date|after_or_equal:course_sdate',
            'parent_id' => 'nullable|exists:local_form,id',
        ]);

        $sortorder = DB::table('local_form')->max('sortorder') + 1;

        $id = DB::table('local_form')->insertGetId([
            'parent_id' => $request->parent_id,
            'name' => $request->name,
            'shortname' => $request->shortname,
            'description' => $request->description,
            'course_sdate' => $request->course_sdate,
            'course_edate' => $request->course_edate,
            'visible' => $request->has('visible'),
            'sortorder' => $sortorder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('forms.createnew', ['formid' => $id])->with('success', 'Form created successfully.');
    }


    public function edit($id)
    {
        $form = DB::table('local_form')->where('id', $id)->first();
        $forms = DB::table('local_form')
            ->where('id', '!=', $id) // don't allow setting itself as parent
            ->orderBy('name')->get();

        if (!$form) {
            abort(404);
        }

        return view('admin.registration.edit', compact('form', 'forms'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'shortname' => 'required|string|max:100',
            'description' => 'required|string',
            'course_sdate' => 'required|date',
            'course_edate' => 'required|date|after_or_equal:course_sdate',
            'parent_id' => 'nullable|exists:local_form,id',
        ]);

        DB::table('local_form')->where('id', $id)->update([
            'parent_id' => $request->parent_id,
            'name' => $request->name,
            'shortname' => $request->shortname,
            'description' => $request->description,
            'course_sdate' => $request->course_sdate,
            'course_edate' => $request->course_edate,
            'visible' => $request->has('visible'),
            'updated_at' => now(),
        ]);

        return redirect()->route('forms.index')->with('success', 'Form updated!');
    }

    public function toggleVisible($id)
    {
        $form = DB::table('local_form')->where('id', $id)->first();
        if (!$form) {
            return response()->json(['error' => 'Form not found'], 404);
        }

        $newStatus = !$form->visible;

        DB::table('local_form')->where('id', $id)->update(['visible' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => 'Form visibility updated successfully!',
            'visible' => $newStatus
        ]);
    }

    // public function createform()
    // {
    //     return view('admin.registration.createform'); // the Blade view for form creation
    // }

    public function createform($formid)
    {
        // return view('admin.registration.createform', compact('formid'));
        // Get column names using raw query (Query Builder)
        $excludedColumns = ['id', 'formid', 'uid', 'timecreated'];

        $columns = collect(DB::select('SHOW COLUMNS FROM fc_registration_master'))
            ->pluck('Field')
            ->filter(function ($column) use ($excludedColumns) {
                return !in_array(strtolower($column), array_map('strtolower', $excludedColumns));
            })
            ->map(function ($column) {
                return ($column);
            })
            ->values();  // optional: reset keys

        return view('admin.registration.createform', [
            'formid' => $formid,
            'submissionColumns' => $columns,
        ]);
    }

    public function saveform(Request $request, $formid)
    {
        DB::beginTransaction();
        try {
            $sectionTitles = $request->input('section_title', []);
            $sectionLayouts = $request->input('section_layout', []); // ✅ new
            // dd($sectionLayouts);
            $fieldSections = $request->input('field_section', []);
            $fieldNames = $request->input('field_name', []);
            $fieldTypes = $request->input('field_type', []);
            $fieldLabels = $request->input('field_label', []);
            $fieldOptions = $request->input('field_options', []);
            $isRequireds = $request->input('is_required', []);
            $fieldLayouts = $request->input('field_layout', []);

            $tableRows = $request->input('table_rows', []);
            $tableColumns = $request->input('table_columns', []);
            $tableSections = $request->input('table_section', []);
            $tableHeaders = $request->input('table_column_heading', []);

            $sectionIds = [];

            // Insert form sections
            foreach ($sectionTitles as $index => $title) {
                $title = trim($title);
                if (empty($title)) continue;

                $sectionId = FormSection::create([
                    'formid' => $formid,
                    'section_title' => $title,
                    'layout'        => $sectionLayouts[$index] ?? 'col-4', //  save layout
                ]);

                if ($sectionId->id) {
                    $sectionIds[$index] = $sectionId->id;
                }
            }

            // Insert individual fields
            foreach ($fieldNames as $index => $name) {
                $name = trim($name);
                if (empty($name)) continue;

                $sectionIndex = $fieldSections[$index] ?? 0;
                if (!isset($sectionIds[$sectionIndex])) {
                    throw new \Exception("Invalid section index: $sectionIndex");
                }

                $requiredKey = "{$sectionIndex}_{$index}";
                $fieldType = $fieldTypes[$index] ?? 'text';

                //  Only store layout for radio/checkbox
                $layout = ($fieldType === 'radio' || $fieldType === 'checkbox')
                    ? ($fieldLayouts[$index] ?? 'inline')
                    : null;
                DB::table('form_data')->insert([
                    'formid'      => $formid,
                    'section_id'  => $sectionIds[$sectionIndex],
                    'formname'    => $name,
                    'formtype'    => $fieldTypes[$index] ?? 'text',
                    'formlabel'   => $fieldLabels[$index] ?? '',
                    'fieldoption' => $fieldOptions[$index] ?? '',
                    'required'    => isset($isRequireds[$requiredKey]) && $isRequireds[$requiredKey] == 1 ? 1 : 0,
                    'layout'      => $layout, //  store only for radio/checkbox
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            // Insert table-based fields
            if (!empty($tableSections) && !empty($tableRows) && !empty($tableColumns)) {
                foreach ($tableSections as $sectionIndex => $val) {
                    if (!isset($sectionIds[$val])) continue;

                    $sectionId = $sectionIds[$val];
                    $rows = $tableRows[$sectionIndex] ?? 0;
                    $columns = $tableColumns[$sectionIndex] ?? 0;

                    for ($colIndex = 0; $colIndex < $columns; $colIndex++) {
                        for ($rowIndex = 0; $rowIndex < $rows; $rowIndex++) {
                            $headerTitle = $request->input("table_column_heading_{$val}_{$rowIndex}")[$colIndex] ?? '';

                            DB::table('form_data')->insert([
                                'formid'     => $formid,
                                'section_id' => $sectionId,
                                'formname'   => null,
                                'formtype'   => null,
                                'formlabel'  => null,
                                'fieldoption' => null,
                                'required'   => 0,
                                'layout'     => null,
                                'table_index' => 0,
                                'format'     => 'table',
                                'row_index'  => $rowIndex,
                                'col_index'  => $colIndex,
                                'header'     => $headerTitle,
                                'field_type' => trim($request->input("table_row{$rowIndex}_{$val}_0")[$colIndex] ?? ''),
                                'field_title' => trim($request->input("table_title{$rowIndex}_{$val}_0")[$colIndex] ?? ''),
                                'field_url'  => trim($request->input("table_url{$rowIndex}_{$val}_0")[$colIndex] ?? ''),
                                'field_options' => trim($request->input("table_options{$rowIndex}_{$val}_0")[$colIndex] ?? ''),
                                'field_checkbox_options' => trim($request->input("checkbox_options{$rowIndex}_{$val}_0")[$colIndex] ?? ''),
                                'field_radio_options'    => trim($request->input("radio_options{$rowIndex}_{$val}_0")[$colIndex] ?? ''),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('forms.index', [])
                ->with('success', 'Form saved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            // \Log::info($e);
            // \Log::info($e->getMessage());
            die;
            return back()->with('error', 'Error saving form: ' . $e->getMessage());
        }
    }


    public function show($formId)
    {
        // Fetch the requested form
        $form = DB::table('local_form')->where('id', $formId)->first();
        if (!$form) abort(404, 'Form not found');

        // Determine parent form ID
        $parentFormId = ($form->parent_id && $form->parent_id != 0) ? $form->parent_id : $form->id;

        // Fetch children of the parent form
        $childForms = DB::table('local_form')
            ->where('parent_id', $parentFormId)
            ->where('visible', 1)
            ->orderBy('sortorder')
            ->get();

        // Check fields for the current form
        $fields = DB::table('form_data')
            ->where('formid', $form->id)
            ->orderBy('id')
            ->orderBy('row_index')
            ->orderBy('col_index')
            ->get();

        // If this is a parent form with no fields, but children exist,
        // switch to first child form and load its fields instead
        if (($form->parent_id == 0 || $form->parent_id == null) && $fields->isEmpty() && $childForms->isNotEmpty()) {
            // Switch form to first child
            $form = $childForms->first();

            // Reload fields for first child form
            $fields = DB::table('form_data')
                ->where('formid', $form->id)
                ->orderBy('id')
                ->orderBy('row_index')
                ->orderBy('col_index')
                ->get();
        }

        // Group fields for display (table and grid)
        $fieldsBySection = [];
        $gridFields = [];
        foreach ($fields as $field) {
            if ($field->format === 'table') {
                $fieldsBySection[$field->section_id][$field->row_index][$field->col_index] = $field;
            } else {
                $gridFields[$field->section_id][] = $field;
            }
        }

        // Fetch sections for the (possibly switched) form
        $sections = DB::table('form_sections')
            ->where('formid', $form->id)
            ->get();

        // Table headers for table fields
        $headersBySection = [];
        foreach ($fields as $field) {
            if ($field->format === 'table') {
                $headersBySection[$field->section_id][$field->col_index] = $field->header;
            }
        }

        // Submission data for current user and form
        $submissions = DB::table('fc_registration_master')
            ->where('formid', $form->id)
            ->where('uid', Auth::id())
            ->get()
            ->keyBy('fieldname');

        // Fetch logo or other dynamic data if needed
        $data = DB::table('registration_logo')->first();

        return view('admin.forms.show', compact(
            'form',
            'data',
            'childForms',
            'sections',
            'fieldsBySection',
            'gridFields',
            'headersBySection',
            'submissions'
        ));
    }





    //finaallll
    public function submit(Request $request, $formId)
    {
        // dd($request->all());
        try {


            $userId = Auth::id();
            $timestamp = now()->timestamp;

            // Check if a submission already exists
            $existingSubmission = DB::table('fc_registration_master')
                ->where('formid', $formId)
                ->where('uid', $userId)
                ->first();

            // Handle dynamic fields (field_*)
            $dynamicFields = [];

            foreach ($request->all() as $key => $value) {
                if (Str::startsWith($key, 'field_')) {
                    if ($value instanceof UploadedFile) {
                        // Optional validation
                        $request->validate([
                            $key => 'file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx|max:5120', // 5MB
                        ]);

                        $filename = time() . '_' . $value->getClientOriginalName();
                        $path = $value->storeAs('form-uploads', $filename, 'public');

                        $dynamicFields[Str::replaceFirst('field_', '', $key)] = $path;
                    } elseif (is_array($value)) {
                        $dynamicFields[Str::replaceFirst('field_', '', $key)] =  implode(',', $value);
                    } else {
                        $dynamicFields[Str::replaceFirst('field_', '', $key)] = $value;
                    }
                }
            }

            // Insert or Update form_submission
            if (!empty($dynamicFields)) {
                $dynamicFields['formid'] = $formId;
                $dynamicFields['uid'] = $userId;
                $dynamicFields['timecreated'] = $timestamp;

                if ($existingSubmission) {
                    DB::table('fc_registration_master')
                        ->where('formid', $formId)
                        ->where('uid', $userId)
                        ->update($dynamicFields);
                } else {
                    DB::table('fc_registration_master')->insert($dynamicFields);
                }
            }

            // Handle dynamic table data if present
            $tableDataInserted = false;

            // Grouping table data by section and row
            $tableData = [];
            $headers_table_data_values = [];
            foreach ($request->all() as $key => $value) {
                // Match header keys like: header_11_0
                if (preg_match('/^header_(\d+)_(\d+)$/', $key, $matches)) {
                    $sectionId = (int)$matches[1];
                    $colIndex = (int)$matches[2];
                    $headers_table_data_values[$sectionId]['headers'][$colIndex] = $value;
                }

                // Match table data keys like: table_11_0_0
                if (preg_match('/^table_(\d+)_(\d+)_(\d+)$/', $key, $matches)) {
                    $sectionId = (int)$matches[1];
                    $rowIndex = (int)$matches[2];
                    $colIndex = (int)$matches[3];
                    $headers_table_data_values[$sectionId]['values'][$rowIndex][$colIndex] = $value;
                }
            }

            // Delete old table data for this form and user before reinserting
            DB::table('form_submission_tabledata')
                ->where('formid', $formId)
                ->where('uid', $userId)
                ->delete();

            foreach ($headers_table_data_values as $sectionId => $sectionData) {
                $headers = $sectionData['headers'] ?? [];
                $values = $sectionData['values'] ?? [];

                foreach ($values as $rowIndex => $cols) {
                    foreach ($cols as $colIndex => $columnValue) {
                        $columnKey = $headers[$colIndex] ?? 'column_' . $colIndex;
                        $fieldType = 'text';
                        $filePath = null;

                        $valueKey = "table_{$sectionId}_{$rowIndex}_{$colIndex}";

                        // File upload detection
                        if ($request->hasFile($valueKey)) {
                            $filePath = $request->file($valueKey)->store("form-uploads" . $formId . $userId, 'public');
                            // dd($filePath);
                            // $filePath = $request->file($valueKey)->store("form-uploads", 'public');

                            $fieldType = 'file';
                            $columnValue = null;
                        } elseif (is_array($columnValue)) {
                            $fieldType = 'checkbox';
                            // $columnValue = json_encode($columnValue);
                            $columnValue = implode(',', array_map('trim', $columnValue));
                        } elseif (in_array($columnValue, ['on', 'off', '1', '0'], true)) {
                            $fieldType = 'checkbox';
                            $columnValue = ($columnValue === 'on' || $columnValue === '1') ? 1 : 0;
                        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $columnValue)) {
                            $fieldType = 'date';
                        } elseif (filter_var($columnValue, FILTER_VALIDATE_EMAIL)) {
                            $fieldType = 'email';
                        } elseif (in_array($columnValue, ['male', 'female', 'other'], true)) {
                            $fieldType = 'radio';
                        } elseif (strlen($columnValue) > 100) {
                            $fieldType = 'textarea';
                        }

                        // Insert into DB
                        DB::table('form_submission_tabledata')->insert([
                            'formid' => $formId,
                            'uid' => $userId,
                            'section_id' => $sectionId,
                            'row_index' => $rowIndex,
                            'col_index' => $colIndex,
                            'column_key' => $columnKey,
                            'field_type' => $fieldType,
                            'column_value' => $columnValue,
                            'file_path' => $filePath,
                            'timecreated' => $timestamp,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $tableDataInserted = true;
                    }
                }
            }

            // Insert all table data at once (if present)
            if ($tableDataInserted) {
                DB::table('form_submission_tabledata')->insert($tableData);
            }

            // Check if there was any data to submit
            if (!$dynamicFields && !$tableDataInserted) {
                return redirect()->back()->with('error', 'Nothing to submit.');
            }

            return redirect()->back()->with('success', 'Form submitted successfully!');
        } catch (\Exception $e) {

            // \Log::info($e);
            // \Log::info($e->getMessage());

            // Return with an error message
            return redirect()->back()->with('error', 'An error occurred while submitting the form. Please try again later.');
        }
    }


    public function moveUp($id)
    {
        $form = DB::table('local_form')->where('id', $id)->first();

        if (!$form) return back()->with('error', 'Form not found.');

        $above = DB::table('local_form')
            ->where('sortorder', '<', $form->sortorder)
            ->orderByDesc('sortorder')
            ->first();

        if ($above) {
            DB::transaction(function () use ($form, $above) {
                DB::table('local_form')->where('id', $form->id)->update(['sortorder' => $above->sortorder]);
                DB::table('local_form')->where('id', $above->id)->update(['sortorder' => $form->sortorder]);
            });
        }

        return back()->with('success', 'Form moved up successfully.');
    }

    public function moveDown($id)
    {
        $form = DB::table('local_form')->where('id', $id)->first();

        if (!$form) return back()->with('error', 'Form not found.');

        $below = DB::table('local_form')
            ->where('sortorder', '>', $form->sortorder)
            ->orderBy('sortorder')
            ->first();

        if ($below) {
            DB::transaction(function () use ($form, $below) {
                DB::table('local_form')->where('id', $form->id)->update(['sortorder' => $below->sortorder]);
                DB::table('local_form')->where('id', $below->id)->update(['sortorder' => $form->sortorder]);
            });
        }

        return back()->with('success', 'Form moved down successfully.');
    }


    public function courseList(Request $request, $formid)
    {
        $statusval = $request->input('statusval');

        // 1) Fetch submission records (optionally filter by confirm_status)
        $query = DB::table('fc_registration_master')->where('formid', $formid);
        if (!is_null($statusval) && $statusval !== '') {
            $query->where('confirm_status', $statusval);
        }
        $records = $query->get();

        // 2) Extract UIDs
        $uids = $records->pluck('uid')->filter()->unique()->values()->all();

        // 3) Get dynamic field names for this form (so your grid knows what to show)
        $dynamicFieldNames = DB::table('form_data')
            ->where('formid', $formid)
            ->pluck('formname')
            ->toArray();

        // 4) Always include uid + Fullname (computed below)
        $fields = array_merge(['uid', 'Fullname'], $dynamicFieldNames);

        // 5) Full name map for uid → "First Last" (from user_credentials.pk)
        $fullnames = collect();
        if (!empty($uids)) {
            $fullnames = DB::table('user_credentials')
                ->whereIn('pk', $uids)        // pk maps to fc_registration_master.uid
                ->select(DB::raw("CONCAT(first_name, ' ', last_name) as full_name"), 'pk')
                ->pluck('full_name', 'pk');   // [uid => "First Last"]
        }

        $idToNameMappings = [
            'service_master_pk'          => ['table' => 'service_master',           'id' => 'pk', 'name' => 'service_name'],
            'last_service_pk'            => ['table' => 'service_master',           'id' => 'pk', 'name' => 'service_name'],
            'cadre_master_pk'            => ['table' => 'cadre_master',             'id' => 'pk', 'name' => 'cadre_name'],
            'admission_category_pk'      => ['table' => 'admission_category_master',       'id' => 'pk', 'name' => 'seat_name'],
            'religion_master_pk'         => ['table' => 'religion_master',          'id' => 'pk', 'name' => 'religion_name'],
            'country_master_pk'          => ['table' => 'country_master',           'id' => 'pk', 'name' => 'country_name'],
            'postal_country_pk'          => ['table' => 'country_master',           'id' => 'pk', 'name' => 'country_name'],
            'state_master_pk'            => ['table' => 'state_master',             'id' => 'pk', 'name' => 'state_name'],
            'postal_state_pk'            => ['table' => 'state_master',             'id' => 'pk', 'name' => 'state_name'],
            'domicile_state_pk'          => ['table' => 'state_master',             'id' => 'pk', 'name' => 'state_name'],
            'birth_state'                => ['table' => 'state_master',             'id' => 'pk', 'name' => 'state_name'],
            'state_district_mapping_pk'  => ['table' => 'state_district_mapping',   'id' => 'pk', 'name' => 'district_name'],
            'pdistrict_id'               => ['table' => 'state_district_mapping',   'id' => 'pk', 'name' => 'district_name'],
            'highest_stream_pk'          => ['table' => 'stream_master',           'id' => 'pk', 'name' => 'stream_name'],
            'course_master_pk'           => ['table' => 'course_master',            'id' => 'pk', 'name' => 'course_name'],
            'city'                       => ['table' => 'city_master',             'id' => 'pk', 'name' => 'city_name'],
            'postal_city'                => ['table' => 'city_master',             'id' => 'pk', 'name' => 'city_name'],
            't-Shirt'                    => ['table' => 'student_cloths_size_master',  'id' => 'pk', 'name' => 'cloth_size'],
            'Blazer'                     => ['table' => 'student_cloths_size_master',  'id' => 'pk', 'name' => 'cloth_size'],
            'Trouser'                    => ['table' => 'student_cloths_size_master',  'id' => 'pk', 'name' => 'cloth_size'],
            'Tracksuite'                  => ['table' => 'student_cloths_size_master',  'id' => 'pk', 'name' => 'cloth_size'],
            'father_profession'           => ['table' => 'parents_profession_master',  'id' => 'pk', 'name' => 'profession_name'],
            'mother_profession'           => ['table' => 'parents_profession_master',  'id' => 'pk', 'name' => 'profession_name'],
        ];

        $lookupData = [];
        foreach ($idToNameMappings as $field => $map) {
            // Collect the set of IDs present in the current records for this field
            $ids = $records->pluck($field)->filter()->unique()->values();
            if ($ids->isEmpty()) {
                continue;
            }

            $pairs = DB::table($map['table'])
                ->whereIn($map['id'], $ids)
                ->pluck($map['name'], $map['id'])
                ->toArray(); // [id => name]

            $lookupData[$field] = $pairs;
        }

        // 9) Build the users grid payload, applying mappings
        $users = [];
        foreach ($records as $record) {
            $uid = $record->uid;
            foreach ($fields as $field) {
                if ($field === 'Fullname') {
                    $users[$uid]['Fullname'] = $fullnames[$uid] ?? '';
                    continue;
                }

                // Pull raw value from the row (dynamic fields may or may not exist as columns)
                $raw = $record->$field ?? null;

                // a) ID→Name (table) mapping
                if (isset($lookupData[$field])) {
                    $users[$uid][$field] = $raw !== null
                        ? ($lookupData[$field][$raw] ?? $raw)
                        : '';
                    continue;
                }

                // b) Enum mapping (like gender/status) if the field name matches exactly
                if (isset($enumMappings[$field])) {
                    $users[$uid][$field] = $raw !== null
                        ? ($enumMappings[$field][$raw] ?? $raw)
                        : '';
                    continue;
                }

                // c) Fallback: keep the original value
                $users[$uid][$field] = $raw ?? '';
            }
        }

        // 10) Refresh UIDs from the final users array (distinct users)
        $uids = array_keys($users);

        // 11) Load related user details / passwords (if you show them)
        $userDetails = collect();
        $passwords   = collect();

        if (!empty($uids)) {
            $userDetails = DB::table('users')->whereIn('id', $uids)->get()->keyBy('id');
            $passwords   = DB::table('users')->whereIn('id', $uids)->pluck('password', 'id');
        }

        // 12) Count distinct students for the form
        $total_students = DB::table('fc_registration_master')
            ->where('formid', $formid)
            ->distinct('uid')
            ->count('uid');

        // 13) Send everything to the view
        return view('admin.forms.course_list', compact(
            'records',
            'users',
            'fields',
            'userDetails',
            'passwords',
            'formid',
            'total_students',
            'statusval'
        ));
    }






    public function display($formid, $uid)
    {
        // Fetch data based on formid and uid
        // Example:
        // $submission = DB::table('form_submissions')->where('formid', $formid)->where('uid', $uid)->first();

        return view('forms.display', compact('formid', 'uid'));
    }

    public function downloadPdf($formid, $uid)
    {

        return "Downloading PDF for Form ID: $formid and User ID: $uid";
    }

    // Helper method for checking valid JSON
    private function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }


    private function getNameById($id, $fieldname)
    {
        // Map field names to database tables
        $tableMap = [
            'country' => 'country_master',
            'state' => 'state_master',
            'district' => 'state_district_mapping',
            'language' => 'language_master',
            // Add other mappings as needed
        ];

        $table = null;
        $nameField = null;

        foreach ($tableMap as $key => $tableName) {
            if (strpos($fieldname, $key) !== false) {
                $table = $tableName;
                $nameField = $key . '_name';
                break;
            }
        }

        if ($table) {
            // $record = DB::table($table)->where('id', $id)->first();
            // return $record ? $record->$nameField : '';
        }

        return $id; // Return original ID if no mapping found
    }
    public function homeUser()
    {
        $forms = DB::table('local_form')->orderBy('sortorder')->get();
        return view('admin.forms.home_page', compact('forms'));
    }


    public function main_page()
    {
        $forms = DB::table('local_form')->orderBy('sortorder')->get();
        return view('admin.forms.main_page', compact('forms'));
    }
    public function exemption()
    {
        $forms = DB::table('local_form')->orderBy('sortorder')->get();
        return view('admin.forms.exemption', compact('forms'));
    }
    public function logo_page()
    {
        $forms = DB::table('local_form')->orderBy('sortorder')->get();
        return view('admin.forms.logo_page', compact('forms'));
    }

    public function exportfcformList(Request $request, $formid)
    {
        // 1. Get Form Name
        $formName = DB::table('local_form')->where('id', $formid)->value('name');
        $statusval = $request->input('statusval');
        $format = $request->input('format'); // 'xlsx', 'csv', or 'pdf'

        // 2. Fetch registration records
        $query = DB::table('fc_registration_master')->where('formid', $formid);
        if (!empty($statusval)) {
            $query->where('confirm_status', $statusval);
        }
        $records = $query->get();

        if ($records->isEmpty()) {
            return back()->with('error', 'No records found for export.');
        }

        // 3. Extract UIDs
        $uids = $records->pluck('uid')->unique()->toArray();

        // 4. Get dynamic field names
        $dynamicFieldNames = DB::table('form_data')
            ->where('formid', $formid)
            ->pluck('formname')
            ->toArray();

        // 5. Fields for export: uid, Fullname, + dynamic fields
        $fields = array_merge(['uid', 'Fullname'], $dynamicFieldNames);

        // 6. Fetch fullnames for UIDs
        $fullnames = DB::table('user_credentials')
            ->whereIn('pk', $uids)
            ->select('pk', DB::raw("CONCAT(first_name, ' ', last_name) as full_name"))
            ->pluck('full_name', 'pk'); // [uid => full name]

        // 7. Example mapping for ID → Name conversions (extend as needed)
        $mappings = [
            'service_master_pk'          => ['table' => 'service_master',           'id' => 'pk', 'name' => 'service_name'],
            'last_service_pk'            => ['table' => 'service_master',           'id' => 'pk', 'name' => 'service_name'],
            // 'cadre_master_pk'            => ['table' => 'cadre_master',             'id' => 'pk', 'name' => 'cadre_name'],
            'admission_category_pk'      => ['table' => 'admission_category_master',       'id' => 'pk', 'name' => 'seat_name'],
            'religion_master_pk'         => ['table' => 'religion_master',          'id' => 'pk', 'name' => 'religion_name'],
            'country_master_pk'          => ['table' => 'country_master',           'id' => 'pk', 'name' => 'country_name'],
            'postal_country_pk'          => ['table' => 'country_master',           'id' => 'pk', 'name' => 'country_name'],
            'state_master_pk'            => ['table' => 'state_master',             'id' => 'pk', 'name' => 'state_name'],
            'postal_state_pk'            => ['table' => 'state_master',             'id' => 'pk', 'name' => 'state_name'],
            'domicile_state_pk'          => ['table' => 'state_master',             'id' => 'pk', 'name' => 'state_name'],
            'birth_state'                => ['table' => 'state_master',             'id' => 'pk', 'name' => 'state_name'],
            'state_district_mapping_pk'  => ['table' => 'state_district_mapping',   'id' => 'pk', 'name' => 'district_name'],
            'pdistrict_id'               => ['table' => 'state_district_mapping',   'id' => 'pk', 'name' => 'district_name'],
            'highest_stream_pk'          => ['table' => 'stream_master',           'id' => 'pk', 'name' => 'stream_name'],
            'course_master_pk'           => ['table' => 'course_master',            'id' => 'pk', 'name' => 'course_name'],
            'city'                       => ['table' => 'city_master',             'id' => 'pk', 'name' => 'city_name'],
            'postal_city'                => ['table' => 'city_master',             'id' => 'pk', 'name' => 'city_name'],
            't-Shirt'                    => ['table' => 'student_cloths_size_master',  'id' => 'pk', 'name' => 'cloth_size'],
            'Blazer'                     => ['table' => 'student_cloths_size_master',  'id' => 'pk', 'name' => 'cloth_size'],
            'Trouser'                    => ['table' => 'student_cloths_size_master',  'id' => 'pk', 'name' => 'cloth_size'],
            'Tracksuite'                 => ['table' => 'student_cloths_size_master',  'id' => 'pk', 'name' => 'cloth_size'],
            'father_profession'          => ['table' => 'parents_profession_master',  'id' => 'pk', 'name' => 'profession_name'],
            'mother_profession'          => ['table' => 'parents_profession_master',  'id' => 'pk', 'name' => 'profession_name'],
        ];

        // Preload lookup values
        $lookupValues = [];
        foreach ($mappings as $field => $map) {
            $lookupValues[$field] = DB::table($map['table'])
                ->pluck($map['name'], $map['id']); // [id => name]
        }

        // 8. Format user data
        $users = [];
        foreach ($records as $record) {
            $uid = $record->uid;
            $row = [];

            foreach ($fields as $field) {
                if ($field === 'uid') {
                    $row['uid'] = $uid;
                } elseif ($field === 'Fullname') {
                    $row['Fullname'] = $fullnames[$uid] ?? '';
                } elseif (isset($mappings[$field])) {
                    // Replace ID with Name
                    $row[$field] = $lookupValues[$field][$record->$field] ?? '';
                } else {
                    $row[$field] = $record->$field ?? '';
                }
            }

            $users[] = $row;
        }

        // 9. Handle export
        if ($format === 'csv') {
            return Excel::download(new FcformListExport($users, $fields), $formName . '.csv', \Maatwebsite\Excel\Excel::CSV);
        } elseif ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.forms.export.fcform_pdf', [
                'records' => $users,
                'fields' => $fields,
                'formName' => $formName,
            ])->setPaper('A3', 'landscape');
            return $pdf->download($formName . '.pdf');
        } else {
            // Default to Excel (.xlsx)
            return Excel::download(new FcformListExport($users, $fields), $formName . '.xlsx');
        }
    }


    // 3 sep working
    // public function generatePdf($form_id, $user_id)
    // {
    //     ini_set('pcre.backtrack_limit', '10000000');

    //     if (!is_numeric($form_id) || !is_numeric($user_id) || $form_id <= 0 || $user_id <= 0) {
    //         abort(400, 'Invalid ID parameters');
    //     }

    //     try {
    //         // 1) Get form metadata
    //         $formInfo = DB::table('local_form')
    //             ->where('id', $form_id)
    //             ->first(['description', 'course_sdate', 'course_edate']);

    //         if (!$formInfo) abort(404, 'Form not found');

    //         $form_description = $formInfo->description;
    //         $form_date_range = date('d-m-Y', strtotime($formInfo->course_sdate)) . " to " . date('d-m-Y', strtotime($formInfo->course_edate));

    //         // 2) Get submission (normal fields)
    //         $submission = DB::table('fc_registration_master')
    //             ->where('formid', $form_id)
    //             ->where('uid', $user_id)
    //             ->first();
    //         $submissionArray = (array) $submission;

    //         $user_name = $submissionArray['name'] ?? 'User';
    //         $logo_path = '';
    //         $base64 = '';
    //         $sections = [];

    //         // 3) Profile image (or default)
    //         if (!empty($submissionArray['profile'])) {
    //             $path = storage_path("app/public/{$submissionArray['profile']}");
    //             if (file_exists($path)) {
    //                 $type = pathinfo($path, PATHINFO_EXTENSION);
    //                 $data = base64_encode(file_get_contents($path));
    //                 $base64 = "data:image/{$type};base64,{$data}";
    //             }
    //         }
    //         if (empty($base64)) {
    //             $defaultPath = public_path('images/dummypic.jpeg');
    //             if (file_exists($defaultPath)) {
    //                 $type = pathinfo($defaultPath, PATHINFO_EXTENSION);
    //                 $data = base64_encode(file_get_contents($defaultPath));
    //                 $base64 = "data:image/{$type};base64,{$data}";
    //             }
    //         }

    //         // 4) Fetch form structure
    //         $formStructure = DB::table('form_sections AS s')
    //             ->join('form_data AS f', 's.id', '=', 'f.section_id')
    //             ->where('f.formid', $form_id)
    //             ->select('s.id AS section_id', 's.section_title', 'f.formname', 'f.formlabel', 'f.format')
    //             ->orderBy('s.id')
    //             ->get();

    //         $idToNameMappings = [
    //             'service_master_pk'          => ['table' => 'service_master',           'id' => 'pk', 'name' => 'service_name'],
    //             'last_service_pk'            => ['table' => 'service_master',           'id' => 'pk', 'name' => 'service_name'],
    //             'cadre_master_pk'            => ['table' => 'cadre_master',             'id' => 'pk', 'name' => 'cadre_name'],
    //             'admission_category_pk'      => ['table' => 'admission_category_master',       'id' => 'pk', 'name' => 'seat_name'],
    //             'religion_master_pk'         => ['table' => 'religion_master',          'id' => 'pk', 'name' => 'religion_name'],
    //             'country_master_pk'          => ['table' => 'country_master',           'id' => 'pk', 'name' => 'country_name'],
    //             'postal_country_pk'          => ['table' => 'country_master',           'id' => 'pk', 'name' => 'country_name'],
    //             'state_master_pk'            => ['table' => 'state_master',             'id' => 'pk', 'name' => 'state_name'],
    //             'postal_state_pk'            => ['table' => 'state_master',             'id' => 'pk', 'name' => 'state_name'],
    //             'domicile_state_pk'          => ['table' => 'state_master',             'id' => 'pk', 'name' => 'state_name'],
    //             'birth_state'                => ['table' => 'state_master',             'id' => 'pk', 'name' => 'state_name'],
    //             'state_district_mapping_pk'  => ['table' => 'state_district_mapping',   'id' => 'pk', 'name' => 'district_name'],
    //             'pdistrict_id'               => ['table' => 'state_district_mapping',   'id' => 'pk', 'name' => 'district_name'],
    //             'highest_stream_pk'          => ['table' => 'stream_master',           'id' => 'pk', 'name' => 'stream_name'],
    //             'course_master_pk'           => ['table' => 'course_master',            'id' => 'pk', 'name' => 'course_name'],
    //             'city'                       => ['table' => 'city_master',             'id' => 'pk', 'name' => 'city_name'],
    //             'postal_city'                => ['table' => 'city_master',             'id' => 'pk', 'name' => 'city_name'],
    //             't-Shirt'                    => ['table' => 'student_cloths_size_master',  'id' => 'pk', 'name' => 'cloth_size'],
    //             'Blazer'                     => ['table' => 'student_cloths_size_master',  'id' => 'pk', 'name' => 'cloth_size'],
    //             'Trouser'                    => ['table' => 'student_cloths_size_master',  'id' => 'pk', 'name' => 'cloth_size'],
    //             'Tracksuite'                 => ['table' => 'student_cloths_size_master',  'id' => 'pk', 'name' => 'cloth_size'],
    //             'father_profession'          => ['table' => 'parents_profession_master',  'id' => 'pk', 'name' => 'profession_name'],
    //             'mother_profession'          => ['table' => 'parents_profession_master',  'id' => 'pk', 'name' => 'profession_name'],
    //             'language'                   => ['table' => 'language_master',           'id' => 'pk', 'name' => 'language_name']
    //         ];

    //         $enumMappings = [
    //             'gender' => [1 => 'Male', 2 => 'Female', 3 => 'Other'],
    //             'status' => [0 => 'Inactive', 1 => 'Active'],
    //         ];

    //         // 6) Preload lookup data for this user's submission
    //         $lookupData = [];
    //         foreach ($idToNameMappings as $field => $map) {
    //             if (empty($submissionArray[$field])) continue;
    //             $ids = [$submissionArray[$field]];

    //             $pairs = DB::table($map['table'])
    //                 ->whereIn($map['id'], $ids)
    //                 ->pluck($map['name'], $map['id'])
    //                 ->toArray();

    //             $lookupData[$field] = $pairs;
    //         }

    //         // 7) Process sections
    //         $tableProcessed = [];
    //         foreach ($formStructure as $item) {
    //             $section = $item->section_title;
    //             $fieldname = trim($item->formname);
    //             $label = $item->formlabel;
    //             $type = $item->format;

    //             if (!isset($sections[$section])) {
    //                 $sections[$section] = [];
    //             }

    //             // Handle tables separately
    //             if ($type === 'table') {
    //                 $sectionId = $item->section_id;
    //                 $tableKey = $sectionId . '_' . $fieldname;
    //                 if (in_array($tableKey, $tableProcessed)) continue;

    //                 $tableData = DB::table('form_submission_tabledata')
    //                     ->where('formid', $form_id)
    //                     ->where('uid', $user_id)
    //                     ->where('section_id', $sectionId)
    //                     ->get();

    //                 if ($tableData->isNotEmpty()) {
    //                     $grouped = $tableData->groupBy('row_index');
    //                     $rows = [];
    //                     $headers = [];

    //                     foreach ($grouped as $row) {
    //                         $rowData = [];
    //                         foreach ($row as $cell) {
    //                             $headers[$cell->column_key] = true;

    //                             $rawVal = $cell->column_value;

    //                             // ID → Name replacement
    //                             if (isset($lookupData[$cell->column_key])) {
    //                                 $rowData[$cell->column_key] = $lookupData[$cell->column_key][$rawVal] ?? $rawVal;
    //                             } elseif (isset($enumMappings[$cell->column_key])) {
    //                                 $rowData[$cell->column_key] = $enumMappings[$cell->column_key][$rawVal] ?? $rawVal;
    //                             } else {
    //                                 $rowData[$cell->column_key] = $rawVal;
    //                             }
    //                         }
    //                         $rows[] = $rowData;
    //                     }

    //                     $sections[$section][] = [
    //                         'label_en' => $label,
    //                         'type' => 'table',
    //                         'headers' => array_keys($headers),
    //                         'rows' => $rows,
    //                     ];
    //                 }

    //                 $tableProcessed[] = $tableKey;
    //                 continue;
    //             }

    //             // Handle normal fields
    //             $value = $submissionArray[$fieldname] ?? null;
    //             if (!is_null($value) && $value !== '') {
    //                 // Apply ID→Name mapping if applicable
    //                 if (isset($lookupData[$fieldname])) {
    //                     $value = $lookupData[$fieldname][$value] ?? $value;
    //                 } elseif (isset($enumMappings[$fieldname])) {
    //                     $value = $enumMappings[$fieldname][$value] ?? $value;
    //                 }

    //                 $sections[$section][] = [
    //                     'label_en' => $label,
    //                     'fieldvalue' => $value,
    //                 ];
    //             }
    //         }

    //         // 8) Render HTML
    //         $html = view('admin.registration.form_template', [
    //             'form_description' => $form_description,
    //             'form_date_range' => $form_date_range,
    //             'sections' => $sections,
    //             'logo_path' => $base64,
    //             'user_name' => $user_name,
    //         ])->render();

    //         // 9) Setup mPDF
    //         $tempDir = storage_path('temp/mpdf');
    //         if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

    //         $fontDir = base_path('vendor/mpdf/mpdf/ttfonts');
    //         $mpdf = new \Mpdf\Mpdf([
    //             'tempDir' => $tempDir,
    //             'fontDir' => [$fontDir],
    //             'default_font' => 'dejavusans',
    //         ]);

    //         // Devanagari font support (optional)
    //         if (file_exists("{$fontDir}/NotoSansDevanagari-Regular.ttf")) {
    //             $mpdf->fontdata['devanagari'] = [
    //                 'R' => "{$fontDir}/NotoSansDevanagari-Regular.ttf",
    //                 'B' => "{$fontDir}/NotoSansDevanagari-Bold.ttf",
    //             ];
    //             $mpdf->SetFont('devanagari');
    //         }

    //         $mpdf->WriteHTML($html);

    //         return response($mpdf->Output('form.pdf', 'I'), 200)
    //             ->header('Content-Type', 'application/pdf');
    //     } catch (\Exception $e) {
    //         \Log::error('PDF Generation Error: ' . $e->getMessage());
    //         abort(500, 'An error occurred while generating the PDF.');
    //     }
    // }


    public function generatePdf($form_id, $user_id)
    {
        ini_set('pcre.backtrack_limit', '10000000');

        if (!is_numeric($form_id) || !is_numeric($user_id) || $form_id <= 0 || $user_id <= 0) {
            abort(400, 'Invalid ID parameters');
        }

        try {
            // 1) Get form metadata
            $formInfo = DB::table('local_form')
                ->where('id', $form_id)
                ->first(['description', 'course_sdate', 'course_edate']);

            if (!$formInfo) abort(404, 'Form not found');

            $form_description = $formInfo->description;
            $form_date_range = date('d-m-Y', strtotime($formInfo->course_sdate)) . " to " . date('d-m-Y', strtotime($formInfo->course_edate));

            // 2) Get submission (normal fields)
            $submission = DB::table('fc_registration_master')
                ->where('formid', $form_id)
                ->where('uid', $user_id)
                ->first();
            $submissionArray = (array) $submission;

            $user_name = $submissionArray['name'] ?? 'User';
            $base64 = '';
            $sections = [];

            // 3) Profile image (or default)
            if (!empty($submissionArray['profile'])) {
                $path = storage_path("app/public/{$submissionArray['profile']}");
                if (file_exists($path)) {
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = base64_encode(file_get_contents($path));
                    $base64 = "data:image/{$type};base64,{$data}";
                }
            }
            if (empty($base64)) {
                $defaultPath = public_path('images/dummypic.jpeg');
                if (file_exists($defaultPath)) {
                    $type = pathinfo($defaultPath, PATHINFO_EXTENSION);
                    $data = base64_encode(file_get_contents($defaultPath));
                    $base64 = "data:image/{$type};base64,{$data}";
                }
            }

            // 4) Fetch form structure
            $formStructure = DB::table('form_sections AS s')
                ->join('form_data AS f', 's.id', '=', 'f.section_id')
                ->where('f.formid', $form_id)
                ->select('s.id AS section_id', 's.section_title', 'f.formname', 'f.formlabel', 'f.format')
                ->orderBy('s.id')
                ->get();

            // 5) Mappings
            $idToNameMappings = [
                'service_master_pk'          => ['table' => 'service_master',           'id' => 'pk', 'name' => 'service_name'],
                'last_service_pk'            => ['table' => 'service_master',           'id' => 'pk', 'name' => 'service_name'],
                'cadre_master_pk'            => ['table' => 'cadre_master',             'id' => 'pk', 'name' => 'cadre_name'],
                'admission_category_pk'      => ['table' => 'admission_category_master', 'id' => 'pk', 'name' => 'seat_name'],
                'religion_master_pk'         => ['table' => 'religion_master',          'id' => 'pk', 'name' => 'religion_name'],
                'country_master_pk'          => ['table' => 'country_master',           'id' => 'pk', 'name' => 'country_name'],
                'postal_country_pk'          => ['table' => 'country_master',           'id' => 'pk', 'name' => 'country_name'],
                'state_master_pk'            => ['table' => 'state_master',             'id' => 'pk', 'name' => 'state_name'],
                'postal_state_pk'            => ['table' => 'state_master',             'id' => 'pk', 'name' => 'state_name'],
                'domicile_state_pk'          => ['table' => 'state_master',             'id' => 'pk', 'name' => 'state_name'],
                'birth_state'                => ['table' => 'state_master',             'id' => 'pk', 'name' => 'state_name'],
                'state_district_mapping_pk'  => ['table' => 'state_district_mapping',   'id' => 'pk', 'name' => 'district_name'],
                'pdistrict_id'               => ['table' => 'state_district_mapping',   'id' => 'pk', 'name' => 'district_name'],
                'highest_stream_pk'          => ['table' => 'stream_master',            'id' => 'pk', 'name' => 'stream_name'],
                'course_master_pk'           => ['table' => 'course_master',            'id' => 'pk', 'name' => 'course_name'],
                'city'                       => ['table' => 'city_master',              'id' => 'pk', 'name' => 'city_name'],
                'postal_city'                => ['table' => 'city_master',              'id' => 'pk', 'name' => 'city_name'],
                't-Shirt'                    => ['table' => 'student_cloths_size_master', 'id' => 'pk', 'name' => 'cloth_size'],
                'Blazer'                     => ['table' => 'student_cloths_size_master', 'id' => 'pk', 'name' => 'cloth_size'],
                'Trouser'                    => ['table' => 'student_cloths_size_master', 'id' => 'pk', 'name' => 'cloth_size'],
                'Tracksuite'                 => ['table' => 'student_cloths_size_master', 'id' => 'pk', 'name' => 'cloth_size'],
                'father_profession'          => ['table' => 'parents_profession_master', 'id' => 'pk', 'name' => 'profession_name'],
                'mother_profession'          => ['table' => 'parents_profession_master', 'id' => 'pk', 'name' => 'profession_name'],
                'language'                   => ['table' => 'language_master',          'id' => 'pk', 'name' => 'language_name'],
                'boardname'                  => ['table' => 'university_board_name_master', 'id' => 'pk', 'name' => 'board_name'],
                'university'                 => ['table' => 'university_board_name_master', 'id' => 'pk', 'name' => 'board_name'],
                'Degree'                     => ['table' => 'degree_master',            'id' => 'pk', 'name' => 'degree_name'],
                'instituitontype'            => ['table' => 'institute_type_master',    'id' => 'pk', 'name' => 'type_name'],

            ];

            $enumMappings = [
                'gender' => [1 => 'Male', 2 => 'Female', 3 => 'Other'],
                'status' => [0 => 'Inactive', 1 => 'Active'],
            ];

            // 6) Preload lookup data for this user's submission
            $lookupData = [];
            foreach ($idToNameMappings as $field => $map) {
                if (empty($submissionArray[$field])) continue;
                $ids = [$submissionArray[$field]];

                $pairs = DB::table($map['table'])
                    ->whereIn($map['id'], $ids)
                    ->pluck($map['name'], $map['id'])
                    ->toArray();

                $lookupData[$field] = $pairs;
            }

            // 7) Process sections
            $tableProcessed = [];
            foreach ($formStructure as $item) {
                $section = $item->section_title;
                $fieldname = trim($item->formname);
                $label = $item->formlabel;
                $type = $item->format;

                if (!isset($sections[$section])) {
                    $sections[$section] = [];
                }

                // Handle tables
                if ($type === 'table') {
                    $sectionId = $item->section_id;
                    $tableKey = $sectionId . '_' . $fieldname;
                    if (in_array($tableKey, $tableProcessed)) continue;

                    $tableData = DB::table('form_submission_tabledata')
                        ->where('formid', $form_id)
                        ->where('uid', $user_id)
                        ->where('section_id', $sectionId)
                        ->get();

                    if ($tableData->isNotEmpty()) {
                        $grouped = $tableData->groupBy('row_index');
                        $rows = [];
                        $headers = [];

                        foreach ($grouped as $row) {
                            $rowData = [];
                            foreach ($row as $cell) {
                                $headers[$cell->column_key] = true;
                                $rawVal = $cell->column_value;

                                // 🔹 Special case: any column containing "language"
                                if (stripos($cell->column_key, 'language') !== false) {
                                    $name = DB::table('language_master')
                                        ->where('pk', $rawVal)
                                        ->value('language_name');
                                    $rowData[$cell->column_key] = $name ?? $rawVal;

                                    // 🔹 Special case: "board" or "university"
                                } elseif (stripos($cell->column_key, 'board') !== false || stripos($cell->column_key, 'university') !== false) {
                                    $name = DB::table('university_board_name_master')
                                        ->where('pk', $rawVal)
                                        ->value('board_name');
                                    $rowData[$cell->column_key] = $name ?? $rawVal;

                                    // 🔹 Special case: "degree"
                                } elseif (stripos($cell->column_key, 'degree') !== false) {
                                    $name = DB::table('degree_master')
                                        ->where('pk', $rawVal)
                                        ->value('degree_name'); // use degree_full_name for clarity
                                    $rowData[$cell->column_key] = $name ?? $rawVal;
                                } elseif (stripos($cell->column_key, 'institution') !== false || stripos($cell->column_key, 'instituiton') !== false) {
                                    $name = DB::table('institute_type_master')
                                        ->where('pk', $rawVal)
                                        ->value('type_name');
                                    $rowData[$cell->column_key] = $name ?? $rawVal;

                                    // 🔹 Normal ID → Name replacement
                                } elseif (isset($idToNameMappings[$cell->column_key])) {
                                    $map = $idToNameMappings[$cell->column_key];
                                    $name = DB::table($map['table'])
                                        ->where($map['id'], $rawVal)
                                        ->value($map['name']);
                                    $rowData[$cell->column_key] = $name ?? $rawVal;
                                } elseif (isset($lookupData[$cell->column_key])) {
                                    $rowData[$cell->column_key] = $lookupData[$cell->column_key][$rawVal] ?? $rawVal;
                                } elseif (isset($enumMappings[$cell->column_key])) {
                                    $rowData[$cell->column_key] = $enumMappings[$cell->column_key][$rawVal] ?? $rawVal;
                                } else {
                                    $rowData[$cell->column_key] = $rawVal;
                                }
                            }
                            $rows[] = $rowData;
                        }



                        $sections[$section][] = [
                            'label_en' => $label,
                            'type' => 'table',
                            'headers' => array_keys($headers),
                            'rows' => $rows,
                        ];
                    }

                    $tableProcessed[] = $tableKey;
                    continue;
                }

                // Handle normal fields
                $value = $submissionArray[$fieldname] ?? null;
                if (!is_null($value) && $value !== '') {
                    if (isset($lookupData[$fieldname])) {
                        $value = $lookupData[$fieldname][$value] ?? $value;
                    } elseif (isset($enumMappings[$fieldname])) {
                        $value = $enumMappings[$fieldname][$value] ?? $value;
                    }

                    $sections[$section][] = [
                        'label_en' => $label,
                        'fieldvalue' => $value,
                    ];
                }
            }

            // 8) Render HTML
            $html = view('admin.registration.form_template', [
                'form_description' => $form_description,
                'form_date_range' => $form_date_range,
                'sections' => $sections,
                'logo_path' => $base64,
                'user_name' => $user_name,
            ])->render();

            // 9) Setup mPDF
            $tempDir = storage_path('temp/mpdf');
            if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

            $fontDir = base_path('vendor/mpdf/mpdf/ttfonts');
            $mpdf = new \Mpdf\Mpdf([
                'tempDir' => $tempDir,
                'fontDir' => [$fontDir],
                'default_font' => 'dejavusans',
            ]);

            if (file_exists("{$fontDir}/NotoSansDevanagari-Regular.ttf")) {
                $mpdf->fontdata['devanagari'] = [
                    'R' => "{$fontDir}/NotoSansDevanagari-Regular.ttf",
                    'B' => "{$fontDir}/NotoSansDevanagari-Bold.ttf",
                ];
                $mpdf->SetFont('devanagari');
            }

            $mpdf->WriteHTML($html);

            return response($mpdf->Output('form.pdf', 'I'), 200)
                ->header('Content-Type', 'application/pdf');
        } catch (\Exception $e) {
            \Log::error('PDF Generation Error: ' . $e->getMessage());
            abort(500, 'An error occurred while generating the PDF.');
        }
    }
}
