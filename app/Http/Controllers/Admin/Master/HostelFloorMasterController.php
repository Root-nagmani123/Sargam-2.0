<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HasBrandedExport;
use Illuminate\Http\Request;
use App\DataTables\Master\HostelFloorMasterDataTable;
// use App\Models\HostelFloorMaster;
use App\Models\FloorMaster;

class HostelFloorMasterController extends Controller
{
    use HasBrandedExport;

    public function index(HostelFloorMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.hostel_floor.index');
    }

    public function create()
    {
        return view('admin.master.hostel_floor.create');
    }

    public function store(Request $request){

        $request->validate([
            'floor_name' => 'required|string|max:255|unique:floor_master,floor_name,' . ($request->pk ? decrypt($request->pk) : 'null').',pk',
        ]);

        if($request->pk) {
            $message = 'Floor updated successfully.';
            $floorMaster = FloorMaster::findOrFail(decrypt($request->pk));
        }
        else {
            $message = 'Floor created successfully.';
            $floorMaster = new FloorMaster();
        }
        $floorMaster->floor_name = $request->floor_name;
        // Preserve existing behaviour (default active) while allowing the modal's
        // Floor Status field to set it when provided.
        $floorMaster->active_inactive = $request->filled('active_inactive')
            ? (int) $request->active_inactive
            : ($floorMaster->active_inactive ?? 1);

        $floorMaster->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.hostel.floor.index')->with('success', $message);
    }

    public function edit($id){
        $id = decrypt($id);
        $hostelFloorMaster = FloorMaster::findOrFail($id);
        return view('admin.master.hostel_floor.create', compact('hostelFloorMaster'));
    }

    public function destroy($id){
        $id = decrypt($id);
        $floorMaster = FloorMaster::findOrFail($id);
        $floorMaster->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Floor deleted successfully.']);
        }

        return redirect()->route('master.hostel.floor.index')->with('success', 'Floor deleted successfully.');
    }

    /** Branded CSV / PDF / Print (new-design-index-page.md §4b) via the shared trait. */
    public function export($format = 'pdf') {
        $rows = [];
        $i    = 1;
        foreach (FloorMaster::orderBy('floor_name')->get() as $f) {
            $rows[] = [$i++, $f->floor_name, $f->active_inactive == 1 ? 'Active' : 'Inactive'];
        }
        return $this->brandedExport($format, 'Hostel Floor', ['S. No.', 'Floor Name', 'Status'], $rows, 'floor-master');
    }
}
