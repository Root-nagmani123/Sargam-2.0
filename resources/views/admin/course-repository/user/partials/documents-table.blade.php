@php
    $documents = $documents ?? collect();
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
<div class="card cru-table-card border-0 shadow-sm rounded-4 overflow-hidden" data-cru-table-card="{{ $cruTableId }}">
    @if($totalCount > 0)
    <div class="cru-table-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 px-md-4 py-3 border-bottom bg-white">
        <p class="small text-muted mb-0 d-none d-sm-block">
            <i class="bi bi-table me-1" aria-hidden="true"></i>Manage visible columns
        </p>
        @include('admin.course-repository.user.partials.table-column-toggle', [
            'cruTableId' => $cruTableId,
            'cruColumnStorageKey' => $cruColumnStorageKey,
            'cruColumns' => $cruColumns,
        ])
    </div>
    @endif
    <div class="table-responsive">
        <table class="table mb-0 align-middle cru-table" id="{{ $cruTableId }}">
            <thead class="table-light">
                <tr>
                    <th class="text-center text-nowrap cru-col-sno small text-uppercase" data-col="sno">S. No.</th>
                    <th class="cru-col-document_name small text-uppercase" data-col="document_name">Document Name</th>
                    <th class="cru-col-file_title small text-uppercase" data-col="file_title">File Title</th>
                    <th class="cru-col-course small text-uppercase" data-col="course">Course</th>
                    <th class="cru-col-subject small text-uppercase" data-col="subject">Subject</th>
                    <th class="cru-col-topic small text-uppercase" data-col="topic">Topic</th>
                    <th class="text-nowrap cru-col-session_date small text-uppercase" data-col="session_date">Session Date</th>
                    <th class="cru-col-author small text-uppercase" data-col="author">Author</th>
                    <th class="text-center cru-col-action small text-uppercase" data-col="action">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $doc)
                <tr>
                    <td class="text-center cru-col-sno">{{ $loop->iteration }}</td>
                    <td class="text-truncate cru-col-document_name" style="max-width: 12rem;">
                        <span class="fw-semibold text-dark">{{ Str::limit($doc->upload_document ?? 'N/A', 40) }}</span>
                    </td>
                    <td class="text-truncate cru-col-file_title" style="max-width: 10rem;">{{ Str::limit($doc->file_title ?? 'N/A', 35) }}</td>
                    <td class="cru-col-course">
                        @if($doc->detail && $doc->detail->course)
                            {{ $doc->detail->course->course_name }}
                        @else
                            NA
                        @endif
                    </td>
                    <td class="cru-col-subject">
                        @if($doc->detail)
                            {{ Str::limit($doc->detail->subject_display_name, 25) }}
                        @else
                            NA
                        @endif
                    </td>
                    <td class="cru-col-topic">
                        @if($doc->detail)
                            {{ Str::limit($doc->detail->topic_display_name, 20) }}
                        @else
                            NA
                        @endif
                    </td>
                    <td class="text-nowrap cru-col-session_date">
                        @if($doc->detail && $doc->detail->session_date)
                            {{ $doc->detail->session_date->format('d/m/Y') }}
                        @else
                            NA
                        @endif
                    </td>
                    <td class="cru-col-author">
                        @if($doc->detail)
                            {{ Str::limit($doc->detail->author_display_name, 20) }}
                        @else
                            NA
                        @endif
                    </td>
                    <td class="text-center cru-col-action">
                        @include('admin.course-repository.user.partials.document-actions', ['doc' => $doc])
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
