{{--
    Toolbar row (new-design-index-page.md §2): filters on the left, search on the
    right. Grids with nothing to filter by pass no `filters` slot and get the
    search alone, right-aligned — which is the documented shape for a grid with
    no filters (§1's "no status pills" rule applied to the toolbar row).

    Usage — search only:
        <x-programme-dt-toolbar :action="route('master.state.index')"
                                placeholder="Search state" />

    Usage — with filters:
        <x-programme-dt-toolbar :action="route('…')" placeholder="Search …">
            <x-slot:filters>
                <div class="programme-dt-filter-select"><select …></select></div>
            </x-slot:filters>
        </x-programme-dt-toolbar>
--}}
@props([
    'action',
    'placeholder' => 'Search',
    'label' => null,
    'name' => 'search',
    'value' => null,
    'perPage' => '10',
    'filters' => null,
])

@php
    // NOT filled($filters): a ComponentSlot is an OBJECT, and blank()/filled() only
    // trim strings — so filled() is true even for an empty slot, which renders a
    // stray "Filters" label with nothing beside it. Cast to string first.
    $hasFilters = trim((string) $filters) !== '';
@endphp

<div {{ $attributes->class([
        'd-flex flex-column flex-lg-row align-items-lg-center gap-3 mb-4 programme-dt-toolbar',
        'justify-content-between' => $hasFilters,
        'justify-content-end' => ! $hasFilters,
    ]) }}>

    @if ($hasFilters)
        <div class="d-flex flex-wrap align-items-center gap-3">
            <span class="programme-dt-filters-label">Filters</span>
            {{ $filters }}
        </div>
    @endif

    <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
        {{-- Anything the page wants beside the search (a Columns button, say). --}}
        {{ $slot }}

        <x-programme-dt-search :action="$action" :name="$name" :placeholder="$placeholder"
            :label="$label" :value="$value">
            {{-- per_page must ride along or searching silently resets the page size. --}}
            <input type="hidden" name="per_page" value="{{ request('per_page', $perPage) }}">
        </x-programme-dt-search>
    </div>
</div>
