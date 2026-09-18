<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\DataTables\Master\TermMasterDataTable;
use App\Models\TermMaster;

class TermMasterController extends Controller
{
    public function index(TermMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.term.index');
    }

    public function store(Request $request)
    {
        $id = $request->pk ? decrypt($request->pk) : null;

        $request->validate([
            'term_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('term_master', 'term_name')->ignore($id, 'pk'),
            ],
            'active_inactive' => 'required|in:0,1',
        ]);

        $term = $id ? TermMaster::findOrFail($id) : new TermMaster();
        $term->term_name = $request->term_name;
        $term->active_inactive = $request->active_inactive;
        $term->save();

        $message = $id ? 'Term updated successfully.' : 'Term created successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.term.index')->with('success', $message);
    }

    public function destroy($id)
    {
        $term = TermMaster::findOrFail(decrypt($id));
        $term->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Term deleted successfully.']);
        }

        return redirect()->route('master.term.index')->with('success', 'Term deleted successfully.');
    }
}
