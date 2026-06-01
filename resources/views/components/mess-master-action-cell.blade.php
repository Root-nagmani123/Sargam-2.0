@props([
    'entityLabel' => 'record',
    'recordId',
    'isActive' => true,
    'canDelete' => false,
    'destroyUrl',
    'toggleTable',
    'editClass' => '',
    'editAttributes' => [],
    'showView' => false,
    'viewClass' => '',
    'viewAttributes' => [],
])
<div class="mess-row-actions d-inline-flex align-items-center gap-2 programme-action-group" role="group"
    aria-label="{{ ucfirst($entityLabel) }} actions">
    @if($showView && $viewClass)
        <button type="button"
            class="{{ $viewClass }} programme-action-btn"
            @foreach($viewAttributes as $attr => $val)
                {{ $attr }}="{{ $val }}"
            @endforeach
            aria-label="View {{ $entityLabel }}"
            title="View {{ $entityLabel }}">
            <i class="bi bi-eye" aria-hidden="true"></i>
        </button>
    @endif
    @if($editClass)
        <button type="button"
            class="{{ $editClass }} programme-action-btn"
            @foreach($editAttributes as $attr => $val)
                {{ $attr }}="{{ $val }}"
            @endforeach
            aria-label="Edit {{ $entityLabel }}"
            title="Edit {{ $entityLabel }}">
            <i class="bi bi-pencil" aria-hidden="true"></i>
        </button>
    @endif
    <div class="form-check form-switch mess-action-switch-wrap mb-0">
        <input class="form-check-input status-toggle" type="checkbox" role="switch"
            data-table="{{ $toggleTable }}"
            data-column="status"
            data-id="{{ $recordId }}"
            data-id_column="id"
            aria-label="Toggle {{ $entityLabel }} status"
            {{ $isActive ? 'checked' : '' }}>
    </div>
    @if($isActive)
        <button type="button"
            class="mess-delete-btn programme-action-btn programme-action-btn--danger"
            disabled
            aria-disabled="true"
            title="Cannot delete active {{ $entityLabel }}"
            aria-label="Delete {{ $entityLabel }}">
            <i class="bi bi-trash" aria-hidden="true"></i>
        </button>
    @elseif($canDelete)
        <form method="POST" action="{{ $destroyUrl }}" class="d-inline mess-delete-form m-0"
            onsubmit="return confirm('Are you sure you want to delete this {{ $entityLabel }}?');">
            @csrf
            @method('DELETE')
            <button type="submit"
                class="mess-delete-btn programme-action-btn programme-action-btn--danger"
                aria-label="Delete {{ $entityLabel }}"
                title="Delete {{ $entityLabel }}">
                <i class="bi bi-trash" aria-hidden="true"></i>
            </button>
        </form>
    @else
        <button type="button"
            class="mess-delete-btn programme-action-btn programme-action-btn--danger"
            disabled
            aria-disabled="true"
            title="You do not have permission to delete"
            aria-label="Delete {{ $entityLabel }}">
            <i class="bi bi-trash" aria-hidden="true"></i>
        </button>
    @endif
</div>
