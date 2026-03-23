<div class="modal-header">
    <h5 class="modal-title">{{ isset($subType) ? 'Edit' : 'Add' }} Sub Type</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<form id="subTypeForm" method="POST" action="{{ isset($subType) ? route('admin.security.idcard_sub_type.update', encrypt($subType->pk)) : route('admin.security.idcard_sub_type.store') }}">
    @csrf
    <div class="modal-body">
        <div class="mb-3">
            <label for="sec_id_cardno_master" class="form-label">Card Type <span class="text-danger">*</span></label>
            <select name="sec_id_cardno_master" id="sec_id_cardno_master" class="form-select" required>
                <option value="">Select Card Type</option>
                @foreach($cardTypes as $id => $name)
                    <option value="{{ $id }}" {{ (string) old('sec_id_cardno_master', $subType->sec_id_cardno_master ?? '') === (string) $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="card_name" class="form-label">Employee Category <span class="text-danger">*</span></label>
            <select name="card_name" id="card_name" class="form-select" required>
                @php
                    $sel = old('card_name', $subType->card_name ?? '');
                @endphp
                <option value="">Select Category</option>
                <option value="p" {{ $sel === 'p' ? 'selected' : '' }}>Permanent Employee</option>
                <option value="c" {{ $sel === 'c' ? 'selected' : '' }}>Contractual Employee</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="config_name" class="form-label">Sub Type Name <span class="text-danger">*</span></label>
            <input type="text"
                   name="config_name"
                   id="config_name"
                   class="form-control"
                   value="{{ old('config_name', $subType->config_name ?? '') }}"
                   placeholder="Enter sub type (e.g., Officer, Staff, Vendor)"
                   maxlength="255"
                   required>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">close</i>
            Cancel
        </button>
        <button type="submit" class="btn btn-success">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">{{ isset($subType) ? 'update' : 'save' }}</i>
            {{ isset($subType) ? 'Update' : 'Save' }}
        </button>
    </div>
</form>

<script>
$(document).ready(function () {
    $('#subTypeForm').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalHtml = submitBtn.html();

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function (response) {
                if (response.success) {
                    $('#subTypeModal').modal('hide');
                    toastr.success(response.action === 'create'
                        ? 'Sub Type created successfully'
                        : 'Sub Type updated successfully');
                    setTimeout(() => location.reload(), 800);
                }
            },
            error: function (xhr) {
                submitBtn.prop('disabled', false).html(originalHtml);

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    let msg = '';
                    Object.values(xhr.responseJSON.errors).forEach(function (errArr) {
                        if (errArr.length) {
                            msg += errArr[0] + '<br>';
                        }
                    });
                    toastr.error(msg || 'Validation error.');
                } else {
                    toastr.error(xhr.responseJSON?.message || 'An error occurred while saving.');
                }
            }
        });
    });
});
</script>

