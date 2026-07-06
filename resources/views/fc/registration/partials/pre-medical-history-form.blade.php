{{-- Legacy Step 3 only (no form-builder groups). Expects: $preMedical, $preMedicalCourse --}}
@php $layout = $preMedicalFormLayout ?? 'legacy'; @endphp
<form method="POST" action="{{ route('fc-reg.registration.step3.pre-medical-history') }}" enctype="multipart/form-data">
    @csrf
    @if($errors->hasAny(['allergy_illness', 'prolonged_medication', 'hospital_history', 'altitude_illness', 'additional_info', 'pre_med_doc']))
        <div class="alert alert-danger py-2 small mb-3">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    <p class="text-muted small mb-3">
        Declarations for post-arrival medical processing. Course is taken from your Step&nbsp;1 session selection
        @if(!empty($preMedicalCourse))(<strong>{{ $preMedicalCourse }}</strong>)@endif.
    </p>
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label small fw-semibold">History of allergy / previous illness / injury / disability / asthma / slip disc / blood transfusion</label>
            <textarea name="allergy_illness" class="form-control" rows="2" maxlength="60000">{{ old('allergy_illness', $preMedical?->allergy_illness) }}</textarea>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Any history of prolonged medication intake</label>
            <textarea name="prolonged_medication" class="form-control" rows="2" maxlength="60000">{{ old('prolonged_medication', $preMedical?->prolonged_medication) }}</textarea>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Hospital admission / operation / injury (nature and duration, if any)</label>
            <textarea name="hospital_history" class="form-control" rows="2" maxlength="60000">{{ old('hospital_history', $preMedical?->hospital_history) }}</textarea>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">High-altitude illness (place and problem, if any)</label>
            <textarea name="altitude_illness" class="form-control" rows="2" maxlength="60000">{{ old('altitude_illness', $preMedical?->altitude_illness) }}</textarea>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Any other significant information about health status</label>
            <textarea name="additional_info" class="form-control" rows="2" maxlength="60000">{{ old('additional_info', $preMedical?->additional_info) }}</textarea>
        </div>
        <div class="col-12">
            <label class="form-label small fw-semibold">Supporting document (PDF or image)</label>
            <input type="file" name="pre_med_doc"
                   class="form-control form-control-sm fc-file-upload"
                   accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                   data-max-kb="5120">
            <div id="pre-med-doc-error" class="text-danger small mt-1"></div>
            <div class="form-text text-muted">{{ fc_file_upload_hint('nullable|file|mimes:pdf,jpg,jpeg,png|max:10240') }}</div>
            @if($preMedical?->doc_path)
                <div class="small mt-1">Current file: <a href="{{ asset($preMedical->doc_path) }}" target="_blank" rel="noopener">View</a></div>
            @endif
        </div>
    </div>
    @if($layout === 'dynamic')
        <div class="d-flex justify-content-between align-items-center mt-4">
            <a href="{{ route('fc-reg.registration.step2') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to Step 2
            </a>
            <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-save me-1"></i>Save pre-medical history</button>
        </div>
    @else
        <div class="mt-3 text-end">
            <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Save pre-medical history</button>
        </div>
    @endif
</form>
<script>
(function () {
    var input = document.querySelector('input[name="pre_med_doc"].fc-file-upload');
    if (!input) return;
    var maxKb = parseInt(input.getAttribute('data-max-kb'), 10) || 5120;
    var allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
    var errEl = document.getElementById('pre-med-doc-error');

    function validate(file) {
        if (!file) { errEl.textContent = ''; return true; }
        var ext = file.name.split('.').pop().toLowerCase();
        if (allowedExts.indexOf(ext) === -1) {
            errEl.textContent = 'Invalid file type. Allowed: PDF, JPG, PNG.';
            input.classList.add('is-invalid');
            input.value = '';
            return false;
        }
        if (file.size > maxKb * 1024) {
            var limit = maxKb >= 1024 ? (maxKb / 1024) + ' MB' : maxKb + ' KB';
            errEl.textContent = 'File is too large. Maximum allowed size is ' + limit + '.';
            input.classList.add('is-invalid');
            input.value = '';
            return false;
        }
        errEl.textContent = '';
        input.classList.remove('is-invalid');
        return true;
    }

    input.addEventListener('change', function () { validate(this.files[0]); });

    var form = input.closest('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (input.files && input.files.length && !validate(input.files[0])) {
                e.preventDefault();
                input.focus();
            }
        });
    }
}());
</script>
