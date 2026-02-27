<div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-1 gap-md-2 flex-wrap"
     role="group"
     aria-label="MDO Duty Type actions">

    <!-- Edit -->
    <a href="{{ route('master.mdo_duty_type.edit', ['id' => encrypt($row->pk)]) }}"
       class="d-inline-flex align-items-center gap-1 text-primary"
       aria-label="Edit MDO Duty Type">
        <span class="material-icons material-symbols-rounded small" aria-hidden="true">edit</span>
    </a>

    <!-- Delete -->
    @if($row->active_inactive == 1)
        <a href="javascript:void(0);"
                class="d-inline-flex align-items-center gap-1 text-primary"
                disabled
                aria-disabled="true"
                title="Cannot delete active MDO Duty Type">
            <span class="material-icons material-symbols-rounded small" aria-hidden="true">delete</span>
        </a>
    @else
        <form id="delete-form-{{ $row->pk }}"
              action="{{ route('master.mdo_duty_type.delete', ['id' => encrypt($row->pk)]) }}"
              method="POST"
              class="d-inline">
            @csrf
            @method('DELETE')

            <a href="javascript:void(0);"
                        class="d-inline-flex align-items-center gap-1 text-primary"
                    aria-label="Delete MDO Duty Type"
                    onclick="return confirm('Are you sure you want to delete this MDO Duty Type?');">
                <span class="material-icons material-symbols-rounded small" aria-hidden="true">delete</span>
            </a>
        </form>
    @endif

</div>
