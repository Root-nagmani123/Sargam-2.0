{{-- Status switch for the Manage Sub-Categories grid.

     Rendered per row by IssueSubCategoryController::data() and returned as a raw
     column. Its change handler is delegated from document (see the page's scripts),
     so ajax-injected rows keep working. --}}
<div class="d-flex align-items-center justify-content-center gap-2">
    <div class="form-check form-switch mb-0">
        <input class="form-check-input status-toggle-subcategory"
               type="checkbox"
               role="switch"
               data-id="{{ $subCategory->pk }}"
               data-url="{{ route('admin.issue-sub-categories.update', $subCategory->pk) }}"
               {{ (int) $subCategory->status === 1 ? 'checked' : '' }}>
    </div>
</div>
