@extends('admin.layouts.master')

@section('title', ($repository->course_repository_name ?? 'Repository Details') . ' | Course Repository')

@section('setup_content')
@php
$crumbItems = [
['label' => 'Home', 'url' => route('admin.dashboard')],
['label' => 'Academic', 'url' => null],
['label' => 'Course Repository', 'url' => route('admin.course-repository.user.index')],
];
if (!empty($ancestors)) {
foreach ($ancestors as $ancestor) {
$crumbItems[] = [
'label' => $ancestor->course_repository_name,
'url' => route('admin.course-repository.user.show', $ancestor->pk),
];
}
}
$crumbItems[] = $repository->course_repository_name;

$childCount = $repository->children->count();
$documentCount = $documents->count();
@endphp

<div class="cru-page">
    <div class="container-fluid px-3 px-md-4 py-4" id="cru-user-main">
        <x-breadcrum :title="$repository->course_repository_name" :items="$crumbItems" />

        <div id="cruFilterResults">
            @if($childCount === 0 && $documentCount === 0)
            <div class="card border-0 shadow-sm rounded-4 text-center py-5 px-3">
                <div class="card-body">
                    <span
                        class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-secondary mb-3 cru-empty-icon">
                        <i class="bi bi-inbox fs-2" aria-hidden="true"></i>
                    </span>
                    <h2 class="h5 fw-semibold text-dark mb-2">No sub-categories or documents</h2>
                    <p class="text-muted small mb-0 mx-auto" style="max-width: 28rem;">
                        Nothing is available in this repository yet. Adjust filters or explore another category.
                    </p>
                </div>
            </div>
            @else
            @if($childCount > 0)
            @php
                $cruGridListTableId = 'cruRepoListTableShow';
                $cruGridColumnStorageKey = 'cru-repo-list-' . $cruGridListTableId;
                $cruGridColumns = [
                    ['key' => 'sno', 'label' => 'S. No.', 'locked' => true],
                    ['key' => 'name', 'label' => 'Category', 'default' => true],
                    ['key' => 'subcount', 'label' => 'Sub Categories', 'default' => true],
                ];
            @endphp
            <div class="d-flex flex-wrap align-items-end justify-content-end gap-3 mb-3">
                @include('admin.course-repository.user.partials.page-toolbar', ['showViewToggle' => true])
            </div>

            {{-- Shared filter toolbar (with inline column show/hide) — stays visible across both card and grid views --}}
            @if(isset($courses) && isset($subjects) && isset($faculties))
            @include('admin.course-repository.user.partials.filter-card', [
            'route' => route('admin.course-repository.user.show', $repository->pk),
            'courses' => $courses,
            'subjects' => $subjects,
            'faculties' => $faculties,
            'sectors' => $sectors ?? collect(),
            'ministries' => $ministries ?? collect(),
            'filters' => $filters ?? [],
            'columnToggle' => [
                'tableId' => $cruGridListTableId,
                'storageKey' => $cruGridColumnStorageKey,
                'columns' => $cruGridColumns,
            ],
            ])
            @endif

            <div class="course-cards-grid mb-4 mb-md-5" id="courseCardsGrid">
                <div class="cru-view-cards card card-body">
                    <div class="row g-3 g-md-4">
                        @foreach ($repository->children as $child)
                        @include('admin.course-repository.user.partials.repository-card', [
                        'repository' => $child,
                        'cardRoute' => route('admin.course-repository.user.show', $child->pk),
                        ])
                        @endforeach
                    </div>
                </div>
                @include('admin.course-repository.user.partials.repository-list-table', [
                'items' => $repository->children,
                'listTableId' => $cruGridListTableId,
                'listRouteMode' => 'show',
                'nameColumnLabel' => 'Category',
                'cruColumns' => $cruGridColumns,
                'cruColumnStorageKey' => $cruGridColumnStorageKey,
                ])
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
                                @php $fileUrl = $doc->public_file_url; @endphp
                              <tr class="{{ $loop->odd ? 'table-light' : '' }}">
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <span
                                            class="material-icons material-symbols-rounded text-danger me-2">picture_as_pdf</span>
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
                                            @elseif($doc->detail && $doc->detail->author_name && !is_numeric($doc->detail->author_name))
                                            {{ Str::limit($doc->detail->author_name, 15) }}
                                            @else
                                            N/A
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            @if($fileUrl)
                                                <a href="{{ $fileUrl }}" target="_blank"
                                                    class="btn btn-sm btn-outline-secondary" onclick="event.stopPropagation();">
                                                    <span class="material-icons material-symbols-rounded me-1" style="font-size:16px;">visibility</span>
                                                    View
                                                </a>
                                                <a href="{{ $fileUrl }}" download="{{ $doc->upload_document }}"
                                                    class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation();">
                                                    <span class="material-icons material-symbols-rounded me-1" style="font-size:16px;">download</span>
                                                    Download
                                                </a>
                                            @else
                                                <span class="text-muted small">N/A</span>
                                            @endif
                                        </div>
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
    </div>
</div>

@include('admin.course-repository.user.partials.assets')
@endsection