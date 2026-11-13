@php
    // Compact entry point into the universal search page. Pass `folder` to scope the
    // search to a category (and everything under it), `inputId` to keep ids unique
    // when more than one launcher is on a page.
    $inputId = $inputId ?? 'cruLaunchSearch';
    $folder = $folder ?? null;
    $placeholder = $placeholder ?? 'Search the whole repository — documents, topics, subjects, faculty…';
@endphp

<form method="GET"
      action="{{ route('admin.course-repository.user.search') }}"
      class="cru-search-launcher mb-4"
      role="search">
    @if($folder !== null)
        <input type="hidden" name="folder" value="{{ $folder }}">
    @endif

    <div class="cru-search-box">
        <i class="bi bi-search cru-search-box-icon" aria-hidden="true"></i>
        <label for="{{ $inputId }}" class="visually-hidden">Search the Course Repository</label>
        <input type="search"
               class="form-control cru-search-input"
               id="{{ $inputId }}"
               name="q"
               placeholder="{{ $placeholder }}"
               autocomplete="off"
               spellcheck="false">
        <button type="submit" class="btn cru-search-submit">
            <i class="bi bi-search d-md-none" aria-hidden="true"></i>
            <span class="d-none d-md-inline">Search</span>
        </button>
    </div>
</form>
