<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\DataTableSearchHelper;
use Yajra\DataTables\Facades\DataTables;
use App\Models\CasteCategoryMaster;
use Illuminate\Validation\Rule;

class CasteCategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable();
        }

        return view('admin.setup.caste_category.index');
    }

    /**
     * Server-side feed for the listing grid (search/sort/paginate happen in SQL).
     */
    protected function datatable()
    {
        $query = CasteCategoryMaster::query();
        if (! DataTableSearchHelper::clientOrdered()) {
            $query->orderBy('pk', 'desc');
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('name', fn ($row) => e($row->category_name))
            ->addColumn('action', function ($row) {
                return '<div class="d-flex gap-2">'
                    .'<a href="'.route('admin.setup.caste_category.edit', encrypt($row->pk)).'" class="text-success openEditCasteCategory" title="Edit">'
                    .'<i class="material-icons material-symbols-rounded" style="font-size:22px;">edit</i></a>'
                    .'<form action="'.route('admin.setup.caste_category.delete', encrypt($row->pk)).'" method="POST" onsubmit="return confirm(\'Delete this Caste Category?\')">'
                    .csrf_field().method_field('DELETE')
                    .'<button type="submit" class="btn btn-link p-0 text-danger" title="Delete">'
                    .'<i class="material-icons material-symbols-rounded" style="font-size:22px;">delete</i></button>'
                    .'</form></div>';
            })
            ->addColumn('status', function ($row) {
                return '<div class="form-check form-switch d-inline-block">'
                    .'<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                    .' data-table="caste_category_master" data-column="active_inactive" data-id="'.e($row->pk).'"'
                    .($row->active_inactive == 1 ? ' checked' : '').'></div>';
            })
            ->setRowAttr(['data-pk' => fn ($row) => $row->pk])
            ->filterColumn('name', fn ($q, $keyword) => $q->where('category_name', 'like', "%{$keyword}%"))
            ->orderColumn('name', 'category_name $1')
            ->rawColumns(['action', 'status'])
            ->make(true);
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
