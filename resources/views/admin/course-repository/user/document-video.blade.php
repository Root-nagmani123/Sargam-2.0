@extends('admin.layouts.master')

@section('title', 'Video | Course Repository Admin')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                        <h4 class="mb-0 fw-bold text-primary">
                            <i class="bi bi-play-btn me-2" aria-hidden="true"></i>Video Content
                        </h4>
                        <button type="button"
                                onclick="window.history.back()"
                                class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Back
                        </button>
                    </div>
                    @if($document->videolink)
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <a href="{{ $document->videolink }}"
                               class="btn btn-sm btn-outline-primary"
                               target="_blank"
                               rel="noopener noreferrer">
                                <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>Open video link
                            </a>
                            @if(preg_match('/\.(mp4|webm|ogg|mov|m4v)(\?.*)?$/i', $document->videolink))
                                <a href="{{ $document->videolink }}"
                                   class="btn btn-sm btn-primary"
                                   download>
                                    <i class="bi bi-download me-1" aria-hidden="true"></i>Download video
                                </a>
                            @endif
                        </div>
                        <div class="ratio ratio-16x9 rounded-3 overflow-hidden shadow-sm">
                            <iframe src="{{ $document->video_embed_url }}"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    allowfullscreen
                                    title="Video Content">
                            </iframe>
                        </div>
                    @else
                        <div class="alert alert-light border text-center rounded-3 py-5 mb-0">
                            <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                            No video link available for this document.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.course-repository.user.partials.assets')
@endsection
