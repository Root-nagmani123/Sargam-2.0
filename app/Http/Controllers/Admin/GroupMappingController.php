<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Imports\GroupMapping\GroupMappingMultipleSheetImport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\{CourseMaster, CourseGroupTypeMaster, GroupTypeMasterCourseMasterMap, StudentCourseGroupMap, StudentMasterCourseMap, VenueMaster,FacultyMaster, StudentMaster};
use App\Exports\GroupMappingExport;
use App\DataTables\GroupMappingDataTable;
use Carbon\Carbon;
use App\Http\Requests\Admin\GroupMapping\BulkMessageRequest;
use App\Services\Messaging\EmailService;
use App\Services\Messaging\SmsService;
use App\Services\NotificationService;

class GroupMappingController extends Controller
{
    protected EmailService $emailService;
    protected SmsService $smsService;

    public function __construct(EmailService $emailService, SmsService $smsService)
    {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
    }

    public function index(GroupMappingDataTable $dataTable)
    {
        $data_course_id =  get_Role_by_course();

        $courses = CourseMaster::where('active_inactive', '1')
            ->where('end_date', '>', now());

        if(!empty($data_course_id))
        {
            $courses = $courses->whereIn('pk',$data_course_id);
        }

        $courses = $courses->orderBy('course_name')
            ->pluck('course_name', 'pk')
            ->toArray();

        $groupTypes = CourseGroupTypeMaster::where('active_inactive', 1)
                ->orderBy('type_name')
                ->pluck('type_name', 'pk')
                ->toArray();

        return $dataTable->render('admin.group_mapping.index', compact('courses', 'groupTypes'));
    }


    /**
     * Show the form for creating a new group mapping.
     *
     * @return \Illuminate\View\View
     */
    function create()
    {
        $data_course_id =  get_Role_by_course();
          
        $courses = CourseMaster::where('active_inactive', '1');
           $courses->where('end_date', '>', now());
              if(!empty($data_course_id))
            {
                $courses = CourseMaster::whereIn('pk',$data_course_id);
            }
            $courses = $courses->orderBy('pk', 'desc')
            ->pluck('course_name', 'pk')
            ->toArray();
        $courseGroupTypeMaster = CourseGroupTypeMaster::pluck('type_name', 'pk')->toArray();
        $facilities = FacultyMaster::where('active_inactive', 1)
            ->orderBy('full_name')
            ->pluck('full_name', 'pk')
            ->toArray();

        return view('admin.group_mapping.create', compact('courses', 'courseGroupTypeMaster', 'facilities'));
    }

    /**
     * Show the form for editing an existing group mapping.
     *
     * @param string $id Encrypted group mapping ID
     * @return \Illuminate\View\View
     */
    function edit(string $id)
    {
        $groupMapping = GroupTypeMasterCourseMasterMap::find(decrypt($id));
        $data_course_id =  get_Role_by_course();
        
        // Get active courses (active_inactive = '1' and end_date > now())
        $activeCourses = CourseMaster::where('active_inactive', '1');
        if(!empty($data_course_id))
        {
            $activeCourses = $activeCourses->whereIn('pk',$data_course_id);
        }
        $activeCourses = $activeCourses->where('end_date', '>', now())
            ->orderBy('pk', 'desc')
            ->pluck('course_name', 'pk')
            ->toArray();
        
        // If editing and the current course is archived, include it in the list
        $courses = $activeCourses;
        if ($groupMapping && $groupMapping->course_name) {
            $currentCourse = CourseMaster::find($groupMapping->course_name);
            if ($currentCourse && !isset($courses[$currentCourse->pk])) {
                $courses = [$currentCourse->pk => $currentCourse->course_name] + $courses;
            }
        }
        
        $courseGroupTypeMaster = CourseGroupTypeMaster::pluck('type_name', 'pk')->toArray();

        $facilities = FacultyMaster::where('active_inactive', 1)
            ->orderBy('full_name')
            ->pluck('full_name', 'pk')
            ->toArray();

        if ($groupMapping && $groupMapping->facility_id && !isset($facilities[$groupMapping->facility_id])) {
            $Faculty= FacultyMaster::find($groupMapping->facility_id);
            if ($Faculty) {
                $facilities[$Faculty->pk] = $Faculty->full_name;
            }
        }

        return view('admin.group_mapping.create', compact('groupMapping', 'courses', 'courseGroupTypeMaster', 'facilities'));
    }

    /**
     * Store or update a group mapping in the database.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    function store(Request $request)
    {
        try {
            $request->validate([
                'course_id' => 'required|string|max:255',
                'type_id' => 'required|string|max:255',
                'group_name' => 'required|string|max:255',
                'facility_id' => 'nullable|string|max:255',
            ]);

            // Get old facility_id if updating
            $oldFacilityId = null;
            $isUpdate = false;
            if ($request->pk) {
                $groupMapping = GroupTypeMasterCourseMasterMap::find(decrypt($request->pk));
                $oldFacilityId = $groupMapping->facility_id;
                $isUpdate = true;
                $message = 'Group Mapping updated successfully.';
            } else {
                $groupMapping = new GroupTypeMasterCourseMasterMap();
                $message = 'Group Mapping created successfully.';
            }

            // Check if any details changed (for update scenario)
            $detailsChanged = false;
            if ($isUpdate) {
                $detailsChanged = (
                    $groupMapping->course_name != $request->course_id ||
                    $groupMapping->type_name != $request->type_id ||
                    $groupMapping->group_name != $request->group_name
                );
            }

            $groupMapping->course_name = $request->course_id;
            $groupMapping->type_name = $request->type_id;
            $groupMapping->group_name = $request->group_name;
            $newFacilityId = $request->facility_id ?: null;
            $groupMapping->facility_id = $newFacilityId;
            $groupMapping->save();

            // Get the course name for notification
            $course = CourseMaster::find($request->course_id);
            $courseName = $course ? $course->course_name : '';

            // Handle notifications - convert faculty PKs to user_ids and handle all scenarios
            $notificationService = app(NotificationService::class);
            
            // Convert faculty PKs to user_ids (employee_master_pk from FacultyMaster)
            $oldFacilityUserId = $oldFacilityId ? $this->convertFacultyPkToUserId((int)$oldFacilityId) : null;
            $newFacilityUserId = $newFacilityId ? $this->convertFacultyPkToUserId((int)$newFacilityId) : null;

            // Scenario 1: Faculty REMOVED - old faculty exists but new is null
            if ($oldFacilityUserId && !$newFacilityUserId) {
                $notificationService->create(
                    $oldFacilityUserId,
                    'group_mapping_removed',
                    'Group Mapping',
                    $groupMapping->pk,
                    'Removed from Group Mapping',
                    "You have been removed from the group mapping '{$request->group_name}' for course '{$courseName}'."
                );
            }
            // Scenario 2: Faculty CHANGED - old and new are different
            elseif ($oldFacilityUserId && $newFacilityUserId && $oldFacilityId != $newFacilityId) {
                // Notify old faculty they were removed
                $notificationService->create(
                    $oldFacilityUserId,
                    'group_mapping_removed',
                    'Group Mapping',
                    $groupMapping->pk,
                    'Removed from Group Mapping',
                    "You have been removed from the group mapping '{$request->group_name}' for course '{$courseName}'."
                );
                // Notify new faculty they were added
                $notificationService->create(
                    $newFacilityUserId,
                    'group_mapping_added',
                    'Group Mapping',
                    $groupMapping->pk,
                    'Added to Group Mapping',
                    "You have been added to the group mapping '{$request->group_name}' for course '{$courseName}'."
                );
            }
            // Scenario 3: Faculty ADDED - no old faculty, new faculty added (create or update)
            elseif (!$oldFacilityUserId && $newFacilityUserId) {
                $notificationService->create(
                    $newFacilityUserId,
                    'group_mapping_added',
                    'Group Mapping',
                    $groupMapping->pk,
                    'Added to Group Mapping',
                    "You have been added to the group mapping '{$request->group_name}' for course '{$courseName}'."
                );
            }
            // Scenario 4: Faculty SAME but details changed - same faculty, other details updated
            elseif ($oldFacilityUserId && $newFacilityUserId && $oldFacilityId == $newFacilityId && $detailsChanged) {
                $notificationService->create(
                    $newFacilityUserId,
                    'group_mapping_updated',
                    'Group Mapping',
                    $groupMapping->pk,
                    'Group Mapping Updated',
                    "The group mapping '{$request->group_name}' for course '{$courseName}' has been updated."
                );
            }

            return redirect()->route('group.mapping.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Import group mappings from an Excel file.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    function importGroupMapping(Request $request)
    {
        // print_r($request->all());die;
        try {
            $request->validate([
                'file' => 'required|mimes:xlsx,xls,csv|max:10248',
            ]);

           $import = new GroupMappingMultipleSheetImport(
                $request->course_master_pk
            );

            Excel::import($import, $request->file('file'));
            $failures = $import->sheet1Import->failures;

            if (count($failures) > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation errors found in Excel file.',
                    'failures' => $failures,
                ], 422);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Group Mapping imported successfully.',
            ], 200);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Fetch and return a paginated list of students for a specific group mapping.
     * Filters students by both group mapping and course enrollment.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function studentList(Request $request)
    {
        try {
            $groupMappingID = decrypt($request->groupMappingID);
            $groupMapping = GroupTypeMasterCourseMasterMap::with(['Faculty', 'courseGroup'])->findOrFail($groupMappingID);
            
            // Get the course ID from the group mapping
            $courseId = $groupMapping->course_name;
            
            // Get search query
            $searchQuery = $request->input('search', '');
            
            // Filter students by:
            // 1. Group mapping (group_type_master_course_master_map_pk)
            // 2. Course enrollment (via StudentMasterCourseMap - students must be enrolled in the course)
            $query = StudentCourseGroupMap::with('studentsMaster:display_name,email,contact_no,generated_OT_code,pk')
                ->where('group_type_master_course_master_map_pk', $groupMapping->pk);
            
            // Apply search filter if search query is provided
            if (!empty($searchQuery)) {
                $query->whereHas('studentsMaster', function($q) use ($searchQuery) {
                    $q->where('display_name', 'like', '%' . $searchQuery . '%')
                      ->orWhere('email', 'like', '%' . $searchQuery . '%')
                      ->orWhere('contact_no', 'like', '%' . $searchQuery . '%')
                      ->orWhere('generated_OT_code', 'like', '%' . $searchQuery . '%');
                });
            }
            
            $students = $query->paginate(10, ['*'], 'page', $request->page);
            
            // Render the HTML partial
            $groupMappingPk = $groupMapping->pk;
            $courseName = $groupMapping->courseGroup->course_name ?? 'N/A';
            $html = view('admin.group_mapping.student_list_ajax', [
                'students' => $students,
                'groupMappingPk' => $groupMappingPk,
                'groupName' => $groupMapping->group_name,
                'facilityName' => optional($groupMapping->Faculty)->venue_name,
                'courseName' => $courseName,
                'searchQuery' => $searchQuery,
            ])->render();

            return response()->json([
                'status' => 'success',
                'html' => $html,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function updateStudent(Request $request)
    {
        try {
            $validated = $request->validate([
                'student_id' => 'required|string',
                'display_name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'contact_no' => 'nullable|string|max:20',
            ]);

            try {
                $studentId = decrypt($validated['student_id']);
            } catch (\Throwable $exception) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid student identifier.',
                ], 422);
            }

            $student = StudentMaster::findOrFail($studentId);
            $student->display_name = $validated['display_name'];
            $student->email = $validated['email'];
            $student->contact_no = $validated['contact_no'];
            $student->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Student details updated successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $validationException) {
            throw $validationException;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteStudent(Request $request)
    {
        try {
            $request->validate([
                'mapping_id' => 'required|string',
            ]);

            try {
                $mappingId = decrypt($request->mapping_id);
            } catch (\Throwable $exception) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid mapping identifier.',
                ], 422);
            }

            $mapping = StudentCourseGroupMap::findOrFail($mappingId);
            $mapping->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Student removed from the group successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $validationException) {
            throw $validationException;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get group names based on selected group type.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGroupNamesByType(Request $request)
    {
        try {
            $request->validate([
                'group_type_id' => 'required|integer|exists:course_group_type_master,pk',
            ]);

            $groupTypeId = $request->group_type_id;
            
            // Get group type name
            $groupType = CourseGroupTypeMaster::findOrFail($groupTypeId);
            
            // Get all group names for this group type
            $groupNames = GroupTypeMasterCourseMasterMap::where('type_name', $groupTypeId)
                ->where('active_inactive', 1)
                ->orderBy('group_name')
                ->pluck('group_name')
                ->unique()
                ->values()
                ->toArray();

            return response()->json([
                'status' => 'success',
                'group_type_name' => $groupType->type_name,
                'group_names' => $groupNames,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Add a single student to group mapping (manual entry).
     * Follows the same logic as Excel import but for a single record.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addSingleStudent(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'otcode' => 'required|string|max:255',
                'group_name' => 'required|string|max:255',
                'course_master_pk' => 'required|integer|exists:course_master,pk',
                'group_type' => 'required|string|max:255',
            ]);

            // Trim all inputs
            $data = array_map('trim', $validated);

            // Lookup: StudentMaster by OT code (case-insensitive)
            $studentMaster = StudentMaster::whereRaw('LOWER(generated_OT_code) = ?', [strtolower($data['otcode'])])
                ->select('pk')->first();
                

            if (!$studentMaster) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Student not found for OT code: {$data['otcode']}",
                ], 422);
            }

            // Lookup: GroupTypeMasterCourseMasterMap by group name (case-insensitive)
         $groupMap = GroupTypeMasterCourseMasterMap::whereRaw(
                        'LOWER(group_name) = ?',
                        [strtolower($data['group_name'])]
                    )
                    ->where('course_name', $data['course_master_pk']) // 🔥 REQUIRED
                    ->where('active_inactive', 1)
                    ->first();

            // print_r($groupMap);die;

            if (!$groupMap) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Group map not found for group name: {$data['group_name']}",
                ], 422);
            }

            // Lookup: CourseGroupTypeMaster to verify group type
            $courseGroupType = CourseGroupTypeMaster::where('pk', $groupMap->type_name)->first();

            if (!$courseGroupType) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Course group type not found for type name ID: {$groupMap->type_name}",
                ], 422);
            }

            // Compare group type (case-insensitive)
            if (strcasecmp($courseGroupType->type_name, $data['group_type']) !== 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Group type mismatch: expected '{$courseGroupType->type_name}', got '{$data['group_type']}' for group '{$data['group_name']}'",
                ], 422);
            }

            // Check if mapping already exists
            $studentCourseExists = StudentMasterCourseMap::where(
                        'student_master_pk',
                        $studentMaster->pk
                    )
                    ->where('course_master_pk', $data['course_master_pk'])
                    ->where('active_inactive', 1)
                    ->exists();

                if (!$studentCourseExists) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Student does not belong to selected course",
                    ], 422);
                }

            // Create the mapping (same as Excel import)
            StudentCourseGroupMap::create([
                'student_master_pk' => $studentMaster->pk,
                'group_type_master_course_master_map_pk' => $groupMap->pk,
                'active_inactive' => 1,
                'created_date' => now(),
                'modified_date' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Student added to group successfully.',
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $validationException) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validationException->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Export the student list for group mappings to an Excel file.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function exportStudentList($id = null)
    {
        try {
            // If ID is provided, validate it
            if ($id) {
                try {
                    decrypt($id);
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', 'Invalid Group Mapping ID.');
                }
            }

            $fileName = 'group-mapping-export-' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download(new GroupMappingExport($id), $fileName);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }


    function delete(string $id)
    {
        try {
            
            $groupMapping = GroupTypeMasterCourseMasterMap::findOrFail(decrypt($id));
            $groupMapping->studentCourseGroupMap()->delete();
            $groupMapping->delete();
            
            return redirect()->route('group.mapping.index')->with('success', 'Group Mapping deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function sendMessage(BulkMessageRequest $request)
    {
        try {
            $groupMappingId = decrypt($request->input('group_mapping_id'));
        } catch (\Throwable $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid group mapping selection.',
            ], 422);
        }

        $encryptedStudentIds = collect($request->input('student_ids'));
        $studentIds = collect();

        foreach ($encryptedStudentIds as $encryptedId) {
            try {
                $studentIds->push(decrypt($encryptedId));
            } catch (\Throwable $exception) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'One or more selected OTs could not be verified.',
                ], 422);
            }
        }

        $studentMappings = StudentCourseGroupMap::with('studentsMaster:pk,display_name,email,contact_no')
            ->where('group_type_master_course_master_map_pk', $groupMappingId)
            ->whereIn('student_master_pk', $studentIds)
            ->get();

        if ($studentMappings->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No matching OTs were found for this group.',
            ], 422);
        }

        $matchedStudentIds = $studentMappings->pluck('student_master_pk');
        $missingSelections = $studentIds->diff($matchedStudentIds);

        if ($missingSelections->isNotEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some selected OTs are not part of the chosen group.',
            ], 422);
        }

        $students = $studentMappings->pluck('studentsMaster')->filter();

        if ($students->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No contact records were found for the selected OTs.',
            ], 422);
        }

        $channel = $request->input('channel');
        $message = $request->input('message');

        if ($channel === 'email') {
            $emails = $students->pluck('email')->filter();

            if ($emails->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'None of the selected OTs have an email address on record.',
                ], 422);
            }

            $failed = $this->emailService->sendBulk($emails, $message);
            $sentCount = $emails->count() - count($failed);

            return response()->json([
                'status' => 'success',
                'message' => $sentCount > 0
                    ? "Email sent to {$sentCount} OT(s)."
                    : 'Unable to send email to the selected OTs.',
                'data' => [
                    'channel' => 'email',
                    'attempted' => $emails->count(),
                    'failed' => $failed,
                ],
            ], $sentCount > 0 ? 200 : 500);
        }

        $phoneNumbers = $students->pluck('contact_no')->filter();

        if ($phoneNumbers->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'None of the selected OTs have a contact number on record.',
            ], 422);
        }

        $failed = $this->smsService->sendBulk($phoneNumbers, $message);
        $sentCount = $phoneNumbers->count() - count($failed);

        if ($sentCount <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to send SMS to the selected OTs.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => "SMS sent to {$sentCount} OT(s).",
            'data' => [
                'channel' => 'sms',
                'attempted' => $phoneNumbers->count(),
                'failed' => $failed,
            ],
        ]);
    }

    /**
     * Convert faculty PK to user_id (employee_master_pk)
     * 
     * @param int|null $facultyPk
     * @return int|null
     */
    protected function convertFacultyPkToUserId(?int $facultyPk): ?int
    {
        if (empty($facultyPk)) {
            return null;
        }
        
        $faculty = FacultyMaster::find($facultyPk);
        if (!$faculty || !$faculty->employee_master_pk) {
            return null;
        }
        
        return (int) $faculty->employee_master_pk;
    }
}
