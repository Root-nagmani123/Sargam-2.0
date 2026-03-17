<div class="modal-header">
    <h5 class="modal-title">{{ isset($cardType) ? 'Edit' : 'Add' }} Card Type</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<form id="cardTypeForm" method="POST" action="{{ isset($cardType) ? route('admin.security.idcard_card_type.update', encrypt($cardType->pk)) : route('admin.security.idcard_card_type.store') }}">
    @csrf
    <div class="modal-body">
        <div class="mb-3">
            <label for="sec_card_name" class="form-label">Card Type Name <span class="text-danger">*</span></label>
            <input type="text"
                   name="sec_card_name"
                   id="sec_card_name"
                   class="form-control"
                   value="{{ old('sec_card_name', $cardType->sec_card_name ?? '') }}"
                   placeholder="Enter card type name (e.g., LBSNAA, CPWD)"
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
            <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">{{ isset($cardType) ? 'update' : 'save' }}</i>
            {{ isset($cardType) ? 'Update' : 'Save' }}
        </button>
    </div>
</form>

<script>
$(document).ready(function () {
    $('#cardTypeForm').on('submit', function (e) {
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
                    $('#cardTypeModal').modal('hide');
                    toastr.success(response.action === 'create'
                        ? 'Card Type created successfully'
                        : 'Card Type updated successfully');
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

