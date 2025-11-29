<?php

namespace App\Http\Controllers\Admin\Master;

use App\DataTables\MDODutyTypeMasterDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MDODutyTypeMaster;

class MDODutyTypeController extends Controller
{
    public function index()
    {
        // Show only active by default; include inactive if query param ?show=all
        $query = MDODutyTypeMaster::orderBy('pk','desc');
        if (request('show') !== 'all') {
            $query->where('active_inactive', 1);
        }
        $mdoDutyTypes = $query->paginate(10)->appends(request()->query());
        return view('admin.master.mdo_duty_type.index', compact('mdoDutyTypes'));
    }
    // {
    //     // $mdoDutyTypes = MDODutyTypeMaster::latest('pk')->get();
    //     // return view('admin.master.mdo_duty_type.index', compact('mdoDutyTypes'));
    // }

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
                'mdo_duty_type_name' => 'required|string|max:255'
            ]);

            if( $request->id ) {
                $mdoDutyType = MDODutyTypeMaster::findOrFail(decrypt($request->id));
                $mdoDutyType->update([
                    'mdo_duty_type_name' => $request->mdo_duty_type_name
                ]);
                if($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'action' => 'update',
                        'data' => [
                            'pk' => $mdoDutyType->pk,
                            'encrypted_pk' => encrypt($mdoDutyType->pk),
                            'mdo_duty_type_name' => $mdoDutyType->mdo_duty_type_name,
                            'active_inactive' => $mdoDutyType->active_inactive,
                        ]
                    ]);
                }
                return redirect()->route('master.mdo_duty_type.index')->with('success', 'MDO Duty Type updated successfully');
            }
            MDODutyTypeMaster::create(['mdo_duty_type_name' => $request->mdo_duty_type_name]);
            $created = MDODutyTypeMaster::latest('pk')->first();
            if($request->ajax()) {
                return response()->json([
                    'success' => true,
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

    public function delete($id)
    {
        try {
            $mdoDutyType = MDODutyTypeMaster::findOrFail(decrypt($id));
            $mdoDutyType->delete();
            return redirect()->route('master.mdo_duty_type.index')->with('success', 'MDO Duty Type deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
        
    }
}
