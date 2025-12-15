<?php
namespace App\Http\Controllers\Admin\Master;
use App\Http\Controllers\Controller;


use App\DataTables\DisciplineMasterDataTable;
use App\Models\DisciplineMaster;
use App\Models\CourseMaster;
use Illuminate\Http\Request;

class DisciplineMasterController extends Controller
{
    public function index(DisciplineMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.master.discipline.index');
    }

    public function create()
    {
        $courses = CourseMaster::where('active_inactive',1)->where('end_date', '>', now())->get();
        return view('admin.master.discipline.create_edit', compact('courses'));
    }

    public function edit($id)
    {
        $discipline = DisciplineMaster::findOrFail(decrypt($id));
        $courses = CourseMaster::where('active_inactive',1)->where('end_date', '>', now())->get();
        return view('admin.master.discipline.create_edit', compact('discipline','courses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'discipline_name' => 'required|string|max:100',
            'mark_diduction' => 'nullable|numeric|min:0',
            'course_master_pk' => 'required|exists:course_master,pk',
            'active_inactive' => 'required|in:1,2',
        ]);

        $data = $request->all();
        $data['created_date'] = now();
        $data['modified_date'] = now();

        DisciplineMaster::updateOrCreate(
            ['pk' => $request->id ? decrypt($request->id) : null],
            $data
        );

        return redirect()->route('master.discipline.index')
            ->with('success','Discipline saved successfully');
    }

  
     public function destroy($id)
    {
        try {
             DisciplineMaster::where('pk', decrypt($id))->delete();

            return redirect()->route('master.discipline.index')->with('success', 'Deleted successfully.');
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong.');
        }
    }
}
