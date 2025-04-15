<div class="row">
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="title" label="Title :" type="text" value="{{ old('title') }}" formLabelClass="form-label" formInputClass="form-control @error('title') is-invalid @enderror" />
            
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="first_name" label="First Name :" type="text" value="{{ old('first_name') }}" formLabelClass="form-label" formInputClass="form-control @error('first_name') is-invalid @enderror" />
            
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="middle_name" label="Middle Name :" type="text" value="{{ old('middle_name') }}" formLabelClass="form-label" formInputClass="form-control @error('middle_name') is-invalid @enderror" />
            
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="last_name" label="Last Name :" type="text" value="{{ old('last_name') }}" formLabelClass="form-label" formInputClass="form-control @error('last_name') is-invalid @enderror" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="father_husband_name" label="Father's/Husband's Name :" type="text" value="{{ old('father_husband_name') }}" formLabelClass="form-label" formInputClass="form-control @error('father_husband_name') is-invalid @enderror" />
            
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            @php
                $maritalStatusOptions = [
                    'single' => 'Single',
                    'married' => 'Married',
                    'other' => 'Other'
                ];
            @endphp

            <x-select name="marital_status" label="Marital Status :" :options="$maritalStatusOptions" :value="old('marital_status')" formLabelClass="form-label" formSelectClass="form-select @error('marital_status') is-invalid @enderror" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            @php
                $genderOptions = [
                    'male' => 'Male',
                    'female' => 'Female',
                    'other' => 'Other'
                ];
            @endphp

            <x-select name="gender" label="Gender :" :options="$genderOptions" :value="old('gender')" formLabelClass="form-label" formSelectClass="form-select @error('gender') is-invalid @enderror" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            @php
                $casteCategoryOptions = [
                    'general' => 'General',
                    'obc' => 'OBC',
                    'sc' => 'SC',
                    'st' => 'ST',
                    'ews' => 'EWS'
                ];
            @endphp

            <x-select name="caste_category" label="Caste Category :" :options="$casteCategoryOptions" :value="old('caste_category')" formLabelClass="form-label" formSelectClass="form-select @error('caste_category') is-invalid @enderror" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="height" label="Exact Height by Measurement (Without Shoes):" type="text" value="{{ old('height') }}" formLabelClass="form-label" formInputClass="form-control @error('height') is-invalid @enderror" />
            
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="date_of_birth" label="Date of Birth :" type="date" value="{{ old('date_of_birth') }}" formLabelClass="form-label" formInputClass="form-control @error('date_of_birth') is-invalid @enderror" />
            
        </div>
    </div>
</div>