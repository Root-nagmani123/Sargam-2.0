<div class="hn-sections d-flex flex-wrap align-items-start gap-1" role="menubar" aria-label="Communications navigation sections">

    {{-- COMMUNICATIONS --}}
    <div class="hn-dropdown" role="none">
        <button class="hn-section-btn" role="menuitem" aria-haspopup="true" aria-expanded="false">
            <i class="material-icons material-symbols-rounded" aria-hidden="true">chat</i>
            <span>Communications</span>
            <i class="material-icons material-symbols-rounded hn-arrow" aria-hidden="true">expand_more</i>
        </button>
        <div class="hn-dropdown-panel" role="menu">
            <x-menu.communication_setup />
        </div>
    </div>

</div>
