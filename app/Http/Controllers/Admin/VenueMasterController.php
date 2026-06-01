<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{VenueMaster};
use App\Support\DataTableRedisCache;

class VenueMasterController extends Controller
{
    private const INDEX_LIST_EPOCH_KEY = 'venue_master_index_list_epoch';

    public static function bumpIndexCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::INDEX_LIST_EPOCH_KEY, 'VenueMasterController');
    }

    public function index(Request $request)
    {
        $perPage = 10;

        $epoch = DataTableRedisCache::readListEpoch(self::INDEX_LIST_EPOCH_KEY);
        $cacheKey = 'venue_master_index:v1:' . md5(json_encode([
            'epoch' => $epoch,
            'page' => (int) $request->input('page', 1),
            'per_page' => $perPage,
        ]));

        $cached = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'VENUE_MASTER_INDEX_CACHE_ENABLED',
                'seconds' => 'VENUE_MASTER_INDEX_CACHE_SECONDS',
            ],
            'VenueMasterController@index',
            fn () => $this->buildVenueMasterIndexPaginator($request, $perPage)
        );

        $venues = new \Illuminate\Pagination\LengthAwarePaginator(
            $cached['items'],
            $cached['total'],
            $cached['perPage'],
            $cached['currentPage'],
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.venueMaster.index', compact('venues'));
    }

    /**
     * @return array{items: array<int, mixed>, total: int, perPage: int, currentPage: int}
     */
    private function buildVenueMasterIndexPaginator(Request $request, int $perPage): array
    {
        $paginator = VenueMaster::query()
            ->orderBy('venue_id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'items' => $paginator->items(),
            'total' => $paginator->total(),
            'perPage' => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
        ];
    }

    public function create(Request $request)
    {
        if ($request->ajax() || $request->expectsJson()) {
            return view('admin.venueMaster._form');
        }

        return redirect()->route('Venue-Master.index', ['open_venue_modal' => 'add']);
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

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Venue Added Successfully',
            ]);
        }

        return redirect()->route('Venue-Master.index')->with('success', 'Venue Added Successfully');
    }

    public function edit(Request $request, $id) {
        $venue = VenueMaster::findOrFail($id);

        if ($request->ajax() || $request->expectsJson()) {
            return view('admin.venueMaster._form', compact('venue'));
        }

        return redirect()->route('Venue-Master.index', [
            'open_venue_modal' => 'edit',
            'venue_id' => $id,
        ]);
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

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Venue Updated Successfully',
            ]);
        }

        return redirect()->route('Venue-Master.index')->with('success', 'Venue Updated Successfully');
    }

    public function destroy($id) {
        VenueMaster::destroy($id);
        self::bumpIndexCacheEpoch();
        return redirect()->route('Venue-Master.index')->with('success', 'Venue Deleted Successfully');
    }
}
