<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Support\DataTableRedisCache;
use App\Support\DataTableSearchHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Mess\SubStore;

class SubStoreController extends Controller
{
    private const LIST_CACHE_EPOCH_KEY = 'mess_sub_store_master_list_epoch';
    private const DT_LIST_EPOCH_KEY = 'mess_sub_store_master_dt_list_epoch';

    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'SubStoreController');
        DataTableRedisCache::bumpListEpoch(self::DT_LIST_EPOCH_KEY, 'SubStoreController');
    }

    public function index(Request $request)
    {
        if ($request->ajax() && $request->has('draw')) {
            return DataTableRedisCache::serveCachedAjax(
                $request,
                'mess_sub_store_master_dt:v1:',
                self::DT_LIST_EPOCH_KEY,
                [
                    'enabled' => 'MESS_SUB_STORE_MASTER_DATATABLE_CACHE_ENABLED',
                    'seconds' => 'MESS_SUB_STORE_MASTER_DATATABLE_CACHE_SECONDS',
                ],
                'SubStoreController@index',
                fn () => $this->buildSubStoreDatatableResponse($request)
            );
        }

        return view('mess.sub-stores.index');
    }

    private function buildSubStoreDatatableResponse(Request $request): JsonResponse
    {
        $query = SubStore::query();

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
                        $inner->where('sub_store_name', 'like', $like)
                            ->orWhere('status', 'like', $like);
                    });
                }
            });
        }

        $recordsFiltered = (clone $query)->count();

        $paged = clone $query;
        $orderCol = DataTableSearchHelper::orderColumnIndex($request, 0);
        $orderDir = DataTableSearchHelper::orderDirection($request, 'asc');

        switch ($orderCol) {
            case 0:
                $paged->orderBy('sub_store_name', $orderDir);
                break;
            case 1:
                $paged->orderBy('status', $orderDir);
                break;
            default:
                $paged->orderByDesc('id');
        }
        $paged->orderByDesc('id');

        if ($length !== -1) {
            $paged->skip($start)->take($length);
        }

        $rows = $paged->get();
        $canDelete = function_exists('hasRole') && (hasRole('Super Admin') || hasRole('Mess-Admin'));

        $data = $rows->map(fn (SubStore $subStore) => $this->buildSubStoreDatatableRow($subStore, $canDelete))->all();

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
    private function buildSubStoreDatatableRow(SubStore $subStore, bool $canDelete): array
    {
        $nameCell = '<div class="fw-semibold">' . e($subStore->sub_store_name) . '</div>';
        $statusCell = '<span class="badge bg-' . e($subStore->status_badge_class) . '">'
            . e($subStore->status_label) . '</span>';

        $editBtn = '<button type="button" class="text-primary btn-edit-substore bg-transparent border-0"'
            . ' data-id="' . (int) $subStore->id . '"'
            . ' data-sub-store-name="' . e($subStore->sub_store_name) . '"'
            . ' data-status="' . e($subStore->status ?? 'active') . '"'
            . ' title="Edit"><i class="material-icons material-symbol-rounded">edit</i></button>';

        $deleteForm = '';
        if ($canDelete) {
            $deleteUrl = route('admin.mess.sub-stores.destroy', $subStore->id);
            $deleteForm = '<form method="POST" action="' . e($deleteUrl) . '" class="d-inline"'
                . ' onsubmit="return confirm(\'Are you sure you want to delete this sub store?\');">'
                . '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">'
                . '<input type="hidden" name="_method" value="DELETE">'
                . '<button type="submit" class="text-primary bg-transparent border-0 p-0" title="Delete">'
                . '<i class="material-icons material-symbol-rounded">delete</i></button>'
                . '</form>';
        }

        $actions = '<div class="d-flex gap-2 flex-wrap">' . $editBtn . $deleteForm . '</div>';

        return [
            $nameCell,
            $statusCell,
            $actions,
        ];
    }

    public function create()
    {
        return view('mess.sub-stores.create');
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
        $subStore = SubStore::findOrFail($id);
        return view('mess.sub-stores.edit', compact('subStore'));
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
