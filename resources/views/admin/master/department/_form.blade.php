<form action="{{ route('master.department.master.store') }}" method="POST" id="departmentForm">
    @csrf
    @if(!empty($departmentMaster)) 
        <input type="hidden" name="pk" value="{{ encrypt($departmentMaster->pk) }}">
    @endif
    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <x-input
                    name="department_name"
                    label="Name :" 
                    placeholder="Name" 
                    formLabelClass="form-label fw-semibold"
                    required="true"
                    value="{{ old('department_name', optional($departmentMaster)->department_name ?? '') }}"
                    />
            </div>
        </div>
    </div>
</form>
