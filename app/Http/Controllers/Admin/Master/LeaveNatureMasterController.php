<?php

namespace App\Http\Controllers\Admin\Master;

use App\DataTables\LeaveNatureMasterDataTable;
use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use App\Models\LeaveNatureMaster;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveNatureMasterController extends Controller
{
    public function index(LeaveNatureMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.leave_nature.index');
    }

    public function create()
    {
        return view('admin.master.leave_nature.create_edit');
    }

    public function edit($id)
    {
        $leaveNature = LeaveNatureMaster::findOrFail(decrypt($id));

        return view('admin.master.leave_nature.create_edit', compact('leaveNature'));
    }

    public function store(Request $request)
    {
        $pk = $request->id ? decrypt($request->id) : null;

        $request->validate([
            'leave_type' => ['required', Rule::in([
                LeaveApplication::TYPE_PT_EXEMPTION,
                LeaveApplication::TYPE_STATIONED_LEAVE,
            ])],
            'nature_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('leave_nature_master', 'nature_name')
                    ->where(fn ($query) => $query->where('leave_type', $request->leave_type))
                    ->ignore($pk, 'pk'),
            ],
            'active_inactive' => 'required|in:1,2',
        ]);

        $displayOrder = $pk
            ? LeaveNatureMaster::find($pk)?->display_order ?? 0
            : (int) LeaveNatureMaster::where('leave_type', $request->leave_type)->max('display_order') + 1;

        LeaveNatureMaster::updateOrCreate(
            ['pk' => $pk],
            [
                'leave_type' => $request->leave_type,
                'nature_name' => $request->nature_name,
                'display_order' => $displayOrder,
                'active_inactive' => $request->active_inactive,
                'modified_date' => now(),
                'created_date' => $pk ? LeaveNatureMaster::find($pk)?->created_date ?? now() : now(),
            ]
        );

        return redirect()->route('master.leave-nature.index')
            ->with('success', 'Leave nature saved successfully.');
    }

    public function destroy($id)
    {
        try {
            $leaveNature = LeaveNatureMaster::where('pk', decrypt($id))->firstOrFail();

            if ($leaveNature->active_inactive == 1) {
                return redirect()->back()->with('error', 'Active records cannot be deleted. Please deactivate it first.');
            }

            $leaveNature->delete();

            return redirect()->route('master.leave-nature.index')
                ->with('success', 'Deleted successfully.');
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            return redirect()->back()->with('error', 'Something went wrong.');
        }
    }
}
