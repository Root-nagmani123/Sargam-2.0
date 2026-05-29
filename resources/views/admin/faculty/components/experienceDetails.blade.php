@php
    $experiences = $faculty->facultyExperienceMap ?? collect();
@endphp

@if($experiences->isNotEmpty())
    @foreach ($experiences as $experience)
        <div class="row experience-row g-3 align-items-end mb-3">
            <div class="col-12 col-md-6">
                <x-input name="experience[]" label="Years of Experience :" placeholder="Years of Experience"
                    formLabelClass="form-label" value="{{ $experience->Years_Of_Experience }}" />
            </div>
            <div class="col-12 col-md-6">
                <x-input name="specialization[]" label="Area of Specialization :" placeholder="Area of Specialization"
                    formLabelClass="form-label" value="{{ $experience->Specialization }}" />
            </div>
            <div class="col-12 col-md-6">
                <x-input name="institution[]" label="Previous Institutions :" placeholder="Previous Institutions"
                    formLabelClass="form-label" value="{{ $experience->pre_Institutions }}" />
            </div>
            <div class="col-12 col-md-6">
                <x-input name="position[]" label="Position Held :" placeholder="Position Held" formLabelClass="form-label"
                    value="{{ $experience->Position_hold }}" />
            </div>
            <div class="col-12 col-md-6">
                <x-input type="number" name="duration[]" label="Duration :" placeholder="Duration"
                    formLabelClass="form-label" min="0" value="{{ $experience->duration }}" />
            </div>
            <div class="col-12 col-md-6">
                <x-input name="work[]" label="Nature of Work :" placeholder="Nature of Work" formLabelClass="form-label"
                    value="{{ $experience->Nature_of_Work }}" />
            </div>
        </div>
    @endforeach
    <div id="experience_fields"></div>
    <div class="d-flex justify-content-end mt-2">
        <button onclick="experience_fields();" class="btn fw-btn-add" type="button" title="Add experience">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
        </button>
    </div>
@else
    <div class="row experience-row g-3 align-items-end" id="experience_fields">
        <div class="col-12 col-md-6">
            <x-input name="experience[]" label="Years of Experience :" placeholder="Years of Experience"
                formLabelClass="form-label" />
        </div>
        <div class="col-12 col-md-6">
            <x-input name="specialization[]" label="Area of Specialization :" placeholder="Area of Specialization"
                formLabelClass="form-label" />
        </div>
        <div class="col-12 col-md-6">
            <x-input name="institution[]" label="Previous Institutions :" placeholder="Previous Institutions"
                formLabelClass="form-label" />
        </div>
        <div class="col-12 col-md-6">
            <x-input name="position[]" label="Position Held :" placeholder="Position Held" formLabelClass="form-label" />
        </div>
        <div class="col-12 col-md-6">
            <x-input type="number" name="duration[]" label="Duration :" placeholder="Duration"
                formLabelClass="form-label" min="0" />
        </div>
        <div class="col-12 col-md-6">
            <x-input name="work[]" label="Nature of Work :" placeholder="Nature of Work" formLabelClass="form-label" />
        </div>
        <div class="col-auto ms-md-auto">
            <label class="form-label d-none d-md-block">&nbsp;</label>
            <div class="mb-3">
                <button onclick="experience_fields();" class="btn fw-btn-add" type="button" title="Add experience">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>
@endif
