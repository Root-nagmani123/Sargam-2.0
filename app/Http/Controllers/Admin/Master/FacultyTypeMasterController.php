<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsMasterGrid;
use App\Models\FacultyTypeMaster;
use App\Support\DataTableRedisCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FacultyTypeMasterController extends Controller
{
    use ExportsMasterGrid;

    private const LIST_CACHE_EPOCH_KEY = 'master_faculty_type_list_epoch';

    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'FacultyTypeMasterController');
    }

    public function index(Request $request)
    {
        // The grid is a client-side DataTable now (paging / search / sort all run
        // in the browser), so the whole set is handed over in one go and the
        // cache key no longer varies by page. Key bumped to v2 so a cached v1
        // paginator can't be served into the new view.
        $epoch = DataTableRedisCache::readListEpoch(self::LIST_CACHE_EPOCH_KEY);
        $cacheKey = 'master_fac_type_list:v2:' . md5(json_encode(['epoch' => $epoch]));

        $facultyTypes = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'FACULTY_TYPE_MASTER_LIST_CACHE_ENABLED',
                'seconds' => 'FACULTY_TYPE_MASTER_LIST_CACHE_SECONDS',
            ],
            'FacultyTypeMasterController@index',
            fn () => FacultyTypeMaster::orderByDesc('pk')->get()
        );

        return view('admin.master.faculty_type.index', compact('facultyTypes'));
    }

    /* ====================================================================
     * Export - CSV | Excel | PDF | Print   (rendering lives in ExportsMasterGrid)
     * ================================================================= */

    /**
     * Canonical export columns, in display order.
     *
     * Keys must match FTM_EXPORT_COLUMN_KEYS in faculty_type/index.blade.php.
     * 'sno' is not a data column; it only drives the running serial.
     *
     * @return array<string, array<string, mixed>>
     */
    private function exportColumnDefs(): array
    {
        return [
            'sno' => [
                'heading' => 'S. No.',
                'width'   => '10%',
                'align'   => 'center',
                'value'   => fn ($row, int $index) => $index + 1,
            ],
            'faculty_type' => [
                'heading' => 'Faculty Type',
                'width'   => '48%',
                'align'   => 'left',
                'value'   => fn ($row) => $row->faculty_type_name ?: 'N/A',
            ],
            'short_name' => [
                'heading' => 'Short Name',
                'width'   => '22%',
                'align'   => 'left',
                // Column is `shot_faculty_type_name` in the schema - the typo is
                // the live column name, not one to "fix" here.
                'value'   => fn ($row) => $row->shot_faculty_type_name ?: 'N/A',
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
     * The grid's rows, filtered the way the grid filters them.
     *
     * Ordering mirrors index()'s orderByDesc('pk'). The grid is a CLIENT-side
     * DataTable and its search runs over every searchable column - the name, the
     * short name AND the rendered "Active"/"Inactive" label - so all three are
     * matched here. Substring semantics are kept identical: searching "active"
     * matches "Inactive" too, exactly as it does in the browser.
     */
    private function exportQuery(string $search): Builder
    {
        $query = FacultyTypeMaster::query();

        if ($search !== '') {
            $needle = mb_strtolower($search);

            $query->where(function ($q) use ($search, $needle) {
                $q->where('faculty_type_name', 'like', '%' . $search . '%')
                  ->orWhere('shot_faculty_type_name', 'like', '%' . $search . '%');

                if (str_contains('active', $needle)) {
                    $q->orWhere('active_inactive', 1);
                }

                if (str_contains('inactive', $needle)) {
                    $q->orWhere(function ($inactive) {
                        $inactive->where('active_inactive', '!=', 1)->orWhereNull('active_inactive');
                    });
                }
            });
        }

        return $query->orderByDesc('pk');
    }

    public function export(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, self::$exportFormats, true), 404);

        $search = trim((string) $request->query('q', ''));

        return $this->renderMasterExport(
            $format,
            $this->exportQuery($search)->get(),
            $this->resolveExportColumns($request, $this->exportColumnDefs()),
            'Faculty Type',
            'FacultyType',
            $search !== '' ? 'Search: ' . $search : null,
            'No faculty types to export'
        );
    }

    public function create()
    {
        return view('admin.master.faculty_type.create');
    }

    public function store(Request $request)
    {
        // Lengths match the columns: faculty_type_name varchar(100),
        // shot_faculty_type_name varchar(50).
        $request->validate([
            'faculty_type_name' => 'required|string|max:100',
            'shot_faculty_type_name' => 'required|string|max:50',
        ], [
            'faculty_type_name.required' => 'Faculty type name is required.',
            'faculty_type_name.max' => 'Faculty type name must not exceed 100 characters.',
            'shot_faculty_type_name.required' => 'Short name is required.',
            'shot_faculty_type_name.max' => 'Short name must not exceed 50 characters.',
        ]);

        try {
            if ($request->pk) {
                $facultyType = FacultyTypeMaster::findOrFail(decrypt($request->pk));
            } else {
                // Not create($request->all()): the model is $guarded = [], so
                // every request key would be writable, and the two real columns
                // are assigned explicitly just below anyway. A bare instance also
                // saves the redundant INSERT-then-UPDATE the create() caused.
                $facultyType = new FacultyTypeMaster();
            }

            $facultyType->faculty_type_name = $request->faculty_type_name;
            $facultyType->shot_faculty_type_name = $request->shot_faculty_type_name;
            $facultyType->save();

            self::bumpListCacheEpoch();

            return redirect()->route('master.faculty.type.master.index')->with('success', 'Faculty Type created successfully');
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            return redirect()->back()->with('error', 'Something went wrong');
        }
    }

    public function edit($id)
    {
        $facultyType = FacultyTypeMaster::findOrFail(decrypt($id));

        return view('admin.master.faculty_type.create', compact('facultyType'));
    }

    public function delete($id)
    {
        try {
            $facultyType = FacultyTypeMaster::findOrFail(decrypt($id));
            $facultyType->delete();

            self::bumpListCacheEpoch();

            return redirect()->route('master.faculty.type.master.index')->with('success', 'Faculty Type deleted successfully');
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            return redirect()->back()->with('error', 'Something went wrong');
        }
    }
}
