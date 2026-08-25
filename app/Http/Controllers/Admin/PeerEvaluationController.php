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
use App\Models\CourseMaster;
use App\Models\PeerColumn;
use App\Models\PeerReflectionField;
use App\Support\PeerEvaluationForm;

class PeerEvaluationController extends Controller
{
    /**
     * Display main management panel with Course → Event → Group hierarchy
     */
    public function index()
    {
        // Get courses with their events and group counts
		
		// Courses come from course_master now (peer_courses was retired by
		// 2026_08_24_000002_point_peer_evaluation_at_course_master). Only courses
		// that already carry peer content are listed - course_master has 145 rows
		// and paging through the ones with nothing attached is just noise. Use
		// "Manage Events" to attach an event to a new course.
		$courses = CourseMaster::query()
			->where(function ($query) {
				$query->whereExists(function ($sub) {
					$sub->selectRaw('1')->from('peer_events')
						->whereColumn('peer_events.course_id', 'course_master.pk');
				})->orWhereExists(function ($sub) {
					$sub->selectRaw('1')->from('peer_groups')
						->whereColumn('peer_groups.course_id', 'course_master.pk');
				});
			})
			->withCount([
				'peerEvents as events_count',
				'peerGroups as groups_count',
			])
			->with(['peerEvents' => function ($query) {
				$query->active()->withCount('groups');
			}])
			->orderBy('course_name')
			->paginate(5); 

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

    // Course CRUD lived here. Courses are course_master rows now, owned by
    // Course Master (admin/programme) - this module no longer creates or
    // deletes them. See 2026_08_24_000002_point_peer_evaluation_at_course_master.

    // ==================== EVENT MANAGEMENT METHODS ====================

    /**
     * Add new event to course
     */
    public function addEvent(Request $request)
    {
        $request->validate([
            'event_name' => 'required|string|max:255',
            'course_id' => 'required|exists:course_master,pk'
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
            'course_id' => 'required|exists:course_master,pk',
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
            'course_id' => 'nullable|exists:course_master,pk',
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
            'course_id' => 'nullable|exists:course_master,pk',
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
     * Record one evaluator's submission for one group.
     *
     * Authorisation and validation both run off PeerEvaluationForm - the same
     * class user_index() renders from - so anything that could not be shown is
     * also refused here.
     *
     * The previous version trusted the request completely: any authenticated
     * caller could post any group_id with any member_id, any column_id and any
     * score, whether or not they were in the group and whether or not the form
     * was open.
     */
    public function store(Request $request)
    {
        $userPk = (int) auth()->user()->pk;

        $validated = $request->validate([
            'group_id' => ['required', 'integer'],
            'scores' => ['array'],
            'remarks' => ['array'],
            'remarks.*' => ['nullable', 'string', 'max:2000'],
            'reflections' => ['array'],
            'reflections.*' => ['nullable', 'string', 'max:5000'],
        ]);

        $group = DB::table('peer_groups')->where('id', $validated['group_id'])->first();

        if (! $group) {
            return back()->withInput()->with('error', 'That peer group no longer exists.');
        }

        // Membership, not role: holding an admin role does not put you in the
        // group, and peer_scores has nowhere to record a non-member.
        $membership = PeerEvaluationForm::membershipOf((int) $group->id, $userPk);

        if (! $membership) {
            return back()->withInput()->with('error', 'You are not a member of this group.');
        }

        if ($closed = PeerEvaluationForm::closedReason($group)) {
            return back()->withInput()->with('error', $closed);
        }

        // Resolved server-side. Ids in the request are only ever checked against
        // these, never trusted.
        $peerIds = PeerEvaluationForm::peersFor($group, (int) $membership->id)
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
        $columns = PeerEvaluationForm::columnsFor($group)->keyBy('id');
        $fieldIds = PeerEvaluationForm::reflectionFieldsFor($group)
            ->pluck('id')->map(fn ($id) => (int) $id)->all();

        // The remarks box is offered when any criterion on the form asks for one:
        // the design puts one remark against the evaluated OT, not one per
        // criterion, so a single flag anywhere in scope opens it.
        $allowsRemarks = $columns->contains(fn ($column) => (bool) $column->has_remarks);

        $clean = [];
        $distributed = 0.0;

        foreach ((array) $request->input('scores', []) as $memberId => $columnScores) {
            $memberId = (int) $memberId;

            if (! in_array($memberId, $peerIds, true)) {
                return back()->withInput()->with(
                    'error',
                    'That submission scored somebody who is not one of your peers in this group.'
                );
            }

            foreach ((array) $columnScores as $columnId => $score) {
                $column = $columns->get((int) $columnId);

                if (! $column) {
                    return back()->withInput()->with(
                        'error',
                        'That submission used a criterion that is not on this form.'
                    );
                }

                // An untouched box is not a zero - leave it unrecorded so the
                // report can tell "not scored" from "scored nothing".
                if ($score === null || $score === '') {
                    continue;
                }

                if (! is_numeric($score)) {
                    return back()->withInput()->with('error', 'Scores have to be numbers.');
                }

                $score = (float) $score;
                // The column's own cap wins; the group value is only the default
                // a new column starts from. Same rule the form renders with.
                $max = (float) ($column->max_marks ?? $group->max_marks ?? 10);

                if ($score < 0 || $score > $max) {
                    return back()->withInput()->with('error', sprintf(
                        '%s has to be between 0 and %s.',
                        $column->column_name,
                        self::trimDecimals($max)
                    ));
                }

                if ($column->evaluation_type === PeerColumn::TYPE_DISTRIBUTE_MARKS) {
                    $distributed += $score;
                }

                $clean[$memberId][(int) $column->id] = $score;
            }
        }

        // buffer_marks is one pool per evaluator across the group's "Distribute
        // Marks" criteria, so it can only be checked once the whole submission is
        // known - not per box as it is typed.
        $buffer = (float) ($group->buffer_marks ?? 0);

        if ($buffer > 0 && $distributed > $buffer) {
            return back()->withInput()->with('error', sprintf(
                'You have handed out %s marks but the pool for this group is %s.',
                self::trimDecimals($distributed),
                self::trimDecimals($buffer)
            ));
        }

        try {
            DB::beginTransaction();

            foreach ($clean as $memberId => $columnScores) {
                foreach ($columnScores as $columnId => $score) {
                    DB::table('peer_scores')->updateOrInsert(
                        [
                            'member_id' => $memberId,
                            'column_id' => $columnId,
                            'group_id' => $group->id,
                            'evaluator_id' => $userPk,
                        ],
                        [
                            'score' => $score,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            if ($allowsRemarks) {
                // One free-text note per evaluated OT, keyed the same way the
                // scores are so the Evaluation Report can line a remark up with
                // that evaluator's scores. A cleared box deletes the row rather
                // than storing an empty one.
                foreach ((array) $request->input('remarks', []) as $memberId => $remark) {
                    $memberId = (int) $memberId;

                    if (! in_array($memberId, $peerIds, true)) {
                        continue;
                    }

                    $remark = is_string($remark) ? trim($remark) : '';

                    if ($remark === '') {
                        DB::table('peer_evaluation_remarks')
                            ->where([
                                'member_id' => $memberId,
                                'group_id' => $group->id,
                                'evaluator_id' => $userPk,
                            ])
                            ->delete();
                        continue;
                    }

                    DB::table('peer_evaluation_remarks')->updateOrInsert(
                        [
                            'member_id' => $memberId,
                            'group_id' => $group->id,
                            'evaluator_id' => $userPk,
                        ],
                        [
                            'remarks' => mb_substr($remark, 0, 2000),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }

            foreach ((array) $request->input('reflections', []) as $fieldId => $description) {
                $fieldId = (int) $fieldId;

                if (! in_array($fieldId, $fieldIds, true)) {
                    continue;
                }

                $description = is_string($description) ? trim($description) : '';

                if ($description === '') {
                    DB::table('reflection_responses')
                        ->where([
                            'evaluator_id' => $userPk,
                            'field_id' => $fieldId,
                            'group_id' => $group->id,
                        ])
                        ->delete();
                    continue;
                }

                DB::table('reflection_responses')->updateOrInsert(
                    [
                        'evaluator_id' => $userPk,
                        'field_id' => $fieldId,
                        'group_id' => $group->id,
                    ],
                    [
                        'description' => $description,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            DB::commit();

            return redirect()->route('peer.index', ['group_id' => $group->id])
                ->with('success', 'Evaluation submitted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to store evaluation: ' . $e->getMessage());

            return back()->withInput()->with('error', 'Failed to submit evaluation. Please try again.');
        }
    }

    /** "10.00" -> "10", "7.50" -> "7.5". Marks are decimals but read as counts. */
    private static function trimDecimals(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /**
     * The OT-facing evaluation form.
     *
     * Every decision about what appears here - which groups, which criteria,
     * which peers, whether the form is open at all - comes from
     * App\Support\PeerEvaluationForm, and store() validates against the same
     * class. They used to run separate queries, which is how a column belonging
     * to another course could be rendered, and how any column_id in the table
     * could be posted back.
     */
    public function user_index(Request $request)
    {
        $userPk = (int) auth()->user()->pk;

        // Only groups this OT is actually a member of. Membership is resolved
        // through the login handle, not by comparing ids - see
        // PeerEvaluationForm's identity note.
        $groups = PeerEvaluationForm::groupsFor($userPk);

        $requested = $request->query('group_id');

        // With no explicit pick, land on a group that is actually open rather
        // than on whichever row sorted first.
        $selectedGroup = filled($requested)
            ? $groups->firstWhere('id', (int) $requested)
            : ($groups->first(fn ($g) => PeerEvaluationForm::closedReason($g) === null) ?? $groups->first());

        if (filled($requested) && ! $selectedGroup) {
            return redirect()->route('peer.index')
                ->with('error', 'You are not a member of the selected group.');
        }

        $selectedGroupId = $selectedGroup->id ?? null;
        $columns = collect();
        $reflectionFields = collect();
        $members = collect();
        $answers = ['scores' => [], 'remarks' => [], 'reflections' => []];
        $closedReason = null;
        $allowsRemarks = false;

        if ($selectedGroup) {
            $closedReason = PeerEvaluationForm::closedReason($selectedGroup);

            $membership = PeerEvaluationForm::membershipOf($selectedGroup->id, $userPk);

            $columns = PeerEvaluationForm::columnsFor($selectedGroup);
            // The remarks toggle only appears when a criterion asks for one.
            // store() gates the same way, so the column can never be shown
            // without somewhere to save it.
            $allowsRemarks = $columns->contains(fn ($column) => (bool) $column->has_remarks);
            $reflectionFields = PeerEvaluationForm::reflectionFieldsFor($selectedGroup);
            // Nobody evaluates themselves, so the evaluator's own row is dropped.
            $members = PeerEvaluationForm::peersFor($selectedGroup, $membership?->id);
            // Submitting twice is an edit (store() uses updateOrInsert), so the
            // form has to reopen showing what was saved - otherwise the second
            // submit would overwrite good answers with the default 0.
            $answers = PeerEvaluationForm::existingAnswers($selectedGroup->id, $userPk);
        }

        return view('admin.forms.peer_evaluation.index', compact(
            'groups',
            'columns',
            'members',
            'selectedGroupId',
            'reflectionFields',
            'selectedGroup',
            'closedReason',
            'allowsRemarks',
            'answers'
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

        $group = DB::table('peer_groups')->where('id', $groupId)->first();

        if (! $group) {
            return back()->with('error', 'That peer group no longer exists.');
        }

        // member_pk is a student_master.pk, and the rest of the row is a copy of
        // that student. This used to insert group_id + member_pk alone, leaving
        // user_id NULL - and user_id is the login handle every OT-facing query
        // matches on (PeerGroupSource::EVALUATOR_JOIN), so a member added here
        // could never open the form or be credited with a score. Same columns
        // PeerGroupSource::syncMembers() writes, so both paths agree.
        $students = DB::table('student_master')
            ->whereIn('pk', $request->member_pks)
            ->get(['pk', 'first_name', 'middle_name', 'last_name', 'generated_OT_code', 'user_id'])
            ->keyBy('pk');

        $courseName = $group->course_id
            ? DB::table('course_master')->where('pk', $group->course_id)->value('course_name')
            : null;
        $eventName = $group->event_id
            ? DB::table('peer_events')->where('id', $group->event_id)->value('event_name')
            : null;

        $skipped = 0;

        foreach ($request->member_pks as $memberPk) {
            $student = $students->get($memberPk);

            if (! $student) {
                $skipped++;
                continue;
            }

            $name = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                $student->first_name, $student->middle_name, $student->last_name,
            ]))));

            DB::table('peer_group_members')->updateOrInsert(
                [
                    'group_id' => $groupId,
                    'member_pk' => $memberPk,
                ],
                [
                    'user_name' => $name ?: 'Unnamed',
                    'ot_code' => $student->generated_OT_code,
                    'user_id' => $student->user_id,
                    'course_name' => $courseName,
                    'event_name' => $eventName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        if ($skipped > 0) {
            return back()->with(
                'error',
                $skipped . ' of the selected people are not in student_master and were not added.'
            );
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
     * "My Groups" - every peer group this OT belongs to, open or not.
     *
     * Rows link straight into peer.index; that is the one OT-facing form, and
     * this screen only decides which group it opens on. Closed groups are still
     * listed, with the reason, rather than silently disappearing.
     */
    public function user_groups()
    {
        $groups = PeerEvaluationForm::groupsFor((int) auth()->user()->pk)
            ->map(function ($group) {
                $group->closed_reason = PeerEvaluationForm::closedReason($group);

                return $group;
            });

        return view('admin.forms.peer_evaluation.user_groups', compact('groups'));
    }

    /**
     * Legacy deep link: /peer/evaluate/{group}.
     *
     * The view this rendered (peer_evaluation.user_evaluation) has never existed
     * in the repo, so every hit on this route was a 500. There is one OT-facing
     * form and it is peer.index, so send the caller there instead of building a
     * second half of the same screen. The route stays so old links keep working.
     */
    public function user_evaluation($groupId)
    {
        return redirect()->route('peer.index', ['group_id' => (int) $groupId]);
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
            // print_r($groups); exit;

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
            // print_r($members);
            

        // Whatever the OTs were actually scored on. This used to scope on
        // course/event only, so a column belonging to a DIFFERENT group of the
        // same course showed up as an empty extra column on this grid.
        // PeerEvaluationForm is the same reader the form itself renders from.
        $columns = PeerEvaluationForm::columnsFor($currentGroup);


        $scores = DB::table('peer_scores')
            ->leftJoin('user_credentials', 'peer_scores.evaluator_id', '=', 'user_credentials.pk')
            ->where('peer_scores.group_id', $groupId)
            ->select(
                'peer_scores.*',
                'user_credentials.first_name as evaluator_first_name',
                'user_credentials.last_name as evaluator_last_name'
            )
            ->get();
            // print_r($scores); exit;
                

        // Same scoping as the columns above, for the same reason.
        $reflectionFields = PeerEvaluationForm::reflectionFieldsFor($currentGroup);


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
        $group = DB::table('peer_groups')->where('id', $groupId)->first();

        if (! $group) {
            return back()->with('error', 'That peer group no longer exists.');
        }

        // The format arrives as a query string, so it can be absent or anything
        // at all. It used to be passed straight through to the download
        // filename: no format meant a file called "..._submissions." and
        // PhpSpreadsheet blew up with "No ReaderType or WriterType could be
        // detected". Only the two the screen offers are accepted.
        $format = strtolower((string) $request->input('format'));

        if (! in_array($format, ['xlsx', 'csv', 'pdf'], true)) {
            return back()->with('error', 'Pick Excel or CSV before exporting.');
        }

        $members = DB::table('peer_group_members')
            ->leftJoin('user_credentials', function ($join) {
                $join->whereRaw(\App\Support\PeerGroupSource::EVALUATOR_JOIN);
            })
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

        // Was DB::table('peer_columns')->get(): every column in the system,
        // including other courses'. The export has to show the same criteria the
        // OTs were actually scored on, which is what the form itself renders.
        $columns = PeerEvaluationForm::columnsFor($group);

        $scores = DB::table('peer_scores')
            ->leftJoin('user_credentials', 'peer_scores.evaluator_id', '=', 'user_credentials.pk')
            ->where('peer_scores.group_id', $groupId)
            ->select(
                'peer_scores.*',
                'user_credentials.first_name as evaluator_first_name',
                'user_credentials.last_name as evaluator_last_name'
            )
            ->get();

        // Same reason as the columns above: scoped to this group, not every
        // active field in the system.
        $reflectionFields = PeerEvaluationForm::reflectionFieldsFor($group);

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
