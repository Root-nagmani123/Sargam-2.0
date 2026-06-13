@php
    $showViewToggle = $showViewToggle ?? false;
@endphp
@if($showViewToggle)
<div class="cru-toolbar d-flex justify-content-end mb-0">
    <div class="cru-view-toggle cru-view-toggle-pill d-inline-flex align-items-center p-1 rounded-1 border bg-light" role="group" aria-label="Repository view mode">
        <button type="button"
                class="btn btn-sm border-0 rounded-1 cru-view-toggle-btn d-inline-flex align-items-center gap-1"
                data-cru-view="grid"
                aria-pressed="false"
                title="Grid list view">
            <i class="bi bi-list-ul" aria-hidden="true"></i>
            <span>Grid</span>
        </button>
        <button type="button"
                class="btn btn-sm border-0 rounded-1 cru-view-toggle-btn d-inline-flex align-items-center gap-1 active"
                data-cru-view="card"
                aria-pressed="true"
                title="Card view">
            <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
            <span>Card</span>
        </button>
    </div>
</div>
@endif
