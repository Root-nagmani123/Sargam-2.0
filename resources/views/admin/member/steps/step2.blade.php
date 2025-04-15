<div class="row">
    <div class="col-md-6">
        <div class="mb-3">

            @php
                $employeeTypeOptions = [
                    
                ];
            @endphp

            <x-select name="type" label="Employee Type :" :options="$employeeTypeOptions" :value="old('type')" formLabelClass="form-label" formSelectClass="form-select @error('type') is-invalid @enderror" />

            
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="id" label="Employee ID :" type="text" value="{{ old('id') }}" formLabelClass="form-label" formInputClass="form-control @error('id') is-invalid @enderror" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            @php
                $employeeGroupOptions = [
                    
                ];
            @endphp 

            <x-select name="group" label="Employee Group :" :options="$employeeGroupOptions" :value="old('group')" formLabelClass="form-label" formSelectClass="form-select @error('group') is-invalid @enderror" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            @php
                $designationOptions = [
                    
                ];
            @endphp

            <x-select name="designation" label="Designation :" :options="$designationOptions" :value="old('designation')" formLabelClass="form-label" formSelectClass="form-select @error('designation') is-invalid @enderror" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="userid" label="User ID :" type="text" value="{{ old('userid') }}" formLabelClass="form-label" formInputClass="form-control @error('userid') is-invalid @enderror" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            @php
                $sectionOptions = [
                    
                ];
            @endphp

            <x-select name="section" label="Section :" :options="$sectionOptions" :value="old('section')" formLabelClass="form-label" formSelectClass="form-select @error('section') is-invalid @enderror" />

        </div>
    </div>
</div>