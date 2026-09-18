<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\DataTables\Master\ExaminationTypeMasterDataTable;
use App\Models\ExaminationTypeMaster;

class ExaminationTypeMasterController extends Controller
{
    public function index(ExaminationTypeMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.examination_type.index');
    }

    public function store(Request $request)
    {
        $id = $request->pk ? decrypt($request->pk) : null;

        $request->validate([
            'exam_type_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('examination_type_master', 'exam_type_name')->ignore($id, 'pk'),
            ],
            'active_inactive' => 'required|in:0,1',
        ]);

        $examType = $id ? ExaminationTypeMaster::findOrFail($id) : new ExaminationTypeMaster();
        $examType->exam_type_name = $request->exam_type_name;
        $examType->active_inactive = $request->active_inactive;
        $examType->save();

        $message = $id ? 'Examination type updated successfully.' : 'Examination type created successfully.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        return redirect()->route('master.examination.type.index')->with('success', $message);
    }

    public function destroy($id)
    {
        $examType = ExaminationTypeMaster::findOrFail(decrypt($id));
        $examType->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Examination type deleted successfully.']);
        }

        return redirect()->route('master.examination.type.index')->with('success', 'Examination type deleted successfully.');
    }
}
