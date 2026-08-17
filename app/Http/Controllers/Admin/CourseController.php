<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ExportsBrandedGrid;
use App\Http\Controllers\Controller;
use App\Models\CoursesMaster;
use App\Models\CourseTeamMaster;
use Illuminate\Http\Request;
use App\Http\Requests\ProgrammeRequest;
use App\Models\{EmployeeMaster, CourseMaster, FacultyMaster, User};
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use App\DataTables\CourseMasterDataTable;
use App\Support\DataTableRedisCache;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Services\CourseService;


class CourseController extends Controller
{
    use ExportsBrandedGrid;

    /** Cache TTL (seconds) for programme course-filter listings. */
    private const COURSE_LIST_CACHE_TTL = 3600; // 1 hour

    /**
     * The listing's export columns — the same six the grid shows (Status is a
     * badge on screen and its label here; the Action cell has no export column),
     * so a download reconciles against the screen it came from.
     *
     * One definition feeds CSV, Excel, PDF and Print; none of them may build
     * their own column list.
     *
     * @return array<string, array{heading:string, class:string, value:callable}>
     */
    private function exportColumnDefs(): array
    {
        $date = fn ($value) => filled($value) ? Carbon::parse($value)->format('d-m-Y') : '-';

        return [
            'sno' => [
                'heading' => 'S. No.',
                'class' => 'col-sno',
                'value' => fn ($row, int $index) => $index + 1,
            ],
            'course_name' => [
                'heading' => 'Course Name',
                'class' => 'col-name',
                'value' => fn ($row) => (string) ($row->course_name ?: '-'),
            ],
            'couse_short_name' => [
                'heading' => 'Short Name',
                'class' => 'col-short',
                'value' => fn ($row) => (string) ($row->couse_short_name ?: '-'),
            ],
            'course_year' => [
                'heading' => 'Course Year',
                'class' => 'col-year',
                'value' => fn ($row) => (string) ($row->course_year ?: '-'),
            ],
            'start_year' => [
                'heading' => 'Start Date',
                'class' => 'col-date',
                'value' => fn ($row) => $date($row->start_year),
            ],
            'end_date' => [
                'heading' => 'End Date',
                'class' => 'col-date',
                'value' => fn ($row) => $date($row->end_date),
            ],
            'status' => [
                'heading' => 'Status',
                'class' => 'col-status',
                'value' => fn ($row) => ((int) $row->active_inactive === 1) ? 'Active' : 'Inactive',
            ],
        ];
    }

    /**
     * Course Master → CSV / Excel / PDF / Print, all four off one query and one
     * column list via {@see ExportsBrandedGrid}.
     *
     * Honours everything the grid is showing: the Active/Archived pill, the
     * Course Name filter, the search box and the Columns modal — and the same
     * role scoping, because the query goes through
     * {@see CourseMasterDataTable::applyListingScope()}.
     */
    public function export(Request $request, string $format = 'csv')
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

        $statusFilter = $request->query('status_filter') === 'archive' ? 'archive' : 'active';
        $courseFilter = trim((string) $request->query('course_filter', ''));
        $search = trim((string) $request->query('q', ''));

        $query = CourseMaster::query();
        CourseMasterDataTable::applyListingScope($query, $statusFilter, $courseFilter ?: null);

        if ($search !== '') {
            // The same columns the grid's own search covers.
            $query->where(function ($sub) use ($search) {
                $sub->where('course_name', 'like', "%{$search}%")
                    ->orWhere('couse_short_name', 'like', "%{$search}%")
                    ->orWhere('course_year', 'like', "%{$search}%");
            });
        }

        $rows = $query->orderBy('pk', 'desc')->get();

        $filterParts = array_filter([
            'Status: ' . ($statusFilter === 'archive' ? 'Archived' : 'Active'),
            $courseFilter !== '' ? 'Course: ' . (CourseMaster::find($courseFilter)->course_name ?? $courseFilter) : null,
            $search !== '' ? 'Search: ' . $search : null,
        ]);

        return $this->brandedGridResponse(
            $format,
            'Course Master',
            'CourseMaster',
            $rows,
            $this->resolveExportColumns($this->exportColumnDefs(), $request),
            implode('  |  ', $filterParts),
            [
                'emptyText' => 'No courses to export',
                'centeredKeys' => ['sno', 'course_year', 'start_year', 'end_date', 'status'],
                'textKeys' => ['course_year'],
                'columnStyles' => '
        .col-sno    { width: 6%;  text-align: center; }
        .col-name   { width: 32%; }
        .col-short  { width: 16%; }
        .col-year   { width: 11%; text-align: center; }
        .col-date   { width: 12%; text-align: center; }
        .col-status { width: 11%; text-align: center; }',
            ]
        );
    }

    public function index(CourseMasterDataTable $dataTable)
    {
        $data_course_id = get_Role_by_course();

        // Default to active courses (matching the default status filter)
        $currentDate = Carbon::now()->format('Y-m-d');
        $epoch = DataTableRedisCache::readListEpoch(CourseMasterDataTable::LISTING_CACHE_EPOCH_KEY);
        $cacheKey = 'programme_index_course_filter_options:v1:' . md5(json_encode([
            'epoch' => $epoch,
            'data_course_id' => $data_course_id,
            'date' => $currentDate,
        ]));

        $courses = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'PROGRAMME_DATATABLE_CACHE_ENABLED',
                'seconds' => 'PROGRAMME_DATATABLE_CACHE_SECONDS',
            ],
            'CourseController@programmeIndexCourseOptions',
            function () use ($data_course_id, $currentDate) {
                $q = CourseMaster::where('end_date', '>=', $currentDate);
                if (! empty($data_course_id)) {
                    $q->whereIn('pk', $data_course_id);
                }

                return $q->orderBy('course_name')->pluck('course_name', 'pk')->toArray();
            },
            self::COURSE_LIST_CACHE_TTL
        );

        return $dataTable->render('admin.programme.index', compact('courses'));
    }

    public function getCoursesByStatus(Request $request)
    {
        $status = $request->input('status', 'active');
        $data_course_id = get_Role_by_course();
        $currentDate = Carbon::now()->format('Y-m-d');
        $epoch = DataTableRedisCache::readListEpoch(CourseMasterDataTable::LISTING_CACHE_EPOCH_KEY);
        $cacheKey = 'programme_get_courses_by_status:v1:' . md5(json_encode([
            'epoch' => $epoch,
            'status' => $status,
            'data_course_id' => $data_course_id,
            'date' => $currentDate,
        ]));

        $payload = DataTableRedisCache::remember(
            $cacheKey,
            [
                'enabled' => 'PROGRAMME_DATATABLE_CACHE_ENABLED',
                'seconds' => 'PROGRAMME_DATATABLE_CACHE_SECONDS',
            ],
            'CourseController@getCoursesByStatus',
            function () use ($status, $currentDate, $data_course_id) {
                if ($status === 'active') {
                    $q = CourseMaster::where('end_date', '>=', $currentDate);
                    if (! empty($data_course_id)) {
                        $q->whereIn('pk', $data_course_id);
                    }
                    $courses = $q->orderBy('course_name')
                        ->pluck('course_name', 'pk')
                        ->toArray();
                } else {
                    $q = CourseMaster::where('end_date', '<', $currentDate);
                    if (! empty($data_course_id)) {
                        $q->whereIn('pk', $data_course_id);
                    }
                    $courses = $q->orderBy('course_name')
                        ->pluck('course_name', 'pk')
                        ->toArray();
                }

                return ['success' => true, 'courses' => $courses];
            },
            self::COURSE_LIST_CACHE_TTL
        );

        return response()->json($payload);
    }

    public function create()
    {
        // $deputationEmployeeList = EmployeeMaster::getDeputationEmployeeListNameAndPK();
        $facultyList = FacultyMaster::pluck('full_name', 'pk')->toArray();
        $roleOptions = [
            'Leave' => 'Leave',
            'Memo' => 'Memo',
            'Discipline' => 'Discipline',
            'Club Society' => 'Club Society'
        ];
        
        $isPrivileged = hasRole('Super Admin') || hasRole('Admin');
        if ($isPrivileged) {
            $supportingSectionList = Role::orderBy('name')->pluck('name', 'id')->toArray();
            $selectedSupportingSection = old('supportingsection', '');
        } else {
            $userRoleIds = \Illuminate\Support\Facades\DB::table('model_has_roles')
                ->where('model_id', \Illuminate\Support\Facades\Auth::user()->pk)
                ->where('model_type', \App\Models\User::class)
                ->pluck('role_id')
                ->toArray();
            $supportingSectionList = Role::whereIn('id', $userRoleIds)->orderBy('name')->pluck('name', 'id')->toArray();
            $selectedSupportingSection = old('supportingsection', count($userRoleIds) === 1 ? $userRoleIds[0] : '');
        }
        return view('admin.programme.create', compact('facultyList', 'roleOptions', 'supportingSectionList', 'selectedSupportingSection'));
    }

    public function edit(string $id)
    {
        try {
            $courseMasterObj = CourseMaster::findOrFail(decrypt($id));
            // $deputationEmployeeList = EmployeeMaster::getDeputationEmployeeListNameAndPK();
            $facultyList = FacultyMaster::pluck('full_name', 'pk')->toArray();

            $courseCoordinatorAssignments = $courseMasterObj->courseCordinatorMater()
                ->select('Coordinator_name', 'Assistant_Coordinator_name', 'assistant_coordinator_role')
                ->orderBy('pk')
                ->get();

            $coordinator_name = $courseCoordinatorAssignments->first()->Coordinator_name ?? '';

            $assistantCoordinatorCollection = $courseCoordinatorAssignments
                ->filter(function ($coordinator) {
                    return !is_null($coordinator->Assistant_Coordinator_name) && $coordinator->Assistant_Coordinator_name !== '';
                })
                ->unique('Assistant_Coordinator_name')
                ->values();

            // Get unique assistant coordinator names and their corresponding roles
            $assistant_coordinator_name = $assistantCoordinatorCollection->pluck('Assistant_Coordinator_name')->toArray();
            $assistant_coordinator_roles = $assistantCoordinatorCollection->pluck('assistant_coordinator_role')->toArray();
            $roleOptions = [
                'Leave' => 'Leave',
                'Memo' => 'Memo',
                'Discipline' => 'Discipline',
                'Club Society' => 'Club Society'
            ];
            $isPrivileged = hasRole('Super Admin') || hasRole('Admin');
            if ($isPrivileged) {
                $supportingSectionList = Role::orderBy('name')->pluck('name', 'id')->toArray();
            } else {
                $userRoleIds = \Illuminate\Support\Facades\DB::table('model_has_roles')
                    ->where('model_id', \Illuminate\Support\Facades\Auth::user()->pk)
                    ->where('model_type', \App\Models\User::class)
                    ->pluck('role_id')
                    ->toArray();
                $supportingSectionList = Role::whereIn('id', $userRoleIds)->orderBy('name')->pluck('name', 'id')->toArray();
            }
            $selectedSupportingSection = $courseMasterObj->user_role_master_pk ?? '';
            
            return view('admin.programme.edit', compact('courseMasterObj', 'facultyList', 'coordinator_name', 'assistant_coordinator_name', 'assistant_coordinator_roles', 'roleOptions', 'supportingSectionList', 'selectedSupportingSection'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Invalid course ID');
        }

    }

    public function store(ProgrammeRequest $request, CourseService $courseService)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validated();
            
            // Delegate all business logic to the service
            $courseService->createOrUpdateCourse($validated, $request->course_id);

            DB::commit();

            CourseMasterDataTable::bumpListingCacheEpoch();

            return redirect()->route('programme.index')->with('success', 'Course created successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Course creation error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Something went wrong. Please try again.');
        }
    } 



    public function view($id)
    {
        try {
            // Decrypt the ID
            $decryptedId = decrypt($id);
            
            // Find the course with coordinators
            $course = CourseMaster::with('courseCordinatorMater')->findOrFail($decryptedId);
            
            // Get coordinator details
            $coordinators = $course->courseCordinatorMater;
            $coordinatorName = $coordinators->first()->Coordinator_name ?? 'Not Assigned';
            $assistantCoordinators = $coordinators->pluck('Assistant_Coordinator_name')->filter()->unique()->values()->toArray();
            
            // Get faculty details for coordinators
            $coordinatorFaculty = FacultyMaster::where('full_name', $coordinatorName)->first();
            $assistantCoordinatorFaculties = FacultyMaster::whereIn('full_name', $assistantCoordinators)->get();
            
            return response()->json([
                'success' => true,
                'course' => [
                    'course_name' => $course->course_name,
                    'course_short_name' => $course->couse_short_name,
                    'course_year' => $course->course_year,
                    'start_date' => $course->start_year ? \Carbon\Carbon::parse($course->start_year)->format('Y-m-d') : 'Not Set',
                    'end_date' => $course->end_date ? \Carbon\Carbon::parse($course->end_date)->format('Y-m-d') : 'Not Set',
                    'pt_start_time' => $course->pt_start_time ? \Carbon\Carbon::parse($course->pt_start_time)->format('H:i') : null,
                    'pt_end_time' => $course->pt_end_time ? \Carbon\Carbon::parse($course->pt_end_time)->format('H:i') : null,
                    'coordinator_name' => $coordinatorName,
                    'assistant_coordinators' => $assistantCoordinators,
                    'coordinator_photo' => $coordinatorFaculty ? ($coordinatorFaculty->photo_uplode_path ?? null) : null,
                    'assistant_coordinator_photos' => $assistantCoordinatorFaculties->pluck('photo_uplode_path')->filter()->toArray(),
                ]
            ]);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            \Log::error('Decryption error in course view: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Invalid course ID'
            ], 400);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Course not found: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Course view error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading course details'
            ], 500);
        }
    }

    public function show($id)
    {
        // try {
            // Decrypt the ID
            $decryptedId = decrypt($id);
            
            // Find the course with coordinators
            $course = CourseMaster::with('courseCordinatorMater')->findOrFail($decryptedId);
            
            // Get coordinator details
            $coordinators = $course->courseCordinatorMater;
            
            // Get coordinator PK from the first coordinator record
            $coordinatorPk = $coordinators->first()->Coordinator_name ?? null;
            
            // Get assistant coordinator PKs - filter out null/empty values
            $assistantCoordinatorPks = $coordinators->pluck('Assistant_Coordinator_name')
                ->filter(function($pk) {
                    return $pk !== null && $pk !== '';
                })
                ->unique()
                ->values()
                ->toArray();
            
            // Fetch coordinator faculty using PK
            $coordinatorFaculty = null;
            $coordinatorName = 'Not Assigned';
            if ($coordinatorPk) {
                $coordinatorFaculty = FacultyMaster::find($coordinatorPk);
                $coordinatorName = $coordinatorFaculty ? $coordinatorFaculty->full_name : 'Not Assigned';
            }
            
            // Fetch assistant coordinator faculties using PKs
            $assistantCoordinatorFaculties = FacultyMaster::whereIn('pk', $assistantCoordinatorPks)->get();



            // Map assistant coordinators with their names, photos and roles
            $assistantCoordinatorsData = [];
            foreach ($coordinators as $coordinator) {
                if ($coordinator->Assistant_Coordinator_name) {
                    $assistantFaculty = $assistantCoordinatorFaculties->firstWhere('pk', $coordinator->Assistant_Coordinator_name);



                    $assistantCoordinatorsData[] = [
                        'name' => $assistantFaculty ? $assistantFaculty->full_name : 'Not Assigned',
                        'role' => $coordinator->assistant_coordinator_role ?? 'Not Specified',
                        'photo' => $assistantFaculty ? $assistantFaculty->photo_uplode_path : null
                    ];
                }
            }

            // Resolve the name of the user who created the course
            $createdByName = null;
            if (!empty($course->created_by)) {
                $creator = User::find($course->created_by);
                if ($creator) {
                    $createdByName = trim(($creator->first_name ?? '') . ' ' . ($creator->last_name ?? ''))
                        ?: ($creator->user_name ?? $creator->name ?? null);
                }
            }

            // Resolve the supporting section name from the linked role
            $supportingSectionName = null;
            if (!empty($course->user_role_master_pk)) {
                $supportingSectionName = Role::where('id', $course->user_role_master_pk)->value('name');
            }

            return view('admin.programme.show', compact(
                'course',
                'coordinatorName',
                'coordinatorFaculty',
                'assistantCoordinatorsData',
                'createdByName',
                'supportingSectionName'
            ));
        // } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
        //     \Log::error('Decryption error in course show: ' . $e->getMessage());
        //     return redirect()->route('programme.index')->with('error', 'Invalid course ID');
        // } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        //     \Log::error('Course not found: ' . $e->getMessage());
        //     return redirect()->route('programme.index')->with('error', 'Course not found');
        // } catch (\Exception $e) {
        //     \Log::error('Course show error: ' . $e->getMessage());
        //     return redirect()->route('programme.index')->with('error', 'An error occurred while loading course details');
        // }
    }

    public function downloadPdf($id)
    {
        try {
            // Decrypt the ID
            $decryptedId = decrypt($id);
            
            // Find the course with coordinators
            $course = CourseMaster::with('courseCordinatorMater')->findOrFail($decryptedId);
            
            // Get coordinator details
            $coordinators = $course->courseCordinatorMater;
            
            // Get coordinator PK from the first coordinator record
            $coordinatorPk = $coordinators->first()->Coordinator_name ?? null;
            
            // Get assistant coordinator PKs - filter out null/empty values
            $assistantCoordinatorPks = $coordinators->pluck('Assistant_Coordinator_name')
                ->filter(function($pk) {
                    return $pk !== null && $pk !== '';
                })
                ->unique()
                ->values()
                ->toArray();
            
            // Fetch coordinator faculty using PK
            $coordinatorFaculty = null;
            $coordinatorName = 'Not Assigned';
            if ($coordinatorPk) {
                $coordinatorFaculty = FacultyMaster::find($coordinatorPk);
                $coordinatorName = $coordinatorFaculty ? $coordinatorFaculty->full_name : 'Not Assigned';
            }
            
            // Fetch assistant coordinator faculties using PKs
            $assistantCoordinatorFaculties = FacultyMaster::whereIn('pk', $assistantCoordinatorPks)->get();
            
            // Map assistant coordinators with their names, photos and roles
            $assistantCoordinatorsData = [];
            foreach ($coordinators as $coordinator) {
                if ($coordinator->Assistant_Coordinator_name) {
                    $assistantFaculty = $assistantCoordinatorFaculties->firstWhere('pk', $coordinator->Assistant_Coordinator_name);
                    $assistantCoordinatorsData[] = [
                        'name' => $assistantFaculty ? $assistantFaculty->full_name : 'Not Assigned',
                        'role' => $coordinator->assistant_coordinator_role ?? 'Not Specified',
                        'photo' => $assistantFaculty ? $assistantFaculty->photo_uplode_path : null
                    ];
                }
            }
            
            // Generate PDF
            $pdf = Pdf::loadView('admin.programme.pdf', compact(
                'course',
                'coordinatorName',
                'coordinatorFaculty',
                'assistantCoordinatorsData'
            ));
            
            $pdf->setPaper('a4', 'portrait');
            
            $filename = 'Course_' . str_replace(' ', '_', $course->course_name) . '_' . date('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            \Log::error('Decryption error in course PDF download: ' . $e->getMessage());
            return redirect()->route('programme.index')->with('error', 'Invalid course ID');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Course not found: ' . $e->getMessage());
            return redirect()->route('programme.index')->with('error', 'Course not found');
        } catch (\Exception $e) {
            \Log::error('Course PDF download error: ' . $e->getMessage());
            return redirect()->route('programme.index')->with('error', 'An error occurred while generating PDF');
        }
    }

    public function debug($id)
    {
        try {
            $decryptedId = decrypt($id);
            $course = CourseMaster::with('courseCordinatorMater')->findOrFail($decryptedId);
            
            $coordinators = $course->courseCordinatorMater;
            $allAssistantNames = $coordinators->pluck('Assistant_Coordinator_name')->toArray();
            
            return response()->json([
                'course_id' => $decryptedId,
                'course_name' => $course->course_name,
                'coordinators_count' => $coordinators->count(),
                'all_assistant_names' => $allAssistantNames,
                'raw_coordinator_data' => $coordinators->toArray(),
                'filtered_assistant_coordinators' => $coordinators->pluck('Assistant_Coordinator_name')
                    ->filter(function($name) {
                        return $name !== null && $name !== '';
                    })
                    ->unique()
                    ->values()
                    ->toArray()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $decryptedId = decrypt($id);
            $course = CourseMaster::findOrFail($decryptedId);
            
            // Delete related course coordinators
            $course->courseCordinatorMater()->delete();
            
            // Delete the course
            $course->delete();
            
            DB::commit();

            CourseMasterDataTable::bumpListingCacheEpoch();

            return redirect()->route('programme.index')->with('success', 'Course deleted successfully');
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            DB::rollBack();
            \Log::error('Decryption error in course delete: ' . $e->getMessage());
            return redirect()->route('programme.index')->with('error', 'Invalid course ID');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            \Log::error('Course not found: ' . $e->getMessage());
            return redirect()->route('programme.index')->with('error', 'Course not found');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Course delete error: ' . $e->getMessage());
            return redirect()->route('programme.index')->with('error', 'An error occurred while deleting the course');
        }
    }
}
