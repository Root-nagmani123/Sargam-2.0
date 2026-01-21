<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PeerGroupMembersImport;
use App\Exports\PeerEvaluationExport;
use PDF;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\PeerGroup;
use App\Models\PeerEvent;
use App\Models\PeerCourse;
use App\Models\PeerColumn;
use App\Models\PeerReflectionField;

class PeerEvaluationController extends Controller
{
    /**
     * Display main management panel with Course → Event → Group hierarchy
     */
    public function index()
    {
        // Get courses with their events and group counts
        $courses = PeerCourse::with(['events' => function ($query) {
            $query->active()->withCount('groups');
        }])->active()->withCount(['events', 'groups'])->get();

        // Get events with their course and group counts
        $events = PeerEvent::active()->withCount('groups')->get();

        // Get groups with member count using proper Eloquent
        $groups = PeerGroup::with(['course', 'event'])
            ->withCount('members')
            ->where('is_active', 1)
            ->get();

        $columns = PeerColumn::with(['course', 'event'])->get();

        // Get reflection fields with their course and event
        $reflectionFields = PeerReflectionField::with(['course', 'event'])->get();

        return view('admin.forms.peer_evaluation.admin', compact('courses', 'groups', 'columns', 'reflectionFields', 'events'));
    }

    // ==================== COURSE MANAGEMENT METHODS ====================

    /**
     * Add new course
     */
    public function addCourse(Request $request)
    {
        $request->validate([
            'course_name' => 'required|string|max:255|unique:peer_courses,course_name'
        ]);

        try {
            PeerCourse::create([
                'course_name' => $request->course_name,
                'is_active' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Course added successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add course: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== EVENT MANAGEMENT METHODS ====================

    /**
     * Add new event to course
     */
    public function addEvent(Request $request)
    {
        $request->validate([
            'event_name' => 'required|string|max:255',
            'course_id' => 'required|exists:peer_courses,id'
        ]);

        try {
            PeerEvent::create([
                'event_name' => $request->event_name,
                'course_id' => $request->course_id,
                'is_active' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Event added successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add event: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get events by course (AJAX)
     */
    public function getEventsByCourse($courseId)
    {
        try {
            $events = PeerEvent::where('course_id', $courseId)->active()->get();
            return response()->json($events);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    // ==================== GROUP MANAGEMENT METHODS ====================

    /**
     * Add new group with course and event
     */
    public function addGroup(Request $request)
    {
        $request->validate([
            'group_name' => 'required|string|max:255',
            'course_id' => 'required|exists:peer_courses,id',
            'event_id' => 'required|exists:peer_events,id',
            'max_marks' => 'required|numeric|min:1|max:100'
        ]);

        try {
            // Verify event belongs to course
            $event = PeerEvent::where('id', $request->event_id)
                ->where('course_id', $request->course_id)
                ->firstOrFail();

            PeerGroup::create([
                'group_name' => $request->group_name,
                'course_id' => $request->course_id,
                'event_id' => $request->event_id,
                'max_marks' => $request->max_marks,
                'is_active' => true,
                'is_form_active' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Group added successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding group: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update max marks for group
     */
    public function updateMaxMarks(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:peer_groups,id',
            'max_marks' => 'required|numeric|min:1|max:100'
        ]);

        try {
            $group = PeerGroup::findOrFail($request->group_id);
            $group->update([
                'max_marks' => $request->max_marks,
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Max marks updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update max marks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle form active status
     */
    public function toggleFormStatus($id)
    {
        try {
            $group = PeerGroup::findOrFail($id);
            $group->is_form_active = !$group->is_form_active;
            $group->save();

            return response()->json([
                'status' => 'success',
                'is_form_active' => $group->is_form_active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete group
     */
    public function deleteGroup($id)
    {
        try {
            $group = PeerGroup::findOrFail($id);
            $group->delete();

            return response()->json([
                'success' => true,
                'message' => 'Group deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting group: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== COLUMN MANAGEMENT METHODS ====================

    /**
     * Add new column with optional course/event association
     */
    public function addColumn(Request $request)
    {
        $request->validate([
            'column_name' => 'required|string|max:255',
            'course_id' => 'nullable|exists:peer_courses,id',
            'event_id' => 'nullable|exists:peer_events,id'
        ]);

        try {
            PeerColumn::create([
                'column_name' => $request->column_name,
                'course_id' => $request->course_id,
                'event_id' => $request->event_id,
                'is_visible' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Column added successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding column: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle column visibility
     */
    public function toggleColumnVisibility($id)
    {
        try {
            $column = PeerColumn::findOrFail($id);
            $column->is_visible = !$column->is_visible;
            $column->save();

            return response()->json([
                'success' => true,
                'is_visible' => $column->is_visible
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete column
     */
    public function deleteColumn($id)
    {
        try {
            $column = PeerColumn::findOrFail($id);
            $column->delete();

            return response()->json([
                'success' => true,
                'message' => 'Column deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting column: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== REFLECTION FIELDS MANAGEMENT ====================

    /**
     * Add reflection field with optional course/event association
     */
    public function addReflectionField(Request $request)
    {
        $request->validate([
            'field_label' => 'required|string|max:255',
            'course_id' => 'nullable|exists:peer_courses,id',
            'event_id' => 'nullable|exists:peer_events,id'
        ]);

        try {
            DB::table('peer_reflection_fields')->insert([
                'field_label' => $request->field_label,
                'course_id' => $request->course_id,
                'event_id' => $request->event_id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reflection field added successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add reflection field: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle reflection field active status
     */
    public function toggleReflectionField($id)
    {
        try {
            $field = DB::table('peer_reflection_fields')->where('id', $id)->first();

            if ($field) {
                $newState = !$field->is_active;
                DB::table('peer_reflection_fields')
                    ->where('id', $id)
                    ->update(['is_active' => $newState]);

                return response()->json([
                    'success' => true,
                    'new_state' => $newState
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Reflection field not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle reflection field'
            ], 500);
        }
    }

    /**
     * Delete reflection field
     */
    public function deleteReflectionField($id)
    {
        try {
            DB::table('peer_reflection_fields')->where('id', $id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Reflection field deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete reflection field'
            ], 500);
        }
    }

    // ==================== EXISTING METHODS (Keep as is) ====================

    /**
     * Get members for a specific group (AJAX)
     */
    public function getGroupMembers($groupId)
    {
        $members = DB::table('peer_group_members')
            ->where('group_id', $groupId)
            ->select('member_pk', 'user_name as first_name', 'user_id')
            ->get();

        return response()->json($members);
    }

    /**
     * Store evaluation scores and reflections
     */
    public function store(Request $request)
    {
        $scores = $request->input('scores', []);
        $reflections = $request->input('reflections', []);
        $groupId = $request->input('group_id');
        $userId = auth()->id();

        try {
            DB::beginTransaction();

            foreach ($scores as $memberId => $columns) {
                foreach ($columns as $columnId => $score) {
                    DB::table('peer_scores')->updateOrInsert(
                        [
                            'member_id' => $memberId,
                            'column_id' => $columnId,
                            'group_id' => $groupId,
                            'evaluator_id' => $userId
                        ],
                        [
                            'score' => $score,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    );
                }
            }

            foreach ($reflections as $fieldId => $description) {
                DB::table('reflection_responses')->updateOrInsert(
                    [
                        'evaluator_id' => $userId,
                        'field_id' => $fieldId,
                        'group_id' => $groupId
                    ],
                    [
                        'description' => $description,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }

            DB::commit();
            return back()->with('success', 'Evaluation submitted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to store evaluation: ' . $e->getMessage());
            return back()->with('error', 'Failed to submit evaluation: ' . $e->getMessage());
        }
    }

    /**
     * Display user evaluation form
     */
    public function user_index(Request $request)
    {
        $groups = DB::table('peer_groups')
            ->select(
                'peer_groups.*',
                DB::raw('COUNT(peer_group_members.id) as member_count')
            )
            ->leftJoin('peer_group_members', 'peer_groups.id', '=', 'peer_group_members.group_id')
            ->groupBy('peer_groups.id')
            ->get();

        $allUsers = DB::table('fc_registration_master')
            ->select('pk', 'first_name')
            ->orderBy('first_name')
            ->get();

        $selectedGroupId = $request->query('group_id', $groups->first()->id ?? null);
        $selectedGroup = null;
        $columns = collect();
        $reflectionFields = collect();

        if ($selectedGroupId) {
            $selectedGroup = DB::table('peer_groups')->where('id', $selectedGroupId)->first();

            if ($selectedGroup) {
                $columns = DB::table('peer_columns')
                    ->where('is_visible', 1)
                    ->where(function ($query) use ($selectedGroup) {
                        $query->where('course_id', $selectedGroup->course_id)
                            ->orWhereNull('course_id');
                    })
                    ->get();

                $reflectionFields = DB::table('peer_reflection_fields')
                    ->where('is_active', 1)
                    ->where(function ($query) use ($selectedGroup) {
                        $query->where('course_id', $selectedGroup->course_id)
                            ->orWhereNull('course_id');
                    })
                    ->get();
            }

            $members = DB::table('peer_group_members')
                ->where('group_id', $selectedGroupId)
                ->select('id', 'member_pk', 'user_name as first_name', 'user_id', 'ot_code')
                ->get();
        } else {
            $members = [];
        }

        return view('admin.forms.peer_evaluation.index', compact(
            'groups',
            'columns',
            'allUsers',
            'members',
            'selectedGroupId',
            'reflectionFields',
            'selectedGroup'
        ));
    }

    /**
     * Legacy method - Add group without course/event
     */
    public function storeGroup(Request $request)
    {
        $request->validate([
            'group_name' => 'required|string|max:255',
        ]);

        DB::table('peer_groups')->insert([
            'group_name' => $request->group_name,
            'max_marks' => $request->max_marks ?? 10.00,
            'is_active' => true,
            'is_form_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return back()->with('success', 'Group added successfully!');
    }

    /**
     * Toggle group active status
     */
    public function toggleGroup($id)
    {
        $group = DB::table('peer_groups')->where('id', $id)->first();
        DB::table('peer_groups')->where('id', $id)->update(['is_active' => !$group->is_active]);
        return response()->json(['new_state' => !$group->is_active]);
    }

    /**
     * Show group members
     */
    public function showGroupMembers($groupId)
    {
        $group = DB::table('peer_groups')->where('id', $groupId)->first();
        $members = DB::table('peer_group_members')
            ->join('fc_registration_master', 'peer_group_members.member_pk', '=', 'fc_registration_master.pk')
            ->where('peer_group_members.group_id', $groupId)
            ->select('fc_registration_master.pk', 'fc_registration_master.first_name')
            ->get();

        return view('admin.forms.peer_evaluation.group_members', compact('group', 'members'));
    }

    /**
     * Add members to group
     */
    public function addMembersToGroup(Request $request, $groupId)
    {
        $request->validate([
            'member_pks' => 'required|array',
            'member_pks.*' => 'required|integer'
        ]);

        foreach ($request->member_pks as $memberPk) {
            $exists = DB::table('peer_group_members')
                ->where('group_id', $groupId)
                ->where('member_pk', $memberPk)
                ->exists();

            if (!$exists) {
                DB::table('peer_group_members')->insert([
                    'group_id' => $groupId,
                    'member_pk' => $memberPk,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        return back()->with('success', 'Members added to group successfully!');
    }

    /**
     * Remove member from group
     */
    public function removeMemberFromGroup($groupId, $memberPk)
    {
        DB::table('peer_group_members')
            ->where('group_id', $groupId)
            ->where('member_pk', $memberPk)
            ->delete();

        return back()->with('success', 'Member removed from group successfully!');
    }

    /**
     * Show import members view
     */
    public function importMembersView($groupId)
    {
        $group = DB::table('peer_groups')->where('id', $groupId)->first();
        return view('admin.forms.peer_evaluation.import_members', compact('group'));
    }

    /**
     * Import members from Excel
     */
    public function importExcel(Request $request, $groupId)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new PeerGroupMembersImport($groupId), $request->file('excel_file'));
            return back()->with('success', 'Excel file imported successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    /**
     * Download template for peer group members
     */
    public function PeerDownloadTemplate(): StreamedResponse
    {
        $headers = [
            'user_id',
            'user_name',
            'ot_code',
            'course_name',
            'event_name'
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, NULL, 'A1');

        $lastColumn = $sheet->getHighestColumn();
        $lastRow    = $sheet->getHighestRow();

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '99CCFF'],
            ],
        ]);

        foreach (range('A', $lastColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'peer_group_members_template.xlsx');
    }

    /**
     * Show groups available to user
     */
    public function user_groups()
    {
        $userId = auth()->id();

        $groups = DB::table('peer_groups as g')
            ->leftJoin('peer_group_members as m', 'g.id', '=', 'm.group_id')
            ->where('g.is_form_active', 1)
            ->select(
                'g.id',
                'g.group_name',
                DB::raw('MAX(m.course_name) as course_name'),
                DB::raw('MAX(m.event_name) as event_name'),
                DB::raw('GROUP_CONCAT(m.ot_code SEPARATOR ", ") as ot_codes')
            )
            ->groupBy('g.id', 'g.group_name')
            ->get();

        $userGroups = DB::table('peer_group_members')
            ->where('user_id', $userId)
            ->pluck('group_id')
            ->toArray();

        return view('admin.forms.peer_evaluation.user_groups', compact('groups', 'userGroups'));
    }

    /**
     * Show evaluation form for specific group
     */
    public function user_evaluation($groupId)
    {
        $userId = auth()->id();

        $group = DB::table('peer_groups')
            ->where('id', $groupId)
            ->where('is_form_active', 1)
            ->first();

        if (!$group || !DB::table('peer_group_members')->where('group_id', $groupId)->where('user_id', $userId)->exists()) {
            abort(403, 'You are not authorized for this group.');
        }

        $columns = DB::table('peer_columns')->where('is_visible', 1)->get();
        $members = DB::table('peer_group_members')
            ->where('group_id', $groupId)
            ->select('member_pk', 'user_name as first_name', 'user_id')
            ->get();

        return view('admin.forms.peer_evaluation.user_evaluation', compact('group', 'columns', 'members'));
    }

    /**
     * View all submissions for a group
     */
    // public function viewSubmissions($groupId)
    // {
    //     $groups = DB::table('peer_groups')
    //         ->select('id', 'group_name', 'is_active')
    //         ->get();

    //     if ($groups->isEmpty()) {
    //         return redirect()->back()->with('error', 'Group not found.');
    //     }

    //     $members = DB::table('peer_group_members')
    //         ->leftJoin('user_credentials', 'peer_group_members.user_id', '=', 'user_credentials.pk')
    //         ->where('peer_group_members.group_id', $groupId)
    //         ->select(
    //             'peer_group_members.id',
    //             'peer_group_members.member_pk',
    //             'peer_group_members.user_name as first_name',
    //             'peer_group_members.user_id',
    //             'peer_group_members.ot_code',
    //             'user_credentials.first_name as user_full_name',
    //             'user_credentials.last_name as user_last_name'
    //         )
    //         ->get();

    //     $columns = DB::table('peer_columns')->where('is_visible', 1)->get();

    //     $scores = DB::table('peer_scores')
    //         ->leftJoin('user_credentials', 'peer_scores.evaluator_id', '=', 'user_credentials.pk')
    //         ->where('peer_scores.group_id', $groupId)
    //         ->select(
    //             'peer_scores.*',
    //             'user_credentials.first_name as evaluator_first_name',
    //             'user_credentials.last_name as evaluator_last_name'
    //         )
    //         ->get();

    //     $reflectionFields = DB::table('peer_reflection_fields')->where('is_active', 1)->get();

    //     $reflectionResponses = DB::table('reflection_responses')
    //         ->leftJoin('user_credentials', 'reflection_responses.evaluator_id', '=', 'user_credentials.pk')
    //         ->where('reflection_responses.group_id', $groupId)
    //         ->select(
    //             'reflection_responses.*',
    //             'user_credentials.first_name as evaluator_first_name',
    //             'user_credentials.last_name as evaluator_last_name'
    //         )
    //         ->get()
    //         ->keyBy(function ($item) {
    //             return $item->evaluator_id . '-' . $item->field_id;
    //         });

    //     $selectedGroupId = $groupId;

    //     return view('admin.forms.peer_evaluation.view_submissions', compact(
    //         'groups',
    //         'members',
    //         'columns',
    //         'scores',
    //         'selectedGroupId',
    //         'reflectionFields',
    //         'reflectionResponses'
    //     ));
    // }

    public function viewSubmissions($groupId)
    {
      //  dd('ddd');
        $groups = DB::table('peer_groups')
            ->select('id', 'group_name', 'is_active', 'course_id', 'event_id')
            ->get();

        if ($groups->isEmpty()) {
            return redirect()->back()->with('error', 'Group not found.');
        }

       

        // Get the specific group to access its course_id and event_id
        $currentGroup = DB::table('peer_groups')->where('id', $groupId)->first();

        if (!$currentGroup) {
            return redirect()->back()->with('error', 'Group not found.');
        }
       
        $members = DB::table('peer_group_members')
            ->leftJoin('user_credentials', 'peer_group_members.user_id', '=', 'user_credentials.pk')
            ->where('peer_group_members.group_id', $groupId)
            ->select(
                'peer_group_members.id',
                'peer_group_members.member_pk',
                'peer_group_members.user_name as first_name',
                'peer_group_members.user_id',
                'peer_group_members.ot_code',
                'user_credentials.first_name as user_full_name',
                'user_credentials.last_name as user_last_name'
            )
            ->get();

        // Get columns related to this group's course and event
        $columns = DB::table('peer_columns')
            ->where('is_visible', 1)
            ->where(function ($query) use ($currentGroup) {
                // Course-specific columns
                $query->where('course_id', $currentGroup->course_id)
                    // Event-specific columns for this course
                    ->orWhere(function ($q) use ($currentGroup) {
                        $q->where('course_id', $currentGroup->course_id)
                            ->where('event_id', $currentGroup->event_id);
                    })
                    // Global columns (no course/event association)
                    ->orWhere(function ($q) {
                        $q->whereNull('course_id')
                            ->whereNull('event_id');
                    });
            })->orderBy('id')
            ->get();

        $scores = DB::table('peer_scores')
            ->leftJoin('user_credentials', 'peer_scores.evaluator_id', '=', 'user_credentials.pk')
            ->where('peer_scores.group_id', $groupId)
            ->select(
                'peer_scores.*',
                'user_credentials.first_name as evaluator_first_name',
                'user_credentials.last_name as evaluator_last_name'
            )
            ->get();

        // Get reflection fields related to this group's course and event
        $reflectionFields = DB::table('peer_reflection_fields')
            ->where('is_active', 1)
            ->where(function ($query) use ($currentGroup) {
                // Course-specific reflection fields
                $query->where('course_id', $currentGroup->course_id)
                    // Event-specific reflection fields for this course
                    ->orWhere(function ($q) use ($currentGroup) {
                        $q->where('course_id', $currentGroup->course_id)
                            ->where('event_id', $currentGroup->event_id);
                    })
                    // Global reflection fields (no course/event association)
                    ->orWhere(function ($q) {
                        $q->whereNull('course_id')
                            ->whereNull('event_id');
                    });
            })
            ->get();

        $reflectionResponses = DB::table('reflection_responses')
            ->leftJoin('user_credentials', 'reflection_responses.evaluator_id', '=', 'user_credentials.pk')
            ->where('reflection_responses.group_id', $groupId)
            ->select(
                'reflection_responses.*',
                'user_credentials.first_name as evaluator_first_name',
                'user_credentials.last_name as evaluator_last_name'
            )
            ->get()
            ->keyBy(function ($item) {
                return $item->evaluator_id . '-' . $item->field_id;
            });

        $selectedGroupId = $groupId;
     
        return view('admin.forms.peer_evaluation.view_submissions', compact(
            'groups',
            'members',
            'columns',
            'scores',
            'selectedGroupId',
            'reflectionFields',
            'reflectionResponses',
            'currentGroup' // Pass the current group to access course/event info in view
        ));
    }

    /**
     * Export submissions in various formats
     */
    public function exportSubmissions(Request $request, $groupId)
    {
        $format = $request->input('format');

        $members = DB::table('peer_group_members')
            ->leftJoin('user_credentials', 'peer_group_members.user_id', '=', 'user_credentials.pk')
            ->where('peer_group_members.group_id', $groupId)
            ->select(
                'peer_group_members.id',
                'peer_group_members.member_pk',
                'peer_group_members.user_name as first_name',
                'peer_group_members.user_id',
                'peer_group_members.ot_code',
                'user_credentials.first_name as user_full_name',
                'user_credentials.last_name as user_last_name'
            )
            ->get();

        $columns = DB::table('peer_columns')->get();

        $scores = DB::table('peer_scores')
            ->leftJoin('user_credentials', 'peer_scores.evaluator_id', '=', 'user_credentials.pk')
            ->where('peer_scores.group_id', $groupId)
            ->select(
                'peer_scores.*',
                'user_credentials.first_name as evaluator_first_name',
                'user_credentials.last_name as evaluator_last_name'
            )
            ->get();

        $reflectionFields = DB::table('peer_reflection_fields')->where('is_active', 1)->get();

        $reflectionResponses = DB::table('reflection_responses')
            ->leftJoin('user_credentials', 'reflection_responses.evaluator_id', '=', 'user_credentials.pk')
            ->where('reflection_responses.group_id', $groupId)
            ->select(
                'reflection_responses.*',
                'user_credentials.first_name as evaluator_first_name',
                'user_credentials.last_name as evaluator_last_name'
            )
            ->get()
            ->keyBy(function ($item) {
                return $item->evaluator_id . '-' . $item->field_id;
            });

        $group = DB::table('peer_groups')->where('id', $groupId)->first();
        $groupName = $group->group_name ?? 'Group';

        if ($format === 'pdf') {
            $pdf = PDF::loadView('admin.forms.peer_evaluation.export_pdf', [
                'members' => $members,
                'columns' => $columns,
                'scores' => $scores,
                'groupName' => $groupName,
                'reflectionFields' => $reflectionFields,
                'reflectionResponses' => $reflectionResponses
            ]);
            return $pdf->download($groupName . '_submissions.pdf');
        } else {
            return Excel::download(new PeerEvaluationExport(
                $members,
                $columns,
                $scores,
                $groupName,
                $reflectionFields,
                $reflectionResponses
            ), $groupName . '_submissions.' . $format);
        }
    }
}
