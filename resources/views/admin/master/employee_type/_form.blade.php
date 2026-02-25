<form action="{{ route('master.employee.type.store') }}" method="POST" id="employeeTypeForm">
    @csrf
    @if(!empty($employeeTypeMaster)) 
        <input type="hidden" name="pk" value="{{ encrypt($employeeTypeMaster->pk) }}">
    @endif
    <div class="row">
        <div class="col-12">
            <div class="mb-3">
                <x-input
                    name="employee_type_name"
                    label="Name :" 
                    placeholder="Enter employee type name" 
                    formLabelClass="form-label fw-semibold"
                    required="true"
                    value="{{ old('employee_type_name', $employeeTypeMaster->category_type_name ?? '') }}"
                    />
            </div>
        </div>
    </div>
</form>
