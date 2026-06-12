<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Support\DataTableRedisCache;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Mess\ClientType;

class ClientTypeController extends Controller
{
    private const LIST_CACHE_EPOCH_KEY = 'mess_client_type_master_list_epoch';

    public static function bumpListCacheEpoch(): void
    {
        DataTableRedisCache::bumpListEpoch(self::LIST_CACHE_EPOCH_KEY, 'ClientTypeController');
    }

    public function index()
    {
        $epoch = DataTableRedisCache::readListEpoch(self::LIST_CACHE_EPOCH_KEY);
        $cacheKey = 'mess_client_type_master_list:v1:' . md5(json_encode(['epoch' => $epoch]));

        $clientTypes = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'MESS_CLIENT_TYPE_MASTER_LIST_CACHE_ENABLED',
                'seconds' => 'MESS_CLIENT_TYPE_MASTER_LIST_CACHE_SECONDS',
            ],
            'ClientTypeController@index',
            fn () => ClientType::orderByDesc('id')->get()
        );

        return view('mess.client-types.index', compact('clientTypes'));
    }

    public function create()
    {
        return redirect()->route('admin.mess.client-types.index', ['open' => 'create']);
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
        return redirect()->route('admin.mess.client-types.index', ['open' => 'edit', 'id' => $id]);
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
