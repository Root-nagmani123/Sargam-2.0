<div class="dropdown text-center">
    <button class="btn btn-link p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions">
        <span class="material-icons menu-icon material-symbols-rounded" style="font-size: 24px;">more_horiz</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        <li>
            <a class="dropdown-item d-flex align-items-center" href="{{ route('master.mdo_duty_type.edit', ['id' => encrypt($row->pk)]) }}">
                <span class="material-icons menu-icon material-symbols-rounded me-2" style="font-size: 20px;">edit</span>
                Edit
            </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        @if($row->active_inactive == 1)
            <li>
                <span class="dropdown-item d-flex align-items-center disabled" title="Cannot delete active MDO Duty Type" aria-disabled="true">
                    <span class="material-icons menu-icon material-symbols-rounded me-2" style="font-size: 20px;">delete</span>
                    Delete
                </span>
            </li>
        @else
            <li>
                <form id="delete-form-{{ $row->pk }}" action="{{ route('master.mdo_duty_type.delete', ['id' => encrypt($row->pk)]) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <a href="#" class="dropdown-item d-flex align-items-center text-danger" onclick="event.preventDefault(); if(confirm('Are you sure you want to delete this MDO Duty Type?')) document.getElementById('delete-form-{{ $row->pk }}').submit();">
                        <span class="material-icons menu-icon material-symbols-rounded me-2" style="font-size: 20px;">delete</span>
                        Delete
                    </a>
                </form>
            </li>
        @endif
    </ul>
</div>
