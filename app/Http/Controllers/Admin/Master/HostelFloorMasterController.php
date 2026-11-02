<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Concerns\ExportsMasterGrid;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\DataTables\Master\HostelFloorMasterDataTable;
// use App\Models\HostelFloorMaster;
use App\Models\FloorMaster;

class HostelFloorMasterController extends Controller
{
    use ExportsMasterGrid;

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

    /*
     * Export - CSV | Excel | PDF | Print   (rendering lives in ExportsMasterGrid)
     *
     * Keys are what the index page sends as ?cols= (HF_EXPORT_COLUMN_KEYS there);
     * only the columns the grid is showing are exported.
     */
    private function exportColumnDefs(): array
    {
        return [
            'sno' => [
                'heading' => 'S. No.',
                'width'   => '12%',
                'align'   => 'center',
                'value'   => fn ($row, int $index) => $index + 1,
            ],
            'floor_name' => [
                'heading' => 'Floor Name',
                'width'   => '68%',
                'align'   => 'left',
                'value'   => fn ($row) => $row->floor_name ?? '-',
            ],
            'status' => [
                'heading' => 'Status',
                'width'   => '20%',
                'align'   => 'center',
                'value'   => fn ($row) => ((int) $row->active_inactive === 1) ? 'Active' : 'Inactive',
            ],
        ];
    }

    /**
     * The grid's own query, minus paging: HostelFloorMasterDataTable::query()
     * ordering plus the search the grid is showing. Yajra searches only
     * floor_name, word by word (multi_term) — every word must match.
     */
    private function exportQuery(string $search): Builder
    {
        $query = FloorMaster::query();

        foreach (preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) as $term) {
            $query->where('floor_name', 'like', '%' . $term . '%');
        }

        return $query->latest('pk');
    }

    public function export(Request $request, string $format = 'excel')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, self::$exportFormats, true), 404);

        $q = $request->query('q', '');
        $search = is_string($q) ? trim($q) : '';
        $rows = $this->exportQuery($search)->get();

        return $this->renderMasterExport(
            $format,
            $rows,
            $this->resolveExportColumns($request, $this->exportColumnDefs()),
            'Floor Master',
            'FloorMaster',
            $search !== '' ? 'Search: ' . $search : null,
            'No floors to export'
        );
    }
}
