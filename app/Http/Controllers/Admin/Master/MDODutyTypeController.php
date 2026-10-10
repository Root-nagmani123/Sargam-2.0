<?php

namespace App\Http\Controllers\Admin\Master;

use App\DataTables\MDODutyTypeMasterDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MDODutyTypeMaster;
use App\Models\MDOEscotDutyMap;
use Illuminate\Support\Facades\DB;

class MDODutyTypeController extends Controller
{
    public function index(MDODutyTypeMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.mdo_duty_type.index');
        
    }

    /**
     * MDO / Escort / Other are looked up BY NAME (MDOEscotDutyMap::getMdoDutyTypes(),
     * LOWER(name) = 'mdo' | 'escort' | 'other'), and that lookup decides, for
     * example, whether an escort exemption must name its faculty. Renaming one
     * of these rows makes its key resolve to null, so they keep their name
     * (case aside), stay active and cannot be deleted.
     *
     * @return string|null 'mdo' | 'escort' | 'other' when $pk is one of them
     */
    private function systemTypeKey($pk): ?string
    {
        if (! is_scalar($pk) || ! ctype_digit((string) $pk)) {
            return null;
        }

        $key = array_search((int) $pk, array_map('intval', array_filter(MDOEscotDutyMap::getMdoDutyTypes())), true);

        return $key === false ? null : $key;
    }

    private function refuseSystemType(Request $request, string $message)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'status' => false, 'message' => $message], 422);
        }

        return redirect()->route('master.mdo_duty_type.index')->with('error', $message);
    }

    public function changeStatus(Request $request)
    {
        // The guard and the update must see the same pk: an array binding is
        // flattened to its first element, so pk[]=2 would pass a scalar-only guard.
        if (! is_scalar($request->pk) || ! ctype_digit((string) $request->pk)) {
            return $this->refuseSystemType($request, 'Invalid duty type.');
        }

        if ($this->systemTypeKey($request->pk) !== null && (string) $request->active_inactive !== '1') {
            return $this->refuseSystemType($request, 'MDO, Escort and Other are system duty types and cannot be deactivated.');
        }

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
                $mdoDutyType = MDODutyTypeMaster::findOrFail($request->id);

                $systemKey = $this->systemTypeKey($mdoDutyType->pk);
                if ($systemKey !== null
                    && (mb_strtolower(trim((string) $request->mdo_duty_type_name)) !== $systemKey
                        || (string) $request->active_inactive !== '1')) {
                    return $this->refuseSystemType($request, 'MDO, Escort and Other are system duty types: they cannot be renamed or deactivated.');
                }

                $mdoDutyType->update([
                    'mdo_duty_type_name' => $request->mdo_duty_type_name,
                    'active_inactive' => $request->active_inactive
                ]);
                if($request->ajax()) {
                    return response()->json([
                        'success' => true,
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
            if ($this->systemTypeKey($mdoDutyType->pk) !== null) {
                return $this->refuseSystemType($request, 'MDO, Escort and Other are system duty types and cannot be deleted.');
            }
            $mdoDutyType->delete();
            return redirect()->route('master.mdo_duty_type.index')->with('success', 'MDO Duty Type deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
        
    }
}
