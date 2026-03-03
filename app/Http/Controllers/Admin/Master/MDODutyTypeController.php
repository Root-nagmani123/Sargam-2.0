<?php

namespace App\Http\Controllers\Admin\Master;

use App\DataTables\MDODutyTypeMasterDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MDODutyTypeMaster;
use Illuminate\Support\Facades\DB;

class MDODutyTypeController extends Controller
{
    public function index(MDODutyTypeMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.mdo_duty_type.index');
        
    }

    public function changeStatus(Request $request)
    {
        DB::table('mdo_duty_type_master')
            ->where('pk', $request->pk)
            ->update([
                'active_inactive' => $request->active_inactive,
                'modified_date' => now()
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Status updated successfully'
        ]);
    }

    public function create()
    {
        if(request()->ajax()) {
            return view('admin.master.mdo_duty_type._form');
        }
        return view('admin.master.mdo_duty_type.create');
    }

    public function edit($id)
    {
        $mdoDutyType = MDODutyTypeMaster::findOrFail(decrypt($id));
        if(request()->ajax()) {
            return view('admin.master.mdo_duty_type._form', compact('mdoDutyType'));
        }
        return view('admin.master.mdo_duty_type.create', compact('mdoDutyType'));
    }

    public function store(Request $request)
    { 
        try {
            $request->validate([
                'mdo_duty_type_name' => 'required|string|max:255',
                'active_inactive' => 'required'
            ]);

            if($request->id){
                $mdoDutyType = MDODutyTypeMaster::findOrFail(decrypt($request->id));
                $mdoDutyType->update([
                    'mdo_duty_type_name' => $request->mdo_duty_type_name,
                    'active_inactive' => $request->active_inactive
                ]);
                if($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'MDO Duty Type updated successfully',
                        'action' => 'update',
                        'data' => [
                            'pk' => $mdoDutyType->pk,
                            'encrypted_pk' =>$mdoDutyType->pk,
                            'mdo_duty_type_name' => $mdoDutyType->mdo_duty_type_name,
                            'active_inactive' => $mdoDutyType->active_inactive,
                        ]
                    ]);
                }
                return redirect()->route('master.mdo_duty_type.index')->with('success', 'MDO Duty Type updated successfully');
            }
            MDODutyTypeMaster::create(['mdo_duty_type_name' => $request->mdo_duty_type_name,'active_inactive' => $request->active_inactive]);
            $created = MDODutyTypeMaster::latest('pk')->first();
            if($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'MDO Duty Type created successfully',
                    'action' => 'create',
                    'data' => [
                        'pk' => $created->pk,
                        'encrypted_pk' => encrypt($created->pk),
                        'mdo_duty_type_name' => $created->mdo_duty_type_name,
                        'active_inactive' => $created->active_inactive,
                    ]
                ]);
            }
            return redirect()->route('master.mdo_duty_type.index')->with('success', 'MDO Duty Type created successfully');
        } catch (\Exception $e) {
            if($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->withErrors($e->getMessage());
        }   
    }

    public function delete(Request $request)
    {
        try {
            $mdoDutyType = MDODutyTypeMaster::findOrFail($request->id);
            $mdoDutyType->delete();
            return redirect()->route('master.mdo_duty_type.index')->with('success', 'MDO Duty Type deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
        
    }
}
