<div class="row g-3">
    <div class="col-12">
        <p class="text-body-secondary small mb-0 fw-medium">Optional &amp; additional details</p>
        <hr class="my-2">
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <x-input name="homeaddress" formLabelClass="form-label fw-medium" label="Home Address (Optional)" id="homeaddress" value="{{ old('homeaddress') }}" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <x-input name="residencenumber" formLabelClass="form-label fw-medium" label="Residence Number" id="residencenumber" value="{{ old('residencenumber') }}" labelRequired="true" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <x-input name="miscellaneous" formLabelClass="form-label fw-medium" label="Other Miscellaneous Fields" id="miscellaneous" value="{{ old('miscellaneous') }}" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <label class="form-label fw-medium" for="picture">Upload Picture</label>
            <input type="file" class="form-control" id="picture" name="picture" accept=".jpg,.jpeg,.png">
            <small class="text-body-secondary small d-block mt-1">JPG, PNG, JPEG · Max 500 KB</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-0">
            <label class="form-label fw-medium" for="additionaldocument">Additional Document</label>
            <input type="file" class="form-control" id="additionaldocument" name="additionaldocument" accept=".pdf,.jpg,.jpeg,.png">
            <small class="text-body-secondary small d-block mt-1">PDF, JPG, PNG · Max 1 MB</small>
        </div>
    </div>
</div>