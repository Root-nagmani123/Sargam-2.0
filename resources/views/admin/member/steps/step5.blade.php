<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <x-input name="homeaddress" formLabelClass="form-label" label="Home Address Data : (Optional)" id="homeaddress" value="{{ old('homeaddress') }}"  />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="residencenumber" formLabelClass="form-label" label="Residence Number :" id="residencenumber" value="{{ old('residencenumber') }}" labelRequired="true" />

        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">

            <x-input name="miscellaneous" formLabelClass="form-label" label="Other Miscellaneous Fields :" id="miscellaneous" value="{{ old('miscellaneous') }}" labelRequired="true" />

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
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="additionaldocument">Additional Document Upload :</label>
            <input type="file" class="form-control" id="additionaldocument" name="additionaldocument">
        </div>
    </div>
    <small class="text-muted d-block">
        Allowed file types: <strong>PDF</strong>, <strong>JPG</strong>, <strong>PNG</strong> | Max file size: <strong>1 MB</strong>
    </small>
</div>