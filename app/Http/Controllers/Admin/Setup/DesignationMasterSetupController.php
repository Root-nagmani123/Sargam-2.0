<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DesignationMaster;
use Illuminate\Validation\Rule;
use App\DataTables\MemberDataTable;

class DesignationMasterSetupController extends Controller
{
    public function index(Request $request)
    {
        $designations = DesignationMaster::orderBy('pk','desc')->paginate(10);
        return view('admin.setup.designation_master.index', compact('designations'));
    }

    public function create(Request $request)
    {
        if($request->ajax()) {
            return view('admin.setup.designation_master._form');
        }
        return redirect()->route('admin.setup.designation_master.index');
    }

    public function edit(Request $request, $id)
    {
        try { $pk = decrypt($id); } catch(\Exception $e){ abort(404); }
        $designation = DesignationMaster::findOrFail($pk);
        if($request->ajax()) {
            return view('admin.setup.designation_master._form', compact('designation'));
        }
        return redirect()->route('admin.setup.designation_master.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'designation_name' => ['required','string','max:150', Rule::unique('designation_master','designation_name')],
        ]);
        $model = new DesignationMaster();
        $model->designation_name = $validated['designation_name'];
        $model->active_inactive = 1;
        $model->save();
        // PR #319 re-review F-059: MemberDataTable does NOT currently read or display
        // designation_master in any form (verified: grep "designation" against
        // app/DataTables/MemberDataTable.php returns zero matches) — this bump (and
        // the two further calls below, in update()/delete()) is therefore a harmless
        // no-op today, kept in case a future column add restores the dependency the
        // original comment here assumed.
        MemberDataTable::bumpListingCacheEpoch();
        if($request->ajax()) {
            return response()->json([
                'success'=>true,
                'action'=>'create',
                'data'=>[
                    'pk'=>$model->pk,
                    'encrypted_pk'=>encrypt($model->pk),
                    'designation_name'=>$model->designation_name,
                    'active_inactive'=>$model->active_inactive,
                ]
            ]);
        }
        return redirect()->route('admin.setup.designation_master.index')->with('success','Designation created');
    }

    public function update(Request $request, $id)
    {
        try { $pk = decrypt($id); } catch(\Exception $e){ abort(404); }
        $model = DesignationMaster::findOrFail($pk);
        $validated = $request->validate([
            'designation_name' => ['required','string','max:150', Rule::unique('designation_master','designation_name')->ignore($model->pk,'pk')],
        ]);
        $model->designation_name = $validated['designation_name'];
        $model->save();
        MemberDataTable::bumpListingCacheEpoch();
        if($request->ajax()) {
            return response()->json([
                'success'=>true,
                'action'=>'update',
                'data'=>[
                    'pk'=>$model->pk,
                    'encrypted_pk'=>encrypt($model->pk),
                    'designation_name'=>$model->designation_name,
                    'active_inactive'=>$model->active_inactive,
                ]
            ]);
        }
        return redirect()->route('admin.setup.designation_master.index')->with('success','Designation updated');
    }

    public function delete(Request $request, $id)
    {
        try { $pk = decrypt($id); } catch(\Exception $e){ abort(404); }
        $model = DesignationMaster::findOrFail($pk);
        $model->delete();
        MemberDataTable::bumpListingCacheEpoch();
        if($request->ajax()) { return response()->json(['success'=>true,'deleted'=>true]); }
        return redirect()->route('admin.setup.designation_master.index')->with('success','Deleted');
    }
}
