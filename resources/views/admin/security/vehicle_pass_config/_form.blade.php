@php $isEdit = isset($config); @endphp
<div class="modal-header border-bottom">
    <h5 class="modal-title fw-bold mb-0">{{ $isEdit ? 'Edit' : 'Add' }} Vehicle Pass Configuration</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<form id="configForm"
      action="{{ $isEdit ? route('admin.security.vehicle_pass_config.update', encrypt($config->pk)) : route('admin.security.vehicle_pass_config.store') }}"
      method="POST" novalidate>
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="modal-body">
        <div id="configFormAlert" class="alert d-none mb-3" role="alert"></div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="sec_vehicle_type_pk" class="form-label fw-semibold">Vehicle Type <span class="text-danger">*</span></label>
                <select name="sec_vehicle_type_pk" id="sec_vehicle_type_pk" class="form-select" required>
                    <option value="">--- Select Vehicle Type ---</option>
                    @foreach($vehicleTypes as $vt)
                        <option value="{{ $vt->pk }}" {{ (int) ($config->sec_vehicle_type_pk ?? 0) === (int) $vt->pk ? 'selected' : '' }}>
                            {{ $vt->vehicle_type }}
                        </option>
                    @endforeach
                </select>
                <div class="invalid-feedback" data-field="sec_vehicle_type_pk"></div>
            </div>

            <div class="col-md-6">
                <label for="charges" class="form-label fw-semibold">Charges (₹) <span class="text-danger">*</span></label>
                <input type="number" name="charges" id="charges" class="form-control"
                       value="{{ $config->charges ?? '' }}" step="0.01" min="0" placeholder="Enter charges" required>
                <div class="invalid-feedback" data-field="charges"></div>
            </div>

            <div class="col-md-6">
                <label for="start_counter" class="form-label fw-semibold">Start Counter <span class="text-danger">*</span></label>
                <input type="number" name="start_counter" id="start_counter" class="form-control"
                       value="{{ $config->start_counter ?? 1 }}" min="1" placeholder="Enter start counter" required>
                <div class="invalid-feedback" data-field="start_counter"></div>
                <small class="form-text text-muted">Starting number for vehicle pass IDs.</small>
            </div>

            <div class="col-md-6">
                <label for="configPreview" class="form-label fw-semibold">Preview</label>
                <input type="text" id="configPreview" class="form-control bg-light" readonly
                       value="VP{{ now()->format('Ymd') }}{{ str_pad((string) ($config->start_counter ?? 1), 4, '0', STR_PAD_LEFT) }}">
                <small class="form-text text-muted">Preview of the vehicle pass ID format.</small>
            </div>
        </div>
    </div>

    <div class="modal-footer border-0 gap-2 justify-content-end">
        <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary rounded-1 px-4" id="configSubmitBtn">{{ $isEdit ? 'Update' : 'Save Configuration' }}</button>
    </div>
</form>

<script>
(function () {
    var datePrefix = @json(now()->format('Ymd'));
    var $form = $('#configForm');
    var $alert = $('#configFormAlert');

    // Live preview from the start counter.
    $('#start_counter').on('input', function () {
        var counter = parseInt(this.value, 10);
        if (isNaN(counter) || counter < 1) { counter = 1; }
        $('#configPreview').val('VP' + datePrefix + String(counter).padStart(4, '0'));
    });

    $form.on('submit', function (e) {
        e.preventDefault();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');
        $alert.addClass('d-none').removeClass('alert-danger alert-success').empty();

        var $btn = $('#configSubmitBtn');
        var original = $btn.text();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (response) {
                $alert.removeClass('d-none alert-danger').addClass('alert-success')
                      .html('<i class="bi bi-check-circle me-1"></i>' + (response.message || 'Saved successfully.'));

                if (window.jQuery && $.fn.DataTable.isDataTable('#vehiclePassConfig-table')) {
                    $('#vehiclePassConfig-table').DataTable().ajax.reload(null, false);
                }

                setTimeout(function () {
                    bootstrap.Modal.getInstance(document.getElementById('vehiclePassConfigModal'))?.hide();
                }, 900);
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(function (field) {
                        $form.find('[name="' + field + '"]').addClass('is-invalid');
                        $form.find('.invalid-feedback[data-field="' + field + '"]').text(errors[field][0]);
                    });
                } else {
                    $alert.removeClass('d-none alert-success').addClass('alert-danger')
                          .html('<i class="bi bi-exclamation-circle me-1"></i>' + ((xhr.responseJSON && xhr.responseJSON.message) || 'An error occurred while saving.'));
                }
            },
            complete: function () {
                $btn.prop('disabled', false).text(original);
            }
        });
    });
})();
</script>
