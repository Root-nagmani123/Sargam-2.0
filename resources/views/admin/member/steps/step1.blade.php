<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="title">Title :</label>
            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                   id="title" name="title" placeholder="Mr./Ms./Dr./Prof. etc." 
                   value="{{ old('title') }}">
            @error('title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="first_name">First Name :</label>
            <input type="text" class="form-control @error('first_name') is-invalid @enderror" 
                   id="first_name" name="first_name" value="{{ old('first_name') }}">
            @error('first_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="middle_name">Middle Name :</label>
            <input type="text" class="form-control @error('middle_name') is-invalid @enderror" 
                   id="middle_name" name="middle_name" value="{{ old('middle_name') }}">
            @error('middle_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="last_name">Last Name :</label>
            <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                   id="last_name" name="last_name" value="{{ old('last_name') }}">
            @error('last_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="father_husband_name">Father's/Husband's Name :</label>
            <input type="text" class="form-control @error('father_husband_name') is-invalid @enderror" 
                   id="father_husband_name" name="father_husband_name" value="{{ old('father_husband_name') }}">
            @error('father_husband_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="marital_status">Marital Status :</label>
            <select class="form-select @error('marital_status') is-invalid @enderror" 
                    id="marital_status" name="marital_status">
                <option value="">Select</option>
                <option value="single" {{ old('marital_status') == 'single' ? 'selected' : '' }}>Single</option>
                <option value="married" {{ old('marital_status') == 'married' ? 'selected' : '' }}>Married</option>
                <option value="other" {{ old('marital_status') == 'other' ? 'selected' : '' }}>Other</option>
            </select>
            @error('marital_status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="gender">Gender :</label>
            <select class="form-select @error('gender') is-invalid @enderror" 
                    id="gender" name="gender">
                <option value="">Select</option>
                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
            </select>
            @error('gender')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="caste_category">Caste Category :</label>
            <select class="form-select @error('caste_category') is-invalid @enderror" 
                    id="caste_category" name="caste_category">
                <option value="">Select</option>
                <option value="general" {{ old('caste_category') == 'general' ? 'selected' : '' }}>General</option>
                <option value="obc" {{ old('caste_category') == 'obc' ? 'selected' : '' }}>OBC</option>
                <option value="sc" {{ old('caste_category') == 'sc' ? 'selected' : '' }}>SC</option>
                <option value="st" {{ old('caste_category') == 'st' ? 'selected' : '' }}>ST</option>
                <option value="ews" {{ old('caste_category') == 'ews' ? 'selected' : '' }}>EWS</option>
            </select>
            @error('caste_category')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="height">Exact Height by Measurement (Without Shoes):</label>
            <input type="text" class="form-control @error('height') is-invalid @enderror" 
                   id="height" name="height" value="{{ old('height') }}">
            @error('height')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="date_of_birth">Date of Birth :</label>
            <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" 
                   id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth') }}">
            @error('date_of_birth')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>