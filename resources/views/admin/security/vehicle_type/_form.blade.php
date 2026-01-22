<div class="modal-header">
    <h5 class="modal-title">{{ isset($vehicleType) ? 'Edit' : 'Add' }} Vehicle Type</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<form id="vehicleTypeForm" method="POST" action="{{ isset($vehicleType) ? route('admin.security.vehicle_type.update', encrypt($vehicleType->pk)) : route('admin.security.vehicle_type.store') }}">
    @csrf
    <div class="modal-body">
        <div class="mb-3">
            <label for="vehicle_type" class="form-label">Vehicle Type <span class="text-danger">*</span></label>
            <input type="text" name="vehicle_type" id="vehicle_type" class="form-control" 
                value="{{ old('vehicle_type', $vehicleType->vehicle_type ?? '') }}" 
                placeholder="Enter vehicle type (e.g., Car, Bike, Scooter)" required maxlength="100">
            <small class="form-text text-muted">Enter the vehicle type name</small>
        </div>
        
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" id="description" class="form-control" rows="3" 
                placeholder="Enter description (optional)">{{ old('description', $vehicleType->description ?? '') }}</textarea>
            <small class="form-text text-muted">Optional description for the vehicle type</small>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">close</i>
            Cancel
        </button>
        <button type="submit" class="btn btn-success">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle;">{{ isset($vehicleType) ? 'update' : 'save' }}</i>
            {{ isset($vehicleType) ? 'Update' : 'Save' }}
        </button>
    </div>
</form>

<script>
$(document).ready(function() {
    $('#vehicleTypeForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    $('#vehicleTypeModal').modal('hide');
                    toastr.success(response.action === 'create' ? 'Vehicle Type created successfully' : 'Vehicle Type updated successfully');
                    setTimeout(() => location.reload(), 1000);
                }
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html(originalText);
                
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMsg = '';
                    Object.keys(errors).forEach(key => {
                        errorMsg += errors[key][0] + '<br>';
                    });
                    toastr.error(errorMsg);
                } else {
                    toastr.error(xhr.responseJSON?.message || 'An error occurred');
                }
            }
        });
    });
});
</script>
