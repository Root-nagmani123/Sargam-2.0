@extends('admin.layouts.timetable')

@section('title', ($repository->course_repository_name ?? 'Repository Details') . ' | Course Repository')

@section('content')
<!-- Main Content -->
<div class="container-fluid px-4 py-4" id="main-content">
    <!-- Title Section with Back Button -->
    <div class="title-section mb-4">
        <div class="d-flex align-items-center gap-3">
            <button type="button" 
                    onclick="window.history.back()" 
                    class="btn-back btn btn-link p-0 text-decoration-none"
                    aria-label="Go back">
                <i class="bi bi-arrow-left fs-4 text-dark"></i>
            </button>
            <h1 class="h2 mb-0 fw-bold text-dark">{{ $repository->course_repository_name }}</h1>
        </div>
    </div>

    <!-- Breadcrumb Navigation -->
    @if (!empty($ancestors) || $repository->parent)
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('admin.course-repository.user.index') }}" class="text-decoration-none">Course Repository</a>
            </li>
            @if (!empty($ancestors))
                @foreach ($ancestors as $ancestor)
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.course-repository.user.show', $ancestor->pk) }}" class="text-decoration-none">
                            {{ $ancestor->course_repository_name }}
                        </a>
                    </li>
                @endforeach
            @endif
            <li class="breadcrumb-item active" aria-current="page">{{ $repository->course_repository_name }}</li>
        </ol>
    </nav>
    @endif

    <!-- Repository Info Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-2"><strong>{{ $repository->course_repository_name }}</strong></h4>
                    @if($repository->course_repository_details)
                        <p class="text-muted mb-0">{{ $repository->course_repository_details }}</p>
                    @endif
                    <small class="text-muted">
                        Created: {{ $repository->created_date ? $repository->created_date->format('d-m-Y H:i') : 'N/A' }}
                    </small>
                </div>
            </div>
        </div>
    </div>

    @if($repository->children->count() == 0 && $documents->count() == 0)
        <!-- Empty State -->
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                <p class="text-muted mt-3">No sub-categories or documents found in this repository.</p>
            </div>
        </div>
    @else
        <!-- Child Repositories Section -->
        @if($repository->children->count() > 0)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0 fw-bold">Sub-Categories ({{ $repository->children->count() }})</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach ($repository->children as $child)
                    <div class="col-md-4 col-lg-3">
                        <div class="card h-100 border">
                            <div class="card-body">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-folder-fill text-primary fs-4"></i>
                                    <div class="flex-grow-1">
                                        <h6 class="card-title mb-1">
                                            <a href="{{ route('admin.course-repository.user.show', $child->pk) }}" 
                                               class="text-decoration-none text-dark fw-bold">
                                                {{ Str::limit($child->course_repository_name, 40) }}
                                            </a>
                                        </h6>
                                        <small class="text-muted">
                                            <span class="badge bg-primary">{{ $child->children->count() }} Sub-categories</span>
                                            <span class="badge bg-success ms-1">{{ $child->getDocumentCount() }} Documents</span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Documents Section -->
        @if($documents->count() > 0)
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0 fw-bold">Documents ({{ $documents->count() }})</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>S.No.</th>
                                <th>Document Name</th>
                                <th>File Title</th>
                                <th>Course</th>
                                <th>Subject</th>
                                <th>Topic</th>
                                <th>Session Date</th>
                                <th>Author</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($documents as $index => $doc)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i>
                                    <strong>{{ Str::limit($doc->upload_document ?? 'N/A', 30) }}</strong>
                                </td>
                                <td>{{ Str::limit($doc->file_title ?? 'N/A', 25) }}</td>
                                <td>
                                    <small>
                                        @if($doc->detail && $doc->detail->course)
                                            {{ $doc->detail->course->course_name }}
                                        @else
                                            N/A
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    <small>
                                        @if($doc->detail && $doc->detail->subject)
                                            {{ Str::limit($doc->detail->subject->subject_name, 20) }}
                                        @else
                                            N/A
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    <small>
                                        @if($doc->detail && $doc->detail->topic)
                                            {{ Str::limit($doc->detail->topic->subject_topic, 15) }}
                                        @else
                                            N/A
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    <small>
                                        @if($doc->detail && $doc->detail->session_date)
                                            {{ $doc->detail->session_date->format('d-m-Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    <small>
                                        @if($doc->detail && $doc->detail->author)
                                            {{ Str::limit($doc->detail->author->full_name, 15) }}
                                        @elseif($doc->detail && $doc->detail->author_name)
                                            {{ Str::limit($doc->detail->author_name, 15) }}
                                        @else
                                            N/A
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    <a href="{{ route('course-repository.document.download', $doc->pk) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    @endif
</div>

<!-- Link to CSS -->
<link rel="stylesheet" href="{{ asset('css/course-repository-user.css') }}">
@endsection
