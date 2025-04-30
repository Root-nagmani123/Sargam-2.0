<div>
    <h4 class="card-subtitle mb-3 mt-3">Experience Details</h4>
    <hr>

    @php
        $experiences = $faculty->facultyExperienceMap ?? collect();
    @endphp

    @foreach ($experiences as $experience)
    <div class="row mb-3 experience_group">
        <div class="col-3">
            <x-input
                name="experience[]"
                label="Years of Experience :"
                placeholder="Years of Experience"
                formLabelClass="form-label"
                required="true"
                value="{{ $experience->Years_Of_Experience }}"
            />
        </div>
        <div class="col-3">
            <x-input
                name="specialization[]"
                label="Area of Specialization :"
                placeholder="Area of Specialization"
                formLabelClass="form-label"
                required="true"
                value="{{ $experience->Specialization }}"
            />
        </div>
        <div class="col-3">
            <x-input
                name="institution[]"
                label="Previous Institutions :"
                placeholder="Previous Institutions"
                formLabelClass="form-label"
                required="true"
                value="{{ $experience->pre_Institutions }}"
            />
        </div>
        <div class="col-3">
            <x-input
                name="position[]"
                label="Position Held :"
                placeholder="Position Held"
                formLabelClass="form-label"
                required="true"
                value="{{ $experience->Position_hold }}"
            />
        </div>
        <div class="col-3 mt-3">
            <x-input
                type="number"
                name="duration[]"
                label="Duration :"
                placeholder="Duration"
                formLabelClass="form-label"
                min="0"
                required="true"
                value="{{ $experience->duration }}"
            />
        </div>
        <div class="col-3 mt-3">
            <x-input
                name="work[]"
                label="Nature of Work :"
                placeholder="Nature of Work"
                formLabelClass="form-label"
                required="true"
                value="{{ $experience->Nature_of_Work }}"
            />
        </div>
    </div>
    @endforeach

    {{-- Add button for appending new rows dynamically --}}
    <div class="col-6">
        <label for="Schoolname" class="form-label"></label>
        <div class="mb-3 float-end">
            <button onclick="experience_fields();" class="btn btn-success btn-sm" type="button">
                <i class="material-icons menu-icon">add</i>
            </button>
        </div>
    </div>
</div>
