<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoursesMaster;
use App\Models\CourseTeamMaster;
use Illuminate\Http\Request;
use App\Http\Requests\ProgrammeRequest;
use App\Models\{EmployeeMaster, CourseMaster, FacultyMaster};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use App\DataTables\CourseMasterDataTable;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;


class CourseController extends Controller
{
    public function index(CourseMasterDataTable $dataTable)
    {
        return $dataTable->render('admin.programme.index');
    }

    public function create()
    {
        // $deputationEmployeeList = EmployeeMaster::getDeputationEmployeeListNameAndPK();
        $facultyList = FacultyMaster::pluck('full_name', 'pk')->toArray();
        $roleOptions = [
            'Leave' => 'Leave',
            'Memo' => 'Memo'
        ];
        return view('admin.programme.create', compact('facultyList', 'roleOptions'));
    }

    public function edit(string $id)
    {
        try {
            $courseMasterObj = CourseMaster::findOrFail(decrypt($id));
            // $deputationEmployeeList = EmployeeMaster::getDeputationEmployeeListNameAndPK();
            $facultyList = FacultyMaster::pluck('full_name', 'pk')->toArray();

            $courseCordinatorMaterData = $courseMasterObj->courseCordinatorMater->map(function($item){
                $item['Coordinator_name'] = $item['Coordinator_name'];
                $item['Assistant_Coordinator_name'] = $item['Assistant_Coordinator_name'];
                $item['assistant_coordinator_role'] = $item['assistant_coordinator_role'];
                return $item;
            })->toArray();
            
            $coordinator_name = array_column($courseCordinatorMaterData, 'Coordinator_name');
            $assistant_coordinator_name = array_column($courseCordinatorMaterData, 'Assistant_Coordinator_name');
            $assistant_coordinator_roles = array_column($courseCordinatorMaterData, 'assistant_coordinator_role');
            $coordinator_name = $coordinator_name[0] ?? '';
            $roleOptions = [
                'Leave' => 'Leave',
                'Memo' => 'Memo'
            ];
            
            return view('admin.programme.create', compact('courseMasterObj', 'facultyList', 'coordinator_name', 'assistant_coordinator_name', 'assistant_coordinator_roles', 'roleOptions'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Invalid course ID');
        }

    }

    public function store(ProgrammeRequest $request)
    {
        DB::beginTransaction();
        try {

            $validated = $request->validated();

            $validated['courseyear'] = date('Y', strtotime($validated['courseyear']));
            $validated['startdate'] = date('Y-m-d', strtotime($validated['startdate']));
            $validated['enddate'] = date('Y-m-d', strtotime($validated['enddate']));

            $courseMasterObj = null;

            if( $request->course_id ) {

                // Update existing course

                $courseMasterObj = CourseMaster::findOrFail(decrypt($request->course_id));
                $courseMasterObj->update([
                    'course_name' => $validated['coursename'],
                    'couse_short_name' => $validated['courseshortname'],
                    'course_year' => $validated['courseyear'],
                    'start_year' => $validated['startdate'],
                    'end_date' => $validated['enddate'],
                    'Modified_date' => now(),
                ]);
            } else {

                // Create new course

                $courseMasterObj = new CourseMaster();
                $courseMasterObj->fill([
                    'course_name' => $validated['coursename'],
                    'couse_short_name' => $validated['courseshortname'],
                    'course_year' => $validated['courseyear'],
                    'start_year' => $validated['startdate'],
                    'end_date' => $validated['enddate'],
                    'created_date' => now(),
                    'Modified_date' => now(),
                ]);
                $courseMasterObj->save();
            }

            if (!$courseMasterObj) {
                return redirect()->back()->with('error', 'Course creation failed');
            }

            // Delete existing course coordinators
            $courseMasterObj->courseCordinatorMater()->delete();


            foreach ($validated['assistantcoursecoordinator'] as $key => $value) {
                $courseMasterObj->courseCordinatorMater()->create([
                    'courses_master_pk' => $courseMasterObj->pk,
                    'Coordinator_name' => $validated['coursecoordinator'],
                    'Assistant_Coordinator_name' => $value,
                    'assistant_coordinator_role' => $validated['assistant_coordinator_role'][$key] ?? '',
                    'created_date' => now(),
                    'Modified_date' => now(),
                ]);
            }

            DB::commit();
            return redirect()->route('programme.index')->with('success', 'Course created successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Course creation error: ' . $e->getMessage());
            // return redirect()->back()->with('error', $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong. Please try again.');
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
        try {
            // Decrypt the ID
            $decryptedId = decrypt($id);
            
            // Find the course with coordinators
            $course = CourseMaster::with('courseCordinatorMater')->findOrFail($decryptedId);
            
            // Get coordinator details
            $coordinators = $course->courseCordinatorMater;
            $coordinatorName = $coordinators->first()->Coordinator_name ?? 'Not Assigned';
            
            // Get assistant coordinators - filter out null/empty values but keep valid names
            $assistantCoordinators = $coordinators->pluck('Assistant_Coordinator_name')
                ->filter(function($name) {
                    return $name !== null && $name !== '';
                })
                ->unique()
                ->values()
                ->toArray();
            
            // Debug logging
            \Log::info('Course coordinators data:', [
                'course_id' => $decryptedId,
                'coordinators_count' => $coordinators->count(),
                'coordinator_name' => $coordinatorName,
                'assistant_coordinators' => $assistantCoordinators,
                'raw_coordinator_data' => $coordinators->toArray()
            ]);
            
            // Additional debugging for assistant coordinators
            $allAssistantNames = $coordinators->pluck('Assistant_Coordinator_name')->toArray();
            \Log::info('All assistant coordinator names (before filtering):', $allAssistantNames);
            
            // Get faculty details for coordinators
            $coordinatorFaculty = FacultyMaster::where('full_name', $coordinatorName)->first();
            $assistantCoordinatorFaculties = FacultyMaster::whereIn('full_name', $assistantCoordinators)->get();
            
            // Map assistant coordinators with their photos and roles
            $assistantCoordinatorsData = [];
            foreach ($coordinators as $coordinator) {
                if ($coordinator->Assistant_Coordinator_name) {
                    $assistantFaculty = $assistantCoordinatorFaculties->firstWhere('full_name', $coordinator->Assistant_Coordinator_name);
                    $assistantCoordinatorsData[] = [
                        'name' => $coordinator->Assistant_Coordinator_name,
                        'role' => $coordinator->assistant_coordinator_role ?? 'Not Specified',
                        'photo' => $assistantFaculty ? $assistantFaculty->photo_uplode_path : null
                    ];
                }
            }

            return view('admin.programme.show', compact(
                'course',
                'coordinatorName',
                'coordinatorFaculty',
                'assistantCoordinatorsData'
            ));
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            \Log::error('Decryption error in course show: ' . $e->getMessage());
            return redirect()->route('programme.index')->with('error', 'Invalid course ID');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Course not found: ' . $e->getMessage());
            return redirect()->route('programme.index')->with('error', 'Course not found');
        } catch (\Exception $e) {
            \Log::error('Course show error: ' . $e->getMessage());
            return redirect()->route('programme.index')->with('error', 'An error occurred while loading course details');
        }
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
            $coordinatorName = $coordinators->first()->Coordinator_name ?? 'Not Assigned';
            $assistantCoordinators = $coordinators->pluck('Assistant_Coordinator_name')->filter()->unique()->values()->toArray();
            
            // Get faculty details for coordinators
            $coordinatorFaculty = FacultyMaster::where('full_name', $coordinatorName)->first();
            $assistantCoordinatorFaculties = FacultyMaster::whereIn('full_name', $assistantCoordinators)->get();
            
            // Map assistant coordinators with their photos and roles
            $assistantCoordinatorsData = [];
            foreach ($coordinators as $coordinator) {
                if ($coordinator->Assistant_Coordinator_name) {
                    $assistantFaculty = $assistantCoordinatorFaculties->firstWhere('full_name', $coordinator->Assistant_Coordinator_name);
                    $assistantCoordinatorsData[] = [
                        'name' => $coordinator->Assistant_Coordinator_name,
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
}
