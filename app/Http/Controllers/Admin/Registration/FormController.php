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


class FormController extends Controller
{
    public function index(Request $request)
    {
        // $forms = DB::table('local_form')->orderBy('sortorder')->get();
        // $forms = DB::table('local_form')->orderBy('sortorder')->paginate(3);

        // return view('admin.registration.index', compact('forms'));
        $query = DB::table('local_form')->orderBy('sortorder');

        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $forms = $query->paginate(3);

        return view('admin.registration.index', compact('forms'));
    }

    public function create()
    {
        return view('admin.registration.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'shortname' => 'required|string|max:100',
            'description' => 'required|string',
            'course_sdate' => 'required|date',
            'course_edate' => 'required|date|after_or_equal:course_sdate',
        ]);

        $sortorder = DB::table('local_form')->max('sortorder') + 1;

        $id = DB::table('local_form')->insertGetId([
            'name' => $request->name,
            'shortname' => $request->shortname,
            'description' => $request->description,
            'course_sdate' => $request->course_sdate,
            'course_edate' => $request->course_edate,
            'visible' => $request->has('visible'),
            // 'fc_registration' => $request->has('fc_registration'),
            // 'createcohort' => $request->has('createcohort'),
            'sortorder' => $sortorder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('forms.createnew', ['formid' => $id])->with('success', 'Form created successfully.');

        // return redirect()->route('forms.index')->with('success', 'Form submitted!');
    }

    public function edit($id)
    {
        $form = DB::table('local_form')->where('id', $id)->first();
        if (!$form) {
            abort(404); // or redirect()->back()->with('error', 'Form not found');
        }
        return view('admin.registration.edit', compact('form'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'shortname' => 'required|string|max:100',
            'description' => 'required|string',
            'course_sdate' => 'required|date',
            'course_edate' => 'required|date|after_or_equal:course_sdate',
        ]);

        DB::table('local_form')->where('id', $id)->update([
            'name' => $request->name,
            'shortname' => $request->shortname,
            'description' => $request->description,
            'course_sdate' => $request->course_sdate,
            'course_edate' => $request->course_edate,
            'visible' => $request->has('visible'),
            // 'fc_registration' => $request->has('fc_registration'),
            // 'createcohort' => $request->has('createcohort'),
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
        $columns = collect(DB::select('SHOW COLUMNS FROM form_submission'))->pluck('Field');

        return view('admin.registration.createform', [
            'formid' => $formid,
            'submissionColumns' => $columns,
        ]);
    }

    public function saveform(Request $request, $formid)
    {
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
            foreach ($fieldNames as $index => $name) {
                $name = trim($name);
                if (empty($name)) continue;

                $sectionIndex = $fieldSections[$index] ?? 0;
                if (!isset($sectionIds[$sectionIndex])) {
                    throw new \Exception("Invalid section index: $sectionIndex");
                }

                DB::table('form_data')->insert([
                    'formid'     => $formid,
                    'section_id' => $sectionIds[$sectionIndex],
                    'formname'   => $name,
                    'formtype'   => $fieldTypes[$index] ?? 'text',
                    'formlabel'  => $fieldLabels[$index] ?? '',
                    'fieldoption' => $fieldOptions[$index] ?? '',
                    'required'   => in_array($index, $isRequireds) ? 1 : 0,
                    'layout'     => $fieldLayouts[$index] ?? 'vertical',
                    'created_at' => now(),
                    'updated_at' => now(),
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
        // Get form details
        $form = DB::table('local_form')
            ->where('id', $formId)
            ->where('visible', 1)
            ->first();

        if (!$form) {
            abort(404, 'Form not found');
        }

        // Get form sections
        $sections = DB::table('form_sections')
            ->where('formid', $formId)
            ->orderBy('sort_order')
            ->get();

        // Get form fields organized by section
        $fieldsBySection = [];
        $gridFields = [];

        $fields = DB::table('form_data')
            ->where('formid', $formId)
            ->orderBy('section_id')
            ->orderBy('row_index')
            ->orderBy('col_index')
            ->get();

        foreach ($fields as $field) {
            if ($field->format === 'table') {
                $fieldsBySection[$field->section_id][$field->row_index][$field->col_index] = $field;
            } else {
                $gridFields[$field->section_id][] = $field;
            }
        }

        // Get headers for table sections
        $headersBySection = [];
        $headerFields = DB::table('form_data')
            ->where('formid', $formId)
            ->where('format', 'table')
            ->get();

        foreach ($headerFields as $field) {
            if (!isset($headersBySection[$field->section_id][$field->col_index])) {
                $headersBySection[$field->section_id][$field->col_index] = $field->header;
            }
        }

        // Get user submissions
        $submissions = DB::table('form_submission')
            ->where('formid', $formId)
            ->where('uid', Auth::id())
            ->get()
            ->keyBy('fieldname');

        return view('admin.forms.show', compact(
            'form',
            'sections',
            'fieldsBySection',
            'gridFields',
            'headersBySection',
            'submissions'
        ));
    }


    //finaalll
    public function submit(Request $request, $formId)
    {
        // dd($request->all());
        try {
            $userId = Auth::id();
            $timestamp = now()->timestamp;

            // Delete previous submission
            // DB::table('form_submission')
            //     ->where('formid', $formId)
            //     ->where('uid', $userId)
            //     ->delete();

            // Check if a submission already exists
            $existingSubmission = DB::table('form_submission')
                ->where('formid', $formId)
                ->where('uid', $userId)
                ->first();
            // dd($existingSubmission);

            // Handle dynamic fields (field_*)
            $dynamicFields = collect($request->all())
                ->filter(fn($value, $key) => str_starts_with($key, 'field_'))
                ->mapWithKeys(fn($value, $key) => [Str::replaceFirst('field_', '', $key) => $value])
                ->toArray();

            // File handling (example for field_profile)
            if ($request->hasFile('field_profile')) {
                $dynamicFields['profile'] = $request->file('field_profile')
                    ->store("form-uploads/{$formId}/{$userId}", 'public');
            }

            // Insert into main submission table if fields exist
            // Insert or Update form_submission
            if (!empty($dynamicFields)) {
                $dynamicFields['formid'] = $formId;
                $dynamicFields['uid'] = $userId;
                $dynamicFields['timecreated'] = $timestamp;
                // dd($dynamicFields);

                if ($existingSubmission) {
                    DB::table('form_submission')
                        ->where('formid', $formId)
                        ->where('uid', $userId)
                        ->update($dynamicFields);
                } else {
                    DB::table('form_submission')->insert($dynamicFields);
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
                            $filePath = $request->file($valueKey)->store("form-uploads/{$formId}/{$userId}/tables", 'public');
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
    public function courseList(Request $request, $formid)
    {
        $statusval = $request->input('statusval');

        // Fetch records
        $query = DB::table('form_submission')->where('formid', $formid);

        if ($statusval) {
            $query->where('confirm_status', $statusval);
        }

        $records = $query->get();

        // Fetch column names in the order they are defined in the database
        $columns = DB::select(
            'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            ['form_submission']
        );
        $allColumns = array_map(fn($col) => $col->COLUMN_NAME, $columns);

        // Fields to exclude from dynamic listing (static fields will be handled separately)
        $excluded = ['id', 'formid', 'uid', 'timecreated'];

        // Preserve column order from DB, but exclude the static fields
        $fields = [];
        foreach ($allColumns as $column) {
            if (!in_array($column, $excluded)) {
                $fields[] = $column;
            }
        }
        // Make sure 'uid' is at the beginning if you want it there
        if (!in_array('uid', $fields)) {
            array_unshift($fields, 'uid');
        }

        // Group field values per UID
        $users = [];
        foreach ($records as $record) {
            $uid = $record->uid;
            foreach ($fields as $field) {
                $users[$uid][$field] = $record->$field ?? '';
            }
        }

        $uids = array_keys($users);

        // Get related user data
        $userDetails = DB::table('users')->whereIn('id', $uids)->get()->keyBy('id');
        $passwords = DB::table('users')->whereIn('id', $uids)->pluck('password', 'id');

        // Total student count
        $total_students = DB::table('form_submission')
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
    public function main_page()
    {
        $forms = DB::table('local_form')->orderBy('sortorder')->get();
        return view('admin.forms.main_page', compact('forms'));

    }
     public function logo_page()
    {
        $forms = DB::table('local_form')->orderBy('sortorder')->get();
        return view('admin.forms.logo_page', compact('forms'));

    }

    //export function 

    // public function exportfcformList(Request $request, $formid)
    // {
    //     $statusval = $request->input('statusval');

    //     // Build query
    //     $query = DB::table('form_submission')->where('formid', $formid);
    //     if ($statusval) {
    //         $query->where('confirm_status', $statusval);
    //     }
    //     $records = $query->get();

    //     // Get ordered column names
    //     $columns = DB::select(
    //         'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
    //         ['form_submission']
    //     );
    //     $allColumns = array_map(fn($col) => $col->COLUMN_NAME, $columns);

    //     $excluded = ['id', 'formid', 'timecreated'];
    //     $fields = [];

    //     foreach ($allColumns as $column) {
    //         if (!in_array($column, $excluded)) {
    //             $fields[] = $column;
    //         }
    //     }

    //     // Optional: move 'uid' to front
    //     if (!in_array('uid', $fields)) {
    //         array_unshift($fields, 'uid');
    //     }

    //     return Excel::download(new FcformListExport($records, $fields), 'course_list.xlsx');
    // }


    public function exportfcformList(Request $request, $formid)
    {
        $formName = DB::table('local_form')->where('id', $formid)->value('name'); // adjust if your table/column names differ
        $statusval = $request->input('statusval');
        $format = $request->input('format'); // 'xlsx', 'csv', or 'pdf'

        $query = DB::table('form_submission')->where('formid', $formid);
        if ($statusval) {
            $query->where('confirm_status', $statusval);
        }
        $records = $query->get();

        $columns = DB::select(
            'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            ['form_submission']
        );
        $allColumns = array_map(fn($col) => $col->COLUMN_NAME, $columns);

        $excluded = ['id', 'formid', 'timecreated'];
        $fields = array_filter($allColumns, fn($col) => !in_array($col, $excluded));

        // Ensure 'uid' is first
        $fields = array_values($fields);
        if (($key = array_search('uid', $fields)) !== false) {
            unset($fields[$key]);
        }
        array_unshift($fields, 'uid');

        if ($format === 'csv') {
            return Excel::download(new FcformListExport($records, $fields), $formName . '.csv', \Maatwebsite\Excel\Excel::CSV);
        } elseif ($format === 'pdf') {
            $pdf = Pdf::loadView('admin.forms.export.fcform_pdf', [
                'records' => $records,
                'fields' => $fields,
                'formName' => $formName,
            ])->setPaper('A4', 'landscape');
            return $pdf->download($formName .'.pdf');
        } else { // default Excel
            return Excel::download(new FcformListExport($records, $fields),  $formName .'.xlsx');
        }
    }
}

