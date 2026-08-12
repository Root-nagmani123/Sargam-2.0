@extends('admin.layouts.master')
@section('title', 'Category Item Master')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
    /* ── Category Item Master — new design chrome (built on --ds-* tokens + programme-dt system) ── */
    .itemcat-master-page .itemcat-master-card {
        background: var(--ds-surface, #fff);
        border-radius: var(--ds-radius-card, 8px);
        box-shadow: var(--ds-shadow, 0 1px 3px rgba(16, 24, 40, .1));
    }

    /* Download / Print bar */
    .itemcat-master-page .itemcat-master-export-btn {
        height: var(--ds-control-h, 40px);
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: 0 1.1rem;
        font-size: .9375rem;
        font-weight: 500;
        color: var(--ds-primary, #004a93);
        border: 1px solid var(--ds-line, #d0d5dd);
        border-radius: var(--ds-radius-2, 8px);
        background: var(--ds-surface, #fff);
    }

    .itemcat-master-page .itemcat-master-export-btn:hover {
        background: #f2f7fc;
        border-color: var(--ds-primary, #004a93);
        color: var(--ds-primary, #004a93);
    }

    .itemcat-master-page .itemcat-master-export-btn i { font-size: 1.15rem; line-height: 1; }

    /* Native filter <select> pill (the design system styles only the Choices variant) */
    .itemcat-master-page .programme-dt-filter-select .form-select {
        min-height: 40px;
        border-radius: 8px;
        border: 1px solid #d0d5dd;
        font-size: .9375rem;
        color: #344054;
        box-shadow: none;
    }

    .itemcat-master-page .programme-dt-filter-select .form-select:focus {
        border-color: var(--ds-primary, #004a93);
        box-shadow: 0 0 0 3px rgba(0, 74, 147, .12);
    }

    /* Collapse the leftover (now-emptied) DataTables control wrappers once the
       global enhancer has relocated search / pagination into the slots below. */
    .itemcat-master-page .dt-top:empty,
    .itemcat-master-page .dt-foot:empty { display: none; margin: 0; }

    /* Column visibility is presented as a programme-style modal, so the mess
       Column-manager's own injected dropdown stays hidden — it remains the
       underlying state engine that keeps Download/Print column-sync correct. */
    .itemcat-master-page .mess-col-manager-dropdown { display: none !important; }

    #itemcatColumnToggleGrid .colvis-item {
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }

    #itemcatColumnToggleGrid .colvis-item:hover {
        border-color: var(--ds-primary, #004a93) !important;
        background-color: rgba(0, 74, 147, 0.04);
    }

    #itemcatColumnToggleGrid .colvis-item .form-check-input { cursor: pointer; flex-shrink: 0; }

    .itemcat-master-page .itemcat-name-primary { font-weight: 600; color: var(--ds-ink, #1f2937); }

    /* Row actions — icon over label (blue Edit, red Delete), matching the mock. */
    .itemcat-master-page .itemcat-actions { gap: 1.1rem; }
    .itemcat-master-page .itemcat-actions form { margin: 0; }

    .itemcat-master-page .itemcat-action-btn {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        gap: .1rem;
        padding: 0;
        border: 0;
        background: transparent;
        line-height: 1.1;
        font-size: .75rem;
        font-weight: 500;
    }

    .itemcat-master-page .itemcat-action-btn i { font-size: 1.2rem; line-height: 1; }
    .itemcat-master-page .itemcat-action-btn.text-primary { color: var(--ds-primary, #004a93) !important; }
    .itemcat-master-page .itemcat-action-btn.text-danger { color: var(--ds-secondary, #d92d20) !important; }
    .itemcat-master-page .itemcat-action-btn:hover { opacity: .78; }

    /* Pagination → arrows + numbers only (drop First/Last, swap word labels). */
    .itemcat-master-page .programme-dt-footer .paginate_button.first,
    .itemcat-master-page .programme-dt-footer .paginate_button.last { display: none; }

    .itemcat-master-page .programme-dt-footer .paginate_button.previous .page-link,
    .itemcat-master-page .programme-dt-footer .paginate_button.next .page-link { font-size: 0; }

    .itemcat-master-page .programme-dt-footer .paginate_button.previous .page-link::before { content: "\2039"; font-size: 1.1rem; }
    .itemcat-master-page .programme-dt-footer .paginate_button.next .page-link::before { content: "\203A"; font-size: 1.1rem; }

    /* ── Add / Edit Category Item modals (clean rounded card, red Cancel + blue submit) ── */
    .itemcat-modal .modal-content {
        border-radius: 16px;
        box-shadow: 0 24px 48px rgba(16, 24, 40, .18);
        overflow: hidden;
    }

    .itemcat-modal .modal-header {
        padding: 1.25rem 1.5rem 1rem;
        border-bottom: 1px solid var(--ds-line, #eef2f6);
    }

    .itemcat-modal .modal-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--ds-ink, #1f2937);
    }

    .itemcat-modal .modal-body { padding: 1.25rem 1.5rem; }

    .itemcat-modal .itemcat-modal-label {
        font-weight: 600;
        font-size: .875rem;
        color: var(--ds-ink, #1f2937);
        margin-bottom: .375rem;
    }

    .itemcat-modal .itemcat-modal-control {
        min-height: 44px;
        border-radius: 8px;
        border: 1px solid var(--ds-line, #d0d5dd);
        font-size: .9375rem;
        color: var(--ds-ink, #1f2937);
        padding: .5rem .875rem;
    }

    .itemcat-modal textarea.itemcat-modal-control { min-height: 92px; }
    .itemcat-modal .itemcat-modal-control::placeholder { color: #98a2b3; }

    .itemcat-modal .itemcat-modal-control:focus {
        border-color: var(--ds-primary, #004a93);
        box-shadow: 0 0 0 3px rgba(0, 74, 147, .12);
    }

    .itemcat-modal .modal-footer {
        padding: 1rem 1.5rem 1.5rem;
        border-top: 1px solid var(--ds-line, #eef2f6);
    }

    .itemcat-modal .itemcat-modal-cancel,
    .itemcat-modal .itemcat-modal-submit {
        min-height: 44px;
        border-radius: 8px;
        padding: .5rem 1.5rem;
        font-weight: 600;
        font-size: .9375rem;
    }

    .itemcat-modal .itemcat-modal-cancel {
        color: var(--ds-secondary, #d92d20);
        background: #fff;
        border: 1px solid var(--ds-secondary, #d92d20);
    }

    .itemcat-modal .itemcat-modal-cancel:hover {
        background: #fff5f5;
        color: var(--ds-secondary, #d92d20);
    }
</style>
@endpush

@section('content')
@php
    $categoryTypes = \App\Models\Mess\ItemCategory::categoryTypes();
    $selectedCategoryType = $categoryTypeFilter ?? request('category_type', '');
@endphp
<div class="container-fluid itemcat-master-page">
    <x-breadcrum title="Category Item Master" :showBack="false">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createItemCategoryModal">
            <i class="material-symbols-rounded" style="font-size: 1.1rem;">add</i>
            <span>Add Category Item</span>
        </button>
    </x-breadcrum>

    {{-- Success feedback is rendered as the global green toast — see mess.partials.delete-confirm --}}

    {{-- Download / Print, right-aligned above the card. The design shows no status
         pills here (Sargam 2.0.pdf p11) — the ?status= filter is still served, it
         simply has no on-screen control. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-end gap-3 mb-3">
        <div class="d-flex flex-wrap gap-2 ms-auto">
            <button type="button" class="btn itemcat-master-export-btn" id="itemcatDownloadBtn">
                <i class="material-symbols-rounded">download</i>
                <span>Download</span>
            </button>
            <button type="button" class="btn itemcat-master-export-btn" id="itemcatPrintBtn">
                <i class="material-symbols-rounded">print</i>
                <span>Print</span>
            </button>
        </div>
    </div>

    <div class="card itemcat-master-card border-0">
        <div class="card-body">
            {{-- Toolbar: Category-type filter (left) + Columns modal trigger & search (right) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3 programme-dt-toolbar">
                <form method="GET" action="{{ route('admin.mess.itemcategories.index') }}" id="itemCatFilterForm"
                      class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>
                    <div class="programme-dt-filter-select">
                        <select name="category_type" id="filter_category_type" class="form-select" aria-label="Filter by category type"
                                onchange="document.getElementById('itemCatFilterForm').submit()">
                            <option value="">Category type</option>
                            @foreach($categoryTypes as $value => $label)
                                <option value="{{ $value }}" {{ (string) $selectedCategoryType === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- An <a>, not a button: it also has to clear the server-side
                         category_type filter, which only a fresh GET can do. --}}
                    <a href="{{ route('admin.mess.itemcategories.index') }}"
                       class="btn programme-dt-btn-reset d-inline-flex align-items-center justify-content-center {{ request()->hasAny(['category_type', 'status']) ? '' : 'd-none' }}"
                       data-mess-remove-filter="itemCategoriesTable"
                       data-mess-filter-server="{{ filled($selectedCategoryType) ? '1' : '0' }}">Remove Filter</a>
                </form>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnItemcatColumns"
                            data-bs-toggle="modal" data-bs-target="#itemcatColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    @include('mess.partials.search-toggle', ['tableId' => 'itemCategoriesTable'])
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="itemCategoriesTable" class="table table-hover programme-dt-table align-middle mb-0 w-100">
                        <thead>
                            <tr>
                                <th scope="col">Category Name</th>
                                <th scope="col">Category Type</th>
                                <th scope="col">Item Category Description</th>
                                <th scope="col" class="no-sort">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            {{-- Footer: pagination (left) + "Showing [N] of M items" (right), populated by the global enhancer --}}
            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3" data-dt-footer-for="itemCategoriesTable"></div>
        </div>
    </div>
</div>

{{-- Column Visibility Modal (programme/attendance style) --}}
@include('mess.partials.column-visibility', ['tableId' => 'itemCategoriesTable', 'key' => 'itemcat'])

{{-- Create Category Item Modal --}}
<div class="modal fade itemcat-modal" id="createItemCategoryModal" tabindex="-1" aria-labelledby="createItemCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <form method="POST" action="{{ route('admin.mess.itemcategories.store') }}">
                @csrf
                <div class="modal-header">
                    <h4 class="modal-title" id="createItemCategoryModalLabel">Add Category Item</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label itemcat-modal-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" name="category_name" class="form-control itemcat-modal-control" required
                                   value="{{ old('category_name') }}" placeholder="e.g. Egg Bhurji">
                            @error('category_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label itemcat-modal-label">Category Type <span class="text-danger">*</span></label>
                            <select name="category_type" class="form-select itemcat-modal-control" required>
                                <option value="">Select Type</option>
                                @foreach($categoryTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('category_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label itemcat-modal-label">Item Category Description</label>
                            <textarea name="description" class="form-control itemcat-modal-control" rows="3" placeholder="e.g. Enter Item Category Description....">{{ old('description') }}</textarea>
                            @error('description')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label itemcat-modal-label">Status</label>
                            <select name="status" class="form-select itemcat-modal-control">
                                <option value="" {{ old('status') ? '' : 'selected' }}>Select Status</option>
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn itemcat-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary itemcat-modal-submit">Add Category Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Category Item Modal --}}
<div class="modal fade itemcat-modal" id="editItemCategoryModal" tabindex="-1" aria-labelledby="editItemCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <form id="editItemCategoryForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h4 class="modal-title" id="editItemCategoryModalLabel">Edit Category Item</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label itemcat-modal-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" name="category_name" id="edit_category_name" class="form-control itemcat-modal-control" required
                                   placeholder="e.g. Egg Bhurji">
                        </div>
                        <div class="col-12">
                            <label class="form-label itemcat-modal-label">Category Type <span class="text-danger">*</span></label>
                            <select name="category_type" id="edit_category_type" class="form-select itemcat-modal-control" required>
                                <option value="">Select Type</option>
                                @foreach($categoryTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label itemcat-modal-label">Item Category Description</label>
                            <textarea name="description" id="edit_description" class="form-control itemcat-modal-control" rows="3" placeholder="e.g. Enter Item Category Description...."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label itemcat-modal-label">Status</label>
                            <select name="status" id="edit_status" class="form-select itemcat-modal-control">
                                <option value="">Select Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn itemcat-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary itemcat-modal-submit">Update Category Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Branded delete-confirmation dialog + global success toast --}}
@include('mess.partials.delete-confirm')

@include('components.mess-master-datatables', [
    'tableId' => 'itemCategoriesTable',
    'searchPlaceholder' => 'Search category items...',
    'orderColumn' => 0,
    'actionColumnIndex' => 4,
    'infoLabel' => 'category items',
    'serverSide' => true,
    'ajaxUrlBase' => route('admin.mess.itemcategories.index'),
    'dom' => '<"dt-top"f>rt<"dt-foot"lip>',
    'lengthMenu' => [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
    'serverSideColumnDefs' => [
        ['className' => 'text-center', 'targets' => [3, 4]],
        // Status carries no sort caret in the design — the pills above the card
        // are how you slice by status here.
        ['orderable' => false, 'targets' => [3]],
    ],
])
@push('scripts')
@include('mess.partials.grid-filters-script')
{{-- Download / Print → branded server-side report (admin.mess.itemcategories.export).
     Passes the live search term + Category-type filter + chosen columns so the
     report matches what's on screen. Print opens the PDF inline for printing. --}}
<script>
(function () {
    var TABLE_ID = 'itemCategoriesTable';
    var BASE = @json(route('admin.mess.itemcategories.export'));
    var $ = window.jQuery;

    function buildUrl(format, inline) {
        var params = ['format=' + format];

        var dt = ($ && $.fn.DataTable && $.fn.DataTable.isDataTable('#' + TABLE_ID))
            ? $('#' + TABLE_ID).DataTable() : null;
        var search = dt ? dt.search() : '';
        if (search) params.push('search=' + encodeURIComponent(search));

        var typeSel = document.getElementById('filter_category_type');
        if (typeSel && typeSel.value) params.push('category_type=' + encodeURIComponent(typeSel.value));

        var cols = (window.MessColumnManager && typeof window.MessColumnManager.resolveExportIndexes === 'function')
            ? window.MessColumnManager.resolveExportIndexes(TABLE_ID) : null;
        if (cols && cols.length) params.push('columns=' + encodeURIComponent(cols.join(',')));

        if (inline) params.push('inline=1');
        return BASE + '?' + params.join('&');
    }

    var downloadBtn = document.getElementById('itemcatDownloadBtn');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function () {
            window.location.href = buildUrl('excel', false);
        });
    }

    var printBtn = document.getElementById('itemcatPrintBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.open(buildUrl('pdf', true), '_blank');
        });
    }
})();
</script>
{{-- Edit modal population --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('mousedown', function(e) {
        var btn = e.target.closest('.btn-edit-itemcategory');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('editItemCategoryForm').action = '{{ url("admin/mess/itemcategories") }}/' + btn.getAttribute('data-id');
        document.getElementById('edit_category_name').value = btn.getAttribute('data-category-name') || '';
        document.getElementById('edit_category_type').value = btn.getAttribute('data-category-type') || '';
        document.getElementById('edit_description').value = btn.getAttribute('data-description') || '';
        document.getElementById('edit_status').value = btn.getAttribute('data-status') || 'active';
        new bootstrap.Modal(document.getElementById('editItemCategoryModal')).show();
    }, true);
});
</script>
@endpush
@endsection
