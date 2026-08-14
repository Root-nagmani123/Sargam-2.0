{{--
    Toolbar search — server-side variant (new-design-index-page.md §2).

    The preferred variant is the empty .programme-dt-search slot that
    datatable-global-ui.js fills with DataTables' own filter. Pages that
    paginate in Laravel have no such filter, so they get this: a plain GET form
    in the same slot, styled to match by the .programme-dt-search-form rules in
    custom.css.

    Other grid state (per_page, sort, dir) must ride along as hidden inputs or
    submitting a search silently resets it — pass them through the default slot.

    Usage:
        <x-programme-dt-search :action="route('master.country.index')"
                               placeholder="Search country"
                               label="Search by country name">
            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
        </x-programme-dt-search>
--}}
@props([
    'action',
    'name' => 'search',
    'placeholder' => 'Search',
    'label' => null,
    'value' => null,
])

@php
    $term = $value ?? (string) request($name, '');
@endphp

<div class="programme-dt-search">
    <form method="GET" action="{{ $action }}" role="search" class="programme-dt-search-form">
        {{-- Hidden grid state supplied by the page (per_page, sort, dir, …). --}}
        {{ $slot }}

        <input type="search" name="{{ $name }}" value="{{ $term }}"
               class="programme-dt-search-input"
               placeholder="{{ $placeholder }}"
               aria-label="{{ $label ?? $placeholder }}"
               autocomplete="off">

        @if ($term !== '')
            {{-- A link, not a JS reset: it must clear the term on the SERVER,
                 and it drops ?page= at the same time so the user lands on page 1
                 of the full list instead of page 7 of a list that no longer has one. --}}
            <a href="{{ $action }}" class="programme-dt-search-clear" title="Clear search"
               aria-label="Clear search">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </a>
        @endif
    </form>
</div>
