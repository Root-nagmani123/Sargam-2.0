<div class="d-flex gap-2 align-items-center">

    {{-- EDIT BUTTON --}}
    <a href="{{ route('master.mdo_duty_type.edit', ['id' => encrypt($row->pk)]) }}"
       class="action-btn edit-btn"
       aria-label="Edit MDO Duty Type"
       title="Edit">
        <span class="material-icons">edit</span>
    </a>

    {{-- DELETE BUTTON --}}
    <form action="{{ route('master.mdo_duty_type.delete', ['id' => encrypt($row->pk)]) }}"
          method="POST"
          onsubmit="return confirm('Are you sure you want to delete this record?')"
          class="d-inline">

        @csrf
        @method('DELETE')

        <button type="submit"
                class="action-btn delete-btn {{ $row->active_inactive == 1 ? 'disabled-btn' : '' }}"
                aria-label="{{ $row->active_inactive == 1 ? 'Delete disabled for active duty type' : 'Delete MDO Duty Type' }}"
                title="{{ $row->active_inactive == 1 ? 'Cannot delete active MDO Duty Type' : 'Delete' }}"
                {{ $row->active_inactive == 1 ? 'disabled' : '' }}>
            <span class="material-icons">delete</span>
        </button>

    </form>

</div>
