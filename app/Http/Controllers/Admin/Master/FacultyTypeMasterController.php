<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\FacultyTypeMaster;
use App\Support\DataTableRedisCache;
use Illuminate\Http\Request;

class FacultyTypeMasterController extends Controller
{
    private const LIST_CACHE_EPOCH_KEY = 'master_faculty_type_list_epoch';

    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'FacultyTypeMasterController');
    }

    public function index(Request $request)
    {
        $epoch = DataTableRedisCache::readListEpoch(self::LIST_CACHE_EPOCH_KEY);
        // Full dataset: the listing is a client-side DataTable, which owns
        // search / pagination / "Showing N of M" in the browser.
        $cacheKey = 'master_fac_type_list:v2:' . md5(json_encode(['epoch' => $epoch]));

        $facultyTypes = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'FACULTY_TYPE_MASTER_LIST_CACHE_ENABLED',
                'seconds' => 'FACULTY_TYPE_MASTER_LIST_CACHE_SECONDS',
            ],
            'FacultyTypeMasterController@index',
            fn () => FacultyTypeMaster::latest('pk')->get()
        );

        return view('admin.master.faculty_type.index', compact('facultyTypes'));
    }

    public function create()
    {
        return redirect()->route('master.faculty.type.master.index', ['open_ftm_modal' => 'add']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'faculty_type_name' => 'required|string|max:255',
            'shot_faculty_type_name' => 'required|string|max:255',
        ]);

        try {
            if ($request->pk) {
                $facultyType = FacultyTypeMaster::findOrFail(decrypt($request->pk));
            } else {
                $facultyType = FacultyTypeMaster::create($request->all());
            }

            $facultyType->faculty_type_name = $request->faculty_type_name;
            $facultyType->shot_faculty_type_name = $request->shot_faculty_type_name;
            $facultyType->save();

            self::bumpListCacheEpoch();

            $message = $request->pk ? 'Faculty Type updated successfully.' : 'Faculty Type created successfully.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return redirect()->route('master.faculty.type.master.index')->with('success', $message);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Something went wrong.'], 500);
            }

            return redirect()->back()->with('error', 'Something went wrong');
        }
    }

    public function edit($id)
    {
        $facultyType = FacultyTypeMaster::findOrFail(decrypt($id));

        return redirect()->route('master.faculty.type.master.index', [
            'open_ftm_modal' => 'edit',
            'ftm_pk' => $id,
            'ftm_short' => $facultyType->shot_faculty_type_name,
            'ftm_name' => $facultyType->faculty_type_name,
        ]);
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
