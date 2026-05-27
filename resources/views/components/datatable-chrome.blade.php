{{--
  Optional slots for global DataTables UI (search / pagination / count).
  If omitted, public/js/datatable-global-ui.js auto-creates them.

  Usage:
    <x-datatable-chrome table-id="my-table" />
    ... table#my-table or Yajra table ...
--}}
@props([
    'tableId',
    'showSearch' => true,
    'toolbar' => null,
])

@if($showSearch || $toolbar)
<div {{ $attributes->merge(['class' => 'd-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3 mb-4 programme-dt-toolbar']) }}>
    @if($toolbar)
    <div class="d-flex flex-wrap align-items-center gap-3">
        {{ $toolbar }}
    </div>
    @endif
    @if($showSearch)
    <div class="programme-dt-search ms-xl-auto" data-dt-search-for="{{ $tableId }}"></div>
    @endif
</div>
@endif

<div class="programme-dt-panel">
    {{ $slot }}
    <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="{{ $tableId }}"></div>
</div>
