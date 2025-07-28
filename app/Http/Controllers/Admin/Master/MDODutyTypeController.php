<?php

namespace App\Http\Controllers\Admin\Master;

use App\DataTables\MDODutyTypeMasterDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MDODutyTypeMaster;

class MDODutyTypeController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:master.mdo_duty_type.index', ['only' => ['index']]);
        $this->middleware('permission:master.mdo_duty_type.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:master.mdo_duty_type.edit', ['only' => ['edit', 'store']]);
        $this->middleware('permission:master.mdo_duty_type.delete', ['only' => ['delete']]);
    }

    public function index(MDODutyTypeMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.mdo_duty_type.index');
    }
    // {
    //     // $mdoDutyTypes = MDODutyTypeMaster::latest('pk')->get();
    //     // return view('admin.master.mdo_duty_type.index', compact('mdoDutyTypes'));
    // }

    public function create()
    {
        return view('admin.master.mdo_duty_type.create');
    }

    public function edit($id)
    {
        $mdoDutyType = MDODutyTypeMaster::findOrFail(decrypt($id));
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
                return redirect()->route('master.mdo_duty_type.index')->with('success', 'MDO Duty Type updated successfully');
            }
            MDODutyTypeMaster::create(['mdo_duty_type_name' => $request->mdo_duty_type_name]);
            return redirect()->route('master.mdo_duty_type.index')->with('success', 'MDO Duty Type created successfully');
        } catch (\Exception $e) {
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