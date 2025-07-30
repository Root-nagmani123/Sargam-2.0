<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MemoConclusionMaster;

class MemoConclusionMasterController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:master.memo.conclusion.master.index')->only(['index']);
        $this->middleware('permission:master.memo.conclusion.master.create')->only(['create', 'store']);
        $this->middleware('permission:master.memo.conclusion.master.edit')->only(['edit', 'store']);
        $this->middleware('permission:master.memo.conclusion.master.delete')->only(['destroy']);
    }
    public function index()
    {
        $conclusions = MemoConclusionMaster::all();
        return view('admin.master.memo_conclusion_master.index', compact('conclusions'));
    }

    public function create()
    {
        return view('admin.master.memo_conclusion_master.create_edit');
    }

    public function edit($id)
    {
        $conclusion = MemoConclusionMaster::findOrFail(decrypt($id));
        return view('admin.master.memo_conclusion_master.create_edit', compact('conclusion'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'discussion_name' => 'required|string|max:100',
            'pt_discusion' => 'nullable|string',
            'active_inactive' => 'required|in:1,2',
        ]);

        try {
            if ($request->id) {
                $conclusion = MemoConclusionMaster::findOrFail(decrypt($request->id));
            } else {
                $conclusion = new MemoConclusionMaster();
            }

            $conclusion->discussion_name = $request->discussion_name;
            $conclusion->pt_discusion = $request->pt_discusion;
            $conclusion->active_inactive = $request->active_inactive;

            $conclusion->save();

            return redirect()->route('master.memo.conclusion.master.index')->with('success', 'Memo Conclusion saved successfully.');
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong.');
        }
    }

    public function destroy($id)
    {
        try {
            $conclusion = MemoConclusionMaster::findOrFail(decrypt($id));
            $conclusion->delete();

            return redirect()->route('master.memo.conclusion.master.index')->with('success', 'Deleted successfully.');
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong.');
        }
    }
}
