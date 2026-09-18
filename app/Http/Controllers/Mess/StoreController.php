<?php
namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Support\DataTableRedisCache;
use App\Support\DataTableSearchHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Mess\Store;

class StoreController extends Controller
{
    private const LIST_CACHE_EPOCH_KEY = 'mess_store_master_list_epoch';
    private const DT_LIST_EPOCH_KEY = 'mess_store_master_dt_list_epoch';

    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'StoreController');
        DataTableRedisCache::bumpListEpoch(self::DT_LIST_EPOCH_KEY, 'StoreController');
    }

    public function index(Request $request)
    {
        if ($request->ajax() && $request->has('draw')) {
            return DataTableRedisCache::serveCachedAjax(
                $request,
                'mess_store_master_dt:v1:',
                self::DT_LIST_EPOCH_KEY,
                [
                    'enabled' => 'MESS_STORE_MASTER_DATATABLE_CACHE_ENABLED',
                    'seconds' => 'MESS_STORE_MASTER_DATATABLE_CACHE_SECONDS',
                ],
                'StoreController@index',
                fn () => $this->buildStoreDatatableResponse($request)
            );
        }

        return view('mess.stores.index');
    }

    private function storeFilteredQuery(Request $request): Builder
    {
        return Store::query();
    }

    private function buildStoreDatatableResponse(Request $request): JsonResponse
    {
        $query = $this->storeFilteredQuery($request);

        $draw = (int) $request->input('draw', 0);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        if ($length < 1 || $length > 100) {
            $length = 10;
        }

        $searchTokens = DataTableSearchHelper::tokens((string) $request->input('search.value', ''));

        $recordsTotal = (clone $query)->count();

        if ($searchTokens !== []) {
            $query->where(function ($q) use ($searchTokens) {
                foreach ($searchTokens as $token) {
                    $like = DataTableSearchHelper::likePattern($token);
                    $q->where(function ($inner) use ($like) {
                        $inner->where('store_name', 'like', $like)
                            ->orWhere('store_code', 'like', $like)
                            ->orWhere('store_type', 'like', $like)
                            ->orWhere('location', 'like', $like)
                            ->orWhere('status', 'like', $like);
                    });
                }
            });
        }

        $recordsFiltered = (clone $query)->count();

        $paged = clone $query;
        $orderCol = DataTableSearchHelper::orderColumnIndex($request, 0);
        $orderDir = DataTableSearchHelper::orderDirection($request, 'desc');

        switch ($orderCol) {
            case 0:
                $paged->orderBy('id', $orderDir);
                break;
            case 1:
                $paged->orderBy('store_name', $orderDir);
                break;
            case 2:
                $paged->orderBy('store_type', $orderDir);
                break;
            case 3:
                $paged->orderBy('location', $orderDir);
                break;
            case 4:
                $paged->orderBy('status', $orderDir);
                break;
            default:
                $paged->orderByDesc('id');
        }

        if ($length !== -1) {
            $paged->skip($start)->take($length);
        }

        $rows = $paged->get();
        $canDelete = function_exists('hasRole') && (hasRole('Super Admin') || hasRole('Mess-Admin'));
        $storeTypes = Store::storeTypes();

        $data = $rows->map(fn (Store $store) => $this->buildStoreDatatableRow($store, $canDelete, $storeTypes))->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * @return string[]
     */
    private function buildStoreDatatableRow(Store $store, bool $canDelete, array $storeTypes): array
    {
        $nameCell = '<div class="fw-semibold">' . e($store->store_name) . '</div>'
            . '<div class="text-muted small">Code: ' . e($store->store_code) . '</div>';
        $typeLabel = $storeTypes[$store->store_type ?? 'mess'] ?? ($store->store_type ?? '-');
        $typeCell = '<span class="text-capitalize">' . e($typeLabel) . '</span>';
        $locationCell = e($store->location ?? '-');
        $statusCell = '<span class="badge bg-' . e($store->status_badge_class) . '">'
            . e($store->status_label) . '</span>';

        $editBtn = '<button type="button" class="btn btn-sm btn-warning btn-edit-store bg-transparent border-0 p-0 text-primary"'
            . ' data-id="' . (int) $store->id . '"'
            . ' data-store-name="' . e($store->store_name) . '"'
            . ' data-store-type="' . e(trim((string) ($store->store_type ?? '')) ?: 'mess') . '"'
            . ' data-location="' . e($store->location ?? '') . '"'
            . ' data-status="' . e($store->status ?? 'active') . '"'
            . ' title="Edit"><i class="material-symbols-rounded">edit</i></button>';

        $deleteForm = '';
        if ($canDelete) {
            $deleteUrl = route('admin.mess.stores.destroy', $store->id);
            $deleteForm = '<form method="POST" action="' . e($deleteUrl) . '" class="d-inline"'
                . ' onsubmit="return confirm(\'Are you sure you want to delete this store?\');">'
                . '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">'
                . '<input type="hidden" name="_method" value="DELETE">'
                . '<button type="submit" class="btn btn-sm btn-danger bg-transparent border-0 p-0 text-primary" title="Delete">'
                . '<i class="material-symbols-rounded">delete</i></button>'
                . '</form>';
        }

        $actions = '<div class="d-flex gap-2 flex-wrap">' . $editBtn . $deleteForm . '</div>';

        return [
            (string) $store->id,
            $nameCell,
            $typeCell,
            $locationCell,
            $statusCell,
            $actions,
        ];
    }

    public function create()
    {
        return view('mess.stores.create');
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
        $store = Store::findOrFail($id);
        return view('mess.stores.edit', compact('store'));
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
