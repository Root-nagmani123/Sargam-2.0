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
                        <i class="bi bi-arrow-left fs-4 text-dark"></i>
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

            <!-- Documents List -->
            <div class="documents-list mt-4">
                @forelse($documents as $document)
                    <div class="card document-item shadow-sm mb-3">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-1 text-center">
                                    <i class="bi bi-file-earmark-pdf-fill text-danger fs-3"></i>
                                </div>
                                <div class="col-md-6">
                                    <a href="{{ route('admin.course-repository.user.document-view', $document->pk) }}" 
                                       class="text-decoration-none fw-semibold text-primary">
                                        {{ $document->detail_document ?? 'Document ' . $document->pk }}
                                    </a>
                                    <p class="text-muted small mb-0 mt-1">
                                        Last modified {{ $document->modify_date ? $document->modify_date->format('d/m/Y H:i') : 'N/A' }}
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <a href="#" 
                                       class="text-decoration-none text-primary small"
                                       data-bs-toggle="modal" 
                                       data-bs-target="#pdfDetailsModal"
                                       data-document-id="{{ $document->pk }}">
                                        Click here for PDF details
                                    </a>
                                </div>
                                <div class="col-md-2 text-end">
                                    @if($document->videolink)
                                        <a href="{{ route('admin.course-repository.user.document-video', $document->pk) }}" 
                                           class="btn btn-sm btn-info">
                                            Open Video
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle me-2"></i>
                        No documents found for this week.
                    </div>
                @endforelse
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