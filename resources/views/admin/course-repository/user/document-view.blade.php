@extends('admin.layouts.timetable')

@section('title', 'Document View | Course Repository Admin')

@section('content')
<div class="document-viewer-wrapper">
    <!-- PDF Viewer Header -->
    <div class="pdf-viewer-header bg-white shadow-sm sticky-top">
        <div class="container-fluid px-4 py-2">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-link" aria-label="Menu">
                        <span class="material-icons material-symbols-rounded">menu</span>
                    </button>
                    <span class="fw-semibold">{{ $pdfDocument->document_path ?? 'Document' }}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-secondary" aria-label="Previous page">
                            <span class="material-icons material-symbols-rounded">chevron_left</span>
                        </button>
                        <span class="small">1 / 2</span>
                        <button class="btn btn-sm btn-outline-secondary" aria-label="Next page">
                            <span class="material-icons material-symbols-rounded">chevron_right</span>
                        </button>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button class="btn btn-sm btn-outline-secondary" aria-label="Zoom out">-</button>
                        <span class="small">100%</span>
                        <button class="btn btn-sm btn-outline-secondary" aria-label="Zoom in">+</button>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" aria-label="Rotate">
                        <span class="material-icons material-symbols-rounded">refresh</span>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" aria-label="Fit to page">
                        <span class="material-icons material-symbols-rounded">fullscreen</span>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" aria-label="Download">
                        <span class="material-icons material-symbols-rounded">download</span>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" aria-label="Print">
                        <span class="material-icons material-symbols-rounded">print</span>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" aria-label="Close" onclick="window.history.back()">
                        <span class="material-icons material-symbols-rounded">close</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- PDF Viewer Content -->
    <div class="pdf-viewer-content">
        <div class="container-fluid px-4 py-4">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="pdf-container bg-white shadow-sm p-4">
                        @if($pdfDocument && Storage::exists($pdfDocument->document_path))
                            <iframe src="{{ Storage::url($pdfDocument->document_path) }}" 
                                    class="w-100" 
                                    style="height: 80vh; border: none;"
                                    title="PDF Document Viewer">
                            </iframe>
                        @else
                            <div class="text-center py-5">
                                <span class="material-icons material-symbols-rounded text-danger" style="font-size: 4rem;">picture_as_pdf</span>
                                <p class="mt-3 text-muted">PDF document not available</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/course-repository-user.css') }}">
@endsection