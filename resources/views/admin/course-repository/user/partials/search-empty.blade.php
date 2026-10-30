@php
    use App\Support\CourseRepositorySearch;

    $narrowed = CourseRepositorySearch::hasDocumentFilters($criteria) || $criteria['folder'] !== null;
@endphp

<div class="card border-0 shadow-sm rounded-4 cru-search-empty">
    <div class="card-body text-center py-5 px-3">
        <span class="cru-empty-icon d-inline-flex align-items-center justify-content-center rounded-circle mb-3">
            <i class="bi bi-search fs-2" aria-hidden="true"></i>
        </span>

        <h3 class="h6 fw-semibold text-dark mb-2">
            @if($criteria['q'] !== '')
                Nothing matched &ldquo;{{ Str::limit($criteria['q'], 60) }}&rdquo;
            @else
                Nothing matched these filters
            @endif
        </h3>

        <p class="text-muted small mb-3 mx-auto" style="max-width: 32rem;">
            @if($narrowed)
                The search is currently narrowed down. Widening it usually helps.
            @else
                Check the spelling, or search for fewer words — every word has to appear somewhere in a record.
            @endif
        </p>

        <div class="d-flex flex-wrap justify-content-center gap-2">
            @if($narrowed)
                <a href="{{ $searchUrl(['course' => null, 'subject' => null, 'author' => null, 'year' => null, 'from' => null, 'to' => null, 'folder' => null]) }}"
                   class="btn btn-sm cru-sr-btn-primary">Search everywhere</a>
            @endif
            @if($criteria['type'] !== CourseRepositorySearch::TYPE_ALL)
                <a href="{{ $searchUrl(['type' => CourseRepositorySearch::TYPE_ALL]) }}" class="btn btn-sm btn-outline-secondary">
                    Search everything, not just {{ strtolower(CourseRepositorySearch::TYPES[$criteria['type']]) }}
                </a>
            @endif
            <a href="{{ route('admin.course-repository.user.index') }}" class="btn btn-sm btn-outline-secondary">Browse categories</a>
        </div>
    </div>
</div>
