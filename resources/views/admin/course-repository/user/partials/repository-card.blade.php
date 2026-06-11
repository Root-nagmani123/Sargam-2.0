@php
    $displayName = $repository->course_repository_name;
    $bannerTitle = Str::upper($displayName);
    $hasImage = !empty($repository->category_image) && \Storage::disk('public')->exists($repository->category_image);
    $imageUrl = $hasImage ? asset('storage/' . $repository->category_image) : null;

    $repositoryName = strtolower($displayName);
    $routeUrl = $cardRoute ?? null;
    if (!$routeUrl) {
        if (strpos($repositoryName, 'foundation course') !== false) {
            $routeUrl = route('admin.course-repository.user.foundation-course');
        } else {
            $routeUrl = route('admin.course-repository.user.show', $repository->pk);
        }
    }

    $subCategoryCount = $repository->children->count() ?? 0;
    $attachmentCount = $repository->documents->count() ?? 0;
    $subCategoryText = $subCategoryCount . ' ' . Str::plural('Sub-category', $subCategoryCount);
    $attachmentText = $attachmentCount . ' ' . Str::plural('Attachment', $attachmentCount);
@endphp
<div class="col-sm-6 col-lg-4 cru-card-col">
    <article class="card course-card h-100 overflow-hidden border-0 shadow-sm rounded-4">
        <div class="cru-card-banner position-relative rounded-top-4">
            @if($imageUrl)
                <img src="{{ $imageUrl }}"
                     alt="{{ $displayName }}"
                     class="cru-card-banner-img"
                     loading="lazy"
                     onerror="this.remove()">
            @else
            <div class="cru-card-banner-text">
                <span class="cru-card-banner-label">{{ $bannerTitle }}</span>
            </div>
            @endif
        </div>
        <div class="card-body d-flex flex-column text-start px-3 px-md-4 py-3 py-md-4">
            <h5 class="card-title fw-bold mb-2 lh-sm text-dark">{{ $displayName }}</h5>
            <p class="text-muted small mb-4 d-flex flex-wrap gap-3">
                @if($metaLabel ?? false)
                    <span><i class="bi bi-diagram-3 me-1" aria-hidden="true"></i>{{ $metaLabel }}</span>
                @else
                    <span><i class="bi bi-diagram-3 me-1" aria-hidden="true"></i>{{ $subCategoryText }}</span>
                    <span><i class="bi bi-paperclip me-1" aria-hidden="true"></i>{{ $attachmentText }}</span>
                @endif
            </p>

            <div class="mt-auto">
                <a href="{{ $routeUrl }}" class="btn btn-primary w-100 fw-semibold cru-btn-primary">
                    Click Here
                </a>
            </div>
        </div>
    </article>
</div>
