<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseRepositoryMaster;
use App\Models\CourseRepositoryDetail;
use App\Models\CourseRepositoryDocument;
use App\Models\CourseRepositorySubtopic;
use App\Models\CourseMaster;
use App\Models\SubjectMaster;
use App\Models\FacultyMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;


class CourseRepositoryController extends Controller
{
    /**
     * Display listing of all course repositories
     * GET /course-repository
     * Optional parent_pk query parameter to show children of a specific parent
     */
    public function index(Request $request)
    {
        try {
            $parentPk = $request->query('parent_pk');
            $parentRepository = null;
            $ancestors = [];
             $documents_count_array = [];
            if ($parentPk) {
                
                // Show children of specific parent
                $parentRepository = CourseRepositoryMaster::findOrFail($parentPk);
                $repositories = $parentRepository->children()
                    ->with(['children', 'documents'])
                    ->orderBy('created_date', 'desc')
                    ->paginate(15);
                   
            
                                $documents_count_array = [];

                    foreach ($repositories as $child) {

                        $documents_count = CourseRepositoryDetail::where(
                            'course_repository_master_pk',
                            $child->pk
                        )->count();

                        $documents_count_array[$child->pk] = $documents_count;
                    }


                // Build ancestor chain for breadcrumb
                $current = $parentRepository;
                while ($current && $current->parent) {
                    // Prepend to keep order from root -> ... -> parent
                    array_unshift($ancestors, $current->parent);
                    $current = $current->parent;
                }
            } else {
                // Show only root repositories (no parent)
                $repositories = CourseRepositoryMaster::where('del_folder_status', 1)
                    ->whereNull('parent_type')
                    ->with(['children', 'documents'])
                    ->orderBy('created_date', 'desc')
                    ->paginate(15);
                    $documents_count_array = [];

                    foreach ($repositories as $child) {

                        $documents_count = CourseRepositoryDetail::where(
                            'course_repository_master_pk',
                            $child->pk
                        )->count();

                        $documents_count_array[$child->pk] = $documents_count;
                    }
                    
            }
            
            return view('admin.course-repository.index', [
                'repositories' => $repositories,
                'parentRepository' => $parentRepository,
                'parentPk' => $parentPk,
                'ancestors' => $ancestors,
                'documents_count_array' => $documents_count_array,
            ]);
        } catch (Exception $e) {
            Log::error('Error in course repository index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load repositories');
        }
    }

    /**
     * Show repository details
     * GET /course-repository/{pk}
     */
    public function show($pk)
    {
        try {
            $repository = CourseRepositoryMaster::with([
                'details' => function($query) {
                    $query->with(['documents', 'course', 'subject', 'topic', 'creator']);
                }, 
                'parent',
                'children' => function($query) {
                    $query->with(['documents', 'children' => function($q) {
                        $q->with(['documents', 'children' => function($q2) {
                            $q2->with('documents');
                        }]);
                    }]);
                },
                'documents'
            ])->findOrFail($pk);
            $documents_count_array = [];
            
            foreach ($repository->children as $child) {
                        $documents_count = CourseRepositoryDetail::where(
                            'course_repository_master_pk',
                            $child->pk
                        )->count();
                        $documents_count_array[$child->pk] = $documents_count;
                         }
          
            
            // Get all documents linked through details with course_repository_details_pk
            // Also include documents directly linked to master via course_repository_master_pk
            $documents = CourseRepositoryDocument::where('del_type', 1)
                ->where(function($query) use ($pk) {
                    $query->where('course_repository_master_pk', $pk)
                        ->orWhereIn('course_repository_details_pk', 
                            CourseRepositoryDetail::where('course_repository_master_pk', $pk)->pluck('pk')
                        );
                })
                ->orderBy('pk', 'desc')
                ->get();

            // Build ancestor chain for breadcrumb
            $ancestors = [];
            $current = $repository;
            while ($current && $current->parent) {
                array_unshift($ancestors, $current->parent);
                $current = $current->parent;
            }
           
            return view('admin.course-repository.show', [
                'repository' => $repository,
                'documents' => $documents,
                'ancestors' => $ancestors,
                'documents_count_array' => $documents_count_array,
                // Data for dynamic dropdowns
                'courses' => CourseMaster::where('active_inactive', 1)->get(),
                'subjects' => SubjectMaster::where('active_inactive', 1)->get(),
                'topics' => CourseRepositorySubtopic::all(),
                'authors' => FacultyMaster::select('pk','full_name')->get(),
            ]);
        } catch (Exception $e) {
            Log::error('Error in course repository show: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Repository not found');
        }
    }

    /**
     * Show create repository form
     * GET /course-repository/create
     * Optional parent_pk query parameter for creating child repositories
     */
    public function create(Request $request)
    {
        try {
            $subtopics = CourseRepositorySubtopic::all();
            $parentPk = $request->query('parent_pk');
            $parentRepository = null;
            
            if ($parentPk) {
                $parentRepository = CourseRepositoryMaster::findOrFail($parentPk);
            }
            
            return view('admin.course-repository.create', [
                'subtopics' => $subtopics,
                'parentRepository' => $parentRepository,
                'parentPk' => $parentPk,
            ]);
        } catch (Exception $e) {
            Log::error('Error loading create form: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load form');
        }
    }

    /**
     * Store a new repository
     * POST /course-repository
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'course_repository_name' => 'required|string|max:500',
                'folder_type' => 'nullable|integer',
                'parent_type' => 'nullable|integer',
                'file_type' => 'nullable|integer',
                'full_path' => 'nullable|string|max:255',
                'course_repository_details' => 'nullable|string|max:1000',
            ]);
            
            $validated['created_date'] = now();
            $validated['created_by'] = auth()->id();
            $validated['status'] = 1;
            $validated['del_folder_status'] = 1;
            
            // If parent_type not provided or empty, set to null (root repository)
            if (!isset($validated['parent_type']) || empty($validated['parent_type'])) {
                $validated['parent_type'] = null;
            }
            
            $repository = CourseRepositoryMaster::create($validated);
            
            // Check if AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Category created successfully',
                    'data' => $repository
                ]);
            }
            
            return redirect()->route('course-repository.show', $repository->pk)
                ->with('success', 'Repository created successfully');
        } catch (Exception $e) {
            Log::error('Error storing repository: ' . $e->getMessage());
            
            // Check if AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create category: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create repository');
        }
    }

    /**
     * Show edit repository form
     * GET /course-repository/{pk}/edit
     */
    public function edit($pk)
    {
        try {
            $repository = CourseRepositoryMaster::findOrFail($pk);
            $subtopics = CourseRepositorySubtopic::all();
            
            return view('admin.course-repository.edit', [
                'repository' => $repository,
                'subtopics' => $subtopics,
            ]);
        } catch (Exception $e) {
            Log::error('Error loading edit form: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Repository not found');
        }
    }

    /**
     * Update a repository
     * PUT /course-repository/{pk}
     */
    public function update($pk, Request $request)
    {
        try {
            $repository = CourseRepositoryMaster::findOrFail($pk);
            
            $validated = $request->validate([
                'course_repository_name' => 'required|string|max:500',
                'folder_type' => 'nullable|integer',
                'parent_type' => 'nullable|integer',
                'file_type' => 'nullable|integer',
                'full_path' => 'nullable|string|max:255',
                'course_repository_details' => 'nullable|string|max:1000',
            ]);
            
            $validated['modify_date'] = now();
            $validated['modify_by'] = auth()->id();
            
            $repository->update($validated);
            
            // Check if AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Category updated successfully',
                    'data' => $repository
                ]);
            }
            
            return redirect()->route('course-repository.show', $repository->pk)
                ->with('success', 'Repository updated successfully');
        } catch (Exception $e) {
            Log::error('Error updating repository: ' . $e->getMessage());
            
            // Check if AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update category: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update repository');
        }
    }

    /**
     * Delete a repository (soft delete)
     * DELETE /course-repository/{pk}
     */
    public function destroy($pk)
    {
        try {
            $repository = CourseRepositoryMaster::findOrFail($pk);
            
            $repository->update([
                'del_folder_status' => 0,
                'del_folder_date' => now(),
                'delete_by' => auth()->id(),
            ]);
            
            return redirect()->route('course-repository.index')
                ->with('success', 'Repository deleted successfully');
        } catch (Exception $e) {
            Log::error('Error deleting repository: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete repository');
        }
    }

    /**
     * Upload document
     * POST /course-repository/{pk}/upload-document
     */
    public function uploadDocument($pk, Request $request)
    {
        try {
            $validated = $request->validate([
                'file' => 'required|file|max:102400',
                'file_title' => 'nullable|string|max:5000',
                'course_repository_details_pk' => 'nullable|integer',
            ]);
            
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('course-repository', $fileName, 'public');
            
            $document = CourseRepositoryDocument::create([
                'upload_document' => $fileName,
                'course_repository_master_pk' => $pk,
                'course_repository_details_pk' => $validated['course_repository_details_pk'] ?? null,
                'file_title' => $validated['file_title'] ?? $fileName,
                'full_path' => $filePath,
                'del_type' => 1,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'document' => $document,
            ]);
        } catch (Exception $e) {
            Log::error('Error uploading document: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete document (soft delete)
     * DELETE /course-repository/document/{pk}
     */
    public function deleteDocument($pk)
    {
        try {
            $document = CourseRepositoryDocument::findOrFail($pk);
            
            $document->update([
                'del_type' => 0,
                'deleted_date' => now(),
                'deleted_by' => auth()->id(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully',
            ]);
        } catch (Exception $e) {
            Log::error('Error deleting document: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Deletion failed',
            ], 500);
        }
    }

    /**
     * Download document
     * GET /course-repository/document/{pk}/download
     */
    public function downloadDocument($pk)
    {
        try {
            $document = CourseRepositoryDocument::findOrFail($pk);
            
            if (!$document->full_path) {
                return redirect()->back()->with('error', 'File not found');
            }
            
            return Storage::disk('public')->download($document->full_path, $document->upload_document);
        } catch (Exception $e) {
            Log::error('Error downloading document: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Download failed');
        }
    }

    /**
     * Search repositories and documents
     * GET /course-repository/search
     */
    public function search(Request $request)
    {
        try {
            $keyword = $request->input('keyword', '');
            
            if (empty($keyword)) {
                return response()->json([
                    'repositories' => [],
                    'documents' => [],
                ]);
            }
            
            $repositories = CourseRepositoryMaster::where('del_folder_status', 1)
                ->where('course_repository_name', 'like', '%' . $keyword . '%')
                ->limit(10)
                ->get();
            
            $documents = CourseRepositoryDocument::where('del_type', 1)
                ->where(function($query) use ($keyword) {
                    $query->where('file_title', 'like', '%' . $keyword . '%')
                        ->orWhere('upload_document', 'like', '%' . $keyword . '%');
                })
                ->limit(10)
                ->get();
            
            return response()->json([
                'repositories' => $repositories,
                'documents' => $documents,
            ]);
        } catch (Exception $e) {
            Log::error('Error in search: ' . $e->getMessage());
            return response()->json(['error' => 'Search failed'], 500);
        }
    }

    /**
     * Get subjects by course (AJAX endpoint)
     * GET /course-repository/subjects?course_pk=X
     */
    public function getSubjectsByCourse(Request $request)
    {
        try {
            $coursePk = $request->query('course_pk');
            
            if (!$coursePk) {
                return response()->json(['success' => false, 'data' => []]);
            }

            // Get all subjects that are mapped to this course in course_repository_details
            $subjects = SubjectMaster::distinct()
                ->join('course_repository_details', 'subject_master.pk', '=', 'course_repository_details.subject_pk')
                ->where('course_repository_details.program_structure_pk', $coursePk)
                ->where('subject_master.active_inactive', 1)
                ->select('subject_master.pk', 'subject_master.subject_name')
                ->get();

            return response()->json(['success' => true, 'data' => $subjects]);
        } catch (Exception $e) {
            Log::error('Error in getSubjectsByCourse: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to load subjects'], 500);
        }
    }

    /**
     * Get topics by subject (AJAX endpoint)
     * GET /course-repository/topics?subject_pk=X
     */
    public function getTopicsBySubject(Request $request)
    {
        try {
            $subjectPk = $request->query('subject_pk');
            
            if (!$subjectPk) {
                return response()->json(['success' => false, 'data' => []]);
            }

            // Get all topics that are mapped to this subject in course_repository_details
            $topics = CourseRepositorySubtopic::distinct()
                ->join('course_repository_details', 'course_repository_subtopic.pk', '=', 'course_repository_details.topic_pk')
                ->where('course_repository_details.subject_pk', $subjectPk)
                ->select('course_repository_subtopic.pk', 'course_repository_subtopic.course_repo_topic', 'course_repository_subtopic.course_repo_sub_topic')
                ->get();

            return response()->json(['success' => true, 'data' => $topics]);
        } catch (Exception $e) {
            Log::error('Error in getTopicsBySubject: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to load topics'], 500);
        }
    }

    /**
     * Get session date by topic (AJAX endpoint)
     * GET /course-repository/session-dates?topic_pk=X
     */
    public function getSessionDateByTopic(Request $request)
    {
        try {
            $topicPk = $request->query('topic_pk');
            
            if (!$topicPk) {
                return response()->json(['success' => false, 'data' => []]);
            }

            // Get session dates for this topic from course_repository_details
            $sessionDates = CourseRepositoryDetail::where('topic_pk', $topicPk)
                ->where('status', 1)
                ->distinct()
                ->select('session_date')
                ->orderBy('session_date', 'desc')
                ->get()
                ->map(function($item) {
                    return [
                        'session_date' => $item->session_date ? $item->session_date->format('Y-m-d') : null,
                        'display' => $item->session_date ? $item->session_date->format('d-m-Y') : 'No Date'
                    ];
                });

            return response()->json(['success' => true, 'data' => $sessionDates]);
        } catch (Exception $e) {
            Log::error('Error in getSessionDateByTopic: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to load session dates'], 500);
        }
    }

    /**
     * Get authors/faculty by topic (AJAX endpoint)
     * GET /course-repository/authors-by-topic?topic_pk=X
     */
    public function getAuthorsByTopic(Request $request)
    {
        try {
            $topicPk = $request->query('topic_pk');
            
            if (!$topicPk) {
                return response()->json(['success' => false, 'data' => []]);
            }

            // Get distinct faculty/authors for this topic from course_repository_details
            $authors = FacultyMaster::distinct()
                ->join('course_repository_details', 'faculty_master.pk', '=', 'course_repository_details.author_name')
                ->where('course_repository_details.topic_pk', $topicPk)
                ->select('faculty_master.pk', 'faculty_master.full_name')
                ->get();

            return response()->json(['success' => true, 'data' => $authors]);
        } catch (Exception $e) {
            Log::error('Error in getAuthorsByTopic: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to load authors'], 500);
        }
    }

    /**
     * Get groups by course (AJAX endpoint)
     * GET /course-repository/groups?course_pk=X
     */
    public function getGroupsByCourse(Request $request)
    {
        try {
            $coursePk = $request->query('course_pk');
            
            if (!$coursePk) {
                return response()->json(['success' => false, 'data' => []]);
            }

            $groups = \DB::table('group_type_master_course_master_map as gtm')
                ->join('course_group_type_master as cgtm', 'gtm.type_name', '=', 'cgtm.pk')
                ->where('gtm.course_name', $coursePk)
                ->where('gtm.active_inactive', 1)
                ->select(
                    'gtm.pk',
                    'gtm.group_name',
                    'cgtm.type_name as group_type'
                )
                ->orderBy('gtm.group_name')
                ->get();

            return response()->json(['success' => true, 'data' => $groups]);
        } catch (Exception $e) {
            Log::error('Error fetching groups: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to load groups'], 500);
        }
    }

    /**
     * Get timetables by group (AJAX endpoint)
     * GET /course-repository/timetables?group_pk=X
     */
    public function getTimetablesByGroup(Request $request)
    {
        try {
            $groupPk = $request->query('group_pk');
            $course_master_pk = $request->query('course_master_pk');
            
            if (!$groupPk) {
                return response()->json(['success' => false, 'data' => []]);
            }

            $timetables = \DB::table('timetable as t')
                ->where('t.course_group_type_master', $groupPk)
                ->where('t.course_master_pk', $course_master_pk)
                ->select(
                    't.pk',
                    't.subject_topic',
                    't.START_DATE',
                    't.END_DATE',
                    't.class_session'
                )
                ->orderBy('t.START_DATE')
                ->orderBy('t.START_DATE')
                ->get()
                ->map(function($item) {
                    $item->display = $item->subject_topic . ' (' . date('d-m-Y', strtotime($item->event_date)) . ' ' . date('h:i A', strtotime($item->start_time)) . ')';
                    return $item;
                });

            return response()->json(['success' => true, 'data' => $timetables]);
        } catch (Exception $e) {
            Log::error('Error fetching timetables: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to load timetables'], 500);
        }
    }
}
