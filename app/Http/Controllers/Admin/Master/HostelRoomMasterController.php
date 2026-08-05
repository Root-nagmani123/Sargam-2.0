<?php

namespace App\Http\Controllers\Admin\Master;

use App\DataTables\Master\HostelRoomMasterDataTable;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HasBrandedExport;
use Illuminate\Http\Request;
use App\Models\HostelRoomMaster;

class HostelRoomMasterController extends Controller
{
    use HasBrandedExport;

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
        $hostelRoomMaster->capacity = $request->capacity;
        // Preserve existing behaviour (default active) while allowing the modal's
        // Room Status field to set it when provided (matches Building/Floor).
        $hostelRoomMaster->active_inactive = $request->filled('active_inactive')
            ? (int) $request->active_inactive
            : ($hostelRoomMaster->active_inactive ?? 1);

        $hostelRoomMaster->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.hostel.room.index')->with('success', $message);
    }

    public function edit($id){
        $id = decrypt($id);
        $hostelRoomMaster = HostelRoomMaster::findOrFail($id);
        return view('admin.master.hostel_room.create', compact('hostelRoomMaster'));
    }

    /** Branded CSV / PDF / Print (new-design-index-page.md §4b) via the shared trait. */
    public function export($format = 'pdf') {
        $rows = [];
        $i    = 1;
        foreach (HostelRoomMaster::orderBy('hostel_room_name')->get() as $r) {
            $rows[] = [$i++, $r->hostel_room_name, $r->capacity, $r->active_inactive == 1 ? 'Active' : 'Inactive'];
        }
        return $this->brandedExport($format, 'Hostel Room', ['S. No.', 'Hostel Room Name', 'Capacity', 'Status'], $rows, 'hostel-room-master');
    }
}
