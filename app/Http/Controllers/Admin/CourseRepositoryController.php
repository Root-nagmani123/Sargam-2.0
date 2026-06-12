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
use App\Models\SectorMaster;
use App\Models\MinistryMaster;
use App\Models\Timetable;
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
            $perPage = (int) $request->input('per_page', 15);
            $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

            if ($parentPk) {
                // Show children of specific parent
                $parentRepository = CourseRepositoryMaster::findOrFail($parentPk);
                $repositories = $parentRepository->children()
                    ->with(['children', 'documents'])
                    ->orderBy('created_date', 'desc')
                    ->paginate($perPage)
                    ->withQueryString();
                   
            
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
                // Show only root repositories (no parent or parent_type = 0)
                $repositories = CourseRepositoryMaster::where('del_folder_status', 1)
                    ->where(function($query) {
                        $query->whereNull('parent_type')
                              ->orWhere('parent_type', 0);
                    })
                    ->with(['children', 'documents'])
                    ->orderBy('created_date', 'desc')
                    ->paginate($perPage)
                    ->withQueryString();
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
                'perPage' => $perPage,
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
                // print_r($documents); exit;

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
                'activeCourses' => CourseMaster::where('active_inactive', 1)->get(),
                'archivedCourses' => CourseMaster::where('active_inactive', 0)->get(),
                'subjects' => SubjectMaster::where('active_inactive', 1)->get(),
                'topics' => CourseRepositorySubtopic::all(),
                'authors' => FacultyMaster::select('pk','full_name')->get(),
                'sectors' => SectorMaster::active()->get(),
                'ministries' => MinistryMaster::active()->get(),
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
                'category_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'category_attachment' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,doc,docx,xls,xlsx|max:5120',
            ]);

            $validated['created_date'] = now();
            $validated['created_by'] = auth()->id();
            $validated['status'] = 1;
            $validated['del_folder_status'] = 1;
            
            // If parent_type not provided or empty, set to null (root repository)
            if (!isset($validated['parent_type']) || empty($validated['parent_type'])) {
                $validated['parent_type'] = null;
            }
            
            // Handle image upload
            if ($request->hasFile('category_image')) {
                $image = $request->file('category_image');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->storeAs('course-repository/categories', $imageName, 'public');
                $validated['category_image'] = 'course-repository/categories/' . $imageName;
            }

            // Handle attachment upload
            if ($request->hasFile('category_attachment')) {
                $attachment = $request->file('category_attachment');
                $attachmentName = time() . '_' . uniqid() . '.' . $attachment->getClientOriginalExtension();
                $attachment->storeAs('course-repository/attachments', $attachmentName, 'public');
                $validated['category_attachment'] = 'course-repository/attachments/' . $attachmentName;
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
                'category_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'category_attachment' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf,doc,docx,xls,xlsx|max:5120',
            ]);

            $validated['modify_date'] = now();
            $validated['modify_by'] = auth()->id();
            
            // Handle image upload
            if ($request->hasFile('category_image')) {
                // Delete old image if exists
                if (filled($repository->category_image) && \Storage::disk('public')->exists($repository->category_image)) {
                    \Storage::disk('public')->delete($repository->category_image);
                }
                
                // Store new image
                $image = $request->file('category_image');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->storeAs('course-repository/categories', $imageName, 'public');
                $validated['category_image'] = 'course-repository/categories/' . $imageName;
            }

            // Handle attachment upload
            if ($request->hasFile('category_attachment')) {
                // Delete old attachment if exists
                if (filled($repository->category_attachment) && \Storage::disk('public')->exists($repository->category_attachment)) {
                    \Storage::disk('public')->delete($repository->category_attachment);
                }

                // Store new attachment
                $attachment = $request->file('category_attachment');
                $attachmentName = time() . '_' . uniqid() . '.' . $attachment->getClientOriginalExtension();
                $attachment->storeAs('course-repository/attachments', $attachmentName, 'public');
                $validated['category_attachment'] = 'course-repository/attachments/' . $attachmentName;
            }

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
     * 
     * Handles form submission from upload modal:
     * - Inserts metadata into course_repository_details
     * - Uploads files and inserts into course_repository_documents
     */
    public function uploadDocument($pk, Request $request)
    {
        try {
            $validated = $request->validate([
                'category' => 'required|string|in:Course,Other,Institutional',
                'course_name' => 'nullable|string',
                'subject_name' => 'nullable|string',
                'timetable_name' => 'nullable|string',
                'session_date' => 'nullable|string',
                'author_name' => 'nullable|string',
                'sector_master' => 'nullable|numeric',
                'ministry_master' => 'nullable|numeric',
                'attachments' => 'nullable|array',
                'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10120', // Max 10MB per file
                'attachment_titles' => 'nullable|array',
                'attachment_titles.*' => 'nullable|string|max:5000',
                'keywords' => 'nullable|string|max:4000',
                'video_link' => 'nullable|string|max:2000',
            ]);
            
            // Get the category
            $category = $validated['category'];
            
            // Get parent course_repository_master to fetch its type
            $parent = CourseRepositoryMaster::findOrFail($pk);
            
            // Build folder hierarchy path from ancestors
            $folderPath = $this->buildFolderPath($parent);
            
            // Step 1: Insert data into course_repository_details table
            $details = CourseRepositoryDetail::create([
                'course_repository_master_pk' => $pk,
                'course_repository_type' =>$parent->parent_type,
                'course_master_pk' => $validated['course_name'] ?? null,
                'subject_pk' => $validated['subject_name'] ?? null,
                'topic_pk' => $validated['timetable_name'] ?? null,
                'session_date' => $validated['session_date'] ?? null,
                'author_name' => $validated['author_name'] ?? null,
                'sector_master_pk' => $validated['sector_master'] ?? null,
                'ministry_master_pk' => $validated['ministry_master'] ?? null,
                'keyword' => $validated['keywords'] ?? null,
                'videolink' => $validated['video_link'] ?? null,
                'created_date' => now(),
                'created_by' => auth()->id(),
                'status' => 1,
                'type' => $category === 'Course' ? 'CO' : ($category === 'Other' ? 'OT' : 'IN'),
            ]);
            
            // Step 2: Upload and insert documents
            if ($request->hasFile('attachments')) {
                $files = $request->file('attachments');
                $titles = $validated['attachment_titles'] ?? [];
                
                foreach ($files as $index => $file) {
                    if ($file && $file->isValid()) {
                        // Generate unique filename
                        $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                        
                        // Store file in hierarchical folder structure under admin root
                        $storageFolder = trim($folderPath, '/');
                        $filePath = $file->storeAs($storageFolder, $fileName, 'public');
                        
                        // Insert into course_repository_documents
                        CourseRepositoryDocument::create([
                            'upload_document' => $fileName,
                            'course_repository_master_pk' => $pk,
                            'course_repository_details_pk' => $details->pk,
                            'course_repository_type' => $parent->parent_type,
                            'file_title' => $titles[$index] ?? $file->getClientOriginalName(),
                            'full_path' => $filePath,
                            'del_type' => 1, // 1 = active, 0 = deleted
                        ]);
                    }
                }
            }
            
            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Documents uploaded and data saved successfully',
                'detail_pk' => $details->pk,
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in uploadDocument: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error uploading document: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
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
     * Return a single document plus its shared detail metadata, so the edit
     * modal (a reuse of the upload form) can be pre-populated.
     * GET /course-repository/document/{pk}/edit-data
     */
    public function getDocument($pk)
    {
        try {
            $document = CourseRepositoryDocument::with([
                'detail',
                'detail.course',
                'detail.subject',
                'detail.topic',
                'detail.author',
                'detail.sector',
                'detail.ministry',
            ])->findOrFail($pk);
            $detail = $document->detail;

            // Map stored detail type back to the upload form's category labels
            $typeMap = ['CO' => 'Course', 'OT' => 'Other', 'IN' => 'Institutional'];
            $category = $detail ? ($typeMap[$detail->type] ?? 'Course') : 'Course';

            return response()->json([
                'success' => true,
                'data' => [
                    'pk' => $document->pk,
                    'file_title' => $document->file_title,
                    'upload_document' => $document->upload_document,
                    'category' => $category,
                    'detail' => $detail ? [
                        'course_master_pk' => $detail->course_master_pk,
                        'course_name' => optional($detail->course)->course_name,
                        'subject_pk' => $detail->subject_pk,
                        'subject_name' => optional($detail->subject)->subject_name,
                        'topic_pk' => $detail->topic_pk,
                        'topic_name' => optional($detail->topic)->subject_topic
                            ?? optional($detail->topic)->course_repo_topic,
                        'session_date' => $detail->session_date
                            ? $detail->session_date->format('Y-m-d')
                            : null,
                        'author_name' => $detail->author_name,
                        'author_label' => optional($detail->author)->full_name,
                        'sector_master_pk' => $detail->sector_master_pk,
                        'sector_name' => optional($detail->sector)->sector_name,
                        'ministry_master_pk' => $detail->ministry_master_pk,
                        'ministry_name' => optional($detail->ministry)->ministry_name,
                        'keyword' => $detail->keyword,
                        'videolink' => $detail->videolink,
                    ] : null,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching document for edit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Could not load document',
            ], 500);
        }
    }

    /**
     * Update a document's title/file and its shared detail metadata.
     * POST /course-repository/document/{pk}/update
     */
    public function updateDocument($pk, Request $request)
    {
        try {
            $validated = $request->validate([
                'category' => 'nullable|string|in:Course,Other,Institutional',
                'file_title' => 'nullable|string|max:5000',
                'document_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10120', // Max 10MB
                'course_name' => 'nullable|string',
                'subject_name' => 'nullable|string',
                'timetable_name' => 'nullable|string',
                'session_date' => 'nullable|string',
                'author_name' => 'nullable|string',
                'sector_master' => 'nullable|numeric',
                'ministry_master' => 'nullable|numeric',
                'keywords' => 'nullable|string|max:4000',
                'video_link' => 'nullable|string|max:2000',
            ]);

            $document = CourseRepositoryDocument::findOrFail($pk);

            // ---- Document-level fields (title + optional file replacement) ----
            $document->file_title = $validated['file_title'] ?? $document->file_title;

            if ($request->hasFile('document_file')) {
                $file = $request->file('document_file');
                if ($file->isValid()) {
                    // Store in the same hierarchical folder used at upload time
                    $parent = CourseRepositoryMaster::find($document->course_repository_master_pk);
                    $storageFolder = $parent
                        ? trim($this->buildFolderPath($parent), '/')
                        : 'course_repository';

                    $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs($storageFolder, $fileName, 'public');

                    // Remove the old physical file if we can resolve it
                    $oldRelative = $this->resolveDocumentRelativePath($document);
                    if ($oldRelative && Storage::disk('public')->exists($oldRelative)) {
                        Storage::disk('public')->delete($oldRelative);
                    }

                    $document->upload_document = $fileName;
                    $document->full_path = $filePath;
                }
            }

            $document->save();

            // ---- Shared detail-level metadata ----
            $detail = $document->detail;
            if ($detail) {
                $category = $validated['category'] ?? null;
                $typeMap = ['Course' => 'CO', 'Other' => 'OT', 'Institutional' => 'IN'];

                $detail->course_master_pk = $validated['course_name'] ?? $detail->course_master_pk;
                $detail->subject_pk = $validated['subject_name'] ?? $detail->subject_pk;
                $detail->topic_pk = $validated['timetable_name'] ?? $detail->topic_pk;
                $detail->session_date = $validated['session_date'] ?? $detail->session_date;
                $detail->author_name = $validated['author_name'] ?? $detail->author_name;
                $detail->sector_master_pk = $validated['sector_master'] ?? $detail->sector_master_pk;
                $detail->ministry_master_pk = $validated['ministry_master'] ?? $detail->ministry_master_pk;
                $detail->keyword = $validated['keywords'] ?? $detail->keyword;
                $detail->videolink = $validated['video_link'] ?? $detail->videolink;
                if ($category && isset($typeMap[$category])) {
                    $detail->type = $typeMap[$category];
                }
                $detail->modify_by = auth()->id();
                $detail->modify_date = now();
                $detail->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in updateDocument: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error updating document: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Update failed: ' . $e->getMessage(),
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
            
            if (!$document->full_path && !$document->normalized_full_path) {
                return redirect()->back()->with('error', 'File not found');
            }

            $relativePath = $this->resolveDocumentRelativePath($document);
            if (!$relativePath) {
                Log::error('Course repository file not found', [
                    'document_pk' => $document->pk,
                    'full_path' => $document->full_path,
                    'normalized_full_path' => $document->normalized_full_path,
                ]);
                return redirect()->back()->with('error', 'File not found in storage');
            }
            
            // Get original filename without timestamp prefix
            $originalName = preg_replace('/^\d+_[a-f0-9]+_/', '', $document->upload_document);
            
            return Storage::disk('public')->download($relativePath, $originalName);
        } catch (Exception $e) {
            Log::error('Error downloading document: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Download failed: ' . $e->getMessage());
        }
    }

    /**
     * Resolve file path variations and return a valid relative path for public disk.
     */
    private function resolveDocumentRelativePath(CourseRepositoryDocument $document): ?string
    {
        $candidates = [];

        if ($document->normalized_full_path) {
            $candidates[] = ltrim(str_replace('\\', '/', $document->normalized_full_path), '/');
        }

        if ($document->full_path) {
            $rawPath = trim((string) $document->full_path);
            $rawPath = preg_replace('#^https?://[^/]+/#i', '', $rawPath);
            $rawPath = str_replace('\\', '/', $rawPath);
            $rawPath = preg_replace('#^/?storage/app/public/#', '', $rawPath);
            $rawPath = preg_replace('#^/?storage/#', '', $rawPath);
            $rawPath = preg_replace('#^/?app/public/#', '', $rawPath);
            $rawPath = ltrim($rawPath, '/');
            if ($rawPath !== '') {
                $candidates[] = $rawPath;
            }
        }

        $candidates = array_values(array_unique(array_filter($candidates)));

        // Backward compatibility: many old rows don't include base repository folder
        // in full_path, while files are physically stored under one of these roots.
        $prefixedCandidates = [];
        foreach ($candidates as $candidate) {
            $normalizedCandidate = ltrim(str_replace('\\', '/', $candidate), '/');

            $prefixedCandidates[] = $candidate;
            $prefixedCandidates[] = 'course_repository/' . $normalizedCandidate;
            $prefixedCandidates[] = 'course-repository/' . $normalizedCandidate;
            $prefixedCandidates[] = 'Course Repository/' . $normalizedCandidate;

            // Base folder replacement fallbacks (important for env mismatch)
            $prefixedCandidates[] = preg_replace('#^course_repository/#', 'course-repository/', $normalizedCandidate);
            $prefixedCandidates[] = preg_replace('#^course-repository/#', 'course_repository/', $normalizedCandidate);
            $prefixedCandidates[] = preg_replace('#^Course Repository/#', 'course-repository/', $normalizedCandidate);
            $prefixedCandidates[] = preg_replace('#^Course Repository/#', 'course_repository/', $normalizedCandidate);
        }
        $candidates = array_values(array_unique(array_filter($prefixedCandidates)));

        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || trim($candidate) === '') {
                continue;
            }
            if (Storage::disk('public')->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
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
     * Get all courses (AJAX endpoint)
     * GET /course-repository/courses
     */
    public function getCourses()
    {
        try {
            $courses = CourseMaster::where('course_active_inactive', 1)
                ->select('pk', 'course_name')
                ->orderBy('course_name')
                ->get();

            return response()->json(['success' => true, 'data' => $courses]);
        } catch (Exception $e) {
            Log::error('Error in getCourses: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to load courses'], 500);
        }
    }

    /**
     * Get subjects by course (AJAX endpoint)
     * GET /course-repository/subjects/{coursePk}
     */
    public function getSubjectsByCourse($coursePk = null, Request $request = null)
    {
        try {
            // Support both route parameter and query parameter for flexibility
            if (!$coursePk && $request) {
                $coursePk = $request->query('course_pk');
            }
            
            if (!$coursePk) {
                return response()->json(['success' => false, 'data' => []], 422);
            }

            // Get all subjects that are mapped to this course in course_repository_details
             $subjects = SubjectMaster::distinct()
                ->join('timetable', 'subject_master.pk', '=', 'timetable.subject_master_pk')
                ->where('timetable.course_master_pk', $coursePk)
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
     * GET /course-repository/topics/{subjectPk}?course_master_pk={coursePk}
     */
    public function getTopicsBySubject($subjectPk = null, Request $request = null)
    {
        try {
            // Support both route parameter and query parameter for flexibility
            if (!$subjectPk && $request) {
                $subjectPk = $request->query('subject_pk');
            }
            
            // Get coursePk from query parameter
            $coursePk = $request ? $request->query('course_master_pk') : null;
            
            if (!$subjectPk) {
                return response()->json(['success' => false, 'data' => []], 422);
            }

            // Get all topics that are mapped to this subject in course_repository_details
            $query = Timetable::distinct()
                ->leftJoin('faculty_master', 'timetable.faculty_master', '=', 'faculty_master.pk')
                ->where('timetable.subject_master_pk', $subjectPk);
            
            // Only filter by course if provided
            if ($coursePk) {
                $query->where('timetable.course_master_pk', $coursePk);
            }
            
            $topics = $query->select('timetable.pk', 'timetable.subject_topic', 'faculty_master.full_name as faculty_name', 'timetable.START_DATE')
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
            $sessionDates = Timetable::where('pk', $topicPk)
                ->distinct()
                ->select('START_DATE')
                ->orderBy('START_DATE', 'desc')
                ->get()
                ->map(function($item) {
                    $date = $item->START_DATE;
                    
                    // Convert string to Carbon if needed
                    if (is_string($date)) {
                        try {
                            $date = \Carbon\Carbon::createFromFormat('Y-m-d', $date) 
                                 ?: \Carbon\Carbon::parse($date);
                        } catch (\Exception $e) {
                            return [
                                'session_date' => null,
                                'display' => 'No Date'
                            ];
                        }
                    }
                    
                    return [
                        'session_date' => $date ? $date->format('Y-m-d') : null,
                        'display' => $date ? $date->format('d-m-Y') : 'No Date'
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
                ->join('timetable', 'faculty_master.pk', '=', 'timetable.faculty_master')
                ->where('timetable.pk', $topicPk)
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

            $groups = \DB::table('timetable as t')
                ->join('subject_master as sm', 't.subject_master_pk', '=', 'sm.pk')
                ->where('t.course_master_pk', $coursePk)
                ->where('t.active_inactive', 1)
                ->select(
                    'sm.pk',
                    'sm.subject_name'
                )
                ->groupBy('sm.pk', 'sm.subject_name')
                ->orderBy('sm.subject_name')
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
            $subjectPk = $request->query('group_pk'); // Actually subject_master_pk
            $coursePk = $request->query('course_master_pk');
            
            if (!$subjectPk || !$coursePk) {
                return response()->json(['success' => false, 'data' => []]);
            }

            $timetables = \DB::table('timetable as t')
                ->leftJoin('faculty_master as fm', 't.faculty_master', '=', 'fm.pk')
                ->where('t.subject_master_pk', $subjectPk)
                ->where('t.course_master_pk', $coursePk)
                ->where('t.active_inactive', 1)
                ->select(
                    't.pk',
                    't.subject_topic',
                    't.START_DATE',
                    't.END_DATE',
                    't.class_session',
                    'fm.full_name as faculty_name'
                )
                ->orderBy('t.START_DATE', 'desc')
                ->get()
                ->map(function($item) {
                    $dateStr = $item->START_DATE ? date('d-m-Y', strtotime($item->START_DATE)) : '';
                    $facultyStr = $item->faculty_name ? ' - ' . $item->faculty_name : '';
                    $item->display = $item->subject_topic . ' (' . $dateStr . ')' . $facultyStr;
                    return $item;
                });

            return response()->json(['success' => true, 'data' => $timetables]);
        } catch (Exception $e) {
            Log::error('Error fetching timetables: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to load timetables'], 500);
        }
    }

    /**
     * Get ministries by sector (AJAX endpoint)
     * GET /course-repository/ministries?sector_pk=X
     */
    public function getMynostriesBySector(Request $request)
    {
        try {
            $sectorPk = $request->query('sector_pk');
            
            if (!$sectorPk) {
                return response()->json(['success' => false, 'message' => 'Sector PK is required'], 400);
            }

            $ministries = MinistryMaster::where('sector_master_pk', $sectorPk)
                ->where('status', 1)
                ->orderBy('ministry_name')
                ->get(['pk', 'ministry_name']);

            return response()->json(['success' => true, 'data' => $ministries]);
        } catch (Exception $e) {
            Log::error('Error fetching ministries: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error fetching ministries'], 500);
        }
    }

    /**
     * Build folder hierarchy path from parent repository
     * Example: "course_repository/Central Course Repository of LBSNAA/Foundation Course/FC-89/Class Materials"
     */
    private function buildFolderPath($repository)
    {
        $hierarchyParts = [];
        $current = $repository;
        
        // Traverse up the hierarchy to build path
        while ($current) {
            // Sanitize folder name (remove special characters that are problematic for file systems)
            $folderName = preg_replace('/[^\w\s\-()]/', '', (string) $current->course_repository_name);
            $folderName = trim($folderName);

            if ($folderName !== '') {
                array_unshift($hierarchyParts, $folderName);
            }
            
            // Get parent if exists
            if (!empty($current->parent_type)) {
                $current = CourseRepositoryMaster::find($current->parent_type);
            } else {
                break;
            }
        }

        $pathParts = array_merge(['course_repository'], $hierarchyParts);
        return implode('/', array_filter($pathParts));
    }

    /**
     * User-facing course repository index page
     * GET /course-repository-user
     */
    public function userIndex(Request $request)
    {
        try {
            $filters = $this->getFilters($request);

            // Get root repositories (main course categories)
            $query = CourseRepositoryMaster::where('del_folder_status', 1)
                ->whereNull('parent_type')
                ->with(['children', 'documents']);

            // Apply filters if provided
            if ($date || $coursePk || $subjectPk || $week || $facultyPk) {
                // Filter repositories that have documents matching the criteria
                $query->whereHas('documents', function($q) use ($date, $coursePk, $subjectPk, $week, $facultyPk) {
                    $q->where('del_type', 1);

                    if ($coursePk || $subjectPk || $date || $week || $facultyPk) {
                        $q->whereHas('detail', function($detailQuery) use ($date, $coursePk, $subjectPk, $week, $facultyPk) {
                            if ($coursePk) {
                                $detailQuery->where('course_master_pk', $coursePk);
                            }
                            if ($subjectPk) {
                                $detailQuery->where('subject_pk', $subjectPk);
                            }
                            if ($date) {
                                $detailQuery->whereDate('session_date', $date);
                            }
                            if ($week) {
                                $detailQuery->whereRaw('WEEK(session_date, 3) = ?', [$week]);
                            }
                            if ($facultyPk) {
                                $detailQuery->where('author_name', $facultyPk);
                            }
                        });
                });
            }
            
            $repositories = $query->orderBy('created_date', 'desc')->get();

            return view('admin.course-repository.user.index', array_merge(
                $this->getUserFilterViewData($request),
                ['repositories' => $repositories]
            ));
        } catch (Exception $e) {
            Log::error('Error in course repository user index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load course repository');
        }
    }

    /**
     * Foundation Course listing page
     */
    public function foundationCourse(Request $request)
    {
        try {
            // Get Foundation Course repositories
            $repositories = CourseRepositoryMaster::where('del_folder_status', 1)
                ->where('course_repository_name', 'like', 'Foundation Course%')
                ->whereNull('parent_type')
                ->with(['children', 'documents'])
                ->orderBy('created_date', 'desc')
                ->get();

            return view('admin.course-repository.user.foundation-course', array_merge(
                $this->getUserFilterViewData($request),
                ['repositories' => $repositories]
            ));
        } catch (Exception $e) {
            Log::error('Error in foundation course: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load foundation course');
        }
    }

    /**
     * Foundation Course detail page (e.g., FC-89)
     */
    public function foundationCourseDetail(Request $request, $courseCode)
    {
        try {
            // Get course repositories for this course code
            $repositories = CourseRepositoryMaster::where('del_folder_status', 1)
                ->where('course_repository_name', 'like', '%' . $courseCode . '%')
                ->with(['children', 'documents'])
                ->orderBy('created_date', 'desc')
                ->get();

            return view('admin.course-repository.user.foundation-course-detail', array_merge(
                $this->getUserFilterViewData($request),
                [
                    'courseCode' => $courseCode,
                    'repositories' => $repositories,
                ]
            ));
        } catch (Exception $e) {
            Log::error('Error in foundation course detail: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load course detail');
        }
    }

    /**
     * Class Material (Subject Wise) page
     */
    public function classMaterialSubjectWise(Request $request, $courseCode)
    {
        try {
            // Get material cards
            $materialCards = CourseRepositoryMaster::where('del_folder_status', 1)
                ->where('course_repository_name', 'like', '%' . $courseCode . '%')
                ->with(['children', 'documents'])
                ->get();

            // Get subjects list
            $subjectsList = SubjectMaster::where('active_inactive', 1)
                ->orderBy('subject_name')
                ->get();

            return view('admin.course-repository.user.class-material-subject-wise', array_merge(
                $this->getUserFilterViewData($request),
                [
                    'courseCode' => $courseCode,
                    'materialCards' => $materialCards,
                    'subjectsList' => $subjectsList,
                ]
            ));
        } catch (Exception $e) {
            Log::error('Error in class material subject wise: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load class materials');
        }
    }

    /**
     * Class Material (Week Wise) page
     */
    public function classMaterialWeekWise(Request $request, $courseCode)
    {
        try {
            // Get material cards
            $materialCards = CourseRepositoryMaster::where('del_folder_status', 1)
                ->where('course_repository_name', 'like', '%' . $courseCode . '%')
                ->with(['children', 'documents'])
                ->get();

            // Generate weeks list (1-52)
            $weeks = [];
            for ($i = 1; $i <= 52; $i++) {
                $weeks[] = [
                    'number' => $i,
                    'label' => 'Week-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                ];
            }

            return view('admin.course-repository.user.class-material-week-wise', array_merge(
                $this->getUserFilterViewData($request),
                [
                    'courseCode' => $courseCode,
                    'materialCards' => $materialCards,
                    'weeks' => $weeks,
                ]
            ));
        } catch (Exception $e) {
            Log::error('Error in class material week wise: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load class materials');
        }
    }

    /**
     * Week detail page
     */
    public function weekDetail(Request $request, $courseCode, $weekNumber)
    {
        try {
            $filters = $this->getFilters($request);

            // Get documents for this week
            $documentsQuery = CourseRepositoryDetail::whereHas('master', function ($query) use ($courseCode) {
                    $query->where('course_repository_name', 'like', '%' . $courseCode . '%');
                })
                ->with(['master', 'documents', 'author', 'subject', 'course', 'topic']);

            if ($this->hasActiveUserFilters($filters)) {
                $documentsQuery->where(function ($query) use ($filters) {
                    $this->applyUserDetailFilters($query, $filters);
                });
            }

            $documents = $documentsQuery->get();

            return view('admin.course-repository.user.week-detail', array_merge(
                $this->getUserFilterViewData($request),
                [
                    'courseCode' => $courseCode,
                    'weekNumber' => $weekNumber,
                    'documents' => $documents,
                ]
            ));
        } catch (Exception $e) {
            Log::error('Error in week detail: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load week details');
        }
    }

    /**
     * Document details (for modal)
     */
    public function documentDetails($documentId)
    {
        try {
            $document = CourseRepositoryDetail::with(['author', 'subject', 'course', 'topic'])
                ->findOrFail($documentId);

            return response()->json([
                'success' => true,
                'document' => [
                    'author' => $document->author ? $document->author->full_name : 'N/A',
                    'subject' => $document->subject ? $document->subject->subject_name : 'N/A',
                    'topic' => $document->topic ? $document->topic->subject_topic : 'N/A',
                    'keyword' => $document->keyword ?? 'N/A',
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Error fetching document details: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to load document details'], 500);
        }
    }

    /**
     * Document view (PDF viewer)
     */
    public function documentView($documentId)
    {
        try {
            $document = CourseRepositoryDetail::with(['documents', 'author', 'subject'])
                ->findOrFail($documentId);

            $pdfDocument = $document->documents->first();
            if (!$pdfDocument) {
                return redirect()->back()->with('error', 'Document not found');
            }

            $pdfRelativePath = $this->resolveDocumentRelativePath($pdfDocument);
            $pdfViewUrl = $pdfRelativePath
                ? Storage::disk('public')->url($pdfRelativePath)
                : $pdfDocument->public_file_url;

            return view('admin.course-repository.user.document-view', [
                'document' => $document,
                'pdfDocument' => $pdfDocument,
                'pdfViewUrl' => $pdfViewUrl,
            ]);
        } catch (Exception $e) {
            Log::error('Error viewing document: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load document');
        }
    }

    /**
     * Document video
     */
    public function documentVideo($documentId)
    {
        try {
            $document = CourseRepositoryDetail::with(['documents', 'author'])
                ->findOrFail($documentId);

            return view('admin.course-repository.user.document-video', [
                'document' => $document,
            ]);
        } catch (Exception $e) {
            Log::error('Error loading video: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to load video');
        }
    }

    /**
     * User-facing repository view page
     * GET /course-repository-user/{pk}
     */
    public function userShow(Request $request, $pk)
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
          
            // List repository details (each may have files and/or a video link)
            $documentsQuery = CourseRepositoryDetail::where('course_repository_master_pk', $pk)
                ->whereHas('documents', function ($query) {
                    $query->where('del_type', 1);
                })
                ->with([
                    'documents' => function ($query) {
                        $query->where('del_type', 1)->orderBy('pk', 'desc');
                    },
                    'course',
                    'subject',
                    'topic',
                    'author',
                ]);

            // Apply filters if provided
            $date = $request->query('date');
            $coursePk = $request->query('course');
            $subjectPk = $request->query('subject');
            $week = $request->query('week');
            $facultyPk = $request->query('faculty');

            if ($date || $coursePk || $subjectPk || $week || $facultyPk) {
                $documentsQuery->whereHas('detail', function($detailQuery) use ($date, $coursePk, $subjectPk, $week, $facultyPk) {
                    if ($coursePk) {
                        $detailQuery->where('course_master_pk', $coursePk);
                    }
                    if ($subjectPk) {
                        $detailQuery->where('subject_pk', $subjectPk);
                    }
                    if ($date) {
                        $detailQuery->whereDate('session_date', $date);
                    }
                    if ($week) {
                        $detailQuery->whereRaw('WEEK(session_date, 3) = ?', [$week]);
                    }
                    if ($facultyPk) {
                        $detailQuery->where('author_name', $facultyPk);
                    }
                });
            }

            $documents = $documentsQuery
                ->orderBy('pk', 'desc')
                ->get();

            // Build ancestor chain for breadcrumb
            $ancestors = [];
            $current = $repository;
            while ($current && $current->parent) {
                array_unshift($ancestors, $current->parent);
                $current = $current->parent;
            }
           
            return view('admin.course-repository.user.show', [
                'repository' => $repository,
                'documents' => $documents,
                'ancestors' => $ancestors,
                'documents_count_array' => $documents_count_array,
                'courses' => $courses,
                'subjects' => $subjects,
                'faculties' => $faculties,
                'filters' => [
                    'date' => $date,
                    'course' => $coursePk,
                    'subject' => $subjectPk,
                    'week' => $week,
                    'faculty' => $facultyPk,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Error in course repository user show: ' . $e->getMessage());
            return redirect()->route('admin.course-repository.user.index')->with('error', 'Repository not found');
        }
    }

    /**
     * Helper method to get filters from request
     */
    private function getFilters(Request $request)
    {
        return [
            'date' => $request->query('date'),
            'course' => $request->query('course'),
            'subject' => $request->query('subject'),
            'week' => $request->query('week'),
            'faculty' => $request->query('faculty'),
            'sector' => $request->query('sector'),
            'ministry' => $request->query('ministry'),
            'search' => $request->query('search'),
        ];
    }

    /**
     * Dropdown data and filter state for user-facing course repository views.
     */
    private function getUserFilterViewData(Request $request): array
    {
        $filters = $this->getFilters($request);
        $sectorPk = $filters['sector'] ?? null;

        $ministries = collect();
        if ($sectorPk) {
            $ministries = MinistryMaster::where('sector_master_pk', $sectorPk)
                ->where('status', 1)
                ->orderBy('ministry_name')
                ->get(['pk', 'ministry_name']);
        }

        return [
            'courses' => CourseMaster::where('active_inactive', 1)->orderBy('course_name')->get(),
            'subjects' => SubjectMaster::where('active_inactive', 1)->orderBy('subject_name')->get(),
            'faculties' => FacultyMaster::select('pk', 'full_name')
                ->whereNotNull('full_name')
                ->orderBy('full_name')
                ->get(),
            'sectors' => SectorMaster::active()->get(),
            'ministries' => $ministries,
            'filters' => $filters,
        ];
    }

    private function hasActiveUserFilters(array $filters): bool
    {
        return !empty($filters['date'])
            || !empty($filters['course'])
            || !empty($filters['subject'])
            || !empty($filters['week'])
            || !empty($filters['faculty'])
            || !empty($filters['sector'])
            || !empty($filters['ministry'])
            || !empty($filters['search']);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $detailQuery
     */
    private function applyUserDetailFilters($detailQuery, array $filters): void
    {
        if (!empty($filters['course'])) {
            $detailQuery->where('course_master_pk', $filters['course']);
        }
        if (!empty($filters['subject'])) {
            $detailQuery->where('subject_pk', $filters['subject']);
        }
        if (!empty($filters['date'])) {
            $detailQuery->whereDate('session_date', $filters['date']);
        }
        if (!empty($filters['faculty'])) {
            $detailQuery->where('author_name', $filters['faculty']);
        }
        if (!empty($filters['sector'])) {
            $detailQuery->where('sector_master_pk', $filters['sector']);
        }
        if (!empty($filters['ministry'])) {
            $detailQuery->where('ministry_master_pk', $filters['ministry']);
        }
        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $detailQuery->where(function ($q) use ($term) {
                $q->where('keyword', 'like', $term)
                    ->orWhereHas('course', function ($c) use ($term) {
                        $c->where('course_name', 'like', $term);
                    })
                    ->orWhereHas('subject', function ($s) use ($term) {
                        $s->where('subject_name', 'like', $term);
                    })
                    ->orWhereHas('topic', function ($t) use ($term) {
                        $t->where('subject_topic', 'like', $term);
                    })
                    ->orWhereHas('author', function ($a) use ($term) {
                        $a->where('full_name', 'like', $term);
                    });
            });
        }
    }
}
