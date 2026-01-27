@extends('admin.layouts.timetable')

@section('title', 'Video | Course Repository Admin')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="mb-0">Video Content</h4>
                        <button type="button" 
                                onclick="window.history.back()" 
                                class="btn btn-sm btn-outline-secondary">
                            <span class="material-icons material-symbols-rounded me-1">arrow_back</span> Back
                        </button>
                    </div>
                    @if($document->videolink)
                        <div class="ratio ratio-16x9">
                            <iframe src="{{ $document->videolink }}" 
                                    allowfullscreen 
                                    title="Video Content">
                            </iframe>
                        </div>
                    @else
                        <div class="alert alert-info text-center">
                            <span class="material-icons material-symbols-rounded me-2">info</span>
                            No video link available for this document.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/course-repository-user.css') }}">
@endsection