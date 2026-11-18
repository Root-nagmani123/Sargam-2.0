{{-- Right side of the toolbar: Columns + the DataTables search slot (doc §2). $table = table id. --}}
<div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
    <button type="button" class="btn programme-dt-btn-columns" data-bs-toggle="modal" data-bs-target="#{{ $table }}Colvis"
            title="Show / hide columns">
        <span>Columns</span><span class="material-icons material-symbols-rounded" aria-hidden="true">view_column</span>
    </button>
    <div class="programme-dt-search" data-dt-search-for="{{ $table }}"></div>
</div>
