<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\NoticeNotification as Notice;
use App\Models\NoticeAudienceMap;
use App\Models\CourseMaster;
use App\Models\DepartmentMaster;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Auth;

class NoticeNotificationController extends Controller
{
    private const TYPES = ['Course notice', 'Office order', 'Personal', 'Office notice', 'Service related'];
    private const TARGETS = ['Office trainee', 'Staff/Faculty', 'All'];

    // Notice List Page
    public function index(Request $request)
    {
        $types = self::TYPES;
        $query = Notice::with(['course', 'user', 'department', 'groupTypeMap.courseGroupType'])
            ->withCount('audienceMaps')
            ->orderBy('pk', 'DESC');

        // 🔍 Filters
        if ($request->notice_type) {
            $query->where('notice_type', $request->notice_type);
        }

        if ($request->course_id) {
            $query->where('course_master_pk', $request->course_id);
        }

        if ($request->status != "") {
            $query->where('active_inactive', $request->status);
        }

        // Department: an "all departments" Staff/Faculty notice reaches this
        // department too, so it stays in the result set alongside the ones
        // pinned to it.
        if ($request->filled('department_id')) {
            $departmentId = $request->input('department_id');
            $query->where(function ($q) use ($departmentId) {
                $q->where('department_master_pk', $departmentId)
                    ->orWhere(function ($w) {
                        $w->whereNull('department_master_pk')
                            ->where('target_audience', 'like', '%Staff/Faculty%');
                    });
            });
        }

        // Year of the notice itself — display_date is what the feed sorts on.
        if ($request->filled('year')) {
            $query->whereYear('display_date', $request->input('year'));
        }

        // 🔍 Free-text search across title, type, course name and creator name
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('notice_title', 'like', $like)
                    ->orWhere('notice_type', 'like', $like)
                    ->orWhereHas('course', function ($c) use ($like) {
                        $c->where('course_name', 'like', $like);
                    })
                    ->orWhereHas('department', function ($d) use ($like) {
                        $d->where('department_name', 'like', $like);
                    })
                    ->orWhereHas('user', function ($u) use ($like) {
                        $u->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", [$like]);
                    });
            });
        }

        // Pagination with filters
        $notices = $query->paginate(10)->appends($request->all());

        // Courses dropdown
        $courses = CourseMaster::select('pk', 'course_name')->where('active_inactive', 1)->where('end_date', '>=', now()->toDateString())->get();

        $departments = DepartmentMaster::active()
            ->select('pk', 'department_name')
            ->orderBy('department_name')
            ->get();

        // Only the years that actually carry notices — an open-ended range would
        // list years the filter can never match.
        $years = Notice::selectRaw('DISTINCT YEAR(display_date) as year')
            ->whereNotNull('display_date')
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->values();

        return view('admin.NoticeNotification.index', compact('notices', 'courses', 'types', 'departments', 'years'));
    }


    // Create Page
    public function create()
    {
        $types = self::TYPES;
        $target = self::TARGETS;
        $departments = DepartmentMaster::active()
            ->select('pk', 'department_name')
            ->orderBy('department_name')
            ->get();

        return view('admin.NoticeNotification.create', compact('types', 'target', 'departments'));
    }

    // Insert
    public function store(Request $request)
    {
        $this->validateNotice($request);

        $data = $request->only([
            'notice_title',
            'description',
            'notice_type',
            'display_date',
            'expiry_date',
            'target_audience',
        ]);
        $data['created_by'] = Auth::id();
        $data = array_merge($data, $this->audienceColumns($request));

        if ($request->hasFile('document')) {
            $data['document'] = $request->file('document')
                ->store('notice_docs', 'public');
        }

        $notice = Notice::create($data);
        $this->syncIndividualAudience($notice, $request);

        return redirect()
            ->route('admin.notice.index')
            ->with('success', 'Notice created successfully!');
    }


    // Edit Page
    public function edit($encId)
    {
        $id = Crypt::decrypt($encId);
        $notice = Notice::with('groupTypeMap')->findOrFail($id);

        $types = self::TYPES;
        $target = self::TARGETS;
        $departments = DepartmentMaster::active()
            ->select('pk', 'department_name')
            ->orderBy('department_name')
            ->get();

        // Pre-selected individual recipients, so the form can re-check them once
        // the AJAX list for the saved course / department comes back.
        $selectedStudents = $notice->audienceMaps()
            ->where('audience_type', NoticeAudienceMap::TYPE_STUDENT)
            ->pluck('reference_pk')
            ->map(function ($pk) {
                return (string) $pk;
            })
            ->values();

        $selectedEmployees = $notice->audienceMaps()
            ->where('audience_type', NoticeAudienceMap::TYPE_EMPLOYEE)
            ->pluck('reference_pk')
            ->map(function ($pk) {
                return (string) $pk;
            })
            ->values();

        return view('admin.NoticeNotification.edit', compact(
            'notice',
            'types',
            'target',
            'encId',
            'departments',
            'selectedStudents',
            'selectedEmployees'
        ));
    }

    // Update
    public function update(Request $request, $encId)
    {
        $this->validateNotice($request);

        $id = Crypt::decrypt($encId);
        $notice = Notice::findOrFail($id);

        $data = $request->only([
            'notice_title',
            'description',
            'notice_type',
            'display_date',
            'expiry_date',
            'target_audience',
        ]);
        $data = array_merge($data, $this->audienceColumns($request));

        if ($request->hasFile('document')) {
            $data['document'] = $request->file('document')->store('notice_docs', 'public');
        }

        $notice->update($data);
        $this->syncIndividualAudience($notice, $request);

        return redirect()->route('admin.notice.index')->with('success', 'Notice updated!');
    }


    // Delete
    public function destroy($encId)
    {
        $id = Crypt::decrypt($encId);
        $data = Notice::findOrFail($id);
        if ($data->active_inactive == 0) {
            $data->audienceMaps()->delete();
            $data->delete();
            return back()->with('success', 'Notice deleted!');
        } else {
            return back()->with('error', 'Active Notice cannot be deleted!');
        }
    }

    /* ------------------------------------------------------------------
     | Target-audience cascade
     * ----------------------------------------------------------------- */

    /**
     * Shared rules for store + update.
     *
     * The audience half is conditional: a field is only required once the
     * selection above it makes it visible, which mirrors what the form shows.
     */
    private function validateNotice(Request $request): void
    {
        $rules = [
            'notice_title'    => 'required|string|max:255',
            'description'     => 'required|string',
            'notice_type'     => 'required|string',
            'display_date'    => 'required|date',
            'expiry_date'     => 'required|date|after_or_equal:display_date',
            'document'        => 'nullable|file|mimetypes:image/jpeg,image/png,application/pdf|max:5048',
            'target_audience' => ['required', 'string', Rule::in(self::TARGETS)],
        ];

        $messages = [
            'notice_title.required'      => 'Please enter notice title.',
            'description.required'       => 'Please enter description.',
            'notice_type.required'       => 'Please select notice type.',
            'display_date.required'      => 'Please select display date.',
            'expiry_date.required'       => 'Please select expiry date.',
            'expiry_date.after_or_equal' => 'Expiry date must be equal or greater than display date.',
            'document.file'              => 'Uploaded file is not valid.',
            'document.mimetypes'         => 'Unsupported file format. Only JPG, PNG and PDF files are allowed.',
            'document.max'               => 'File size must not exceed 5 MB.',
            'target_audience.required'   => 'Please select target audience.',
            'target_audience.in'         => 'Please select a valid target audience.',
        ];

        $target = (string) $request->input('target_audience');

        if ($this->isOfficerTrainee($target)) {
            // Blank = "Select All" courses, so the course itself stays optional.
            $rules['course_master_pk'] = 'nullable|exists:course_master,pk';
            $messages['course_master_pk.exists'] = 'Selected course does not exist.';

            if ($request->filled('course_master_pk')) {
                $selection = (string) $request->input('ot_group_selection');

                if ($selection === '') {
                    $rules['ot_group_selection'] = 'required';
                    $messages['ot_group_selection.required'] = 'Please select a group type.';
                } elseif ($selection === Notice::MODE_INDIVIDUAL) {
                    $rules['student_pks']   = 'required|array|min:1';
                    $rules['student_pks.*'] = 'integer|exists:student_master,pk';
                    $messages['student_pks.required'] = 'Please select at least one Officer Trainee.';
                } elseif ($selection !== Notice::MODE_ALL) {
                    $rules['ot_group_selection'] = 'required|exists:group_type_master_course_master_map,pk';
                    $messages['ot_group_selection.exists'] = 'Selected group type does not exist.';
                }
            }
        } elseif ($this->isStaffFaculty($target)) {
            $rules['department_master_pk'] = 'nullable|exists:department_master,pk';
            $messages['department_master_pk.exists'] = 'Selected department does not exist.';

            if ($request->filled('department_master_pk')) {
                $rules['staff_scope'] = ['required', Rule::in([Notice::MODE_ALL, Notice::MODE_INDIVIDUAL])];
                $messages['staff_scope.required'] = 'Please choose All or Individual.';

                if ($request->input('staff_scope') === Notice::MODE_INDIVIDUAL) {
                    $rules['employee_pks']   = 'required|array|min:1';
                    $rules['employee_pks.*'] = 'integer|exists:employee_master,pk';
                    $messages['employee_pks.required'] = 'Please select at least one staff / faculty member.';
                }
            }
        }

        $request->validate($rules, $messages);
    }

    /**
     * Form fields -> the audience columns on the notice row.
     *
     * Every branch writes all four columns so switching a notice from one
     * audience to another clears the previous branch's values instead of
     * leaving a stale course or department behind.
     */
    private function audienceColumns(Request $request): array
    {
        $columns = [
            'course_master_pk'     => null,
            'department_master_pk' => null,
            'group_type_map_pk'    => null,
            'audience_mode'        => Notice::MODE_ALL,
        ];

        $target = (string) $request->input('target_audience');

        if ($this->isOfficerTrainee($target)) {
            $columns['course_master_pk'] = $request->filled('course_master_pk')
                ? (int) $request->input('course_master_pk')
                : null;

            if ($columns['course_master_pk']) {
                $selection = (string) $request->input('ot_group_selection', Notice::MODE_ALL);

                if ($selection === Notice::MODE_INDIVIDUAL) {
                    $columns['audience_mode'] = Notice::MODE_INDIVIDUAL;
                } elseif ($selection !== '' && $selection !== Notice::MODE_ALL) {
                    $columns['audience_mode'] = Notice::MODE_GROUP;
                    $columns['group_type_map_pk'] = (int) $selection;
                }
            }
        } elseif ($this->isStaffFaculty($target)) {
            $columns['department_master_pk'] = $request->filled('department_master_pk')
                ? (int) $request->input('department_master_pk')
                : null;

            if ($columns['department_master_pk'] && $request->input('staff_scope') === Notice::MODE_INDIVIDUAL) {
                $columns['audience_mode'] = Notice::MODE_INDIVIDUAL;
            }
        }

        return $columns;
    }

    /**
     * Replace the notice's individual-recipient rows.
     *
     * Wiped unconditionally first: an edit that moves a notice off "Individual"
     * must not leave the old picks behind, because the feed reads them whenever
     * audience_mode says individual.
     */
    private function syncIndividualAudience(Notice $notice, Request $request): void
    {
        $notice->audienceMaps()->delete();

        if ($notice->audience_mode !== Notice::MODE_INDIVIDUAL) {
            return;
        }

        $target = (string) $notice->target_audience;

        if ($this->isOfficerTrainee($target)) {
            $type = NoticeAudienceMap::TYPE_STUDENT;
            $ids = (array) $request->input('student_pks', []);
        } elseif ($this->isStaffFaculty($target)) {
            $type = NoticeAudienceMap::TYPE_EMPLOYEE;
            $ids = (array) $request->input('employee_pks', []);
        } else {
            return;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if (empty($ids)) {
            return;
        }

        $rows = [];
        foreach ($ids as $id) {
            $rows[] = [
                'notices_notification_pk' => $notice->pk,
                'audience_type'           => $type,
                'reference_pk'            => $id,
                'active_inactive'         => 1,
            ];
        }

        NoticeAudienceMap::insert($rows);
    }

    private function isOfficerTrainee(string $target): bool
    {
        return stripos($target, 'Office trainee') !== false;
    }

    private function isStaffFaculty(string $target): bool
    {
        return stripos($target, 'Staff/Faculty') !== false;
    }

    /* ------------------------------------------------------------------
     | AJAX sources for the cascade
     * ----------------------------------------------------------------- */

    /**
     * Courses available to target: the running ones, plus `include` if given.
     *
     * The edit form passes the notice's saved course as `include` — most saved
     * courses have already ended and would otherwise be missing from the list,
     * which would silently reset the dropdown to "Select All" and widen the
     * notice's audience the next time it was saved.
     */
    public function getCourses(Request $request)
    {
        $courses = CourseMaster::where(function ($q) use ($request) {
            $q->where(function ($live) {
                $live->where('active_inactive', 1)
                    ->where('end_date', '>=', date('Y-m-d'));
            });

            if ($request->filled('include')) {
                $q->orWhere('pk', $request->input('include'));
            }
        })
            ->orderBy('course_name', 'ASC')
            ->get(['pk', 'course_name']);

        return response()->json([
            'status' => true,
            'data' => $courses
        ]);
    }

    /**
     * Group types mapped to a course.
     *
     * group_type_master_course_master_map.course_name holds course_master.pk and
     * .type_name holds course_group_type_master.pk — the column names do not
     * describe their contents, which is why this joins rather than reads names.
     */
    public function getGroupTypes(Request $request)
    {
        $request->validate(['course_master_pk' => 'required|exists:course_master,pk']);

        $groups = DB::table('group_type_master_course_master_map as gmap')
            ->join('course_group_type_master as cgt', 'cgt.pk', '=', 'gmap.type_name')
            ->where('gmap.course_name', $request->input('course_master_pk'))
            ->where('gmap.active_inactive', 1)
            ->where('cgt.active_inactive', 1)
            ->orderBy('cgt.type_name')
            ->orderBy('gmap.group_name')
            ->get([
                'gmap.pk',
                'gmap.group_name',
                'cgt.type_name as group_type_name',
            ]);

        $data = $groups->map(function ($g) {
            return [
                'pk'    => $g->pk,
                'label' => trim($g->group_type_name . ' - ' . $g->group_name, ' -'),
            ];
        });

        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * Officer Trainees of a course, optionally narrowed to one group.
     *
     * The OT code lives in course_wise_ot_list and is course-specific, so it is
     * joined on both the student and the course.
     */
    public function getStudents(Request $request)
    {
        $request->validate([
            'course_master_pk'  => 'required|exists:course_master,pk',
            'group_type_map_pk' => 'nullable|exists:group_type_master_course_master_map,pk',
        ]);

        $courseId = $request->input('course_master_pk');

        $query = DB::table('student_master_course__map as scm')
            ->join('student_master as sm', 'sm.pk', '=', 'scm.student_master_pk')
            ->leftJoin('course_wise_ot_list as ot', function ($join) use ($courseId) {
                $join->on('ot.student_master_pk', '=', 'sm.pk')
                    ->where('ot.course_master_pk', '=', $courseId);
            })
            ->where('scm.course_master_pk', $courseId)
            ->where('sm.status', 1);

        if ($request->filled('group_type_map_pk')) {
            $query->join('student_course_group_map as scg', function ($join) use ($request) {
                $join->on('scg.student_master_pk', '=', 'sm.pk')
                    ->where('scg.group_type_master_course_master_map_pk', '=', $request->input('group_type_map_pk'))
                    ->where('scg.active_inactive', '=', 1);
            });
        }

        $students = $query->distinct()
            ->orderBy('ot.generated_ot_code')
            ->orderBy('sm.first_name')
            ->get([
                'sm.pk',
                'sm.first_name',
                'sm.middle_name',
                'sm.last_name',
                'ot.generated_ot_code',
            ]);

        $data = $students->map(function ($s) {
            $name = trim(preg_replace('/\s+/', ' ', $s->first_name . ' ' . $s->middle_name . ' ' . $s->last_name));
            $code = $s->generated_ot_code ?: null;

            return [
                'pk'      => $s->pk,
                'name'    => $name,
                'ot_code' => $code,
                'label'   => $code ? $code . ' - ' . $name : $name,
            ];
        });

        return response()->json(['status' => true, 'data' => $data]);
    }

    public function getDepartments()
    {
        $departments = DepartmentMaster::active()
            ->orderBy('department_name')
            ->get(['pk', 'department_name']);

        return response()->json(['status' => true, 'data' => $departments]);
    }

    public function getEmployees(Request $request)
    {
        $request->validate(['department_master_pk' => 'required|exists:department_master,pk']);

        $employees = DB::table('employee_master as em')
            ->leftJoin('designation_master as dm', 'dm.pk', '=', 'em.designation_master_pk')
            ->where('em.department_master_pk', $request->input('department_master_pk'))
            ->where('em.status', 1)
            ->orderBy('em.first_name')
            ->get([
                'em.pk',
                'em.first_name',
                'em.middle_name',
                'em.last_name',
                'em.emp_id',
                'dm.designation_name',
            ]);

        $data = $employees->map(function ($e) {
            $name = trim(preg_replace('/\s+/', ' ', $e->first_name . ' ' . $e->middle_name . ' ' . $e->last_name));
            $suffix = array_filter([$e->emp_id ?: null, $e->designation_name ?? null]);

            return [
                'pk'    => $e->pk,
                'name'  => $name,
                'label' => $suffix ? $name . ' (' . implode(' | ', $suffix) . ')' : $name,
            ];
        });

        return response()->json(['status' => true, 'data' => $data]);
    }
}
