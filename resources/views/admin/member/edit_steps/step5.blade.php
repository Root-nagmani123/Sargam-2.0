<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <x-input name="homeaddress" formLabelClass="form-label" label="Home Address Data : (Optional)" id="homeaddress" value="{{ $member->home_town_details ?? old('homeaddress') }}" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="residencenumber" formLabelClass="form-label" label="Residence Number :" id="residencenumber" value="{{ $member->residence_no ?? old('residencenumber') }}" labelRequired="true" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="miscellaneous" formLabelClass="form-label" label="Other Miscellaneous Fields :" id="miscellaneous" value="{{ $member->other_miscellaneous_fields ?? old('miscellaneous') }}" labelRequired="true" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="picture">Upload Picture :</label>
            <input type="file" class="form-control" id="picture" name="picture">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="additionaldocument">Additional Document Upload :</label>
            <input type="file" class="form-control" id="additionaldocument" name="additionaldocument">
        </div>
    </div>
</div>