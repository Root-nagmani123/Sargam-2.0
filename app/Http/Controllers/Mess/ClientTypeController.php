<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\ClientType;

class ClientTypeController extends Controller
{
    public function index()
    {
        $clientTypes = ClientType::paginate(20);
        return view('admin.mess.client-types.index', compact('clientTypes'));
    }

    public function create()
    {
        return view('admin.mess.client-types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'type_name' => 'required|string|max:255',
            'type_code' => 'required|string|unique:mess_client_types',
            'default_credit_limit' => 'required|numeric|min:0'
        ]);

        ClientType::create($request->all());
        return redirect()->route('admin.mess.client-types.index')->with('success', 'Client type created successfully.');
    }

    public function show($id)
    {
        $clientType = ClientType::findOrFail($id);
        return view('admin.mess.client-types.show', compact('clientType'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $clientType = ClientType::findOrFail($id);
        return view('admin.mess.client-types.edit', compact('clientType'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $clientType = ClientType::findOrFail($id);
        
        $request->validate([
            'type_name' => 'required|string|max:255',
            'type_code' => 'required|string|unique:mess_client_types,type_code,' . $id,
            'default_credit_limit' => 'required|numeric|min:0'
        ]);

        $clientType->update($request->all());
        return redirect()->route('admin.mess.client-types.index')->with('success', 'Client type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $clientType = ClientType::findOrFail($id);
        $clientType->delete();
        return redirect()->route('admin.mess.client-types.index')->with('success', 'Client type deleted successfully.');
    }
}
