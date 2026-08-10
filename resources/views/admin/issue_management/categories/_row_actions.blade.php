{{-- Row actions for the Manage Categories grid.

     Rendered per row by IssueCategoryController::data() and returned as a raw column.
     Markup is a faithful copy of what the page rendered inline before the grid moved
     server-side, so behaviour is unchanged.

     NOTE (pre-existing, not introduced by the server-side move): the Delete control is
     an <a> with no href/handler inside the form, so clicking it never submits — the
     delete has never actually fired from this screen. Left as-is deliberately; fixing
     it is a behaviour change that belongs in its own commit. --}}
<div class="btn-action-group justify-content-center">
    <a href="javascript:void(0)" class="text-primary"
       onclick="editCategory({{ $category->pk }}, {{ Illuminate\Support\Js::from($category->issue_category) }}, {{ Illuminate\Support\Js::from($category->description) }}, {{ (int) $category->status }})"
       title="Edit Category">
        <i class="material-icons material-symbols-rounded">edit</i>
    </a>
    <form action="{{ route('admin.issue-categories.destroy', $category->pk) }}"
          method="POST" class="d-inline"
          onsubmit="return confirm('Are you sure you want to delete this category?');">
        @csrf
        @method('DELETE')
        <a class="text-primary" title="Delete Category">
            <i class="material-icons material-symbols-rounded">delete</i>
        </a>
    </form>
</div>
