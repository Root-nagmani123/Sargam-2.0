<div class="row">
    <div class="col-md-6">
        <div class="mb-3">

            @php
                $employeeTypeOptions = App\Models\EmployeeTypeMaster::getEmployeeTypeList();
                $employeeTypeOptions = array_column($employeeTypeOptions, 'category_type_name', 'pk');
                
            @endphp

            <x-select name="type" label="Employee Type :" :options="$employeeTypeOptions" :value="old('type')" formLabelClass="form-label" formSelectClass="form-select" />

            
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="id" label="Employee ID :" type="text" value="{{ old('id') }}" formLabelClass="form-label" formInputClass="form-control" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            @php
                $employeeGroupOptions = App\Models\EmployeeGroupMaster::getEmployeeGroupList();
                $employeeGroupOptions = array_column($employeeGroupOptions, 'emp_group_name', 'pk');
            @endphp 

            <x-select name="group" label="Employee Group :" :options="$employeeGroupOptions" :value="old('group')" formLabelClass="form-label" formSelectClass="form-select" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            @php
                $designationOptions = App\Models\DesignationMaster::getDesignationList();
                $designationOptions = array_column($designationOptions, 'designation_name', 'pk');
            @endphp

            <x-select name="designation" label="Designation :" :options="$designationOptions" :value="old('designation')" formLabelClass="form-label" formSelectClass="form-select" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="userid" label="User ID :" type="text" value="{{ old('userid') }}" formLabelClass="form-label" formInputClass="form-control" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            @php
                $sectionOptions = App\Models\DepartmentMaster::getDepartmentList();
                $sectionOptions = array_column($sectionOptions, 'department_name', 'pk');
            @endphp

            <x-select name="section" label="Department Name :" :options="$sectionOptions" :value="old('section')" formLabelClass="form-label" formSelectClass="form-select" />

        </div>
    </div>
</div>