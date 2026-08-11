@php
    $isActive = (int) $priority->status === 1;
    // destroy() refuses a priority that any issue log references.
    $inUse = (int) ($priority->issue_logs_count ?? 0) > 0;
@endphp
{{-- Edit · status switch · Delete — the canonical stack (§3b). Status is still
     editable from the Edit modal too. Rendered per row by
     IssuePriorityController::data(); every handler behind it is delegated. --}}
<div class="ic-act-group" role="group" aria-label="Row actions">
    <button type="button" class="ic-act ic-act--edit ip-edit-btn" aria-label="Edit priority"
            data-id="{{ $priority->pk }}"
            data-name="{{ $priority->priority }}"
            data-description="{{ $priority->description }}"
            data-status="{{ (int) $priority->status }}">
        <span class="ic-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
        <span class="ic-act__label">Edit</span>
    </button>

    {{-- No .form-check/.form-switch wrapper: custom.css:107 pulls the input
         -2.375rem left inside one, which breaks the switch-above-caption layout.
         custom.js binds .status-toggle globally, so there is no toggle JS to
         write here. The caption names the ACTION, not the state (§3b). --}}
    <label class="ic-act ic-act--toggle">
        <span class="ic-act__icon">
            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                   data-table="issue_priority_master" data-column="status"
                   data-id="{{ $priority->pk }}" {{ $isActive ? 'checked' : '' }}>
        </span>
        <span class="ic-act__label">{{ $isActive ? 'Activate' : 'Deactivate' }}</span>
    </label>

    @if($inUse)
        <span class="ic-act ic-act--del is-disabled" aria-disabled="true"
              title="In use by {{ $priority->issue_logs_count }} issue(s) — cannot be deleted">
            <span class="ic-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
            <span class="ic-act__label">Delete</span>
        </span>
    @else
        <form action="{{ route('admin.issue-priorities.destroy', $priority->pk) }}"
              method="POST" class="ic-delete-form">
            @csrf
            @method('DELETE')
            <button type="submit" class="ic-act ic-act--del" aria-label="Delete priority"
                    data-name="{{ $priority->priority }}">
                <span class="ic-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                <span class="ic-act__label">Delete</span>
            </button>
        </form>
    @endif
</div>
