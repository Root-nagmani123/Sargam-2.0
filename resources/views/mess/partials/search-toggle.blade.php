{{--
    Mess Management — the design's search control: an icon button that reveals the
    search input, sitting immediately right of the Columns pill
    (Sargam 2.0.pdf, every Mess screen; docs/new-design-index-page.md §2 "Toggle").

    Two forms, depending on what owns the search on that page:

      DataTables grid — the enhancer relocates DataTables' own input into the slot:
        @include('mess.partials.search-toggle', ['tableId' => 'storesTable'])

      Page-owned input (the Laravel-paginated reports drive their own GET filter):
        give the existing <input> `d-none` when no search is active, then
        @include('mess.partials.search-toggle', ['inputId' => 'ssrSearch'])

    Starts open when a search is already applied, so a shared/reloaded URL doesn't
    hide the term that is filtering the grid.
--}}
@php
    $tableId = $tableId ?? null;
    $inputId = $inputId ?? null;
    $openOnLoad = $openOnLoad ?? filled(request('search'));
    $key = $tableId ?: $inputId;
@endphp

@once
@push('styles')
<style>
    /* Icon button: light-blue rounded square with a blue glyph, matching the mock. */
    .mess-search-toggle {
        width: var(--ds-control-h, 40px);
        height: var(--ds-control-h, 40px);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        padding: 0;
        border: 1px solid transparent;
        border-radius: var(--ds-radius-2, 8px);
        background: #eaf1fb;
        color: var(--ds-primary, #004384);
        line-height: 1;
    }

    .mess-search-toggle:hover,
    .mess-search-toggle[aria-expanded="true"] {
        background: #dbe7f7;
        border-color: var(--ds-primary, #004384);
        color: var(--ds-primary, #004384);
    }

    .mess-search-toggle i { font-size: 1.05rem; line-height: 1; }

    /* The revealed input sits to the LEFT of the button, as in the design. */
    .mess-search-wrap { gap: .5rem; }
    .mess-search-wrap .programme-dt-search { margin: 0; }
</style>
@endpush
@endonce

<div class="mess-search-wrap d-inline-flex align-items-center">
    @if($tableId)
        <div class="programme-dt-search {{ $openOnLoad ? '' : 'd-none' }}"
             data-dt-search-for="{{ $tableId }}"
             data-mess-search-panel="{{ $key }}"></div>
    @endif

    <button type="button" class="btn mess-search-toggle"
            data-mess-search-toggle="{{ $key }}"
            @if($inputId) data-mess-search-input="{{ $inputId }}" @endif
            aria-expanded="{{ $openOnLoad ? 'true' : 'false' }}"
            title="Search" aria-label="Search">
        <i class="bi bi-search" aria-hidden="true"></i>
    </button>
</div>

@once
@push('scripts')
<script>
(function () {
    // One delegated handler for every toggle on the page.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-mess-search-toggle]');
        if (!btn) return;

        var key = btn.getAttribute('data-mess-search-toggle');
        var inputId = btn.getAttribute('data-mess-search-input');
        var panel = inputId
            ? document.getElementById(inputId)
            : document.querySelector('[data-mess-search-panel="' + key + '"]');
        if (!panel) return;

        var open = panel.classList.contains('d-none');
        panel.classList.toggle('d-none', !open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (open) {
            var field = inputId ? panel : panel.querySelector('input');
            if (field) field.focus();
        }
    });

    // Escape closes an empty box; a box holding a term stays put, so the user can
    // always see what is filtering the grid.
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var field = e.target;
        if (!field || field.tagName !== 'INPUT' || (field.value || '').trim() !== '') return;

        // Page-owned input: the toggle names it by id.
        var btn = field.id
            ? document.querySelector('[data-mess-search-input="' + field.id + '"]')
            : null;
        var panel = btn ? field : null;

        // DataTables slot: the input lives inside the panel the toggle owns.
        if (!btn) {
            panel = field.closest('[data-mess-search-panel]');
            if (!panel) return;
            btn = document.querySelector(
                '[data-mess-search-toggle="' + panel.getAttribute('data-mess-search-panel') + '"]'
            );
        }
        if (!btn || !panel) return;

        panel.classList.add('d-none');
        btn.setAttribute('aria-expanded', 'false');
        btn.focus();
    });
})();
</script>
@endpush
@endonce
