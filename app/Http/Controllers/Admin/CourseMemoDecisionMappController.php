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
                    return $row->active_inactive == 1
                        ? '<span class="cmd-badge-active">Active</span>'
                        : '<span class="cmd-badge-inactive">Inactive</span>';
                })

                ->addColumn('action', function ($row) {
                    $deleteUrl = route('course.memo.decision.delete', encrypt($row->pk));
                    $isActive  = ($row->active_inactive == 1);

                    $editBtn = '<a href="javascript:void(0)" class="cmd-action-btn text-primary editConclusion"'
                        . ' data-id="' . $row->pk . '"'
                        . ' data-course="' . $row->course_master_pk . '"'
                        . ' data-memo="' . $row->memo_type_master_pk . '"'
                        . ' data-conclusion="' . $row->memo_conclusion_master_pk . '"'
                        . ' data-status="' . $row->active_inactive . '"'
                        . ' title="Edit"><span class="material-symbols-rounded">edit</span></a>';

                    $toggleBtn = '<div class="form-check form-switch d-inline-block mb-0" style="min-height:0;">'
                        . '<input class="form-check-input status-toggle" type="checkbox" role="switch"'
                        . ' data-table="course_memo_decision_mapp" data-column="active_inactive"'
                        . ' data-id="' . $row->getKey() . '" ' . ($isActive ? 'checked' : '') . '>'
                        . '</div>';

                    $deleteBtn = $isActive
                        ? '<button type="button" class="cmd-action-btn text-muted" disabled style="opacity:0.35;cursor:not-allowed;" title="Cannot delete active record"><span class="material-symbols-rounded">delete</span></button>'
                        : '<button type="button" class="cmd-action-btn text-danger cmd-delete-btn"'
                            . ' data-url="' . $deleteUrl . '"'
                            . ' title="Delete"><span class="material-symbols-rounded">delete</span></button>';

                    return '<div class="d-inline-flex align-items-center gap-1">' . $editBtn . $toggleBtn . $deleteBtn . '</div>';
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
