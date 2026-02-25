<div class="row g-3">
    <input type="hidden" name="employeePK" id="employeePK" >
    <div class="col-12">
        <p class="text-body-secondary small mb-0 fw-medium">Employment information</p>
        <hr class="my-2">
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            @php
                $employeeTypeOptions = App\Models\EmployeeTypeMaster::getEmployeeTypeList();
                $employeeTypeOptions = array_column($employeeTypeOptions, 'category_type_name', 'pk');
            @endphp
            <x-select name="type" label="Employee Type" :options="$employeeTypeOptions" :value="old('type')" formLabelClass="form-label fw-medium" formSelectClass="form-select" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <x-input name="id" label="Employee ID" type="text" value="{{ old('id') }}" formLabelClass="form-label fw-medium" formInputClass="form-control" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            @php
                $employeeGroupOptions = App\Models\EmployeeGroupMaster::getEmployeeGroupList();
                $employeeGroupOptions = array_column($employeeGroupOptions, 'emp_group_name', 'pk');
            @endphp
            <x-select name="group" label="Employee Group" :options="$employeeGroupOptions" :value="old('group')" formLabelClass="form-label fw-medium" formSelectClass="form-select" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            @php
                $designationOptions = App\Models\DesignationMaster::getDesignationList();
                $designationOptions = array_column($designationOptions, 'designation_name', 'pk');
            @endphp
            <x-select name="designation" label="Designation" :options="$designationOptions" :value="old('designation')" formLabelClass="form-label fw-medium" formSelectClass="form-select" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <x-input name="userid" label="User ID" type="text" value="{{ old('userid') }}" formLabelClass="form-label fw-medium" formInputClass="form-control" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            @php
                $sectionOptions = App\Models\DepartmentMaster::getDepartmentList();
                $sectionOptions = array_column($sectionOptions, 'department_name', 'pk');
            @endphp
            <x-select name="section" label="Department" :options="$sectionOptions" :value="old('section')" formLabelClass="form-label fw-medium" formSelectClass="form-select" labelRequired="true" />
        </div>
    </div>
</div>