<div class="d-inline-flex align-items-center gap-2 mdo-duty-actions"
     role="group"
     aria-label="MDO Duty Type actions">

    <!-- Edit -->
    <a href="{{ route('master.mdo_duty_type.edit', ['id' => encrypt($row->pk)]) }}"
       class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
       aria-label="Edit MDO Duty Type">
        <span class="material-icons material-symbols-rounded"
              style="font-size:18px;"
              aria-hidden="true">edit</span>
        <span class="d-none d-md-inline">Edit</span>
    </a>

    <!-- Delete -->
    @if($row->active_inactive == 1)
        <button type="button"
                class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
                disabled
                aria-disabled="true"
                title="Cannot delete active MDO Duty Type">
            <span class="material-icons material-symbols-rounded"
                  style="font-size:18px;"
                  aria-hidden="true">delete</span>
            <span class="d-none d-md-inline">Delete</span>
        </button>
    @else
        <form id="delete-form-{{ $row->pk }}"
              action="{{ route('master.mdo_duty_type.delete', ['id' => encrypt($row->pk)]) }}"
              method="POST"
              class="d-inline">
            @csrf
            @method('DELETE')

            <button type="submit"
                    class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1"
                    aria-label="Delete MDO Duty Type"
                    onclick="return confirm('Are you sure you want to delete this MDO Duty Type?');">
                <span class="material-icons material-symbols-rounded"
                      style="font-size:18px;"
                      aria-hidden="true">delete</span>
                <span class="d-none d-md-inline">Delete</span>
            </button>
        </form>
    @endif

</div>
