<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\DataTables\Master\ComponentMasterDataTable;
use App\Models\ComponentMaster;

class ComponentMasterController extends Controller
{
    public function index(ComponentMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.component.index');
    }

    public function store(Request $request)
    {
        $id = $request->pk ? decrypt($request->pk) : null;

        $request->validate([
            'component_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('component_master', 'component_name')->ignore($id, 'pk'),
            ],
            'active_inactive' => 'required|in:0,1',
        ]);

        $component = $id ? ComponentMaster::findOrFail($id) : new ComponentMaster();
        $component->component_name = $request->component_name;
        $component->active_inactive = $request->active_inactive;
        $component->save();

        $message = $id ? 'Component updated successfully.' : 'Component created successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.component.index')->with('success', $message);
    }

    public function destroy($id)
    {
        $component = ComponentMaster::findOrFail(decrypt($id));
        $component->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Component deleted successfully.']);
        }

        return redirect()->route('master.component.index')->with('success', 'Component deleted successfully.');
    }
}
