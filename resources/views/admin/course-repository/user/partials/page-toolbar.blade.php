@php
    $showViewToggle = $showViewToggle ?? false;
@endphp
@if($showViewToggle)
<div class="cru-toolbar d-flex justify-content-end mb-3">
    <div class="btn-group cru-view-toggle" role="group" aria-label="Repository view mode">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-cru-view="grid" aria-pressed="false" title="List view">
            <i class="bi bi-list-ul" aria-hidden="true"></i>
            <span class="ms-1">Grid</span>
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm active" data-cru-view="card" aria-pressed="true" title="Card view">
            <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
            <span class="ms-1">Card</span>
        </button>
    </div>
</div>
@endif
