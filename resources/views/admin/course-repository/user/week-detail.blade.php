@extends('admin.layouts.master')

@section('title', 'Week-{{ str_pad($weekNumber, 2, "0", STR_PAD_LEFT) }} | Course Repository Admin')

@section('content')
<div class="d-flex">
    <!-- Left Sidebar -->
   

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="container-fluid px-4 py-4" id="main-content">
            <!-- Title Section with Back Button -->
            <div class="title-section mb-4">
                <div class="d-flex align-items-center gap-3">
                    <button type="button" 
                            onclick="window.history.back()" 
                            class="btn-back btn btn-link p-0 text-decoration-none"
                            aria-label="Go back">
                        <span class="material-icons material-symbols-rounded fs-4 text-dark">arrow_back</span>
                    </button>
                    <h1 class="h2 mb-0 fw-bold text-dark">Week-{{ str_pad($weekNumber, 2, '0', STR_PAD_LEFT) }}</h1>
                </div>
            </div>

            <!-- Filter Card -->
            @include('admin.course-repository.user.partials.filter-card', [
                'route' => route('admin.course-repository.user.week-detail', [$courseCode, $weekNumber]),
                'courses' => $courses,
                'subjects' => $subjects,
                'faculties' => $faculties,
                'filters' => $filters,
            ])

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-3" id="weekTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab" aria-controls="active" aria-selected="true">
                        Active
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="archive-tab" data-bs-toggle="tab" data-bs-target="#archive" type="button" role="tab" aria-controls="archive" aria-selected="false">
                        Archive
                    </button>
                </li>
            </ul>

                    <div class="tab-content" id="weekTabContent">
                        <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
                            @if($documents->isEmpty())
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-6 d-block mb-2" aria-hidden="true"></i>
                                <p class="mb-0">No documents found for this week.</p>
                            </div>
                            @else
                            @php
                                $cruTableId = 'cruWeekDetailTable';
                                $cruColumnStorageKey = 'cru-user-week-detail-columns';
                                $cruColumns = [
                                    ['key' => 'sno', 'label' => 'S. No.', 'locked' => true],
                                    ['key' => 'course', 'label' => 'Course Name', 'default' => true],
                                    ['key' => 'subject', 'label' => 'Major Subject Name', 'default' => true],
                                    ['key' => 'topic', 'label' => 'Topic Name', 'default' => true],
                                    ['key' => 'action', 'label' => 'Action', 'locked' => true],
                                ];
                            @endphp
                            <div class="card cru-table-card overflow-hidden" data-cru-table-card="{{ $cruTableId }}">
                                <div class="cru-table-toolbar d-flex flex-wrap align-items-center justify-content-end gap-2 px-3 py-2 border-bottom bg-white">
                                    @include('admin.course-repository.user.partials.table-column-toggle', [
                                        'cruTableId' => $cruTableId,
                                        'cruColumnStorageKey' => $cruColumnStorageKey,
                                        'cruColumns' => $cruColumns,
                                    ])
                                </div>
                                <div class="table-responsive">
                                    <table class="table mb-0 align-middle cru-table" id="{{ $cruTableId }}">
                                        <thead>
                                            <tr>
                                                <th class="text-center cru-col-sno" data-col="sno">S. No.</th>
                                                <th class="cru-col-course" data-col="course">Course Name</th>
                                                <th class="cru-col-subject" data-col="subject">Major Subject Name</th>
                                                <th class="cru-col-topic" data-col="topic">Topic Name</th>
                                                <th class="text-center cru-col-action" data-col="action">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($documents as $document)
                                            <tr>
                                                <td class="text-center cru-col-sno">{{ $loop->iteration }}</td>
                                                <td class="cru-col-course">
                                                    @if($document->detail && $document->detail->course)
                                                        {{ $document->detail->course->course_name }}
                                                    @else
                                                        NA
                                                    @endif
                                                </td>
                                                <td class="cru-col-subject">
                                                    @if($document->detail && $document->detail->subject)
                                                        {{ $document->detail->subject->subject_name }}
                                                    @else
                                                        NA
                                                    @endif
                                                </td>
                                                <td class="cru-col-topic">
                                                    @if($document->detail && $document->detail->topic)
                                                        {{ $document->detail->topic->subject_topic }}
                                                    @else
                                                        NA
                                                    @endif
                                                </td>
                                                <td class="text-center cru-col-action">
                                                    @include('admin.course-repository.user.partials.document-actions', [
                                                        'detailPk' => $document->pk,
                                                        'detail' => $document,
                                                        'fileDoc' => $document->documents->first(),
                                                    ])
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="cru-table-footer px-3 py-3 border-top">
                                    <p class="small text-muted mb-0">
                                        Showing <span class="fw-semibold text-dark">{{ $documents->count() }}</span> of
                                        <span class="fw-semibold text-dark">{{ $documents->count() }}</span> items
                                    </p>
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="tab-pane fade" id="archive" role="tabpanel" aria-labelledby="archive-tab">
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-archive me-2" aria-hidden="true"></i>
                                No archived documents found.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

@include('admin.course-repository.user.partials.pdf-details-modal')
@include('admin.course-repository.user.partials.assets')
@if(!empty($documents) && $documents->isNotEmpty())
@push('scripts')
@include('admin.course-repository.user.partials.column-toggle-script', [
    'cruTableId' => 'cruWeekDetailTable',
    'cruColumnStorageKey' => 'cru-user-week-detail-columns',
    'cruColumns' => [
        ['key' => 'sno', 'label' => 'S. No.', 'locked' => true],
        ['key' => 'course', 'label' => 'Course Name', 'default' => true],
        ['key' => 'subject', 'label' => 'Major Subject Name', 'default' => true],
        ['key' => 'topic', 'label' => 'Topic Name', 'default' => true],
        ['key' => 'action', 'label' => 'Action', 'locked' => true],
    ],
])
@endpush
@endif
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('pdfDetailsModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const documentId = button.getAttribute('data-document-id');

            fetch(`/course-repository-user/document/${documentId}/details`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('modal-author').textContent = data.document.author;
                        document.getElementById('modal-subject').textContent = data.document.subject;
                        document.getElementById('modal-topic').textContent = data.document.topic;
                        document.getElementById('modal-keyword').textContent = data.document.keyword;
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    }
});
</script>
@endsection
