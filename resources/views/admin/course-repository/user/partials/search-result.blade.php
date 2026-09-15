@php
    use App\Support\CourseRepositorySearch;

    $tokens = $tokens ?? [];

    $extension = strtolower(pathinfo((string) $doc->upload_document, PATHINFO_EXTENSION));
    $icon = match (true) {
        $doc->display_video !== null => ['bi-play-btn-fill', 'cru-sr-icon-video'],
        $extension === 'pdf' => ['bi-file-earmark-pdf-fill', 'cru-sr-icon-pdf'],
        in_array($extension, ['ppt', 'pptx'], true) => ['bi-file-earmark-slides-fill', 'cru-sr-icon-slides'],
        in_array($extension, ['doc', 'docx'], true) => ['bi-file-earmark-word-fill', 'cru-sr-icon-doc'],
        in_array($extension, ['xls', 'xlsx', 'csv'], true) => ['bi-file-earmark-spreadsheet-fill', 'cru-sr-icon-sheet'],
        in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) => ['bi-file-earmark-image-fill', 'cru-sr-icon-image'],
        default => ['bi-file-earmark-fill', 'cru-sr-icon-file'],
    };

    // The topic is worth its own line only when it says something the title doesn't.
    $showTopic = $doc->display_topic
        && mb_strtolower(trim($doc->display_topic)) !== mb_strtolower(trim($doc->display_title));

    $viewUrl = $doc->detail_pk
        ? route('admin.course-repository.user.document-view', $doc->detail_pk)
        : route('course-repository.document.stream', ['pk' => $doc->pk]);

    $meta = array_values(array_filter([
        $doc->display_course ? ['bi-mortarboard', $doc->display_course] : null,
        $doc->display_subject ? ['bi-journal-bookmark', $doc->display_subject] : null,
        $doc->display_date ? ['bi-calendar3', $doc->display_date] : null,
        $doc->display_author ? ['bi-person', $doc->display_author] : null,
    ]));
@endphp

<li class="cru-sr-item">
    <div class="cru-sr-icon {{ $icon[1] }}" aria-hidden="true">
        <i class="bi {{ $icon[0] }}"></i>
    </div>

    <div class="cru-sr-body">
        <h3 class="cru-sr-title">
            <a href="{{ $viewUrl }}" class="cru-sr-title-link">{!! CourseRepositorySearch::highlight($doc->display_title, $tokens, 160) !!}</a>
        </h3>

        @if($showTopic)
            <p class="cru-sr-topic">{!! CourseRepositorySearch::highlight($doc->display_topic, $tokens, 200) !!}</p>
        @endif

        @if($meta)
            <ul class="cru-sr-meta">
                @foreach($meta as [$metaIcon, $metaValue])
                    <li><i class="bi {{ $metaIcon }}" aria-hidden="true"></i>{!! CourseRepositorySearch::highlight($metaValue, $tokens, 70) !!}</li>
                @endforeach
            </ul>
        @endif

        <div class="cru-sr-foot">
            @if($doc->folder_pk && $doc->folder_trail)
                <a href="{{ route('admin.course-repository.user.show', $doc->folder_pk) }}"
                   class="cru-sr-trail"
                   title="Open {{ implode(' / ', $doc->folder_trail) }}">
                    <i class="bi bi-folder2" aria-hidden="true"></i>
                    <span>{!! CourseRepositorySearch::highlight(CourseRepositorySearch::trailLabel($doc->folder_trail), $tokens) !!}</span>
                </a>
            @endif

            <div class="cru-sr-actions">
                <a href="{{ $viewUrl }}" class="btn btn-sm cru-sr-btn cru-sr-btn-primary">
                    <i class="bi bi-eye" aria-hidden="true"></i><span>View</span>
                </a>

                @if($doc->display_video && $doc->detail_pk)
                    <a href="{{ route('admin.course-repository.user.document-video', $doc->detail_pk) }}"
                       class="btn btn-sm cru-sr-btn cru-sr-btn-video">
                        <i class="bi bi-play-btn" aria-hidden="true"></i><span>Video</span>
                    </a>
                @endif

                @if($doc->upload_document)
                    <a href="{{ route('course-repository.document.download', $doc->pk) }}?file={{ urlencode($doc->upload_document) }}"
                       class="btn btn-sm cru-sr-btn">
                        <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
</li>
