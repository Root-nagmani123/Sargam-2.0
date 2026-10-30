<?php
namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\DataTables\ExamBuildingMasterDataTable;
use App\Models\ExamBuildingMaster;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExamBuildingMasterController extends Controller
{
    public function index(ExamBuildingMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.exam_building.index');
    }

    public function create()
    {
        return view('admin.master.exam_building.create_edit');
    }

    public function edit($id)
    {
        $building = ExamBuildingMaster::findOrFail(decrypt($id));

        return view('admin.master.exam_building.create_edit', compact('building'));
    }

    public function store(Request $request)
    {
        $pk = $request->id ? decrypt($request->id) : null;

        $request->validate([
            'building_name' => [
                'required', 'string', 'max:150',
                Rule::unique('exam_building_master', 'building_name')->ignore($pk, 'pk'),
            ],
            'building_short_name' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
            'active_inactive' => 'required|in:1,2',
        ]);

        $data = $request->only([
            'building_name',
            'building_short_name',
            'description',
            'active_inactive',
        ]);
        $data['modified_date'] = now();

        if (! $pk) {
            $data['created_date'] = now();
        }

        ExamBuildingMaster::updateOrCreate(['pk' => $pk], $data);

        return redirect()->route('master.exam_building.index')
            ->with('success', 'Building saved successfully');
    }

    public function destroy($id)
    {
        try {
            $pk = decrypt($id);

            // A building already attached to a venue must not disappear from under it.
            $inUse = \App\Models\VenueMaster::where('building_master_pk', $pk)->exists();
            if ($inUse) {
                return redirect()->back()
                    ->with('error', 'This building is used by one or more venues and cannot be deleted.');
            }

            ExamBuildingMaster::where('pk', $pk)->delete();

            return redirect()->route('master.exam_building.index')
                ->with('success', 'Deleted successfully.');
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            return redirect()->back()->with('error', 'Something went wrong.');
        }
    }
}
