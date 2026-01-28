<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\{CourseMemoDecisionMapp, CourseMaster, MemoTypeMaster, MemoConclusionMaster};
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CourseMemoDecisionMappController extends Controller
{
    public function index(Request $request)
    {

        $mappings = CourseMemoDecisionMapp::all();
        $data_course_id =  get_Role_by_course();
        if (!empty($data_course_id)) {
            $CourseMaster = CourseMaster::whereIn('pk', $data_course_id)
                ->where('active_inactive', '1')
                ->where('end_date', '>', now())
                ->get();
        } else {
            $CourseMaster = CourseMaster::where('active_inactive', '1')
                ->where('end_date', '>', now())
                ->get();
        }
        $MemoTypeMaster = MemoTypeMaster::where('active_inactive', 1)
            ->get();
        $MemoConclusionMaster = MemoConclusionMaster::where('active_inactive', 1)
            ->get();
        if ($request->ajax()) {

            $data_course_id = get_Role_by_course();

            //$query = CourseMemoDecisionMapp::with(['course', 'memo', 'memoConclusion'])->get();

            $query = CourseMemoDecisionMapp::with(['course', 'memo', 'memoConclusion'])
    ->orderBy('course_memo_decision_mapp.created_date', 'desc');

            if (!empty($data_course_id)) {
                $query->whereIn('course_master_pk', $data_course_id);
            }

            return DataTables::of($query)

                ->addIndexColumn()

                ->addColumn('course_name', function ($row) {
                    return $row->course->course_name ?? '-';
                })

                ->addColumn('memo_decision', function ($row) {
                    return $row->memo->memo_type_name ?? '-';
                })

                ->addColumn('memo_conclusion', function ($row) {
                    return $row->memoConclusion->discussion_name ?? '-';
                })

                ->addColumn('status', function ($row) {
                    return '<div class="form-check form-switch d-inline-block">
                    <input class="form-check-input status-toggle"
                        type="checkbox"
                        data-table="course_memo_decision_mapp"
                        data-column="active_inactive"
                        data-id="' . $row->getKey() . '"
                        ' . ($row->active_inactive == 1 ? 'checked' : '') . '>
                </div>';
                })

                ->addColumn('action', function ($row) {

                    $editUrl   = route('course.memo.decision.edit', encrypt($row->pk));
                    $deleteUrl = route('course.memo.decision.delete', encrypt($row->pk));

                    $editBtn = '
                <a href="javascript:void(0)"
                    class="btn btn-sm btn-outline-warning d-flex align-items-center gap-1 editConclusion"
                    data-id="' . $row->pk . '"
                    data-course="' . $row->course_master_pk . '"
                    data-memo="' . $row->memo_type_master_pk . '"
                    data-conclusion="' . $row->memo_conclusion_master_pk . '"
                    data-status="' . $row->active_inactive . '">
                    <i class="material-icons material-symbols-rounded" style="font-size:18px;">edit</i>
                    <span class="d-none d-md-inline">Edit</span>
                </a>';

                    if ($row->active_inactive == 1) {
                        $deleteBtn = '
                    <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" disabled>
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">delete</i>
                        <span class="d-none d-md-inline">Delete</span>
                    </button>';
                    } else {
                        $deleteBtn = '
                    <form action="' . $deleteUrl . '" method="POST" class="d-inline">
                        ' . csrf_field() . method_field('DELETE') . '
                        <button type="submit"
                            class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                            onclick="return confirm(\'Are you sure you want to delete this memo type?\');">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">delete</i>
                            <span class="d-none d-md-inline">Delete</span>
                        </button>
                    </form>';
                    }

                    return '<div class="d-inline-flex gap-2">' . $editBtn . $deleteBtn . '</div>';
                })

                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('admin.course_memo_decision_mapping.index', compact('mappings', 'CourseMaster', 'MemoTypeMaster', 'MemoConclusionMaster'));
    }



    public function create()
    {
        $mappings = CourseMemoDecisionMapp::all();
        $data_course_id =  get_Role_by_course();
        if (!empty($data_course_id)) {
            $CourseMaster = CourseMaster::whereIn('pk', $data_course_id)
                ->where('active_inactive', '1')
                ->where('end_date', '>', now())
                ->get();
        } else {
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
        try {
            $validated = $request->validate([
                'course_master_pk'          => 'required|numeric',
                'memo_type_master_pk'       => 'required|numeric',
                'memo_conclusion_master_pk' => 'required|numeric',
                'active_inactive'           => 'required|in:1,2',
            ]);

            CourseMemoDecisionMapp::create([
                'course_master_pk'          => $validated['course_master_pk'],
                'memo_type_master_pk'       => $validated['memo_type_master_pk'],
                'memo_conclusion_master_pk' => $validated['memo_conclusion_master_pk'],
                'active_inactive'           => $validated['active_inactive'],
                'created_date'              => now(),
                'modified_date'             => now(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Mapping added successfully.',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {

            Log::error('CourseMemoDecisionMapp store error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }



    public function edit($id)
    {
        $data_course_id =  get_Role_by_course();
        if (!empty($data_course_id)) {
            $CourseMaster = CourseMaster::whereIn('pk', $data_course_id)
                ->where('active_inactive', '1')
                ->where('end_date', '>', now())
                ->get();
        } else {
            $CourseMaster = CourseMaster::where('active_inactive', '1')
                ->where('end_date', '>', now())
                ->get();
        }
        $MemoTypeMaster = MemoTypeMaster::all();
        $courseMemoMap = CourseMemoDecisionMapp::findOrFail(decrypt($id));
        $MemoConclusionMaster = MemoConclusionMaster::all();
        return view('admin.course_memo_decision_mapping.create_edit', compact('courseMemoMap', 'CourseMaster', 'MemoTypeMaster', 'MemoConclusionMaster'));
    }


    public function update(Request $request)
    {
        $request->validate([
            'id'                        => 'required|numeric',
            'course_master_pk'          => 'required|numeric',
            'memo_type_master_pk'       => 'required|numeric',
            'memo_conclusion_master_pk' => 'required|numeric',
            'active_inactive'           => 'required|in:1,2',
        ]);

        $mapping = CourseMemoDecisionMapp::find($request->id);

        if (!$mapping) {
            return response()->json([
                'status' => false,
                'message' => 'Record not found'
            ]);
        }

        $mapping->update([
            'course_master_pk'          => $request->course_master_pk,
            'memo_type_master_pk'       => $request->memo_type_master_pk,
            'memo_conclusion_master_pk' => $request->memo_conclusion_master_pk,
            'active_inactive'           => $request->active_inactive,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Mapping updated successfully'
        ]);
    }



    public function destroy($id)
    {
        $mapping = CourseMemoDecisionMapp::findOrFail(decrypt($id));
        $mapping->delete();

        return redirect()->route('course.memo.decision.index')->with('success', 'Mapping deleted successfully.');
    }
}
