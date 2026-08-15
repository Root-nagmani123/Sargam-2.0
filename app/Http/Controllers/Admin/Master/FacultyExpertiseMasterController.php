<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\FacultyExpertiseMaster;
use App\Support\DataTableRedisCache;
use Illuminate\Http\Request;

class FacultyExpertiseMasterController extends Controller
{
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
        $expertise->save();

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
