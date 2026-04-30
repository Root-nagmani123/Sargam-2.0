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
        $epoch = DataTableRedisCache::readListEpoch(self::LIST_CACHE_EPOCH_KEY);
        $page = max(1, (int) $request->query('page', 1));
        $cacheKey = 'master_fac_exp_list:v1:' . md5(json_encode(['epoch' => $epoch, 'page' => $page]));

        $faculties = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'FACULTY_EXPERTISE_MASTER_LIST_CACHE_ENABLED',
                'seconds' => 'FACULTY_EXPERTISE_MASTER_LIST_CACHE_SECONDS',
            ],
            'FacultyExpertiseMasterController@index',
            fn () => FacultyExpertiseMaster::latest('pk')->paginate(10)
        );

        return view('admin.master.faculty_expertise_master.index', compact('faculties'));
    }

    public function create() {
        return view("admin.master.faculty_expertise_master.create");
    }

    public function store(Request $request) {
        $request->validate([
            'expertise_name' => 'required|string|max:255|unique:faculty_expertise_master,expertise_name',
        ]);

        if( $request->id ) {

            // Update existing record
            $id = decrypt($request->id);
            $expertise = FacultyExpertiseMaster::find($id);
        }
        else {
            // Create new record
            $expertise = new FacultyExpertiseMaster();
            $expertise->created_date = now();
        }
        $expertise->expertise_name = $request->expertise_name;
        $expertise->created_by = auth()->user()->id;
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
