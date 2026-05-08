<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\DataTables\AppellationMasterDataTable;
use App\Models\AppellationMaster;
use Illuminate\Http\Request;

class AppellationMasterController extends Controller
{
    public function index(AppellationMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.appellation.index');
    }

    public function create()
    {
        return view('admin.master.appellation.create_edit');
    }

    public function edit($id)
    {
        $appellation = AppellationMaster::findOrFail(decrypt($id));
        return view('admin.master.appellation.create_edit', compact('appellation'));
    }

    public function store(Request $request)
    {
        $pk = $request->id ? decrypt($request->id) : null;

        $request->validate([
            'appettation_name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z\s\.]+$/',
                \Illuminate\Validation\Rule::unique('appellation_master', 'appettation_name')->ignore($pk, 'pk'),
            ],
            'active_inactive'  => 'required|in:1,2',
        ], [
            'appettation_name.required' => 'Appellation name is required.',
            'appettation_name.regex'    => 'Appellation name must contain only letters and spaces.',
            'appettation_name.max'      => 'Appellation name must not exceed 50 characters.',
            'appettation_name.unique'   => 'This appellation name already exists.',
            'active_inactive.required'  => 'Status is required.',
            'active_inactive.in'        => 'Invalid status selected.',
        ]);

        AppellationMaster::updateOrCreate(
            ['pk' => $pk],
            [
                'appettation_name' => $request->appettation_name,
                'active_inactive'  => $request->active_inactive,
            ]
        );

        return redirect()->route('master.appellation.index')
            ->with('success', 'Appellation saved successfully.');
    }

    public function destroy($id)
    {
        try {
            $appellation = AppellationMaster::where('pk', decrypt($id))->firstOrFail();

            if ($appellation->active_inactive == 1) {
                return redirect()->back()->with('error', 'Active records cannot be deleted. Please deactivate it first.');
            }

            $appellation->delete();

            return redirect()->route('master.appellation.index')
                ->with('success', 'Deleted successfully.');
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong.');
        }
    }
}
