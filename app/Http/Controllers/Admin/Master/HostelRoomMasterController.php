<?php

namespace App\Http\Controllers\Admin\Master;

use App\DataTables\Master\HostelRoomMasterDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HostelRoomMaster;

class HostelRoomMasterController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:master.hostel-room-master.index', ['only' => ['index']]);
        $this->middleware('permission:master.hostel-room-master.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:master.hostel-room-master.edit', ['only' => ['edit', 'store']]);
    }
    public function index(HostelRoomMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.hostel_room.index');
    }

    public function create()
    {
        return view('admin.master.hostel_room.create');
    }

    public function store(Request $request){
        
        $request->validate([
            'hostel_room_name' => 'required|string|max:255|unique:hostel_room_master,hostel_room_name,' . ($request->pk ? decrypt($request->pk) : 'null').',pk',
            'capacity' => 'required|integer|min:1',
        ]);
        
        if($request->pk) {
            $message = 'Hostel Room updated successfully.';
            $hostelRoomMaster = HostelRoomMaster::findOrFail(decrypt($request->pk));
        }
        else {
            $message = 'Hostel Room created successfully.';
            $hostelRoomMaster = new HostelRoomMaster();
        }
        $hostelRoomMaster->hostel_room_name = $request->hostel_room_name;
        $hostelRoomMaster->active_inactive = 1;
        $hostelRoomMaster->capacity = $request->capacity;

        $hostelRoomMaster->save();

        return redirect()->route('master.hostel.room.index')->with('success', $message);
    }

    public function edit($id){
        $id = decrypt($id);
        $hostelRoomMaster = HostelRoomMaster::findOrFail($id);
        return view('admin.master.hostel_room.create', compact('hostelRoomMaster'));
    }
}
