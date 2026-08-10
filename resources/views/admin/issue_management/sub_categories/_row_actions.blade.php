{{-- Row actions for the Manage Sub-Categories grid.

     Rendered per row by IssueSubCategoryController::data() and returned as a raw
     column. Faithful copy of the previous inline markup.

     NOTE (pre-existing): the Delete control is an <a> inside the form with no submit
     handler, so it never actually posts. Preserved as-is — fixing it is a behaviour
     change and belongs in its own commit. --}}
<div class="d-flex justify-content-center gap-2">
    <a href="javascript:void(0)" class="text-primary"
       onclick="editSubCategory({{ $subCategory->pk }}, {{ $subCategory->issue_category_master_pk ?? 'null' }}, {{ Illuminate\Support\Js::from($subCategory->issue_sub_category) }}, {{ (int) $subCategory->status }})"
       title="Edit Sub-Category">
        <i class="material-icons material-symbols-rounded" style="font-size: 18px;">edit</i>
    </a>
    <form action="{{ route('admin.issue-sub-categories.destroy', $subCategory->pk) }}"
          method="POST"
          class="d-inline"
          onsubmit="return confirm('Are you sure you want to delete this sub-category?');">
        @csrf
        @method('DELETE')
        <a href="javascript:void(0)" class="text-primary" title="Delete Sub-Category">
            <i class="material-icons material-symbols-rounded" style="font-size: 18px;">delete</i>
        </a>
    </form>
</div>
