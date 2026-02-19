<form action="{{ route('master.employee.group.store') }}" method="POST" id="employeeGroupForm">
    @csrf
    @if(!empty($employeeGroupMaster)) 
        <input type="hidden" name="pk" value="{{ encrypt($employeeGroupMaster->pk) }}">
    @endif
    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <x-input
                    name="group_name"
                    label="Group Name :" 
                    placeholder="Group Name" 
                    formLabelClass="form-label fw-semibold"
                    required="true"
                    value="{{ old('group_name', optional($employeeGroupMaster)->group_name ?? '') }}"
                    />
            </div>
        </div>
    </div>
</form>
