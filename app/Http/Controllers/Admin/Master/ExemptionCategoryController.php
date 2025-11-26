<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ExemptionCategoryMaster; 
use App\Models\ExemptionMedicalSpecialityMaster; 

class ExemptionCategoryController extends Controller
{
   
    public function index()
    {
        $categories = ExemptionCategoryMaster::paginate(10);
        return view('admin.master.exemption_categories_master.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.master.exemption_categories_master.create_edit');
    }

public function store(Request $request)
{
    $request->validate([
        'exemp_category_name' => 'required|string|max:100',
        'exemp_cat_short_name' => 'required|string|max:50',
    ]);

    try {
        if ($request->id) {
            // Update Mode
            $category = ExemptionCategoryMaster::findOrFail(decrypt($request->id));
            $category->exemp_category_name = $request->exemp_category_name;
            $category->exemp_cat_short_name = $request->exemp_cat_short_name;
            $category->active_inactive = $request->active_inactive ?? 1;
            $category->save();

            $message = 'Category updated successfully.';
        } else {
            // Create Mode
            ExemptionCategoryMaster::create([
                'exemp_category_name' => $request->exemp_category_name,
                'exemp_cat_short_name' => $request->exemp_cat_short_name,
                'active_inactive' => $request->active_inactive ?? 1,
            ]);

            $message = 'Category created successfully.';
        }

        return redirect()->route('master.exemption.category.master.index')->with('success', $message);
    } catch (\Exception $e) {
        return redirect()->back()->with('error', $e->getMessage())->withInput();
    }
}


    public function edit($id)
    {
        print_r(decrypt($id));
        $exemptionCategory = ExemptionCategoryMaster::findOrFail(decrypt($id));
        // print_r($category);die;
        return view('admin.master.exemption_categories_master.create_edit', compact('exemptionCategory'));
    }

    public function delete($id)
    {
        ExemptionCategoryMaster::destroy(decrypt($id));
        return redirect()->route('master.exemption.category.master.index')->with('success', 'Category deleted successfully.');
    }





    public function medicalSpecialityIndex()
{
    $specialities = ExemptionMedicalSpecialityMaster::paginate(10);
    return view('admin.master.exemption_medical_speciality.index', compact('specialities'));
}

public function medicalSpecialityCreate()
{
    return view('admin.master.exemption_medical_speciality.create_edit');
}

public function medicalSpecialityStore(Request $request)
{
    $request->validate([
        'medical_speciality_name' => 'required|string|max:100',
    ]);

    try {
        if ($request->id) {

            $speciality = ExemptionMedicalSpecialityMaster::findOrFail(decrypt($request->id));
            $speciality->speciality_name = $request->medical_speciality_name;
            $speciality->active_inactive = $request->active_inactive ?? 1;
            $speciality->save();

            $message = 'Medical Speciality updated successfully.';
        } else {
            ExemptionMedicalSpecialityMaster::create([
                'speciality_name' => $request->medical_speciality_name,
                'active_inactive' => $request->active_inactive ?? 1,
            ]);

            $message = 'Medical Speciality created successfully.';
        }

        return redirect()->route('master.exemption.medical.speciality.index')->with('success', $message);
    } catch (\Exception $e) {
        return redirect()->back()->with('error', $e->getMessage())->withInput();
    }
}

public function medicalSpecialityEdit($id)
{
    $speciality = ExemptionMedicalSpecialityMaster::findOrFail(decrypt($id));
    return view('admin.master.exemption_medical_speciality.create_edit', compact('speciality'));
}

public function medicalSpecialityDelete($id)
{
    ExemptionMedicalSpecialityMaster::destroy(decrypt($id));
    return redirect()->route('master.exemption.medical.speciality.index')->with('success', 'Medical Speciality deleted successfully.');
}

}
