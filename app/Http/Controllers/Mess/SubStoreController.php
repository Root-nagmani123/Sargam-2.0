<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Support\DataTableRedisCache;
use Illuminate\Http\Request;
use App\Models\Mess\SubStore;

class SubStoreController extends Controller
{
    private const LIST_CACHE_EPOCH_KEY = 'mess_sub_store_master_list_epoch';

    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'SubStoreController');
    }

    public function index()
    {
        $epoch = DataTableRedisCache::readListEpoch(self::LIST_CACHE_EPOCH_KEY);
        $cacheKey = 'mess_sub_store_master_list:v1:' . md5(json_encode(['epoch' => $epoch]));

        $subStores = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'MESS_SUB_STORE_MASTER_LIST_CACHE_ENABLED',
                'seconds' => 'MESS_SUB_STORE_MASTER_LIST_CACHE_SECONDS',
            ],
            'SubStoreController@index',
            fn () => SubStore::orderByDesc('id')->get()
        );

        return view('mess.sub-stores.index', compact('subStores'));
    }

    public function create()
    {
        return redirect()->route('admin.mess.sub-stores.index', ['open' => 'create']);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        SubStore::create($data);

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.sub-stores.index')->with('success', 'Sub Store added successfully');
    }

    public function edit($id)
    {
        return redirect()->route('admin.mess.sub-stores.index', ['open' => 'edit', 'id' => $id]);
    }

    public function update(Request $request, $id)
    {
        $subStore = SubStore::findOrFail($id);
        $data = $this->validatedData($request, $subStore);

        $subStore->update($data);

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.sub-stores.index')->with('success', 'Sub Store updated successfully');
    }

    public function destroy($id)
    {
        $subStore = SubStore::findOrFail($id);
        $subStore->delete();

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.sub-stores.index')->with('success', 'Sub Store deleted successfully');
    }

    /**
     * Build an array of validated attributes for create/update.
     */
    protected function validatedData(Request $request, ?SubStore $subStore = null): array
    {
        $validated = $request->validate([
            'sub_store_name' => ['required', 'string', 'max:255', 'regex:/^[\pL\pN\s\-]+$/u'],
            'status'         => ['nullable', 'in:active,inactive'],
        ], [
            'sub_store_name.regex' => 'Sub Store name may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.',
        ]);

        $status = $validated['status'] ?? SubStore::STATUS_ACTIVE;

        return [
            'sub_store_name' => $validated['sub_store_name'],
            'status'         => $status,
        ];
    }
}
