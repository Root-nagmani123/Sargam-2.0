<?php

namespace App\Http\Controllers\Admin\Registration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class FormEditController extends Controller
{
    // public function fc_edit($form_id)
    // {
    //     // Retrieve form sections and fields from the database
    //     $sections = DB::table('form_sections')
    //         ->where('formid', $form_id)
    //         ->orderBy('sort_order')
    //         ->get();

    //     $fields = DB::table('form_data')
    //         ->where('formid', $form_id)
    //         ->get();

    //     return view('admin.registration.fc_edit', compact('form_id', 'sections', 'fields'));
    // }
    public function fc_edit($form_id)
    {
        // Retrieve form sections and fields
        $sections = DB::table('form_sections')
            ->where('formid', $form_id)
            ->orderBy('sort_order')
            ->get();

        $fields = DB::table('form_data')
            ->where('formid', $form_id)
            ->get();

        // Get all column names from the form_submission table
        $columns = Schema::getColumnListing('form_submission');

        // Optional: Exclude technical columns
        $excluded = ['id', 'formid', 'uid', 'created_at', 'updated_at'];
        $filteredColumns = array_filter($columns, function ($col) use ($excluded) {
            return !in_array($col, $excluded);
        });

        return view('admin.registration.fc_edit', [
            'form_id' => $form_id,
            'sections' => $sections,
            'fields' => $fields,
            'columns' => $filteredColumns,
        ]);
    }

    public function fc_update(Request $request)
    {
        // Validate the request
        $request->validate([
            'section_title.*' => 'required|string',
            'field_label.*' => 'required|string',
            'field_name.*' => 'required|string',
        ]);
        // Begin database transaction
        DB::beginTransaction();
        $form_id = $request->form_id;
        try {
            // Handle sections
            foreach ($request->section_id as $index => $section_id) {
                $title = trim($request->section_title[$index]);
                $order = $request->sort_order[$index] ?? $index;

                if ($section_id === 'new') {
                    // Insert new section
                    $newId = DB::table('form_sections')->insertGetId([
                        'formid' => $form_id,
                        'section_title' => $title,
                        'sort_order' => $order,
                    ]);

                    $newSectionMap[$index] = $newId; // map temporary index to actual ID
                } else {
                    // Update existing section
                    DB::table('form_sections')
                        ->where('id', $section_id)
                        ->update([
                            'section_title' => $title,
                            'sort_order' => $order,
                            // 'updated_at' => now(),
                        ]);
                }
            }

            // Handle fields
            foreach ($request->field_id as $index => $field_id) {
                $is_required = isset($request->is_required[$index]) ? 1 : 0;
                // $section_id = $request->field_section[$index];
                $temp_section_id = $request->field_section[$index];
                $section_id = ($temp_section_id === 'new' || !is_numeric($temp_section_id))
                    ? ($newSectionMap[$index] ?? null)
                    : $temp_section_id;

                // Determine if this is a table format field
                $is_table_format = in_array($request->field_type[$index], ['Label', 'View/Download', 'Radio Button', 'Textarea', 'Checkbox', 'Select Box']);

                if ($field_id === 'new') {
                    // Insert new field
                    if ($is_table_format) {
                        DB::table('form_data')->insert([
                            'formid' => $form_id,
                            'section_id' => $section_id,
                            'field_type' => $request->field_type[$index],
                            'field_title' => $request->field_name[$index],
                            'formlabel' => $request->field_label[$index],
                            'fieldoption' => $request->field_options[$index] ?? null,
                            'field_options' => $request->field_options[$index] ?? null,
                            'field_checkbox_options' => $request->field_options[$index] ?? null,
                            'field_radio_options' => $request->field_options[$index] ?? null,
                            'field_url' => ($request->field_type[$index] === 'View/Download') ? $request->field_options[$index] : null,
                            'required' => $is_required,
                            'format' => 'table',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        DB::table('form_data')->insert([
                            'formid' => $form_id,
                            'section_id' => $section_id,
                            'formname' => $request->field_name[$index],
                            'formtype' => $request->field_type[$index],
                            'formlabel' => $request->field_label[$index],
                            'fieldoption' => $request->field_options[$index] ?? null,
                            'required' => $is_required,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                } else {
                    // Update existing field
                    if ($is_table_format) {
                        DB::table('form_data')
                            ->where('id', $field_id)
                            ->update([
                                'section_id' => $section_id,
                                'field_type' => $request->field_type[$index],
                                'field_title' => $request->field_name[$index],
                                'formlabel' => $request->field_label[$index],
                                'fieldoption' => $request->field_options[$index] ?? null,
                                'field_options' => $request->field_options[$index] ?? null,
                                'field_checkbox_options' => $request->field_options[$index] ?? null,
                                'field_radio_options' => $request->field_options[$index] ?? null,
                                'field_url' => ($request->field_type[$index] === 'View/Download') ? $request->field_options[$index] : null,
                                'required' => $is_required,
                                'format' => 'table',
                                'updated_at' => now(),
                            ]);
                    } else {
                        DB::table('form_data')
                            ->where('id', $field_id)
                            ->update([
                                'section_id' => $section_id,
                                'formname' => $request->field_name[$index],
                                'formtype' => $request->field_type[$index],
                                'formlabel' => $request->field_label[$index],
                                'fieldoption' => $request->field_options[$index] ?? null,
                                'required' => $is_required,
                                'updated_at' => now(),
                            ]);
                    }
                }
            }

            // Handle deletions
            if ($request->has('delete_sections')) {
                DB::table('form_sections')
                    ->whereIn('id', $request->delete_sections)
                    ->delete();
            }

            if ($request->has('delete_fields')) {
                DB::table('form_data')
                    ->whereIn('id', $request->delete_fields)
                    ->delete();
            }

            // Commit transaction
            DB::commit();

            return redirect()->route('forms.index', $form_id)
                ->with('success', 'Form fields updated successfully!');
        } catch (\Exception $e) {
            // \Log::info($e);
            // dd($e->getLine());
            dd($e->getMessage());
            // Log the error message
            // Rollback transaction on error
            DB::rollBack();

            return back()->with('error', 'Error updating form: ' . $e->getMessage());
        }
    }


    //registration logo page method

    public function LogoCreate()
    {
        $data = DB::table('registration_logo')->first(); 
        return view('admin.forms.logo_page', compact('data'));
    }

    public function LogoUpdate(Request $request)
    {
        //     $request->validate([
        //         'logo1' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        //         'logo2' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        //         'logo3' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        //         'logo4' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        //         'heading' => 'required|string|max:255',
        //         'sub_heading' => 'required|string|max:255',
        //     ]);

        //     $data = DB::table('registration_logo')->first();

        //     // Handle uploads
        //     $logo1 = $request->file('logo1') ? $request->file('logo1')->store('logos', 'public') : ($data->logo1 ?? null);
        //     $logo2 = $request->file('logo2') ? $request->file('logo2')->store('logos', 'public') : ($data->logo2 ?? null);
        //     $logo3 = $request->file('logo3') ? $request->file('logo3')->store('logos', 'public') : ($data->logo3 ?? null);
        //     $logo4 = $request->file('logo4') ? $request->file('logo4')->store('logos', 'public') : ($data->logo4 ?? null);

        //     $formData = [
        //         'logo1' => $logo1,
        //         'logo2' => $logo2,
        //         'logo3' => $logo3,
        //         'logo4' => $logo4,
        //         'heading' => $request->heading,
        //         'sub_heading' => $request->sub_heading,
        //         'updated_at' => now(),
        //     ];

        //     if ($data) {
        //         DB::table('registration_logo')->where('id', $data->id)->update($formData);
        //         return redirect()->back()->with('success', 'Registration Page updated successfully!');
        //     } else {
        //         $formData['created_at'] = now();
        //         DB::table('registration_logo')->insert($formData);
        //         return redirect()->back()->with('success', 'Registration Page created successfully!');
        //     }
        // }

        $request->validate([
            'logo1' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'logo2' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'logo3' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'logo4' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'heading' => 'required|string|max:255',
            'sub_heading' => 'required|string|max:255',
        ]);

        $data = DB::table('registration_logo')->first();

        $formData = [
            'heading' => $request->heading,
            'sub_heading' => $request->sub_heading,
            'updated_at' => now(),
        ];

        for ($i = 1; $i <= 4; $i++) {
            $logoField = "logo$i";
            $removeField = "remove_logo$i";

            if ($request->has($removeField)) {
                // Remove image from storage
                if (!empty($data->$logoField)) {
                    Storage::disk('public')->delete($data->$logoField);
                }
                $formData[$logoField] = null;
            } elseif ($request->file($logoField)) {
                // Upload new image
                $path = $request->file($logoField)->store('logos', 'public');

                // Delete old one if exists
                if (!empty($data->$logoField)) {
                    Storage::disk('public')->delete($data->$logoField);
                }

                $formData[$logoField] = $path;
            } else {
                // Keep existing
                $formData[$logoField] = $data->$logoField ?? null;
            }
        }

        if ($data) {
            DB::table('registration_logo')->where('id', $data->id)->update($formData);
            return redirect()->back()->with('success', 'Registration Page updated successfully!');
        } else {
            $formData['created_at'] = now();
            DB::table('registration_logo')->insert($formData);
            return redirect()->back()->with('success', 'Registration Page created successfully!');
        }
    }
}
