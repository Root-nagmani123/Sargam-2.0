{{-- Row actions for the Manage Priorities grid.

     Rendered server-side per row by IssuePriorityController::data() and returned as
     a raw column, so the markup (and its CSRF token) lives in Blade rather than as a
     PHP string. Kept identical to what the page used to render inline. --}}
<button type="button" class="btn btn-sm btn-warning"
        onclick="editPriority({{ $priority->pk }}, {{ json_encode($priority->priority) }}, {{ json_encode($priority->description ?? '') }}, {{ (int) $priority->status }})">
    <iconify-icon icon="solar:pen-bold"></iconify-icon> Edit
</button>
<form action="{{ route('admin.issue-priorities.destroy', $priority->pk) }}"
      method="POST" class="d-inline"
      onsubmit="return confirm('Are you sure you want to delete this priority?');">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-danger">
        <iconify-icon icon="solar:trash-bin-trash-bold"></iconify-icon> Delete
    </button>
</form>
