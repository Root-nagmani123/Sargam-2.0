<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mess\Concerns\BuildsMasterDataDatatable;
use App\Support\DataTableRedisCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Mess\ClientType;

class ClientTypeController extends Controller
{
    use BuildsMasterDataDatatable;

    private const LIST_CACHE_EPOCH_KEY = 'mess_client_type_master_list_epoch';
    private const DT_LIST_EPOCH_KEY = 'mess_client_type_master_dt_list_epoch';

    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'ClientTypeController');
        DataTableRedisCache::bumpListEpoch(self::DT_LIST_EPOCH_KEY, 'ClientTypeController');
    }

    public function index(Request $request)
    {
        if ($request->ajax() && $request->has('draw')) {
            return DataTableRedisCache::serveCachedAjax(
                $request,
                'mess_client_type_master_dt:v1:',
                self::DT_LIST_EPOCH_KEY,
                [
                    'enabled' => 'MESS_CLIENT_TYPE_MASTER_DATATABLE_CACHE_ENABLED',
                    'seconds' => 'MESS_CLIENT_TYPE_MASTER_DATATABLE_CACHE_SECONDS',
                ],
                'ClientTypeController@index',
                fn () => $this->buildClientTypeDatatableResponse($request)
            );
        }

        return view('mess.client-types.index');
    }

    private function buildClientTypeDatatableResponse(Request $request): JsonResponse
    {
        $canDelete = function_exists('hasRole') && (hasRole('Super Admin') || hasRole('Mess-Admin'));
        $clientTypeOptions = ClientType::clientTypes();

        return $this->buildMasterDataDatatableResponse(
            $request,
            ClientType::query(),
            ['client_type', 'client_name', 'status'],
            [0 => 'client_type', 1 => 'client_name', 2 => 'status'],
            'asc',
            fn ($rows) => $rows->map(fn (ClientType $clientType) => $this->buildClientTypeDatatableRow($clientType, $canDelete, $clientTypeOptions))->all(),
            true
        );
    }

    /**
     * @return string[]
     */
    private function buildClientTypeDatatableRow(ClientType $clientType, bool $canDelete, array $clientTypeOptions): array
    {
        $typeLabel = $clientTypeOptions[$clientType->client_type] ?? $clientType->client_type;
        $typeCell = '<div class="fw-semibold">' . e($typeLabel) . '</div>';
        $nameCell = '<div class="fw-semibold">' . e($clientType->client_name) . '</div>';
        $statusCell = '<span class="badge bg-' . e($clientType->status_badge_class) . '">'
            . e($clientType->status_label) . '</span>';

        $editBtn = '<button type="button" class="text-primary btn-edit-clienttype bg-transparent border-0"'
            . ' data-id="' . (int) $clientType->id . '"'
            . ' data-client-type="' . e($clientType->client_type) . '"'
            . ' data-client-name="' . e($clientType->client_name) . '"'
            . ' data-status="' . e($clientType->status ?? 'active') . '"'
            . ' title="Edit"><i class="material-icons material-symbol-rounded">edit</i></button>';

        $deleteForm = '';
        if ($canDelete) {
            $deleteUrl = route('admin.mess.client-types.destroy', $clientType->id);
            $deleteForm = '<form method="POST" action="' . e($deleteUrl) . '" class="d-inline"'
                . ' onsubmit="return confirm(\'Are you sure you want to delete this client type?\');">'
                . '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">'
                . '<input type="hidden" name="_method" value="DELETE">'
                . '<button type="submit" class="text-primary bg-transparent border-0 p-0" title="Delete">'
                . '<i class="material-icons material-symbol-rounded">delete</i></button>'
                . '</form>';
        }

        $actions = '<div class="d-flex gap-2 flex-wrap">' . $editBtn . $deleteForm . '</div>';

        return [
            $typeCell,
            $nameCell,
            $statusCell,
            $actions,
        ];
    }

    public function create()
    {
        return view('mess.client-types.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        ClientType::create($data);

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.client-types.index')->with('success', 'Client Type added successfully');
    }

    public function edit($id)
    {
        $clientType = ClientType::findOrFail($id);
        return view('mess.client-types.edit', compact('clientType'));
    }

    public function update(Request $request, $id)
    {
        $clientType = ClientType::findOrFail($id);
        $data = $this->validatedData($request, $clientType);

        $clientType->update($data);

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.client-types.index')->with('success', 'Client Type updated successfully');
    }

    public function destroy($id)
    {
        $clientType = ClientType::findOrFail($id);
        $clientType->delete();

        self::bumpListCacheEpoch();

        return redirect()->route('admin.mess.client-types.index')->with('success', 'Client Type deleted successfully');
    }

    /**
     * Regex: letters, numbers, spaces, hyphen only (no special characters).
     */
    protected const CLIENT_NAME_PATTERN = '/^[\pL\pN\s\-]+$/u';

    /**
     * Build an array of validated attributes for create/update.
     */
    protected function validatedData(Request $request, ?ClientType $clientType = null): array
    {
        $clientNameRules = [
            'required',
            'string',
            'max:255',
            'regex:' . self::CLIENT_NAME_PATTERN,
            Rule::unique('mess_client_types', 'client_name'),
        ];
        if ($clientType !== null) {
            $clientNameRules[3] = Rule::unique('mess_client_types', 'client_name')->ignore($clientType->id);
        }

        $validated = $request->validate([
            'client_type' => ['required', 'string', 'in:employee,ot,course,section,other'],
            'client_name' => $clientNameRules,
            'status'      => ['nullable', 'in:active,inactive'],
        ], [
            'client_name.regex' => 'Client name may only contain letters, numbers, spaces and hyphens. Special characters are not allowed.',
        ]);

        $status = $validated['status'] ?? ClientType::STATUS_ACTIVE;

        return [
            'client_type' => $validated['client_type'],
            'client_name' => $validated['client_name'],
            'status'      => $status,
        ];
    }
}
