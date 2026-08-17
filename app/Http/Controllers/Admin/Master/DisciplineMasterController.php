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
        // Add / Edit are modals on the listing now (§3c), so the course list the
        // form needs has to come with the page.
        return $dataTable->render('admin.master.discipline.index', [
            'courses' => $this->selectableCourses(),
        ]);
    }

    /** Running courses the signed-in role may map, shared by index/create/edit. */
    private function selectableCourses()
    {
        $data_course_id = get_Role_by_course();

        $query = CourseMaster::where('active_inactive', 1)
            ->where('end_date', '>=', now()->toDateString());

        if (! empty($data_course_id)) {
            $query->whereIn('pk', $data_course_id);
        }

        return $query->get();
    }

    public function create()
    {
        $data_course_id =  get_Role_by_course();
         if(!empty($data_course_id))
        {
            $courses = CourseMaster::whereIn('pk',$data_course_id)->where('active_inactive',1)->where('end_date', '>=', now()->toDateString())->get();
        }
        else
        {
            $courses = CourseMaster::where('active_inactive',1)->where('end_date', '>=', now()->toDateString())->get();
        }
        return view('admin.master.discipline.create_edit', compact('courses'));
    }

    public function edit($id)
    {
        $discipline = DisciplineMaster::findOrFail(decrypt($id));
        $data_course_id =  get_Role_by_course();
         if(!empty($data_course_id))
        {
            $courses = CourseMaster::whereIn('pk',$data_course_id)->where('active_inactive',1)->where('end_date', '>=', now()->toDateString())->get();
        }
        else
        {
            $courses = CourseMaster::where('active_inactive',1)->where('end_date', '>=', now()->toDateString())->get();
        }
        return view('admin.master.discipline.create_edit', compact('discipline','courses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'discipline_name' => 'required|string|max:100',
            'mark_deduction' => 'nullable|numeric|min:0',
            'course_master_pk' => 'required|exists:course_master,pk',
            'active_inactive' => 'required|in:1,2',
        ]);

        $data = $request->all();
        // The form posts 1 = Active / 2 = Inactive, while the grid's status switch
        // writes 1 / 0. Store the switch's shape so a row deactivated either way
        // reads back the same, and so the Edit modal can preselect it.
        $data['active_inactive'] = (int) $request->active_inactive === 1 ? 1 : 0;
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
