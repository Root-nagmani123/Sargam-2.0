<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\ClientType;

class ClientTypeController extends Controller
{
    public function index()
    {
        $clientTypes = ClientType::orderByDesc('id')->get();
        return view('mess.client-types.index', compact('clientTypes'));
    }

    public function create()
    {
        return view('mess.client-types.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        ClientType::create($data);

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
        return redirect()->route('admin.mess.client-types.index')->with('success', 'Client Type updated successfully');
    }

    public function destroy($id)
    {
        $clientType = ClientType::findOrFail($id);
        $clientType->delete();
        return redirect()->route('admin.mess.client-types.index')->with('success', 'Client Type deleted successfully');
    }

    /**
     * Build an array of validated attributes for create/update.
     */
    protected function validatedData(Request $request, ?ClientType $clientType = null): array
    {
        $validated = $request->validate([
            'client_type' => ['required', 'string', 'in:employee,ot,course,other,section'],
            'client_name' => ['required', 'string', 'max:255'],
            'status'      => ['nullable', 'in:active,inactive'],
        ]);

        $status = $validated['status'] ?? ClientType::STATUS_ACTIVE;

        return [
            'client_type' => $validated['client_type'],
            'client_name' => $validated['client_name'],
            'status'      => $status,
        ];
    }
}
