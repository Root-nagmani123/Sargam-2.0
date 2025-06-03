<div class="row">
    <div class="col-md-6">
        <div class="mb-3">

            @php $titleOptions = App\Models\EmployeeMaster::title; @endphp

            <x-select name="title" label="Title :" :options="$titleOptions" :value="old('title')" formLabelClass="form-label" formSelectClass="form-select" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="first_name" label="First Name :" type="text" value="{{ old('first_name') }}" formLabelClass="form-label" formInputClass="form-control" />
            
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="middle_name" label="Middle Name :" type="text" value="{{ old('middle_name') }}" formLabelClass="form-label" formInputClass="form-control" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="last_name" label="Last Name :" type="text" value="{{ old('last_name') }}" formLabelClass="form-label" formInputClass="form-control" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="father_husband_name" label="Father's/Husband's Name :" type="text" value="{{ old('father_husband_name') }}" formLabelClass="form-label" formInputClass="form-control" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php $maritalStatusOptions = App\Models\EmployeeMaster::maritalStatus; @endphp

            <x-select name="marital_status" label="Marital Status :" :options="$maritalStatusOptions" :value="old('marital_status')" formLabelClass="form-label" formSelectClass="form-select" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            @php $genderOptions = App\Models\EmployeeMaster::gender; @endphp

            <x-select name="gender" label="Gender :" :options="$genderOptions" :value="old('gender')" formLabelClass="form-label" formSelectClass="form-select" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">


            @php $casteCategory = App\Models\CasteCategoryMaster::GetSeatName(); @endphp
            <x-select name="caste_category" label="Caste Category :" :options="$casteCategory" :value="old('caste_category')" formLabelClass="form-label" formSelectClass="form-select" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="height" label="Exact Height by Measurement (Without Shoes):" type="text" value="{{ old('height') }}" formLabelClass="form-label" formInputClass="form-control" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="date_of_birth" label="Date of Birth :" type="date" value="{{ old('date_of_birth') }}" formLabelClass="form-label" formInputClass="form-control" />

        </div>
    </div>
</div>