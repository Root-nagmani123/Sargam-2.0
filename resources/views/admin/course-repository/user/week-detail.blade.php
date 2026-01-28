@extends('admin.layouts.timetable')

@section('title', 'Week-{{ str_pad($weekNumber, 2, "0", STR_PAD_LEFT) }} | Course Repository Admin')

@section('content')
<div class="d-flex">
    <!-- Left Sidebar -->
    <aside class="course-sidebar-wrapper">
        <x-course-sidebar />
    </aside>

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

            <!-- Tab Content -->
            <div class="tab-content" id="weekTabContent">
                <!-- Active Tab -->
                <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
                    @if($documents->isEmpty())
                        <div class="alert alert-info text-center">
                            <span class="material-icons material-symbols-rounded me-2">info</span>
                            No documents found for this week.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead style="background-color: #dc3545; color: white;">
                                    <tr>
                                        <th class="text-center fw-bold">S.No.</th>
                                        <th class="text-center fw-bold">Course Name</th>
                                        <th class="text-center fw-bold">Major Subject Name</th>
                                        <th class="text-center fw-bold">Topic Name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($documents as $index => $document)
                                    <tr class="{{ $loop->odd ? 'table-light' : '' }}">
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="text-center">
                                            @if($document->detail && $document->detail->course)
                                                {{ $document->detail->course->course_name }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($document->detail && $document->detail->subject)
                                                {{ $document->detail->subject->subject_name }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($document->detail && $document->detail->topic)
                                                {{ $document->detail->topic->subject_topic }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- Archive Tab -->
                <div class="tab-pane fade" id="archive" role="tabpanel" aria-labelledby="archive-tab">
                    <div class="alert alert-info text-center">
                        <span class="material-icons material-symbols-rounded me-2">archive</span>
                        No archived documents found.
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- PDF Details Modal -->
@include('admin.course-repository.user.partials.pdf-details-modal')

<link rel="stylesheet" href="{{ asset('css/course-repository-user.css') }}">
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