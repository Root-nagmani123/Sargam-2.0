<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mess\NumberConfig;

class NumberConfigController extends Controller
{
    public function index()
    {
        $configs = NumberConfig::all();
        return view('admin.mess.number-configs.index', compact('configs'));
    }

    public function create()
    {
        return view('admin.mess.number-configs.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'config_type' => 'required|string|unique:mess_number_configs',
            'prefix' => 'required|string|max:10',
            'current_number' => 'required|integer|min:0',
            'padding' => 'required|integer|min:1'
        ]);

        NumberConfig::create($request->all());
        return redirect()->route('admin.mess.number-configs.index')->with('success', 'Number configuration created successfully.');
    }

    public function show($id)
    {
        $config = NumberConfig::findOrFail($id);
        return view('admin.mess.number-configs.show', compact('config'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $config = NumberConfig::findOrFail($id);
        return view('admin.mess.number-configs.edit', compact('config'));
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
        $config = NumberConfig::findOrFail($id);
        
        $request->validate([
            'prefix' => 'required|string|max:10',
            'current_number' => 'required|integer|min:0',
            'padding' => 'required|integer|min:1'
        ]);

        $config->update($request->all());
        return redirect()->route('admin.mess.number-configs.index')->with('success', 'Number configuration updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $config = NumberConfig::findOrFail($id);
        $config->delete();
        return redirect()->route('admin.mess.number-configs.index')->with('success', 'Number configuration deleted successfully.');
    }
}
