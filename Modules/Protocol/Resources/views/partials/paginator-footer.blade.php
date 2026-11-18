{{-- Footer variant B (Laravel paginator) from docs/new-design-index-page.md §4 --}}
<div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
  <div class="programme-dt-pagination">
    {{ $paginator->links('vendor.pagination.custom') }}
  </div>
  <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
    <div class="dataTables_info">Showing {{ $paginator->count() }} of {{ number_format($paginator->total()) }} items</div>
  </div>
</div>
