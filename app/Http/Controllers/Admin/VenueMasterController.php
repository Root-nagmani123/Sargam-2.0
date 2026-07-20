<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{VenueMaster};
use App\Support\DataTableRedisCache;
use Illuminate\Support\Collection;

class VenueMasterController extends Controller
{
    private const INDEX_LIST_EPOCH_KEY = 'venue_master_index_list_epoch';

    public static function bumpIndexCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::INDEX_LIST_EPOCH_KEY, 'VenueMasterController');
    }

    public function index(Request $request)
    {
        // Rendered in full; the list paginates / searches client-side (DataTables).
        $epoch = DataTableRedisCache::readListEpoch(self::INDEX_LIST_EPOCH_KEY);
        $cacheKey = 'venue_master_index:v2:' . md5(json_encode(['epoch' => $epoch]));

        $venues = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'VENUE_MASTER_INDEX_CACHE_ENABLED',
                'seconds' => 'VENUE_MASTER_INDEX_CACHE_SECONDS',
            ],
            'VenueMasterController@index',
            fn () => VenueMaster::query()->orderBy('venue_id', 'desc')->get()
        );
        if (! $venues instanceof Collection) {
            $venues = collect($venues);
        }

        return view('admin.venueMaster.index', compact('venues'));
    }

    public function create() {
        return view('admin.venueMaster.create');
    }

    public function store(Request $request) {
        $request->validate([
            'venue_name' => 'required|string|max:255',
            'venue_short_name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);
        VenueMaster::create([
            'venue_name' => $request->venue_name,
            'description' => $request->description,
            'venue_short_name' => $request->venue_short_name,
            'created_date' => now(),
        ]);
        self::bumpIndexCacheEpoch();
        return redirect()->route('Venue-Master.index')->with('success', 'Venue Added Successfully');
    }

    public function edit($id) {
        $venue = VenueMaster::findOrFail($id);
        return view('admin.venueMaster.edit', compact('venue'));
    }

    public function update(Request $request, $id) {
        $request->validate([
            'venue_name' => 'required|string|max:255',
            'venue_short_name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);
        VenueMaster::where('venue_id', $id)->update([
            'venue_name' => $request->venue_name,
            'description' => $request->description,
            'venue_short_name' => $request->venue_short_name,
            'modified_date' => now(),
        ]);
        self::bumpIndexCacheEpoch();
        return redirect()->route('Venue-Master.index')->with('success', 'Venue Updated Successfully');
    }

    public function destroy($id) {
        VenueMaster::destroy($id);
        self::bumpIndexCacheEpoch();
        return redirect()->route('Venue-Master.index')->with('success', 'Venue Deleted Successfully');
    }
}
