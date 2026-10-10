@extends('admin.layouts.master')

@section('title', 'Search | Course Repository')

@section('setup_content')
@php
    use App\Support\CourseRepositorySearch;

    $crumbItems = [
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Academic', 'url' => null],
        ['label' => 'Course Repository', 'url' => route('admin.course-repository.user.index')],
        'Search',
    ];

    // Every control on this page is a link that keeps the rest of the search intact,
    // so the URL alone always describes the full result set (shareable, bookmarkable,
    // and correct on back/forward).
    $baseParams = [
        'q' => $criteria['q'],
        'type' => $criteria['type'],
        'sort' => $criteria['sort'],
        'course' => $criteria['course'],
        'subject' => $criteria['subject'],
        'author' => $criteria['author'],
        'year' => $criteria['year'],
        'from' => $criteria['from'],
        'to' => $criteria['to'],
        'folder' => $criteria['folder'],
        'per_page' => $criteria['per_page'] === 25 ? '' : $criteria['per_page'],
    ];

    $searchUrl = function (array $overrides = []) use ($baseParams) {
        $params = array_filter(
            array_merge($baseParams, $overrides),
            fn ($value) => $value !== null && $value !== ''
        );

        return route('admin.course-repository.user.search')
            . ($params === [] ? '' : '?' . http_build_query($params));
    };

    $exampleSearches = ['Public Administration', 'Phase-I 2024', 'Law', 'Ethics', 'Week 04'];
    $hasRefinements = CourseRepositorySearch::hasDocumentFilters($criteria);
@endphp

<div class="cru-page cru-search-page">
    <div class="container-fluid px-3 px-md-4 py-4" id="cru-user-main">
        <x-breadcrum title="Search the Course Repository" :items="$crumbItems" />

        @include('admin.course-repository.user.partials.flash-alert')

        {{-- ── Search box ─────────────────────────────────────────────────── --}}
        <form method="GET" action="{{ route('admin.course-repository.user.search') }}" id="cruSearchForm" role="search" novalidate>
            {{-- Carried through the form so a new query or an Apply keeps the tab,
                 the chosen scope, the sort order and the page size. --}}
            <input type="hidden" name="type" value="{{ $criteria['type'] }}">
            <input type="hidden" name="per_page" value="{{ $criteria['per_page'] }}">
            @if($tokens !== [])
                {{-- Omitted while the box is empty: criteria() downgrades "Best match"
                     to "Newest" when there are no words, and echoing that back would
                     stop relevance ranking from ever returning once words are typed. --}}
                <input type="hidden" name="sort" value="{{ $criteria['sort'] }}">
            @endif
            @if($criteria['folder'] !== null)
                <input type="hidden" name="folder" value="{{ $criteria['folder'] }}">
            @endif

            <div class="card cru-search-hero border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body p-3 p-md-4">
                    @if($folderTrail)
                        <p class="cru-search-scope">
                            <i class="bi bi-folder2-open" aria-hidden="true"></i>
                            Searching inside <strong>{{ implode(' / ', $folderTrail) }}</strong>
                            <a href="{{ $searchUrl(['folder' => null]) }}" class="cru-search-scope-clear">Search everywhere instead</a>
                        </p>
                    @endif

                    <div class="cru-search-box" data-cru-suggest-root>
                        <i class="bi bi-search cru-search-box-icon" aria-hidden="true"></i>
                        <label for="cruSearchInput" class="visually-hidden">Search the Course Repository</label>
                        <input type="search"
                               class="form-control cru-search-input"
                               id="cruSearchInput"
                               name="q"
                               value="{{ $criteria['q'] }}"
                               placeholder="Search documents, topics, subjects, courses, faculty, categories…"
                               autocomplete="off"
                               spellcheck="false"
                               aria-label="Search the Course Repository"
                               aria-expanded="false"
                               aria-controls="cruSuggestList"
                               data-cru-suggest-url="{{ route('admin.course-repository.user.search.suggest') }}">
                        <button type="submit" class="btn cru-search-submit">
                            <i class="bi bi-search d-md-none" aria-hidden="true"></i>
                            <span class="d-none d-md-inline">Search</span>
                        </button>

                        <ul class="cru-suggest-list" id="cruSuggestList" role="listbox" hidden></ul>
                    </div>

                    @unless($hasQuery)
                        <div class="cru-search-examples">
                            <span>Try</span>
                            @foreach($exampleSearches as $example)
                                <a href="{{ $searchUrl(['q' => $example, 'sort' => CourseRepositorySearch::SORT_RELEVANCE]) }}"
                                   class="cru-chip cru-chip-example">{{ $example }}</a>
                            @endforeach
                        </div>
                    @endunless
                </div>
            </div>

            {{-- ── Type tabs + refinements ─────────────────────────────────── --}}
            <div class="card cru-search-controls border-0 shadow-sm rounded-4 mb-4">
                <div class="cru-search-tabs" role="tablist" aria-label="Result type">
                    @foreach(CourseRepositorySearch::TYPES as $typeKey => $typeLabel)
                        <a href="{{ $searchUrl(['type' => $typeKey]) }}"
                           class="cru-search-tab {{ $criteria['type'] === $typeKey ? 'active' : '' }}"
                           aria-current="{{ $criteria['type'] === $typeKey ? 'page' : 'false' }}">
                            {{ $typeLabel }}
                            @if($hasQuery && $typeKey === CourseRepositorySearch::TYPE_CATEGORIES && $categoryTotal > 0)
                                <span class="cru-search-tab-count">{{ number_format($categoryTotal) }}</span>
                            @elseif($hasQuery && $typeKey === $criteria['type'] && $documentTotal > 0)
                                <span class="cru-search-tab-count">{{ number_format($documentTotal) }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>

                <div class="cru-search-refine">
                    <div class="cru-refine-grid">
                        <div class="cru-refine-field">
                            <label for="cruRefineCourse">Course / Batch</label>
                            <select class="form-select form-select-sm" id="cruRefineCourse" name="course">
                                <option value="">All courses</option>
                                @foreach($facets['courses'] as $course)
                                    <option value="{{ $course }}" @selected($criteria['course'] === $course)>{{ $course }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="cru-refine-field">
                            <label for="cruRefineSubject">Subject</label>
                            <select class="form-select form-select-sm" id="cruRefineSubject" name="subject">
                                <option value="">All subjects</option>
                                @foreach($facets['subjects'] as $subject)
                                    <option value="{{ $subject }}" @selected($criteria['subject'] === $subject)>{{ $subject }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="cru-refine-field">
                            <label for="cruRefineAuthor">Faculty / Author</label>
                            <input type="text"
                                   class="form-control form-control-sm"
                                   id="cruRefineAuthor"
                                   name="author"
                                   value="{{ $criteria['author'] }}"
                                   placeholder="Any name"
                                   autocomplete="off">
                        </div>

                        <div class="cru-refine-field cru-refine-field-sm">
                            <label for="cruRefineYear">Year</label>
                            <select class="form-select form-select-sm" id="cruRefineYear" name="year">
                                <option value="">Any year</option>
                                @foreach($facets['years'] as $year)
                                    <option value="{{ $year }}" @selected($criteria['year'] === (string) $year)>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="cru-refine-field cru-refine-field-sm">
                            <label for="cruRefineFrom">Session from</label>
                            <input type="date" class="form-control form-control-sm" id="cruRefineFrom" name="from" value="{{ $criteria['from'] }}">
                        </div>

                        <div class="cru-refine-field cru-refine-field-sm">
                            <label for="cruRefineTo">Session to</label>
                            <input type="date" class="form-control form-control-sm" id="cruRefineTo" name="to" value="{{ $criteria['to'] }}">
                        </div>

                        <div class="cru-refine-actions">
                            <button type="submit" class="btn btn-sm cru-sr-btn-primary cru-refine-apply">Apply</button>
                            @if($hasRefinements)
                                <a href="{{ $searchUrl(['course' => null, 'subject' => null, 'author' => null, 'year' => null, 'from' => null, 'to' => null]) }}"
                                   class="btn btn-sm btn-outline-danger">Clear filters</a>
                            @endif
                        </div>
                    </div>

                    @if($chips)
                        <ul class="cru-chip-row">
                            @foreach($chips as $chip)
                                <li>
                                    <span class="cru-chip cru-chip-active">
                                        <span class="cru-chip-key">{{ $chip['label'] }}:</span>
                                        {{ Str::limit($chip['value'], 40) }}
                                        <a href="{{ $searchUrl([$chip['key'] => null]) }}"
                                           class="cru-chip-remove"
                                           aria-label="Remove {{ $chip['label'] }} filter">&times;</a>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </form>

        @if(! $hasQuery)
            {{-- ── Nothing asked for yet ───────────────────────────────────── --}}
            <div class="card border-0 shadow-sm rounded-4 cru-search-intro">
                <div class="card-body text-center py-5 px-3">
                    <span class="cru-empty-icon d-inline-flex align-items-center justify-content-center rounded-circle mb-3">
                        <i class="bi bi-search fs-2" aria-hidden="true"></i>
                    </span>
                    <h2 class="h5 fw-semibold text-dark mb-2">Search across the entire Course Repository</h2>
                    <p class="text-muted small mb-4 mx-auto" style="max-width: 40rem;">
                        One box for everything filed in the repository — session documents, presentations,
                        recorded videos and category folders. Search by document title, topic, subject,
                        course or batch, faculty name, session date or folder name.
                    </p>
                    <a href="{{ route('admin.course-repository.user.index') }}" class="btn btn-sm cru-sr-btn-primary">
                        <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
                        Browse all categories
                    </a>
                </div>
            </div>
        @else
            {{-- ── Matching categories ─────────────────────────────────────── --}}
            @if($criteria['type'] === CourseRepositorySearch::TYPE_ALL && $categoryPreview->isNotEmpty())
                <section class="cru-search-section" aria-labelledby="cruCategoryHeading">
                    <div class="cru-section-head">
                        <h2 class="cru-section-title" id="cruCategoryHeading">
                            Matching categories
                            <span class="cru-section-count">{{ number_format($categoryTotal) }}</span>
                        </h2>
                        @if($categoryTotal > $categoryPreview->count())
                            <a href="{{ $searchUrl(['type' => CourseRepositorySearch::TYPE_CATEGORIES]) }}" class="cru-section-more">
                                See all {{ number_format($categoryTotal) }}
                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>

                    <div class="cru-cat-grid">
                        @foreach($categoryPreview as $category)
                            @include('admin.course-repository.user.partials.search-category', [
                                'category' => $category,
                                'count' => $categoryCounts[$category['pk']] ?? 0,
                                'tokens' => $tokens,
                                'searchUrl' => $searchUrl,
                            ])
                        @endforeach
                    </div>
                </section>
            @endif

            @if($criteria['type'] === CourseRepositorySearch::TYPE_CATEGORIES)
                {{-- ── Categories tab ──────────────────────────────────────── --}}
                <section class="cru-search-section" aria-labelledby="cruCategoryHeading">
                    <div class="cru-section-head">
                        <h2 class="cru-section-title" id="cruCategoryHeading">
                            @if($categoryTotal === 0)
                                No matching categories
                            @else
                                {{ number_format($categoryTotal) }} {{ Str::plural('category', $categoryTotal) }}
                                @if($criteria['q'] !== '') matching &ldquo;{{ $criteria['q'] }}&rdquo; @endif
                            @endif
                        </h2>
                    </div>

                    @if($categoryTotal === 0)
                        @include('admin.course-repository.user.partials.search-empty', ['criteria' => $criteria, 'searchUrl' => $searchUrl])
                    @else
                        <div class="cru-cat-grid">
                            @foreach($categories as $category)
                                @include('admin.course-repository.user.partials.search-category', [
                                    'category' => $category,
                                    'count' => $categoryCounts[$category['pk']] ?? 0,
                                    'tokens' => $tokens,
                                    'searchUrl' => $searchUrl,
                                ])
                            @endforeach
                        </div>

                        <div class="cru-search-pager">
                            {{ $categories->links() }}
                        </div>
                    @endif
                </section>
            @else
                {{-- ── Document / video results ────────────────────────────── --}}
                <section class="cru-search-section" aria-labelledby="cruResultsHeading">
                    <div class="cru-section-head">
                        <h2 class="cru-section-title" id="cruResultsHeading">
                            @if($documentTotal === 0)
                                No results
                            @else
                                {{ number_format($documentTotal) }}
                                {{ $criteria['type'] === CourseRepositorySearch::TYPE_VIDEOS ? Str::plural('video', $documentTotal) : Str::plural('result', $documentTotal) }}
                                @if($criteria['q'] !== '') for &ldquo;{{ $criteria['q'] }}&rdquo; @endif
                            @endif
                        </h2>

                        @if($documentTotal > 0)
                            <div class="cru-section-tools">
                                <label for="cruSortSelect" class="cru-tool-label">Sort</label>
                                <select class="form-select form-select-sm cru-tool-select" id="cruSortSelect" data-cru-nav>
                                    @foreach(CourseRepositorySearch::SORTS as $sortKey => $sortLabel)
                                        @continue($sortKey === CourseRepositorySearch::SORT_RELEVANCE && $tokens === [])
                                        <option value="{{ $searchUrl(['sort' => $sortKey]) }}" @selected($criteria['sort'] === $sortKey)>{{ $sortLabel }}</option>
                                    @endforeach
                                </select>

                                <label for="cruPerPageSelect" class="cru-tool-label">Show</label>
                                <select class="form-select form-select-sm cru-tool-select cru-tool-select-sm" id="cruPerPageSelect" data-cru-nav>
                                    @foreach([10, 25, 50, 100] as $size)
                                        <option value="{{ $searchUrl(['per_page' => $size === 25 ? '' : $size]) }}" @selected($criteria['per_page'] === $size)>{{ $size }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>

                    @if($documentTotal === 0)
                        @include('admin.course-repository.user.partials.search-empty', ['criteria' => $criteria, 'searchUrl' => $searchUrl])
                    @else
                        <ul class="cru-sr-list">
                            @foreach($documents as $doc)
                                @include('admin.course-repository.user.partials.search-result', ['doc' => $doc, 'tokens' => $tokens])
                            @endforeach
                        </ul>

                        <div class="cru-search-pager">
                            <p class="cru-pager-summary">
                                Showing <strong>{{ number_format($documents->firstItem()) }}</strong>–<strong>{{ number_format($documents->lastItem()) }}</strong>
                                of <strong>{{ number_format($documentTotal) }}</strong>
                            </p>
                            {{ $documents->links() }}
                        </div>
                    @endif
                </section>
            @endif
        @endif
    </div>
</div>

@include('admin.course-repository.user.partials.assets')
@include('admin.course-repository.user.partials.search-scripts')
@endsection
