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
    public function fc_edit(Request $request, $form_id)
    {
        // dd($request->all());
        if ($request->has('deleted_sections')) {
            foreach ($request->deleted_sections as $sectionId) {
                // Delete related fields first
                DB::table('form_data')->where('section_id', $sectionId)->delete();

                // Then delete the section
                DB::table('form_sections')->where('id', $sectionId)->delete();
            }
        }


        // Retrieve form sections and fields
        $sections = DB::table('form_sections')
            ->where('formid', $form_id)
            ->orderBy('sort_order')
            ->get();

        $fields = DB::table('form_data')
            ->where('formid', $form_id)
            ->get();

        // Get all column names from the fc_registration_master table
        $columns = Schema::getColumnListing('fc_registration_master');

        // Optional: Exclude technical columns
        $excluded = ['id', 'formid', 'uid', 'created_at', 'updated_at'];
        $filteredColumns = array_values(array_filter($columns, function ($col) use ($excluded) {
            return !in_array($col, $excluded);
        }));

        // dd($filteredColumns);

        return view('admin.registration.fc_edit', [
            'form_id' => $form_id,
            'sections' => $sections,
            'fields' => $fields,
            'columns' => $filteredColumns,
        ]);
    }

    // public function fc_update(Request $request)
    // {
    //     // dd($request->all());
    //     $request->validate([
    //         'section_title.*' => 'required|string',
    //         'field_label.*' => 'required|string',
    //         'field_name.*' => 'required|string',
    //     ]);

    //     DB::beginTransaction();
    //     $form_id = $request->form_id;
    //     $newSectionMap = [];

    //     try {
    //         // Handle Sections
    //         if ($request->has('section_id') && is_array($request->section_id)) {
    //             foreach ($request->section_id as $index => $section_id) {
    //                 $title = trim($request->section_title[$index]);
    //                 $order = $request->sort_order[$index] ?? $index;

    //                 if ($section_id === 'new') {
    //                     // Insert new section
    //                     $newId = DB::table('form_sections')->insertGetId([
    //                         'formid' => $form_id,
    //                         'section_title' => $title,
    //                         'sort_order' => $order,
    //                     ]);

    //                     $newSectionMap[$index] = $newId;
    //                 } else {
    //                     // Update existing section
    //                     DB::table('form_sections')->where('id', $section_id)->update([
    //                         'section_title' => $title,
    //                         'sort_order' => $order,
    //                     ]);
    //                 }
    //             }
    //         }

    //         // Handle Fields
    //         if ($request->has('field_id') && is_array($request->field_id)) {
    //             foreach ($request->field_id as $index => $field_id) {
    //                 $field_type = $request->field_type[$index];
    //                 // Check required for existing fields by ID, and for new fields by index
    //                 if ($field_id === 'new') {
    //                     $is_required = isset($request->is_required[$index]) ? 1 : 0;
    //                 } else {
    //                     $is_required = isset($request->is_required[$field_id]) ? 1 : 0;
    //                 }

    //                 // Resolve Section ID
    //                 $temp_section_id = $request->field_section[$index];
    //                 $section_id = ($temp_section_id === 'new' || !is_numeric($temp_section_id))
    //                     ? ($newSectionMap[$index] ?? null)
    //                     : $temp_section_id;

    //                 $is_table_format = in_array($field_type, ['Label', 'View/Download', 'Radio Button', 'Textarea', 'Checkbox', 'Select Box']);

    //                 $common_data = [
    //                     'formid' => $form_id,
    //                     'section_id' => $section_id,
    //                     'formlabel' => $request->field_label[$index],
    //                     'required' => $is_required,
    //                     'updated_at' => now(),
    //                 ];

    //                 if ($is_table_format) {
    //                     $field_data = array_merge($common_data, [
    //                         'field_type' => $field_type,
    //                         'field_title' => $request->field_name[$index],
    //                         'fieldoption' => $request->field_options[$index] ?? null,
    //                         'field_options' => $request->field_options[$index] ?? null,
    //                         'field_checkbox_options' => $request->field_options[$index] ?? null,
    //                         'field_radio_options' => $request->field_options[$index] ?? null,
    //                         'field_url' => ($field_type === 'View/Download') ? $request->field_options[$index] : null,
    //                         'format' => 'table',
    //                     ]);
    //                 } else {
    //                     $field_data = array_merge($common_data, [
    //                         'formname' => $request->field_name[$index],
    //                         'formtype' => $field_type,
    //                         'fieldoption' => $request->field_options[$index] ?? null,
    //                     ]);
    //                 }

    //                 if ($field_id === 'new') {
    //                     $field_data['created_at'] = now();
    //                     DB::table('form_data')->insert($field_data);
    //                 } else {
    //                     DB::table('form_data')->where('id', $field_id)->update($field_data);
    //                 }
    //             }
    //         }

    //         // Handle Deletions
    //         if ($request->has('delete_sections')) {
    //             DB::table('form_sections')->whereIn('id', $request->delete_sections)->delete();
    //         }

    //         if ($request->has('delete_fields')) {
    //             DB::table('form_data')->whereIn('id', $request->delete_fields)->delete();
    //         }

    //         DB::commit();
    //         return redirect()->route('forms.index', $form_id)->with('success', 'Form fields updated successfully!');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->with('error', 'Error updating form: ' . $e->getMessage());
    //     }
    // }

    public function fc_update(Request $request)
    {
        $request->validate([
            'section_title.*' => 'required|string',
            // 'field_label.*' => 'required|string',
            // 'field_name.*' => 'required|string',
        ]);

        DB::beginTransaction();
        $form_id = $request->form_id;
        $newSectionMap = []; // Maps "new-1", "new-2", etc. to inserted DB IDs

        try {
            // Handle Sections
            if ($request->has('section_id') && is_array($request->section_id)) {
                foreach ($request->section_id as $index => $section_id) {
                    $title = trim($request->section_title[$index]);
                    $order = $request->sort_order[$index] ?? $index;
                    $layout = $request->section_layout[$index] ?? 'col-4'; // New layout field

                    if (str_starts_with($section_id, 'new')) {
                        // Insert new section and map the "new-1" key to DB ID
                        $newId = DB::table('form_sections')->insertGetId([
                            'formid' => $form_id,
                            'section_title' => $title,
                            'sort_order' => $order,
                            'layout' => $layout,

                            // 'created_at' => now(),
                            // 'updated_at' => now(),
                        ]);
                        $newSectionMap[$section_id] = $newId;
                    } else {
                        // Update existing section
                        DB::table('form_sections')->where('id', $section_id)->update([
                            'section_title' => $title,
                            'sort_order' => $order,
                            'layout' => $layout,
                            // 'updated_at' => now(),
                        ]);
                    }
                }
            }

            // Handle Fields
            if ($request->has('field_id') && is_array($request->field_id)) {

                foreach ($request->field_id as $index => $field_id) {
                    $field_type = $request->field_type[$index];

                    $is_required = ($field_id === 'new')
                        ? (isset($request->is_required[$index]) ? 1 : 0)
                        : (isset($request->is_required[$field_id]) ? 1 : 0);

                    $temp_section_id = $request->field_section[$index];
                    $section_id = (str_starts_with($temp_section_id, 'new'))
                        ? ($newSectionMap[$temp_section_id] ?? null)
                        : $temp_section_id;

                    // Define field data
                    $common_data = [
                        'formid' => $form_id,
                        'section_id' => $section_id,
                        'formlabel' => $request->field_label[$index],
                        'required' => $is_required,
                        'updated_at' => now(),
                    ];

                    $is_table_format = in_array($field_type, ['Label', 'View/Download', 'Radio Button', 'Textarea', 'Checkbox', 'Select Box']);

                    if ($is_table_format) {
                        $field_data = array_merge($common_data, [
                            'field_type' => $field_type,
                            'field_title' => $request->field_name[$index],
                            'fieldoption' => $request->field_options[$index] ?? null,
                            'field_options' => $request->field_options[$index] ?? null,
                            'field_checkbox_options' => $request->field_options[$index] ?? null,
                            'field_radio_options' => $request->field_options[$index] ?? null,
                            'field_url' => ($field_type === 'View/Download') ? $request->field_options[$index] : null,
                            'format' => 'table',
                        ]);
                    } else {
                        $field_data = array_merge($common_data, [
                            'formname' => $request->field_name[$index],
                            'formtype' => $field_type,
                            'fieldoption' => $request->field_options[$index] ?? null,
                        ]);
                    }

                    if ($field_id === 'new') {
                        // $field_data['created_at'] = now();
                        DB::table('form_data')->insert($field_data);
                    } else {
                        DB::table('form_data')->where('id', $field_id)->update($field_data);
                    }
                }
            }

            // Handle Deletions
            if ($request->has('delete_sections')) {
                DB::table('form_sections')->whereIn('id', $request->delete_sections)->delete();
            }

            if ($request->has('delete_fields')) {
                DB::table('form_data')->whereIn('id', $request->delete_fields)->delete();
            }

            DB::commit();
            return redirect()->route('forms.index', $form_id)->with('success', 'Form fields updated successfully!');
        } catch (\Exception $e) {
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
