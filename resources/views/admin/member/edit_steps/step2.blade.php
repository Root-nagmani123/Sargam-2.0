<div class="row">
    <input type="hidden" name="employeePK" id="employeePK" >
    <div class="col-md-6">
        <div class="mb-3">
            
            @php
                $employeeTypeOptions = App\Models\EmployeeTypeMaster::getEmployeeTypeList();
                $employeeTypeOptions = array_column($employeeTypeOptions, 'category_type_name', 'pk');
            @endphp

            <x-select name="type" label="Employee Type :" :options="$employeeTypeOptions" :value="$member->emp_type ?? old('type')" formLabelClass="form-label" formSelectClass="form-select" labelRequired="true" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="id" label="Employee ID :" type="text" value="{{ $member->emp_id ?? old('id') }}" formLabelClass="form-label" formInputClass="form-control" labelRequired="true" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            @php
                $employeeGroupOptions = App\Models\EmployeeGroupMaster::getEmployeeGroupList();
                $employeeGroupOptions = array_column($employeeGroupOptions, 'emp_group_name', 'pk');
            @endphp 

            <x-select name="group" label="Employee Group :" :options="$employeeGroupOptions" :value="$member->emp_group_pk ?? old('group')" formLabelClass="form-label" formSelectClass="form-select" labelRequired="true" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            @php
                $designationOptions = App\Models\DesignationMaster::getDesignationList();
                $designationOptions = array_column($designationOptions, 'designation_name', 'pk');
            @endphp

            <x-select name="designation" label="Designation :" :options="$designationOptions" :value=" $member->designation_master_pk ?? old('designation')" formLabelClass="form-label" formSelectClass="form-select" labelRequired="true" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            {{-- @dd($member) --}}
            <x-input name="userid" label="User ID :" type="text" value="{{ $member->userCredential->user_name ?? old('userid') }}" formLabelClass="form-label" formInputClass="form-control" labelRequired="true" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            @php
                $sectionOptions = App\Models\DepartmentMaster::getDepartmentList();
                $sectionOptions = array_column($sectionOptions, 'department_name', 'pk');
            @endphp

            <x-select name="section" label="Department Name :" :options="$sectionOptions" :value="$member->department_master_pk ?? old('section')" formLabelClass="form-label" formSelectClass="form-select" labelRequired="true" />

        </div>
    </div>
</div>