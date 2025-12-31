<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\{CourseMemoDecisionMapp,CourseMaster, MemoTypeMaster, MemoConclusionMaster};
class CourseMemoDecisionMappController extends Controller
{
     public function index()
    { 
        $data_course_id =  get_Role_by_course();
         if(!empty($data_course_id))
        {
            $mappings = CourseMemoDecisionMapp::whereIn('course_master_pk',$data_course_id)->with(['course', 'memo', 'memoConclusion'])->get();
        }
        else
        {
            $mappings = CourseMemoDecisionMapp::with(['course', 'memo', 'memoConclusion'])->get();
        }
        return view('admin.course_memo_decision_mapping.index', compact('mappings'));
    }

    public function create()
    { 
        $mappings = CourseMemoDecisionMapp::all();
        $data_course_id =  get_Role_by_course();
         if(!empty($data_course_id))
        {
            $CourseMaster = CourseMaster::whereIn('pk',$data_course_id)
            ->where('active_inactive', '1')
            ->where('end_date', '>', now())
            ->get();
        }
        else
        {
            $CourseMaster = CourseMaster::where('active_inactive', '1')
            ->where('end_date', '>', now())
            ->get();
        }
        $MemoTypeMaster = MemoTypeMaster::where('active_inactive', 1)
                              ->get();
        $MemoConclusionMaster = MemoConclusionMaster::where('active_inactive', 1)
                              ->get();
        return view('admin.course_memo_decision_mapping.create_edit', compact('mappings', 'CourseMaster', 'MemoTypeMaster', 'MemoConclusionMaster'));
    }

    public function store(Request $request)
    { 
        
        $request->validate([
            'course_master_pk' => 'required|numeric',
            'memo_type_master_pk' => 'required|numeric',
            'memo_conclusion_master_pk' => 'required|numeric',
            'active_inactive' => 'required|in:1,2',
        ]);
        CourseMemoDecisionMapp::create($request->all());

        return redirect()->route('course.memo.decision.index')->with('success', 'Mapping added successfully.');
    }

   public function edit($id)
{
    $data_course_id =  get_Role_by_course();
     if(!empty($data_course_id))
    {
        $CourseMaster = CourseMaster::whereIn('pk',$data_course_id)
        ->where('active_inactive', '1')
        ->where('end_date', '>', now())
        ->get();
    }
    else
    {
        $CourseMaster = CourseMaster::where('active_inactive', '1')
        ->where('end_date', '>', now())
        ->get();
    }
    $MemoTypeMaster = MemoTypeMaster::all();
    $courseMemoMap = CourseMemoDecisionMapp::findOrFail(decrypt($id));
            $MemoConclusionMaster = MemoConclusionMaster::all();
    return view('admin.course_memo_decision_mapping.create_edit', compact('courseMemoMap', 'CourseMaster', 'MemoTypeMaster', 'MemoConclusionMaster'));
}


    public function update(Request $request, $id)
    {
        $request->validate([
            'course_master_pk' => 'required|numeric',
            'memo_type_master_pk' => 'required|numeric',
            'memo_conclusion_master_pk' => 'required|numeric',
            'active_inactive' => 'required|in:1,2',
        ]);

        $mapping = CourseMemoDecisionMapp::findOrFail(decrypt($id));
        $mapping->update($request->all());

        return redirect()->route('course.memo.decision.index')->with('success', 'Mapping updated successfully.');
    }

    public function destroy($id)
    {
        $mapping = CourseMemoDecisionMapp::findOrFail(decrypt($id));
        $mapping->delete();

        return redirect()->route('course.memo.decision.index')->with('success', 'Mapping deleted successfully.');
    }
}
 