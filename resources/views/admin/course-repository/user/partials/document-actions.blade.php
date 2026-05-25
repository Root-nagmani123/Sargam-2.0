@php
    $detailPk = $detailPk ?? ($doc?->course_repository_details_pk ?? null);
    $detail = $detail ?? ($doc?->detail ?? null);
    $fileDoc = $fileDoc ?? ($doc ?? null);
    $videoLink = trim((string) ($detail->videolink ?? ''));
    $hasVideo = $videoLink !== '';
    $hasPdfView = $detailPk || ($fileDoc?->public_file_url ?? null);
    $hasFileDownload = $fileDoc && ($fileDoc->pk ?? null);
    $isDirectVideoFile = $hasVideo && preg_match('/\.(mp4|webm|ogg|mov|m4v)(\?.*)?$/i', $videoLink);
@endphp
<div class="d-inline-flex align-items-center justify-content-center gap-2 cru-table-actions">
    @if($hasPdfView)
        @if($detailPk)
            <a href="{{ route('admin.course-repository.user.document-view', $detailPk) }}"
               class="btn btn-link btn-sm text-primary p-0 cru-btn-view"
               title="View document"
               aria-label="View document">
                <i class="bi bi-eye fs-5" aria-hidden="true"></i>
            </a>
        @elseif($fileDoc?->public_file_url)
            <a href="{{ $fileDoc->public_file_url }}"
               class="btn btn-link btn-sm text-primary p-0 cru-btn-view"
               target="_blank"
               rel="noopener noreferrer"
               title="View document"
               aria-label="View document in new tab">
                <i class="bi bi-eye fs-5" aria-hidden="true"></i>
            </a>
        @endif
    @endif

    @if($hasVideo && $detailPk)
        <a href="{{ route('admin.course-repository.user.document-video', $detailPk) }}"
           class="btn btn-link btn-sm text-danger p-0 cru-btn-video"
           title="View video"
           aria-label="View video">
            <i class="bi bi-play-btn fs-5" aria-hidden="true"></i>
        </a>
        <a href="{{ $videoLink }}"
           class="btn btn-link btn-sm text-danger p-0 cru-btn-video-download"
           @if($isDirectVideoFile) download @endif
           target="_blank"
           rel="noopener noreferrer"
           title="{{ $isDirectVideoFile ? 'Download video' : 'Open video link' }}"
           aria-label="{{ $isDirectVideoFile ? 'Download video' : 'Open video link' }}">
            <i class="bi bi-download fs-5" aria-hidden="true"></i>
        </a>
    @endif

    @if($hasFileDownload)
        <a href="{{ route('course-repository.document.download', $fileDoc->pk) }}?file={{ urlencode($fileDoc->upload_document) }}"
           class="btn btn-link btn-sm text-primary p-0 cru-btn-download"
           title="Download file"
           aria-label="Download file">
            <i class="bi bi-file-earmark-arrow-down fs-5" aria-hidden="true"></i>
        </a>
    @endif
</div>
