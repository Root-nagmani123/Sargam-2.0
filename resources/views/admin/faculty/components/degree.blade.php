@php
    $qualifications = $faculty->facultyQualificationMap ?? collect();
@endphp

@if($qualifications->isNotEmpty())
    @foreach ($qualifications as $qualification)
        <div class="row degree-row g-3 align-items-end mb-3">
            <div class="col-12 col-md-6">
                <x-input name="degree[]" label="Degree :" placeholder="eg. B.Tech" formLabelClass="form-label"
                    helperSmallText="Bachelors, Masters, PhD" value="{{ $qualification->Degree_name }}" />
            </div>
            <div class="col-12 col-md-6">
                <x-input name="university_institution_name[]" label="University/Institution Name :"
                    placeholder="eg. Delhi University" formLabelClass="form-label"
                    value="{{ $qualification->University_Institution_Name }}" />
            </div>
            <div class="col-12 col-md-6">
                <x-select name="year_of_passing[]" label="Year of Passing :" placeholder="Select Year"
                    formLabelClass="form-label" :options="$years" helperSmallText="Select the year of passing"
                    value="{{ $qualification->Year_of_passing }}" />
            </div>
            <div class="col-12 col-md-6">
                <x-input type="number" min="0" max="100" name="percentage_CGPA[]" label="Percentage/CGPA"
                    placeholder="eg. 6.6" formLabelClass="form-label"
                    value="{{ $qualification->Percentage_CGPA }}" />
            </div>
            <div class="col-12 col-md-6">
                <x-input type="file" name="certificate[]" label="Certificates/Documents Upload :"
                    placeholder="Certificates/Documents Upload" formLabelClass="form-label"
                    helperSmallText="Please upload your certificates/documents, if any" />
                @if(!empty($qualification->Certifcates_upload_path))
                    <a href="{{ asset('storage/'.$qualification->Certifcates_upload_path) }}" target="_blank" class="d-inline-block mt-1">
                        <i class="bi bi-eye text-primary"></i> View certificate
                    </a>
                @endif
            </div>
        </div>
    @endforeach
    <div id="education_fields"></div>
    <div class="d-flex justify-content-end mt-2">
        <button onclick="education_fields();" class="btn fw-btn-add" type="button" title="Add qualification">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
        </button>
    </div>
@else
    <div class="row degree-row g-3 align-items-end" id="education_fields">
        <div class="col-12 col-md-6">
            <x-input name="degree[]" label="Degree :" placeholder="eg. B.Tech" formLabelClass="form-label"
                helperSmallText="Bachelors, Masters, PhD" />
        </div>
        <div class="col-12 col-md-6">
            <x-input name="university_institution_name[]" label="University/Institution Name :"
                placeholder="eg. Delhi University" formLabelClass="form-label" />
        </div>
        <div class="col-12 col-md-6">
            <x-select name="year_of_passing[]" label="Year of Passing :" placeholder="Select Year"
                formLabelClass="form-label" :options="$years" helperSmallText="Select the year of passing" />
        </div>
        <div class="col-12 col-md-6">
            <x-input type="number" min="0" max="100" name="percentage_CGPA[]" label="Percentage/CGPA"
                placeholder="eg. 6.6" formLabelClass="form-label" />
        </div>
        <div class="col-12 col-md-6">
            <x-input type="file" name="certificate[]" label="Certificates/Documents Upload :"
                placeholder="Certificates/Documents Upload" formLabelClass="form-label"
                helperSmallText="Please upload your certificates/documents, if any" />
        </div>
        <div class="col-auto ms-md-auto">
            <label class="form-label d-none d-md-block">&nbsp;</label>
            <div class="mb-3">
                <button onclick="education_fields();" class="btn fw-btn-add" type="button" title="Add qualification">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>
@endif
