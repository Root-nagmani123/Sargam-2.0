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

    /**
     * The menus that link to this screen (menus 17 "Notice Notifications" and 198
     * "Notice"). Holding either, or being Super Admin, makes a notice author.
     */
    public const AUTHOR_PERMISSIONS = ['admin_notice', 'notice_sidebar'];

    public function __construct()
    {
        // Every action here is authoring: the list with its edit / status / delete
        // controls, the forms, the writes, and the staff / OT directory lookups that
        // feed the audience picker. Until PR #334 F-003 / F-013 they carried only
        // `auth`, so any login (an OT included) could publish a notice into every
        // dashboard and read the staff and trainee directory. Reading notices is the
        // dashboard feed, which does not route through this controller.
        $this->middleware(function ($request, $next) {
            if (! canAuthorNotices()) {
                abort(403, 'You are not authorised to manage notices.');
            }

            return $next($request);
        });
    }

    /**
     * The notices this author may list, open, edit and delete: their own, or every
     * notice for Super Admin. Holding the authoring menu used to mean administering
     * every author's notices, Personal ones addressed to one named person included,
     * which the dashboard feed would never show them (PR #334 F-039). Another
     * author's notice is a 404, so its existence is not disclosed either.
     */
    private function manageableNotices()
    {
        $query = Notice::query();

        if (! isSidebarPrivilegedUser()) {
            $query->where('created_by', Auth::id());
        }

        return $query;
    }

    // Notice List Page
    public function index(Request $request)
    {
        $types = self::TYPES;
        $query = $this->manageableNotices()
            ->with(['user', 'courses', 'departments', 'groupTypeMaps.courseGroupType'])
            ->withCount([
                'audienceMaps as individual_count' => function ($q) {
                    $q->whereIn('audience_type', [NoticeAudienceMap::TYPE_STUDENT, NoticeAudienceMap::TYPE_EMPLOYEE]);
                },
            ])
            ->orderBy('pk', 'DESC');

        // 🔍 Filters
        if ($request->notice_type) {
            $query->where('notice_type', $request->notice_type);
        }

        // Course and Department are both multi-valued now, so both filters ask the
        // audience map rather than the scalar column.
        //
        // The ALL_TARGETS sentinel finds the notices the list renders as
        // "All courses" / "All departments" — those carry no audience rows at
        // all, so no ordinary value could ever match them.
        // Scalar only, like year below: ?course_id[]= / ?department_id[]= reached
        // whereTargets()'s string cast and returned 500 (PR #334 F-025).
        $courseId = $request->input('course_id');
        if (is_scalar($courseId) && trim((string) $courseId) !== '') {
            $this->whereTargets($query, NoticeAudienceMap::TYPE_COURSE, $courseId, 'Office trainee');
        }

        if ($request->status != "") {
            $query->where('active_inactive', $request->status);
        }

        // Strict match: picking a department shows the notices addressed to that
        // department, not also every "all departments" notice. A filter that
        // widens its own result set reads as broken.
        $departmentId = $request->input('department_id');
        if (is_scalar($departmentId) && trim((string) $departmentId) !== '') {
            $this->whereTargets($query, NoticeAudienceMap::TYPE_DEPARTMENT, $departmentId, 'Staff/Faculty');
        }

        // Which date the year applies to is the user's choice. "Year" sitting
        // beside Created / Display / Expiry columns was a guess, and naming one
        // of them in the label still left the other two unreachable.
        $yearField = $this->yearColumn($request->input('year_field'));

        // Scalar only: ?year[]= reached whereYear() and the view's string cast
        // and returned 500 (PR #334 F-025).
        $year = $request->input('year');
        if (is_scalar($year) && trim((string) $year) !== '') {
            $query->whereYear($yearField, $year);
        }

        // 🔍 Free-text search across title, type, course name and creator name
        $search = is_scalar($request->input('search')) ? trim((string) $request->input('search')) : '';
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('notice_title', 'like', $like)
                    ->orWhere('notice_type', 'like', $like)
                    ->orWhereHas('courses', function ($c) use ($like) {
                        $c->where('course_name', 'like', $like);
                    })
                    ->orWhereHas('departments', function ($d) use ($like) {
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

        // Only the years that actually carry notices on the chosen date column —
        // an open-ended range would list years the filter can never match.
        $years = $this->manageableNotices()->selectRaw("DISTINCT YEAR({$yearField}) as year")
            ->whereNotNull($yearField)
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->values();

        $yearFields = self::YEAR_FIELDS;
        $allTargets = self::ALL_TARGETS;

        return view('admin.NoticeNotification.index', compact(
            'notices',
            'courses',
            'types',
            'departments',
            'years',
            'yearFields',
            'allTargets'
        ));
    }

    /**
     * Whitelist for the "Year of" selector. Keys are what the form posts; values
     * are interpolated into SQL, so they may only ever come from this map.
     */
    private const YEAR_FIELDS = [
        'display' => 'display_date',
        'created' => 'created_at',
        'expiry'  => 'expiry_date',
    ];

    /** Filter value meaning "addressed to every course / department". */
    public const ALL_TARGETS = '__all__';

    private function yearColumn($key): string
    {
        // An array key (?year_field[]=) is an "Illegal offset type" TypeError.
        return is_string($key) && isset(self::YEAR_FIELDS[$key])
            ? self::YEAR_FIELDS[$key]
            : self::YEAR_FIELDS['display'];
    }

    /**
     * Narrow to notices addressed to $referencePk — or, for the ALL_TARGETS
     * sentinel, to those addressed to every course / department, which is stored
     * as the absence of rows of that type.
     */
    private function whereTargets($query, string $type, $referencePk, string $audience): void
    {
        if ((string) $referencePk === self::ALL_TARGETS) {
            $query->where('target_audience', 'like', '%' . $audience . '%')
                ->whereDoesntHave('audienceMaps', function ($q) use ($type) {
                    $q->where('audience_type', $type);
                });

            return;
        }

        $query->whereHas('audienceMaps', function ($q) use ($type, $referencePk) {
            $q->where('audience_type', $type)->where('reference_pk', $referencePk);
        });
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
        $data['description'] = notice_safe_html($data['description'] ?? '');
        $data = array_merge($data, $this->audienceColumns($request));

        if ($request->hasFile('document')) {
            $data['document'] = $request->file('document')
                ->store('notice_docs', 'public');
        }

        // One transaction: a notice committed without its audience rows reads as
        // "every course / every department" in the feed, so a failed audience
        // insert must take the notice down with it.
        DB::transaction(function () use ($data, $request) {
            $notice = Notice::create($data);
            $this->syncAudience($notice, $request);
        });

        return redirect()
            ->route('admin.notice.index')
            ->with('success', 'Notice created successfully!');
    }


    // Edit Page
    public function edit($encId)
    {
        $id = Crypt::decrypt($encId);
        $notice = $this->manageableNotices()->with('audienceMaps')->findOrFail($id);

        $types = self::TYPES;
        $target = self::TARGETS;

        // Saved selections, so the form can re-check them once the AJAX lists
        // come back. Cast to string: the form posts strings, and the JS compares
        // option values as strings.
        $selected = $notice->audienceMaps
            ->groupBy('audience_type')
            ->map(function ($rows) {
                return $rows->pluck('reference_pk')->map(fn ($pk) => (string) $pk)->values()->all();
            });

        $selectedCourses = $selected[NoticeAudienceMap::TYPE_COURSE] ?? [];
        $selectedGroups = $selected[NoticeAudienceMap::TYPE_GROUP] ?? [];
        $selectedDepartments = $selected[NoticeAudienceMap::TYPE_DEPARTMENT] ?? [];
        $selectedStudents = $selected[NoticeAudienceMap::TYPE_STUDENT] ?? [];
        $selectedEmployees = $selected[NoticeAudienceMap::TYPE_EMPLOYEE] ?? [];

        // Active departments, plus any this notice is already pinned to even if
        // since deactivated: an unlisted pick is not re-posted, and a notice with
        // no department left saves as "every department" (PR #334 F-020). Courses
        // get the same treatment through getCourses()'s include list.
        $departments = DepartmentMaster::query()
            ->where(function ($q) use ($selectedDepartments) {
                $q->where('active_inactive', 1)
                    ->orWhereIn('pk', array_map('intval', $selectedDepartments));
            })
            ->select('pk', 'department_name', 'active_inactive')
            ->orderBy('department_name')
            ->get();

        // A pre-targeting OT notice with no course reaches nobody; the form must say
        // so, and offer an explicit "every course" choice rather than read an empty
        // course list as one (PR #334 F-032).
        $legacyCourseless = $notice->audience_mode === null
            && $notice->isOfficerTraineeAudience()
            && $selectedCourses === [];

        return view('admin.NoticeNotification.edit', compact(
            'legacyCourseless',
            'notice',
            'types',
            'target',
            'encId',
            'departments',
            'selectedCourses',
            'selectedGroups',
            'selectedDepartments',
            'selectedStudents',
            'selectedEmployees'
        ));
    }

    // Update
    public function update(Request $request, $encId)
    {
        $this->validateNotice($request);

        $id = Crypt::decrypt($encId);
        $notice = $this->manageableNotices()->findOrFail($id);

        $data = $request->only([
            'notice_title',
            'description',
            'notice_type',
            'display_date',
            'expiry_date',
            'target_audience',
        ]);
        $data['description'] = notice_safe_html($data['description'] ?? '');

        if ($request->hasFile('document')) {
            $data['document'] = $request->file('document')->store('notice_docs', 'public');
        }

        if ($this->keepsLegacyCourselessAudience($notice, $request)) {
            // Audience untouched: no audience columns written, no rows re-synced.
            $notice->update($data);

            return redirect()->route('admin.notice.index')->with('success', 'Notice updated!');
        }

        $data = array_merge($data, $this->audienceColumns($request));

        // syncAudience() deletes every audience row before re-inserting; outside a
        // transaction a failed insert would leave the notice addressed to everyone.
        DB::transaction(function () use ($notice, $data, $request) {
            $notice->update($data);
            $this->syncAudience($notice, $request);
        });

        return redirect()->route('admin.notice.index')->with('success', 'Notice updated!');
    }


    // Delete
    public function destroy($encId)
    {
        $id = Crypt::decrypt($encId);
        $data = $this->manageableNotices()->findOrFail($id);
        if ($data->active_inactive == 0) {
            // One unit: a failure between the two must not leave the notice with no
            // audience rows, which the feed reads as "everyone" (PR #334 F-065).
            DB::transaction(function () use ($data) {
                $data->audienceMaps()->delete();
                $data->delete();
            });
            return back()->with('success', 'Notice deleted!');
        } else {
            return back()->with('error', 'Active Notice cannot be deleted!');
        }
    }

    /* ------------------------------------------------------------------
     | Target-audience cascade
     * ----------------------------------------------------------------- */

    /**
     * Posted ids for one audience field, cleaned to a list of non-negative ints.
     *
     * 0 is a real id: department_master holds pk 0 (NIAR). Dropping it as falsy
     * left a NIAR-only notice with no D rows, which the feed reads as "every
     * department". Non-numeric input (including the '__all__' filter sentinel,
     * which intval() would turn into 0) is discarded before the cast instead.
     */
    private function idsFrom(Request $request, string $field): array
    {
        $ids = $request->input($field, []);

        if (! is_array($ids)) {
            $ids = $ids === null || $ids === '' ? [] : [$ids];
        }

        $numeric = array_filter($ids, fn ($id) => is_int($id) || (is_string($id) && ctype_digit(trim($id))));

        return array_values(array_unique(array_map('intval', $numeric)));
    }

    /**
     * Shared rules for store + update.
     *
     * Each field is only required once the selection above it makes it visible,
     * which mirrors what the form shows. Messages are deliberately one per
     * field: the array rules name `.*` elements too, and without these the
     * form reported the same problem once per selected row.
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

        // Read before validation runs, so an array (target_audience[]=) must not reach
        // a string cast; it is left to the 'string' rule above to reject (PR #334 F-041).
        $target = is_string($request->input('target_audience')) ? $request->input('target_audience') : '';

        if ($this->isOfficerTrainee($target)) {
            // Empty = "Select All" courses, so the courses themselves stay optional.
            $rules['course_master_pks']   = 'nullable|array';
            $rules['course_master_pks.*'] = 'integer|exists:course_master,pk';
            $messages['course_master_pks.*.exists'] = 'One of the selected courses does not exist.';

            if ($this->idsFrom($request, 'course_master_pks')) {
                $scope = is_string($request->input('ot_scope')) ? $request->input('ot_scope') : '';

                $rules['ot_scope'] = ['required', Rule::in([Notice::MODE_ALL, Notice::MODE_GROUP, Notice::MODE_INDIVIDUAL])];
                $messages['ot_scope.required'] = 'Please choose All, Group or Individual.';
                $messages['ot_scope.in'] = 'Please choose All, Group or Individual.';

                if ($scope === Notice::MODE_GROUP) {
                    $rules['group_type_map_pks']   = 'required|array|min:1';
                    $rules['group_type_map_pks.*'] = 'integer|exists:group_type_master_course_master_map,pk';
                    $messages['group_type_map_pks.required'] = 'Please select at least one group.';
                    $messages['group_type_map_pks.*.exists'] = 'One of the selected groups does not exist.';
                } elseif ($scope === Notice::MODE_INDIVIDUAL) {
                    $rules['student_pks']   = 'required|array|min:1';
                    $rules['student_pks.*'] = 'integer|exists:student_master,pk';
                    $messages['student_pks.required'] = 'Please select at least one Officer Trainee.';
                    $messages['student_pks.*.exists'] = 'One of the selected Officer Trainees does not exist.';
                }
            }
        } elseif ($this->isStaffFaculty($target)) {
            $rules['department_master_pks']   = 'nullable|array';
            $rules['department_master_pks.*'] = 'integer|exists:department_master,pk';
            $messages['department_master_pks.*.exists'] = 'One of the selected departments does not exist.';

            if ($this->idsFrom($request, 'department_master_pks')) {
                $rules['staff_scope'] = ['required', Rule::in([Notice::MODE_ALL, Notice::MODE_INDIVIDUAL])];
                $messages['staff_scope.required'] = 'Please choose All or Individual.';
                $messages['staff_scope.in'] = 'Please choose All or Individual.';

                if ($request->input('staff_scope') === Notice::MODE_INDIVIDUAL) {
                    $rules['employee_pks']   = 'required|array|min:1';
                    $rules['employee_pks.*'] = 'integer|exists:employee_master,pk';
                    $messages['employee_pks.required'] = 'Please select at least one staff / faculty member.';
                    $messages['employee_pks.*.exists'] = 'One of the selected staff members does not exist.';
                }
            }
        }

        $validator = validator($request->all(), $rules, $messages);

        if ($validator->fails()) {
            // Collapse duplicates before throwing. A `.*` rule fails once per bad
            // row and Laravel keys each one separately — student_pks.0,
            // student_pks.1, ... — so three bad ids produced the same sentence
            // three times. Fold the indexed keys back onto their base field and
            // keep one copy of each distinct message.
            $unique = [];

            foreach ($validator->errors()->toArray() as $field => $fieldMessages) {
                $base = preg_replace('/\.\d+$/', '', $field);

                foreach ($fieldMessages as $message) {
                    if (! in_array($message, $unique[$base] ?? [], true)) {
                        $unique[$base][] = $message;
                    }
                }
            }

            throw \Illuminate\Validation\ValidationException::withMessages($unique);
        }
    }

    /**
     * Form fields -> the scalar audience columns on the notice row.
     *
     * These mirror the single-selection case only; notice_audience_map is what
     * this module reads back. They are still written so anything outside this
     * module that joins on course_master_pk keeps working for the common case.
     */
    private function audienceColumns(Request $request): array
    {
        $columns = [
            'course_master_pk'     => null,
            'department_master_pk' => null,
            'group_type_map_pk'    => null,
            'audience_mode'        => Notice::MODE_ALL,
        ];

        $target = is_string($request->input('target_audience')) ? $request->input('target_audience') : '';

        if ($this->isOfficerTrainee($target)) {
            $courses = $this->idsFrom($request, 'course_master_pks');
            $columns['course_master_pk'] = count($courses) === 1 ? $courses[0] : null;

            if ($courses) {
                $scope = is_string($request->input('ot_scope')) ? $request->input('ot_scope') : Notice::MODE_ALL;

                if ($scope === Notice::MODE_INDIVIDUAL) {
                    $columns['audience_mode'] = Notice::MODE_INDIVIDUAL;
                } elseif ($scope === Notice::MODE_GROUP) {
                    $groups = $this->idsFrom($request, 'group_type_map_pks');
                    $columns['audience_mode'] = Notice::MODE_GROUP;
                    $columns['group_type_map_pk'] = count($groups) === 1 ? $groups[0] : null;
                }
            }
        } elseif ($this->isStaffFaculty($target)) {
            $departments = $this->idsFrom($request, 'department_master_pks');
            $columns['department_master_pk'] = count($departments) === 1 ? $departments[0] : null;

            if ($departments && $request->input('staff_scope') === Notice::MODE_INDIVIDUAL) {
                $columns['audience_mode'] = Notice::MODE_INDIVIDUAL;
            }
        }

        return $columns;
    }

    /**
     * Replace every audience row for this notice.
     *
     * Wiped first: an edit that moves a notice from one audience to another must
     * not leave the old rows behind, because the feed reads them as the notice's
     * whole audience.
     */
    private function syncAudience(Notice $notice, Request $request): void
    {
        $notice->audienceMaps()->delete();

        $target = (string) $notice->target_audience;
        $rows = [];

        $add = function (string $type, array $ids) use (&$rows, $notice) {
            foreach ($ids as $id) {
                $rows[] = [
                    'notices_notification_pk' => $notice->pk,
                    'audience_type'           => $type,
                    'reference_pk'            => $id,
                    'active_inactive'         => 1,
                ];
            }
        };

        if ($this->isOfficerTrainee($target)) {
            $courses = $this->idsFrom($request, 'course_master_pks');
            $add(NoticeAudienceMap::TYPE_COURSE, $courses);

            if ($courses) {
                if ($notice->audience_mode === Notice::MODE_GROUP) {
                    $add(NoticeAudienceMap::TYPE_GROUP, $this->idsFrom($request, 'group_type_map_pks'));
                } elseif ($notice->audience_mode === Notice::MODE_INDIVIDUAL) {
                    $add(NoticeAudienceMap::TYPE_STUDENT, $this->idsFrom($request, 'student_pks'));
                }
            }
        } elseif ($this->isStaffFaculty($target)) {
            $departments = $this->idsFrom($request, 'department_master_pks');
            $add(NoticeAudienceMap::TYPE_DEPARTMENT, $departments);

            if ($departments && $notice->audience_mode === Notice::MODE_INDIVIDUAL) {
                $add(NoticeAudienceMap::TYPE_EMPLOYEE, $this->idsFrom($request, 'employee_pks'));
            }
        }

        if ($rows) {
            NoticeAudienceMap::insert($rows);
        }
    }

    /**
     * A notice saved before audience targeting (audience_mode NULL) for Officer
     * Trainees with no course row reaches no trainee (PR #334 F-046). The edit form
     * shows its course list empty, which on a targeted notice means "every course",
     * so saving it through the ordinary path — even a title-only edit — wrote
     * audience_mode 'all' and published it to every OT (F-032).
     *
     * Such a notice keeps its audience exactly as stored unless the author picks
     * courses, or ticks the explicit "every Officer Trainee in every course" box the
     * edit form offers for this case only. Changing the target audience also takes
     * the ordinary path.
     */
    private function keepsLegacyCourselessAudience(Notice $notice, Request $request): bool
    {
        $target = is_string($request->input('target_audience')) ? $request->input('target_audience') : '';

        return $notice->audience_mode === null
            && $notice->isOfficerTraineeAudience()
            && ! $notice->audienceMaps()->where('audience_type', NoticeAudienceMap::TYPE_COURSE)->exists()
            && $this->isOfficerTrainee($target)
            && $this->idsFrom($request, 'course_master_pks') === []
            && ! $request->boolean('all_courses_confirmed');
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
     * Courses available to target: the running ones, plus any `include` ids.
     *
     * The edit form passes the notice's saved courses as `include` — most saved
     * courses have already ended and would otherwise be missing from the list,
     * which would silently drop them from the selection the next time the notice
     * was saved.
     */
    public function getCourses(Request $request)
    {
        $include = $this->idsFrom($request, 'include');

        $courses = CourseMaster::where(function ($q) use ($include) {
            $q->where(function ($live) {
                $live->where('active_inactive', 1)
                    ->where('end_date', '>=', date('Y-m-d'));
            });

            if ($include) {
                $q->orWhereIn('pk', $include);
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
     * Group types mapped to any of the given courses.
     *
     * group_type_master_course_master_map.course_name holds course_master.pk and
     * .type_name holds course_group_type_master.pk — the column names do not
     * describe their contents, which is why this joins rather than reads names.
     *
     * With several courses selected the group label is prefixed with the course
     * name, because "Lecture Group - A" exists in more than one course and the
     * bare label would be ambiguous.
     */
    public function getGroupTypes(Request $request)
    {
        $request->validate([
            'course_master_pks'   => 'required|array|min:1',
            'course_master_pks.*' => 'integer|exists:course_master,pk',
        ]);

        $courseIds = $this->idsFrom($request, 'course_master_pks');
        $multiCourse = count($courseIds) > 1;

        $groups = DB::table('group_type_master_course_master_map as gmap')
            ->join('course_group_type_master as cgt', 'cgt.pk', '=', 'gmap.type_name')
            ->leftJoin('course_master as cm', 'cm.pk', '=', 'gmap.course_name')
            ->whereIn('gmap.course_name', $courseIds)
            ->where('gmap.active_inactive', 1)
            ->where('cgt.active_inactive', 1)
            ->orderBy('cm.course_name')
            ->orderBy('cgt.type_name')
            ->orderBy('gmap.group_name')
            ->get([
                'gmap.pk',
                'gmap.group_name',
                'cgt.type_name as group_type_name',
                'cm.course_name',
            ]);

        $data = $groups->map(function ($g) use ($multiCourse) {
            $label = trim($g->group_type_name . ' - ' . $g->group_name, ' -');

            return [
                'pk'    => $g->pk,
                'label' => $multiCourse && $g->course_name ? $g->course_name . ' · ' . $label : $label,
            ];
        });

        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * Officer Trainees across the given courses, optionally narrowed to groups.
     *
     * The OT code falls back from the course-specific code in course_wise_ot_list
     * to student_master.generated_OT_code: course_wise_ot_list is only populated
     * for courses that have been through the OT-code import, so for every other
     * course the list rendered with no codes at all.
     */
    public function getStudents(Request $request)
    {
        $request->validate([
            'course_master_pks'    => 'required|array|min:1',
            'course_master_pks.*'  => 'integer|exists:course_master,pk',
            'group_type_map_pks'   => 'nullable|array',
            'group_type_map_pks.*' => 'integer|exists:group_type_master_course_master_map,pk',
        ]);

        $courseIds = $this->idsFrom($request, 'course_master_pks');
        $groupIds = $this->idsFrom($request, 'group_type_map_pks');

        $query = DB::table('student_master_course__map as scm')
            ->join('student_master as sm', 'sm.pk', '=', 'scm.student_master_pk')
            ->leftJoin('course_wise_ot_list as ot', function ($join) use ($courseIds) {
                $join->on('ot.student_master_pk', '=', 'sm.pk')
                    ->whereIn('ot.course_master_pk', $courseIds);
            })
            ->whereIn('scm.course_master_pk', $courseIds)
            ->where('sm.status', 1);

        if ($groupIds) {
            $query->whereExists(function ($sub) use ($groupIds) {
                $sub->select(DB::raw(1))
                    ->from('student_course_group_map as scg')
                    ->whereColumn('scg.student_master_pk', 'sm.pk')
                    ->whereIn('scg.group_type_master_course_master_map_pk', $groupIds)
                    ->where('scg.active_inactive', 1);
            });
        }

        $students = $query->distinct()
            ->orderBy('sm.first_name')
            ->get([
                'sm.pk',
                'sm.first_name',
                'sm.middle_name',
                'sm.last_name',
                'sm.generated_OT_code as student_ot_code',
                'ot.generated_ot_code as course_ot_code',
            ]);

        $data = $students->map(function ($s) {
            $name = trim(preg_replace('/\s+/', ' ', $s->first_name . ' ' . $s->middle_name . ' ' . $s->last_name));
            $code = $s->course_ot_code ?: ($s->student_ot_code ?: null);

            return [
                'pk'      => $s->pk,
                'name'    => $name,
                'ot_code' => $code,
                'label'   => $code ? $code . ' - ' . $name : $name,
            ];
        })->sortBy(function ($row) {
            // OT code first when there is one, so the list reads in code order
            // and the codeless stragglers fall to the bottom rather than
            // interleaving by first name.
            return ($row['ot_code'] ? '0' : '1') . ($row['ot_code'] ?: $row['name']);
        })->values();

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
        $request->validate([
            'department_master_pks'   => 'required|array|min:1',
            'department_master_pks.*' => 'integer|exists:department_master,pk',
        ]);

        $departmentIds = $this->idsFrom($request, 'department_master_pks');
        $multiDepartment = count($departmentIds) > 1;

        $employees = DB::table('employee_master as em')
            ->leftJoin('designation_master as dm', 'dm.pk', '=', 'em.designation_master_pk')
            ->leftJoin('department_master as dep', 'dep.pk', '=', 'em.department_master_pk')
            ->whereIn('em.department_master_pk', $departmentIds)
            ->where('em.status', 1)
            ->orderBy('em.first_name')
            ->get([
                'em.pk',
                'em.first_name',
                'em.middle_name',
                'em.last_name',
                'em.emp_id',
                'dm.designation_name',
                'dep.department_name',
            ]);

        $data = $employees->map(function ($e) use ($multiDepartment) {
            $name = trim(preg_replace('/\s+/', ' ', $e->first_name . ' ' . $e->middle_name . ' ' . $e->last_name));
            $suffix = array_filter([
                $e->emp_id ?: null,
                $e->designation_name ?? null,
                $multiDepartment ? ($e->department_name ?? null) : null,
            ]);

            return [
                'pk'    => $e->pk,
                'name'  => $name,
                'label' => $suffix ? $name . ' (' . implode(' | ', $suffix) . ')' : $name,
            ];
        });

        return response()->json(['status' => true, 'data' => $data]);
    }
}
