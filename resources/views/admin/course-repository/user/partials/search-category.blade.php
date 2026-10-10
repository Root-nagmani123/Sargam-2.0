@php
    use App\Support\CourseRepositorySearch;

    $tokens = $tokens ?? [];
    // Everything above the folder itself, so the card says where in the tree it sits.
    $parentTrail = array_slice($category['trail'], 0, -1);
@endphp

<div class="cru-cat-card">
    <a href="{{ route('admin.course-repository.user.show', $category['pk']) }}"
       class="cru-cat-main"
       title="{{ implode(' / ', $category['trail']) }}">
        <span class="cru-cat-icon" aria-hidden="true"><i class="bi bi-folder2-open"></i></span>
        <span class="cru-cat-text">
            <span class="cru-cat-name">{!! CourseRepositorySearch::highlight($category['name'], $tokens, 70) !!}</span>
            @if($parentTrail)
                <span class="cru-cat-trail">{!! CourseRepositorySearch::highlight(CourseRepositorySearch::trailLabel($parentTrail, 2), $tokens) !!}</span>
            @else
                <span class="cru-cat-trail cru-cat-trail-root">Top-level category</span>
            @endif
        </span>
    </a>

    <div class="cru-cat-foot">
        <span class="cru-cat-count">
            <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
            {{ number_format($count) }} {{ Str::plural('document', $count) }}
        </span>
        <a href="{{ $searchUrl(['folder' => $category['pk'], 'type' => CourseRepositorySearch::TYPE_ALL]) }}"
           class="cru-cat-scope"
           title="Restrict the search to this category and everything under it">
            <i class="bi bi-search" aria-hidden="true"></i>
            Search inside
        </a>
    </div>
</div>
