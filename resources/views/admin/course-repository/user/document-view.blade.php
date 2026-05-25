@extends('admin.layouts.master')

@section('title', 'Document View | Course Repository Admin')

@section('setup_content')
<div class="document-viewer-wrapper cru-page">
    <div class="pdf-viewer-header bg-white shadow-sm sticky-top">
        <div class="container-fluid px-3 px-md-4 py-2">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2 gap-md-3 min-w-0">
                    <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Menu">
                        <i class="bi bi-list" aria-hidden="true"></i>
                    </button>
                    <span class="fw-semibold text-truncate">{{ $pdfDocument->document_path ?? 'Document' }}</span>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Page navigation">
                        <button class="btn btn-outline-secondary" type="button" aria-label="Previous page">
                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        </button>
                        <span class="btn btn-outline-secondary disabled small px-3">1 / 2</span>
                        <button class="btn btn-outline-secondary" type="button" aria-label="Next page">
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Zoom controls">
                        <button class="btn btn-outline-secondary" type="button" aria-label="Zoom out">−</button>
                        <span class="btn btn-outline-secondary disabled small px-2">100%</span>
                        <button class="btn btn-outline-secondary" type="button" aria-label="Zoom in">+</button>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Rotate">
                        <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Fit to page">
                        <i class="bi bi-arrows-fullscreen" aria-hidden="true"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Download">
                        <i class="bi bi-download" aria-hidden="true"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="Print">
                        <i class="bi bi-printer" aria-hidden="true"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" type="button" aria-label="Close" onclick="window.history.back()">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="pdf-viewer-content">
        <div class="container-fluid px-3 px-md-4 py-4">
            <div class="row justify-content-center">
                <div class="col-xl-10">
                    <div class="pdf-container bg-white shadow-sm rounded-3 p-3 p-md-4">
                        @if($pdfDocument && Storage::exists($pdfDocument->document_path))
                            <iframe src="{{ Storage::url($pdfDocument->document_path) }}"
                                    class="w-100 rounded-2"
                                    style="height: 80vh; border: none;"
                                    title="PDF Document Viewer">
                            </iframe>
                        @else
                            <div class="text-center py-5">
                                <i class="bi bi-file-earmark-pdf text-danger display-4 d-block mb-3" aria-hidden="true"></i>
                                <p class="text-muted mb-0">PDF document not available</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.course-repository.user.partials.assets')
@endsection
