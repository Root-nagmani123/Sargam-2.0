<div class="row g-3 mw-step-grid">
    <div class="col-md-6">
        <div class="mb-3">
            <x-input name="homeaddress" formLabelClass="form-label" label="Home Address Data (Optional)" id="homeaddress" value="{{ old('homeaddress') }}" placeholder="Optional home address notes" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-input name="residencenumber" formLabelClass="form-label" label="Residence Number" id="residencenumber" value="{{ old('residencenumber') }}" placeholder="eg. H-12, Block A" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-input name="miscellaneous" formLabelClass="form-label" label="Other Miscellaneous Fields" id="miscellaneous" value="{{ old('miscellaneous') }}" placeholder="Any additional notes" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="picture">Upload Picture</label>
            <input type="file" class="form-control" id="picture" name="picture" accept=".jpg,.jpeg,.png">
        </div>
        <small class="text-muted d-block mb-3">
            Allowed file types: <strong>JPG</strong>, <strong>PNG</strong>, <strong>JPEG</strong> | Max file size: <strong>500 KB</strong>
        </small>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="additionaldocument">Additional Document Upload</label>
            <input type="file" class="form-control" id="additionaldocument" name="additionaldocument" accept=".pdf,.jpg,.jpeg,.png">
        </div>
        <small class="text-muted d-block">
            Allowed file types: <strong>PDF</strong>, <strong>PNG</strong>, <strong>JPG</strong> | Max file size: <strong>1 MB</strong>
        </small>
    </div>
</div>
