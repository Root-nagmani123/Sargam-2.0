@php $isActive = (int) $subCategory->status === 1; @endphp
{{-- Rendered per row by IssueSubCategoryController::data(); every handler behind
     it is delegated, so an ajax redraw needs no re-binding. --}}
<div class="ic-act-group" role="group" aria-label="Row actions">
    <button type="button" class="ic-act ic-act--edit isc-edit-btn" aria-label="Edit sub-category"
            data-id="{{ $subCategory->pk }}"
            data-category="{{ $subCategory->issue_category_master_pk }}"
            data-name="{{ $subCategory->issue_sub_category }}"
            data-status="{{ (int) $subCategory->status }}">
        <span class="ic-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
        <span class="ic-act__label">Edit</span>
    </button>

    {{-- No .form-check/.form-switch wrapper — see the shared stylesheet. --}}
    <label class="ic-act ic-act--toggle">
        <span class="ic-act__icon">
            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                   data-table="issue_sub_category_master" data-column="status"
                   data-id="{{ $subCategory->pk }}" {{ $isActive ? 'checked' : '' }}>
        </span>
        <span class="ic-act__label">{{ $isActive ? 'Activate' : 'Deactivate' }}</span>
    </label>

    @if($isActive)
        {{-- destroy() refuses to delete an active sub-category — mirror that guard. --}}
        <span class="ic-act ic-act--del is-disabled" aria-disabled="true"
              title="Deactivate this sub-category before deleting it">
            <span class="ic-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
            <span class="ic-act__label">Delete</span>
        </span>
    @else
        <form action="{{ route('admin.issue-sub-categories.destroy', $subCategory->pk) }}"
              method="POST" class="ic-delete-form">
            @csrf
            @method('DELETE')
            <button type="submit" class="ic-act ic-act--del" aria-label="Delete sub-category"
                    data-name="{{ $subCategory->issue_sub_category }}">
                <span class="ic-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                <span class="ic-act__label">Delete</span>
            </button>
        </form>
    @endif
</div>
