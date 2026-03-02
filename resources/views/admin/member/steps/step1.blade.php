<div class="row g-3">
    <div class="col-12">
        <p class="text-body-secondary small mb-0 fw-medium">Personal information</p>
        <hr class="my-2">
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            @php $titleOptions = App\Models\EmployeeMaster::title; @endphp
            <x-select name="title" label="Title" :options="$titleOptions" :value="old('title')" formLabelClass="form-label fw-medium" formSelectClass="form-select" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <x-input name="first_name" label="First Name" type="text" value="{{ old('first_name') }}" formLabelClass="form-label fw-medium" formInputClass="form-control only-letters" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <x-input name="middle_name" label="Middle Name" type="text" value="{{ old('middle_name') }}" formLabelClass="form-label fw-medium" formInputClass="form-control only-letters" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <x-input name="last_name" label="Last Name" type="text" value="{{ old('last_name') }}" formLabelClass="form-label fw-medium" formInputClass="form-control only-letters" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <x-input name="father_husband_name" label="Father's / Husband's Name" type="text" value="{{ old('father_husband_name') }}" formLabelClass="form-label fw-medium" formInputClass="form-control only-letters" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            @php $maritalStatusOptions = App\Models\EmployeeMaster::maritalStatus; @endphp
            <x-select name="marital_status" label="Marital Status" :options="$maritalStatusOptions" :value="old('marital_status')" formLabelClass="form-label fw-medium" formSelectClass="form-select" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            @php $genderOptions = App\Models\EmployeeMaster::gender; @endphp
            <x-select name="gender" label="Gender" :options="$genderOptions" :value="old('gender')" formLabelClass="form-label fw-medium" formSelectClass="form-select" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            @php $casteCategory = App\Models\CasteCategoryMaster::GetSeatName(); @endphp
            <x-select name="caste_category" label="Caste Category" :options="$casteCategory" :value="old('caste_category')" formLabelClass="form-label fw-medium" formSelectClass="form-select" labelRequired="true" />
        </div>
    </div>
    <div class="col-12 mt-2">
        <p class="text-body-secondary small mb-0 fw-medium">Other details</p>
        <hr class="my-2">
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <x-input name="height" label="Height (without shoes)" type="text" value="{{ old('height') }}" formLabelClass="form-label fw-medium" formInputClass="form-control only-numbers" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <x-input name="date_of_birth" label="Date of Birth" type="date" value="{{ old('date_of_birth') }}" formLabelClass="form-label fw-medium" formInputClass="form-control" labelRequired="true" />
        </div>
    </div>
</div>