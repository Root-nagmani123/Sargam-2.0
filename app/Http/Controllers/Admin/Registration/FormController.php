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
        $query = DB::table('local_form')->orderBy('sortorder');

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
        // dd($request->all());
        // dd($formid);
        // $formId = $request->input('formid', 0);
        // dd($formId);
        DB::beginTransaction();

        try {
            $sectionTitles = $request->input('section_title', []);
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
                ]);

                if ($sectionId->id) {
                    $sectionIds[$index] = $sectionId->id;
                }
            }

            // Insert standard fields
            // foreach ($fieldNames as $index => $name) {
            //     $name = trim($name);
            //     if (empty($name)) continue;

            //     $sectionIndex = $fieldSections[$index] ?? 0;
            //     if (!isset($sectionIds[$sectionIndex])) {
            //         throw new \Exception("Invalid section index: $sectionIndex");
            //     }

            //     DB::table('form_data')->insert([
            //         'formid'     => $formid,
            //         'section_id' => $sectionIds[$sectionIndex],
            //         'formname'   => $name,
            //         'formtype'   => $fieldTypes[$index] ?? 'text',
            //         'formlabel'  => $fieldLabels[$index] ?? '',
            //         'fieldoption' => $fieldOptions[$index] ?? '',
            //         // 'required'   => in_array($index, $isRequireds) ? 1 : 0,
            //         'required'   => isset($isRequireds[$index]) && $isRequireds[$index] == 1 ? 1 : 0,
            //         'layout'     => $fieldLayouts[$index] ?? 'vertical',
            //         'created_at' => now(),
            //         'updated_at' => now(),
            //     ]);
            // }

            foreach ($fieldNames as $index => $name) {
                $name = trim($name);
                if (empty($name)) continue;

                $sectionIndex = $fieldSections[$index] ?? 0;
                if (!isset($sectionIds[$sectionIndex])) {
                    throw new \Exception("Invalid section index: $sectionIndex");
                }

                $requiredKey = "{$sectionIndex}_{$index}";

                DB::table('form_data')->insert([
                    'formid'      => $formid,
                    'section_id'  => $sectionIds[$sectionIndex],
                    'formname'    => $name,
                    'formtype'    => $fieldTypes[$index] ?? 'text',
                    'formlabel'   => $fieldLabels[$index] ?? '',
                    'fieldoption' => $fieldOptions[$index] ?? '',
                    'required'    => isset($isRequireds[$requiredKey]) && $isRequireds[$requiredKey] == 1 ? 1 : 0,
                    'layout'      => $fieldLayouts[$index] ?? 'vertical',
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

    // public function show($formId)
    // {
    //     // Get form details
    //     $form = DB::table('local_form')
    //         ->where('id', $formId)
    //         ->where('visible', 1)
    //         ->first();

    //     if (!$form) {
    //         abort(404, 'Form not found');
    //     }

    //     // Get form sections
    //     $sections = DB::table('form_sections')
    //         ->where('formid', $formId)
    //         ->orderBy('sort_order')
    //         ->get();

    //     // Get form fields organized by section
    //     $fieldsBySection = [];
    //     $gridFields = [];

    //     $fields = DB::table('form_data')
    //         ->where('formid', $formId)
    //         ->orderBy('section_id')
    //         ->orderBy('row_index')
    //         ->orderBy('col_index')
    //         ->get();

    //     foreach ($fields as $field) {
    //         if ($field->format === 'table') {
    //             $fieldsBySection[$field->section_id][$field->row_index][$field->col_index] = $field;
    //         } else {
    //             $gridFields[$field->section_id][] = $field;
    //         }
    //     }

    //     // Get headers for table sections
    //     $headersBySection = [];
    //     $headerFields = DB::table('form_data')
    //         ->where('formid', $formId)
    //         ->where('format', 'table')
    //         ->get();

    //     foreach ($headerFields as $field) {
    //         if (!isset($headersBySection[$field->section_id][$field->col_index])) {
    //             $headersBySection[$field->section_id][$field->col_index] = $field->header;
    //         }
    //     }

    //     // Get user submissions
    //     $submissions = DB::table('form_submission')
    //         ->where('formid', $formId)
    //         ->where('uid', Auth::id())
    //         ->get()
    //         ->keyBy('fieldname');

    //     return view('admin.forms.show', compact(
    //         'form',
    //         'sections',
    //         'fieldsBySection',
    //         'gridFields',
    //         'headersBySection',
    //         'submissions'
    //     ));
    // }



    // public function show($formId)
    // {
    //     // Get all visible forms (for sidebar)
    //     $allForms = DB::table('local_form')
    //         ->where('visible', 1)
    //         ->orderBy('sortorder')
    //         ->get();

    //     //dynamic logo  

    //     $data = DB::table('registration_logo')->first(); // Assumes single row
    //     // return view('your_view_name', compact('form', 'allForms', 'data'));


    //     // Get selected form details
    //     $form = DB::table('local_form')->where('id', $formId)->first();

    //     if (!$form) {
    //         abort(404, 'Form not found');
    //     }

    //     // Fetch sections for the selected form
    //     $sections = DB::table('form_sections')
    //         ->where('formid', $formId)
    //         // ->orderBy('sort_order')
    //         ->get();

    //     // Fetch fields
    //     $fields = DB::table('form_data')
    //         ->where('formid', $formId)
    //         ->orderBy('id') // Ensure consistent ordering
    //         // ->orderBy('section_id')
    //         ->orderBy('row_index')
    //         ->orderBy('col_index')
    //         ->get();

    //     // dd($fields);

    //     $fieldsBySection = [];
    //     $gridFields = [];

    //     foreach ($fields as $field) {
    //         if ($field->format === 'table') {
    //             $fieldsBySection[$field->section_id][$field->row_index][$field->col_index] = $field;
    //         } else {
    //             $gridFields[$field->section_id][] = $field;
    //         }
    //     }

    //     // Get headers
    //     $headersBySection = [];
    //     foreach ($fields as $field) {
    //         if ($field->format === 'table') {
    //             $headersBySection[$field->section_id][$field->col_index] = $field->header;
    //         }
    //     }

    //     // Get user submissions
    //     $submissions = DB::table('form_submission')
    //         ->where('formid', $formId)
    //         ->where('uid', Auth::id())
    //         ->get()
    //         ->keyBy('fieldname');
    //     // dd($data);
    //     return view('admin.forms.show', compact(
    //         'form',
    //         'data',
    //         'allForms',
    //         'sections',
    //         'fieldsBySection',
    //         'gridFields',
    //         'headersBySection',
    //         'submissions'
    //     ));
    // }

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
                            $columnValue = json_encode($columnValue);
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

            \Log::info($e);
            \Log::info($e->getMessage());

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


    //final
    // public function courseList(Request $request, $formid)
    // {
    //     $statusval = $request->input('statusval');

    //     // Fetch records
    //     $query = DB::table('form_submission')->where('formid', $formid);

    //     if ($statusval) {
    //         $query->where('confirm_status', $statusval);
    //     }

    //     $records = $query->get();

    //     // Fetch column names in the order they are defined in the database
    //     $columns = DB::select(
    //         'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
    //         ['form_submission']
    //     );
    //     $allColumns = array_map(fn($col) => $col->COLUMN_NAME, $columns);

    //     // Fields to exclude from dynamic listing (static fields will be handled separately)
    //     $excluded = ['id', 'formid', 'uid', 'timecreated'];

    //     // Preserve column order from DB, but exclude the static fields
    //     $fields = [];
    //     foreach ($allColumns as $column) {
    //         if (!in_array($column, $excluded)) {
    //             $fields[] = $column;
    //         }
    //     }
    //     // Make sure 'uid' is at the beginning if you want it there
    //     if (!in_array('uid', $fields)) {
    //         array_unshift($fields, 'uid');
    //     }

    //     // Group field values per UID
    //     $users = [];
    //     foreach ($records as $record) {
    //         $uid = $record->uid;
    //         foreach ($fields as $field) {
    //             $users[$uid][$field] = $record->$field ?? '';
    //         }
    //     }

    //     $uids = array_keys($users);

    //     // Get related user data
    //     $userDetails = DB::table('users')->whereIn('id', $uids)->get()->keyBy('id');
    //     $passwords = DB::table('users')->whereIn('id', $uids)->pluck('password', 'id');

    //     // Total student count
    //     $total_students = DB::table('form_submission')
    //         ->where('formid', $formid)
    //         ->distinct('uid')
    //         ->count('uid');

    //     return view('admin.forms.course_list', compact(
    //         'records',
    //         'users',
    //         'fields',
    //         'userDetails',
    //         'passwords',
    //         'formid',
    //         'total_students',
    //         'statusval'
    //     ));
    // }

    public function courseList(Request $request, $formid)
    {
        $statusval = $request->input('statusval');

        // Fetch submission records
        $query = DB::table('fc_registration_master')->where('formid', $formid);
        if ($statusval) {
            $query->where('confirm_status', $statusval);
        }
        $records = $query->get();

        // Extract UIDs early
        $uids = $records->pluck('uid')->unique()->toArray();

        // Get dynamic field names used in this form from form_data
        $dynamicFieldNames = DB::table('form_data')
            ->where('formid', $formid)
            ->pluck('formname')
            ->toArray();

        // Always include 'uid' for mapping user details
        $fields = array_merge(['uid', 'Fullname'], $dynamicFieldNames);

        //fullnames for uid mapping
        $fullnames = DB::table('user_credentials')
            ->whereIn('pk', $uids) // use 'pk' if it maps to `uid` from your other table
            ->select(DB::raw("CONCAT(first_name, ' ', last_name) as full_name"), 'pk')
            ->pluck('full_name', 'pk'); // creates [pk => "First Last"] map

        // Group values per UID
        $users = [];

        foreach ($records as $record) {
            $uid = $record->uid;
            foreach ($fields as $field) {
                if ($field === 'Fullname') {
                    $users[$uid]['Fullname'] = $fullnames[$uid] ?? '';
                } else {
                    $users[$uid][$field] = $record->$field ?? '';
                }
            }
        }

        $uids = array_keys($users);

        // Get related user details
        $userDetails = DB::table('users')->whereIn('id', $uids)->get()->keyBy('id');
        $passwords = DB::table('users')->whereIn('id', $uids)->pluck('password', 'id');

        // Total student count (distinct users)
        $total_students = DB::table('fc_registration_master')
            ->where('formid', $formid)
            ->distinct('uid')
            ->count('uid');

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

    //  public function home()
    // {
    //     $forms = DB::table('local_form')->orderBy('sortorder')->get();
    //     return view('admin.forms.home_page', compact('forms'));
    // }
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

    //export function 
    public function exportfcformList(Request $request, $formid)
    {
        $formName = DB::table('local_form')->where('id', $formid)->value('name');
        $statusval = $request->input('statusval');
        $format = $request->input('format'); // 'xlsx', 'csv', or 'pdf'

        // Fetch registration records
        $query = DB::table('fc_registration_master')->where('formid', $formid);
        if (!empty($statusval)) {
            $query->where('confirm_status', $statusval);
        }
        $records = $query->get();

        // Extract UIDs
        $uids = $records->pluck('uid')->unique()->toArray();

        // Get dynamic field names from form_data
        $dynamicFieldNames = DB::table('form_data')
            ->where('formid', $formid)
            ->pluck('formname')
            ->toArray();

        // Fields for export (ensure order): uid, Fullname, dynamic fields...
        $fields = array_merge(['uid', 'Fullname'], $dynamicFieldNames);

        // Fetch fullnames for UIDs
        $fullnames = DB::table('user_credentials')
            ->whereIn('pk', $uids)
            ->select('pk', DB::raw("CONCAT(first_name, ' ', last_name) as full_name"))
            ->pluck('full_name', 'pk'); // [uid => full name]

        // Format user data for export
        $users = [];
        foreach ($records as $record) {
            $uid = $record->uid;
            $row = [];

            foreach ($fields as $field) {
                if ($field === 'uid') {
                    $row['uid'] = $uid;
                } elseif ($field === 'Fullname') {
                    $row['Fullname'] = $fullnames[$uid] ?? '';
                } else {
                    $row[$field] = $record->$field ?? '';
                }
            }

            $users[] = $row;
        }

        // Handle export based on format
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



    public function generatePdf($form_id, $user_id)
    {
        ini_set('pcre.backtrack_limit', '10000000');

        if (!is_numeric($form_id) || !is_numeric($user_id) || $form_id <= 0 || $user_id <= 0) {
            abort(400, 'Invalid ID parameters');
        }

        try {
            // Get form metadata
            $formInfo = DB::table('local_form')
                ->where('id', $form_id)
                ->first(['description', 'course_sdate', 'course_edate']);

            if (!$formInfo) abort(404, 'Form not found');

            $form_description = $formInfo->description;
            $form_date_range = date('d-m-Y', strtotime($formInfo->course_sdate)) . " to " . date('d-m-Y', strtotime($formInfo->course_edate));

            // Get submission (normal fields)
            $submission = DB::table('fc_registration_master')
                ->where('formid', $form_id)
                ->where('uid', $user_id)
                ->first();
            $submissionArray = (array) $submission;

            $user_name = $submissionArray['name'] ?? 'User';
            $logo_path = '';
            $base64 = '';
            $sections = [];

            // Profile image
            if (!empty($submissionArray['profile'])) {
                $path = storage_path("app/public/{$submissionArray['profile']}");
                if (file_exists($path)) {
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = base64_encode(file_get_contents($path));
                    $base64 = "data:image/{$type};base64,{$data}";
                }
            }
            // If no profile or invalid path, set default
            if (empty($base64)) {
                $defaultPath = public_path('images/dummypic.jpeg');
                if (file_exists($defaultPath)) {
                    $type = pathinfo($defaultPath, PATHINFO_EXTENSION);
                    $data = base64_encode(file_get_contents($defaultPath));
                    $base64 = "data:image/{$type};base64,{$data}";
                }
            }
            // dd($base64);

            // Fetch form structure
            $formStructure = DB::table('form_sections AS s')
                ->join('form_data AS f', 's.id', '=', 'f.section_id')
                ->where('f.formid', $form_id)
                ->select('s.id AS section_id', 's.section_title', 'f.formname', 'f.formlabel', 'f.format')
                ->orderBy('s.id')
                ->get();

            $tableProcessed = [];

            foreach ($formStructure as $item) {
                $section = $item->section_title;
                $fieldname = trim($item->formname);
                $label = $item->formlabel;
                $type = $item->format;

                if (!isset($sections[$section])) {
                    $sections[$section] = [];
                }

                // Handle table fields
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
                                $rowData[$cell->column_key] = $cell->column_value;
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
                    $sections[$section][] = [
                        'label_en' => $label,
                        'fieldvalue' => $value,
                    ];
                }
            }

            // Render HTML
            $html = view('admin.registration.form_template', [
                'form_description' => $form_description,
                'form_date_range' => $form_date_range,
                'sections' => $sections,
                'logo_path' => $base64,
                'user_name' => $user_name,
            ])->render();

            // Setup mPDF
            $tempDir = storage_path('temp/mpdf');
            if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

            $fontDir = base_path('vendor/mpdf/mpdf/ttfonts');
            $mpdf = new \Mpdf\Mpdf([
                'tempDir' => $tempDir,
                'fontDir' => [$fontDir],
                'default_font' => 'dejavusans',
            ]);

            // Optional: Devanagari font support
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








    //     public function generatePdf($form_id, $user_id)
    // {
    //     ini_set('pcre.backtrack_limit', '10000000');

    //     if (!is_numeric($form_id) || !is_numeric($user_id) || $form_id <= 0 || $user_id <= 0) {
    //         abort(400, 'Invalid ID parameters');
    //     }

    //     try {
    //         $formInfo = DB::table('local_form')
    //             ->where('id', $form_id)
    //             ->first(['description', 'course_sdate', 'course_edate']);

    //         if (!$formInfo) {
    //             abort(404, 'Form not found');
    //         }

    //         $form_description = $formInfo->description;
    //         $form_date_range = date('d-m-Y', strtotime($formInfo->course_sdate)) . " to " . date('d-m-Y', strtotime($formInfo->course_edate));

    //         $submission = DB::table('form_submission_tabledata')
    //             ->where('formid', $form_id)
    //             ->where('uid', $user_id)
    //             ->first();

    //         $submissionArray = (array) $submission;
    //         $user_name = $submissionArray['name'] ?? 'User';

    //         $formStructure = DB::table('form_sections as s')
    //             ->join('form_submission_tabledata as t', 's.id', '=', 't.section_id')
    //             ->leftJoin('form_data as d', function ($join) {
    //                 $join->on('t.section_id', '=', 'd.section_id')
    //                     ->on('t.row_index', '=', 'd.row_index')
    //                     ->on('t.col_index', '=', 'd.col_index')
    //                     ->whereColumn('t.formid', 'd.formid');
    //             })
    //             ->where('t.formid', $form_id)
    //             ->where('t.uid', $user_id)
    //             ->select(
    //                 's.section_title',
    //                 't.formid',
    //                 't.uid',
    //                 't.section_id',
    //                 't.row_index',
    //                 't.col_index',
    //                 't.column_key',
    //                 't.field_type',
    //                 't.column_value',
    //                 'd.formlabel',
    //                 'd.formtype',
    //                 'd.format',
    //                 'd.formname'
    //             )
    //             ->orderBy('s.id')
    //             ->get();

    //         $sections = [];
    //         $logo_path = '';
    //         $processedTables = [];

    //         foreach ($formStructure as $item) {
    //             $section = $item->section_title;
    //             $fieldname = trim($item->formname);
    //             $label = $item->formlabel;
    //             $type = $item->format;

    //             if (!isset($sections[$section])) {
    //                 $sections[$section] = [];
    //             }

    //             if ($type === 'table') {
    //                 $sectionId = $item->section_id;

    //                 // Ensure unique table is processed per section
    //                 $tableKey = $sectionId . '_' . $fieldname;
    //                 if (in_array($tableKey, $processedTables)) {
    //                     continue;
    //                 }

    //                 // Get table data for this section
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
    //                             $rowData[$cell->column_key] = $cell->column_value;
    //                         }

    //                         $rows[] = $rowData;
    //                     }

    //                     $sections[$section][] = [
    //                         'label_en' => $label,
    //                         'type' => 'table',
    //                         'headers' => array_keys($headers),
    //                         'rows' => $rows,
    //                     ];

    //                     $processedTables[] = $tableKey;
    //                 }

    //                 continue;
    //             }

    //             $value = $submissionArray[$fieldname] ?? null;
    //             if (!is_null($value) && $value !== '') {
    //                 $sections[$section][] = [
    //                     'label_en' => $label,
    //                     'fieldvalue' => $value,
    //                 ];
    //             }
    //         }

    //         // ✅ ADDITION: Handle individual fields from form_data not already added
    //         $fieldLabelsAdded = [];

    //         foreach ($sections as $secTitle => $fields) {
    //             foreach ($fields as $f) {
    //                 if (isset($f['label_en'])) {
    //                     $fieldLabelsAdded[] = $f['label_en'];
    //                 }
    //             }
    //         }

    //         $individualFields = DB::table('form_data as d')
    //             ->join('form_sections as s', 'd.section_id', '=', 's.id')
    //             ->where('d.formid', $form_id)
    //             ->where('d.format', '!=', 'table')
    //             ->select('s.section_title', 'd.formlabel', 'd.formname')
    //             ->orderBy('s.id')
    //             ->get();

    //         foreach ($individualFields as $field) {
    //             $sectionTitle = $field->section_title;
    //             $label = $field->formlabel;
    //             $name = $field->formname;

    //             if (in_array($label, $fieldLabelsAdded)) {
    //                 continue; // already handled
    //             }

    //             $value = $submissionArray[$name] ?? null;
    //             if (!is_null($value) && $value !== '') {
    //                 if (!isset($sections[$sectionTitle])) {
    //                     $sections[$sectionTitle] = [];
    //                 }

    //                 $sections[$sectionTitle][] = [
    //                     'label_en' => $label,
    //                     'fieldvalue' => $value,
    //                 ];
    //             }
    //         }

    //         // Convert logo to base64 (if exists)
    //         $base64 = '';
    //         if (!empty($logo_path)) {
    //             $path = storage_path("app/public/{$logo_path}");
    //             if (file_exists($path)) {
    //                 $type = pathinfo($path, PATHINFO_EXTENSION);
    //                 $data = base64_encode(file_get_contents($path));
    //                 $base64 = "data:image/{$type};base64,{$data}";
    //             }
    //         }

    //         // Render HTML view
    //         $html = view('admin.registration.form_template', [
    //             'form_description' => $form_description,
    //             'form_date_range' => $form_date_range,
    //             'sections' => $sections,
    //             'logo_path' => $base64,
    //             'user_name' => $user_name,
    //         ])->render();

    //         // Prepare PDF
    //         $tempDir = storage_path('temp/mpdf');
    //         if (!is_dir($tempDir)) {
    //             mkdir($tempDir, 0777, true);
    //         }
    //         $fontDir = base_path('vendor/mpdf/mpdf/ttfonts');

    //         $mpdf = new \Mpdf\Mpdf([
    //             'tempDir' => $tempDir,
    //             'fontDir' => [$fontDir],
    //             'default_font' => 'dejavusans',
    //         ]);

    //         // Load Devanagari font if available
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
    //         \Log::error('PDF Generation Error: ' . $e->getMessage(), [
    //             'line' => $e->getLine(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
    //         abort(500, 'An error occurred while generating the PDF.');
    //     }
    // }

}
