<a href="javascript:void(0)"
   class="me-2"
   onclick="openEditModal('{{ encrypt($row->pk) }}', '{{ $row->mdo_duty_type_name }}')"
   title="Edit">
    <iconify-icon icon="solar:pen-bold" class="fs-5 text-primary"></iconify-icon>
</a>

<form title="{{ $row->active_inactive == 1 ? 'Cannot delete active MDO Duty Type' : 'Delete' }}" action="{{ route(
    'master.mdo_duty_type.delete',
    ['id' => encrypt($row->pk)]
) }}" method="POST"
    class="d-inline">
    @csrf
    @method('DELETE')
    <a onclick="event.preventDefault(); 
                                                        if(confirm('Are you sure you want to delete this record?')) {
                                                            this.closest('form').submit();
                                                        }" {{ $row->active_inactive == 1 ? 'disabled' : '' }}>
        <iconify-icon icon="solar:trash-bin-2-bold" class="fs-5 text-dark"></iconify-icon>
    </a>
</form>