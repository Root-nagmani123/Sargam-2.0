@php $isActive = (int) $category->status === 1; @endphp
{{-- Every action is the same stack: a fixed-height icon strip over a caption, in
     an equal-width column — so the icons keep an even rhythm no matter how wide
     the captions are. Rendered per row by IssueCategoryController::data(), and
     driven entirely by delegated handlers so an ajax redraw needs no re-binding. --}}
<div class="ic-act-group" role="group" aria-label="Row actions">
    <button type="button" class="ic-act ic-act--edit ic-edit-btn" aria-label="Edit category"
            data-id="{{ $category->pk }}"
            data-name="{{ $category->issue_category }}"
            data-description="{{ $category->description }}"
            data-status="{{ (int) $category->status }}">
        <span class="ic-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
        <span class="ic-act__label">Edit</span>
    </button>

    {{-- NB: no .form-check/.form-switch wrapper. Those pull the input left by
         -2.375rem (custom.css:106) for the switch-beside-label layout, which
         would knock it off-centre here. The .status-toggle skin is keyed on
         the input itself, so it still applies. --}}
    <label class="ic-act ic-act--toggle">
        <span class="ic-act__icon">
            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                   data-table="issue_category_master" data-column="status"
                   data-id="{{ $category->pk }}" {{ $isActive ? 'checked' : '' }}>
        </span>
        <span class="ic-act__label">{{ $isActive ? 'Activate' : 'Deactivate' }}</span>
    </label>

    @if($isActive)
        {{-- destroy() refuses to delete an active category — mirror that guard here. --}}
        <span class="ic-act ic-act--del is-disabled" aria-disabled="true"
              title="Deactivate this category before deleting it">
            <span class="ic-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
            <span class="ic-act__label">Delete</span>
        </span>
    @else
        <form action="{{ route('admin.issue-categories.destroy', $category->pk) }}"
              method="POST" class="ic-delete-form">
            @csrf
            @method('DELETE')
            <button type="submit" class="ic-act ic-act--del" aria-label="Delete category"
                    data-name="{{ $category->issue_category }}">
                <span class="ic-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                <span class="ic-act__label">Delete</span>
            </button>
        </form>
    @endif
</div>
