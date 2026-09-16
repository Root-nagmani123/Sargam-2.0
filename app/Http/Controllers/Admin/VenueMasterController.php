<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{VenueMaster, BuildingMaster, FloorMaster};
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

    /**
     * Exam capacity fields (COE BRD "Venue Master"). All optional: the 116 existing
     * venues predate them and Timetable/Attendance/Calendar never set them.
     */
    private function venueValidationRules(): array
    {
        return [
            "venue_name" => "required|string|max:255",
            "venue_short_name" => "required|string|max:100",
            "description" => "nullable|string",
            "building_master_pk" => "nullable|exists:building_master,pk",
            "floor_master_pk" => "nullable|exists:floor_master,pk",
            "room_number" => "nullable|string|max:50",
            "capacity" => "nullable|integer|min:0|max:100000",
            "laptop_capacity" => "nullable|integer|min:0|max:100000|lte:capacity",
            "seating_capacity" => "nullable|integer|min:0|max:100000|lte:capacity",
        ];
    }

    private function venueValidationMessages(): array
    {
        return [
            "laptop_capacity.lte" => "Laptop capacity cannot be more than the total capacity.",
            "seating_capacity.lte" => "Seating capacity cannot be more than the total capacity.",
        ];
    }

    /**
     * Blank numeric/select inputs arrive as "" and must be stored as NULL, not 0.
     */
    private function venuePayload(Request $request): array
    {
        $nullable = [
            "building_master_pk",
            "floor_master_pk",
            "room_number",
            "capacity",
            "laptop_capacity",
            "seating_capacity",
        ];

        $payload = [
            "venue_name" => $request->venue_name,
            "description" => $request->description,
            "venue_short_name" => $request->venue_short_name,
        ];

        foreach ($nullable as $field) {
            $value = $request->input($field);
            $payload[$field] = ($value === null || $value === "") ? null : $value;
        }

        return $payload;
    }

    private function venueFormLookups(): array
    {
        return [
            "buildings" => BuildingMaster::where("active_inactive", 1)
                ->orderBy("building_name")->get(),
            "floors" => FloorMaster::where("active_inactive", 1)
                ->orderBy("floor_name")->get(),
        ];
    }

    public function create() {
        return view('admin.venueMaster.create', $this->venueFormLookups());
    }

    public function store(Request $request) {
        $request->validate($this->venueValidationRules(), $this->venueValidationMessages());

        VenueMaster::create($this->venuePayload($request) + [
            'created_date' => now(),
        ]);
        self::bumpIndexCacheEpoch();
        return redirect()->route('Venue-Master.index')->with('success', 'Venue Added Successfully');
    }

    public function edit($id) {
        $venue = VenueMaster::findOrFail($id);
        return view('admin.venueMaster.edit', compact('venue') + $this->venueFormLookups());
    }

    public function update(Request $request, $id) {
        $request->validate($this->venueValidationRules(), $this->venueValidationMessages());

        VenueMaster::where('venue_id', $id)->update($this->venuePayload($request) + [
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
