<?php

namespace Modules\Protocol\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Protocol\Entities\ProtocolRequest;
use Modules\Protocol\Http\Requests\ReviewProtocolRequest;
use App\Models\HostelBuildingMaster;
use Modules\Protocol\Services\DriverMasterService;
use Illuminate\Http\Request;
use Modules\Protocol\Entities\ProtocolDriverMaster;

class DriverMasterController extends Controller
{

    protected $service;
    public function __construct(DriverMasterService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->service->getDatatable($request);
        }
        $pageData = $this->service->pageData();
        return view('protocol::admin.drivers-master.index', compact('pageData'));
    }

    public function create(Request $request)
    {
        return view('protocol::admin.drivers-master.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'type' => [
                'required',
                'string',
                'max:255',
            ],

            'dob' => [
                'required',
                'date',
                'before:today',
            ],

            'driver_no' => [
                'required',
                'string',
                'max:10',
                'unique:protocol_driver_masters,driver_no',
            ],

            'driving_licence_no' => [
                'required',
                'string',
                'max:255',
                'unique:protocol_driver_masters,driving_licence_no',
            ],

            'licence_expiry_date' => [
                'required',
                'date',
                'after:today',
            ],

            'hiring_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'helper_one' => [
                'nullable',
                'string',
                'max:255',
            ],

            'helper_two' => [
                'nullable',
                'string',
                'max:255',
            ],

            'note_about_driver' => [
                'nullable',
                'string',
            ],

            'country_id' => [
                'required',
                'integer',
                'exists:country_master,pk',
            ],

            'state_id' => [
                'required',
                'integer',
                'exists:state_master,pk',
            ],

            'city_id' => [
                'required',
                'integer',
                'exists:city_master,pk',
            ],

            'postal_code' => [
                'required',
                'string',
                'max:6',
                'regex:/^[0-9]+$/',
            ],

            'email' => [
                'required',
                'email',
                'max:150',
            ],

            'address' => [
                'required',
                'string',
            ],
        ]);
        try {
            $data = $this->service->store($request->all());
            
            if ($data) {
                //dd($data);
                return redirect()->route('protocol.driver-master.index')->with('success', 'Driver info. saved successfully');
            } else {
                //dd('else');
                return redirect()->back()->with('error', 'Failed to save driver info.');
            }
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    public function edit($id){
      //  dd('done');
       try {
        $data = ProtocolDriverMaster::findOrFail($id);
        return view('protocol::admin.drivers-master.edit',compact('data'));
       } catch (\Throwable $th) {
         return redirect()->back()->with('error', $th->getMessage());
       }
    }

    public function update($id,Request $request){
        //dd($request->all(),$id);
        $request->validate([
    'name' => [
        'required',
        'string',
        'max:255',
    ],

    'type' => [
        'required',
        'string',
        'max:255',
    ],

    'dob' => [
        'required',
        'date',
        'before:today',
    ],

    'driver_no' => [
        'required',
        'string',
        'max:10',
        'unique:protocol_driver_masters,driver_no,' . $id,
    ],

    'driving_licence_no' => [
        'required',
        'string',
        'max:255',
        'unique:protocol_driver_masters,driving_licence_no,' . $id,
    ],

    'licence_expiry_date' => [
        'required',
        'date',
        'after:today',
    ],

    'hiring_date' => [
        'required',
        'date',
        'before_or_equal:today',
    ],

    'helper_one' => [
        'nullable',
        'string',
        'max:255',
    ],

    'helper_two' => [
        'nullable',
        'string',
        'max:255',
    ],

    'note_about_driver' => [
        'nullable',
        'string',
    ],

    'country_id' => [
        'required',
        'integer',
        'exists:country_master,pk',
    ],

    'state_id' => [
        'required',
        'integer',
        'exists:state_master,pk',
    ],

    'city_id' => [
        'required',
        'integer',
        'exists:city_master,pk',
    ],

    'postal_code' => [
        'required',
        'string',
        'max:6',
        'regex:/^[0-9]+$/',
    ],

    'email' => [
        'required',
        'email',
        'max:150',
    ],

    'address' => [
        'required',
        'string',
    ],
]);
        try {
           $data =  $this->service->update($id,$request->all());
           //dd($data);
        if ($data) {
                return redirect()->route('protocol.driver-master.index')->with('success', 'Driver info. updated successfully');
            } else {
                return redirect()->back()->with('error', 'Failed to update driver info.');
            }
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    public function destroy($driver)
    {
        //dd($driver);
    try {
        if($this->service->delete($driver)){
         return redirect()->back()->with('success', 'Driver info. deleted successfully');   
        }
         return redirect()->back()->with('error', 'Failed to delete driver info.');
    } catch (\Throwable $th) {
        return redirect()->back()->with('error', $th->getMessage());
    }

    return redirect()
        ->route('protocol.driver-master.index')
        ->with('success', 'Driver deleted successfully.');
    }
}
