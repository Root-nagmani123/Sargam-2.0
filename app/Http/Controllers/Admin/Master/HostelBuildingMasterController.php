<?php

namespace App\Http\Controllers\Admin\Master;

use App\DataTables\Master\BuildingMasterDataTable;
use App\Http\Controllers\Concerns\ExportsMasterGrid;
use App\Http\Controllers\Controller;
use App\Models\BuildingMaster;
// use App\Models\HostelBuildingMaster;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HostelBuildingMasterController extends Controller
{
    use ExportsMasterGrid;

    protected $buildingType;

    public function __construct()
    {
        $this->buildingType = BuildingMaster::$buildingType;
    }

    public function index(BuildingMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.hostel_building.index', ['buildingType' => $this->buildingType]);
    }

    public function create()
    {
        return view('admin.master.hostel_building.create', ['buildingType' => $this->buildingType]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'building_name' => 'required|string|max:255|unique:building_master,building_name,'.($request->pk ? decrypt($request->pk) : 'null').',pk',
            'no_of_floors' => 'required|integer|min:0',
            'no_of_rooms' => 'required|integer|min:0',
            // building_master.building_type is an ENUM, not a varchar: a value
            // outside the list passes a plain string rule and is then rejected by
            // MySQL (error 1265 under STRICT_TRANS_TABLES), surfacing as a 500
            // instead of a 422. Validate against the same list the form offers,
            // so the column's domain is enforced where the error is reportable.
            'building_type' => ['required', 'string', Rule::in(array_keys($this->buildingType))],
        ]);

        if ($request->pk) {
            $message = 'Building updated successfully.';
            $buildingMaster = BuildingMaster::findOrFail(decrypt($request->pk));
        } else {
            $message = 'Building created successfully.';
            $buildingMaster = new BuildingMaster;
        }
        $buildingMaster->building_name = $request->building_name;
        $buildingMaster->no_of_floors = $request->no_of_floors;
        $buildingMaster->no_of_rooms = $request->no_of_rooms;
        $buildingMaster->building_type = $request->building_type;
        // Preserve existing behaviour (default active) while allowing the modal's
        // Building Status field to set it when provided.
        $buildingMaster->active_inactive = $request->filled('active_inactive')
            ? (int) $request->active_inactive
            : ($buildingMaster->active_inactive ?? 1);

        $buildingMaster->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.hostel.building.index')->with('success', $message);
    }

    public function edit($id)
    {
        $id = decrypt($id);
        $hostelBuildingMaster = BuildingMaster::findOrFail($id);

        return view('admin.master.hostel_building.create', compact('hostelBuildingMaster'), ['buildingType' => $this->buildingType]);
    }

    /*
     * Export - CSV | Excel | PDF | Print   (rendering lives in ExportsMasterGrid)
     *
     * Keys are what the index page sends as ?cols= (HB_EXPORT_COLUMN_KEYS there);
     * only the columns the grid is showing are exported.
     */
    private function exportColumnDefs(): array
    {
        return [
            'sno' => [
                'heading' => 'S. No.',
                'width'   => '8%',
                'align'   => 'center',
                'value'   => fn ($row, int $index) => $index + 1,
            ],
            'building_name' => [
                'heading' => 'Building Name',
                'width'   => '32%',
                'align'   => 'left',
                'value'   => fn ($row) => $row->building_name ?? '-',
            ],
            'no_of_floors' => [
                'heading' => 'No. of Floors',
                'width'   => '14%',
                'align'   => 'center',
                'value'   => fn ($row) => $row->no_of_floors ?? '-',
            ],
            'no_of_rooms' => [
                'heading' => 'No. of Rooms',
                'width'   => '14%',
                'align'   => 'center',
                'value'   => fn ($row) => $row->no_of_rooms ?? '-',
            ],
            'building_type' => [
                'heading' => 'Building Type',
                'width'   => '18%',
                'align'   => 'left',
                'value'   => fn ($row) => $row->building_type ?? '-',
            ],
            'status' => [
                'heading' => 'Status',
                'width'   => '14%',
                'align'   => 'center',
                'value'   => fn ($row) => ((int) $row->active_inactive === 1) ? 'Active' : 'Inactive',
            ],
        ];
    }

    /**
     * The grid's own query, minus paging: BuildingMasterDataTable::query()
     * ordering plus the search the grid is showing. Yajra searches the four
     * Column::make() columns, word by word (multi_term): every word must match
     * at least one of them — reproduced here so the export is the screen.
     */
    private function exportQuery(string $search): Builder
    {
        $query = BuildingMaster::query();

        foreach (preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) as $term) {
            $like = '%' . $term . '%';
            $query->where(function ($q) use ($like) {
                $q->where('building_name', 'like', $like)
                    ->orWhere('no_of_floors', 'like', $like)
                    ->orWhere('no_of_rooms', 'like', $like)
                    ->orWhere('building_type', 'like', $like);
            });
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
            'Building Master',
            'BuildingMaster',
            $search !== '' ? 'Search: ' . $search : null,
            'No buildings to export',
            'landscape'
        );
    }

    public function destroy($id)
    {
        $id = decrypt($id);
        $buildingMaster = BuildingMaster::findOrFail($id);
        $buildingMaster->delete();

        return redirect()->route('master.hostel.building.index')->with('success', 'Building deleted successfully.');
    }
}
