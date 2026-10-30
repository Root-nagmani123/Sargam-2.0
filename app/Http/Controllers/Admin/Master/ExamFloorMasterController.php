<?php
namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\DataTables\ExamFloorMasterDataTable;
use App\Models\ExamFloorMaster;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExamFloorMasterController extends Controller
{
    public function index(ExamFloorMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.exam_floor.index');
    }

    public function create()
    {
        return view('admin.master.exam_floor.create_edit');
    }

    public function edit($id)
    {
        $floor = ExamFloorMaster::findOrFail(decrypt($id));

        return view('admin.master.exam_floor.create_edit', compact('floor'));
    }

    public function store(Request $request)
    {
        $pk = $request->id ? decrypt($request->id) : null;

        $request->validate([
            'floor_name' => [
                'required', 'string', 'max:100',
                Rule::unique('exam_floor_master', 'floor_name')->ignore($pk, 'pk'),
            ],
            'display_order' => 'nullable|integer|min:0|max:1000',
            'active_inactive' => 'required|in:1,2',
        ]);

        $data = $request->only([
            'floor_name',
            'display_order',
            'active_inactive',
        ]);
        $data['display_order'] = ($data['display_order'] === null || $data['display_order'] === '')
            ? null
            : $data['display_order'];
        $data['modified_date'] = now();

        if (! $pk) {
            $data['created_date'] = now();
        }

        ExamFloorMaster::updateOrCreate(['pk' => $pk], $data);

        return redirect()->route('master.exam_floor.index')
            ->with('success', 'Floor saved successfully');
    }

    public function destroy($id)
    {
        try {
            $pk = decrypt($id);

            // A floor already attached to a venue must not disappear from under it.
            $inUse = \App\Models\VenueMaster::where('floor_master_pk', $pk)->exists();
            if ($inUse) {
                return redirect()->back()
                    ->with('error', 'This floor is used by one or more venues and cannot be deleted.');
            }

            ExamFloorMaster::where('pk', $pk)->delete();

            return redirect()->route('master.exam_floor.index')
                ->with('success', 'Deleted successfully.');
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            return redirect()->back()->with('error', 'Something went wrong.');
        }
    }
}
