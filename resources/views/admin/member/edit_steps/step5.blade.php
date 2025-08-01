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

            <x-input name="miscellaneous" formLabelClass="form-label" label="Other Miscellaneous Fields :" id="miscellaneous" value="{{ $member->other_miscellaneous_fields ?? old('miscellaneous') }}" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="picture">Upload Picture :</label>
            <input type="file" class="form-control" id="picture" name="picture">
        </div>
        <small class="text-muted d-block">
            Allowed file types: <strong>JPG</strong>, <strong>PNG</strong>, <strong>JPEG</strong> | Max file size: <strong>500 KB</strong>
        </small>
        {!! $member->profile_picture ? '<a href="' . asset('storage/' . $member->profile_picture) . '" target="_blank" class="btn btn-primary">View Picture</a>' : '' !!}
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="additionaldocument">Additional Document Upload :</label>
            <input type="file" class="form-control" id="additionaldocument" name="additionaldocument">
        </div>
        <small class="text-muted d-block">
            Allowed file types: <strong>PDF</strong>, <strong>JPG</strong>, <strong>PNG</strong> | Max file size: <strong>1 MB</strong>
        </small>
        {!! $member->additional_doc_upload ? '<a href="' . asset('storage/' . $member->additional_doc_upload) . '" target="_blank" class="btn btn-primary">View Document</a>' : '' !!}
    </div>
</div>