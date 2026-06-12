<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Support\DataTableRedisCache;
use Illuminate\Http\Request;
use App\Models\Mess\Store;

class StoreController extends Controller
{
    private const LIST_CACHE_EPOCH_KEY = 'mess_store_master_list_epoch';

    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'StoreController');
    }

    public function index()
    {
        $epoch = DataTableRedisCache::readListEpoch(self::LIST_CACHE_EPOCH_KEY);
        $cacheKey = 'mess_store_master_list:v1:' . md5(json_encode(['epoch' => $epoch]));

        $stores = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'MESS_STORE_MASTER_LIST_CACHE_ENABLED',
                'seconds' => 'MESS_STORE_MASTER_LIST_CACHE_SECONDS',
            ],
            'StoreController@index',
            fn () => Store::orderByDesc('id')->get()
        );

        return view('mess.stores.index', compact('stores'));
    }

    public function create()
    {
        return redirect()->route('admin.mess.stores.index', ['open' => 'create']);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        Store::create(array_merge($data, [
            'store_code' => $this->generateStoreCode(),
        ]));

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.stores.index')->with('success', 'Store added successfully');
    }

    public function edit($id)
    {
        return redirect()->route('admin.mess.stores.index', ['open' => 'edit', 'id' => $id]);
    }

    public function update(Request $request, $id)
    {
        $store = Store::findOrFail($id);
        $data  = $this->validatedData($request, $store);

        $store->update($data);

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.stores.index')->with('success', 'Store updated successfully');
    }

    public function destroy($id)
    {
        $store = Store::findOrFail($id);
        $store->delete();

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.stores.index')->with('success', 'Store deleted successfully');
    }

    /**
     * Regex: letters, numbers, spaces, hyphen only (no special characters).
     */
    protected const STORE_NAME_PATTERN = '/^[\pL\pN\s\-]+$/u';

    /**
     * Regex: letters, numbers, spaces, hyphen, comma, period (no special characters). Empty allowed for nullable.
     */
    protected const LOCATION_PATTERN = '/^[\pL\pN\s\-\.\,]*$/u';

    protected function validatedData(Request $request, ?Store $store = null): array
    {
        $validated = $request->validate([
            'store_name' => [
                'required',
                'string',
                'max:255',
                'regex:' . self::STORE_NAME_PATTERN,
            ],
            'store_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(Store::storeTypes()))],
            'location'   => [
                'nullable',
                'string',
                'max:255',
                'regex:' . self::LOCATION_PATTERN,
            ],
            'status'     => ['nullable', 'in:active,inactive'],
        ], [
            'store_name.regex' => 'Store name may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.',
            'location.regex'   => 'Location may only contain letters, numbers, spaces, hyphens, commas and periods. Special characters are not allowed.',
        ]);

        $status = $validated['status'] ?? Store::STATUS_ACTIVE;
        $storeType = $validated['store_type'] ?? Store::TYPE_MESS;

        return [
            'store_name' => $validated['store_name'],
            'store_type' => $storeType,
            'location'   => $validated['location'] ?? null,
            'status'     => $status,
        ];
    }

    /**
     * Generate a unique store code.
     */
    protected function generateStoreCode(): string
    {
        $next = ((int) Store::max('id')) + 1;
        $code = 'STR' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);

        while (Store::where('store_code', $code)->exists()) {
            $next++;
            $code = 'STR' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
        }

        return $code;
    }
}
