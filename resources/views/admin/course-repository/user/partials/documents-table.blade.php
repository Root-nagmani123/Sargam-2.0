@php
    $documents = $documents ?? collect();
    $documentsAsDetails = $documentsAsDetails ?? false;
    $totalCount = $documents->count();
    $cruTableId = 'cruDocumentsTable';
    $cruColumnStorageKey = 'cru-user-documents-columns';
    $cruColumns = [
        ['key' => 'sno', 'label' => 'S. No.', 'locked' => true],
        ['key' => 'document_name', 'label' => 'Document Name', 'default' => true],
        ['key' => 'file_title', 'label' => 'File Title', 'default' => true],
        ['key' => 'course', 'label' => 'Course', 'default' => true],
        ['key' => 'subject', 'label' => 'Subject', 'default' => true],
        ['key' => 'topic', 'label' => 'Topic', 'default' => true],
        ['key' => 'session_date', 'label' => 'Session Date', 'default' => true],
        ['key' => 'author', 'label' => 'Author', 'default' => true],
        ['key' => 'action', 'label' => 'Action', 'locked' => true],
    ];
@endphp
@php
    // The inline column show/hide control lives in the filter toolbar, so the
    // toolbar (and Columns button) only appears when there is a documents table.
    // Skip it when the parent already rendered a sub-category filter card
    // (children present) to avoid duplicate filter form IDs.
    $cruHasChildren = !empty($childCount) && $childCount > 0;
    $cruShowFilterToolbar = $totalCount > 0
        && !$cruHasChildren
        && isset($courses) && isset($subjects) && isset($faculties);
@endphp

{{-- Filter toolbar with inline column show/hide — only rendered when a table exists --}}
@if($cruShowFilterToolbar)
    @include('admin.course-repository.user.partials.filter-card', [
        'route' => route('admin.course-repository.user.show', $repository->pk),
        'courses' => $courses,
        'subjects' => $subjects,
        'faculties' => $faculties,
        'sectors' => $sectors ?? collect(),
        'ministries' => $ministries ?? collect(),
        'filters' => $filters ?? [],
        'columnToggle' => [
            'tableId' => $cruTableId,
            'storageKey' => $cruColumnStorageKey,
            'columns' => $cruColumns,
        ],
    ])
@endif

<div class="card cru-table-card border-0 shadow-sm rounded-4" data-cru-table-card="{{ $cruTableId }}">
    <div class="table-responsive">
        <table class="table mb-0 align-middle cru-table" id="{{ $cruTableId }}">
            <thead>
                <tr>
                    <th class="text-center text-nowrap cru-col-sno small text-uppercase" data-col="sno">S. No.</th>
                    <th class="cru-col-document_name small text-uppercase" data-col="document_name">Document Name</th>
                    <th class="cru-col-file_title small text-uppercase" data-col="file_title">File Title</th>
                    <th class="cru-col-course small text-uppercase" data-col="course">Course</th>
                    <th class="cru-col-subject small text-uppercase" data-col="subject">Subject</th>
                    <th class="cru-col-topic small text-uppercase" data-col="topic">Topic</th>
                    <th class="text-nowrap cru-col-session_date small text-uppercase" data-col="session_date">Session Date</th>
                    <th class="cru-col-author small text-uppercase" data-col="author">Author</th>
                    <th class="text-center text-nowrap cru-col-action small text-uppercase" data-col="action">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $doc)
                @php
                    if ($documentsAsDetails) {
                        $detail = $doc;
                        $fileDoc = $doc->documents->first();
                        $documentName = $fileDoc->upload_document ?? $detail->detail_document ?? 'N/A';
                        $fileTitle = $fileDoc->file_title ?? 'N/A';
                    } else {
                        $detail = $doc->detail;
                        $fileDoc = $doc;
                        $documentName = $doc->upload_document ?? 'N/A';
                        $fileTitle = $doc->file_title ?? 'N/A';
                    }
                @endphp
                <tr>
                    <td class="text-center cru-col-sno">{{ $loop->iteration }}</td>
                    <td class="text-truncate cru-col-document_name" style="max-width: 12rem;">
                        <span class="fw-semibold text-dark">{{ Str::limit($documentName, 40) }}</span>
                    </td>
                    <td class="text-truncate cru-col-file_title" style="max-width: 10rem;">{{ Str::limit($fileTitle, 35) }}</td>
                    <td class="cru-col-course">
                        @if($detail && $detail->course)
                            {{ $detail->course->course_name }}
                        @else
                            NA
                        @endif
                    </td>
                    <td class="cru-col-subject">
                        @if($detail && $detail->subject)
                            {{ Str::limit($detail->subject->subject_name, 25) }}
                        @else
                            NA
                        @endif
                    </td>
                    <td class="cru-col-topic">
                        @if($detail && $detail->topic)
                            {{ Str::limit($detail->topic->subject_topic, 20) }}
                        @else
                            NA
                        @endif
                    </td>
                    <td class="text-nowrap cru-col-session_date">
                        @if($detail && $detail->session_date)
                            {{ $detail->session_date->format('d/m/Y') }}
                        @else
                            NA
                        @endif
                    </td>
                    <td class="cru-col-author">
                        @if($detail && $detail->author)
                            {{ Str::limit($detail->author->full_name, 20) }}
                        @elseif($detail && $detail->author_name)
                            {{ Str::limit($detail->author_name, 20) }}
                        @else
                            NA
                        @endif
                    </td>
                    <td class="text-center cru-col-action">
                        @include('admin.course-repository.user.partials.document-actions', [
                            'detailPk' => $detail?->pk,
                            'detail' => $detail,
                            'fileDoc' => $fileDoc,
                        ])
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">
                        <i class="bi bi-file-earmark-x d-block fs-3 mb-2" aria-hidden="true"></i>
                        No documents found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($totalCount > 0)
    <div class="cru-table-footer d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-3 border-top bg-white">
        <p class="small text-muted mb-0">
            Showing <span class="fw-semibold text-dark">{{ $totalCount }}</span> of
            <span class="fw-semibold text-dark">{{ $totalCount }}</span> items
        </p>
    </div>
    @endif
</div>

@push('scripts')
@include('admin.course-repository.user.partials.column-toggle-script', [
    'cruTableId' => $cruTableId,
    'cruColumnStorageKey' => $cruColumnStorageKey,
    'cruColumns' => $cruColumns,
])
@endpush
