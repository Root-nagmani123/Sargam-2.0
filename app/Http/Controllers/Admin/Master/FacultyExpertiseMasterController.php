<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ExportsMasterGrid;
use App\Models\FacultyExpertiseMaster;
use App\Support\DataTableRedisCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FacultyExpertiseMasterController extends Controller
{
    use ExportsMasterGrid;

    private const LIST_CACHE_EPOCH_KEY = 'master_faculty_expertise_list_epoch';

    /**
     * Same Redis store / TTL pattern as other master listings (see {@see DataTableRedisCache}).
     */
    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'FacultyExpertiseMasterController');
    }

    public function index(Request $request)
    {
        // The grid is a client-side DataTable now (paging / search / sort all run
        // in the browser), so the whole set is handed over in one go and the
        // cache key no longer varies by page. Key bumped to v2 so a cached v1
        // paginator can't be served into the new view.
        $epoch = DataTableRedisCache::readListEpoch(self::LIST_CACHE_EPOCH_KEY);
        $cacheKey = 'master_fac_exp_list:v2:' . md5(json_encode(['epoch' => $epoch]));

        $faculties = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'FACULTY_EXPERTISE_MASTER_LIST_CACHE_ENABLED',
                'seconds' => 'FACULTY_EXPERTISE_MASTER_LIST_CACHE_SECONDS',
            ],
            'FacultyExpertiseMasterController@index',
            fn () => FacultyExpertiseMaster::latest('pk')->get()
        );

        return view('admin.master.faculty_expertise_master.index', compact('faculties'));
    }

    /* ====================================================================
     * Export - CSV | Excel | PDF | Print   (rendering lives in ExportsMasterGrid)
     * ================================================================= */

    /**
     * Canonical export columns, in display order.
     *
     * Keys must match FEX_EXPORT_COLUMN_KEYS in
     * faculty_expertise_master/index.blade.php. 'sno' is not a data column; it
     * only drives the running serial.
     *
     * @return array<string, array<string, mixed>>
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
            'expertise' => [
                'heading' => 'Faculty Expertise',
                'width'   => '68%',
                'align'   => 'left',
                'value'   => fn ($row) => $row->expertise_name ?: 'N/A',
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
     * Ordering mirrors index()'s latest('pk'). The grid is a CLIENT-side
     * DataTable whose search runs over both searchable columns - the expertise
     * name AND the rendered "Active"/"Inactive" label - so the status labels are
     * matched here too. Substring semantics are kept identical: searching
     * "active" matches "Inactive" as well, exactly as it does in the browser.
     */
    private function exportQuery(string $search): Builder
    {
        $query = FacultyExpertiseMaster::query();

        if ($search !== '') {
            $needle = mb_strtolower($search);

            $query->where(function ($q) use ($search, $needle) {
                $q->where('expertise_name', 'like', '%' . $search . '%');

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
            'Faculty Expertise',
            'FacultyExpertise',
            $search !== '' ? 'Search: ' . $search : null,
            'No faculty expertise to export'
        );
    }

    public function create() {
        return view("admin.master.faculty_expertise_master.create");
    }

    public function store(Request $request) {
        $pk = $request->id ? decrypt($request->id) : null;

        $request->validate([
            // max:50 matches the column (varchar(50)); the unique rule has to
            // ignore the row being edited or re-saving it fails on its own name.
            'expertise_name' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('faculty_expertise_master', 'expertise_name')->ignore($pk, 'pk'),
            ],
        ], [
            'expertise_name.required' => 'Expertise name is required.',
            'expertise_name.max'      => 'Expertise name must not exceed 50 characters.',
            'expertise_name.unique'   => 'This expertise name already exists.',
        ]);

        if( $pk ) {

            // Update existing record
            $expertise = FacultyExpertiseMaster::find($pk);
        }
        else {
            // Create new record
            $expertise = new FacultyExpertiseMaster();
            $expertise->created_date = now();
        }
        $expertise->expertise_name = $request->expertise_name;
        // User's primary key is `pk`, not `id` — ->id resolves to null here.
        $expertise->created_by = auth()->user()?->getKey();

        try {
            $expertise->save();
        } catch (\Illuminate\Database\QueryException $e) {
            // The unique rule above is a read-then-write: two submissions in the
            // same instant both pass it. The database index is what actually
            // prevents the duplicate, so turn its error into the same field
            // message the validator would have produced rather than a 500.
            if (($e->errorInfo[1] ?? null) === 1062) {
                return back()
                    ->withInput()
                    ->withErrors(['expertise_name' => 'This expertise name already exists.']);
            }

            throw $e;
        }

        self::bumpListCacheEpoch();

        return redirect()->route('master.faculty.expertise.index')->with('success', 'Expertise saved successfully.');
    }

    public function edit(string $id) {
        if( !$id ) {
            return redirect()->route('master.faculty.expertise.index')->with('error', 'Invalid request.');
        }
        $expertise = FacultyExpertiseMaster::find(decrypt($id));
        if( !$expertise ) {
            return redirect()->route('master.faculty.expertise.index')->with('error', 'Expertise not found.');
        }

        return view("admin.master.faculty_expertise_master.create", compact('expertise'));
    }

    public function delete(string $id) {

        if( !$id ) {
            return redirect()->route('master.faculty.expertise.index')->with('error', 'Invalid request.');
        }
        $expertise = FacultyExpertiseMaster::find(decrypt($id));
        if( !$expertise ) {
            return redirect()->route('master.faculty.expertise.index')->with('error', 'Expertise not found.');
        }
        if( $expertise->delete() ) {
            self::bumpListCacheEpoch();

            return redirect()->route('master.faculty.expertise.index')->with('success', 'Expertise deleted successfully.');
        }
        else {
            return redirect()->route('master.faculty.expertise.index')->with('error', 'Failed to delete expertise.');
        }
    }
}
