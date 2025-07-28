<a href="{{ route(
    'master.mdo_duty_type.edit',
    ['id' => encrypt($row->pk)]
) }}"
    class="btn btn-primary btn-sm">Edit</a>
<form title="{{ $row->active_inactive == 1 ? 'Cannot delete active MDO Duty Type' : 'Delete' }}" action="{{ route(
    'master.mdo_duty_type.delete',
    ['id' => encrypt($row->pk)]
) }}" method="POST"
    class="d-inline">
    @csrf
    @method('DELETE')
    <button type="button" class="btn btn-danger btn-sm" onclick="event.preventDefault(); 
                                                        if(confirm('Are you sure you want to delete this record?')) {
                                                            this.closest('form').submit();
                                                        }" {{ $row->active_inactive == 1 ? 'disabled' : '' }}>
        Delete
    </button>
</form>