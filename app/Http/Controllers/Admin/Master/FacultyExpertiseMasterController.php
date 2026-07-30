<?php

namespace App\Http\Controllers\Admin\Master;

use App\DataTables\Master\FacultyExpertiseMasterDataTable;
use App\Http\Controllers\Controller;
use App\Models\FacultyExpertiseMaster;
use App\Support\DataTableRedisCache;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FacultyExpertiseMasterController extends Controller
{
    /** Shared with {@see FacultyExpertiseMasterDataTable} so both cache layers invalidate together. */
    public const LIST_CACHE_EPOCH_KEY = 'master_faculty_expertise_list_epoch';

    /**
     * Same Redis store / TTL pattern as other master listings (see {@see DataTableRedisCache}).
     */
    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'FacultyExpertiseMasterController');
    }

    public function index()
    {
        // Listing is cached inside the DataTable's ajax(), keyed off the same epoch.
        return (new FacultyExpertiseMasterDataTable())->render('admin.master.faculty_expertise_master.index');
    }

    public function create() {
        return view("admin.master.faculty_expertise_master.create");
    }

    public function store(Request $request) {
        $id = $request->id ? decrypt($request->id) : null;

        $request->validate([
            'expertise_name' => [
                'required',
                'string',
                // expertise_name is varchar(50) — keep in sync with the column.
                'max:50',
                // Must ignore the row being edited, else re-saving it collides with itself.
                Rule::unique('faculty_expertise_master', 'expertise_name')->ignore($id, 'pk'),
            ],
        ], [], ['expertise_name' => 'expertise name']);

        if( $id ) {
            // Update existing record
            $expertise = FacultyExpertiseMaster::find($id);

            if( !$expertise ) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['status' => 'error', 'message' => 'Expertise not found.'], 404);
                }

                return redirect()->route('master.faculty.expertise.index')->with('error', 'Expertise not found.');
            }
        }
        else {
            // Create new record
            $expertise = new FacultyExpertiseMaster();
            $expertise->created_date = now();
            $expertise->created_by = auth()->id();
            $expertise->active_inactive = 1;
        }
        $expertise->expertise_name = $request->expertise_name;
        $expertise->save();

        self::bumpListCacheEpoch();

        $message = $id ? 'Expertise updated successfully.' : 'Expertise created successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.faculty.expertise.index')->with('success', $message);
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
