<div class="d-flex gap-2 align-items-center">

    {{-- EDIT BUTTON --}}
    <a href="{{ route('master.mdo_duty_type.edit', ['id' => encrypt($row->pk)]) }}"
       class="action-btn edit-btn"
       aria-label="Edit MDO Duty Type"
       title="Edit">
        <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">edit</i>
    </a>

    {{-- DELETE BUTTON --}}
    <div class="delete-icon-container" 
         data-item-id="{{ $row->pk }}" 
         data-delete-url="{{ route('master.mdo_duty_type.delete', ['id' => encrypt($row->pk)]) }}">
        @if($row->active_inactive == 1)
            <span class="delete-icon-disabled" title="Cannot delete active MDO Duty Type">
                <i class="material-icons menu-icon material-symbols-rounded"
                    style="font-size: 24px; color: #ccc; cursor: not-allowed;">delete</i>
            </span>
        @else
            <form action="{{ route('master.mdo_duty_type.delete', ['id' => encrypt($row->pk)]) }}"
                  method="POST"
                  onsubmit="return confirm('Are you sure you want to delete this record?')"
                  class="d-inline m-0 delete-form" data-status="0">
                @csrf
                @method('DELETE')
                <a href="javascript:void(0)" onclick="event.preventDefault();
                    if(confirm('Are you sure you want to delete this MDO Duty Type?')) {
                        this.closest('form').submit();
                    }" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete MDO Duty Type">
                    <i class="material-icons menu-icon material-symbols-rounded"
                        style="font-size: 24px;">delete</i>
                </a>
            </form>
        @endif
    </div>

</div>
