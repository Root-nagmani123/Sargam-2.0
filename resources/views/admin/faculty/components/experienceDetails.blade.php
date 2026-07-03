<div>
    @php
        $experiences = $faculty->facultyExperienceMap ?? collect();
    @endphp

    @if($experiences->isNotEmpty())
        @foreach ($experiences as $experience)
            <div class="row mb-3 experience_group">
                <div class="col-12 col-sm-6 col-md-3">
                    <x-input name="experience[]" label="Years of Experience :" placeholder="Years of Experience"
                        formLabelClass="form-label" value="{{ $experience->Years_Of_Experience }}" />
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <x-input name="specialization[]" label="Area of Specialization :" placeholder="Area of Specialization"
                        formLabelClass="form-label" value="{{ $experience->Specialization }}" />
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <x-input name="institution[]" label="Previous Institutions :" placeholder="Previous Institutions"
                        formLabelClass="form-label" value="{{ $experience->pre_Institutions }}" />
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <x-input name="position[]" label="Position Held :" placeholder="Position Held" formLabelClass="form-label"
                        value="{{ $experience->Position_hold }}" />
                </div>
                <div class="col-12 col-sm-6 col-md-3 mt-3">
                    <x-input type="number" name="duration[]" label="Duration :" placeholder="Duration"
                        formLabelClass="form-label" min="0" value="{{ $experience->duration }}" />
                </div>
                <div class="col-12 col-sm-6 col-md-3 mt-3">
                    <x-input name="work[]" label="Nature of Work :" placeholder="Nature of Work" formLabelClass="form-label"
                        value="{{ $experience->Nature_of_Work }}" />
                </div>
            </div>
        @endforeach
    @else
        <div class="row mb-3">
            <div class="col-12 col-sm-6 col-md-3">
                <x-input name="experience[]" label="Years of Experience :" placeholder="Years of Experience"
                    formLabelClass="form-label" />
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <x-input name="specialization[]" label="Area of Specialization :" placeholder="Area of Specialization"
                    formLabelClass="form-label" />
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <x-input name="institution[]" label="Previous Institutions :" placeholder="Previous Institutions"
                    formLabelClass="form-label" />
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <x-input name="position[]" label="Position Held :" placeholder="Position Held" formLabelClass="form-label" />
            </div>
            <div class="col-12 col-sm-6 col-md-3 mt-3">
                <x-input type="number" name="duration[]" label="Duration :" placeholder="Duration"
                    formLabelClass="form-label" min="0" />
            </div>
            <div class="col-12 col-sm-6 col-md-3 mt-3">
                <x-input name="work[]" label="Nature of Work :" placeholder="Nature of Work" formLabelClass="form-label" />
            </div>
        </div>
    @endif

    {{-- Target container for dynamically added experience rows --}}
    <div id="experience_fields"></div>

    {{-- Add / Remove Buttons --}}
    <div class="row">
        <div class="col-12">
            <div class="mb-3 float-end d-flex gap-2">
                <button onclick="remove_last_experience_field();" class="btn btn-danger btn-sm" type="button">
                    <i class="material-icons menu-icon">remove</i>
                </button>
                <button onclick="experience_fields();" class="btn btn-success btn-sm" type="button">
                    <i class="material-icons menu-icon">add</i>
                </button>
            </div>
        </div>
    </div>
</div>
