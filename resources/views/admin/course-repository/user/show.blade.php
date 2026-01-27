@extends('admin.layouts.timetable')

@section('title', ($repository->course_repository_name ?? 'Repository Details') . ' | Course Repository')

@section('content')
<!-- Main Content -->
<div class="container-fluid px-4 py-4" id="main-content">
    <!-- Title Section with Back Button -->
    <div class="title-section mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <button type="button" 
                        onclick="window.history.back()" 
                        class="btn-back btn btn-link p-0 text-decoration-none"
                        aria-label="Go back">
                    <span class="material-icons material-symbols-rounded fs-4 text-dark">arrow_back</span>
                </button>
                <h1 class="h2 mb-0 fw-bold text-dark">{{ $repository->course_repository_name }}</h1>
            </div>
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

    <!-- Filter Card -->
    @if(isset($courses) && isset($subjects) && isset($faculties))
    @include('admin.course-repository.user.partials.filter-card', [
        'route' => route('admin.course-repository.user.show', $repository->pk),
        'courses' => $courses,
        'subjects' => $subjects,
        'faculties' => $faculties,
        'filters' => $filters ?? [],
    ])
    @endif

    @if($repository->children->count() == 0 && $documents->count() == 0)
        <!-- Empty State -->
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <span class="material-icons material-symbols-rounded" style="font-size: 48px; color: #ccc;">inbox</span>
                <p class="text-muted mt-3">No sub-categories or documents found in this repository.</p>
            </div>
        </div>
    @else
        <!-- Child Repositories Section -->
        @if($repository->children->count() > 0)
        <div class="course-cards-grid mb-4">
            <div class="row g-4">
                @foreach ($repository->children as $child)
                <div class="col-md-4 col-lg-3">
                    <div class="card course-card shadow-sm h-100" onclick="window.location='{{ route('admin.course-repository.user.show', $child->pk) }}'">
                        <div class="card-img-wrapper">
                            @php
                                $imageUrl = null;
                                if($child->category_image && \Storage::disk('public')->exists($child->category_image)) {
                                    $imageUrl = asset('storage/' . $child->category_image);
                                }
                                if(!$imageUrl) {
                                    $imageUrl = 'https://via.placeholder.com/400x200/004a93/ffffff?text=' . urlencode($child->course_repository_name);
                                }
                            @endphp
                            <img src="{{ $imageUrl }}" 
                                 alt="{{ $child->course_repository_name }}"
                                 class="card-img-top"
                                 loading="lazy"
                                 onerror="this.src='https://via.placeholder.com/400x200/004a93/ffffff?text={{ urlencode($child->course_repository_name) }}'">
                        </div>
                        <div class="card-body d-flex flex-column" style="background-color: #F2F2F2;">
                            <h5 class="card-title text-center fw-bold mb-3">{{ Str::limit($child->course_repository_name, 50) }}</h5>
                            <div class="mt-auto">
                                <a href="{{ route('admin.course-repository.user.show', $child->pk) }}"
                                   class="btn btn-outline-primary w-100 fw-semibold"
                                   onclick="event.stopPropagation();">
                                    Click Here
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
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
                    <table class="table table-hover mb-0 align-middle">
                        <thead style="background-color: #dc3545; color: white;">
                            <tr>
                                <th class="text-center fw-bold">S.No.</th>
                                <th class="fw-bold">Document Name</th>
                                <th class="fw-bold">File Title</th>
                                <th class="fw-bold">Course</th>
                                <th class="fw-bold">Subject</th>
                                <th class="fw-bold">Topic</th>
                                <th class="fw-bold">Session Date</th>
                                <th class="fw-bold">Author</th>
                                <th class="text-center fw-bold">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($documents as $index => $doc)
                            <tr class="{{ $loop->odd ? 'table-light' : '' }}" style="cursor: pointer;" onclick="window.location='{{ route('admin.course-repository.user.document-details', $doc->pk) }}'">
                                <td class="text-center">{{ $loop->iteration }}</td>
                                <td>
                                    <span class="material-icons material-symbols-rounded text-danger me-2">picture_as_pdf</span>
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
                                <td class="text-center">
                                    <a href="{{ route('course-repository.document.download', $doc->pk) }}" 
                                       class="btn btn-sm btn-outline-primary"
                                       onclick="event.stopPropagation();">
                                        <span class="material-icons material-symbols-rounded me-1">download</span> Download
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

