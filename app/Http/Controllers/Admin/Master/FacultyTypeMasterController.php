<?php

namespace App\Http\Controllers\Admin\Master;

use App\DataTables\Master\FacultyTypeMasterDataTable;
use App\Http\Controllers\Controller;
use App\Models\FacultyTypeMaster;
use App\Support\DataTableRedisCache;
use Illuminate\Http\Request;

class FacultyTypeMasterController extends Controller
{
    /** Shared with {@see FacultyTypeMasterDataTable} so both cache layers invalidate together. */
    public const LIST_CACHE_EPOCH_KEY = 'master_faculty_type_list_epoch';

    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'FacultyTypeMasterController');
    }

    public function index()
    {
        // Listing is cached inside the DataTable's ajax(), keyed off the same epoch.
        return (new FacultyTypeMasterDataTable())->render('admin.master.faculty_type.index');
    }

    public function create()
    {
        return view('admin.master.faculty_type.create');
    }

    public function store(Request $request)
    {
        // Columns are varchar(100) / varchar(50) — keep the rules in sync with them.
        $request->validate([
            'faculty_type_name' => 'required|string|max:100',
            'shot_faculty_type_name' => 'required|string|max:50',
        ], [], [
            'faculty_type_name' => 'faculty type name',
            'shot_faculty_type_name' => 'short name',
        ]);

        try {
            $isUpdate = (bool) $request->pk;
            $facultyType = $isUpdate
                ? FacultyTypeMaster::findOrFail(decrypt($request->pk))
                : new FacultyTypeMaster();

            $facultyType->faculty_type_name = $request->faculty_type_name;
            $facultyType->shot_faculty_type_name = $request->shot_faculty_type_name;
            $facultyType->save();

            self::bumpListCacheEpoch();

            $message = $isUpdate ? 'Faculty Type updated successfully' : 'Faculty Type created successfully';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'success', 'message' => $message]);
            }

            return redirect()->route('master.faculty.type.master.index')->with('success', $message);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Something went wrong'], 500);
            }

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
