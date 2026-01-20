@extends('admin.layouts.master')

@section('title', ($repository->course_repository_name ?? 'Repository Details') . ' | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb Navigation -->
    <div class="mb-3">
        <span class="text-muted">
            <a href="{{ route('course-repository.index') }}" class="text-decoration-none text-muted">Academics</a>
            <span class="mx-2">></span>
            <a href="{{ route('course-repository.index') }}" class="text-decoration-none text-muted">MCTP</a>
            <span class="mx-2">></span>
            <a href="{{ route('course-repository.index') }}" class="text-decoration-none text-muted">Course Repository Admin</a>
            @if (!empty($ancestors))
                @foreach ($ancestors as $ancestor)
                    <span class="mx-2">></span>
                    <a href="{{ route('course-repository.show', $ancestor->pk) }}" class="text-decoration-none text-muted">
                        {{ $ancestor->course_repository_name }}
                    </a>
                @endforeach
            @endif
            <span class="mx-2">></span>
            <span class="text-danger fw-bold">{{ $repository->course_repository_name }}</span>
        </span>
    </div>

    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="mb-0"><strong>{{ $repository->course_repository_name }}</strong></h4>
                        <p class="text-muted mt-2 mb-0">
                            <small>
                                Created: {{ $repository->created_date ? $repository->created_date->format('d-m-Y H:i') : 'N/A' }}
                            </small>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="javascript:void(0)" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                            <i class="fas fa-plus"></i> Add New Category
                        </a>
                        <a href="javascript:void(0)" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-upload"></i> Upload Documents
                        </a>
                    </div>
                </div>
                <hr>
                
                @if($repository->children->count() == 0 && $documents->count() == 0)
                    <!-- Empty State -->
                    <div class="text-center py-5">
                        <i class="fas fa-inbox" style="font-size: 48px; color: #ccc;"></i>
                        <p class="text-muted mt-3">No sub-categories or documents found. Start by adding a category or uploading a document.</p>
                    </div>
                @else
                    <!-- Child Repositories Section -->
                    @if($repository->children->count() > 0)
                    <div class="table-responsive mb-4">
                        <table class="table text-nowrap mb-0" id="child_repositories">
                            <thead>
                                <tr>
                                    <th class="col text-center">S.No.</th>
                                    <th class="col text-center">Sub Category Name</th>
                                    <th class="col text-center">Details</th>
                                    <th class="col text-center">Sub-Categories</th>
                                    <th class="col text-center">Documents</th>
                                    <th class="col text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($repository->children as $index => $child)
                                <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('course-repository.show', $child->pk) }}" class="text-decoration-none">
                                            <strong>{{ $child->course_repository_name }}</strong>
                                        </a>
                                    </td>
                                    <td class="text-center">{{ Str::limit($child->course_repository_details ?? 'N/A', 50) }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary rounded-pill">{{ $child->children->count() }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success rounded-pill">{{ $child->getDocumentCount() }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center align-items-center gap-2">
                                            <a href="{{ route('course-repository.show', $child->pk) }}" 
                                               class="btn btn-sm btn-outline-success">
                                                <i class="material-icons material-symbols-rounded" style="font-size:18px;">visibility</i>
                                                <span class="d-none d-md-inline ms-1">View</span>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-primary edit-repo"
                                                    data-pk="{{ $child->pk }}"
                                                    data-name="{{ $child->course_repository_name }}"
                                                    data-details="{{ $child->course_repository_details }}">
                                                <i class="material-icons material-symbols-rounded" style="font-size:18px;">edit</i>
                                                <span class="d-none d-md-inline ms-1">Edit</span>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger delete-repo"
                                                    data-pk="{{ $child->pk }}">
                                                <i class="material-icons material-symbols-rounded" style="font-size:18px;">delete</i>
                                                <span class="d-none d-md-inline ms-1">Delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    <!-- Documents Section -->
                    @if($documents->count() > 0)
                <div class="table-responsive mt-4">
                    <table class="table text-nowrap mb-0" id="documents">
                        <thead>
                            <tr>
                                <th class="col text-center">S.No.</th>
                                <th class="col text-center">Document Name</th>
                                <th class="col text-center">File Title</th>
                                <th class="col text-center">Course Name</th>
                                <th class="col text-center">Subject</th>
                                <th class="col text-center">Topic</th>
                                <th class="col text-center">Session Date</th>
                                <th class="col text-center">Author</th>
                                <th class="col text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($documents as $index => $doc)
                            <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td class="text-center">
                                    <i class="fas fa-file-alt text-primary me-2"></i>
                                    <strong>{{ Str::limit($doc->upload_document ?? 'N/A', 30) }}</strong>
                                </td>
                                <td class="text-center">{{ Str::limit($doc->file_title ?? 'N/A', 25) }}</td>
                                <td class="text-center">
                                    <small>
                                        @if($doc->detail && $doc->detail->course)
                                            {{ $doc->detail->course->course_name }}
                                        @else
                                            N/A
                                        @endif
                                    </small>
                                </td>
                                <td class="text-center">
                                    <small>
                                        @if($doc->detail && $doc->detail->subject)
                                            {{ Str::limit($doc->detail->subject->subject_name, 20) }}
                                        @else
                                            N/A
                                        @endif
                                    </small>
                                </td>
                                <td class="text-center">
                                    <small>
                                        @if($doc->detail && $doc->detail->topic)
                                            {{ Str::limit($doc->detail->topic->course_repo_topic, 15) }}
                                        @else
                                            N/A
                                        @endif
                                    </small>
                                </td>
                                <td class="text-center">
                                    <small>
                                        @if($doc->detail && $doc->detail->session_date)
                                            {{ $doc->detail->session_date->format('d-m-Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </small>
                                </td>
                                <td class="text-center">
                                    <small>
                                        @if($doc->detail && $doc->detail->creator)
                                            {{ Str::limit($doc->detail->creator->name, 15) }}
                                        @else
                                            N/A
                                        @endif
                                    </small>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center gap-2" role="group">
                                        <a href="{{ route('course-repository.document.download', $doc->pk) }}" 
                                           class="btn btn-sm btn-outline-info d-flex align-items-center gap-1">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">download</i>
                                            <span class="d-none d-md-inline">Download</span>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 delete-doc" 
                                                data-pk="{{ $doc->pk }}">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">delete</i>
                                            <span class="d-none d-md-inline">Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Create Category Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" id="createModalLabel"><i class="fas fa-plus"></i> <strong>Create New Category</strong></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createForm" method="POST" action="{{ route('course-repository.store') }}">
                @csrf
                <input type="hidden" name="parent_type" value="{{ $repository->pk }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="course_repository_name" class="form-label"><strong>Category Name *</strong></label>
                        <input type="text" class="form-control" id="course_repository_name" name="course_repository_name" required placeholder="Enter category name">
                    </div>
                    <div class="mb-3">
                        <label for="course_repository_details" class="form-label"><strong>Details</strong></label>
                        <textarea class="form-control" id="course_repository_details" name="course_repository_details" rows="3" placeholder="Enter description (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" id="editModalLabel"><i class="fas fa-edit"></i> <strong>Edit Category</strong></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_course_repository_name" class="form-label"><strong>Category Name *</strong></label>
                        <input type="text" class="form-control" id="edit_course_repository_name" name="course_repository_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_course_repository_details" class="form-label"><strong>Details</strong></label>
                        <textarea class="form-control" id="edit_course_repository_details" name="course_repository_details" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #dc3545; color: white;">
                <h5 class="modal-title" id="uploadModalLabel"><i class="fas fa-upload"></i> Upload Document</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <!-- Category Selection -->
                    <div class="mb-3">
                        <label class="form-label"><strong>Category</strong></label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input category-radio" type="radio" name="category" id="category_course" value="Course" checked>
                                <label class="form-check-label" for="category_course">Course</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input category-radio" type="radio" name="category" id="category_other" value="Other">
                                <label class="form-check-label" for="category_other">Other</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input category-radio" type="radio" name="category" id="category_institutional" value="Institutional">
                                <label class="form-check-label" for="category_institutional">Institutional</label>
                            </div>
                        </div>
                    </div>

                    <!-- Category Name Display -->
                    <div class="mb-4 p-3" style="background-color: #f8f9fa; border-left: 4px solid #004a93; border-radius: 4px;">
                        <label class="form-label mb-2"><strong>Category Name</strong></label>
                        <div style="font-size: 14px; color: #495057; word-break: break-word;">
                            @if (!empty($ancestors))
                                <span style="color: #666;">
                                    @foreach ($ancestors as $ancestor)
                                        {{ $ancestor->course_repository_name }} <span style="color: #999;">-></span>
                                    @endforeach
                                </span>
                            @endif
                            <span style="color: #004a93; font-weight: 600;">{{ $repository->course_repository_name }}</span>
                        </div>
                    </div>

                    <!-- Course Category Fields -->
                    <div id="courseFields" class="category-fields">
                        <!-- Course Name -->
                        <div class="mb-3">
                            <label for="course_name" class="form-label"><strong>Course Name *</strong></label>
                            <select class="form-select" id="course_name" name="course_name">
                                <option value="">-- Select --</option>
                                @foreach(($courses ?? []) as $course)
                                    <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Select Course Name</small>
                        </div>

                        <!-- Group Name -->
                        <div class="mb-3">
                            <label for="group_name" class="form-label"><strong>Group Name</strong></label>
                            <select class="form-select" id="group_name" name="group_name">
                                <option value="">-- Select --</option>
                            </select>
                            <small class="text-muted d-block mt-1">Select Group Name</small>
                        </div>

                        <!-- Timetable -->
                        <div class="mb-3">
                            <label for="timetable_name" class="form-label"><strong>Timetable</strong></label>
                            <select class="form-select" id="timetable_name" name="timetable_name">
                                <option value="">-- Select --</option>
                            </select>
                            <small class="text-muted d-block mt-1">Select Timetable</small>
                        </div>

                        <!-- Major Subject Name -->
                        <div class="mb-3">
                            <label for="major_subject" class="form-label"><strong>Major Subject Name</strong></label>
                            <select class="form-select" id="major_subject" name="major_subject">
                                <option value="">-- Select --</option>
                                @foreach(($subjects ?? []) as $subject)
                                    <option value="{{ $subject->pk }}">{{ $subject->subject_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Select Major Subject Name</small>
                        </div>

                        <!-- Topic Name -->
                        <div class="mb-3">
                            <label for="topic_name" class="form-label"><strong>Topic Name</strong></label>
                            <select class="form-select" id="topic_name" name="topic_name">
                                <option value="">-- Select --</option>
                                @foreach(($topics ?? []) as $topic)
                                    <option value="{{ $topic->pk }}">{{ $topic->course_repo_topic }} - {{ $topic->course_repo_sub_topic }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Select Topic Name</small>
                        </div>

                        <!-- Session Date -->
                        <div class="mb-3">
                            <label for="session_date" class="form-label"><strong>Session Date</strong></label>
                            <select class="form-select" id="session_date" name="session_date">
                                <option value="">-- Select --</option>
                            </select>
                            <small class="text-muted d-block mt-1">Select Session Date</small>
                        </div>

                        <!-- Author Name -->
                        <div class="mb-3">
                            <label for="author_name" class="form-label"><strong>Author Name</strong></label>
                            <select class="form-select" id="author_name" name="author_name">
                                <option value="">-- Select --</option>
                                @foreach(($authors ?? []) as $author)
                                    <option value="{{ $author->pk }}">{{ $author->full_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Select Author Name</small>
                        </div>
                         <div class="mb-3">
                            <label for="keywords_other" class="form-label"><strong>Keywords *</strong></label>
                            <textarea class="form-control" id="keywords_other" name="keywords_other" rows="2" placeholder="Enter KeyWord" required></textarea>
                        </div>

                    </div>

                    <!-- Other Category Fields -->
                    <div id="otherFields" class="category-fields" style="display: none;">
                        <!-- Course Name -->
                        <div class="mb-3">
                            <label for="course_name_other" class="form-label"><strong>Course Name *</strong></label>
                            <select class="form-select" id="course_name_other" name="course_name_other" required>
                                <option value="">-- Select --</option>
                                @foreach(($courses ?? []) as $course)
                                    <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Select Course Name</small>
                        </div>

                        <!-- Major Subject Name -->
                        <div class="mb-3">
                            <label for="major_subject_other" class="form-label"><strong>Major Subject Name</strong></label>
                            <select class="form-select" id="major_subject_other" name="major_subject_other">
                                <option value="">-- Select --</option>
                                @foreach(($subjects ?? []) as $subject)
                                    <option value="{{ $subject->pk }}">{{ $subject->subject_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Select Major Subject Name</small>
                        </div>

                        <!-- Topic Name -->
                        <div class="mb-3">
                            <label for="topic_name_other" class="form-label"><strong>Topic Name</strong></label>
                            <select class="form-select" id="topic_name_other" name="topic_name_other">
                                <option value="">-- Select --</option>
                                @foreach(($topics ?? []) as $topic)
                                    <option value="{{ $topic->pk }}">{{ $topic->course_repo_topic }} - {{ $topic->course_repo_sub_topic }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Select Topic Name</small>
                        </div>

                        <!-- Session Date -->
                        <div class="mb-3">
                            <label for="session_date_other" class="form-label"><strong>Session Date *</strong></label>
                            <select class="form-select" id="session_date_other" name="session_date_other" required>
                                <option value="">-- Select --</option>
                            </select>
                            <small class="text-muted d-block mt-1">Pick Date</small>
                        </div>

                        <!-- Author Name -->
                        <div class="mb-3">
                            <label for="author_name_other" class="form-label"><strong>Author Name</strong></label>
                            <select class="form-select" id="author_name_other" name="author_name_other">
                                <option value="">-- Select --</option>
                                @foreach(($authors ?? []) as $author)
                                    <option value="{{ $author->pk }}">{{ $author->full_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Select Author Name</small>
                        </div>

                        <!-- Keywords -->
                        <div class="mb-3">
                            <label for="keywords_other" class="form-label"><strong>Keywords *</strong></label>
                            <textarea class="form-control" id="keywords_other" name="keywords_other" rows="2" placeholder="Enter KeyWord" required></textarea>
                        </div>


                        <!-- Attachments Table for Other -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0"><strong>Documents</strong></label>
                                <button type="button" class="btn btn-sm btn-primary addAttachmentRowBtn" data-category="other">
                                    <i class="fas fa-plus"></i> Add Row
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead style="background-color: #004a93; color: white;">
                                        <tr>
                                            <th class="text-center" style="width: 10%;">S.No.</th>
                                            <th class="text-center">Attachment Title</th>
                                            <th class="text-center" style="width: 30%;">Upload Attachment</th>
                                            <th class="text-center" style="width: 8%;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="attachmentTableBody" data-category="other">
                                        <tr class="attachment-row">
                                            <td class="text-center row-number">1</td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" name="attachment_titles_other[]" placeholder="Document title">
                                            </td>
                                            <td class="text-center">
                                                <input type="file" class="form-control form-control-sm" name="attachments_other[]" accept="*/*">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-danger remove-row" style="display: none;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted d-block mt-2">You can add multiple documents. Max file size: 100MB</small>
                        </div>
                    </div>

                    <!-- Institutional Category Fields -->
                    <div id="institutionalFields" class="category-fields" style="display: none;">
                        <!-- Course Name -->
                        <div class="mb-3">
                            <label for="course_name_institutional" class="form-label"><strong>Course Name *</strong></label>
                            <select class="form-select" id="course_name_institutional" name="course_name_institutional" required>
                                <option value="">-- Select --</option>
                                @foreach(($courses ?? []) as $course)
                                    <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Select Course Name</small>
                        </div>

                        <!-- Major Subject Name -->
                        <div class="mb-3">
                            <label for="major_subject_institutional" class="form-label"><strong>Major Subject Name</strong></label>
                            <select class="form-select" id="major_subject_institutional" name="major_subject_institutional">
                                <option value="">-- Select --</option>
                                @foreach(($subjects ?? []) as $subject)
                                    <option value="{{ $subject->pk }}">{{ $subject->subject_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Select Major Subject Name</small>
                        </div>

                        <!-- Topic Name -->
                        <div class="mb-3">
                            <label for="topic_name_institutional" class="form-label"><strong>Topic Name</strong></label>
                            <select class="form-select" id="topic_name_institutional" name="topic_name_institutional">
                                <option value="">-- Select --</option>
                                @foreach(($topics ?? []) as $topic)
                                    <option value="{{ $topic->pk }}">{{ $topic->course_repo_topic }} - {{ $topic->course_repo_sub_topic }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Select Topic Name</small>
                        </div>

                        <!-- Session Date -->
                        <div class="mb-3">
                            <label for="session_date_institutional" class="form-label"><strong>Session Date *</strong></label>
                            <select class="form-select" id="session_date_institutional" name="session_date_institutional" required>
                                <option value="">-- Select --</option>
                            </select>
                            <small class="text-muted d-block mt-1">Pick Date</small>
                        </div>

                        <!-- Author Name -->
                        <div class="mb-3">
                            <label for="author_name_institutional" class="form-label"><strong>Author Name</strong></label>
                            <select class="form-select" id="author_name_institutional" name="author_name_institutional">
                                <option value="">-- Select --</option>
                                @foreach(($authors ?? []) as $author)
                                    <option value="{{ $author->pk }}">{{ $author->full_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Select Author Name</small>
                        </div>

                        <!-- Add Keywords Section -->
                        <div class="mb-3">
                            <label class="form-label"><strong>Add Key words</strong></label>
                            <small class="text-muted d-block mb-2">add keyword</small>
                        </div>

                        <!-- Keywords -->
                        <div class="mb-3">
                            <label for="keywords_institutional" class="form-label"><strong>Keywords *</strong></label>
                            <textarea class="form-control" id="keywords_institutional" name="keywords_institutional" rows="2" placeholder="Enter Keywords" required></textarea>
                        </div>

                        <!-- Video Link -->
                        <div class="mb-3">
                            <label for="video_link_institutional" class="form-label"><strong>Add Video Link</strong></label>
                            <textarea class="form-control" id="video_link_institutional" name="video_link_institutional" rows="2" placeholder="Enter Video Link"></textarea>
                        </div>

                        <!-- Attachments Table for Institutional -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0"><strong>Documents</strong></label>
                                <button type="button" class="btn btn-sm btn-primary addAttachmentRowBtn" data-category="institutional">
                                    <i class="fas fa-plus"></i> Add Row
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead style="background-color: #004a93; color: white;">
                                        <tr>
                                            <th class="text-center" style="width: 10%;">S.No.</th>
                                            <th class="text-center">Attachment Title</th>
                                            <th class="text-center" style="width: 30%;">Upload Attachment</th>
                                            <th class="text-center" style="width: 8%;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="attachmentTableBody" data-category="institutional">
                                        <tr class="attachment-row">
                                            <td class="text-center row-number">1</td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" name="attachment_titles_institutional[]" placeholder="Document title">
                                            </td>
                                            <td class="text-center">
                                                <input type="file" class="form-control form-control-sm" name="attachments_institutional[]" accept="*/*">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-danger remove-row" style="display: none;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted d-block mt-2">You can add multiple documents. Max file size: 100MB</small>
                        </div>
                    </div>

                    <!-- Course Category Video Link -->
                    <div id="courseVideoLink" class="category-fields" style="display: block;">
                        <div class="mb-3">
                            <label for="video_link" class="form-label"><strong>Add Video Link</strong></label>
                            <textarea class="form-control" id="video_link" name="video_link" rows="2" placeholder="Enter Video Link"></textarea>
                        </div>
                    </div>

                    <!-- Course Category Attachments Table -->
                    <div id="courseAttachments" class="category-fields" style="display: block;">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0"><strong>Documents</strong></label>
                                <button type="button" class="btn btn-sm btn-primary addAttachmentRowBtn" data-category="course">
                                    <i class="fas fa-plus"></i> Add Row
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead style="background-color: #004a93; color: white;">
                                        <tr>
                                            <th class="text-center" style="width: 10%;">S.No.</th>
                                            <th class="text-center">Attachment Title</th>
                                            <th class="text-center" style="width: 30%;">Upload Attachment</th>
                                            <th class="text-center" style="width: 8%;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="attachmentTableBody" data-category="course">
                                        <tr class="attachment-row">
                                            <td class="text-center row-number">1</td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" name="attachment_titles[]" placeholder="Document title">
                                            </td>
                                            <td class="text-center">
                                                <input type="file" class="form-control form-control-sm" name="attachments[]" accept="*/*">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-danger remove-row" style="display: none;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted d-block mt-2">You can add multiple documents. Max file size: 100MB</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
                    <button type="submit" class="btn btn-success" id="uploadBtn">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const repositoryPk = {{ $repository->pk }};

    // Basic function to clear and populate dropdown
    function populateDropdown(selectId, data, valueKey, textKey) {
        var $select = $('#' + selectId);
        $select.empty();
        $select.append('<option value="">-- Select --</option>');
        
        if (data && data.length > 0) {
            $.each(data, function(index, item) {
                var text = textKey(item);
                $select.append('<option value="' + item[valueKey] + '">' + text + '</option>');
            });
        }
    }

    // Step 1: Course changes -> Load Groups
   function onCourseChange(courseSelectId, groupSelectId) {

    let coursePk = $('#' + courseSelectId).val();
    let $group = $('#' + groupSelectId);

    $group.empty().append('<option value="">-- Select --</option>');

    if (!coursePk) return;

    $.ajax({
        url: "{{ route('course-repository.groups') }}",
        type: "GET",
        data: { course_pk: coursePk },

        // 🔥 THESE 3 LINES FIX 302
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },

        success: function (response) {
            if (response.success) {
                $.each(response.data, function (i, group) {
                    $group.append(
                        `<option value="${group.pk}">
                            ${group.group_name} (${group.group_type})
                        </option>`
                    );
                });
            }
        },

        error: function (xhr) {
            if (xhr.status === 401) {
                Swal.fire('Session Expired', 'Please login again', 'warning');
            } else {
                console.error(xhr.responseText);
            }
        }
    });
}


    // Step 2: Group changes -> Load Timetables
    function onGroupChange(groupSelectId, timetableSelectId) {
        var groupPk = $('#' + groupSelectId).val();
        var course_master_pk = $('#course_name').val();
        
        // Clear timetable dropdown
        populateDropdown(timetableSelectId, [], 'pk', function(t) { return ''; });
        
        if (!groupPk) return;
        
        // AJAX call to get timetables
        $.ajax({
            url: '/course-repository/timetables',
            type: 'GET',
            data: { group_pk: groupPk , course_master_pk: course_master_pk },
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    populateDropdown(timetableSelectId, response.data, 'pk', function(t) {
                        return t.display;
                    });
                }
            },
            error: function(xhr, status, error) {
                console.log('Error loading timetables:', error);
            }
        });
    }

    // Category radio button change handler
    document.querySelectorAll('.category-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            const category = this.value;
            
            // Hide all category-specific fields and document tables
            document.querySelectorAll('.category-fields').forEach(field => {
                field.style.display = 'none';
            });
            
            // Show the selected category fields
            if (category === 'Course') {
                document.getElementById('courseFields').style.display = 'block';
                document.getElementById('courseVideoLink').style.display = 'block';
                document.getElementById('courseAttachments').style.display = 'block';
                // Make course-specific keywords required
                document.getElementById('keywords').removeAttribute('required');
                document.getElementById('keywords_other').removeAttribute('required');
                document.getElementById('keywords_institutional').removeAttribute('required');
            } else if (category === 'Other') {
                document.getElementById('otherFields').style.display = 'block';
                // Make other-specific keywords required
                document.getElementById('keywords').removeAttribute('required');
                document.getElementById('keywords_other').setAttribute('required', 'required');
                document.getElementById('keywords_institutional').removeAttribute('required');
            } else if (category === 'Institutional') {
                document.getElementById('institutionalFields').style.display = 'block';
                // Make institutional-specific keywords required
                document.getElementById('keywords').removeAttribute('required');
                document.getElementById('keywords_other').removeAttribute('required');
                document.getElementById('keywords_institutional').setAttribute('required', 'required');
            }
        });
    });
    
    // Set initial state (Course is default)
    document.getElementById('courseFields').style.display = 'block';
    document.getElementById('courseVideoLink').style.display = 'block';
    document.getElementById('courseAttachments').style.display = 'block';

    // Bind cascading change events for Course -> Group -> Timetable
    $('#course_name').on('change', function() {
        onCourseChange('course_name', 'group_name');
    });
    
    $('#group_name').on('change', function() {
        onGroupChange('group_name', 'timetable_name');
    });

    // Edit button functionality
    document.querySelectorAll('.edit-repo').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const pk = this.getAttribute('data-pk');
            const name = this.getAttribute('data-name');
            const details = this.getAttribute('data-details');
            
            // Populate edit form
            document.getElementById('edit_course_repository_name').value = name;
            document.getElementById('edit_course_repository_details').value = details || '';
            
            // Update form action
            const editForm = document.getElementById('editForm');
            editForm.action = `/course-repository/${pk}`;
            
            // Show modal
            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();
        });
    });

    // Create form submit
    document.getElementById('createForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message || 'Category created successfully',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to create category'
                });
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Save';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Failed to create category'
            });
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Save';
        });
    });

    // Edit form submit
    document.getElementById('editForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message || 'Category updated successfully',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to update category'
                });
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Update';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Failed to update category'
            });
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Update';
        });
    });

    // Upload document
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const uploadModal = bootstrap.Modal.getInstance(document.getElementById('uploadModal'));
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        
        // Get selected category
        const selectedCategory = document.querySelector('input[name="category"]:checked').value;
        
        // Get attachment files and titles based on selected category
        let attachmentFiles, attachmentTitles;
        
        if (selectedCategory === 'Course') {
            attachmentFiles = this.querySelectorAll('input[name="attachments[]"]');
            attachmentTitles = this.querySelectorAll('input[name="attachment_titles[]"]');
        } else if (selectedCategory === 'Other') {
            attachmentFiles = this.querySelectorAll('input[name="attachments_other[]"]');
            attachmentTitles = this.querySelectorAll('input[name="attachment_titles_other[]"]');
        } else if (selectedCategory === 'Institutional') {
            attachmentFiles = this.querySelectorAll('input[name="attachments_institutional[]"]');
            attachmentTitles = this.querySelectorAll('input[name="attachment_titles_institutional[]"]');
        }
        
        // Validate at least one attachment
        let hasAttachment = false;
        attachmentFiles.forEach(file => {
            if (file.files.length > 0) {
                hasAttachment = true;
            }
        });
        
        if (!hasAttachment) {
            Swal.fire({
                icon: 'warning',
                title: 'Warning!',
                text: 'Please select at least one document to upload'
            });
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Save';
            return;
        }
        
        // Create new FormData with correct files and titles
        const uploadData = new FormData();
        
        // Add CSRF token
        uploadData.append('_token', document.querySelector('[name="_token"]').value);
        
        // Add category
        uploadData.append('category', selectedCategory);
        
        // Add files and titles
        attachmentFiles.forEach((fileInput, index) => {
            if (fileInput.files.length > 0) {
                uploadData.append('attachments[]', fileInput.files[0]);
                uploadData.append('attachment_titles[]', attachmentTitles[index].value || 'Untitled');
            }
        });
        
        // Add keywords based on selected category
        if (selectedCategory === 'Course') {
            const keywordsValue = document.getElementById('keywords').value;
            uploadData.append('keywords', keywordsValue);
            uploadData.append('video_link', document.getElementById('video_link').value);
        } else if (selectedCategory === 'Other') {
            const keywordsValue = document.getElementById('keywords_other').value;
            uploadData.append('keywords', keywordsValue);
            uploadData.append('video_link', document.getElementById('video_link_other').value);
        } else if (selectedCategory === 'Institutional') {
            const keywordsValue = document.getElementById('keywords_institutional').value;
            uploadData.append('keywords', keywordsValue);
            uploadData.append('video_link', document.getElementById('video_link_institutional').value);
        }
        
        fetch(`/course-repository/${repositoryPk}/upload-document`, {
            method: 'POST',
            body: uploadData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Documents uploaded successfully',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    // Close modal
                    uploadModal.hide();
                    // Reset form
                    document.getElementById('uploadForm').reset();
                    // Reload page to show new documents in table
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.error || 'Upload failed'
                });
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Save';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Upload failed'
            });
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Save';
        });
    });

    // Delete document
    document.querySelectorAll('.delete-doc').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const pk = this.getAttribute('data-pk');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/course-repository/document/${pk}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value,
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'Document has been deleted.',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.error || 'Delete failed'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Delete failed'
                        });
                    });
                }
            });
        });
    });

    // Delete category
    document.querySelectorAll('.delete-repo').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const pk = this.getAttribute('data-pk');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/course-repository/${pk}`;
                    
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = document.querySelector('[name="_token"]').value;
                    
                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';
                    
                    form.appendChild(csrfInput);
                    form.appendChild(methodInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });

    // Add new attachment row - Category Specific
    document.querySelectorAll('.addAttachmentRowBtn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const category = this.getAttribute('data-category');
            const tableBody = this.closest('.mb-3').querySelector(`.attachmentTableBody[data-category="${category}"]`);
            const rowCount = tableBody.querySelectorAll('.attachment-row').length + 1;
            
            const newRow = document.createElement('tr');
            newRow.className = 'attachment-row';
            
            // Get correct field names based on category
            let titleFieldName = 'attachment_titles[]';
            let filesFieldName = 'attachments[]';
            
            if (category === 'other') {
                titleFieldName = 'attachment_titles_other[]';
                filesFieldName = 'attachments_other[]';
            } else if (category === 'institutional') {
                titleFieldName = 'attachment_titles_institutional[]';
                filesFieldName = 'attachments_institutional[]';
            }
            
            newRow.innerHTML = `
                <td class="text-center row-number">${rowCount}</td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="${titleFieldName}" placeholder="Document title">
                </td>
                <td class="text-center">
                    <input type="file" class="form-control form-control-sm" name="${filesFieldName}" accept="*/*">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-row">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            
            tableBody.appendChild(newRow);
            updateRowNumbersForCategory(tableBody);
            
            // Add delete handler to new row
            newRow.querySelector('.remove-row').addEventListener('click', function(e) {
                e.preventDefault();
                newRow.remove();
                updateRowNumbersForCategory(tableBody);
            });
        });
    });
    
    // Function to update row numbers for specific category
    function updateRowNumbersForCategory(tableBody) {
        const rows = tableBody.querySelectorAll('.attachment-row');
        rows.forEach((row, index) => {
            row.querySelector('.row-number').textContent = index + 1;
            
            // Show/hide delete button: hide for first row if only 1 row, show for others
            const deleteBtn = row.querySelector('.remove-row');
            if (rows.length === 1) {
                deleteBtn.style.display = 'none';
            } else {
                deleteBtn.style.display = 'block';
            }
        });
    }
    
    // Remove attachment row - Category Specific
    document.querySelectorAll('.attachmentTableBody').forEach(tableBody => {
        tableBody.querySelectorAll('.remove-row').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                this.closest('tr').remove();
                updateRowNumbersForCategory(tableBody);
            });
        });
    });
});
</script>
@endsection
