<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CasteCategoryMaster;
use Illuminate\Validation\Rule;

class CasteCategoryController extends Controller
{
    public function index(Request $request)
    {
        $casteCategories = CasteCategoryMaster::orderBy('pk','desc')->paginate(10);
        return view('admin.setup.caste_category.index', compact('casteCategories'));
    }

    public function create(Request $request)
    {
        if($request->ajax()){
            return view('admin.setup.caste_category._form');
        }
        return redirect()->route('admin.setup.caste_category.index');
    }

    public function edit(Request $request, $id)
    {
        try { $pk = decrypt($id); } catch(\Exception $e){ abort(404); }
        $caste = CasteCategoryMaster::findOrFail($pk);
        if($request->ajax()){
            return view('admin.setup.caste_category._form', compact('caste'));
        }
        return redirect()->route('admin.setup.caste_category.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_name' => ['required','string','max:150', Rule::unique('caste_category_master','category_name')],
        ]);
        $model = new CasteCategoryMaster();
        $model->category_name = $validated['category_name'];
        $model->active_inactive = 1;
        $model->save();
        if($request->ajax()){
            return response()->json([
                'success'=>true,
                'action'=>'create',
                'data'=>[
                    'pk'=>$model->pk,
                    'encrypted_pk'=>encrypt($model->pk),
                    'category_name'=>$model->category_name,
                    'active_inactive'=>$model->active_inactive,
                ]
            ]);
        }
        return redirect()->route('admin.setup.caste_category.index')->with('success','Caste Category created');
    }

    public function update(Request $request, $id)
    {
        try { $pk = decrypt($id); } catch(\Exception $e){ abort(404); }
        $model = CasteCategoryMaster::findOrFail($pk);
        $validated = $request->validate([
            'category_name' => ['required','string','max:150', Rule::unique('caste_category_master','category_name')->ignore($model->pk,'pk')],
        ]);
        $model->category_name = $validated['category_name'];
        $model->save();
        if($request->ajax()){
            return response()->json([
                'success'=>true,
                'action'=>'update',
                'data'=>[
                    'pk'=>$model->pk,
                    'encrypted_pk'=>encrypt($model->pk),
                    'category_name'=>$model->category_name,
                    'active_inactive'=>$model->active_inactive,
                ]
            ]);
        }
        return redirect()->route('admin.setup.caste_category.index')->with('success','Caste Category updated');
    }

    public function delete(Request $request, $id)
    {
        try { $pk = decrypt($id); } catch(\Exception $e){ abort(404); }
        $model = CasteCategoryMaster::findOrFail($pk);
        $model->delete();
        if($request->ajax()) { return response()->json(['success'=>true,'deleted'=>true]); }
        return redirect()->route('admin.setup.caste_category.index')->with('success','Deleted');
    }
}
