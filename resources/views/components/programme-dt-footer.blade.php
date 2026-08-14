{{--
    Footer variant B — Laravel paginator (new-design-index-page.md §4B).

    The hand-written twin of the footer datatable-global-ui.js builds for
    DataTables pages. It reuses the .dataTables_length / .dataTables_info class
    names on purpose: custom.css styles the two variants through the same
    selectors, which is what keeps them visually identical.

    Renders: pagination left · "Showing [N] of M items" right.

    Usage:
        <x-programme-dt-footer :paginator="$countries" per-page-id="countryPerPage" />

    The page MUST also carry data-sargam-dt-ui="false" on its table if any
    DataTable exists on the same screen, or the enhancer will claim and empty
    this footer (see §5 "Opting out").
--}}
@props([
    'paginator',
    'perPageId' => 'programmeDtPerPage',
    'options' => ['10', '25', '50', '100', '200', 'all'],
    // MUST match the controller's own default, or the dropdown shows a page size
    // the server never used.
    'default' => '10',
])

@php
    // Mirror of the controller-side whitelist. Kept here too so a tampered
    // ?per_page= renders a valid dropdown rather than an empty selection.
    $currentPerPage = (string) request('per_page', $default);
    if (!in_array($currentPerPage, $options, true)) {
        $currentPerPage = $default;
    }
@endphp

<div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
    <div class="programme-dt-pagination">
        {{ $paginator->links('vendor.pagination.custom') }}
    </div>

    <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
        <div class="dataTables_length">
            <label class="mb-0" for="{{ $perPageId }}">Showing
                <select id="{{ $perPageId }}"
                        class="form-select form-select-sm js-programme-dt-per-page"
                        aria-label="Rows per page">
                    @foreach ($options as $option)
                        <option value="{{ $option }}" @selected($currentPerPage === $option)>
                            {{ $option === 'all' ? 'All' : $option }}
                        </option>
                    @endforeach
                </select>
            </label>
        </div>
        <div class="dataTables_info">of {{ number_format($paginator->total()) }} items</div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            // One delegated handler for every server-paginated grid on the page.
            // Resets to page 1: keeping the old page number would land the user
            // past the end of the list whenever they enlarge the page size.
            document.addEventListener('change', function (event) {
                var select = event.target.closest('.js-programme-dt-per-page');
                if (!select) {
                    return;
                }
                var url = new URL(window.location.href);
                url.searchParams.set('per_page', select.value);
                url.searchParams.set('page', '1');
                window.location.href = url.toString();
            });
        </script>
    @endpush
@endonce
