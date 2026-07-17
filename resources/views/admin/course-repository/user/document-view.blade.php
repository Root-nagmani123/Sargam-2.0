@extends('admin.layouts.master')

@section('title', 'Document View | Course Repository Admin')

@section('setup_content')
<div class="document-viewer-wrapper cru-page">
    <div class="pdf-viewer-header">
        <div class="container-fluid">
            <x-breadcrum title="{{ $pdfDocument->upload_document ?? $pdfDocument->file_title ?? 'Document' }} View " :showBack="true"></x-breadcrum>
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-link" aria-label="Menu">
                        <span class="material-icons material-symbols-rounded">menu</span>
                    </button>
                    <span class="fw-semibold text-truncate">{{ $pdfDocument->upload_document ?? $pdfDocument->file_title ?? 'Document' }}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-secondary" aria-label="Previous page">
                            <span class="material-icons material-symbols-rounded">chevron_left</span>
                        </button>
                        <span class="btn btn-outline-secondary disabled small px-3">1 / 2</span>
                        <button class="btn btn-outline-secondary" type="button" aria-label="Next page">
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pdf-viewer-content">
        <div class="container-fluid px-4 py-4">
            <div class="row justify-content-center">
                <div class="col-xl-10">
                    <div class="pdf-container bg-white shadow-sm rounded-3 p-3 p-md-4">
                        @if(!empty($pdfViewUrl))
                            <iframe src="{{ $pdfViewUrl }}"
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
