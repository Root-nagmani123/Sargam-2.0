<div>

    <div id="education_fields" class="my-4"></div>
    @php
        $qualifications = $faculty->facultyQualificationMap ?? collect();
    @endphp
    @if(!empty($qualifications->isNotEmpty()))
        
        @foreach ($qualifications as $qualification)
            <div class="row mb-3 education_group">
                <div class="col-3">
                    <x-input name="degree[]" label="Degree :" placeholder="Degree" formLabelClass="form-label" required="true"
                        helperSmallText="Bachelors, Masters, PhD" value="{{ $qualification->Degree_name }}" />
                </div>

                <div class="col-3">
                    <x-input name="university_institution_name[]" label="University/Institution Name :"
                        placeholder="University/Institution Name" formLabelClass="form-label" required="true"
                        value="{{ $qualification->University_Institution_Name }}" />
                </div>

                <div class="col-3">
                    {{-- <x-input type="number" name="year_of_passing[]" label="Year of Passing :" placeholder="Year of Passing"
                        formLabelClass="form-label" min="1900" max="{{ date('Y') }}" step="1" required="true"
                        value="{{ $qualification->Year_of_passing }}" /> --}}
                        <x-select
                                        name="year_of_passing[]"
                                        label="Year of Passing :"
                                        placeholder="Year of Passing"
                                        formLabelClass="form-label"
                                        :options="$years"
                                        required="true"
                                        helperSmallText="Select the year of passing"
                                        value="{{ $qualification->Year_of_passing }}"
                                    />
                </div>

                <div class="col-3">
                    <x-input type="number" min="0" max="100" name="percentage_CGPA[]" label="Percentage/CGPA"
                        placeholder="Percentage/CGPA" formLabelClass="form-label" required="true"
                        value="{{ $qualification->Percentage_CGPA }}" />
                </div>

                <div class="col-3 mt-3">
                    <x-input type="file" name="certificate[]" label="Certificates/Documents Upload :"
                        placeholder="Certificates/Documents Upload" formLabelClass="form-label" required="false"
                        helperSmallText="Please upload your certificates/documents, if any" />

                    @if(!empty($qualification->Certifcates_upload_path))
                    
                        <a href="{{ asset('storage/'.$qualification->Certifcates_upload_path) }}" target="_blank">
                            <i class="material-icons text-info">visibility</i>
                        </a>
                    
                    @endif
                </div>
            </div>
        @endforeach
    @else
        <div class="row" id="education_fields">
            <div class="col-3">

                <x-input name="degree[]" label="Degree :" placeholder="Degree" formLabelClass="form-label" required="true"
                    helperSmallText="Bachelors, Masters, PhD" />

            </div>
            <div class="col-3">
                <x-input name="university_institution_name[]" label="University/Institution Name :"
                    placeholder="University/Institution Name" formLabelClass="form-label" required="true" />
            </div>
            <div class="col-3">
                <x-select
                                        name="year_of_passing[]"
                                        label="Year of Passing :"
                                        placeholder="Year of Passing"
                                        formLabelClass="form-label"
                                        :options="$years"
                                        required="true"
                                        helperSmallText="Select the year of passing"
                                        value="{{ $qualification->Year_of_passing }}"
                                    />
            </div>
            <div class="col-3">
                <x-input type="number" min="0" max="100" name="percentage_CGPA[]" label="Percentage/CGPA"
                    placeholder="Percentage/CGPA" formLabelClass="form-label" required="true" />

            </div>
            <div class="col-3 mt-3">

                <x-input type="file" name="certificate[]" label="Certificates/Documents Upload :"
                    placeholder="Certificates/Documents Upload" formLabelClass="form-label" required="true"
                    helperSmallText="Please upload your certificates/documents, if any" />


            </div>
            <div class="col-9">
                <label for="Schoolname" class="form-label"></label>
                <div class="mb-3 float-end">
                    <button onclick="education_fields();" class="btn btn-success fw-medium" type="button">
                        <i class="material-icons menu-icon">add</i>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Add Button --}}
    <div class="col-9">
        <label for="Schoolname" class="form-label"></label>
        <div class="mb-3 float-end">
            <button onclick="education_fields();" class="btn btn-success fw-medium" type="button">
                <i class="material-icons menu-icon">add</i>
            </button>
        </div>
    </div>
</div>