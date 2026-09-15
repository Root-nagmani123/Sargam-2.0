<div class="mbrw-section">
    <h6 class="mbrw-section-title">Additional Details</h6>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <x-input name="residencenumber" formLabelClass="form-label" label="Residence Number" id="residencenumber" placeholder="eg. 123456" value="{{ old('residencenumber') }}" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-input name="homeaddress" formLabelClass="form-label" label="Home Address Data" id="homeaddress" placeholder="eg. 3005 Cranberry, Wareham MA" value="{{ old('homeaddress') }}" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="picture">Upload Picture</label>
            <input type="file" class="form-control" id="picture" name="picture">
            <small class="text-muted d-block mt-1">
                Allowed file types: <strong>JPG</strong>, <strong>PNG</strong>, <strong>JPEG</strong> | Max file size: <strong>500 KB</strong>
            </small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="additionaldocument">Additional Document Upload</label>
            <input type="file" class="form-control" id="additionaldocument" name="additionaldocument">
            <small class="text-muted d-block mt-1">
                Allowed file types: <strong>PDF</strong>, <strong>JPG</strong>, <strong>PNG</strong> | Max file size: <strong>1 MB</strong>
            </small>
        </div>
    </div>
    <div class="col-12">
        <div class="mb-3">
            <label class="form-label" for="miscellaneous">Additional Message</label>
            <textarea name="miscellaneous" id="miscellaneous" rows="4" class="form-control"
                placeholder="Any other information about this employee">{{ old('miscellaneous') }}</textarea>
        </div>
    </div>
</div>
