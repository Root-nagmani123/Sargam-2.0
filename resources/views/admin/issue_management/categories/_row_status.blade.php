{{-- Status switch for the Manage Categories grid.

     Rendered per row by IssueCategoryController::data() and returned as a raw
     column. The change handler in custom.js / status-toggle-delete.js is bound with
     $(document).on(...), so ajax-injected rows keep working. --}}
<div class="form-check form-switch d-inline-flex justify-content-center">
    <input class="form-check-input status-toggle" type="checkbox" role="switch"
        data-table="issue_category_master" data-column="status" data-id="{{ $category->pk }}"
        {{ (int) $category->status === 1 ? 'checked' : '' }}>
</div>
