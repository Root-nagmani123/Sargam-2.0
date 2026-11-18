{{-- Edit · switch · Delete for the three masters (doc §3b). Status is set from the switch;
     Delete is greyed when the server would refuse it.
     $modal, $updateUrl, $values, $table, $id, $active, $deleteUrl, $name, $deleteBlocked (reason|null) --}}
<div class="am-act-group" role="group" aria-label="Row actions">
    <button type="button" class="am-act" data-am-edit data-am-modal="#{{ $modal }}"
            data-action="{{ $updateUrl }}" data-values="{{ json_encode($values) }}">
        <span class="am-act__icon"><span class="material-icons material-symbols-rounded" aria-hidden="true">edit</span></span>
        <span class="am-act__label">Edit</span>
    </button>

    <label class="am-act am-act--toggle">
        <span class="am-act__icon">
            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                   data-table="{{ $table }}" data-column="status" data-id="{{ $id }}" data-id_column="id"
                   aria-label="{{ $active ? 'Deactivate' : 'Activate' }} {{ $name }}" @checked($active)>
        </span>
        <span class="am-act__label">{{ $active ? 'Deactivate' : 'Activate' }}</span>
    </label>

    @if ($deleteBlocked)
        <span class="am-act am-act--del is-disabled" title="{{ $deleteBlocked }}" aria-disabled="true">
            <span class="am-act__icon"><span class="material-icons material-symbols-rounded" aria-hidden="true">delete</span></span>
            <span class="am-act__label">Delete</span>
        </span>
    @else
        <form method="POST" action="{{ $deleteUrl }}" class="am-act-form am-delete-form" data-name="{{ $name }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="am-act am-act--del">
                <span class="am-act__icon"><span class="material-icons material-symbols-rounded" aria-hidden="true">delete</span></span>
                <span class="am-act__label">Delete</span>
            </button>
        </form>
    @endif
</div>
